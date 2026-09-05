<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// OpenChair port: the host engine's Auth required the Twilio SDK here and read
// Twilio + OpenAI credentials in the constructor. Neither has anything to do with
// logging in — it was leftover coupling from the voice product. OpenChair ships
// no telephony at all (05-prd.md §8 rule 7), so the require and the key-loading
// are gone rather than guarded. Nothing in this controller used them.

class Auth extends MY_Controller {

	
	public $data;
	public $portal_host_client_id = 0;

	public function __construct()
	{
		parent::__construct();
		$globaldata = $this->config->item('global_data');

		// Load config library to fetch API keys from config table
		$this->load->library('config_lib');
		$this->load->model('sites/Usermodel');
        	
    	$this->data['whiteLabelData'] =  [	
			'site_title' => $this->config->item('site_title', 'ion_auth'),
			'logo' => base_url($globaldata['logo_url']),
			'fav_url' => $globaldata['fav_url'],
			'footer_text' => $globaldata['footer_text']
		];

		if(isset($_GET['wl']))
		{
        	$whiteLabelData = $this->app_lib->fetchTableData('settings',array("user_id"=>$_GET['wl'],'meta_type'=>'whitelabel'));



			if(!empty($whiteLabelData))
			{
				$whiteLabelData = unserialize($whiteLabelData[0]['Data']->meta_data);


				if(!empty($whiteLabelData['logo']))
					$this->data['whiteLabelData']['logo'] = $whiteLabelData['logo'];

				if(!empty($whiteLabelData['site_title']))
					$this->data['whiteLabelData']['site_title'] = $whiteLabelData['site_title'];

				if(!empty($whiteLabelData['footer_text']))
					$this->data['whiteLabelData']['footer_text'] = $whiteLabelData['footer_text'];
			}
		}

		// (The client-portal feature has been removed. Portal custom-domain
		// detection / branded-host login was decoupled with that removal.)
		$this->portal_host_client_id = 0;

		// (Removed with the Twilio/OpenAI coupling above: the host constructor
		// loaded and decrypted per-user telephony and LLM keys on EVERY request to
		// the login page, and dereferenced $row[0]['Data'] without checking the
		// row existed — so a fresh install with an empty settings table fataled
		// before it could render a login form.)
	}


	public function index()
	{

	}


	/**
	 * Log the user in
	 */
	public function login()
	{
		// echo 'Hello';exit;
		$this->data['title'] = $this->lang->line('login_heading');



		// validate form input
		$this->form_validation->set_rules('identity', str_replace(':', '', $this->lang->line('login_identity_label')), 'required|trim');
		$this->form_validation->set_rules('password', str_replace(':', '', $this->lang->line('login_password_label')), 'required|trim');

		if ($this->form_validation->run() === TRUE)
		{
			// check to see if the user is logging in
			// check for "remember me"
			$remember = (bool)$this->input->post('remember');

			if ($this->ion_auth->login($this->input->post('identity'), $this->input->post('password'), $remember))
			{
				//if the login is successful
				//redirect them back to the home page
				$this->session->set_flashdata('message', $this->ion_auth->messages());
				$this->session->set_flashdata('show_popup_notice', 1);

				// Store session ID for parallel processing s_value bridge
				$user_id = $this->session->userdata('user_id');
				$this->app_lib->updateTable('users', ['id' => $user_id], ['ses_id' => session_id()]);

				// Store user timezone in session (detected by JS in footer, sent as cookie)
				$user_tz = $this->input->cookie('user_timezone');
				if (!empty($user_tz)) {
					$this->session->set_userdata('user_timezone', $user_tz);
				}

				// (The client-portal feature has been removed; former client_portal
				// users now fall through to the standard post-login redirect.)

				// Agentic Affiliates is the primary product on this instance: land users
				// straight in the Agentic Affiliates mission-control after login (keys live
				// in the Node bridge .env, not the engine settings table, so the
				// onboarding gate below would otherwise always bounce to /setting).
				redirect('app');
				return;

				// Onboarding gate — require Gemini, OpenAI, and Twilio keys before
				// the dashboard. If any one is missing, send to settings.
				$required_keys = ['gemini', 'openai', 'twilio'];
				foreach ($required_keys as $meta_type) {
					$row = $this->Datamodel->fetchTableData('settings', ['user_id' => $user_id, 'meta_type' => $meta_type]);
					if (empty($row)) {
						redirect('setting');
						return;
					}
				}

				redirect('/', 'refresh');
				return;


			}
			else if($this->ion_auth->loginAdmin($this->input->post('identity'), $this->input->post('password'), $remember))
			{
				$this->session->set_flashdata('message', $this->ion_auth->messages());
				$admin_uid = $this->session->userdata('user_id');
				$this->app_lib->updateTable('users', ['id' => $admin_uid], ['ses_id' => session_id()]);

				$user_tz = $this->input->cookie('user_timezone');
				if (!empty($user_tz)) {
					$this->session->set_userdata('user_timezone', $user_tz);
				}
				redirect('/', 'refresh');
			}
			else
			{
				// if the login was un-successful
				// redirect them back to the login page
				$this->session->set_flashdata('message', $this->ion_auth->errors());
				redirect('/login', 'refresh'); // use redirects instead of loading views for compatibility with MY_Controller libraries
			}
		}
		else
		{
			// the user is not logging in so display the login page
			// set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

			$this->data['identity'] = [
				'name' => 'identity',
				'id' => 'identity',
				'type' => 'text',
				'value' => $this->form_validation->set_value('identity'),
			];

			$this->data['password'] = [
				'name' => 'password',
				'id' => 'password',
				'type' => 'password',
			];
			// echo "string";exit;
			$this->data['PageTitle'] = "Login";
			$this->_render_page('login', $this->data);
		}
	}


	/**
	 * Log the user out
	 */
	public function logout()
	{
		$this->data['title'] = "Logout";

		$user = $this->ion_auth->user()->row();
		$userID = $user->id;

		$accessArr= explode("," , $user->access);


		{
			$parent_id = $user->parent_user_id;

		}


		// echo $parent_user_id;exit;
		// log the user out
		$this->ion_auth->logout();

		// redirect them to the login page
		redirect('login?wl='.$parent_id, 'refresh');
	}


	/**
	 * Forgot password
	 */
	public function forgot_password()
	{
		$this->data['title'] = $this->lang->line('forgot_password_heading');
		
		// setting validation rules by checking whether identity is username or email
		if ($this->config->item('identity', 'ion_auth') != 'email')
		{
			$this->form_validation->set_rules('identity', $this->lang->line('forgot_password_identity_label'), 'required');
		}
		else
		{
			$this->form_validation->set_rules('identity', $this->lang->line('forgot_password_validation_email_label'), 'required|valid_email');
		}


		if ($this->form_validation->run() === FALSE)
		{
			$this->data['type'] = $this->config->item('identity', 'ion_auth');
			// setup the input
			$this->data['identity'] = [
				'name' => 'identity',
				'id' => 'identity',
			];

			if ($this->config->item('identity', 'ion_auth') != 'email')
			{
				$this->data['identity_label'] = $this->lang->line('forgot_password_identity_label');
			}
			else
			{
				$this->data['identity_label'] = $this->lang->line('forgot_password_email_identity_label');
			}

			// set any errors and display the form
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
			$this->_render_page('forgot_password', $this->data);
		}
		else
		{
			$identity_column = $this->config->item('identity', 'ion_auth');
			$identity = $this->ion_auth->where($identity_column, $this->input->post('identity'))->users()->row();

			if (empty($identity))
			{

				if ($this->config->item('identity', 'ion_auth') != 'email')
				{
					$this->ion_auth->set_error('forgot_password_identity_not_found');
				}
				else
				{
					$this->ion_auth->set_error('forgot_password_email_not_found');
				}

				$this->session->set_flashdata('message', $this->ion_auth->errors());
				redirect("forgot_password", 'refresh');
			}

			// run the forgotten password method to email an activation code to the user
			$forgotten = $this->ion_auth->forgotten_password($identity->{$this->config->item('identity', 'ion_auth')});

			if ($forgotten)
			{
				// if there were no errors
				$this->session->set_flashdata('message', $this->ion_auth->messages());
				redirect("login", 'refresh'); //we should display a confirmation page here instead of the login page
			}
			else
			{
				$this->session->set_flashdata('message', $this->ion_auth->errors());
				redirect("forgot_password", 'refresh');
			}
		}
	}


