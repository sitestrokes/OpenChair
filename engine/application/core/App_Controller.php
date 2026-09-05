<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class App_Controller extends MY_Controller
{
	public $data;
	public $user;
	public $user_id;
	public $where = array();

    function __construct(){
		parent::__construct();

		// CLI workers (e.g. detached funnel-build) bootstrap their own user
		// context from the row they're operating on. Skipping the web auth
		// gate avoids a redirect-to-login from a process that has no session.
		if (is_cli()) {
			return;
		}

		// Timezone is set to UTC globally in index.php
		// User's display timezone is stored in cookie 'user_timezone' and subscriber.timezone
		// All DB timestamps are UTC — convert to local only for display in views






		// SSE/EventSource parallel requests cannot send POST — inject s_value from PHPSESSID cookie
		if (isset($_GET['parallel']) && !isset($_POST['s_value']) && !empty($_COOKIE['PHPSESSID'])) {
			$_POST['s_value'] = $_COOKIE['PHPSESSID'];
		}

		// ── Security: s_value session bridge
		if (isset($_POST['s_value']) && !empty($_POST['s_value'])) {
			

			$ses_id = $this->input->post('s_value', TRUE);
			$udata = $this->app_lib->fetchTableData('users', array("ses_id" => $ses_id));
			if (!empty($udata) && isset($udata[0]['Data'])) {
				$session_data = [
					'identity'  => $udata[0]['Data']->email,
					'email'     => $udata[0]['Data']->email,
					'user_id'   => $udata[0]['Data']->id,
				];
				$this->session->set_userdata($session_data);
			}
			
		}

		
		//print all the session data for debugging
		// print_r($this->session->all_userdata());exit;
		
		
		if( !$this->ion_auth->logged_in() && !isset($_POST['s_value']) && !$this->ion_auth->login_remembered_user() ) {

			redirect('/login');

		}else {

			// The client-portal feature has been removed. Any lingering
			// client_portal session no longer has a portal to land on, so it is
			// logged out back to the login page rather than admin pages.
			if ($this->ion_auth->in_group('client_portal')) {
				$this->ion_auth->logout();
				redirect('login');
				return;
			}

			$this->user = $this->ion_auth->user()->row();
			$this->user_id = $this->user->id;
			// $this->ses_id = $this->user->ses_id;
			$access = explode(",", $this->user->access);


			$login_type = $this->session->userdata('login_type');
			if ( !empty($login_type) &&  $login_type != 'admin' && !in_array(93,$access) ) {
				if (!$this->ion_auth->check_last_login_device($this->user->email)) {
					redirect('logout');
				}
			}
				
			if( !isset($_POST['s_value']) ){

				$new_ses_id = session_id();
				if ($this->ses_id != $new_ses_id) {
					$this->app_lib->updateTable('users',['id' => $this->user_id],array('ses_id' => $new_ses_id ) );
				}
			}

			$this->data['user_row'] = $this->user;
			$this->data['user_id'] = $this->user_id;

			// Ensure user timezone is in session (pick up from cookie if missing)
			if (!$this->session->userdata('user_timezone')) {
				$tz_cookie = $this->input->cookie('user_timezone');
				if (!empty($tz_cookie)) {
					$this->session->set_userdata('user_timezone', $tz_cookie);
				}
			}
			$this->data['user_timezone'] = $this->session->userdata('user_timezone') ?: 'UTC';

			$parent_id = $this->user_id;

			if($this->user_id !=1)
			{
				$access = explode(",", $this->user->access);
				$parent_id = $this->user->parent_user_id;

				
				if( in_array(1,$access) || in_array(2,$access) || in_array(21,$access) || in_array(3,$access) || in_array(4,$access) || in_array(6,$access) || in_array(7,$access) || in_array(71,$access) || in_array(72,$access) || in_array(9,$access) || in_array(94,$access) || in_array(91,$access) || in_array(92,$access) || in_array(101,$access))
				{

				}
				else
				{
					redirect('logout');
				}
			}


			// ── Unified Agentic Affiliates brand for the dark shell (ic_shell/layout). ──
			// The shell renders logo / favicon / <title> / footer / accent from
			// $this->data['ic_brand']. We compute it HERE (App_Controller) so EVERY
			// controller — not just the instantclose module — gets dynamic branding
			// without re-deriving it. The owner's saved brand (the whitelabel
			// module, settings meta_type 'whitelabel') overrides the config
			// (global_data + ion_auth site_title) defaults.
			$this->data['ic_brand'] = $this->_build_ic_brand($parent_id);


		}

	}

	/**
	 * Resolve the white-label brand the dark shell renders: name, logo, favicon,
	 * site title, footer. Defaults come from config (global_data + ion_auth
	 * site_title); a saved white-label row (this user, else the agency parent —
	 * settings meta_type 'whitelabel', written by the whitelabel module)
	 * overrides any field it sets. Always returns the full shape.
	 *
	 * @param int $parent_id  the agency owner to inherit branding from
	 * @return array{name:string,logo:string,favicon:string,site_title:string,footer:string}
	 */
	protected function _build_ic_brand($parent_id)
	{
		$this->load->config('app_config');
		$g = $this->config->item('global_data');
		$default_title = (string) $this->config->item('site_title', 'ion_auth');

		$brand = [
			'name'       => $default_title,
			'logo'       => !empty($g['logo_url']) ? base_url($g['logo_url']) : '',
			'favicon'    => !empty($g['fav_url'])  ? base_url($g['fav_url'])  : '',
			'site_title' => $default_title,
			'footer'     => isset($g['footer_text']) ? $g['footer_text'] : '',
		];

		// Owner's saved brand (this user, then the agency parent) overrides
		// defaults. The whitelabel module stores: site_title, logo, fav_url,
		// footer_text (URLs are already absolute when saved via upload).
		foreach (array_unique([(int) $this->user_id, (int) $parent_id]) as $uid) {
			if ($uid <= 0) { continue; }
			$row = $this->app_lib->fetchTableData('settings', ['user_id' => $uid, 'meta_type' => 'whitelabel']);
			if (empty($row)) { continue; }
			$wl = @unserialize($row[0]['Data']->meta_data);
			if (!is_array($wl)) { continue; }
			if (!empty($wl['site_title']))  { $brand['name'] = $wl['site_title']; $brand['site_title'] = $wl['site_title']; }
			if (!empty($wl['logo']))        { $brand['logo'] = $wl['logo']; }
			if (!empty($wl['fav_url']))     { $brand['favicon'] = $wl['fav_url']; }
			if (!empty($wl['footer_text'])) { $brand['footer'] = $wl['footer_text']; }
			break; // first match wins (own brand preferred over parent's)
		}

		// Guarantee a space after "© {year}" so a saved brand string jammed against
		// the year ("© 2026Helllo") never renders in the shell footer (L-04).
		if (!empty($brand['footer'])) {
			$brand['footer'] = preg_replace('/©\s*20\d{2}(?=\S)/u', '© ' . date('Y') . ' ', $brand['footer']);
		}
		$brand['name'] = trim((string) $brand['name']);

		return $brand;
	}

	/**
	 * Guard a controller action behind a specific access permission.
	 * Call from inside a controller method or constructor (after parent::__construct).
	 *
	 * Accepts:
	 *   - a permission name matching a can_access_* method on Access_control_lib
	 *     ('core', 'pro_unlimited', 'content_mastery', 'voice_command')
	 *   - a single access code as string ('2', '4', etc.)
	 *   - an array of access codes (any one is sufficient)
	 *
	 * Behavior on denial:
	 *   - AJAX / JSON request → 403 JSON {success:false, message:...}
	 *   - Normal request     → flash error + redirect to /dashboard
	 *
	 * Usage:
	 *   $this->require_access('voice_command');     // OTO3 / Bundle / Fastpass only
	 *   $this->require_access(['9', '91']);         // raw codes
	 */
	public function require_access($permission)
	{
		// Superadmin always passes
		if ($this->access_control_lib->is_super_admin()) {
			return TRUE;
		}

		$allowed = FALSE;

		if (is_string($permission)) {
			$method = 'can_access_' . $permission;
			if (method_exists($this->access_control_lib, $method)) {
				$allowed = $this->access_control_lib->$method();
			} else {
				$allowed = $this->access_control_lib->has_access(NULL, $permission);
			}
		} elseif (is_array($permission)) {
			$allowed = $this->access_control_lib->has_access(NULL, $permission);
		}

		if ($allowed) {
			return TRUE;
		}

		log_message('info', 'require_access DENIED: user_id=' . ($this->user_id ?? 'null')
			. ' permission=' . (is_array($permission) ? implode(',', $permission) : $permission)
			. ' url=' . current_url());

		$is_ajax = $this->input->is_ajax_request()
			|| stripos((string) $this->input->server('HTTP_ACCEPT'), 'application/json') !== FALSE;

		if ($is_ajax) {
			$this->output
				->set_status_header(403)
				->set_content_type('application/json')
				->set_output(json_encode([
					'success' => FALSE,
					'message' => 'Upgrade required to access this feature.',
				]));
			exit;
		}

		$this->session->set_flashdata('error', 'Upgrade required to access this feature.');
		redirect('/dashboard');
		exit;
	}
}