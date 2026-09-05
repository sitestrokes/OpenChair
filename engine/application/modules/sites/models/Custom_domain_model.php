<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Custom_domain_model
 *
 * Attaches a client custom domain (CNAME'd to funnel.agenticagencyapp.com) to
 * a funnel by writing a per-domain folder + .htaccess into the addondomains
 * directory served by the wildcard VirtualDocumentRoot vhost. No Apache
 * reload required — the wildcard vhost picks up the folder automatically.
 *
 * Pattern adapted from NichesitesAI's Subdomain_model::addonDomain().
 */
class Custom_domain_model extends CI_Model
{
    private $addon_dir;
    private $proxy_target;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        // Load without the section flag so 'custom_domains' stays a top-level
        // key — $this->config->item($key, 'custom_domains') then works.
        $this->config->load('subdomain_config', FALSE, TRUE);

        $cfg          = (array) $this->config->item('custom_domains');
        $addon_dir    = isset($cfg['addon_dir'])    ? $cfg['addon_dir']    : '';
        $proxy_target = isset($cfg['proxy_target']) ? $cfg['proxy_target'] : '';

        $this->addon_dir    = $addon_dir    !== '' ? rtrim($addon_dir, '/\\') . DIRECTORY_SEPARATOR : '';
        $this->proxy_target = $proxy_target !== '' ? rtrim($proxy_target, '/')                     : '';
    }

    /**
     * Sanitize and validate a domain string.
     *
     * Returns the cleaned hostname or FALSE.
     */
    public function sanitize($cname_url)
    {
        if (!is_string($cname_url)) return FALSE;
        $h = trim($cname_url);
        $h = preg_replace('#^https?://#i', '', $h);
        $h = preg_replace('#^//#', '', $h);
        $h = trim($h, "/ \t\n\r\0\x0B");
        $h = strtolower($h);
        if ($h === '') return FALSE;
        if (strpos($h, '/') !== FALSE) return FALSE;
        if (strpos($h, '..') !== FALSE) return FALSE;
        if (!preg_match('/^[a-z0-9.\-]+\.[a-z]{2,}$/', $h)) return FALSE;
        return $h;
    }

    /**
     * Attach a custom domain to a funnel.
     *
     * Returns ['ok'=>bool, 'message'=>string, 'domain'=>string|null]
     */
    public function attach($funnel_id, $cname_url)
    {
        $funnel_id = (int) $funnel_id;
        if ($funnel_id <= 0) {
            return ['ok' => FALSE, 'message' => 'Invalid funnel.'];
        }

        $domain = $this->sanitize($cname_url);
        if (!$domain) {
            return ['ok' => FALSE, 'message' => 'Enter a valid domain (e.g. pages.yourbusiness.com).'];
        }

        // Funnel must exist + still own its slug.
        $row = $this->db->select('id, slug, custom_domain')
            ->from('funnels')->where('id', $funnel_id)->limit(1)
            ->get()->row();
        if (!$row) {
            return ['ok' => FALSE, 'message' => 'Funnel not found.'];
        }
        if (empty($row->slug)) {
            return ['ok' => FALSE, 'message' => 'This funnel has no slug yet — set the slug first.'];
        }

        // If this funnel had a previous *different* domain, scrub its folder
        // — but only if no OTHER funnel still uses that old domain (the
        // folder is shared infra; deleting it would break the others).
        if (!empty($row->custom_domain) && $row->custom_domain !== $domain) {
            $still_used = $this->db->select('id')->from('funnels')
                ->where('custom_domain', $row->custom_domain)
                ->where('id !=', $funnel_id)
                ->limit(1)->get()->row();
            if (!$still_used) {
                $this->_remove_folder($row->custom_domain);
            }
        }

        // Sanity-check config — surface a clear error if subdomain_config.php
        // didn't load (otherwise we'd silently try to mkdir at "/").
        if ($this->addon_dir === '' || $this->addon_dir === DIRECTORY_SEPARATOR) {
            return ['ok' => FALSE, 'message' => 'Custom domains addon_dir is not configured. Check application/config/subdomain_config.php.'];
        }
        if ($this->proxy_target === '') {
            return ['ok' => FALSE, 'message' => 'Custom domains proxy_target is not configured. Check application/config/subdomain_config.php.'];
        }

        // Parent must exist + be writable. Don't attempt to mkdir the root
        // addon dir itself — that lives at /opt/bitnami/... and needs sudo.
        if (!is_dir($this->addon_dir)) {
            return ['ok' => FALSE, 'message' => 'Addon-domains parent folder is missing on the server: ' . $this->addon_dir];
        }
        if (!is_writable($this->addon_dir)) {
            return ['ok' => FALSE, 'message' => 'Web server cannot write to ' . $this->addon_dir . '. Run: sudo chown -R daemon:daemon ' . $this->addon_dir];
        }

        // Create per-domain folder.
        $dst_dir = $this->addon_dir . $domain;
        if (!is_dir($dst_dir)) {
            if (!@mkdir($dst_dir, 0755, TRUE) && !is_dir($dst_dir)) {
                return ['ok' => FALSE, 'message' => 'Could not create folder ' . $dst_dir . '. Check write permissions.'];
            }
        }

        // Write .htaccess.
        $htaccess = $this->_htaccess_body($row->slug);
        $written  = @file_put_contents($dst_dir . DIRECTORY_SEPARATOR . '.htaccess', $htaccess);
        if ($written === FALSE) {
            return ['ok' => FALSE, 'message' => 'Could not write .htaccess in ' . $dst_dir];
        }

        // Persist.
        $this->db->where('id', $funnel_id)->update('funnels', [
            'custom_domain'           => $domain,
            'custom_domain_status'    => 'pending',
            'custom_domain_added_at'  => date('Y-m-d H:i:s'),
        ]);

        return [
            'ok'      => TRUE,
            'domain'  => $domain,
            'message' => 'Domain attached. Point its CNAME to ' . $this->_cname_target() . ' and ask the platform admin to extend SSL.',
        ];
    }

    /**
     * Detach the custom domain from a funnel — remove folder + null columns.
     */
    public function detach($funnel_id)
    {
        $funnel_id = (int) $funnel_id;
        if ($funnel_id <= 0) {
            return ['ok' => FALSE, 'message' => 'Invalid funnel.'];
        }

        $row = $this->db->select('id, custom_domain')
            ->from('funnels')->where('id', $funnel_id)->limit(1)
            ->get()->row();
        if (!$row || empty($row->custom_domain)) {
            return ['ok' => TRUE, 'message' => 'No custom domain to detach.'];
        }

        $this->_remove_folder($row->custom_domain);

        $this->db->where('id', $funnel_id)->update('funnels', [
            'custom_domain'           => NULL,
            'custom_domain_status'    => NULL,
            'custom_domain_added_at'  => NULL,
        ]);

        return ['ok' => TRUE, 'message' => 'Custom domain disconnected.'];
    }

    /**
     * Owner-triggered CNAME check. Updates status, returns the new status.
     */
    public function check_cname($funnel_id)
    {
        $funnel_id = (int) $funnel_id;
        $row = $this->db->select('id, custom_domain')
            ->from('funnels')->where('id', $funnel_id)->limit(1)
            ->get()->row();
        if (!$row || empty($row->custom_domain)) {
            return ['ok' => FALSE, 'message' => 'No custom domain attached.'];
        }

        $target = strtolower($this->_cname_target());
        $domain = $row->custom_domain;
        $status = 'broken';

        // dns_get_record can fail silently or return empty — try both apex and www.
        $hosts_to_check = [$domain, 'www.' . $domain];
        foreach ($hosts_to_check as $h) {
            $records = @dns_get_record($h, DNS_CNAME);
            if (is_array($records)) {
                foreach ($records as $r) {
                    if (!empty($r['target']) && stripos(rtrim(strtolower($r['target']), '.'), $target) !== FALSE) {
                        $status = 'live';
                        break 2;
                    }
                }
            }
        }

        $this->db->where('id', $funnel_id)->update('funnels', [
            'custom_domain_status' => $status,
        ]);

        return [
            'ok'     => TRUE,
            'status' => $status,
            'domain' => $domain,
        ];
    }

    private function _cname_target()
    {
        $cfg = (array) $this->config->item('custom_domains');
        return isset($cfg['cname_target']) && $cfg['cname_target'] !== ''
            ? $cfg['cname_target']
            : 'funnel.agenticagencyapp.com';
    }

    /**
     * Build the per-domain .htaccess body.
     *
     * Public so the demo-side custom-domain model can reuse it — both features
     * share `addondomains/{host}/` and we want one source of truth for the
     * proxy rules. When $slug is empty (demo-only attach, no funnel on this
     * host), the bare-root /page/{slug} rule is omitted; everything else stays
     * byte-identical so the catch-all still routes /demo/{token} through to
     * the renderer.
     */
    public function build_htaccess($slug = '')
    {
        $proxy = $this->proxy_target;
        $slug  = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $slug);

        $body  = "Header add Access-Control-Allow-Origin \"*\"\n";
        $body .= "Header add Access-Control-Allow-Methods \"GET,POST,OPTIONS,DELETE,PUT\"\n\n";
        $body .= "RewriteEngine On\n\n";
        if ($slug !== '') {
            $body .= "# Bare root -> the funnel landing page\n";
            $body .= "RewriteRule ^$ {$proxy}/page/{$slug} [P,L]\n\n";
            $body .= "# Everything else proxies straight into the renderer\n";
            $body .= "RewriteCond %{REQUEST_URI} !^/$\n";
            $body .= "RewriteRule ^(.*)$ {$proxy}/\$1 [P,L]\n\n";
        } else {
            $body .= "# Everything proxies straight into the renderer; bare root\n";
            $body .= "# falls through to the renderer's own 404 (no funnel page here).\n";
            $body .= "RewriteRule ^(.*)$ {$proxy}/\$1 [P,L]\n\n";
        }
        $body .= "Header edit Location \"^https?://[^/]+/(.*)$\" \"{$proxy}/\$1\"\n";

        return $body;
    }

    /**
     * Legacy private alias — kept so the funnel-side attach() path stays
     * literally untouched.
     */
    private function _htaccess_body($slug)
    {
        return $this->build_htaccess($slug);
    }

    /**
     * Remove the per-domain folder. Defensive: the resolved path MUST live
     * inside addon_dir, otherwise abort. Idempotent.
     */
    private function _remove_folder($domain)
    {
        $domain = $this->sanitize($domain);
        if (!$domain) return;

        $target = $this->addon_dir . $domain;
        if (!is_dir($target)) return;

        $real_addon = realpath($this->addon_dir);
        $real_target = realpath($target);
        if (!$real_addon || !$real_target) return;
        if (strpos($real_target, $real_addon) !== 0) return;
        if ($real_target === $real_addon) return;

        $this->_rrmdir($real_target);
    }

    private function _rrmdir($dir)
    {
        if (!is_dir($dir)) return;
        $items = @scandir($dir);
        if (!is_array($items)) return;
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $p = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($p) && !is_link($p)) {
                $this->_rrmdir($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }

    /**
     * Domains the SSL-provisioning cron should process (via the /cname/list
     * endpoint). Includes pending + broken (issue/retry) and live (renew), so
     * certs get issued on first attach and auto-renewed before expiry.
     *
     * @return array<int,array{domain:string,status:string}>
     */
    public function provisionable_domains()
    {
        $rows = $this->db->select('custom_domain, custom_domain_status')
            ->from('funnels')
            ->where('custom_domain IS NOT NULL', NULL, FALSE)
            ->where("custom_domain !=", '')
            ->where_in('custom_domain_status', ['pending', 'broken', 'live'])
            ->get()->result();

        $out = [];
        foreach ($rows as $r) {
            $d = strtolower(trim((string) $r->custom_domain));
            if ($d === '') continue;
            $out[] = ['domain' => $d, 'status' => (string) $r->custom_domain_status];
        }
        return $out;
    }

    /**
     * Set a domain's lifecycle status (called by the SSL cron via /cname/status).
     * Accepts the model's own vocabulary: pending | live | broken.
     * Returns TRUE if a row was matched + updated.
     */
    public function set_domain_status($domain, $status)
    {
        $domain = $this->sanitize($domain);
        if (!$domain) return FALSE;
        if (!in_array($status, ['pending', 'live', 'broken'], true)) return FALSE;

        $this->db->where('custom_domain', $domain)->update('funnels', [
            'custom_domain_status' => $status,
        ]);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Used by Pages::_is_allowed_submit_origin() to extend the static
     * funnel_hosts allowlist. Returns lowercased hostnames (apex + www).
     */
    public function get_allowed_hosts()
    {
        $rows = $this->db->select('custom_domain')
            ->from('funnels')
            ->where('custom_domain IS NOT NULL', NULL, FALSE)
            ->where("custom_domain !=", '')
            ->get()->result();

        $hosts = [];
        foreach ($rows as $r) {
            $h = strtolower(trim($r->custom_domain));
            if ($h === '') continue;
            $hosts[] = $h;
            $hosts[] = (strpos($h, 'www.') === 0) ? $h : 'www.' . $h;
        }
        return array_values(array_unique($hosts));
    }
}