	/**
	 * Reset password - final step for forgotten password
	 *
	 * @param string|null $code The reset code
	 */
	public function reset_password($code = NULL)
	{
		if (!$code)
		{
			show_404();
		}

		$this->data['title'] = $this->lang->line('reset_password_heading');
		
		$user = $this->ion_auth->forgotten_password_check($code);

		if ($user)
		{
			// if the code is valid then display the password reset form

			$this->form_validation->set_rules('new', $this->lang->line('reset_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|matches[new_confirm]');
			$this->form_validation->set_rules('new_confirm', $this->lang->line('reset_password_validation_new_password_confirm_label'), 'required');

			if ($this->form_validation->run() === FALSE)
			{
				// display the form

				// set the flash data error message if there is one
				$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

				if(empty($_POST)){
					$this->data['message'] = '';
				}

				$this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
				$this->data['new_password'] = [
					'name' => 'new',
					'id' => 'new',
					'type' => 'password',
					'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
				];
				$this->data['new_password_confirm'] = [
					'name' => 'new_confirm',
					'id' => 'new_confirm',
					'type' => 'password',
					'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
				];
				$this->data['user_id'] = [
					'name' => 'user_id',
					'id' => 'user_id',
					'type' => 'hidden',
					'value' => $user->id,
				];
				$this->data['csrf'] = $this->_get_csrf_nonce();
				$this->data['code'] = $code;

				// render
				$this->_render_page('reset_password', $this->data);
			}
			else
			{
				$identity = $user->{$this->config->item('identity', 'ion_auth')};

			
					// finally change the password
					$change = $this->ion_auth->reset_password($identity, $this->input->post('new'));

					if ($change)
					{
						// if the password was successfully changed
						$this->session->set_flashdata('message', $this->ion_auth->messages());
						redirect("login", 'refresh');
					}
					else
					{
						$this->session->set_flashdata('message', $this->ion_auth->errors());
						redirect('reset_password/' . $code, 'refresh');
					}
				
			}
		}
		else
		{
			// if the code is invalid then send them back to the forgot password page
			$this->session->set_flashdata('message', $this->ion_auth->errors());
			redirect("forgot_password", 'refresh');
		}
	}


	/**
	 * @return array A CSRF key-value pair
	 */
	public function _get_csrf_nonce()
	{
		$this->load->helper('string');
		$key = random_string('alnum', 8);
		$value = random_string('alnum', 20);
		$this->session->set_flashdata('csrfkey', $key);
		$this->session->set_flashdata('csrfvalue', $value);

		return [$key => $value];
	}

	/**
	 * @return bool Whether the posted CSRF token matches
	 */
	public function _valid_csrf_nonce(){
		$csrfkey = $this->input->post($this->session->flashdata('csrfkey'));
		if ($csrfkey && $csrfkey === $this->session->flashdata('csrfvalue'))
		{
			return TRUE;
		}
			return FALSE;
	}

	/**
	 * Render or load the View
	 */
	public function _render_page($view, $data = NULL, $returnhtml = FALSE)//I think this makes more sense
	{

		$viewdata = (empty($data)) ? $this->data : $data;

		$view_html = $this->load->view($view, $viewdata, $returnhtml);

		// This will return html on 3rd argument being true
		if ($returnhtml)
		{
			return $view_html;
		}
	}


    public function terms_page()
    {
        $this->data['PageTitle'] = 'Terms of Service';
        $this->load->view('terms',$this->data);
    }


	/* Functions for Twilio call external services - start */

	// Function to initiate a call via Twilio and handle the initial greeting and input gathering
	public function call_generation() {
        header('Content-Type: text/xml');
        $response = new VoiceResponse();
        $message = isset($_GET['message']) ? $_GET['message'] : "Hello, welcome!";
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : "";
        $funnelId = isset($_GET['funnelId']) ? $_GET['funnelId'] : "";
        $gather_conversaion = "https://ailocalfunnels.engageleads.net/auth/ai_conversation?user_id={$user_id}&funnelId={$funnelId}";
        $response->pause(['length' => 2]); // Pause for 2 seconds
        $response->say($message, ['voice' => 'alice', 'language' => 'en-US']);
        $gather = $response->gather([
            'input'   => 'speech',
            'action'  => $gather_conversaion,
            'method'  => 'POST',
            'timeout' => 5  // seconds to wait for input
        ]);
        $gather->say("Please say your message.");
        // If no input, say goodbye.
        $response->say("We did not receive any input. Goodbye!", ['voice' => 'alice', 'language' => 'en-US']);
        $response->hangup();    
        echo $response;
        exit;
    }   
    
	// Function to handle AI conversation via Twilio
    public function ai_conversation() {
        header('Content-Type: text/xml; charset=utf-8');
        $userInput = $this->input->post('SpeechResult');
        $callSid = $this->input->post('CallSid'); 
        $user_id = $this->input->get('user_id');
        $funnelId = $this->input->get('funnelId');
        $gather_conversaion = "https://ailocalfunnels.engageleads.net/auth/ai_conversation?user_id={$user_id}&funnelId={$funnelId}";

		$funnelData = $this->app_lib->fetchTableData('funnels', array('id' => $funnelId, 'user_id' => $user_id));
        $knowledge_base = "You are a helpful assistant.";
        if(!empty($funnelData)) {
            $knowledge_base = $funnelData[0]['Data']->knowledge_base;
            if(!$knowledge_base){
                $knowledge_base .= "Use this knowledge base to answer the user's questions.";
                $knowledge_base .= "knowledge_base: ".$knowledge_base;
            }
        }
        
        // If no input was recognized, end the call.
        if(empty($userInput)) {
            $response = new VoiceResponse();
            $response->say("We did not receive any input. Goodbye.", ['voice' => 'alice', 'language' => 'en-US']);
            $response->hangup();
            echo $response;
            exit;
        }

        // Log the user's input into the conversation_log table
        $this->db->insert('twilio_conversation_logs', [
            'call_sid' => $callSid,
            'user_id'  => $user_id,
            'speaker'  => 'user',
            'message'  => $userInput
        ]);

        $openai_data = $this->Datamodel->fetchTableData('settings',array("user_id"=>$user_id,'meta_type'=>'openai'));
        $openai_data = unserialize($this->encryption->decrypt($openai_data[0]['Data']->meta_data));
        $apiKey = $openai_data['Openai_api'];
        
        // Call OpenAI to get a conversational response
        $aiResponse = $this->getOpenAIResponse($userInput,$apiKey, $knowledge_base);
        // print_r( $aiResponse); exit;

        // Log the AI's response
        $this->db->insert('twilio_conversation_logs', [
            'call_sid' => $callSid,
            'user_id'  => $user_id,
            'speaker'  => 'ai',
            'message'  => $aiResponse
        ]);

        $conversationTurnsData = ['conversation_turns' => 'conversation_turns+1'];
        $this->db->where('call_sid', $callSid);
        $this->db->update('twilio_call_logs', $conversationTurnsData);
        
        // Build a TwiML response with the AI reply and prompt for the next input.
        $response = new VoiceResponse();
        // $response->say("You said: " . $userInput, ['voice' => 'alice', 'language' => 'en-US']);
        // $response->say("Our response is: " . $aiResponse, ['voice' => 'alice', 'language' => 'en-US']);
        $response->say( $aiResponse, ['voice' => 'alice', 'language' => 'en-US']);
        
        // Use <Gather> to allow further conversation
        $gather = $response->gather([
            'input'   => 'speech',
            'action'  => $gather_conversaion,
            'method'  => 'POST',
            'timeout' => 5
        ]);
        $gather->say("Please say your next message.");
        
        // If nothing is received, say goodbye and hang up.
        $response->say("No input received. Goodbye!", ['voice' => 'alice', 'language' => 'en-US']);
        $response->hangup();
        
        echo $response;
        exit;
    }

	// to generate response from OpenAI for conversation
    public function getOpenAIResponse($prompt,$apiKey, $knowledge_base) {


		$prompt = "users query: ".$prompt."
		          
					please answer the question based on the knowledge base provided below, if the answer is not in the knowledge base, please say 'I don't know the answer or I don't have the information to answer that question'.

					Note: keep the answer short and simple but informative and to the point. Also do not add any extra information or details.

					knowledge_base: ".$knowledge_base ;
		  



        if($apiKey != '') {
            $url_params = array();
            $url_params['model'] = "gpt-4o-mini";
            $url_params['temperature'] = 0.7;
            $url_params['max_tokens'] = 20;
            $path = "/chat/completions";
            $url_params['messages'] = [
                [ "role" => "system", "content" => "You are a helpful assistant. " ],
                [ "role" => "user", "content" => $prompt ]
            ];
            $url_params = json_encode($url_params, JSON_UNESCAPED_UNICODE);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $url_params);
            curl_setopt($ch, CURLOPT_HTTPHEADER , array(
                "Authorization: Bearer " . $apiKey,
                "cache-control: no-cache",
                "Content-Type: application/json"
            ));
            $response = curl_exec($ch);
            if (curl_error($ch)) {
                throw new Exception(curl_error($ch));
            }
            curl_close($ch);
            $json = json_decode($response);
            if(isset($json->choices[0]->message->content)) {
                $resultMessage = trim($json->choices[0]->message->content);
            } else {
                $resultMessage =  "I'm sorry, I didn't understand that.";
            }

            // Log API usage. ai_conversation passes user_id/funnelId via GET.
            try {
                $uid    = (int) ($this->input->get('user_id') ?: 0);
                $fid    = (int) ($this->input->get('funnelId') ?: 0);
                $sid    = $this->input->post('CallSid') ?: null;
                $usage  = isset($json->usage) ? (array) $json->usage : [];
                if ($uid) {
                    $this->load->library('api_usage_lib');
                    $this->api_usage_lib->record([
                        'user_id'       => $uid,
                        'funnel_id'     => $fid ?: null,
                        'call_sid'      => $sid,
                        'source'        => 'openai',
                        'sub_type'      => 'text',
                        'model'         => 'gpt-4o-mini',
                        'endpoint'      => 'auth/ai_conversation',
                        'tokens_in'     => $usage['prompt_tokens']     ?? null,
                        'tokens_out'    => $usage['completion_tokens'] ?? null,
                        'api_key_last4' => substr((string) $apiKey, -4),
                        'request_meta'  => ['hook' => 'twilio_voice_kb_reply'],
                    ]);
                }
            } catch (Exception $ex) {}
        } else {
            $resultMessage =  "AI Api key is missing. Please try again later.";
        }
        return $resultMessage;
    }

	// To handle call status updates from Twilio
    public function twilio_callback() {
        // Retrieve call parameters from Twilio POST
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : "";
        $callSid    = isset($_POST['CallSid']) ? $_POST['CallSid'] : '';
        $callStatus = isset($_POST['CallStatus']) ? $_POST['CallStatus'] : '';
        $callDuration = isset($_POST['CallDuration']) ? $_POST['CallDuration'] : '';
        if($callSid != '') {
            $data = array();
            $data['twilio_call_status'] = $callStatus;
            $data['call_duration'] = $callDuration;
            $this->app_lib->updateTable('twilio_call_logs', array('call_sid' => $callSid), $data);

            // Log Twilio carrier cost on terminal status. Idempotent — Sequence::_log_twilio_call_cost
            // skips the insert if a 'twilio/call' row already exists for this call_sid.
            $terminal = array('completed', 'no-answer', 'busy', 'failed', 'canceled');
            if (in_array(strtolower((string) $callStatus), $terminal, TRUE)) {
                try {
                    $call_row = $this->db->select('user_id, funnel_id, subscriber_id')
                        ->from('twilio_call_logs')
                        ->where('call_sid', $callSid)
                        ->limit(1)
                        ->get()
                        ->row();
                    $this->load->module('sequence/sequence');
                    $this->sequence->_log_twilio_call_cost($call_row, $callSid, $callStatus, $callDuration);
                } catch (Exception $e) {
                    log_message('error', '[twilio_callback] cost log failed: ' . $e->getMessage());
                }
            }
            echo "Status updated";
        } else {
            echo "Status not updated";
        }
    }

