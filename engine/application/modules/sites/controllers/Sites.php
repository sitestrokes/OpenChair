<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sites extends App_Controller
{
	function __construct()
	{
		parent::__construct();

		$this->load->library('s3_lib');  
		$this->load->library('twilio_lib');
		
		// if(!$this->ion_auth->logged_in())
		// {
		// 	redirect('login');
		// }

		# get settings for logo, brandtext, footer
		$u = $this->ion_auth->user()->row();
		$this->user_id = $u->id;
		//echo $u->id; exit;
		$whr['user_id'] = $u->id;
		$this->data['settings'] = $this->Datamodel->fetchTableData('whitelabel_settings', $whr, 'image_name, image_path, footer_text, brand_text');
	}

	public function index($page='')
	{
		// Legacy AgenticAgency dashboard (the "Good morning, there" screen) is
		// retired — it is NOT part of Agentic Affiliates. Any way of reaching it (the
		// native /sites path, a stale bookmark, or a require_access() denial
		// fallback) now bounces to the Agentic Affiliates home. Its view files
		// (pages/create-funnel, pages/mode_funnel) have been DELETED, and every
		// method that rendered them (FunnelSearch, create_funnel, funnelModes)
		// now redirects here too — so no _render_page call references a missing
		// file.
		redirect('app');
	}
	public function FunnelSearch() {
    // Legacy AgenticAgency funnel-search screen (rendered the retired
    // pages/create-funnel dashboard view). Retired with the AA dashboard —
    // bounce to the Agentic Affiliates home. Bridge management lives under
    // /instantclose (Bridges / Leads).
    redirect('app');
}

	public function get_leads_data($id = 0 ){

		$where = array('id' => $id , 'user_id' => $this->user_id);
		$funnel_data = $this->Datamodel->getSingleRow('place_details', $where);
		return $funnel_data;
	}

	public function create_funnel()
	{
		// Legacy AgenticAgency create-funnel screen (rendered the retired
		// pages/create-funnel view). Retired with the AA dashboard — bounce to
		// the Agentic Affiliates home; building happens on /instantclose/build-bridge.
		redirect('app');
		return;
	}

	public function funnel_types($funnel_id)
	{
		$u = $this->ion_auth->user()->row();
		$user_id = $u->id;

		// Load template configuration
		$this->load->config('template_config');
	
		// Fetch funnel data including the keywords column
		$funnel_data = $this->Datamodel->getSingleRow('funnels', array('id' => $funnel_id));
		// print_r($funnel_data);exit;
	
		// Initialize an empty array to store matched templates
		$matched_templates = [];
		$templates_data   = $this->config->item('template')['FE'];

		// Priority 1: always surface the template the AI (or the user) has
		// already saved on the funnel as the headline recommendation. The
		// older keyword-only matcher would routinely return zero hits when
		// the business niche didn't word-match an industry template name
		// (e.g. keywords "Digital Marketing, SEO" against names "HVAC",
		// "Dental", etc.), which hid the entire "Recommended for you"
		// section. Anchoring on the saved template_id guarantees there's
		// always a recommendation visible.
		if ($funnel_data && !empty($funnel_data->template_id) && isset($templates_data[$funnel_data->template_id])) {
			$matched_templates[$funnel_data->template_id] = $templates_data[$funnel_data->template_id];
		}

		// Priority 2: enrich with keyword matches so the recommendation row
		// can show related niches the user might also want. Skips the
		// already-included saved template_id.
		if ($funnel_data && isset($funnel_data->keywords)) {
			// Split the keywords by commas and trim whitespace
			$keywords_array = array_filter(array_map('trim', explode(',', $funnel_data->keywords)));

			// Loop through each keyword and match it with templates
			foreach ($keywords_array as $keyword) {
				if ($keyword === '') continue;
				foreach ($templates_data as $key => $template) {
					if (isset($matched_templates[$key])) continue;
					if (strcasecmp($template['name'], $keyword) == 0 || stripos($template['name'], $keyword) !== false) {
						$matched_templates[$key] = $template;
					}
				}
			}
			$this->data['keywords'] = implode(', ', $keywords_array);
		} else {
			$this->data['keywords'] = 'No keywords available';
		}
	
		// Set session and view data
		$this->session->set_userdata("funnel_id", $funnel_id);
		$this->data["templatesdata"] = $this->config->item('template');
		$this->data['matched_templates'] = $matched_templates; // Pass matched templates to the view
		$this->data['funnel_id'] = $funnel_id;
		$this->data['PageTitle'] = 'Funnel Types';
		$this->data['funnel_type'] = 'Landing';
	
		// Load the view
		$this->load->view('pages/funnel-types1', $this->data);
	}
	
	

	//select template page
	// public function template(){
		
	// 	$funnel_id=$_REQUEST['funnel_id'];
	// 	$funnel_type=$_REQUEST['funnel_type'];

	// 	$this->data['funnel_id']=$funnel_id;
	// 	$this->session->set_userdata("funnel_id",$funnel_id);

	// 	$this->Datamodel->updateTable("funnels",array("id"=>$funnel_id),array("template_id"=>$funnel_type));

	// 	if($funnel_type == 'landing'){
	// 		$this->load->config('template_config');
	// 		$this->data["templatesdata"]=$this->config->item('template');
	// 		$this->data['funnel_type']=$funnel_type;
	// 	}else{
	// 		$this->load->config('thankyou_config');
	// 		$this->data["thankyoutemplates"]=$this->config->item('thankyou');
	// 		$this->data['funnel_type']='Thank You';
	// 	}

	// 	$this->data['PageTitle']='Templates';
	// 	$this->load->view("pages/funnel-types2",$this->data);
	// }

	/**
	 * Can this user create a NEW funnel via the dashboard wizard?
	 * Used by views to hide the "Create New Funnel" button up front.
	 */
	private function _can_create_funnel($user_id) {
		if ((int) $user_id === 1) return TRUE; // superadmin
		// Anti-gaming: every funnel the user has ever created counts toward the
		// cap, including archived AND soft-deleted rows.
		$funnel_count = (int) $this->db->where('user_id', $user_id)->count_all_results('funnels');
		if ($this->access_control_lib->has_reached_funnel_cap(NULL, $funnel_count)) return FALSE;
		return TRUE;
	}

	/**
	 * Cap summary the dashboard view uses to render upgrade copy when the
	 * Create-Funnel button is hidden. All counts taken at request time.
	 * Counts are lifetime (include archived + soft-deleted) -- anti-gaming.
	 */
	private function _plan_caps_summary($user_id) {
		$funnel_count = (int) $this->db->where('user_id', $user_id)->count_all_results('funnels');
		return array(
			'funnel_count' => $funnel_count,
			'funnel_max'   => $this->access_control_lib->max_funnels(),
			'funnel_capped' => $this->access_control_lib->has_reached_funnel_cap(NULL, $funnel_count),
		);
	}

	//check funnel limit
	/**
	 * Pre-flight check for the dashboard's "Create New Funnel" button.
	 * Single source of truth = Access_control_lib.
	 *
	 * Response shape (kept stable for existing JS callers):
	 *   { response: 0|1, limit: <funnel_cap>, reason?: 'funnels' }
	 *   response=1 → can proceed
	 *   response=0 → blocked; `reason` says which cap and `limit` gives the cap value.
	 */
	public function checkFunnellimit(){
		$u = $this->ion_auth->user()->row();
		header('Content-Type: application/json');

		// Superadmin always passes.
		if ($u && (int) $u->id === 1) {
			echo json_encode(array('response' => 1, 'limit' => -1));
			return;
		}

		$user_id = (int) $u->id;
		$ret = array();

		// Funnel cap — count ALL funnels owned by the user (active + draft).
		$funnel_count = (int) $this->db->where('user_id', $user_id)->count_all_results('funnels');
		$funnel_max   = $this->access_control_lib->max_funnels();
		$ret['limit'] = $funnel_max;

		if ($this->access_control_lib->has_reached_funnel_cap(NULL, $funnel_count)) {
			$ret['response'] = 0;
			$ret['reason']   = 'funnels';
			echo json_encode($ret);
			return;
		}

		$ret['response'] = 1;
		echo json_encode($ret);
	}

	/**
	 * Realtime slug availability check for the publish-modal slug input.
	 * Mirrors checkName() — POST in, JSON out.
	 */
	public function checkSlug()
	{
		$u = $this->ion_auth->user()->row();
		if (!$u) {
			header('Content-Type: application/json');
			echo json_encode(array('clean' => '', 'status' => 'taken'));
			return;
		}

		$raw       = isset($_POST['slug']) ? $_POST['slug'] : '';
		$funnel_id = isset($_POST['funnel_id']) ? (int) $_POST['funnel_id'] : 0;

		$this->load->library('slug_lib');
		$clean = $this->slug_lib->normalize($raw);

		$resp = array('clean' => $clean);
		if ($clean === '') {
			$resp['status'] = 'invalid';
		} elseif ($this->slug_lib->is_available($clean, $funnel_id)) {
			$resp['status'] = 'available';
		} else {
			$resp['status'] = 'taken';
		}

		header('Content-Type: application/json');
		echo json_encode($resp);
	}

	/**
	 * Slug-only persistence used by the publish-modal Done button.
	 * Validates ownership, normalizes, checks availability, and updates
	 * funnels.slug + publish_link + thankyou_page_link without touching
	 * any of the page content. Returns the canonical slug + URLs.
	 */
	public function update_slug()
	{
		header('Content-Type: application/json');

		$u = $this->ion_auth->user()->row();
		if (!$u) {
			echo json_encode(array('status' => 'error', 'message' => 'Not logged in'));
			return;
		}

		$funnel_id = isset($_POST['funnel_id']) ? (int) $_POST['funnel_id'] : 0;
		$raw       = isset($_POST['slug']) ? trim($_POST['slug']) : '';

		if (!$funnel_id) {
			echo json_encode(array('status' => 'error', 'message' => 'Missing funnel_id'));
			return;
		}

		$where = ((int) $u->id === 1)
			? array('id' => $funnel_id)
			: array('id' => $funnel_id, 'user_id' => (int) $u->id);
		$row = $this->db->where($where)->get('funnels')->row();
		if (!$row) {
			echo json_encode(array('status' => 'error', 'message' => 'Funnel not found'));
			return;
		}

		$this->load->library('slug_lib');
		$clean = $this->slug_lib->normalize($raw);

		if ($clean === '') {
			$clean = $this->slug_lib->make_funnel_slug($row->campaign_name, $funnel_id);
		} elseif (!$this->slug_lib->is_available($clean, $funnel_id)) {
			echo json_encode(array(
				'status'  => 'error',
				'message' => 'That URL is already taken. Choose another.',
				'slug'    => $row->slug,
			));
			return;
		}

		$publish_link       = base_url('webpages/' . $clean);
		$thankyou_page_link = base_url('webpages/' . $clean . '/thankyou');

		$update = array(
			'slug'               => $clean,
			'publish_link'       => $publish_link,
			'thankyou_page_link' => $thankyou_page_link,
		);
		// Bridge funnels (templates 201-210): the Node capture lookup matches on
		// `WHERE site_uid = slug`, so a slug change MUST realign site_uid /
		// thanku_site_uid in the SAME write. Without this, an inline slug rename
		// left site_uid pointing at the old value and the opt-in reported
		// "not linked to a live campaign" until a separate full save fixed it
		// (mirrors submitAction's $ic_is_bridge alignment).
		if ((int) $row->template_id >= 201 && (int) $row->template_id <= 210) {
			$update['site_uid']        = $clean;
			$update['thanku_site_uid'] = $clean;
		}
		$this->db->where('id', $funnel_id)->update('funnels', $update);

		echo json_encode(array(
			'status'             => '200',
			'slug'               => $clean,
			'publish_link'       => $publish_link,
			'thankyou_page_link' => $thankyou_page_link,
		));
	}

	//To check campaign is already exist or not
	public function checkName()
	{
		$u = $this->ion_auth->user()->row();
		$user_id = $u->id;
		if(isset($_POST['funnel_name']))
		{
			$funnel_name = trim($_POST['funnel_name']);
			$funnel_name_count=$this->Datamodel->checkExit('funnels',array('campaign_name'=>$funnel_name,'user_id'=>$user_id));
			//echo $camp_name_count;exit;
			if($funnel_name_count > 0 ) 
			{
				echo 1;
			} 
			else 
			{
				echo 0;
			}
		}
		else
			echo "post error";
	}

	//insert campaign Name
	public function insertFunnel(){
		set_time_limit(120); // AI generation may take up to ~20s

		$user = $this->ion_auth->user()->row();
      	$userID = $user->id;
		$funnel_name = isset($_POST['funnel_name']) ? trim($_POST['funnel_name']) : '';

		// Server-side uniqueness gate. The wizards run a live JS checkName but
		// that's bypassable (no JS, manual POST, two simultaneous tabs racing
		// past each other's lookup). This second gate guarantees we never
		// create two funnels with the same name under one user. Trimming
		// catches "Foo " vs "Foo"; the column's utf8mb4_unicode_ci collation
		// already makes the comparison case-insensitive.
		if ($funnel_name !== '') {
			$dup = $this->Datamodel->checkExit('funnels', array(
				'campaign_name' => $funnel_name,
				'user_id'       => $userID,
			));
			if ($dup > 0) {
				header('HTTP/1.1 409 Conflict');
				echo 'DUPLICATE';
				return;
			}
		}

		// Plan funnel cap: FE = 25, PRO/Bundle/Fastpass = 75. Anti-gaming:
		// archived and soft-deleted funnels still count.
		$funnel_count = (int) $this->db->where('user_id', $userID)->count_all_results('funnels');
		if ($this->access_control_lib->has_reached_funnel_cap(NULL, $funnel_count)) {
			$cap = $this->access_control_lib->max_funnels();
			header('HTTP/1.1 403 Forbidden');
			echo 'FUNNEL_LIMIT_REACHED:' . $cap;
			return;
		}

		$this->load->library('slug_lib');
		$this->load->library('lang_lib');
		$language_code = $this->lang_lib->normalize(isset($_POST['language_code']) ? $_POST['language_code'] : 'en');

		// Build funnel data with new wizard fields
		$funnel_data = array(
			'campaign_name'     => $funnel_name,
			'slug'              => $this->slug_lib->make_funnel_slug($funnel_name),
			'language_code'     => $language_code,
			'user_id'           => $userID,
			'selected_channels' => isset($_POST['selected_channels']) ? $_POST['selected_channels'] : '["ai_calling","sms","email","thankyou"]',
			'followup_mode'     => isset($_POST['followup_mode']) && $_POST['followup_mode'] === 'personalized' ? 'personalized' : 'template',
		);

		// Stamp the create-via provenance so analytics/dashboards can distinguish
		// template-mode from website-mode funnels. Only when the column exists.
		if ($this->db->field_exists('created_via', 'funnels')) {
			$funnel_data['created_via'] = 'template';
		}

		$funnel_id = $this->Datamodel->insertTable('funnels', $funnel_data);

		// Save extra context and call purpose for later use in pipeline
		$extra_context = isset($_POST['extra_context']) ? $_POST['extra_context'] : '';

		// Persist extra_context on the funnels row so the background build
		// pipeline (Website_analysis::_run_build_pipeline) can read it back when
		// no website was provided. This is the only signal that lets template-
		// mode funnels generate a meaningful knowledge_base / followups / chatbot.
		// Guarded behind field_exists so the migration (030_funnel_extra_context.sql)
		// can ship after this code without breaking deploys.
		if ($extra_context !== '' && $this->db->field_exists('extra_context', 'funnels')) {
			$this->Datamodel->updateTable('funnels', array('id' => $funnel_id), array('extra_context' => $extra_context));
		}
		$call_purpose  = isset($_POST['call_purpose']) ? $_POST['call_purpose'] : 'lead_followup';
		$voice_engine  = isset($_POST['voice_engine']) ? $_POST['voice_engine'] : 'gemini';
		$voice_id      = isset($_POST['voice_id']) ? $_POST['voice_id'] : 'ash';
		$call_delay    = isset($_POST['call_delay']) ? intval($_POST['call_delay']) : 0;
		$greeting      = isset($_POST['greeting']) ? $_POST['greeting'] : '';
		$persona       = isset($_POST['persona']) ? $_POST['persona'] : '';

		// Create voice_call_config record
		$selected_channels = json_decode($funnel_data['selected_channels'], true);
		$voice_active = is_array($selected_channels) && in_array('ai_calling', $selected_channels) ? 1 : 0;

		// Get from_number from the user's most recent Twilio call service
		$from_number = '';
		$call_svc = $this->db->where('user_id', $userID)->order_by('id', 'DESC')->get('twilio_call_services')->row();
		if (!empty($call_svc->phone_number)) {
			$from_number = $call_svc->phone_number;
		}

		$google_sheet_url    = isset($_POST['google_sheet_url']) ? $_POST['google_sheet_url'] : '';
		$booking_provider    = isset($_POST['booking_provider']) && $_POST['booking_provider'] !== ''
			? $_POST['booking_provider']
			: 'google_sheets';
		$posted_ghl_calendar = isset($_POST['ghl_calendar_id']) ? $_POST['ghl_calendar_id'] : '';
		$posted_ghl_account  = isset($_POST['ghl_account_id']) ? (int) $_POST['ghl_account_id'] : 0;
		// Appointment booking is on whenever the user picked "appointment_booking"
		// as the call purpose — the provider can be wired up later (e.g. GC tag/
		// appointment, GHL calendar, or manual handoff). Don't gate the flag on
		// google_sheet_url being filled, that misses GHL/Manual/GC funnels.
		$appointment_booking = ($call_purpose === 'appointment_booking') ? 1 : 0;

		// For GHL, resolve the location_id from the user's PICKED ghl_integrations
		// row (not the first active one) — the wizard now hands us the specific
		// sub-account via $posted_ghl_account. Falls back to the first active row
		// only if the wizard somehow posted no account_id (legacy submit path).
		$resolved_ghl_account_row = null;
		$resolved_location = null;
		if ($booking_provider === 'ghl' && $this->db->table_exists('ghl_integrations')) {
			$q = $this->db->where('user_id', $userID)->where('is_active', 1);
			if ($posted_ghl_account > 0) $q = $q->where('id', $posted_ghl_account);
			$ghl_row = $q->limit(1)->get('ghl_integrations')->row();
			if (!empty($ghl_row)) {
				$resolved_ghl_account_row = $ghl_row;
				$resolved_location = $ghl_row->location_id;
			}
		}

		// Welcome touchpoints — the immediate email + SMS that fire when a lead
		// submits the form (separate from the drip sequences which live on
		// funnels.followups_data). The website flow writes these via its
		// build pipeline; the template flow used to leave them NULL, so leads
		// never received a welcome email or SMS. Capture from the wizard now.
		$welcome_email_subject = isset($_POST['email_subject']) ? trim($_POST['email_subject']) : '';
		$welcome_email_body    = isset($_POST['email_body'])    ? trim($_POST['email_body'])    : '';
		$welcome_sms_preview   = isset($_POST['sms_preview'])   ? trim($_POST['sms_preview'])   : '';
		$_email_tone_for_cfg   = isset($_POST['email_tone'])    ? $_POST['email_tone']          : 'warm';
		$_sms_tone_for_cfg     = isset($_POST['sms_tone'])      ? $_POST['sms_tone']            : 'friendly';
		$email_config_json = ($welcome_email_subject !== '' || $welcome_email_body !== '')
			? json_encode(array('tone' => $_email_tone_for_cfg, 'subject' => $welcome_email_subject, 'body' => $welcome_email_body))
			: null;
		$sms_config_json = ($welcome_sms_preview !== '')
			? json_encode(array('tone' => $_sms_tone_for_cfg, 'preview' => $welcome_sms_preview))
			: null;

		$this->Datamodel->insertTable('voice_call_config', array(
			'funnel_id'           => $funnel_id,
			'user_id'             => $userID,
			'engine'              => $voice_engine,
			'language_code'       => $language_code,
			'voice'               => $voice_id,
			'from_number'         => $from_number,
			'call_purpose'        => $call_purpose,
			'call_delay'          => $call_delay,
			'greeting'            => $greeting,
			'persona'             => $persona,
			'email_config'        => $email_config_json,
			'sms_config'          => $sms_config_json,
			'is_active'           => $voice_active,
			'appointment_booking' => $appointment_booking,
			'booking_provider'    => $booking_provider,
			'google_sheet_url'    => ($booking_provider === 'google_sheets' && !empty($google_sheet_url)) ? $google_sheet_url : null,
			'ghl_calendar_id'     => ($booking_provider === 'ghl' && !empty($posted_ghl_calendar)) ? $posted_ghl_calendar : null,
			'ghl_location_id'     => ($booking_provider === 'ghl') ? $resolved_location : null,
		));

		// Per-funnel GHL binding: ghl_funnel_bindings is the new source of truth
		// for which sub-account + calendar each funnel books into. The
		// voice_call_config columns above stay populated for transition-window
		// bridge compat. Mirrors Twilio_call::save_ghl_binding().
		if ($booking_provider === 'ghl'
			&& !empty($posted_ghl_calendar)
			&& $resolved_ghl_account_row
			&& $this->db->table_exists('ghl_funnel_bindings')
		) {
			$binding_data = array(
				'funnel_id'          => $funnel_id,
				'user_id'            => $userID,
				'ghl_integration_id' => (int) $resolved_ghl_account_row->id,
				'location_id'        => (string) $resolved_ghl_account_row->location_id,
				'calendar_id'        => (string) $posted_ghl_calendar,
			);
			// Upsert by funnel_id (table has UNIQUE KEY uq_funnel).
			$existing_binding = $this->db->where('funnel_id', $funnel_id)->get('ghl_funnel_bindings')->row();
			if ($existing_binding) {
				$this->db->where('id', $existing_binding->id)->update('ghl_funnel_bindings', $binding_data);
			} else {
				$this->db->insert('ghl_funnel_bindings', $binding_data);
			}
		}

		// Save SMS/Email tone preferences
		$sms_tone   = isset($_POST['sms_tone']) ? $_POST['sms_tone'] : 'friendly';
		$email_tone = isset($_POST['email_tone']) ? $_POST['email_tone'] : 'warm';

		// Create client record (Step 5: Save Client Record)
		$business_name = isset($_POST['business_name']) && !empty($_POST['business_name']) ? $_POST['business_name'] : $funnel_name;
		$industry      = isset($_POST['industry']) ? trim($_POST['industry']) : '';
		$services      = isset($_POST['services']) ? trim($_POST['services']) : '';
		$client_data = array(
			'user_id'       => $userID,
			'business_name' => $business_name,
			'language_code' => $language_code,
			'extra_context'  => $extra_context,
			'knowledgebase'  => $extra_context,
			'status'         => 'active',
		);
		// Industry / services live on the clients row (mirroring the website
		// wizard's client insert) so downstream stages — KB generation,
		// persona/SMS/email generation, chatbot training — can pull them
		// without re-reading the POST.
		if ($industry !== '') $client_data['industry'] = $industry;
		if ($services !== '') $client_data['services'] = $services;
		$client_id = $this->Datamodel->insertTable('clients', $client_data);

		// Link funnel to client
		$this->Datamodel->updateTable('funnels', array('id' => $funnel_id), array('client_id' => $client_id));

		// Store context in session for pipeline to pick up
		$this->session->set_userdata('funnel_extra_context', $extra_context);
		$this->session->set_userdata('funnel_call_purpose', $call_purpose);
		$this->session->set_userdata('funnel_sms_tone', $sms_tone);
		$this->session->set_userdata('funnel_email_tone', $email_tone);
		$this->session->set_userdata('funnel_language_code', $language_code);

		// AI-powered template selection and page content generation
		$ai_rec = $this->_generate_template_recommendation($business_name, $extra_context, $call_purpose);

		if (!empty($ai_rec) && !empty($ai_rec['template_id'])) {
			$template_id = $ai_rec['template_id'];
			$page_content = $ai_rec['page_content'];
			$keywords = $ai_rec['keywords'];

			// Build ai_data in the same format the pipeline uses
			$ai_data = array(
				'step1_data' => array(
					'Recommended_Funnel' => array(
						'template_id' => $template_id,
						'name' => $keywords,
					),
				),
				'step2_data' => array(
					'page_content' => $page_content,
					'selected_hook' => $business_name . ' — ' . $call_purpose,
				),
			);

			// Apply AI content to template HTML and save as draft
			$applied = $this->_apply_content_to_template($template_id, $page_content, $funnel_id, $funnel_name);

			$update = array(
				'template_id' => $template_id,
				'keywords'    => $keywords,
				'ai_data'     => json_encode($ai_data),
				// Use the AI's keyword set as the funnel's category at create
				// time so dashboards don't show empty until the background
				// pipeline finishes Step 1. The pipeline preserves this
				// existing value (see Website_analysis._run_build_pipeline).
				'category'    => $keywords,
			);

			if (!empty($applied) && !empty($applied['content'])) {
				$update['content']            = $applied['content'];
				$update['site_uid']           = $applied['site_uid'];
				$update['thankyou_content']   = $applied['thankyou_content'];
				$update['thanku_site_uid']    = $applied['thankyou_site_uid'];
				$update['thankyou_page_link'] = $applied['thankyou_page_link'];
				$update['thank_template_id']  = $applied['thankyou_template_id'];
			}

			$this->Datamodel->updateTable('funnels', array('id' => $funnel_id), $update);
			log_message('debug', 'WIZARD: AI template=' . $template_id . ' | keywords=' . $keywords . ' | content saved for funnel ' . $funnel_id);
		}

		// The legacy detached funnel-build (website_analysis worker) has been
		// removed — building Bridges now happens through the Agentic Affiliates flow.
		echo $funnel_id;
	}

	/**
	 * AI-powered template selection and page content generation for the wizard.
	 * Uses Gemini to pick the best template from available options and generate
	 * page content matching the template's JSON structure.
	 *
	 * @param string $business_name
	 * @param string $extra_context
	 * @param string $call_purpose
	 * @return array ['template_id'=>int, 'page_content'=>array, 'keywords'=>string] or empty on failure
	 */
	private function _generate_template_recommendation($business_name, $extra_context, $call_purpose)
	{
		$this->load->library('gemini_lib');
		$this->load->config('template_config');
		$templates = $this->config->item('template');
		$fe = isset($templates['FE']) ? $templates['FE'] : array();

		// Build template list for Gemini
		$template_list = [];
		foreach ($fe as $id => $t) {
			if ($id >= 101 && $id <= 127) {
				$template_list[] = $id . ' = ' . $t['name'];
			}
		}
		$template_list_str = implode("\n", $template_list);

		// Step 1: Ask Gemini to pick best template
		$pick_prompt = "You are selecting a landing page template for a local business lead generation funnel.

Business Name: {$business_name}
Business Context: {$extra_context}
Call Purpose: {$call_purpose}

Available templates (ID = Category):
{$template_list_str}
124 = Default Niche (use ONLY if no other template fits)

Pick the SINGLE best template for this business. Return ONLY a JSON object (no markdown, no code blocks):
{\"template_id\": 108, \"keywords\": \"Electrical, Wiring, Electrician\", \"reason\": \"one sentence why\"}

Rules:
- template_id must be one of the IDs listed above
- keywords: 2-4 comma-separated terms that describe the business niche
- Pick the closest match. For businesses that don't exactly match, pick the most similar category (e.g. a locksmith → 119 Locksmith, a moving company → 124 Default Niche)";

		$pick_result = $this->gemini_lib->gemini_api($pick_prompt);

		// Handle markdown-wrapped JSON
		if (is_string($pick_result)) {
			$cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($pick_result));
			$cleaned = preg_replace('/```\s*$/', '', $cleaned);
			$pick_result = json_decode(trim($cleaned), true);
		}

		if (!is_array($pick_result) || empty($pick_result['template_id'])) {
			log_message('error', 'WIZARD AI: Template pick failed — ' . json_encode($pick_result));
			return array();
		}

		$template_id = intval($pick_result['template_id']);
		$keywords = isset($pick_result['keywords']) ? $pick_result['keywords'] : $business_name;

		// Validate template_id exists
		if (!isset($fe[$template_id])) {
			$template_id = 124; // Default Niche fallback
		}

		log_message('debug', 'WIZARD AI: Template picked — id=' . $template_id . ' (' . $fe[$template_id]['name'] . ') | keywords=' . $keywords);

		// Step 2: Generate page content matching template JSON format.
		// Delegated to _generate_page_content_for_template so the same logic can
		// be reused when the user manually picks a different template later in
		// edit_template() — each template has its own class-name structure, so
		// page_content built for template A cannot be safely reapplied to
		// template B without regenerating against B's JSON format file.
		$content_result = $this->_generate_page_content_for_template($template_id, $business_name, $extra_context, $call_purpose);

		return array(
			'template_id'  => $template_id,
			'keywords'     => $keywords,
			'page_content' => $content_result,
		);
	}

	/**
	 * Generate page content (text values keyed by CSS class) for a SPECIFIC template.
	 *
	 * Each template under landing-pages/jsondata/{template_id}.txt declares its
	 * own slot layout — the class names, max-length hints, and section keys are
	 * template-specific. Calling this with template A's id and then applying the
	 * result to template B's HTML silently drops anything that lives in classes
	 * only B knows about (the middle-of-page sections that differ between layouts).
	 *
	 * Used by:
	 *   - _generate_template_recommendation() during the initial wizard run.
	 *   - edit_template() when the user manually switches templates from the
	 *     /funnel_types/{id} gallery, so the new template's slots get filled
	 *     with this business's copy instead of staying as placeholder text.
	 *
	 * Returns the decoded page_content array, or [] if the format file is missing
	 * or the AI call fails (callers should treat empty as "skip the rewrite").
	 */
	private function _generate_page_content_for_template($template_id, $business_name, $extra_context, $call_purpose)
	{
		$this->load->library('gemini_lib');

		$json_format_path = FCPATH . ic_template_path('landing-pages/jsondata/' . (int) $template_id . '.txt');
		if (!file_exists($json_format_path)) {
			log_message('error', 'WIZARD AI: JSON format file not found — ' . $json_format_path);
			return array();
		}

		$output_format = file_get_contents($json_format_path);

		$content_prompt = 'Generate landing page content for a local business funnel.

Business Name: ' . $business_name . '
Business Context: ' . $extra_context . '
Call Purpose: ' . $call_purpose . '

Using the business info above, generate page content that replaces all placeholder text in the template below. Keep the EXACT same JSON structure — only change the "text" values.

Rules:
- Replace ALL placeholder text with content specific to this business
- Keep it persuasive and conversion-focused
- Use the business name in headlines
- Keep phone numbers and addresses as realistic placeholders if not provided (use "Call Now" style CTAs)
- For testimonials, generate realistic-sounding ones relevant to the business
- Return ONLY valid JSON, no markdown, no code blocks

Template JSON format to fill:
' . $output_format;

		$content_result = $this->gemini_lib->gemini_api($content_prompt);

		// Handle markdown-wrapped JSON
		if (is_string($content_result)) {
			$cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($content_result));
			$cleaned = preg_replace('/```\s*$/', '', $cleaned);
			$content_result = json_decode(trim($cleaned), true);
		}

		if (!is_array($content_result) || empty($content_result)) {
			log_message('error', 'WIZARD AI: Page content generation failed for template ' . $template_id . ' — falling back to template defaults');
			$content_result = json_decode($output_format, true);
		}

		log_message('debug', 'WIZARD AI: Page content generated for template ' . $template_id . ' — keys=' . (is_array($content_result) ? implode(',', array_keys($content_result)) : 'none'));

		return is_array($content_result) ? $content_result : array();
	}

	/**
	 * Apply AI-generated page_content JSON to a template HTML file.
	 * Replaces text inside elements matching CSS classes from the JSON.
	 * Returns the full wrapped content ready to save in funnels.content.
	 */
	private function _apply_content_to_template($template_id, $page_content, $funnel_id, $funnel_name)
	{
		$this->load->config('template_config');
		$templates = $this->config->item('template');
		$fe = isset($templates['FE']) ? $templates['FE'] : array();

		if (!isset($fe[$template_id])) return '';

		$template_html_path = './' . ic_template_path($fe[$template_id]['html']);
		$template_html = @file_get_contents($template_html_path);
		if (empty($template_html)) return '';

		// Drop every <script>…</script>, exactly as Bridge_build_lib::_load_template_html()
		// does. A template's own scripts are design-time demos (e.g. the thank-you
		// card's setInterval that cycles the waveform through fake states); the real
		// runtime is injected by the renderer. Baking one in means it fights the live
		// wiring forever — it re-set data-state every 2.6s on top of the real call
		// state, so the page kept "changing by itself" after the call had ended.
		$template_html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $template_html);

		// Fix relative paths. The landing-pages2 set uses "assets/{img,logos}/…"
		// while the default set uses "../images|fonts|js/…" — branch on the
		// loaded path so the editor preview shows the right artwork either way.
		if (ic_template_is_v2($template_html_path)) {
			$template_html = ic_template_rewrite_assets($template_html);
		} else {
			$base = base_url('landing-pages/');
			$template_html = str_replace('../images/', $base . 'images/', $template_html);
			$template_html = str_replace('../fonts/', $base . 'fonts/', $template_html);
			$template_html = str_replace('../js/', $base . 'js/', $template_html);
		}

		// Replace text content by CSS class
		if (!empty($page_content) && is_array($page_content)) {
			foreach ($page_content as $category => $sections) {
				if (!is_array($sections)) continue;
				foreach ($sections as $section_name => $items) {
					if (!is_array($items)) continue;
					foreach ($items as $item) {
						if (!empty($item['class']) && isset($item['text'])) {
							$class = preg_quote($item['class'], '/');
							$safe_text = htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8');
							// Use a callback so `$`-digit sequences in generated copy
							// (e.g. "$1.3M", "$10/mo") are inserted literally — a plain
							// preg_replace replacement string would treat `$1`/`$2` as
							// backreferences and silently drop them.
							$template_html = preg_replace_callback(
								'/(<[^>]*class="[^"]*' . $class . '[^"]*"[^>]*>)(.*?)(<\/[a-z0-9]+>)/is',
								function ($m) use ($safe_text) {
									return $m[1] . $safe_text . $m[3];
								},
								$template_html,
								1
							);
						}
					}
				}
			}
		}

		// Build site_uid
		$site_uid = str_ireplace('=', '', base64_encode($funnel_id . '|' . $this->user_id . '|landing'));
		$thankyou_site_uid = str_ireplace('=', '', base64_encode($funnel_id . '|' . $this->user_id . '|thank'));
		$thankyou_page_link = base_url('webpages/thankyou/' . $thankyou_site_uid);

		$global_data = $this->config->item('global_data');
		$faviconimg = base_url($global_data['fav_url']);

		$header = '<meta charset="utf-8">'
			. '<meta http-equiv="X-UA-Compatible" content="IE=edge">'
			. '<meta name="viewport" content="width=device-width, initial-scale=1">'
			. '<title>' . htmlspecialchars($funnel_name) . '</title>'
			. '<meta name="description" class="description" content="">'
			. '<meta name="keywords" class="keywords" content="">'
			. '<link rel="icon" id="favadd" href="' . $faviconimg . '">'
			. '<style id="scrollcss">::-webkit-scrollbar-thumb { background:#8e4599;border-radius: 2px;}'
			. '::-webkit-scrollbar-button {display: none;}'
			. '::-webkit-scrollbar {background-color:#d78b79a6;height: 4px;width: 7px;}'
			. '.alertDiv{position: fixed;bottom: 12px;right: 10px;background: #8e4599;color:#ffffff;display: none;padding: 10px 59px;}'
			. '</style>'
			. '<div class="analyticsCode" style="display:none;"></div>'
			. '<div class="retarget_analyticsCode" style="display:none;"></div>'
			. '<div class="chatbotEmbedCode" style="display:none;"></div>';

		$body = $template_html
			. '<div id="paste_html_code" style="display:none"></div>'
			. '<input type="hidden" id="submissiontype" value="redirect_user">'
			. '<input type="hidden" id="redirect_url" value="' . $thankyou_page_link . '">'
			. '<input type="hidden" id="redirect_userto" value="_self">'
			. '<input type="hidden" id="messageaftersuccess">'
			. '<input type="hidden" id="messageafterfailed">';

		$content = '<funnelrh id="funnelrh">' . $header . '</funnelrh>'
				 . '<funnelrb id="funnelrb">' . $body . '</funnelrb>';

		// Also build thank-you page
		$thankyou_template_id = isset($fe[$template_id]['thankyou_template_id']) ? $fe[$template_id]['thankyou_template_id'] : 0;
		$thankyou_content = '';
		if (!empty($thankyou_template_id)) {
			$this->load->config('thankyou_config');
			$ty_template = $this->config->item($thankyou_template_id, 'thankyou');
			if (!empty($ty_template['html'])) {
				$ty_rel  = ic_template_path($ty_template['html']);
				$ty_html = @file_get_contents('./' . $ty_rel);
				if (!empty($ty_html)) {
					// Same strip as the FE template above — this is the path that baked
					// the demo waveform loop into funnels.thankyou_content.
					$ty_html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $ty_html);
					// Absolutise artwork against whichever set the file came from.
					if (ic_template_is_v2($ty_rel)) {
						$ty_html = ic_template_rewrite_assets($ty_html);
					} else {
						$ty_base = base_url('landing-pages/');
						$ty_html = str_replace('../images/', $ty_base . 'images/', $ty_html);
						$ty_html = str_replace('../fonts/', $ty_base . 'fonts/', $ty_html);
					}
					$thankyou_content = '<funnelrh1 id="funnelrh1">' . $header . '</funnelrh1>'
									  . '<funnelrb1 id="funnelrb1">' . $ty_html . '</funnelrb1>';
				}
			}
		}

		return array(
			'content'            => $content,
			'thankyou_content'   => $thankyou_content,
			'site_uid'           => $site_uid,
			'thankyou_site_uid'  => $thankyou_site_uid,
			'thankyou_page_link' => $thankyou_page_link,
			'thankyou_template_id' => $thankyou_template_id,
		);
	}

	

	//select template page
	public function edit_template($id,$funnel_id)
	{
		if(!empty($id))
		{
			$template_id=$id;
			$accessArr=$this->ion_auth->get_current_user_access();

			// if($template_id >= 111){
			// 	$this->load->config('thankyou_config');
			// 	$template_file=$this->config->item($template_id, 'thankyou');
			// 	$this->data['funnel_type'] = "thankyou";
			// }else{
			// 	$this->load->config('template_config');
			// 	$template_arr=$this->config->item('FE', 'template');
			// 	$template_file=$template_arr[$template_id];
			// 	$this->data['funnel_type'] = "landing";
			// }

			$this->load->config('template_config');
			$template_arr=$this->config->item('FE', 'template');
			$template_file=$template_arr[$template_id];
			$this->data['funnel_type'] = "landing";


			$thankyou_template_id = $template_arr[$template_id]['thankyou_template_id'];
			$this->load->config('thankyou_config');
			$thankyou_template_file=$this->config->item($thankyou_template_id, 'thankyou');
			$this->data['thankyou_template_id'] = $thankyou_template_id;
			// $this->data['funnel_type'] = "thankyou";



			$u = $this->ion_auth->user()->row();
			$user_id = $u->id;

			$this->data['user_id'] = $user_id;
			$this->data['funnel_id'] = $funnel_id;
			$this->data['count'] = $template_id;
			$this->data['templatefile'] = './'.ic_template_path($template_file['html']);
			$this->data['thankyou_templatefile'] = './'.ic_template_path($thankyou_template_file['html']);

			$numbers = $this->twilio_lib->get_available_phone_numbers();
			$this->data['phone_numbers'] = $numbers;

			$twilio_calling_service_data = $this->Datamodel->fetchTableData('twilio_call_services',array('funnel_id'=>$funnel_id));
			$this->data['twilio_calling_services'] = $twilio_calling_service_data;

			// Load AI voice call config for this funnel
			$this->data['voice_call_config'] = $this->Datamodel->getSingleRow('voice_call_config', array('funnel_id' => $funnel_id));

			$templatedata = $this->Datamodel->fetchTableData('funnels',array('id'=>$funnel_id));

			// SECURITY (IDOR): only the owner (or super-admin user 1) may open the
			// editor for a funnel. Without this, any logged-in user could load
			// another tenant's Bridge content, knowledge base, voice config and
			// caller-ID by passing their funnel_id in the URL. The template-switch
			// WRITE path below is already owner-gated; this closes the read path.
			$owner_row = isset($templatedata[0]['Data']) ? $templatedata[0]['Data'] : null;
			$owner_uid = ($owner_row && isset($owner_row->user_id)) ? (int) $owner_row->user_id : 0;
			if (!$owner_row || ((int) $user_id !== 1 && $owner_uid !== (int) $user_id)) {
				$this->session->set_flashdata('error', 'That Bridge is not available.');
				redirect('app');
				return;
			}

			$ai_data = $templatedata[0]['Data']->ai_data;
			if(!empty($ai_data))
			{
				$ai_data = json_decode($ai_data, true);
				$step2_data	= $ai_data['step2_data'];
				$page_content = $step2_data['page_content'];
			}else{
				$page_content = '';
			}

			// Persist the user's manual template pick.
			// When the user clicks a template tile in /funnel_types/{id}, this
			// controller renders the editor against $template_id but the funnels
			// row still points at the AI-recommended template that was set during
			// the wizard. Without this update, leaving the editor and returning
			// (e.g. via "Edit Landing Page") sends the user back to the original
			// AI pick, and funnels.content / funnels.thankyou_content still hold
			// HTML rendered against the old template — so a published page would
			// show the old layout even after the user thought they switched.
			//
			// Critical detail: every template under landing-pages/jsondata/{id}.txt
			// declares its OWN class-name structure. The page_content that was
			// generated by _generate_template_recommendation() during the wizard
			// is keyed by the AI-picked template's classes. Re-applying it to a
			// different template's HTML leaves that template's slots empty (since
			// preg_replace finds no matching class names) — the editor would open
			// with placeholder Lorem-style copy instead of the user's actual
			// business content.
			//
			// Fix: when the picked template differs from the stored one,
			//   1. regenerate page_content against the new template's JSON format,
			//   2. apply it to the new template's HTML via _apply_content_to_template,
			//   3. update funnels.template_id, thank_template_id, content, thankyou_content,
			//      and refresh funnels.ai_data so the editor reads the new content.
			$row_obj = isset($templatedata[0]['Data']) ? $templatedata[0]['Data'] : null;
			$stored_owner    = $row_obj && isset($row_obj->user_id) ? (int) $row_obj->user_id : 0;
			$stored_template = $row_obj && isset($row_obj->template_id) ? (int) $row_obj->template_id : 0;
			$funnel_name_for_render = $row_obj && isset($row_obj->campaign_name) ? $row_obj->campaign_name : '';
			if ($stored_owner === (int) $user_id && (int) $template_id !== $stored_template) {
				// Rehydrate the business context the wizard captured.
				$ctx_business    = $funnel_name_for_render;
				$ctx_extra       = $row_obj && !empty($row_obj->knowledge_base) ? $row_obj->knowledge_base : '';
				$voice_cfg_row   = $this->Datamodel->getSingleRow('voice_call_config', array('funnel_id' => (int) $funnel_id));
				$ctx_call_purpose = ($voice_cfg_row && !empty($voice_cfg_row->call_purpose)) ? $voice_cfg_row->call_purpose : 'lead_followup';

				$new_page_content = $this->_generate_page_content_for_template((int) $template_id, $ctx_business, $ctx_extra, $ctx_call_purpose);
				// If regeneration failed (missing format file or Gemini unavailable)
				// fall back to the existing page_content so the editor still has
				// something to show — the user can still hand-edit the template.
				if (empty($new_page_content)) {
					$new_page_content = $page_content;
					log_message('warning', 'TEMPLATE SWITCH: page_content regeneration failed for funnel ' . $funnel_id . ' template ' . $template_id . ' — reusing previous content (slot mismatch possible)');
				}

				$applied = $this->_apply_content_to_template((int) $template_id, $new_page_content, (int) $funnel_id, $funnel_name_for_render);
				$update = array('template_id' => (int) $template_id);
				if (!empty($applied) && is_array($applied)) {
					if (isset($applied['content']))             $update['content']             = $applied['content'];
					if (isset($applied['thankyou_content']))    $update['thankyou_content']    = $applied['thankyou_content'];
					if (isset($applied['thankyou_template_id'])) $update['thank_template_id'] = $applied['thankyou_template_id'];
				}

				// Refresh ai_data.step2_data.page_content so subsequent edit_template
				// calls (and the editor view below) pick up the regenerated content.
				$ai_data_cur = $ai_data;
				if (!is_array($ai_data_cur)) $ai_data_cur = array();
				if (!isset($ai_data_cur['step2_data']) || !is_array($ai_data_cur['step2_data'])) $ai_data_cur['step2_data'] = array();
				$ai_data_cur['step2_data']['page_content'] = $new_page_content;
				if (!isset($ai_data_cur['step1_data']) || !is_array($ai_data_cur['step1_data'])) $ai_data_cur['step1_data'] = array();
				if (!isset($ai_data_cur['step1_data']['Recommended_Funnel']) || !is_array($ai_data_cur['step1_data']['Recommended_Funnel'])) $ai_data_cur['step1_data']['Recommended_Funnel'] = array();
				$ai_data_cur['step1_data']['Recommended_Funnel']['template_id'] = (int) $template_id;
				$update['ai_data'] = json_encode($ai_data_cur);

				$this->Datamodel->updateTable('funnels', array('id' => (int) $funnel_id), $update);

				// Refresh local copies so the editor view shows the just-saved values.
				$templatedata = $this->Datamodel->fetchTableData('funnels', array('id' => $funnel_id));
				$page_content = $new_page_content;
			}

			$this->data['page_content'] = $page_content;
			$this->data['templatedata'] = $templatedata;

			$this->data['autoresponders'] = $this->Datamodel->fetchTableData('autoresponder',array('user_id'=>$user_id,'status'=>1));

			// Per-user editor autosave preference (persisted in settings table)
			$autosave_row = $this->db->where(['user_id' => $user_id, 'meta_type' => 'editor_autosave'])->get('settings')->row();
			$this->data['autosave_enabled'] = ($autosave_row && (int) $autosave_row->meta_data === 1) ? 1 : 0;

			$this->data['ActiveTab']='editor';
			$this->data['PageTitle']='Page Editor';

			// The funnel row backing this editor session (used for the Share link).
			$widget_funnel_row = isset($templatedata[0]['Data']) ? $templatedata[0]['Data'] : null;

			// Super-admin gate (still read elsewhere in the editor chrome).
			$this->data['is_super_admin'] = ((int) $user_id === 1);

			// Distribution (Embed widget · HTML form · Custom domain) moved OUT of
			// this publish modal onto the dedicated Share & Publish page. The modal
			// now just links there; pass the target URL keyed by the funnel's
			// site_uid (the distribute controller resolves the Bridge by site_uid).
			// OTO1 GATE — Share & Publish is OTO1+ (Bundle/Fastpass/full-access);
			// leaving distribute_url blank hides the modal's "Share & Publish ↗" tab
			// for tiers without it (the distribute page itself also hard-gates).
			$this->data['distribute_url'] = ($widget_funnel_row && !empty($widget_funnel_row->site_uid)
					&& $this->access_control_lib->can_use_custom_domain())
				? site_url('app/distribute/' . rawurlencode($widget_funnel_row->site_uid))
				: '';

			// The editor mounts the shared ic_shell sidebar partial directly (it
			// keeps its own dark skin rather than the standard ic_shell/layout),
			// so the gating flags the partial reads (is_ic_admin / is_ic_superadmin)
			// aren't computed by Ic_render_lib::render() the way they are on every
			// other page. Compute them here so the Admin group (Manage Users,
			// White-label, API Config, …) shows up instead of dropping out.
			$this->load->library('ic_render_lib');
			$this->data = $this->ic_render_lib->sidebar_flags($this->data);

			$this->load->view("sites/editor",$this->data);
		}
	}

	public function editcampaign($funnel_id,$template_id,$page_type){
		// $funnel_id=$_REQUEST['funnel_id'];
		// $template_id=$_REQUEST['template_id'];
		// $page_type=$_REQUEST['page_type'];

		$this->data['PageTitle']='Templates';
		$this->session->set_userdata('funnel_id',$funnel_id);
		$this->data['funnel_id']=$funnel_id;

		// if($template_id == 'landing'){
		// 	$this->load->config('template_config');
		// 	$this->data["templatesdata"]=$this->config->item('template');
		// 	$this->data['funnel_type']='landing';
		// 	$this->load->view("pages/funnel-types2",$this->data);
		// }else if($template_id == 'thankyou'){
		// 	$this->load->config('thankyou_config');
		// 	$this->data["thankyoutemplates"]=$this->config->item('thankyou');
		// 	$this->data['funnel_type']='Thank You';
		// 	$this->load->view("pages/funnel-types2",$this->data);
		// }else{
		// 	// echo $template_id;exit;
		// 	$this->edit_template($template_id);
		// }

		
		$this->data['funnel_id'] = $funnel_id;

		if(($template_id == 'process' || $template_id == 'landing') && $page_type == 'landing'){
			$this->load->config('template_config');
			$this->data["templatesdata"]=$this->config->item('template');
			$this->data['funnel_type']='Landing';
			$this->load->view("pages/funnel-types2",$this->data);
		}else if(($template_id == 'process' || $template_id == 'thankyou') && $page_type == 'thankyou'){
			$this->load->config('thankyou_config');
			$this->data["thankyoutemplates"]=$this->config->item('thankyou');
			$this->data['funnel_type']='Thank You';
			$this->load->view("pages/funnel-types2",$this->data);
		}else{
			// echo $template_id;exit;
			$this->edit_template($template_id,$funnel_id);
		}

		
	}

	/**
	 * Toggle the per-user editor autosave preference.
	 * Stored in the settings table as meta_type = 'editor_autosave', meta_data = '0' | '1'.
	 */
	public function save_autosave_toggle()
	{
		$enabled = (int) $this->input->post('enabled') === 1 ? 1 : 0;

		$existing = $this->db->where(['user_id' => $this->user_id, 'meta_type' => 'editor_autosave'])
		                     ->get('settings')->row();

		if ($existing) {
			$this->db->where('id', $existing->id)->update('settings', ['meta_data' => (string) $enabled]);
		} else {
			$this->db->insert('settings', [
				'user_id'   => $this->user_id,
				'meta_type' => 'editor_autosave',
				'meta_data' => (string) $enabled,
			]);
		}

		echo json_encode(['ok' => true, 'enabled' => $enabled]);
	}

	public function submitAction()
	{
		$funnel_id=$_REQUEST['funnel_id'];
		$camp_id = $funnel_id;
		$template_id=$_REQUEST['template_id'];
		$thankyou_template_id=$_REQUEST['thankyou_template_id'];

		

		$analyticsCode = $_POST['analyticsCode'];
		$retarget_analyticsCode = $_POST['retarget_analyticsCode'];
		$chatbotEmbedCode = $_POST['chatbotEmbedCode'];

		$script_code = array();
		if( !empty($analyticsCode) || !empty($retarget_analyticsCode) || !empty($chatbotEmbedCode) ){
			$script_code = array( 'analyticsCode' => $analyticsCode, 'retarget_analyticsCode' => $retarget_analyticsCode, 'chatbotEmbedCode' => $chatbotEmbedCode );
		}
		

		$u = $this->ion_auth->user()->row();
		$user_id = $u->id;


		$header=$_POST['header1'];
		$body=$_POST['body1'];
		$html='<funnelrh id="funnelrh">'.$header.'</funnelrh><funnelrb id="funnelrb">'.$body.'</funnelrb>';

		$thankyou_header = $_POST['thankyou_header'];
		$thankyou_body = $_POST['thankyou_body'];
		$thankyou_html = '<funnelrh1 id="funnelrh1">'.$thankyou_header.'</funnelrh1><funnelrb1 id="funnelrb1">'.$thankyou_body.'</funnelrb1>';

		
		if($user_id == 1){
			$where=array('id'=>$funnel_id);
		}else{
			$where=array('user_id'=>$user_id,'id'=>$funnel_id);
		}


		$site_uid = str_ireplace("=","",base64_encode($camp_id.'|'.$user_id.'|landing'));
		$thankyou_site_uid = str_ireplace("=","",base64_encode($camp_id.'|'.$user_id.'|thank'));

		// Agentic Affiliates Bridge funnels (template 201–206) capture leads through the
		// Node bridge-service, which resolves the funnel by `site_uid` = the slug
		// (POST /v1/embed/capture → getBySiteUid). Rewriting site_uid to the legacy
		// base64 token here would orphan capture after an edit, so pin both uids to
		// the slug for Bridge funnels. (Slug is computed just below.)
		$ic_is_bridge = ((int) $template_id >= 201 && (int) $template_id <= 210);

		// Load the existing funnel so we know its current slug (and accept a
		// new slug submitted from the publish modal).
		$funnel_row = $this->db->where('id', $funnel_id)->get('funnels')->row();
		$current_slug = $funnel_row ? $funnel_row->slug : '';

		$this->load->library('slug_lib');
		$posted_slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
		if ($posted_slug !== '') {
			$clean = $this->slug_lib->normalize($posted_slug);
			if ($clean !== '' && $this->slug_lib->is_available($clean, (int) $funnel_id)) {
				$current_slug = $clean;
			}
		}

		// If still no slug (legacy row with the modal not used), generate one
		// now so we never re-emit a base64 URL after a save.
		if (empty($current_slug)) {
			$current_slug = $this->slug_lib->make_funnel_slug(
				$funnel_row ? $funnel_row->campaign_name : 'funnel',
				(int) $funnel_id
			);
		}

		$localurl = base_url('webpages/' . $current_slug);
		$thankyou_page_link = base_url('webpages/' . $current_slug . '/thankyou');

		// Bridge funnels: keep site_uid / thanku_site_uid aligned with the slug so
		// the Node capture lookup (WHERE site_uid = slug) keeps working post-edit.
		if ($ic_is_bridge) {
			$site_uid = $current_slug;
			$thankyou_site_uid = $current_slug;
		}

		$data = array();
		$data['template_id'] = $template_id;
		$data['slug'] = $current_slug;
		$data['publish_link'] = $localurl;
		$data['content'] = $html;
		$data['site_uid'] = $site_uid;
		$data['status'] = 1;
		$data['thank_template_id'] = $thankyou_template_id;
		$data['thankyou_page_link'] = $thankyou_page_link;
		$data['thankyou_content'] = $thankyou_html;
		$data['thanku_site_uid'] = $thankyou_site_uid;
		$data['thankpage_status'] = 1;

		if(!empty($script_code)){
			$data['script_code'] = json_encode($script_code);
		}


		$autoresponder_data = $_POST['autoresponder_data'];
		if(!empty($autoresponder_data) && $autoresponder_data != '""'){

			$data['autoresponder_data'] = $this->encryption->encrypt($autoresponder_data);
		}else{
			$data['autoresponder_data'] = '';
		}

		// Embed widget config is persisted from the Share & Publish page
		// (instantclose/save-widget-config), so submitAction no longer touches
		// widget_enabled / widget_config here.

		// if($template_id >= 111)
		// {
		// 	$site_uid=str_ireplace("=","",base64_encode($camp_id.'|'.$user_id.'|thank'));
		// }else{
		// 	$site_uid=str_ireplace("=","",base64_encode($camp_id.'|'.$user_id.'|landing'));
		// }	
		
		// if($template_id >= 111)
		// {	
		// 	$localurl = base_url("webpages")."/thankyou/".$site_uid ;
		// 	$data = array("thank_template_id"=>$template_id,'thankyou_page_link'=>$localurl,'thankyou_content'=>$html,'thanku_site_uid'=>$site_uid,'thankpage_status'=>1);
		// }else{
		// 	$localurl = base_url("webpages")."/".$site_uid ;
		// 	$data = array("template_id"=>$template_id,'publish_link'=>$localurl,'content'=>$html,'site_uid'=>$site_uid,'status'=>1);
		// }

		// $autoresponder_data = $_POST['autoresponder_data'];
		// if(!empty($autoresponder_data))
		// {
		// 	$data['autoresponder_data'] = $this->encryption->encrypt($autoresponder_data);
		// }
		
		$this->Datamodel->updateTable("funnels",$where,$data);

		$res['status'] = '200';
		$res['message'] = 'Template updated successfully.';
		$res['funnel_id'] = $funnel_id;
		$res['slug'] = $current_slug;
		$res['publish_link'] = $localurl;
		$res['thankyou_site_uid'] = $thankyou_site_uid;
		$res['thankyou_page_link'] = $thankyou_page_link;
		echo json_encode($res);

		// echo $funnel_id;
	}

	/**
	 * Silent DRAFT save fired by the editor once the AI auto-populate finishes.
	 * Persists the filled funnel + thank-you HTML to funnels.content/thankyou_content
	 * and stamps a hidden `ic-autopopulated` marker into the content WITHOUT publishing
	 * (status stays 0), so a refresh no longer re-runs the auto-populate animation.
	 * Never touches a live page (status=1) and only writes the owner's own funnel.
	 */
	public function save_draft()
	{
		header('Content-Type: application/json');

		if (!$this->ion_auth->logged_in()) {
			echo json_encode(array('status' => 'error', 'message' => 'Not logged in'));
			return;
		}
		$u = $this->ion_auth->user()->row();
		$user_id = $u->id;

		$funnel_id = isset($_POST['funnel_id']) ? (int) $_POST['funnel_id'] : 0;
		if (!$funnel_id) {
			echo json_encode(array('status' => 'error', 'message' => 'Missing funnel_id'));
			return;
		}

		$funnel = $this->db->where('id', $funnel_id)->get('funnels')->row();
		if (!$funnel) {
			echo json_encode(array('status' => 'error', 'message' => 'Funnel not found'));
			return;
		}
		// Owner-only (superadmin id 1 may write any draft).
		if ((int) $user_id !== 1 && (int) $funnel->user_id !== (int) $user_id) {
			echo json_encode(array('status' => 'error', 'message' => 'Forbidden'));
			return;
		}
		// Never touch a published page — auto-populate only runs on drafts.
		if ((int) $funnel->status === 1) {
			echo json_encode(array('status' => 'skipped', 'message' => 'Already published'));
			return;
		}

		$header    = isset($_POST['header1']) ? $_POST['header1'] : '';
		$body      = isset($_POST['body1']) ? $_POST['body1'] : '';
		$ty_header = isset($_POST['thankyou_header']) ? $_POST['thankyou_header'] : '';
		$ty_body   = isset($_POST['thankyou_body']) ? $_POST['thankyou_body'] : '';

		// Hidden stamp: its presence in content is what tells the editor NOT to
		// auto-populate again (see editor.php $ic_already_populated). Kept in the same
		// hidden-div style as the analyticsCode/chatbotEmbedCode markers already in <head>.
		$marker = '<div class="ic-autopopulated" style="display:none;"></div>';

		$data = array();
		if ($header !== '' || $body !== '') {
			if (strpos($body, 'ic-autopopulated') === false) {
				$body = $marker . $body;
			}
			$data['content'] = '<funnelrh id="funnelrh">' . $header . '</funnelrh><funnelrb id="funnelrb">' . $body . '</funnelrb>';
		}
		if ($ty_header !== '' || $ty_body !== '') {
			$data['thankyou_content'] = '<funnelrh1 id="funnelrh1">' . $ty_header . '</funnelrh1><funnelrb1 id="funnelrb1">' . $ty_body . '</funnelrb1>';
		}
		if (empty($data)) {
			echo json_encode(array('status' => 'error', 'message' => 'No content'));
			return;
		}
		// status is intentionally NOT set here — the Bridge stays a Draft (status=0).

		$where = ((int) $user_id === 1) ? array('id' => $funnel_id) : array('user_id' => $user_id, 'id' => $funnel_id);
		$this->Datamodel->updateTable('funnels', $where, $data);

		echo json_encode(array('status' => '200', 'message' => 'Draft saved'));
	}




	/**
	 * POST /funnels/{id}/unarchive — restore a funnel + its subscribers.
	 */
	public function unarchiveFunnel($id)
	{
		header('Content-Type: application/json');
		$id = (int) $id;
		$funnel = $this->db->where('id', $id)->get('funnels')->row();
		if (!$funnel || ($funnel->user_id != $this->user_id && (int) $this->user_id !== 1)) {
			echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
			return;
		}
		$this->load->library('lifecycle_lib');
		echo json_encode($this->lifecycle_lib->unarchive_funnel($id));
	}

	/**
	 * POST /funnels/{id}/delete (also entry point for legacy DeleteCampaign)
	 * Permanently delete a funnel + cascade. Call logs preserved.
	 */
	public function deleteFunnel($id)
	{
		header('Content-Type: application/json');
		$id = (int) $id;
		$funnel = $this->db->where('id', $id)->get('funnels')->row();
		if (!$funnel || ($funnel->user_id != $this->user_id && (int) $this->user_id !== 1)) {
			echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
			return;
		}

		// Tidy upload folder first (best-effort; ignore failures)
		$this->load->helper('file');
		$dir = 'storage/uploads/campaigns/' . $funnel->user_id . '/' . $id;
		if (is_dir($dir)) {
			@delete_files($dir, true);
			@rmdir($dir);
		}

		$this->load->library('lifecycle_lib');
		echo json_encode($this->lifecycle_lib->delete_funnel($id, $this->user_id));
	}

	// public function editcampaign()
	// {
	// 	$funnel_id=$_REQUEST['funnel_id'];
	// 	$template_id=$_REQUEST['template_id'];

	// 	$this->data['PageTitle']='Templates';
	// 	$this->session->set_userdata('funnel_id',$funnel_id);
	// 	$this->data['funnel_id']=$funnel_id;

	// 	if($template_id == 'process'){
	// 		$this->load->config('template_config');
	// 		$this->data["templatesdata"]=$this->config->item('template');
	// 		$this->data['PageTitle']='Funnel Types';
	// 		$this->load->view('pages/funnel-types2',$this->data);
	// 	}else{
	// 		$this->edit_template($template_id);
	// 	}
	// }


	/**
	 * Funnel Intelligence — At-a-Glance Analytics Dashboard
	 * Route: /funnelIntelligence/{funnel_id}
	 */
	public function funnelIntelligence($id = null)
	{
		if (!$id) { redirect('dashboard'); return; }

		$this->data['ActiveTab'] = 'Analytics';
		$this->data['PageTitle'] = 'Funnel Intelligence';

		$funnel = $this->Datamodel->getSingleRow('funnels', ['id' => $id]);
		if (!$funnel) { redirect('dashboard'); return; }

		// Verify ownership
		if ($funnel->user_id != $this->user_id && $this->user_id != 1) {
			redirect('dashboard'); return;
		}

		$site_uid = $funnel->site_uid;

		// ── Leads ──
		$this->db->where('site_uid', $site_uid);
		$this->db->where('archived_at IS NULL', null, false);
		$this->db->order_by('subscribe_date', 'DESC');
		$leads = $this->db->get('subscribers')->result();

		// Attach sequence state (pending / sent counts) per lead
		if (!empty($leads)) {
			$lead_ids = array_column($leads, 'id');
			$seq_map = [];
			if (!empty($lead_ids)) {
				$seq_rows = $this->db->select('lead_id, SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as seq_pending, SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as seq_sent', FALSE)
					->from('sequence_queue')
					->where_in('lead_id', $lead_ids)
					->group_by('lead_id')
					->get()->result();
				foreach ($seq_rows as $r) $seq_map[$r->lead_id] = $r;
			}
			foreach ($leads as $l) {
				$l->seq_pending = isset($seq_map[$l->id]) ? (int)$seq_map[$l->id]->seq_pending : 0;
				$l->seq_sent    = isset($seq_map[$l->id]) ? (int)$seq_map[$l->id]->seq_sent    : 0;
			}
		}

		// ── Call logs for this funnel ──
		$this->db->where('funnel_id', $id);
		$this->db->order_by('call_added_time', 'DESC');
		$call_logs = $this->db->get('twilio_call_logs')->result();

		// ── Drip queue ──
		$this->db->where('funnel_id', $id);
		$drip_items = $this->db->get('sequence_queue')->result();

		// ── Page views (analytics event log — migration ic_037). ──
		// The table is a clean event log now: one row per event, metrics derived on
		// read. Uniques come from the anonymous session_id (no IP stored); "social"
		// is unique visitors whose page_view carried a social referrer.
		$page_views = (int) $this->db->where(['site_uid' => $site_uid, 'event' => 'page_view'])
		                             ->count_all_results('analytics');
		$unique_visitors = (int) $this->db->select('COUNT(DISTINCT session_id) AS n', false)
		                                  ->where(['site_uid' => $site_uid, 'event' => 'page_view'])
		                                  ->where('session_id IS NOT NULL', null, false)
		                                  ->get('analytics')->row()->n;
		$social_impressions = (int) $this->db->select('COUNT(DISTINCT session_id) AS n', false)
		                                     ->where(['site_uid' => $site_uid, 'event' => 'page_view'])
		                                     ->group_start()
		                                         ->like('referrer', 'facebook.com')
		                                         ->or_like('referrer', 't.co')
		                                         ->or_like('referrer', 'pinterest.com')
		                                         ->or_like('referrer', 'google.com')
		                                         ->or_like('referrer', 'instagram.com')
		                                         ->or_like('referrer', 'linkedin.com')
		                                     ->group_end()
		                                     ->get('analytics')->row()->n;
		$avg_time = $this->Datamodel->getAverageTimeSpent($site_uid);

		// ── KPI Calculations ──
		$kpi = new stdClass();
		$kpi->total_leads = count($leads);
		$kpi->total_calls = count($call_logs);
		$kpi->page_views = $page_views;
		$kpi->unique_visitors = $unique_visitors;
		$kpi->social_impressions = $social_impressions;
		$kpi->avg_time = $avg_time;

		// Call answer rate
		$answered = 0;
		$total_duration = 0;
		$sentiments = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
		$outcomes = [];
		$all_topics = [];

		foreach ($call_logs as $c) {
			if (in_array($c->twilio_call_status, ['completed', 'answered'])) $answered++;
			$total_duration += intval($c->call_duration);
			if ($c->call_sentiment && isset($sentiments[$c->call_sentiment])) {
				$sentiments[$c->call_sentiment]++;
			}
			if ($c->call_outcome) {
				$outcomes[$c->call_outcome] = ($outcomes[$c->call_outcome] ?? 0) + 1;
			}
			if ($c->call_topics) {
				$topics = json_decode($c->call_topics, true);
				if (is_array($topics)) {
					foreach ($topics as $t) {
						$t = strtolower(trim($t));
						$all_topics[$t] = ($all_topics[$t] ?? 0) + 1;
					}
				}
			}
		}

		$kpi->answer_rate = $kpi->total_calls > 0 ? round(($answered / $kpi->total_calls) * 100) : 0;
		$kpi->avg_duration = $kpi->total_calls > 0 ? round($total_duration / $kpi->total_calls) : 0;
		$kpi->total_duration = $total_duration;
		$kpi->sentiments = $sentiments;
		$kpi->outcomes = $outcomes;
		$kpi->conversion_rate = $kpi->total_calls > 0 ? round((($outcomes['booked'] ?? 0) + ($outcomes['interested'] ?? 0)) / $kpi->total_calls * 100) : 0;

		// Lead score distribution
		$score_dist = ['cold' => 0, 'warm' => 0, 'hot' => 0];
		foreach ($leads as $l) {
			$status = $l->lead_status ?: 'cold';
			if (isset($score_dist[$status])) $score_dist[$status]++;
		}
		$kpi->score_distribution = $score_dist;

		// Top topics (sorted by frequency)
		arsort($all_topics);
		$kpi->top_topics = array_slice($all_topics, 0, 8, true);

		// Drip stats
		$drip_stats = ['total' => 0, 'sent' => 0, 'pending' => 0, 'failed' => 0];
		$drip_by_channel = ['email' => ['sent' => 0, 'total' => 0], 'sms' => ['sent' => 0, 'total' => 0], 'ai_call' => ['sent' => 0, 'total' => 0]];
		foreach ($drip_items as $qi) {
			$drip_stats['total']++;
			$drip_stats[$qi->status] = ($drip_stats[$qi->status] ?? 0) + 1;
			if (isset($drip_by_channel[$qi->channel])) {
				$drip_by_channel[$qi->channel]['total']++;
				if ($qi->status === 'sent') $drip_by_channel[$qi->channel]['sent']++;
			}
		}
		$kpi->drip_stats = $drip_stats;
		$kpi->drip_by_channel = $drip_by_channel;
		$kpi->drip_completion = $drip_stats['total'] > 0 ? round($drip_stats['sent'] / $drip_stats['total'] * 100) : 0;

		// Leads over time (last 14 days)
		$leads_by_day = [];
		for ($i = 13; $i >= 0; $i--) {
			$day = date('Y-m-d', strtotime("-{$i} days"));
			$leads_by_day[$day] = 0;
		}
		foreach ($leads as $l) {
			$day = date('Y-m-d', strtotime($l->subscribe_date));
			if (isset($leads_by_day[$day])) $leads_by_day[$day]++;
		}
		$kpi->leads_by_day = $leads_by_day;

		// Client info
		$client = null;
		if ($funnel->client_id) {
			$client = $this->Datamodel->getSingleRow('clients', ['id' => $funnel->client_id]);
		}

		$this->data['funnel']     = $funnel;
		$this->data['client']     = $client;
		$this->data['leads']      = $leads;
		$this->data['call_logs']  = $call_logs;
		$this->data['kpi']        = $kpi;

		$this->load->view('pages/funnel_intelligence', $this->data);
	}


	public function downloadsleads(){
		
		if(isset($_POST) && count($_POST)>0)
		{
			$u = $this->ion_auth->user()->row();
			$user_id = $u->id;
			if($user_id == 1){
				$where = array('site_uid'=>$_POST['site_uid']);
			}else{
				$where = array('user_id'=>$user_id,'site_uid'=>$_POST['site_uid']);
			}
			$campaign_name=$this->Datamodel->fetchTableData('funnels',$where,'campaign_name');
			$camp_name=$campaign_name[0]['Data']->campaign_name;
			$leads = $this->Datamodel->fetchTableData('subscribers',array('site_uid'=>$_POST['site_uid'],'archived_at'=>null));
			header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename='.$camp_name.'_local_leads.csv');

            // create a file pointer connected to the output stream
            $output = fopen('php://output', 'w');

            fputcsv($output, array('Subscriber Name','Subscriber Email','Subscriber Date'));
			
            for($i=0;$i < count($leads);$i++)
            {
				fputcsv($output, array($leads[$i]['Data']->subscriber_name,$leads[$i]['Data']->email,$leads[$i]['Data']->subscribe_date));
            }

        }
	}

	//upload image 
	public function get_pixabay_images(){
		// Guard + URL-encode the keyword — it is interpolated into the API URL, so a
		// raw value with spaces / & would corrupt the query (and an unset key threw).
		$source  = isset($_REQUEST['keyword']) ? trim((string) $_REQUEST['keyword']) : '';
		$page_no = isset($_REQUEST['page_no']) ? max(1, (int) $_REQUEST['page_no']) : 1;
		$q       = rawurlencode($source);

		$process = $this->processURL("https://pixabay.com/api/?key=6885330-4e43c958367275eb56fff3aae&q=$q&image_type=photo&page=$page_no");
		$process = json_decode($process, true, 512, JSON_BIGINT_AS_STRING);

		// Pixabay returns an error string (not JSON with `hits`) on a bad request, so
		// never count() a non-array — that fatals on PHP 8.
		$checkcount = (is_array($process) && isset($process['hits']) && is_array($process['hits'])) ? $process['hits'] : array();
		$count = count($checkcount);
		if($count!=0)
		{
			// $html='<div class="row">';
			$html = '';
			foreach ($checkcount as $key => $value)
			{
				if($value['webformatURL']!='')
				{
					$image_name=str_ireplace('https://pixabay.com/photos/',' ',$value['pageURL']);
					$image_name=str_ireplace('/',' ',$image_name);
					// Grid thumbnail = previewURL (cdn.pixabay.com), which is hotlinkable
					// and renders directly. The webformatURL (pixabay.com/get/…) is
					// hotlink-protected — so it stays in data-src ONLY for the server-side
					// re-host on selection, never as an <img src> (that's why the grid was
					// showing broken images before).
					$preview = isset($value['previewURL']) && $value['previewURL'] !== '' ? $value['previewURL'] : $value['webformatURL'];
					$html.='<div class="col-sm-4 mb-4"><div class="img_lg_col"><a href="#selectedDiv" class="addselectedimage" data-width="'.$value['webformatWidth'].'" data-height="'.$value['webformatHeight'].'" data-src="'.htmlspecialchars($value['webformatURL'], ENT_QUOTES).'" data-preview="'.htmlspecialchars($preview, ENT_QUOTES).'" data-tag="'.htmlspecialchars($value['tags'], ENT_QUOTES).'" data-image_name="'.htmlspecialchars($image_name, ENT_QUOTES).'"><img src="'.htmlspecialchars($preview, ENT_QUOTES).'" class=" img_full" alt="img" loading="lazy"/></a></div></div>';
				}
			}
			$page_no++;

			$html.="<div class='col-sm-12 text-center loadmoreDiv'><button class='btn btn-success loadmore' data-page = '".$page_no."'>Load More</button></div>";
		}else{
			$html="<div class='text-center'><p>No images found. Please try a different keyword.</p></div>";
		}
		
		$ret['html']=$html;
		echo json_encode($ret);
	}

	function processURL($frameurl){
			
		$ch = curl_init();
		curl_setopt_array($ch, array(
		CURLOPT_URL => $frameurl,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => true,   // verify the cert — the API key travels in this URL
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_TIMEOUT => 15
		));

		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	public function downloadImages()
	{
		$url = isset($_POST['url']) ? trim((string) $_POST['url']) : '';
		$fallback = isset($_POST['fallback']) ? trim((string) $_POST['fallback']) : '';
		$user = $this->ion_auth->user()->row();
        $userID = $user->id;

		// SECURITY: this endpoint re-hosts a Pixabay result image chosen in the editor.
		// The URL is attacker-controllable, so it MUST be validated before any server-
		// side fetch — otherwise it is an SSRF / local-file-read primitive (file://,
		// http://169.254.169.254/…, internal services). Lock it to https on a Pixabay
		// CDN host; anything else is rejected outright.
		$is_pixabay = function ($u) {
			$p = parse_url($u);
			$h = isset($p['host']) ? strtolower($p['host']) : '';
			$s = isset($p['scheme']) ? strtolower($p['scheme']) : '';
			$ok = ($h === 'pixabay.com' || substr($h, -12) === '.pixabay.com'
			     || substr($h, -14) === '.pixabaycdn.com');
			return ($s === 'https' && $ok);
		};
		if (!$is_pixabay($url)) {
			$this->output->set_status_header(400);
			echo 'BAD_URL';
			return;
		}
		// Fallback is optional; only trust it if it's also a Pixabay URL.
		if ($fallback !== '' && !$is_pixabay($fallback)) { $fallback = ''; }

		if( !is_dir( $this->config->item('images_uploadDir').'/'.$userID ) ) {
			mkdir($this->config->item('images_uploadDir').'/'.$userID);
		}

		// Fetch helper: curl (NOT file_get_contents, which honours file://), redirects
		// disabled so an allowed host can't 302 to an internal target.
		$fetch = function ($u) {
			$ch = curl_init($u);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_TIMEOUT        => 15,
				CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
				CURLOPT_MAXFILESIZE    => 15 * 1024 * 1024,
				CURLOPT_USERAGENT      => 'Mozilla/5.0 (Agentic Affiliates image import)',
				CURLOPT_REFERER        => 'https://pixabay.com/',
			));
			$resp  = curl_exec($ch);
			$code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$ctype = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
			curl_close($ch);
			if ($resp !== false && $code === 200 && stripos($ctype, 'image/') === 0) { return $resp; }
			return false;
		};

		// Serve from the thumbnail cache first (pixabay_thumb may already have these
		// bytes on disk), else fetch — full webformat first, then the previewURL
		// fallback if Pixabay throttles the webformat host. Each source is retried
		// twice so a transient 429 doesn't fail the whole "Use This Image" action.
		$body = false;
		$sources = array($url);
		if ($fallback !== '' && $fallback !== $url) { $sources[] = $fallback; }
		foreach ($sources as $src) {
			$cache_file = FCPATH . 'uploads/_pixcache/' . sha1($src) . '.img';
			if (is_file($cache_file) && filesize($cache_file) > 0) {
				$body = file_get_contents($cache_file);
				if ($body !== false && $body !== '') { break; }
			}
			for ($i = 0; $i < 2; $i++) {
				$body = $fetch($src);
				if ($body !== false && $body !== '') { break; }
				usleep(500000); // 0.5s backoff
			}
			if ($body !== false && $body !== '') { break; }
		}

		// Must be actual image bytes.
		if ($body === false || $body === '' || @getimagesizefromstring($body) === false) {
			$this->output->set_status_header(422);
			echo 'IMAGE_FETCH_FAILED';
			return;
		}

      	$image_name=rand(1,78468768745473);
        $file_name = $this->config->item('images_uploadDir').'/'.$userID.'/'.$image_name.".jpg";
        file_put_contents($file_name, $body);

		$img = $this->s3_lib->imageUploadByURL($userID,$file_name,'localleadfunnel'.rand(12321,6287634).'.png');

		if(!empty($img) && array_key_exists('URL', $img))
		{
			echo $img['URL'];
		}
		else{
			$this->output->set_status_header(422);
			echo 'UPLOAD_FAILED';
		}
	}

	/**
	 * Same-origin thumbnail proxy for the Pixabay search grid. Pixabay's
	 * webformatURL is hotlink-protected and won't load in an <img> on our domain,
	 * so the grid points here and we stream the bytes server-side. Locked to
	 * Pixabay hosts (same SSRF guard as downloadImages) — it is NOT a general proxy.
	 */
	public function pixabay_thumb()
	{
		$url = isset($_GET['u']) ? trim((string) $_GET['u']) : '';
		$parts  = parse_url($url);
		$host   = isset($parts['host']) ? strtolower($parts['host']) : '';
		$scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
		$host_ok = ($host === 'pixabay.com' || substr($host, -12) === '.pixabay.com'
		         || substr($host, -14) === '.pixabaycdn.com');
		if ($scheme !== 'https' || !$host_ok) {
			$this->output->set_status_header(400);
			return;
		}

		// Disk cache — Pixabay rate-limits the image host aggressively, so re-fetching
		// the same thumbnail on every render trips 429s and breaks the grid. Cache each
		// fetched thumb by URL hash and serve from disk on subsequent hits.
		$cache_dir = FCPATH . 'uploads/_pixcache/';
		if (!is_dir($cache_dir)) { @mkdir($cache_dir, 0755, true); }
		$cache_file = $cache_dir . sha1($url) . '.img';
		if (is_file($cache_file) && filesize($cache_file) > 0) {
			$this->output
				->set_content_type('image/jpeg')
				->set_header('Cache-Control: private, max-age=86400')
				->set_output(file_get_contents($cache_file));
			return;
		}

		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_TIMEOUT        => 15,
			CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
			CURLOPT_MAXFILESIZE    => 15 * 1024 * 1024,
			CURLOPT_USERAGENT      => 'Mozilla/5.0 (Agentic Affiliates thumbnail proxy)',
			CURLOPT_REFERER        => 'https://pixabay.com/',
		));
		$body  = curl_exec($ch);
		$code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$ctype = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		curl_close($ch);

		if ($body === false || $code !== 200 || stripos($ctype, 'image/') !== 0) {
			// Fail soft: 1x1 transparent GIF so the grid shows a blank tile, not a
			// broken-image icon, when Pixabay throttles or the URL has expired.
			$this->output
				->set_content_type('image/gif')
				->set_output(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
			return;
		}
		// Strip any "; charset=…" — a charset param on an image MIME makes some
		// browsers refuse to render it in an <img> (fetch is fine, <img> is not).
		$mime = trim(explode(';', $ctype)[0]);
		@file_put_contents($cache_file, $body);
		$this->output
			->set_content_type($mime)
			->set_header('Cache-Control: private, max-age=86400')
			->set_output($body);
	}

	public function antispam(){
		if(isset($_POST["status"])){
			$status = $_POST["status"];
			$user = $this->ion_auth->user()->row();
			$userID = $user->id;
			$id=$this->Datamodel->insertTable("antispam",['user_id'=>$userID,'status'=>$status]);
			echo $id;
		}
	}
	
	public function verify_antispam(){
		$user = $this->ion_auth->user()->row();
		$userID = $user->id;
		$res = $this->Datamodel->checkExit("antispam",['user_id'=>$userID]);
		if($res > 0){
			$result = $this->Datamodel->fetchTableData("antispam",['user_id'=>$userID],'status');
			$status = $result[0]['Data']->status;
		}else{
			$status = 0;
		}
		echo $status;
	}

	//export Html 
	public function exportHTML() {
		//start
		if(isset($_POST["funnel_id"]) && !empty($_POST["funnel_id"]))
		{

			$user = $this->ion_auth->user()->row();
			$userID = $user->id;

			// SECURITY: funnel_id reaches a shell command (exec wget below), so
			// force it to an integer. Also scope the funnel lookup to the owner
			// (user 1 exempt) so a user can't export another tenant's page.
			$funnel_id = (int) $_POST["funnel_id"];
			$lookup = array('id' => $funnel_id);
			if ((int) $userID !== 1) { $lookup['user_id'] = $userID; }

			if(!is_dir('assets/funnels/'.$userID ) ) {
				mkdir('assets/funnels/'.$userID);
			}

			$camp_dir='assets/funnels/'.$userID.'/'.$funnel_id;

			if(!is_dir($camp_dir) ) {
				mkdir($camp_dir);
			}

			$logfile = $camp_dir.'/localeads.txt';
			$export_dir= $camp_dir.'/localeads';

			$get_publish_link=$this->Datamodel->fetchTableData("funnels",$lookup,'publish_link,campaign_name');

			if (empty($get_publish_link) || empty($get_publish_link[0]['Data'])) {
				die(json_encode(array('responseCode' => 0, 'responseHTML' => 'Not found')));
			}

			$publish_url= $get_publish_link[0]['Data']->publish_link;
			$campaign_name = $get_publish_link[0]['Data']->campaign_name;
			$localeadszip = $campaign_name.'.zip';

			// SECURITY: escapeshellarg every interpolated value ($publish_url comes
			// from the DB; $logfile/$export_dir are int-derived but escaped too).
			exec("wget -m -k -E -p -np -nH --cut-dirs=2 -o ".escapeshellarg($logfile)." --directory-prefix=".escapeshellarg($export_dir)." ".escapeshellarg($publish_url));

			if( strpos(file_get_contents($logfile),'FINISHED') !== false) { 
				// do stuff 
				// $this->load->library('zip');
				// Get real path for our folder
				$rootPath = realpath($export_dir);

				// Initialize archive object
				$zip = new ZipArchive();
				$zip->open($localeadszip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

				// Create recursive directory iterator
				/** @var SplFileInfo[] $files */
				$files = new RecursiveIteratorIterator(
				    new RecursiveDirectoryIterator($rootPath),
				    RecursiveIteratorIterator::LEAVES_ONLY
				);

				foreach ($files as $name => $file)
				{
				    // Skip directories (they would be added automatically)
				    if (!$file->isDir())
				    {
				        // Get real and relative path for current file
				        $filePath = $file->getRealPath();
				        $relativePath = substr($filePath, strlen($rootPath) + 1);

				        // Add current file to archive
				        $zip->addFile($filePath, $relativePath);

				        $file->getFilename();

				        if ($file->getFilename() != 'localeads.txt')
				        {
				            $filesToDelete[] = $filePath;
				        }
				    }
				}

				// Zip archive will be created only after closing object
				$zip->close();

				// print_r($filesToDelete);exit;
				// Delete all files from "delete list"
				foreach ($filesToDelete as $file)
				{
					unlink($file);
				}

				// exit;
				$file_path = $localeadszip;
				header('Content-Type: application/zip');
				header('Content-disposition: attachment; filename='.$campaign_name.'.zip');
				header('Content-Length: ' . filesize($file_path));

				ob_clean();
				flush();

				readfile($file_path);

				ignore_user_abort(true);

				unlink($file_path);

				exit;

				// $this->zip->download('omni.zip');
			} 
				
		}

	}

	public function myaccess(){
		$this->data['ActiveTab']='myaccess';
		$this->data['PageTitle']='My Access';
		$this->load->view("pages/access",$this->data);
	}

	
	//to clone campaign
	public function clonefunnel(){
		$parent_camp_id=$_REQUEST['funnel_id'];
		$campaign_name = isset($_REQUEST['funnel_name']) ? trim($_REQUEST['funnel_name']) : '';

		$u = $this->ion_auth->user()->row();
		$userID = $u->id;

		$data=$this->Datamodel->fetchTableDataArray( "funnels", array("id"=>$parent_camp_id, 'user_id'=>$userID) );
		// echo "<pre>";print_r($data);exit;

		if(empty($data)){
			echo "0"; exit;
		}

		// Auto-suffix the clone name if it collides with an existing funnel
		// for this user. Cloning is "make me a copy" — instead of rejecting
		// a duplicate, find the next free " 2", " 3", … suffix so the user
		// always gets a successful clone (matching the JS "(copy)" UX).
		if ($campaign_name !== '') {
			$base = $campaign_name;
			$try  = $campaign_name;
			$n    = 2;
			while ($this->Datamodel->checkExit('funnels', array(
				'campaign_name' => $try,
				'user_id'       => $userID,
			)) > 0) {
				$try = $base . ' ' . $n;
				$n++;
				if ($n > 50) break; // safety
			}
			$campaign_name = $try;
		}

		$this->load->library('slug_lib');

		$new_data = $data[0];
		$new_data['campaign_name'] = $campaign_name;
		$new_data['slug'] = $this->slug_lib->make_funnel_slug($campaign_name);
		$new_data['user_id'] = $userID;


		unset($new_data['id']);
		unset($new_data['visits']);
		unset($new_data['created_date']);
		unset($new_data['updated_date']);


		$current_camp_id = $this->Datamodel->insertTable('funnels', $new_data);

		$site_uid=str_ireplace("=","",base64_encode($current_camp_id.'|'.$userID.'|landing'));
		$thanku_site_uid = str_ireplace("=","",base64_encode($current_camp_id.'|'.$userID.'|thank'));
		$slug = $new_data['slug'];


		$dataarray = array();
		if( $new_data['status'] == 1 ){
			$dataarray['publish_link'] = base_url('webpages/' . $slug);
			$dataarray['site_uid'] = $site_uid;
		}

		if( $new_data['thankpage_status'] == 1 ){
			$dataarray['thankyou_page_link'] = base_url('webpages/' . $slug . '/thankyou');
			$dataarray['thanku_site_uid'] = $thanku_site_uid;
		}

		if(!empty($dataarray)){

			$this->Datamodel->updateTable("funnels", array("id"=>$current_camp_id), $dataarray);
		}

		echo $current_camp_id;
	}


	public function imageUpload(){
		$u = $this->ion_auth->user()->row();
		$user_id = $u->id;
		echo json_encode($this->s3_lib->imageUpload($user_id));
		// print_r($_FILES);
		// print_r($_POST);
	}

	/**
	 * AJAX: the "My Images" gallery for the editor's image-picker modal.
	 * Returns the current user's previously-uploaded images (from S3
	 * InstantcloseAI/{userID}/images/) as ready-to-render <ul><li><img> HTML.
	 * The modal calls this when it opens / when the "My Images" tab is shown;
	 * without it that tab was always empty even though uploads were saved.
	 * s3_lib->fetchimages() already json-encodes + exits.
	 */
	public function fetchMyImages(){
		$u = $this->ion_auth->user()->row();
		$user_id = $u ? $u->id : 0;
		$this->s3_lib->fetchimages($user_id);
	}

	public function funnelModes()
    {
		// Legacy AgenticAgency "new dossier" screen (rendered the retired
		// pages/mode_funnel view). Retired with the AA dashboard — bounce to
		// the Agentic Affiliates home.
		redirect('app');
		return;
    }


	public function createFunnelWizard()
    {
        $where = array('user_id' => $this->user_id);
        $this->data['funnelCount'] = $this->Datamodel->getallCount('funnels', $where, '', '30daycount');
        $this->data['ActiveTab'] = 'dashboard';
        $this->data['PageTitle'] = 'Create New Funnel';

        // GHL accounts list — drives the per-funnel account+calendar picker on Step 2.
        // Mirrors the shape used for Calendly/Google Calendar accounts.
        $this->data['ghl_accounts'] = [];
        if ($this->db->table_exists('ghl_integrations')) {
            $g_rows = $this->db
                ->select('id, location_id, location_name, token_label')
                ->where('user_id', $this->user_id)
                ->where('is_active', 1)
                ->order_by('created_at', 'ASC')
                ->get('ghl_integrations')->result_array();
            $this->data['ghl_accounts'] = $g_rows ?: [];
        }
        // Backward-compat: legacy view branches still reference $ghl_connected.
        $this->data['ghl_connected'] = !empty($this->data['ghl_accounts']);

        $this->data['calendly_enabled'] = true;
        $this->data['gcal_enabled']     = true;

        // Google Calendar accounts list — populates the picker on Step 2 when
        // provider=google_calendar. Matches the shape Website_analysis sends.
        $this->data['gcal_accounts'] = [];
        if ($this->db->table_exists('gcal_accounts')) {
            $g_rows = $this->db
                ->select('id, account_email, token_label')
                ->where('user_id', $this->user_id)
                ->where('is_active', 1)
                ->order_by('created_at', 'ASC')
                ->get('gcal_accounts')->result_array();
            $this->data['gcal_accounts'] = $g_rows ?: [];
        }

        // Supported languages — drives the language selector on Step 1, mirrors
        // the website wizard's setup so a template-flow funnel can also be
        // created in non-English.
        $this->load->library('lang_lib');
        $supported_langs = $this->lang_lib->supported();
        // UK English variants are gated to specific users (same allowlist as the
        // website wizard at Website_analysis::index).
        $uk_variant_users = array(1, 177, 454);
        if (!in_array((int) $this->user_id, $uk_variant_users, true)) {
            unset($supported_langs['en-gb'], $supported_langs['en-sc'], $supported_langs['en-ie'], $supported_langs['en-wl']);
        }
        // Norwegian (Bokmål) is gated to a specific allowlist for now.
        $nb_users = array(1, 487);
        if (!in_array((int) $this->user_id, $nb_users, true)) {
            unset($supported_langs['nb']);
        }
        $this->data['supported_languages']   = $supported_langs;
        $this->data['default_language_code'] = $this->lang_lib->user_default($this->user_id);

        $this->app_lib->_render_page('pages/create_funnel_wizard', $this->data);
    }

	/**
	 * AJAX: Extract text from uploaded KB files and generate business context via Gemini
	 */
	public function generateContextFromKB()
	{
		header('Content-Type: application/json');

		// Text extraction is shared with the Agentic Affiliates Bridge flow via
		// Kb_extract_lib (one source of truth for PDF/DOCX/TXT handling).
		$this->load->library('kb_extract_lib');
		$res = $this->kb_extract_lib->process_upload(
			isset($_FILES['kb_files']) ? $_FILES['kb_files'] : array('name' => array()),
			FCPATH . 'uploads/kb_files/'
		);
		$all_text = $res['text'];

		// Also accept existing context to enrich
		$existing_context = $this->input->post('existing_context') ?: '';
		$funnel_name = $this->input->post('funnel_name') ?: '';

		if (empty(trim($all_text))) {
			echo json_encode(['status' => false, 'message' => 'No text extracted from uploaded files']);
			return;
		}

		// Truncate total to 60K chars for Gemini
		$all_text = mb_substr($all_text, 0, 60000);

		// Snapshot a smaller raw block to ship back to the client. Used by the
		// wizard to forward into generateVoiceConfig so the persona prompt
		// sees the actual document content, not just the AI summary.
		$kb_raw_for_client = mb_substr($all_text, 0, 8000);

		$prompt = "You are a marketing automation expert. Analyze the following uploaded knowledge base documents and generate a concise business context summary.\n\n";
		if ($funnel_name) {
			$prompt .= "Funnel Name: {$funnel_name}\n";
		}
		if ($existing_context) {
			$prompt .= "Existing Context (user-provided): {$existing_context}\n\n";
		}
		$prompt .= "=== UPLOADED DOCUMENTS ===\n{$all_text}\n=== END DOCUMENTS ===\n\n";
		$prompt .= "Return a JSON object with exactly this structure:\n";
		$prompt .= '{"context": "A 2-4 paragraph summary covering: what the business does, key services/products, target audience, competitive advantages, tone/voice preferences, and any special offers or unique selling points. Write in second person (You/Your) as instructions for an AI agent that will represent this business."}';
		$prompt .= "\n\nIMPORTANT: Return ONLY the JSON object, no markdown formatting.";

		$this->load->library('gemini_lib');
		$result = $this->gemini_lib->gemini_api($prompt);

		if (is_array($result) && !isset($result['error'])) {
			// Gemini parsed as array — extract 'context' key or take first string value
			$context_text = '';
			if (isset($result['context'])) {
				$context_text = $result['context'];
			} else {
				// Try to find any string value in the response
				foreach ($result as $val) {
					if (is_string($val) && strlen($val) > 50) {
						$context_text = $val;
						break;
					}
				}
			}
			if (!empty($context_text)) {
				echo json_encode(['status' => true, 'context' => $context_text, 'kb_raw' => $kb_raw_for_client]);
			} else {
				echo json_encode(['status' => false, 'message' => 'Unexpected response format from AI']);
			}
		} elseif (is_array($result) && isset($result['error'])) {
			echo json_encode(['status' => false, 'message' => $result['error']['message'] ?? 'Gemini API error']);
		} elseif (is_string($result) && strlen($result) > 50) {
			// Gemini returned raw text — try to parse JSON from it
			$decoded = json_decode($result, true);
			if (is_array($decoded) && isset($decoded['context'])) {
				echo json_encode(['status' => true, 'context' => $decoded['context'], 'kb_raw' => $kb_raw_for_client]);
			} else {
				echo json_encode(['status' => true, 'context' => $result, 'kb_raw' => $kb_raw_for_client]);
			}
		} else {
			echo json_encode(['status' => false, 'message' => 'Could not generate context from uploaded files.']);
		}
	}


	public function leads($id, $page = 1){

		$where = array('id' => $id, 'user_id' => $this->user_id);
		$funnel = $this->Datamodel->fetchTableData('funnels', $where);
		$site_uid = $funnel[0]['Data']->site_uid;
	
		if ($page > 0){
			
			$page = $page - 1;
		}
	
		$itemsPerPage = 10;

		$leads = $this->Datamodel->fetchTableDataInLimit($itemsPerPage, $page, 'subscribers', array('site_uid' => $site_uid, 'archived_at' => null), array(), array(), '*', 'id');
		$total_row = $this->Datamodel->checkExit('subscribers', array('site_uid' => $site_uid, 'archived_at' => null));

		// Counts for the Active / Archived tab strip — both views show both
		$archived_count = (int) $this->db->where('site_uid', $site_uid)
		                                 ->where('archived_at IS NOT NULL', null, false)
		                                 ->count_all_results('subscribers');
		$this->data['archived_count'] = $archived_count;
		$this->data['active_count']   = (int) $total_row;

		// Attach sequence state (pending / sent counts) to each lead
		if (!empty($leads)) {
			$lead_ids = array_map(function($l){ return $l['Data']->id; }, $leads);
			$seq_map = [];
			if (!empty($lead_ids)) {
				$seq_rows = $this->db->select('lead_id, SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as seq_pending, SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as seq_sent', FALSE)
					->from('sequence_queue')
					->where_in('lead_id', $lead_ids)
					->group_by('lead_id')
					->get()->result();
				foreach ($seq_rows as $r) $seq_map[$r->lead_id] = $r;
			}
			foreach ($leads as &$l) {
				$lid = $l['Data']->id;
				$l['Data']->seq_pending = isset($seq_map[$lid]) ? (int)$seq_map[$lid]->seq_pending : 0;
				$l['Data']->seq_sent    = isset($seq_map[$lid]) ? (int)$seq_map[$lid]->seq_sent    : 0;
			}
			unset($l);
		}
	
		
		// Pagination
		$config['base_url'] = base_url('leads/'.$id.'/');
		$config['total_rows'] = $total_row;
		$config['per_page'] = $itemsPerPage;
		$config['use_page_numbers'] = TRUE;
		$config['cur_page'] = $page + 1;
		$config['cur_tag_open'] = '<li class="page-item active"><a class="page-link">';
		$config['cur_tag_close'] = '</a></li>';
		$config['first_link'] = 'First';
		$config['last_link'] = 'Last';
		$config['next_link'] = '>>';
		$config['prev_link'] = '<<';
		$config['attributes'] = array('class' => 'page-numbers');

		$this->pagination->initialize($config);
		$str_links = $this->pagination->create_links();
		$this->data["links"] = explode('&nbsp;', $str_links);

		// print_r($str_links); exit;

		$this->data['leads'] = $leads;
		$this->data['funnel_name'] = isset($funnel[0]['Data']->campaign_name) ? $funnel[0]['Data']->campaign_name : 'Unnamed Funnel';
		$this->data['funnel_id'] = $id;
		$this->data['total_leads'] = (int) $total_row;
		$this->data['current_page'] = $page + 1;
		$this->data['per_page'] = $itemsPerPage;
		$this->data['ActiveTab'] = 'leads';
		$this->data['PageTitle'] = 'Leads';
		$this->app_lib->_render_page('shared/leads', $this->data);

	}

	public function archived_leads($id, $page = 1)
	{
		$where = array('id' => $id, 'user_id' => $this->user_id);
		$funnel = $this->Datamodel->fetchTableData('funnels', $where);

		if (empty($funnel)) {
			show_404();
			return;
		}

		$site_uid = $funnel[0]['Data']->site_uid;

		if ($page > 0) {
			$page = $page - 1;
		}

		$itemsPerPage = 10;
		$offset = $page * $itemsPerPage;

		// Archived rows only — newest archive first
		$leads_rows = $this->db->where('site_uid', $site_uid)
		                       ->where('archived_at IS NOT NULL', null, false)
		                       ->order_by('archived_at', 'DESC')
		                       ->limit($itemsPerPage, $offset)
		                       ->get('subscribers')
		                       ->result();

		// Wrap in the same ['Data' => row] envelope the active list expects
		$leads = [];
		foreach ($leads_rows as $r) { $leads[] = ['Data' => $r]; }

		$total_row = (int) $this->db->where('site_uid', $site_uid)
		                            ->where('archived_at IS NOT NULL', null, false)
		                            ->count_all_results('subscribers');

		// Counts for the Active / Archived tab strip — both views need both
		$this->data['archived_count'] = $total_row;
		$this->data['active_count']   = (int) $this->db->where('site_uid', $site_uid)
		                                               ->where('archived_at IS NULL', null, false)
		                                               ->count_all_results('subscribers');

		// Pagination — points back to the archived route
		$config['base_url'] = base_url('leads/' . $id . '/archived/');
		$config['total_rows'] = $total_row;
		$config['per_page'] = $itemsPerPage;
		$config['use_page_numbers'] = TRUE;
		$config['cur_page'] = $page + 1;
		$config['cur_tag_open'] = '<li class="page-item active"><a class="page-link">';
		$config['cur_tag_close'] = '</a></li>';
		$config['first_link'] = 'First';
		$config['last_link'] = 'Last';
		$config['next_link'] = '>>';
		$config['prev_link'] = '<<';
		$config['attributes'] = array('class' => 'page-numbers');

		$this->pagination->initialize($config);
		$str_links = $this->pagination->create_links();
		$this->data['links'] = explode('&nbsp;', $str_links);

		$this->data['leads'] = $leads;
		$this->data['funnel_name'] = isset($funnel[0]['Data']->campaign_name) ? $funnel[0]['Data']->campaign_name : 'Unnamed Funnel';
		$this->data['funnel_id'] = $id;
		$this->data['total_leads'] = $total_row;
		$this->data['current_page'] = $page + 1;
		$this->data['per_page'] = $itemsPerPage;
		$this->data['ActiveTab'] = 'leads';
		$this->data['PageTitle'] = 'Archived Leads';
		$this->data['is_archived_view'] = true;
		$this->app_lib->_render_page('shared/leads', $this->data);
	}

	/**
	 * GET /funnels/archived — list of archived funnels for the current user
	 * Renders the same dashboard view as the main funnel list but with
	 * is_archived_view=true so the cards show a Restore action instead
	 * of the normal launch buttons.
	 */
	public function archived_funnels($page = 1)
	{
		$where_user = ((int) $this->user_id === 1) ? [] : ['user_id' => $this->user_id];
		$where_user['archived_at IS NOT NULL'] = null;
		$where_user['deleted_at IS NULL']      = null;

		$this->db->from('funnels');
		foreach ($where_user as $k => $v) {
			if ($v === null) $this->db->where($k, null, false);
			else $this->db->where($k, $v);
		}
		$this->db->order_by('archived_at', 'DESC');
		$funnels = $this->db->get()->result();

		// Tab counts also exclude tombstoned rows so neither tab surfaces
		// a deleted funnel.
		$active_count   = (int) (((int) $this->user_id === 1)
			? $this->db->where('archived_at IS NULL', null, false)
			           ->where('deleted_at IS NULL', null, false)
			           ->count_all_results('funnels')
			: $this->db->where('user_id', $this->user_id)
			           ->where('archived_at IS NULL', null, false)
			           ->where('deleted_at IS NULL', null, false)
			           ->count_all_results('funnels'));
		$archived_count = count($funnels);

		$this->data['funnels']          = $funnels;
		$this->data['active_count']     = $active_count;
		$this->data['archived_count']   = $archived_count;
		$this->data['is_archived_view'] = true;
		$this->data['ActiveTab']        = 'dashboard';
		$this->data['PageTitle']        = 'Archived Funnels';
		$this->app_lib->_render_page('pages/archived-funnels', $this->data);
	}


	/**
	 * Compliance checklist for funnel owners
	 * GET /compliance-checklist
	 */
	public function compliance_checklist()
	{
		$this->data['PageTitle'] = 'Compliance Checklist';
		$this->data['ActiveTab'] = 'compliance';
		$this->app_lib->_render_page('compliance_checklist', $this->data);
	}

	public function sequences()
	{
		$this->data['PageTitle'] = 'Sequences';
		$this->data['ActiveTab'] = 'sequences';

		// All funnels for this user, newest first
		$funnels = $this->db->where('user_id', $this->user_id)->order_by('id', 'DESC')->get('funnels')->result();
		$funnel_map = [];
		foreach ($funnels as $f) $funnel_map[$f->id] = $f->campaign_name;

		// Active funnel filter — default to the latest funnel when none is specified.
		$funnel_filter = (int) $this->input->get('funnel');
		$funnel_ids = array_column($funnels, 'id');

		if (!empty($funnel_filter) && in_array($funnel_filter, $funnel_ids)) {
			$active_funnel_ids = [$funnel_filter];
		} elseif (!empty($funnel_ids)) {
			// Funnels are ordered id DESC, so the first row is the newest.
			$funnel_filter = (int) $funnel_ids[0];
			$active_funnel_ids = [$funnel_filter];
		} else {
			$active_funnel_ids = [];
			$funnel_filter = 0;
		}

		$upcoming_days  = [];
		$upcoming_total = 0;

		// Admin's timezone — fallback when a lead has no timezone on file
		$admin_tz = $this->session->userdata('user_timezone')
			?: $this->session->userdata('timezone')
			?: 'UTC';

		if (!empty($active_funnel_ids)) {
			$this->db->select('sq.*, s.subscriber_name, s.email AS lead_email, s.phone AS lead_phone, s.timezone AS lead_timezone, f.campaign_name AS funnel_name');
			$this->db->from('sequence_queue sq');
			$this->db->join('subscribers s', 's.id = sq.lead_id', 'left');
			$this->db->join('funnels f', 'f.id = sq.funnel_id', 'left');
			$this->db->where_in('sq.funnel_id', $active_funnel_ids);
			$this->db->where('sq.status', 'pending');
			$this->db->order_by('sq.scheduled_at', 'ASC');
			$rows = $this->db->get()->result();

			// Today/Tomorrow keys computed in admin's timezone (day headers are per-admin, not per-lead)
			try {
				$now_admin      = new DateTime('now', new DateTimeZone($admin_tz));
				$today          = $now_admin->format('Y-m-d');
				$tomorrow_dt    = clone $now_admin; $tomorrow_dt->modify('+1 day');
				$tomorrow       = $tomorrow_dt->format('Y-m-d');
			} catch (\Throwable $e) {
				$today    = gmdate('Y-m-d');
				$tomorrow = gmdate('Y-m-d', time() + 86400);
			}
			$days = [];

			foreach ($rows as $r) {
				if (empty($r->scheduled_at)) continue;

				$row_tz = !empty($r->lead_timezone) ? $r->lead_timezone : $admin_tz;
				try {
					$row_dt = new DateTime($r->scheduled_at, new DateTimeZone('UTC'));
					$row_dt->setTimezone(new DateTimeZone($row_tz));
					$day_key = $row_dt->format('Y-m-d');
				} catch (\Throwable $e) {
					$day_key = date('Y-m-d', strtotime($r->scheduled_at));
				}

				if (!isset($days[$day_key])) {
					if ($day_key === $today)         $label = 'Today';
					elseif ($day_key === $tomorrow)  $label = 'Tomorrow';
					elseif ($day_key < $today)       $label = 'Overdue — ' . date('D, M j', strtotime($day_key));
					else                             $label = date('D, M j', strtotime($day_key));
					$days[$day_key] = ['key' => $day_key, 'label' => $label, 'leads' => []];
				}
				$lid = (int) $r->lead_id;
				if (!isset($days[$day_key]['leads'][$lid])) {
					$days[$day_key]['leads'][$lid] = (object) [
						'lead_id'         => $lid,
						'subscriber_name' => $r->subscriber_name,
						'lead_email'      => $r->lead_email,
						'lead_phone'      => $r->lead_phone,
						'lead_timezone'   => $r->lead_timezone,
						'funnel_name'     => $r->funnel_name,
						'items'           => [],
					];
				}
				$days[$day_key]['leads'][$lid]->items[] = (object) [
					'channel'      => $r->channel,
					'step_number'  => $r->step_number,
					'scheduled_at' => $r->scheduled_at,
					'status'       => $r->status,
					'track'        => $r->track,
					'queue_id'     => $r->id,
				];
			}

			ksort($days);
			$unique_leads = [];
			foreach ($days as $k => $d) {
				$days[$k]['leads'] = array_values($d['leads']);
				foreach ($days[$k]['leads'] as $ld) $unique_leads[$ld->lead_id] = true;
			}
			$upcoming_days  = array_values($days);
			$upcoming_total = count($rows);
			$upcoming_leads = count($unique_leads);
		} else {
			$upcoming_leads = 0;
		}

		$this->data['funnels']        = $funnels;
		$this->data['funnel_map']     = $funnel_map;
		$this->data['funnel_filter']  = $funnel_filter;
		$this->data['upcoming_days']  = $upcoming_days;
		$this->data['upcoming_total'] = $upcoming_total;
		$this->data['upcoming_leads'] = $upcoming_leads;
		$this->data['admin_timezone'] = $admin_tz;

		$this->app_lib->_render_page('pages/sequences', $this->data);
	}

}
