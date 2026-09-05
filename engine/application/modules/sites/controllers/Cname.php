<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cname — machine-to-machine endpoints for the custom-domain SSL cron.
 *
 * The server-side shell script (custom-ssl/cname_ssl_instaclose.sh) talks to
 * these endpoints instead of touching MySQL directly, so the script stays
 * DB-credential-free and `git pull` on the server never conflicts.
 *
 *   GET /cname/list?key=SECRET
 *       Plain-text list of custom domains to process, one "{domain}\tportal"
 *       per line. Includes pending/broken (issue/retry) + live (renew).
 *
 *   GET /cname/status?key=SECRET&domain=foo.com&state=active|failed
 *       The cron reports each domain's outcome; we map it to the funnel's
 *       custom_domain_status (active→live, failed→broken).
 *
 * Auth: a shared secret compared in constant time. Reuses the encrypted
 * `bridge_internal_token` config key (already provisioned for CI↔Node), so no
 * new secret is required. GET-only + no session, so CSRF does not apply.
 */
class Cname extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('config_lib');
        $this->load->model('sites/custom_domain_model');
    }

    /**
     * Read the shared secret from the encrypted config table. Prefers a
     * dedicated `cname_secret` (independent rotation); falls back to the
     * existing `bridge_internal_token` so no new key is strictly required.
     */
    private function _secret()
    {
        foreach (['cname_secret', 'bridge_internal_token'] as $key) {
            try {
                $val = $this->config_lib->getKey($key);
                if ($val && $val !== 'invalid key') { return (string) $val; }
            } catch (Exception $e) { /* try next */ }
        }
        return '';
    }

    /** Verify the ?key= shared secret; emit 403 + return FALSE on mismatch. */
    private function _authorized()
    {
        $secret = $this->_secret();
        $given  = (string) ($this->input->get('key') ?: $this->input->post('key'));

        if ($secret === '' || !hash_equals($secret, $given)) {
            $this->output
                ->set_status_header(403)
                ->set_content_type('text/plain')
                ->set_output("forbidden\n");
            return FALSE;
        }
        return TRUE;
    }

    /**
     * GET /cname/list — plain-text "{domain}\tportal" lines for the SSL cron.
     */
    public function list_domains()
    {
        if (!$this->_authorized()) { return; }

        $lines = [];
        try {
            foreach ($this->custom_domain_model->provisionable_domains() as $row) {
                $domain = trim((string) $row['domain']);
                if ($domain !== '') {
                    $lines[] = $domain . "\tportal";
                }
            }
        } catch (Exception $e) {
            log_message('error', '[Cname::list] ' . $e->getMessage());
        }

        $this->output
            ->set_content_type('text/plain')
            ->set_output($lines === [] ? '' : implode("\n", $lines) . "\n");
    }

    /**
     * GET /cname/status — the cron reports issuance outcome.
     * state=active → live, state=failed → broken (model vocabulary).
     */
    public function status()
    {
        if (!$this->_authorized()) { return; }

        $domain = strtolower(trim((string) ($this->input->get('domain') ?: $this->input->post('domain'))));
        $state  = strtolower(trim((string) ($this->input->get('state')  ?: $this->input->post('state'))));

        $this->output->set_content_type('application/json');

        $map = ['active' => 'live', 'failed' => 'broken', 'pending' => 'pending'];
        if ($domain === '' || !isset($map[$state])) {
            $this->output->set_status_header(400)
                ->set_output(json_encode(['success' => false, 'error' => 'domain and valid state required']));
            return;
        }

        try {
            $ok = $this->custom_domain_model->set_domain_status($domain, $map[$state]);
            $this->output->set_output(json_encode([
                'success' => (bool) $ok, 'domain' => $domain, 'state' => $map[$state],
            ]));
        } catch (Exception $e) {
            log_message('error', '[Cname::status] ' . $e->getMessage());
            $this->output->set_status_header(500)
                ->set_output(json_encode(['success' => false, 'error' => 'update failed']));
        }
    }
}