	// To handle recording callback from Twilio
    public function record_callback() {
        // Get parameters from Twilio POST
        $user_id = $this->input->get('user_id');
        $recordingUrl = $_REQUEST['RecordingUrl'];
        $recordingDuration = $_REQUEST['RecordingDuration'];
        $callSid = $_REQUEST['CallSid'];
		// echo "userid: ".$user_id."callsid: ".$callSid."recordingurl: ".$recordingUrl."recordingduration: ".$recordingDuration;

		$twilio_data = $this->Datamodel->fetchTableData('settings',array("user_id"=>$user_id,'meta_type'=>'twilio'));
        $twilio_data = unserialize($this->encryption->decrypt($twilio_data[0]['Data']->meta_data));
        $this->sid = $twilio_data['twilio_account_sid'];
        $this->token = $twilio_data['twilio_auth_token'];
    
        if ($callSid != '' && $recordingUrl != '') {
            // Set up the file path for saving the recording in your project folder
            // For example, saving in an "uploads/voicecalls" folder
            $filePath = './uploads/voicecalls/' . $user_id . '_' . $callSid . '_recorded.mp3';
    
            // Initialize cURL session to download the recording
            $ch = curl_init($recordingUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // If needed, include your Twilio credentials for authentication
            curl_setopt($ch, CURLOPT_USERPWD, $this->sid . ':' . $this->token);
            $recordingData = curl_exec($ch);
            if(curl_errno($ch)){
                log_message('error', 'cURL error: ' . curl_error($ch));
            }
            curl_close($ch);

			// Save the recording to the file path
			$recording = file_put_contents($filePath, $recordingData);
    
            // Save the downloaded data to a file in your project folder
            if($recording) {
                $data = array();
                $data['recording_url'] = $filePath;
                $data['recording_details'] = json_encode($_POST);
                $this->app_lib->updateTable('twilio_call_logs', array('call_sid' => $callSid), $data);    
                echo "Recording data updated";
            } else {
                $data = array();
                $data['recording_url'] = "No file";
                $data['recording_details'] = "No data";
                $this->app_lib->updateTable('twilio_call_logs', array('call_sid' => $callSid), $data); 
                echo "Failed to save the recording file.";
            }
        } else {
            echo "Recording data not updated";
        }
    }
	/* Functions for Twilio call external services - end */

	/**
	 * GET /auth/process_drip_queue
	 * @deprecated Use /sequence/process_queue?token=XXX instead
	 */
	public function process_drip_queue()
	{
		$token = $this->input->get('key') ?: $this->input->get('token');
		redirect('sequence/process_queue?token=' . urlencode($token));
	}

	/**
	 * POST /auth/analyze_call
	 * Called by Node.js voice bridge after call ends.
	 * Receives transcript, uses Gemini to analyze sentiment/outcome/topics.
	 * Updates twilio_call_logs + subscriber lead_score/lead_status.
	 */
	public function analyze_call()
	{
		log_message('debug', '══════ POST-CALL: analyze_call START ══════');
		$json = json_decode(file_get_contents('php://input'), true);
		log_message('debug', 'POST-CALL: call_sid=' . ($json['call_sid'] ?? 'none') . ' | turns=' . ($json['turns'] ?? 0) . ' | engine=' . ($json['engine'] ?? '?') . ' | transcript_len=' . strlen($json['transcript'] ?? ''));
		$use_recording = isset($json['use_recording']) ? (bool) $json['use_recording'] : false;

		if (empty($json['transcript']) && !$use_recording) {
			log_message('error', 'POST-CALL: Missing transcript');
			echo json_encode(['status' => 'error', 'message' => 'Missing transcript']);
			return;
		}

		$call_sid              = isset($json['call_sid']) ? $json['call_sid'] : '';
		$transcript            = isset($json['transcript']) ? $json['transcript'] : '';
		$conversation_log      = isset($json['conversation_log']) ? $json['conversation_log'] : array();
		$turns                 = isset($json['turns']) ? intval($json['turns']) : 0;
		$engine                = isset($json['engine']) ? $json['engine'] : 'openai';
		$post_user_id          = isset($json['user_id']) ? $json['user_id'] : 0;
		$real_caller_turns     = isset($json['real_caller_turns']) ? intval($json['real_caller_turns']) : -1;
		$caller_actually_spoke = isset($json['caller_actually_spoke']) ? (bool) $json['caller_actually_spoke'] : true;

		// Find the call log entry by call_sid or most recent
		$call_log = null;
		if (!empty($call_sid)) {
			$this->db->where('call_sid', $call_sid);
			$call_log = $this->db->get('twilio_call_logs')->row();
		}
		if (empty($call_log)) {
			$this->db->where('engine', $engine);
			$this->db->where('twilio_call_status', 'initiated');
			$this->db->order_by('id', 'DESC');
			$call_log = $this->db->get('twilio_call_logs')->row();
		}
		log_message('debug', 'POST-CALL: call_log found=' . (!empty($call_log) ? 'YES (id=' . $call_log->id . ', subscriber=' . ($call_log->subscriber_id ?? 'none') . ')' : 'NO'));

		// ── Set per-request context so every downstream Openai/Gemini cost row
		//    inherits call_sid + funnel_id + lead_id without needing to thread params.
		$this->load->library('api_usage_lib');
		$ctx = array();
		if (!empty($call_sid))                                $ctx['call_sid']  = $call_sid;
		if (!empty($call_log) && !empty($call_log->funnel_id))    $ctx['funnel_id'] = $call_log->funnel_id;
		if (!empty($call_log) && !empty($call_log->subscriber_id)) $ctx['lead_id']   = $call_log->subscriber_id;
		if (!empty($ctx)) $this->api_usage_lib->set_context($ctx);

		$update_data = array(
			'conversation_turns'  => $turns,
			'twilio_call_status'  => 'completed',
		);

		// If Gemini engine call with no transcript, use recording file for transcription
		$this->load->library('gemini_lib');

		// This endpoint is called server-to-server by the Node voice bridge, which
		// has NO CI3 session — so Gemini_lib's constructor resolved the key for the
		// wrong user (it falls back to user 1, who has no key). Re-resolve it for
		// the funnel OWNER (browser/phone lane both pass their user_id; the call_log
		// row is authoritative) so analysis uses the owner's BYOK key. Without this,
		// gemini_api() returns no_key and the summary stays "AI analysis pending".
		$owner_user_id = (!empty($call_log) && !empty($call_log->user_id)) ? (int) $call_log->user_id : (int) $post_user_id;
		if ($owner_user_id > 0) {
			if (!$this->gemini_lib->set_user_key($owner_user_id)) {
				log_message('error', 'POST-CALL: no Gemini key for owner user_id=' . $owner_user_id . ' — analysis will be skipped.');
			}
		}

		if ($use_recording && empty($transcript) && !empty($call_sid) && !empty($call_log)) {
			$user_id_for_rec = $call_log->user_id ?: $post_user_id;
			$rec_path = FCPATH . 'uploads/recordings/' . $user_id_for_rec . '/call_' . $call_sid . '.mp4';
			log_message('debug', 'POST-CALL: Looking for recording at: ' . $rec_path);

			if (file_exists($rec_path)) {
				log_message('debug', 'POST-CALL: Recording found (' . round(filesize($rec_path) / 1024) . ' KB). Transcribing with Gemini...');
				$audio_data = base64_encode(file_get_contents($rec_path));
				$transcribe_prompt = "Transcribe this phone call recording. Return the transcript in this format:\nAI: [what the AI agent said]\nCaller: [what the caller said]\n\nAlternate between AI and Caller for each turn. Be accurate. If you cannot hear something clearly, write [inaudible]. Return ONLY the transcript, no other text.";

				$transcribe_result = $this->gemini_lib->gemini_api_with_audio($transcribe_prompt, $audio_data, 'audio/mp4');

				if (!empty($transcribe_result) && is_string($transcribe_result) && strlen($transcribe_result) > 20) {
					$transcript = $transcribe_result;
					log_message('debug', 'POST-CALL: Transcript generated from recording (' . strlen($transcript) . ' chars)');
					// Parse turns
					$lines = explode("\n", $transcript);
					$turns = 0;
					$real_caller_turns = 0;
					foreach ($lines as $line) {
						$line = trim($line);
						if (strpos($line, 'AI:') === 0) $turns++;
						if (strpos($line, 'Caller:') === 0) { $turns++; $real_caller_turns++; }
					}
					$caller_actually_spoke = $real_caller_turns > 0;
				} else {
					log_message('error', 'POST-CALL: Transcription failed — result: ' . (is_string($transcribe_result) ? substr($transcribe_result, 0, 200) : json_encode($transcribe_result)));
				}
			} else {
				// Recording might not be downloaded yet — wait and retry
				log_message('debug', 'POST-CALL: Recording not found yet at: ' . $rec_path . '. Will retry in 5s.');
				sleep(5);
				if (file_exists($rec_path)) {
					log_message('debug', 'POST-CALL: Recording found on retry.');
					$audio_data = base64_encode(file_get_contents($rec_path));
					$transcribe_result = $this->gemini_lib->gemini_api_with_audio(
						"Transcribe this phone call recording. Format: AI: [text]\nCaller: [text]. Return ONLY the transcript.",
						$audio_data, 'audio/mp4'
					);
					if (!empty($transcribe_result) && is_string($transcribe_result) && strlen($transcribe_result) > 20) {
						$transcript = $transcribe_result;
						$lines = explode("\n", $transcript);
						$turns = 0; $real_caller_turns = 0;
						foreach ($lines as $line) {
							if (strpos(trim($line), 'AI:') === 0) $turns++;
							if (strpos(trim($line), 'Caller:') === 0) { $turns++; $real_caller_turns++; }
						}
						$caller_actually_spoke = $real_caller_turns > 0;
					}
				}
			}
		}

		// Use Gemini to analyze
		$prompt = 'Analyze this AI phone call transcript and return a JSON object with these exact keys:
{
  "call_summary": "2-3 sentence summary of the conversation",
  "call_sentiment": "positive or neutral or negative",
  "call_outcome": "interested or not_interested or callback or booked or no_answer or voicemail",
  "call_topics": ["topic 1", "topic 2", "topic 3"],
  "next_action": "Recommended next step based on the conversation",
  "confidence": 0.85,
  "lead_score_delta": 10,
  "lead_requests": [
    {"type": "email", "details": "wants pricing info emailed", "urgency": "immediate"},
    {"type": "sms", "details": "asked for address via text", "urgency": "immediate"}
  ],
  "sentiment_timeline": [
    {"time_pct": 0, "sentiment": "neutral", "label": "Neutral", "value": 0.5},
    {"time_pct": 25, "sentiment": "warming_up", "label": "Warming up", "value": 0.6},
    {"time_pct": 50, "sentiment": "engaged", "label": "Engaged", "value": 0.75},
    {"time_pct": 80, "sentiment": "very_positive", "label": "Very positive", "value": 0.9},
    {"time_pct": 100, "sentiment": "positive", "label": "Positive", "value": 0.85}
  ],
  "extracted_info": {
    "email": "any email address the lead mentioned or null",
    "phone": "any alternate phone number the lead shared or null",
    "preferred_contact": "email or sms or call or null",
    "budget": "any budget or price concern mentioned or null",
    "timeline": "any timeline or urgency mentioned or null",
    "specific_interests": ["specific services or products they asked about"]
  }
}

Rules:
- call_sentiment must be exactly one of: positive, neutral, negative
- call_outcome must be exactly one of: interested, not_interested, callback, booked, no_answer, voicemail
- confidence is 0.0 to 1.0
- lead_score_delta is -20 to +30 (how much to adjust lead score)
- call_topics is array of 2-5 short topic strings
- lead_requests: Array of things the lead explicitly asked for during the call. Extract ANY request like:
  - "email me" / "send me an email" / "email me the details" → type: "email"
  - "text me" / "send me a text" / "SMS me" → type: "sms"
  - "call me back" / "call me later" → type: "callback" (also set call_outcome to "callback")
  - "send me info" / "send me a brochure" / "send me pricing" → type: "email" (default to email for info requests)
  - "send me directions" / "where are you located" → type: "sms" (short info fits SMS)
  Each request has: type (email/sms/callback), details (what they want), urgency (immediate/later)
  If the lead made NO explicit requests, return an empty array []
- sentiment_timeline: Array of 4-8 sentiment data points tracking how the conversation mood evolved over time. Each point has:
  - time_pct: percentage through the call (0-100)
  - sentiment: one of very_negative, negative, neutral, warming_up, engaged, positive, very_positive
  - label: human-readable label for display
  - value: numeric sentiment score 0.0 (very negative) to 1.0 (very positive)
  Analyze the ACTUAL conversation flow — where the caller warmed up, showed interest, hesitated, or became positive. Be accurate to what happened in the transcript.
- extracted_info: Extract any concrete information the lead shared during the call:
  - email: any email address they mentioned (e.g. "my email is john@example.com")
  - phone: any alternate phone number they shared
  - preferred_contact: how they prefer to be contacted (email/sms/call) — infer from what they asked for
  - budget: any budget concerns, price range, or pricing feedback (e.g. "that seems expensive", "my budget is around $500")
  - timeline: any timeline or urgency (e.g. "as soon as possible", "next month", "not in a rush")
  - specific_interests: array of specific services/products they asked about or showed interest in
  Use null for any field not mentioned. If nothing was shared, return all nulls.
- Return ONLY valid JSON, no markdown

TRANSCRIPT:
' . $transcript;

		$analysis = $this->gemini_lib->gemini_api($prompt);

		// Gemini sometimes returns JSON wrapped in markdown fences — parse it
		if (is_string($analysis)) {
			$cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($analysis));
			$cleaned = preg_replace('/```\s*$/', '', $cleaned);
			$parsed = json_decode(trim($cleaned), true);
			if (is_array($parsed)) {
				$analysis = $parsed;
			}
		}

		if (is_array($analysis) && !isset($analysis['error'])) {
			if (isset($analysis['call_summary']))      $update_data['call_summary']      = $analysis['call_summary'];
			if (isset($analysis['call_sentiment']))    $update_data['call_sentiment']    = $analysis['call_sentiment'];
			if (isset($analysis['call_outcome']))      $update_data['call_outcome']      = $analysis['call_outcome'];
			if (isset($analysis['call_topics']))       $update_data['call_topics']       = json_encode($analysis['call_topics']);
			if (isset($analysis['next_action']))       $update_data['next_action']       = $analysis['next_action'];
			if (isset($analysis['confidence']))        $update_data['confidence']        = floatval($analysis['confidence']);
			if (isset($analysis['sentiment_timeline'])) $update_data['sentiment_timeline'] = json_encode($analysis['sentiment_timeline']);
			if (isset($analysis['lead_requests']) && !empty($analysis['lead_requests'])) $update_data['lead_requests'] = json_encode($analysis['lead_requests']);

			// Override if caller never actually spoke (Whisper hallucination protection)
			if (!$caller_actually_spoke || $real_caller_turns === 0) {
				log_message('debug', 'POST-CALL: Caller never spoke — overriding outcome to no_answer');
				$update_data['call_outcome'] = 'no_answer';
				$update_data['call_sentiment'] = 'neutral';
				$update_data['call_summary'] = 'Call connected but the lead did not respond. AI greeted the caller but received no real speech input.';
				$update_data['lead_requests'] = null;
				$analysis['call_outcome'] = 'no_answer';
				$analysis['call_sentiment'] = 'neutral';
				$analysis['lead_requests'] = array();
			}
		} else {
			log_message('error', 'Gemini call analysis failed: ' . (is_string($analysis) ? $analysis : json_encode($analysis)));
			$update_data['call_summary'] = 'AI analysis pending — transcript saved.';
		}

		// Store individual conversation turns in twilio_conversation_logs
		$user_id_for_log = $post_user_id;
		if (!empty($call_log)) {
			$user_id_for_log = $call_log->user_id ?: $post_user_id;
		}
		if (!empty($conversation_log) && is_array($conversation_log)) {
			foreach ($conversation_log as $turn) {
				$speaker = (isset($turn['role']) && $turn['role'] === 'ai') ? 'ai' : 'user';
				$message = isset($turn['text']) ? $turn['text'] : '';
				if (empty($message)) continue;
				$this->db->insert('twilio_conversation_logs', array(
					'user_id'   => $user_id_for_log,
					'user_name' => !empty($call_log->user_name) ? $call_log->user_name : 'Lead',
					'call_sid'  => $call_sid,
					'speaker'   => $speaker,
					'message'   => $message,
				));
			}
			log_message('debug', 'POST-CALL: Stored ' . count($conversation_log) . ' conversation turns in twilio_conversation_logs');
		} else if (!empty($transcript)) {
			// Fallback: parse flat transcript into turns
			$lines = explode("\n", $transcript);
			foreach ($lines as $line) {
				$line = trim($line);
				if (empty($line)) continue;
				if (strpos($line, 'AI:') === 0) {
					$speaker = 'ai';
					$message = trim(substr($line, 3));
				} else if (strpos($line, 'Caller:') === 0) {
					$speaker = 'user';
					$message = trim(substr($line, 7));
				} else {
					continue;
				}
				if (empty($message)) continue;
				$this->db->insert('twilio_conversation_logs', array(
					'user_id'   => $user_id_for_log,
					'user_name' => !empty($call_log->user_name) ? $call_log->user_name : 'Lead',
					'call_sid'  => $call_sid,
					'speaker'   => $speaker,
					'message'   => $message,
				));
			}
			log_message('debug', 'POST-CALL: Parsed flat transcript into conversation turns');
		}

		// Update call log
		if (!empty($call_log)) {
			$this->db->where('id', $call_log->id);
			$this->db->update('twilio_call_logs', $update_data);

			// Is this an Agentic Affiliates bridge? IC funnels have followups_data (3 sentiment
			// buckets). Resolve first so we can gate both enrolment and the legacy pipeline.
			$ic_followups = $this->db->select('followups_data')->where('id', $call_log->funnel_id)
				->limit(1)->get('funnels')->row();
			$is_ic_bridge = $ic_followups && isset($ic_followups->followups_data)
				&& trim((string) $ic_followups->followups_data) !== '';

			// ── Agentic Affiliates sentiment-driven follow-ups ──────────────────────
			// call_outcome is now written. Tell the Node sequence enroller to
			// enrol this lead into the matching set (interested/neutral/
			// not_interested), resolved from THIS call_outcome. Best-effort +
			// idempotent: the Node side also enrols on run-end as a safety net.
			// Only for IC bridges — non-IC funnels use the legacy pipeline below.
			if ($is_ic_bridge) {
				$this->_ic_enrol_followups($call_log->subscriber_id, $call_log->funnel_id);
			}

			// Update subscriber lead score and drip track via the proper pipeline.
			// Runs for BOTH IC and legacy bridges: for IC, _ic_enrol_followups() above
			// already enrolled the static sequence (blocking curl → rows exist), so the
			// dynamic pass here personalizes the just-enrolled pending steps against the
			// call. The ONE IC difference is timing: IC's drip is clock-based (day+1…),
			// so the +2min acceleration block below is skipped for IC (content becomes
			// dynamic, the schedule stays) — see the $is_ic_bridge guard there.
			if (!empty($call_log->subscriber_id) && is_array($analysis) && !isset($analysis['error'])) {
				// Use Lead_scoring_lib for proper weighted scoring (replaces simple delta)
				$this->load->library('Lead_scoring_lib');
				$new_score = $this->lead_scoring_lib->recalculate($call_log->subscriber_id);
				log_message('debug', 'POST-CALL: Lead #' . $call_log->subscriber_id . ' recalculated score=' . $new_score);

				// Use Call_summary_lib::adjust_drip_track for proper track switching + content regeneration
				$this->load->library('Call_summary_lib');
				$summary_obj = (object) $analysis;
				$this->call_summary_lib->adjust_drip_track_public($call_log->subscriber_id, $call_log->funnel_id, $summary_obj);

				// Outcomes whose content must NOT be rewritten here:
				//  - 'booked'    → adjust_drip_track enqueued confirmation rows; a
				//                  transcript-driven rewrite would mangle them into
				//                  something that no longer reads as a confirmation.
				//  - 'voicemail' → retry-or-exhaust; there's no real call content.
				//
				// 'not_interested' is deliberately NOT here. It cancels the pending
				// ai_calls, but the EMAILS still go out (the sequence editor says so
				// explicitly) — and it was the ONLY outcome never personalised:
				// 3/90 negative + 0/28 neutral, vs ~52% for 'interested'. A lead who
				// declined is exactly who needs the follow-up to reflect what they
				// actually said, instead of a generic "great chatting!" template.
				$call_outcome = isset($analysis['call_outcome']) ? $analysis['call_outcome'] : '';
				$terminal_outcomes = ['booked', 'voicemail'];

				if (!in_array($call_outcome, $terminal_outcomes, true)) {
					// Always personalize pending content after a call (with call intelligence)
					$funnel = $this->db->where('id', $call_log->funnel_id)->get('funnels')->row();
					$this->load->library('Sequence_lib');

					// Determine current track from pending items
					$this->db->where(['lead_id' => $call_log->subscriber_id, 'funnel_id' => $call_log->funnel_id, 'status' => 'pending']);
					$this->db->limit(1);
					$first_pending = $this->db->get('sequence_queue')->row();
					$current_track = $first_pending ? $first_pending->track : 'standard';

					// Guard against double rewrite: adjust_drip_track_public above may have
					// already triggered Sequence_lib::regenerate_track_content via
					// Lead_scoring_lib::auto_switch_track when the track flipped to warm/nurture/
					// confirmation. Re-running here would (a) spend 3 redundant gpt-4o-mini calls
					// per call and (b) feed the just-rewritten content back into the model as
					// input, which strips salutations / links / sign-offs on the second pass.
					// Skip if any pending row was personalized in the last 60s — comfortably
					// covers the typical 5-15s analyze_call window without blocking legitimate
					// later triggers (e.g. webhooks, manual reruns) from a separate request.
					$recent_personalize_cutoff = gmdate('Y-m-d H:i:s', time() - 60);
					$recently_personalized = (int) $this->db
						->where('lead_id', $call_log->subscriber_id)
						->where('funnel_id', $call_log->funnel_id)
						->where('personalized_at >=', $recent_personalize_cutoff)
						->count_all_results('sequence_queue');

					if ($recently_personalized > 0) {
						log_message('debug', 'POST-CALL: Skipping regenerate — '
							. $recently_personalized . ' pending row(s) already personalized in last 60s'
							. ' (likely by adjust_drip_track_public → auto_switch_track)');
					} else {
						// Temporarily force personalized mode so rewrite includes call context
						$orig_mode = $funnel ? ($funnel->followup_mode ?? 'template') : 'template';
						if ($orig_mode !== 'personalized') {
							$this->db->where('id', $call_log->funnel_id)->update('funnels', ['followup_mode' => 'personalized']);
						}

						log_message('debug', 'POST-CALL: Regenerating content for track=' . $current_track);
						$this->sequence_lib->regenerate_track_content($call_log->subscriber_id, $call_log->funnel_id, $current_track);

						// Restore original mode
						if ($orig_mode !== 'personalized') {
							$this->db->where('id', $call_log->funnel_id)->update('funnels', ['followup_mode' => $orig_mode]);
						}
					}

					// Accelerate next pending email + SMS to fire within 2 minutes.
					// LEGACY bridges only: their drip fires relative to each call, so
					// releasing the just-finished step's email/SMS now is correct. IC's
					// drip is CLOCK-based (day+1…) from opt-in — accelerating would
					// collapse the whole schedule, so IC keeps its scheduled times.
					// Never accelerate a lead who just declined: they still get the
					// emails, but chasing them 2 minutes after a "no" is the wrong
					// move (personalising that content, above, is the right one).
					if (!$is_ic_bridge && $call_outcome !== 'not_interested') {
						try {
							$soon = gmdate('Y-m-d H:i:s', time() + 120);
							log_message('debug', 'POST-CALL: Accelerating email+sms for lead #' . $call_log->subscriber_id . ' to ' . $soon);
							foreach (['email', 'sms'] as $ch) {
								$next = $this->db->query(
									"SELECT id, step_number FROM sequence_queue WHERE lead_id = ? AND funnel_id = ? AND status = 'pending' AND channel = ? ORDER BY step_number ASC LIMIT 1",
									[$call_log->subscriber_id, $call_log->funnel_id, $ch]
								)->row();
								if ($next) {
									$this->db->query("UPDATE sequence_queue SET scheduled_at = ? WHERE id = ?", [$soon, $next->id]);
									log_message('debug', 'POST-CALL: Accelerated ' . $ch . ' step ' . $next->step_number . ' (id #' . $next->id . ')');
								}
							}
						} catch (\Throwable $e) {
							log_message('error', 'POST-CALL acceleration error: ' . $e->getMessage());
						}
					}
				} else {
					log_message('debug', 'POST-CALL: Skipping regenerate+accelerate for terminal outcome=' . $call_outcome);
				}

				// Auto-schedule callback if lead requested one
				$call_outcome = isset($analysis['call_outcome']) ? $analysis['call_outcome'] : '';
				if ($call_outcome === 'callback') {
					// Parse callback timing from next_action or default to 15 minutes
					$delay_minutes = 15;
					$next_action = isset($analysis['next_action']) ? $analysis['next_action'] : '';
					if (preg_match('/(\d+)\s*min/i', $next_action, $m)) {
						$delay_minutes = max(5, min(60, intval($m[1])));
					} elseif (preg_match('/(\d+)\s*hour/i', $next_action, $m)) {
						$delay_minutes = intval($m[1]) * 60;
					}

					// Get voice config for greeting
					$this->db->where(['funnel_id' => $call_log->funnel_id, 'is_active' => 1]);
					$vc = $this->db->get('voice_call_config')->row();
					$sub = $this->db->where('id', $call_log->subscriber_id)->get('subscribers')->row();
					$lead_name = $sub ? $sub->subscriber_name : 'there';

					$callback_greeting = "Hi {$lead_name}, this is a callback from " . ($funnel ? $funnel->campaign_name : 'us') . " as you requested. ";
					if (!empty($analysis['call_topics']) && is_array($analysis['call_topics'])) {
						$callback_greeting .= "You mentioned interest in " . implode(' and ', array_slice($analysis['call_topics'], 0, 2)) . ". ";
					}
					$callback_greeting .= "How can I help you today?";

					$this->db->insert('sequence_queue', array(
						'funnel_id'    => $call_log->funnel_id,
						'lead_id'      => $call_log->subscriber_id,
						'channel'      => 'ai_call',
						'step_number'  => 0,
						'content'      => json_encode(array(
							'greeting' => $callback_greeting,
							'persona'  => $vc ? $vc->persona : '',
							'voice'    => $vc ? $vc->voice : 'ash',
							'engine'   => $vc ? $vc->engine : 'openai',
						)),
						'scheduled_at' => date('Y-m-d H:i:s', time() + ($delay_minutes * 60)),
						'status'       => 'pending',
						'track'        => 'standard',
					));
					log_message('debug', 'POST-CALL: Callback scheduled in ' . $delay_minutes . ' min for lead #' . $call_log->subscriber_id);
				}

				// Auto-fulfil the lead's explicit requests: when the AI detects the
				// lead asked to be emailed / texted something, generate that message
				// and QUEUE it (sequence_queue) for delivery on the owner's own
				// channels. Only fires for requests where we actually have the
				// contact info + verifiable facts to answer (see the strict content
				// generator — it refuses to invent details and returns null, which
				// skips the send). Best-effort + non-blocking.
				if (isset($analysis['lead_requests']) && is_array($analysis['lead_requests']) && !empty($analysis['lead_requests'])) {
					try {
						$this->_fulfill_lead_requests($call_log, $analysis, $analysis['lead_requests']);
					} catch (\Throwable $e) {
						log_message('error', 'POST-CALL: lead-request fulfilment error: ' . $e->getMessage());
					}
				}

				// Save extracted contact info to subscriber record
				if (isset($analysis['extracted_info']) && is_array($analysis['extracted_info'])) {
					$this->load->library('Call_summary_lib');
					$this->call_summary_lib->save_extracted_info_public($call_log->subscriber_id, (object) $analysis['extracted_info']);
				}
			}
		}

		echo json_encode(['status' => 'ok', 'analyzed' => is_array($analysis) && !isset($analysis['error'])]);
	}

