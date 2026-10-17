<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oc_render_lib — render any module's view inside the OpenChair dark shell.
 *
 * Same contract as the host engine's Ic_render_lib, and for the same reason: the
 * chrome lives at application/views/oc_shell/ (engine-wide, NOT inside the
 * openchair module) so that whitelabel/, setting/, users/ and config/ render in
 * identical chrome to the product pages. A shell owned by one module is a shell
 * every other module has to reimplement.
 *
 * Usage from a controller:
 *   $this->load->library('oc_render_lib');
 *   $this->oc_render_lib->render('pages/radar', $this->data, [
 *       'page'       => 'radar',        // active rail nav key
 *       'page_title' => 'Radar',
 *   ]);
 *
 * The shell is self-contained (its own dark CSS, vanilla JS, no jQuery/Bootstrap).
 * A migrated legacy page that needs jQuery + SweetAlert2 + FontAwesome opts in
 * with ['legacy' => true], exactly as the host engine does.
 */
class Oc_render_lib
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('url');
    }

    /**
     * True if $user_row's users.access comma-list contains $code.
     * Tolerates a missing/blank access column.
     */
    private function _access_has($user_row, $code)
    {
        $raw = is_object($user_row) && isset($user_row->access) ? (string) $user_row->access : '';
        if ($raw === '') { return false; }
        return in_array((string) $code, array_map('trim', explode(',', $raw)), true);
    }

    /**
     * Render $view inside the OpenChair shell.
     *
     * @param string $view  view path for the main column. Resolves against the
     *                      CALLING module's views/ first, then application/views/.
     * @param array  $data  the controller's $this->data (carries user_row etc.)
     * @param array  $opts  page (active rail key) · page_title · page_head ·
     *                      page_script · legacy (bool)
     * @return void
     */
    public function render($view, array $data = [], array $opts = [])
    {
        $data = $this->shell_flags($data);

        $data['content_view'] = $view;
        $data['page']         = isset($opts['page']) ? $opts['page'] : '';
        $data['page_title']   = isset($opts['page_title']) ? $opts['page_title'] : 'OpenChair';
        if (isset($opts['page_head']))   { $data['page_head']   = $opts['page_head']; }
        if (isset($opts['page_script'])) { $data['page_script'] = $opts['page_script']; }

        if (!empty($opts['legacy'])) {
            $data['page_head'] = $this->legacy_assets() . (isset($data['page_head']) ? $data['page_head'] : '');
        }

        $this->CI->load->view('oc_shell/layout', $data);
    }

    /**
     * Compute the rail's gating flags + brand into $data WITHOUT rendering, for a
     * page that mounts oc_shell/_oc_rail.php directly (the print one-pager keeps
     * its own paper skin but still wants the same flags).
     *
     * @param array $data
     * @return array the same array with flags/brand/quota ensured
     */
    public function shell_flags(array $data = [])
    {
        $ci = $this->CI;

        if (empty($data['user_row'])) {
            $data['user_row'] = $ci->ion_auth->user()->row();
        }
        $uid = isset($data['user_id']) ? (int) $data['user_id']
             : (isset($data['user_row']->id) ? (int) $data['user_row']->id : 0);

        // Admin gating by ACCESS CODE '51' (JV Admin) plus superadmin (user 1) —
        // NOT the Ion-Auth 'admin' group. Carried over from the host engine, where
        // most accounts were mis-provisioned into that group, so the group is not
        // a trustworthy signal.
        if (!isset($data['is_oc_admin'])) {
            $data['is_oc_admin'] = ($uid === 1) || $this->_access_has($data['user_row'], '51');
        }
        if (!isset($data['is_oc_superadmin'])) {
            $data['is_oc_superadmin'] = ($uid === 1);
        }

        // ── Tier gates ───────────────────────────────────────────────────────
        // Phase 2 placeholder: the OpenChair access-code map is still an open
        // decision (STRUCTURE.md §9), so every gate resolves TRUE for now and the
        // rail renders complete. When the codes are set these become
        // Access_control_lib calls and nothing else in the shell changes.
        //
        // What each gate controls, per 40-ux-spec.md §2.3:
        //   slammed  — the Slammed bucket + its capacity pitch  (OTO1 as briefed)
        //   deadzone — the Dead-Zone Map                        (OTO1)
        //   ledger   — white-label Ledger reports               (OTO2)
        //   kit      — Fill-the-Chair campaign kit              (OTO3)
        foreach (['slammed', 'deadzone', 'ledger', 'kit', 'alerts'] as $gate) {
            if (!isset($data['oc_can_' . $gate])) { $data['oc_can_' . $gate] = $this->can($gate, $data['user_row']); }
        }

        // Brand — App_Controller::_build_ic_brand() populates ic_brand on every
        // authenticated request. Keep a safe default so the rail never warns.
        if (!isset($data['ic_brand']) || !is_array($data['ic_brand'])) {
            $data['ic_brand'] = ['name' => '', 'logo' => '', 'favicon' => '', 'site_title' => '', 'footer' => ''];
        }

        // Rail-foot quota meters. Real numbers arrive with the two-meter quota
        // model in Phase 8; until then the shell renders the widget with whatever
        // a controller supplies and hides it when nothing is set.
        if (!isset($data['oc_quota'])) { $data['oc_quota'] = null; }

        // Rail badges (PDF p.8): "Home · 3 due" and "My pipeline · 12". Tenant
        // counts over oc_prospect only — two indexed COUNTs, scoped to this user.
        if (!isset($data['oc_due_count']) || !isset($data['oc_pipeline_count'])) {
            $data['oc_due_count'] = 0;
            $data['oc_pipeline_count'] = 0;
            // The pipeline belongs to the agency (oc_012_team_tenancy.sql).
            $parent = isset($data['user_row']->parent_user_id) ? (int) $data['user_row']->parent_user_id : 0;
            $tid = isset($data['oc_tenant_id']) ? (int) $data['oc_tenant_id']
                 : (($parent > 0 && $parent !== $uid) ? $parent : $uid);
            $uid_tenant = $tid;
            if ($uid_tenant > 0 && $ci->db->table_exists('oc_prospect')) {
                $data['oc_pipeline_count'] = (int) $ci->db->where('user_id', $uid_tenant)
                    ->where_not_in('stage', ['lost', 'archived'])
                    ->count_all_results('oc_prospect');
                if ($ci->db->field_exists('next_due_on', 'oc_prospect')) {
                    $data['oc_due_count'] = (int) $ci->db->where('user_id', $uid_tenant)
                        ->where('next_due_on IS NOT NULL')
                        ->where('next_due_on <=', date('Y-m-d'))
                        ->where_not_in('stage', ['won', 'lost', 'archived'])
                        ->count_all_results('oc_prospect');
                }
            }
        }

        return $data;
    }

    /**
     * May this user use a tier gate? (docs/70-radar-build.md R7)
     *
     * One answer for the rail AND the controllers, so an item that is hidden is
     * also refused by URL. Enforcement is off until the founder sets the access
     * codes (config oc_access_enforce), and then: superadmin and the full-access
     * review roles pass; everyone else needs a code listed for the gate.
     *
     * @param string      $gate     core | slammed | alerts | deadzone | ledger | kit
     * @param object|null $user_row
     * @return bool
     */
    public function can($gate, $user_row = null)
    {
        $ci = $this->CI;
        $ci->load->config('app_config');
        if (!$ci->config->item('oc_access_enforce')) { return true; }

        if ($user_row === null) { $user_row = $ci->ion_auth->user()->row(); }
        if (!is_object($user_row)) { return false; }
        if ((int) $user_row->id === 1) { return true; }

        $codes = array_filter(array_map('trim', explode(',', (string) ($user_row->access ?? ''))), 'strlen');
        if (array_intersect($codes, (array) $ci->config->item('oc_access_full'))) { return true; }

        $map = (array) $ci->config->item('oc_access_codes');
        if (!isset($map[$gate])) { return false; }          // an unknown gate is closed, not open
        return (bool) array_intersect($codes, (array) $map[$gate]);
    }

    /**
     * jQuery + SweetAlert2 + FontAwesome for a ported legacy page (users,
     * settings, whitelabel), plus the CSRF prefilter those pages' POSTs need.
     * Same bundle and the same reasoning as the host engine's Ic_render_lib.
     *
     * @return string raw HTML for the $page_head slot
     */
    public function legacy_assets()
    {
        $base = base_url();
        $csrfName = $this->CI->security->get_csrf_token_name();
        $csrfHash = $this->CI->security->get_csrf_hash();

        $csrf = '<script>(function(){'
          . 'window.__csrfName=' . json_encode($csrfName) . ';'
          . 'window.__csrfHash=' . json_encode($csrfHash) . ';'
          . 'document.addEventListener("DOMContentLoaded",function(){'
          . 'document.querySelectorAll(\'form[method="post"],form[method="POST"]\').forEach(function(f){'
          . 'if(!f.querySelector(\'input[name="\'+window.__csrfName+\'"]\')){'
          . 'var i=document.createElement("input");i.type="hidden";i.name=window.__csrfName;i.value=window.__csrfHash;f.appendChild(i);}});});'
          . 'if(window.jQuery){jQuery.ajaxPrefilter(function(o){'
          . 'if(o.type&&o.type.toUpperCase()==="POST"){var n=window.__csrfName,h=window.__csrfHash;if(!n||!h)return;'
          . 'if(o.data instanceof FormData){o.data.append(n,h);}'
          . 'else if(typeof o.data==="string"){o.data=(o.data?o.data+"&":"")+n+"="+encodeURIComponent(h);}'
          . 'else if(typeof o.data==="object"&&o.data){o.data[n]=h;}'
          . 'else{o.data=n+"="+encodeURIComponent(h);}}});}'
          . '})();</script>';

        return ''
          . '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">'
          . '<link rel="stylesheet" href="' . $base . 'assets/js/sweetalert2/sweetalert2.min.css">'
          . '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>'
          . '<script src="' . $base . 'assets/js/sweetalert2/sweetalert2.min.js"></script>'
          . $csrf;
    }
}