	/**
	 * Agentic Affiliates: enrol a lead into the sentiment-driven follow-up set after the
	 * call outcome is known. Resolves the lead's latest trigger_run and POSTs it to
	 * the Node sequence enroller (127.0.0.1:4000 /v1/sequences/enroll), which picks
	 * the bucket from twilio_call_logs.call_outcome and writes sequence_queue rows.
	 *
	 * Best-effort + non-blocking: failures are logged and swallowed so post-call
	 * analysis never breaks on a sequence-enroll hiccup. Idempotent on the Node side.
	 *
	 * @param int $subscriber_id
	 * @param int $funnel_id
	 * @return void
	 */
	private function _ic_enrol_followups($subscriber_id, $funnel_id)
	{
		$subscriber_id = (int) $subscriber_id;
		$funnel_id     = (int) $funnel_id;
		if ($subscriber_id <= 0 || $funnel_id <= 0) { return; }

		// Latest Triple-Trigger run for this lead+funnel (INT keys — collation-safe).
		$run = $this->db->select('id')
			->where('subscriber_id', $subscriber_id)
			->where('funnel_id', $funnel_id)
			->order_by('id', 'DESC')
			->limit(1)
			->get('trigger_runs')->row();
		if (!$run) {
			log_message('debug', 'IC-FOLLOWUP: no trigger_run for subscriber=' . $subscriber_id . ' funnel=' . $funnel_id . ' — skip enrol');
			return;
		}

		$headers = ['Content-Type: application/json'];
		// Internal token from the CI3 config table (no .env in the CI app) — must
		// match the Node side's config.security.internalToken. Empty/unset = gate
		// disabled. Config_lib::getKey returns the string 'invalid key' when absent.
		$this->load->library('config_lib');
		$tok = $this->config_lib->getKey('bridge_internal_token');
		if ($tok && $tok !== 'invalid key') {
			$headers[] = 'X-Internal-Token: ' . $tok;
		}

		// Canonical local-vs-prod bridge base (Bridge_service::internal_base):
		// localhost base_url → http://127.0.0.1:4000, else bridge_internal_base.
		$this->load->library('bridge_service');
		$base = $this->bridge_service->internal_base();

		$ch = curl_init($base . '/v1/sequences/enroll');
		curl_setopt_array($ch, [
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => json_encode(['triggerRunId' => (int) $run->id]),
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 8,
			CURLOPT_CONNECTTIMEOUT => 4,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 0,
		]);
		$resp = curl_exec($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		log_message('debug', 'IC-FOLLOWUP: enrol run=' . (int) $run->id . ' http=' . $code . ' resp=' . substr((string) $resp, 0, 200));
	}

	/**
	 * Fulfill explicit lead requests extracted from the call transcript.
	 * Generates personalized content via OpenAI and queues immediate email/SMS.
	 *
	 * @param object $call_log     The call log row
	 * @param array  $analysis     Full Gemini analysis result
	 * @param array  $lead_requests Array of request objects [{type, details, urgency}, ...]
	 */
	private function _fulfill_lead_requests($call_log, $analysis, $lead_requests)
	{
		$sub = $this->db->where('id', $call_log->subscriber_id)->get('subscribers')->row();
		if (!$sub) return;

		$funnel = $this->db->where('id', $call_log->funnel_id)->get('funnels')->row();
		// Prefer the real brand name; fall back to the campaign label, then a generic.
		$business_name = $funnel ? (trim((string) $funnel->brand_name) ?: trim((string) $funnel->campaign_name) ?: 'our business') : 'our business';
		// The business KNOWLEDGE the message may draw on — the SAME knowledge_base the
		// voice agent used on the call, so a follow-up can actually answer the lead's
		// request from real facts (not fabricate). Clamped so a huge KB can't blow up
		// the prompt. Persona keeps the tone consistent with the call; the affiliate
		// link is a real destination the message may point to.
		$knowledge = $funnel ? trim((string) $funnel->knowledge_base) : '';
		if ($knowledge !== '') { $knowledge = mb_substr($knowledge, 0, 1800); }
		$persona   = $funnel ? mb_substr(trim((string) $funnel->voice_persona), 0, 400) : '';
		$link      = $funnel ? trim((string) $funnel->affiliate_link) : '';
		// WHO the message is from. Without this the model reached the sign-off with
		// only a BRAND name and no person, so it invented "[Your Name]" — which then
		// went out to a real lead. The owner's real name is the only honest source;
		// if we don't have one the prompt tells the model to sign off with the brand
		// instead of inventing a placeholder.
		$sender_name = '';
		if ($funnel && !empty($funnel->user_id)) {
			$owner = $this->db->select('first_name, last_name, company')
				->where('id', (int) $funnel->user_id)->get('users')->row();
			if ($owner) {
				$sender_name = trim(trim((string) $owner->first_name) . ' ' . trim((string) $owner->last_name));
				if ($sender_name === '') { $sender_name = trim((string) $owner->company); }
			}
		}
		$lead_name = $sub->subscriber_name ?: 'there';
		$lead_email = $sub->email ?: '';
		$lead_phone = $sub->phone ?: '';
		$topics = isset($analysis['call_topics']) ? $analysis['call_topics'] : array();
		$summary = isset($analysis['call_summary']) ? $analysis['call_summary'] : '';

		// Load OpenAI for content generation
		$this->load->library('Openai_lib');

		// Fulfilment rows live in a SEPARATE step_number namespace (900+) so they
		// never collide with the normal drip sequence (steps 0..N) on the unique
		// key uk_sequence_idempotent (lead_id, funnel_id, channel, step_number).
		// Incremented per request so two email/SMS asks on one call don't clash
		// with each other either.
		$fulfil_step = 900;

		foreach ($lead_requests as $req) {
			$type = isset($req['type']) ? strtolower($req['type']) : '';
			$details = isset($req['details']) ? $req['details'] : '';
			$urgency = isset($req['urgency']) ? $req['urgency'] : 'immediate';

			// Skip callback type — already handled by the callback scheduler above
			if ($type === 'callback') continue;

			// Skip if we don't have the contact info for this channel
			if ($type === 'email' && empty($lead_email)) {
				log_message('debug', 'POST-CALL: Lead requested email but no email on file for lead #' . $sub->id);
				continue;
			}
			if ($type === 'sms' && empty($lead_phone)) {
				log_message('debug', 'POST-CALL: Lead requested SMS but no phone on file for lead #' . $sub->id);
				continue;
			}

			// Generate personalized content via OpenAI. The business knowledge +
			// persona + link are the ONLY facts the strict prompt lets it state.
			$content = $this->_generate_request_content($type, $details, $lead_name, $business_name, $topics, $summary, $knowledge, $persona, $link, $sender_name);
			if (!$content) continue;

			// Schedule for immediate delivery (or slight delay for "later" urgency)
			$delay_seconds = ($urgency === 'later') ? 1800 : 60; // 30 min vs 1 min

			if ($type === 'email') {
				$this->db->insert('sequence_queue', array(
					'funnel_id'    => $call_log->funnel_id,
					'lead_id'      => $call_log->subscriber_id,
					'channel'      => 'email',
					'step_number'  => $fulfil_step++,
					'subject'      => $content['subject'],
					'content'      => $content['body'],
					'scheduled_at' => date('Y-m-d H:i:s', time() + $delay_seconds),
					'status'       => 'pending',
					'track'        => 'standard',
				));
				log_message('debug', 'POST-CALL: Queued requested email for lead #' . $sub->id . ' — ' . $details);
			} elseif ($type === 'sms') {
				$this->db->insert('sequence_queue', array(
					'funnel_id'    => $call_log->funnel_id,
					'lead_id'      => $call_log->subscriber_id,
					'channel'      => 'sms',
					'step_number'  => $fulfil_step++,
					'content'      => $content['body'],
					'scheduled_at' => date('Y-m-d H:i:s', time() + $delay_seconds),
					'status'       => 'pending',
					'track'        => 'standard',
				));
				log_message('debug', 'POST-CALL: Queued requested SMS for lead #' . $sub->id . ' — ' . $details);
			}
		}
	}

	/**
	 * Generate personalized content for a lead's explicit request using OpenAI.
	 *
	 * @param string $type         email or sms
	 * @param string $details      What the lead asked for
	 * @param string $lead_name    Lead's name
	 * @param string $business     Business / brand name
	 * @param array  $topics       Call topics
	 * @param string $summary      Call summary
	 * @param string $knowledge    Business knowledge base (the ONLY facts to state)
	 * @param string $persona      Voice persona (tone to match the call)
	 * @param string $link         A real destination URL the message may point to
	 * @param string $sender_name  The owner's real name — who the message is FROM
	 * @return array|null          ['subject'=>..., 'body'=>...] or null on failure
	 */
	private function _generate_request_content($type, $details, $lead_name, $business, $topics, $summary, $knowledge = '', $persona = '', $link = '', $sender_name = '')
	{
		$topics_str = !empty($topics) ? implode(', ', $topics) : 'general inquiry';
		// The business facts block — only include what we actually have.
		$facts = '';
		if (trim((string) $knowledge) !== '') { $facts .= "\nBusiness knowledge (the ONLY facts you may state):\n" . trim($knowledge) . "\n"; }
		if (trim((string) $link) !== '')      { $facts .= "\nThe official offer / checkout / purchase page for {$business} is: {$link}\nThis IS the correct link to send whenever the lead asked for the offer link, affiliate link, checkout link, purchase link, \"the link\", where to buy, or where to sign up. Include it plainly in those cases.\n"; }
		// WHO is writing. Supply a real name when we have one; otherwise tell the model
		// to sign off as the brand. Either way it must never invent a placeholder.
		if (trim((string) $sender_name) !== '') {
			$facts .= "\nYou are writing AS: " . trim($sender_name) . " (from {$business}). Sign off with this exact name — never a placeholder.\n";
		} else {
			$facts .= "\nNo individual sender name is available. Sign off with the business name \"{$business}\" alone (e.g. \"The {$business} team\"). NEVER write a placeholder like [Your Name].\n";
		}
		$tone = trim((string) $persona) !== '' ? "\nMatch this tone/persona: " . trim($persona) : '';

		// The insufficiency rule DEPENDS on whether we actually hold a link. The
		// previous single rule always claimed "you DO have it" for link requests —
		// true only when $link is set. With no link configured on the Bridge, that
		// wording told the model to answer a "send me the buying link" request
		// anyway, so it reached for the only URL in its context (the example URL
		// that used to sit in the formatting rule) and mailed it to a real lead.
		if (trim((string) $link) !== '') {
			$link_rule = "- Only set \"insufficient\": true when the request needs a specific thing you genuinely do NOT have (e.g. a downloadable PDF, a custom quote, a booking time) AND the offer link + business knowledge above cannot satisfy it. If the lead just wanted the link, the offer, pricing, or general info, you DO have it — set \"insufficient\": false and answer.";
		} else {
			$link_rule = "- You have NOT been given any offer, checkout, purchase, or sign-up link. If the lead asked for a link, the offer page, where to buy, or where to sign up, you CANNOT fulfil that: set \"insufficient\": true and do not write a URL. Only set \"insufficient\": false when the request can be answered entirely from the business knowledge above without any link.";
		}

		// STRICT anti-fabrication rules shared by both channels. This message is
		// sent automatically on the owner's behalf, so it must NEVER state a fact
		// that isn't in the provided context — no invented prices, links, dates,
		// discounts, attachments, product specs, or promises. When the lead asked
		// for something concrete we don't actually have (e.g. "send me the pricing
		// PDF", "email me the case study"), the model must NOT make it up: it
		// acknowledges the request, says the {$business} team will follow up with
		// it, and returns "insufficient": true so we can SKIP the auto-send and
		// leave it for the owner to handle manually.
		$strict = "STRICT RULES (this message is auto-sent on the business's behalf):
- ONLY use facts present in the context above. Do NOT invent or assume anything.
- You MAY freely include the official offer/checkout link given above, and any price or detail that appears in the Business knowledge. Do NOT invent a DIFFERENT link, price, discount, date, address, phone number, or attachment that is not in the context.
- Do NOT claim something is attached, or promise a file/quote you were not given.
{$link_rule}
- NEVER output square-bracket placeholders of ANY kind — no [Your Name], [Name], [Company], [link], [date]. If you don't have a real value, leave it out entirely and rewrite the sentence. An unsigned message is FAR better than one signed \"[Your Name]\".
- The body is PLAIN TEXT, not markdown. Write any URL bare and complete, exactly as given to you above — never [text](url), never <url>, never **bold**, never backticks. (The \"no markdown\" rule applies to the message body itself, not just the JSON wrapper.)
- NEVER write a URL that does not appear verbatim in the context above. Do not use example, placeholder, or remembered URLs of any kind. If no link was given to you, the message must contain no URL at all.
- Write in the lead's apparent language from the summary; default to English.";

		if ($type === 'email') {
			$prompt = "Write a short, honest follow-up email for a lead who just had a phone call with {$business} and explicitly requested: \"{$details}\".

Context (the ONLY facts you may use):
- Lead name: {$lead_name}
- Topics discussed: {$topics_str}
- Call summary: {$summary}
{$facts}
{$strict}{$tone}

Style: professional, warm, directly addresses the request, ends with a clear next step (reply, or the team follows up). Under 160 words.

Return ONLY valid JSON, no markdown: {\"subject\": \"...\", \"body\": \"...\", \"insufficient\": true|false}";
		} else {
			// SMS — short and direct
			$prompt = "Write a short, honest SMS for a lead who just had a phone call with {$business} and explicitly requested: \"{$details}\".

Context (the ONLY facts you may use):
- Lead name: {$lead_name}
- Topics discussed: {$topics_str}
{$facts}
{$strict}{$tone}

Style: friendly, direct, under 160 characters if possible (max 320). One simple call-to-action (reply or the team follows up).

Return ONLY valid JSON, no markdown: {\"body\": \"...\", \"insufficient\": true|false}";
		}

		$messages = array(
			array('role' => 'system', 'content' => 'You are a marketing assistant that writes personalized follow-up messages. Always return valid JSON only.'),
			array('role' => 'user', 'content' => $prompt),
		);

		// Lower temperature — a factual auto-sent message should be conservative,
		// not creative (reduces the chance of the model embellishing).
		$response = $this->openai_lib->chat_completion($messages, 'gpt-4o-mini', 0.2, 1024);
		if (!$response || !isset($response['choices'][0]['message']['content'])) {
			log_message('error', 'POST-CALL: Failed to generate request content for type=' . $type);
			return null;
		}

		$raw = $response['choices'][0]['message']['content'];
		$cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
		$cleaned = preg_replace('/```\s*$/', '', $cleaned);
		$parsed = json_decode(trim($cleaned), true);

		if (!is_array($parsed) || (($type === 'email') && (!isset($parsed['subject']) || !isset($parsed['body'])))) {
			log_message('error', 'POST-CALL: Invalid JSON from content generation: ' . $raw);
			return null;
		}

		// Anti-fabrication guardrail: if the model flagged the request as needing
		// details it wasn't given, DON'T auto-send an invented message — return
		// null so the caller skips it and the owner can act manually.
		if (!empty($parsed['insufficient'])) {
			log_message('debug', 'POST-CALL: request needs unavailable details — skipping auto-send (type=' . $type . ')');
			return null;
		}

		if ($type === 'sms' && !isset($parsed['subject'])) {
			$parsed['subject'] = ''; // SMS doesn't need a subject
		}

		// Deterministic guard. The prompt ALREADY banned bracket placeholders and the
		// model shipped "[Your Name]" to a real lead anyway, and returned the offer
		// link as markdown "[url](url)" which rendered literally in the email. Prompt
		// rules are best-effort; this is the backstop that actually holds.
		$parsed['body'] = $this->_sanitize_generated_message($parsed['body'], $sender_name, $business, $lead_name);
		if (isset($parsed['subject'])) {
			$parsed['subject'] = $this->_sanitize_generated_message($parsed['subject'], $sender_name, $business, $lead_name);
		}
		if (trim((string) $parsed['body']) === '') {
			log_message('error', 'POST-CALL: generated body was empty after sanitising — skipping (type=' . $type . ')');
			return null;
		}

		// HARD URL GUARD — the layer that actually holds. A prompt is not a
		// boundary: the model previously copied the example URL out of its own
		// formatting rule and mailed "https://example.com/x" to a live lead as the
		// buying link. Any URL in the output must appear verbatim in what we gave
		// it; anything else is fabricated, so refuse to queue the message at all
		// rather than send a wrong link on the owner's behalf.
		$allowed_urls = $this->_extract_urls(trim((string) $link) . ' ' . (string) $knowledge);
		foreach ($this->_extract_urls((string) $parsed['body'] . ' ' . (string) (isset($parsed['subject']) ? $parsed['subject'] : '')) as $found) {
			$ok = false;
			foreach ($allowed_urls as $allowed) {
				// Compare on a normalised form so a trailing slash or punctuation
				// swallowed by the match doesn't cause a false rejection.
				if (rtrim($found, '/.,);:') === rtrim($allowed, '/.,);:')) { $ok = true; break; }
			}
			if (!$ok) {
				log_message('error', 'POST-CALL: BLOCKED fabricated URL "' . $found . '" in generated ' . $type
					. ' (no matching link in context) — message not queued.');
				return null;
			}
		}

		return $parsed;
	}

	/**
	 * Every http(s) URL in a blob of text, lowercased for comparison.
	 * Used to prove that a generated message only contains links we supplied.
	 *
	 * @param string $text
	 * @return array
	 */
	private function _extract_urls($text)
	{
		$out = array();
		if (preg_match_all('#https?://[^\s<>"\'\)\]]+#i', (string) $text, $m)) {
			foreach ($m[0] as $u) { $out[] = strtolower($u); }
		}
		return array_values(array_unique($out));
	}

	/**
	 * Strip model artefacts from an auto-sent message: markdown link syntax and
	 * square-bracket placeholders. This message goes out unattended on the owner's
	 * behalf, so a leaked "[Your Name]" or a literal "[url](url)" is worse than a
	 * slightly plainer sentence.
	 *
	 * @param string $text
	 * @param string $sender_name  Real sender, used to replace a name placeholder
	 * @param string $business     Brand, used when there is no sender name
	 * @param string $lead_name    The lead, used for a greeting placeholder
	 * @return string
	 */
	private function _sanitize_generated_message($text, $sender_name = '', $business = '', $lead_name = '')
	{
		$out = (string) $text;

		// 1) Markdown links → the bare URL. Covers [text](url) and the common
		//    [url](url) the model actually produced.
		$out = preg_replace('/\[([^\]]*)\]\((https?:\/\/[^\s)]+)\)/i', '$2', $out);
		// 2) Autolink form <https://…> → bare URL.
		$out = preg_replace('/<(https?:\/\/[^>\s]+)>/i', '$1', $out);

		// 3) Name placeholders resolve to the RIGHT person. A sender placeholder
		//    ([Your Name]) is the owner; a bare [Name]/[First Name] is almost always
		//    the LEAD's greeting ("Hi [Name],") — substituting the owner there would
		//    address the lead by the wrong person's name.
		$signer = trim((string) $sender_name) !== '' ? trim($sender_name) : trim((string) $business);
		$out = preg_replace('/\[\s*(your|sender|agent|rep|my)\s*name\s*\]/i', $signer, $out);
		if (trim((string) $lead_name) !== '') {
			$out = preg_replace('/\[\s*(first\s*|lead\s*|customer\s*)?name\s*\]/i', trim($lead_name), $out);
		}

		// 4) Any OTHER surviving [placeholder] means the model had no real value for
		//    it. Deleting the token alone leaves broken grammar ("Contact us at
		//    today."), so drop the whole LINE it sits on. A missing line reads fine;
		//    a mangled sentence does not.
		if (preg_match('/\[[^\]\n]{0,60}\]/', $out)) {
			$kept = array();
			foreach (preg_split('/\n/', $out) as $line) {
				if (!preg_match('/\[[^\]\n]{0,60}\]/', $line)) { $kept[] = $line; continue; }
				// A placeholder inside a multi-sentence line: drop just that sentence.
				$fixed = preg_replace('/[^.!?\n]*\[[^\]\n]{0,60}\][^.!?\n]*[.!?]?\s*/', '', $line);
				if (trim($fixed) !== '') { $kept[] = $fixed; }
			}
			$out = implode("\n", $kept);
		}

		// 5) Tidy the damage: blank sign-off lines and trailing separators.
		$out = preg_replace('/^[ \t]*[,\-–—][ \t]*$/m', '', $out);
		$out = preg_replace('/[ \t]{2,}/', ' ', $out);
		$out = preg_replace('/\n{3,}/', "\n\n", $out);

		return trim($out);
	}

	/**
	 * POST /auth/store_recording
	 * Called by Node.js after recording is downloaded and saved to uploads/recordings/{user_id}/
	 * Updates twilio_call_logs with the recording URL and duration.
	 */
	public function store_recording()
	{
		$json = json_decode(file_get_contents('php://input'), true);
		$call_sid      = isset($json['call_sid']) ? $json['call_sid'] : '';
		$recording_url = isset($json['recording_url']) ? $json['recording_url'] : '';
		$duration      = isset($json['duration']) ? intval($json['duration']) : 0;

		if (empty($call_sid) || empty($recording_url)) {
			echo json_encode(['status' => 'error', 'message' => 'Missing call_sid or recording_url']);
			return;
		}

		log_message('debug', 'RECORDING: store_recording — call_sid=' . $call_sid . ' | url=' . $recording_url . ' | duration=' . $duration);

		$update = array('recording_url' => $recording_url);
		if ($duration > 0) {
			$update['call_duration'] = $duration;
		}

		$this->db->where('call_sid', $call_sid);
		$affected = $this->db->update('twilio_call_logs', $update);

		echo json_encode(['status' => 'ok', 'updated' => $this->db->affected_rows()]);
	}

	/**
	 * GET /auth/voice_keyword_rules
	 *
	 * Returns the active voice-transcript keyword rules so the realtime voice
	 * bridges can cache them locally. Public endpoint — bridges have no session,
	 * which is why this lives on Auth (extends MY_Controller, not App_Controller).
	 */
	public function voice_keyword_rules()
	{
		$this->output->set_content_type('application/json');

		$rules = $this->db
			->select('id, keyword, category, severity, action, is_regex')
			->from('content_keyword_rules')
			->like('applies_to', 'voice_transcript')
			->where('is_active', 1)
			->order_by('severity', 'DESC')
			->get()->result();

		// is_active column may not exist on stale installs — retry without it.
		if ($this->db->error()['code']) {
			$rules = $this->db
				->select('id, keyword, category, severity, action, is_regex')
				->from('content_keyword_rules')
				->like('applies_to', 'voice_transcript')
				->order_by('severity', 'DESC')
				->get()->result();
		}

		return $this->output->set_output(json_encode([
			'ok'         => true,
			'count'      => count($rules),
			'rules'      => $rules,
			'fetched_at' => date('c'),
		]));
	}

	/**
	 * POST /auth/voice_live_flag
	 *
	 * Receives a flag payload from a voice bridge when a keyword/AI moderation
	 * trip occurs during a live call. Hands off to Ai_moderator_lib.
	 *
	 * Expected JSON body:
	 *   {
	 *     "user_id": 123, "lead_id": 456, "funnel_id": 789, "call_log_id": 999,
	 *     "flagged_party": "caller"|"ai_assistant"|"both",
	 *     "transcript": "...short excerpt...",
	 *     "violations": [...],
	 *     "action": "log"|"flag"|"end_call",
	 *     "severity": "low"|"medium"|"high"|"critical",
	 *     "source": "voice_bridge/openai"
	 *   }
	 */
	public function voice_live_flag()
	{
		$this->output->set_content_type('application/json');

		$raw = file_get_contents('php://input');
		$payload = $raw ? json_decode($raw, true) : null;
		if (!is_array($payload)) {
			$payload = $this->input->post();
		}
		if (!is_array($payload) || empty($payload)) {
			return $this->output->set_status_header(400)->set_output(json_encode(['ok' => false, 'error' => 'empty payload']));
		}

		$allowed_parties = ['caller', 'ai_assistant', 'both'];
		if (empty($payload['flagged_party']) || !in_array($payload['flagged_party'], $allowed_parties, true)) {
			$payload['flagged_party'] = 'caller';
		}

		$allowed_actions = ['log', 'flag', 'end_call'];
		if (empty($payload['action']) || !in_array($payload['action'], $allowed_actions, true)) {
			$payload['action'] = 'log';
		}

		$this->load->library('ai_moderator_lib');
		$log_id = $this->ai_moderator_lib->flag_voice_violation($payload);

		// Update twilio_call_logs.moderation_status to highest tier observed.
		if (!empty($payload['call_log_id']) && $this->db->table_exists('twilio_call_logs')) {
			$tier_map = ['log' => 'logged', 'flag' => 'flagged', 'end_call' => 'call_ended'];
			$new_status = $tier_map[$payload['action']] ?? 'logged';
			$rank = ['clean' => 0, 'logged' => 1, 'flagged' => 2, 'call_ended' => 3];
			$current = $this->db->select('moderation_status')->where('id', (int) $payload['call_log_id'])->get('twilio_call_logs')->row();
			$cur_rank = $current ? ($rank[$current->moderation_status] ?? 0) : 0;
			$new_rank = $rank[$new_status] ?? 0;
			if ($new_rank > $cur_rank) {
				$this->db->where('id', (int) $payload['call_log_id'])
					->update('twilio_call_logs', ['moderation_status' => $new_status]);
			}
		}

		return $this->output->set_output(json_encode([
			'ok'     => true,
			'log_id' => $log_id,
		]));
	}

	/**
	 * POST /auth/store_transfer
	 * Called by Node.js Gemini bridge when transfer_to_human tool fires.
	 * Sets is_transferred=1 and transferred_to so the system knows the human
	 * agent took over — independent of what GPT later puts in call_outcome.
	 */
	public function store_transfer()
	{
		$json            = json_decode(file_get_contents('php://input'), true);
		$call_sid        = isset($json['call_sid']) ? $json['call_sid'] : '';
		$transfer_number = isset($json['transfer_number']) ? $json['transfer_number'] : '';

		if (empty($call_sid)) {
			echo json_encode(['status' => 'error', 'message' => 'Missing call_sid']);
			return;
		}

		log_message('info', 'TRANSFER: call_sid=' . $call_sid . ' → ' . $transfer_number);

		$this->db->where('call_sid', $call_sid)->update('twilio_call_logs', [
			'is_transferred' => 1,
			'transferred_to' => $transfer_number ?: null,
			'next_action'    => 'Transferred to human agent' . ($transfer_number ? ' (' . $transfer_number . ')' : ''),
		]);

		echo json_encode(['status' => 'ok']);
	}

	/**
	 * POST /auth/store_call_transcript
	 * Called by Node.js voice bridges at call end to persist the raw transcript
	 * stream and the highest moderation tier observed during the call. Both
	 * columns are added by migration 017_voice_transcript_moderation.sql.
	 *
	 * JSON body: { call_log_id, raw_transcript_json, moderation_status }
	 */
	public function store_call_transcript()
	{
		$json = json_decode(file_get_contents('php://input'), true) ?: [];
		$call_log_id = isset($json['call_log_id']) ? (int) $json['call_log_id'] : 0;
		$raw         = isset($json['raw_transcript_json']) ? (string) $json['raw_transcript_json'] : '';
		$mod_status  = isset($json['moderation_status']) ? (string) $json['moderation_status'] : '';

		if ($call_log_id <= 0) {
			echo json_encode(['status' => 'error', 'message' => 'Missing call_log_id']);
			return;
		}
		if (!$this->db->table_exists('twilio_call_logs')) {
			echo json_encode(['status' => 'error', 'message' => 'twilio_call_logs missing']);
			return;
		}

		$update = [];
		$cols = $this->db->list_fields('twilio_call_logs');
		if ($raw !== '' && in_array('raw_transcript_json', $cols, true)) {
			if (strlen($raw) > 1500000) $raw = substr($raw, 0, 1500000);
			$update['raw_transcript_json'] = $raw;
		}
		if ($mod_status !== '' && in_array('moderation_status', $cols, true)) {
			$allowed = ['clean', 'logged', 'flagged', 'call_ended'];
			if (in_array($mod_status, $allowed, true)) {
				// Don't downgrade — only escalate.
				$cur = $this->db->select('moderation_status')->where('id', $call_log_id)->get('twilio_call_logs')->row();
				$rank = ['clean' => 0, 'logged' => 1, 'flagged' => 2, 'call_ended' => 3];
				$cur_rank = $cur ? (isset($rank[$cur->moderation_status]) ? $rank[$cur->moderation_status] : 0) : 0;
				$new_rank = isset($rank[$mod_status]) ? $rank[$mod_status] : 0;
				if ($new_rank >= $cur_rank) {
					$update['moderation_status'] = $mod_status;
				}
			}
		}

		if (!empty($update)) {
			$this->db->where('id', $call_log_id)->update('twilio_call_logs', $update);
		}

		echo json_encode(['status' => 'ok', 'updated' => array_keys($update)]);
	}

	/**
	 * POST /auth/log_api_usage
	 * Called by realtime-voice bridges at session-end to log AI-model spend.
	 * Body: JSON with user_id, source, sub_type, model, tokens, duration, etc.
	 * Returns: { ok: true, cost_usd: <float> }
	 */
	/**
	 * Shared-secret gate for server-to-server webhooks (the Node bridge-service).
	 * Verifies the X-Internal-Token header against config bridge_internal_token
	 * with a constant-time compare. Open in dev when the token is unset, matching
	 * the Node convention and Telegram_api::_internal_ok().
	 */
	private function _internal_ok()
	{
		$this->load->library('config_lib');
		$tok = $this->config_lib->getKey('bridge_internal_token');
		if (!$tok || $tok === 'invalid key') { return true; } // dev / back-compat: gate open
		$hdr = isset($_SERVER['HTTP_X_INTERNAL_TOKEN']) ? (string) $_SERVER['HTTP_X_INTERNAL_TOKEN'] : '';
		return hash_equals((string) $tok, $hdr);
	}

	public function log_api_usage()
	{
		header('Content-Type: application/json');
		// SECURITY: server-to-server only. This trusts a body-supplied user_id to
		// record usage AND to delete that user's platform-seeded API keys once a
		// quota is crossed — so without this gate an unauthenticated caller could
		// wipe any user's keys / poison their usage. Require the shared internal
		// token (same gate as Telegram_api; open in dev when the token is unset).
		if (!$this->_internal_ok()) {
			echo json_encode(['ok' => false, 'msg' => 'forbidden']);
			return;
		}
		$json = json_decode(file_get_contents('php://input'), true);
		if (!is_array($json)) {
			echo json_encode(['ok' => false, 'msg' => 'Invalid JSON']);
			return;
		}

		if (empty($json['user_id']) || empty($json['source']) || empty($json['sub_type'])) {
			echo json_encode(['ok' => false, 'msg' => 'Missing user_id/source/sub_type']);
			return;
		}

		$this->load->library('api_usage_lib');
		$cost = $this->api_usage_lib->record($json);

		// Platform-key quota cleanup: when a child of super-admin (parent_user_id=1)
		// crosses 6000 seconds of realtime voice on OpenAI/Gemini, remove the
		// platform-seeded keys so future calls fall back to "no key" until the
		// user adds their own. Only realtime_voice duration counts toward the cap.
		$source   = isset($json['source']) ? $json['source'] : '';
		$sub_type = isset($json['sub_type']) ? $json['sub_type'] : '';
		if (in_array($source, array('openai', 'gemini'), TRUE) && $sub_type === 'realtime_voice') {
			$this->_enforce_platform_key_quota((int) $json['user_id']);
		}

		echo json_encode([
			'ok'        => $cost !== FALSE,
			'cost_usd'  => is_numeric($cost) ? (float) $cost : 0,
		]);
	}

	/**
	 * Remove platform-seeded OpenAI/Gemini keys from a user once their free
	 * trial is exhausted — by EITHER 6000 seconds (100 min) of realtime voice
	 * usage OR 45 days since registration, whichever comes first. A row is
	 * "platform-seeded" if its encrypted blob still matches super-admin's
	 * blob — once the user saves their own key the blob diverges and the row
	 * is left alone.
	 */
	private function _enforce_platform_key_quota($user_id)
	{
		try {
			$user = $this->db->select('parent_user_id, created_on')
			                 ->from('users')
			                 ->where('id', $user_id)
			                 ->limit(1)->get()->row();
			if (!$user || (int) $user->parent_user_id !== 1) return;

			// Time-based expiry — 45 days from registration.
			$created_on  = (int) ($user->created_on ?? 0);
			$days_used   = $created_on > 0 ? (time() - $created_on) / 86400 : 0;
			$time_expired = ($created_on > 0 && $days_used >= 45);

			// Minutes-based expiry — 6000 seconds (100 min) of realtime voice usage.
			$row = $this->db->select_sum('duration_seconds', 'sec')
			                ->from('api_usage_log')
			                ->where('user_id', $user_id)
			                ->where('sub_type', 'realtime_voice')
			                ->where_in('source', array('openai', 'gemini'))
			                ->get()->row();
			$sec = (int) ($row->sec ?? 0);
			$mins_expired = ($sec >= 6000);

			if (!$time_expired && !$mins_expired) return;

			$super_rows = $this->db->select('meta_type, meta_data')
			                       ->from('settings')
			                       ->where('user_id', 1)
			                       ->where_in('meta_type', array('openai', 'gemini'))
			                       ->get()->result();
			foreach ($super_rows as $sr) {
				if (empty($sr->meta_data)) continue;
				$this->db->where('user_id', $user_id)
				         ->where('meta_type', $sr->meta_type)
				         ->where('meta_data', $sr->meta_data)
				         ->delete('settings');
			}
			$reason = $time_expired ? 'time' : 'minutes';
			log_message('info', 'Platform-key quota exhausted (' . $reason . '), removed seeded keys for user_id=' . $user_id . ' (sec=' . $sec . ', days=' . round($days_used, 1) . ')');
		} catch (\Throwable $e) {
			log_message('error', '_enforce_platform_key_quota failed: ' . $e->getMessage());
		}
	}

	/**
	 * Cron endpoint — runs the platform-key quota check across all child users.
	 * Catches users whose 45-day expiry passes during periods of inactivity
	 * (the per-call hook in record_api_usage only fires when usage is logged).
	 *
	 * Wire as: cron/expire-platform-keys → auth/cron_expire_platform_keys
	 */
	public function cron_expire_platform_keys()
	{
		// CLI-only or super-admin only.
		if (!$this->input->is_cli_request()) {
			$u = $this->ion_auth->user()->row();
			if (!$u || (int) $u->id !== 1) { show_error('Forbidden', 403); return; }
		}

		$users = $this->db->select('id')->from('users')->where('parent_user_id', 1)->get()->result();
		$processed = 0;
		foreach ($users as $u) {
			$this->_enforce_platform_key_quota((int) $u->id);
			$processed++;
		}
		echo 'Processed ' . $processed . ' users.' . PHP_EOL;
	}

	/**
	 * Returns the platform-key trial status for a user. Used by the settings
	 * page to render the "INCLUDED FREE / X days left / EXPIRED" badge.
	 *
	 * Output:
	 *   ['state'        => 'active' | 'expired' | 'own_key' | 'na',
	 *    'days_left'    => int,    // 0 if expired
	 *    'minutes_left' => int,    // 0 if expired
	 *    'reason'       => 'time' | 'minutes' | null]
	 *
	 * 'own_key' means the user saved their own key and is not on the trial.
	 * 'na' means the user is not a child of super-admin (e.g. super-admin
	 * themselves) and the trial concept doesn't apply.
	 */
	public function get_platform_key_status($user_id)
	{
		$default = ['state' => 'na', 'days_left' => 0, 'minutes_left' => 0, 'reason' => null];

		$user = $this->db->select('parent_user_id, created_on')
		                 ->from('users')->where('id', $user_id)
		                 ->limit(1)->get()->row();
		if (!$user || (int) $user->parent_user_id !== 1) return $default;

		// Determine whether the user is currently using the seeded key.
		$super_rows = $this->db->select('meta_type, meta_data')
		                       ->from('settings')->where('user_id', 1)
		                       ->where_in('meta_type', array('openai', 'gemini'))
		                       ->get()->result();
		$user_rows = $this->db->select('meta_type, meta_data')
		                      ->from('settings')->where('user_id', $user_id)
		                      ->where_in('meta_type', array('openai', 'gemini'))
		                      ->get()->result();

		$super_blobs = [];
		foreach ($super_rows as $sr) $super_blobs[$sr->meta_type] = $sr->meta_data;

		$has_seeded = false;
		$has_own    = false;
		foreach ($user_rows as $ur) {
			if (isset($super_blobs[$ur->meta_type]) && $super_blobs[$ur->meta_type] === $ur->meta_data) {
				$has_seeded = true;
			} else if (!empty($ur->meta_data)) {
				$has_own = true;
			}
		}

		// User saved their own key → not on the trial.
		if ($has_own && !$has_seeded) {
			return ['state' => 'own_key', 'days_left' => 0, 'minutes_left' => 0, 'reason' => null];
		}

		$created_on = (int) ($user->created_on ?? 0);
		$days_used  = $created_on > 0 ? (time() - $created_on) / 86400 : 0;
		$days_left  = max(0, (int) floor(45 - $days_used));

		$row = $this->db->select_sum('duration_seconds', 'sec')
		                ->from('api_usage_log')
		                ->where('user_id', $user_id)
		                ->where('sub_type', 'realtime_voice')
		                ->where_in('source', array('openai', 'gemini'))
		                ->get()->row();
		$sec_used     = (int) ($row->sec ?? 0);
		$minutes_left = max(0, (int) floor((6000 - $sec_used) / 60));

		// No seeded rows AND days/minutes ran out → expired.
		if (!$has_seeded) {
			$reason = ($days_left <= 0) ? 'time' : (($minutes_left <= 0) ? 'minutes' : null);
			return ['state' => 'expired', 'days_left' => 0, 'minutes_left' => 0, 'reason' => $reason];
		}

		// Seeded rows still present — trial active.
		return [
			'state'        => 'active',
			'days_left'    => $days_left,
			'minutes_left' => $minutes_left,
			'reason'       => null,
		];
	}

	/**
	 * Send the Calendly one-off scheduling link to the prospect via SMS.
	 * Used by book_appointment() in the calendly branch. No session is available
	 * here (POST from the Node.js voice bridge), so we resolve Twilio credentials
	 * directly from the funnel owner's settings row and call the Twilio SDK.
	 */
	private function _send_calendly_link_sms($user_id, $funnel_id, $to_number, $body)
	{
		try {
			$row = $this->db->where(['user_id' => $user_id, 'meta_type' => 'twilio'])->get('settings')->row();
			if (!$row || empty($row->meta_data)) {
				return ['ok' => false, 'error' => 'no_twilio_creds'];
			}
			$creds = @unserialize($this->encryption->decrypt($row->meta_data));
			if (!is_array($creds) || empty($creds['twilio_account_sid']) || empty($creds['twilio_auth_token'])) {
				return ['ok' => false, 'error' => 'invalid_twilio_creds'];
			}

			// Per-funnel from_number is primary; user-level twilio_from is the
			// fallback. Matches Sequence_lib::dispatch_sms.
			$from_number = '';
			$vc_sms = $this->db->select('from_number')->where('funnel_id', $funnel_id)
				->where('from_number IS NOT NULL AND from_number != ""', null, false)
				->get('voice_call_config')->row();
			if ($vc_sms && !empty($vc_sms->from_number)) {
				$from_number = $vc_sms->from_number;
			} elseif (!empty($creds['twilio_from'])) {
				$from_number = $creds['twilio_from'];
			}
			if (empty($from_number)) {
				return ['ok' => false, 'error' => 'no_from_number'];
			}

			require_once APPPATH . 'libraries/twilio/vendor/autoload.php';
			$client = new \Twilio\Rest\Client($creds['twilio_account_sid'], $creds['twilio_auth_token']);
			$msg = $client->messages->create($to_number, [
				'from' => $from_number,
				'body' => $body,
			]);
			if (!$msg || !$msg->sid) {
				return ['ok' => false, 'error' => 'no_sid_returned'];
			}

			// Audit row so the calendly link delivery is visible in the SMS log.
			$this->db->insert('twilio_sms_logs', [
				'user_id'     => $user_id,
				'funnel_id'   => $funnel_id,
				'message_sid' => $msg->sid,
				'direction'   => 'outbound',
				'from_number' => $from_number,
				'to_number'   => $to_number,
				'body'        => $body,
				'status'      => 'sent',
				'segments'    => $msg->numSegments ?: 1,
				'source'      => 'calendly_link',
			]);

			return ['ok' => true, 'sid' => $msg->sid];
		} catch (Exception $e) {
			log_message('error', '[Auth::_send_calendly_link_sms] ' . $e->getMessage());
			return ['ok' => false, 'error' => $e->getMessage()];
		}
	}

	/**
	 * Email fallback for the Calendly scheduling link when the prospect has no
	 * phone on file. Uses the same Mailgun library the rest of the app uses.
	 */
	private function _send_calendly_link_email($user_id, $to_email, $to_name, $link_url)
	{
		try {
			$this->load->library('mailgun');
			$first = trim(strtok((string) $to_name, ' '));
			$greet = $first !== '' ? "Hi {$first}," : 'Hi,';
			$html  = "<p>{$greet}</p>"
				   . "<p>Please tap the link below to pick a time that works for you and confirm your appointment:</p>"
				   . "<p><a href=\"" . htmlspecialchars($link_url, ENT_QUOTES) . "\">Confirm my appointment</a></p>"
				   . "<p>Or copy this URL into your browser:<br>" . htmlspecialchars($link_url, ENT_QUOTES) . "</p>";
			$res = $this->mailgun->sendMail($to_email, $first, [
				'subject' => 'Confirm your appointment',
				'message' => $html,
			]);
			if ($res === false) {
				return ['ok' => false, 'error' => 'mailgun_credentials_missing'];
			}
			$decoded = json_decode((string) $res, true);
			if (is_array($decoded) && !empty($decoded['id'])) {
				return ['ok' => true];
			}
			return ['ok' => false, 'error' => is_array($decoded) && !empty($decoded['message']) ? $decoded['message'] : 'mailgun_failed'];
		} catch (Exception $e) {
			log_message('error', '[Auth::_send_calendly_link_email] ' . $e->getMessage());
			return ['ok' => false, 'error' => $e->getMessage()];
		}
	}

}
