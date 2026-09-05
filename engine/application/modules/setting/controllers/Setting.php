<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// A2: the Agentic Affiliates settings-page actions now live in this module.
require_once APPPATH . 'modules/setting/controllers/Ic_settings_trait.php';

class Setting extends App_Controller {
    use Ic_settings_trait;

    public $table;
    public function __construct(){
        parent::__construct();
        $this->table = 'settings';
        $this->load->library('product_lib');
        $this->load->library('encryption');
        $this->load->config('responder_config');
    }
    
    /**
     * Render the setting page for API keys save
     */
    public function index()
    {
        // Settings is now a single page — the Agentic Affiliates settings UI. The old
        // engine settings page has been merged into it; anyone hitting /setting
        // lands on instantclose/settings instead.
        redirect('app/settings');
    }


    /**
     * Insert API keys
     */
    public function insertSetting()
    {
        if(isset($_POST['google_api_key']) && isset($_POST['google_api_key']))
        {
            $test = serialize($_POST);
        $data['meta_data'] = $this->encryption->encrypt($test);
            $data['meta_type'] = "google";
            $data['user_id'] = $this->user_id;
            

        }   

        if(isset($_POST['Openai_api']))
        {

            $test = serialize($_POST);
        $data['meta_data'] = $this->encryption->encrypt($test);
            $data['meta_type'] = "openai";
            $data['user_id'] = $this->user_id;
                        
        }

        if(isset($_POST['elevenlabs_api_key']))
        {
            $this->load->library('access_control_lib');
            if (!$this->access_control_lib->can_use_elevenlabs($this->user)) {
                show_error('Your plan does not include the ElevenLabs voice engine.', 403);
                return;
            }
            $test = serialize($_POST);
            $data['meta_data'] = $this->encryption->encrypt($test);
            $data['meta_type'] = "elevenlabs";
            $data['user_id'] = $this->user_id;
        }

        if(isset($_POST['twilio_account_sid'])){
            
            $test = serialize($_POST);
            $data['meta_data'] = $this->encryption->encrypt($test);
            $data['meta_type'] = "twilio";
            $data['user_id'] = $this->user_id;
        }

        if(isset($_POST['smtp_username']))
        {

            $test = serialize($_POST);
        $data['meta_data'] = $this->encryption->encrypt($test);
            $data['meta_type'] = "smtp";
            $data['user_id'] = $this->user_id;
                        
        }

        if(isset($_POST['pitch_lang']))
        {

            // print_r($_POST);
            $data['meta_data'] = serialize($_POST);
            $data['meta_type'] = "lang";
            $data['user_id'] = $this->user_id;
                        
        }

        if(isset($data))
        {
            $existing = $this->db
                ->where('user_id', $data['user_id'])
                ->where('meta_type', $data['meta_type'])
                ->get($this->table)->row();

            if ($existing) {
                $this->datamodel->updateTable(
                    $this->table,
                    ['user_id' => $data['user_id'], 'meta_type' => $data['meta_type']],
                    ['meta_data' => $data['meta_data']]
                );
            } else {
                $this->app_lib->insertTable($this->table, $data);
            }
        }
    }


    /**
     * AJAX: Send a test email using the user's saved SMTP credentials
     * POST /settings/test-smtp
     */
    public function test_smtp()
    {
        header('Content-Type: application/json');

        // Load saved SMTP creds
        $row = $this->db->where(['user_id' => $this->user_id, 'meta_type' => 'smtp'])->get('settings')->row();
        if (!$row) {
            echo json_encode(['ok' => false, 'msg' => 'No SMTP credentials saved. Please save your credentials first.']);
            return;
        }

        $creds = unserialize($this->encryption->decrypt($row->meta_data));
        if (empty($creds['smtp_host']) || empty($creds['smtp_username']) || empty($creds['smtp_password'])) {
            echo json_encode(['ok' => false, 'msg' => 'SMTP credentials incomplete. Please fill in all fields and save.']);
            return;
        }

        $to_email = $this->input->post('test_email');
        if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'msg' => 'Please enter a valid email address.']);
            return;
        }
        $from_name = !empty($creds['sender_name']) ? $creds['sender_name'] : 'SMTP Test';
        $from_email = !empty($creds['sender_email']) ? $creds['sender_email'] : $creds['smtp_username'];

        $subject = 'SMTP Test - ' . date('M j, g:i A');
        $body = '<div style="font-family:Arial,sans-serif;max-width:500px;margin:0 auto;padding:30px;">'
              . '<h2 style="color:#15803D;">SMTP Connection Successful!</h2>'
              . '<p>This test email confirms your SMTP settings are working correctly.</p>'
              . '<p style="font-size:13px;color:#666;">'
              . '<strong>Host:</strong> ' . htmlspecialchars($creds['smtp_host']) . '<br>'
              . '<strong>Port:</strong> ' . htmlspecialchars($creds['smtp_port']) . '<br>'
              . '<strong>From:</strong> ' . htmlspecialchars($from_email) . '<br>'
              . '<strong>Sent at:</strong> ' . date('Y-m-d H:i:s T')
              . '</p></div>';

        require_once('./application/libraries/vendor/autoload.php');

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $creds['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $creds['smtp_username'];
            $mail->Password   = $creds['smtp_password'];

            if ($creds['smtp_port'] == 587) {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }
            $mail->Port = $creds['smtp_port'];

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($from_email, $from_name);
            $mail->addAddress($to_email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();

            echo json_encode(['ok' => true, 'to' => $to_email]);
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $mail->ErrorInfo ?: $e->getMessage()]);
        }
    }

    /**
     * AJAX: Test an API key by making a lightweight call
     * POST /setting/test_api_key
     */
    public function test_api_key()
    {
        header('Content-Type: application/json');
        $type = $this->input->post('type');

        switch ($type) {
            case 'openai':
                $row = $this->db->where(['user_id' => $this->user_id, 'meta_type' => 'openai'])->get('settings')->row();
                if (!$row) { echo json_encode(['ok' => false, 'msg' => 'No OpenAI key saved.']); return; }
                $creds = unserialize($this->encryption->decrypt($row->meta_data));
                $key = isset($creds['Openai_api']) ? $creds['Openai_api'] : '';
                if (empty($key)) { echo json_encode(['ok' => false, 'msg' => 'API key is empty.']); return; }

                // Make a minimal completion call to verify key + credits
                $payload = json_encode([
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => 'Hi']],
                    'max_tokens' => 5
                ]);
                $ch = curl_init('https://api.openai.com/v1/chat/completions');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 20,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $key,
                        'Content-Type: application/json'
                    ]
                ]);
                $resp = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $this->load->library('api_usage_lib');
                $resp_arr = json_decode($resp, true);
                $tokens_in = isset($resp_arr['usage']['prompt_tokens']) ? (int) $resp_arr['usage']['prompt_tokens'] : 1;
                $tokens_out = isset($resp_arr['usage']['completion_tokens']) ? (int) $resp_arr['usage']['completion_tokens'] : 1;
                $this->api_usage_lib->record([
                    'user_id'       => $this->user_id,
                    'source'        => 'openai',
                    'sub_type'      => 'text',
                    'model'         => 'gpt-4o-mini',
                    'tokens_in'     => $tokens_in,
                    'tokens_out'    => $tokens_out,
                    'api_key_last4' => substr($key, -4),
                    'status'        => $code === 200 ? 'success' : 'error',
                    'error_message' => $code === 200 ? NULL : ('HTTP ' . $code),
                    'request_meta'  => ['hook' => 'key_test'],
                ]);

                if ($code === 200) {
                    echo json_encode(['ok' => true, 'msg' => 'OpenAI key is valid with active credits']);
                } elseif ($code === 429) {
                    $body = json_decode($resp, true);
                    $msg = isset($body['error']['message']) ? $body['error']['message'] : '';
                    if (stripos($msg, 'quota') !== false || stripos($msg, 'billing') !== false) {
                        echo json_encode(['ok' => false, 'msg' => 'Key is valid but has no credits. Please add billing at platform.openai.com']);
                    } else {
                        echo json_encode(['ok' => true, 'msg' => 'Key is valid (rate limited, try again shortly)']);
                    }
                } else {
                    $body = json_decode($resp, true);
                    $msg = isset($body['error']['message']) ? $body['error']['message'] : 'Invalid key (HTTP ' . $code . ')';
                    echo json_encode(['ok' => false, 'msg' => $msg]);
                }
                break;

            case 'gemini':
                $row = $this->db->where(['user_id' => $this->user_id, 'meta_type' => 'gemini'])->get('settings')->row();
                if (!$row) { echo json_encode(['ok' => false, 'msg' => 'No Gemini key saved.']); return; }
                $meta = unserialize($this->encryption->decrypt($row->meta_data));
                $key = isset($meta['gemini_api_key']) ? $meta['gemini_api_key'] : '';
                if (empty($key)) { echo json_encode(['ok' => false, 'msg' => 'API key is empty.']); return; }

                $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models?key=' . urlencode($key));
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15
                ]);
                $resp = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $this->load->library('api_usage_lib');
                $this->api_usage_lib->record([
                    'user_id'       => $this->user_id,
                    'source'        => 'gemini',
                    'sub_type'      => 'text',
                    'model'         => 'gemini-2.5-flash',
                    'tokens_in'     => 1,
                    'tokens_out'    => 0,
                    'api_key_last4' => substr($key, -4),
                    'status'        => $code === 200 ? 'success' : 'error',
                    'error_message' => $code === 200 ? NULL : ('HTTP ' . $code),
                    'request_meta'  => ['hook' => 'key_test'],
                ]);

                if ($code === 200) {
                    echo json_encode(['ok' => true, 'msg' => 'Gemini key is valid']);
                } else {
                    $body = json_decode($resp, true);
                    $msg = isset($body['error']['message']) ? $body['error']['message'] : 'Invalid key (HTTP ' . $code . ')';
                    echo json_encode(['ok' => false, 'msg' => $msg]);
                }
                break;

            case 'elevenlabs':
                $row = $this->db->where(['user_id' => $this->user_id, 'meta_type' => 'elevenlabs'])->get('settings')->row();
                if (!$row) { echo json_encode(['ok' => false, 'msg' => 'No ElevenLabs key saved.']); return; }
                $creds = unserialize($this->encryption->decrypt($row->meta_data));
                $key = isset($creds['elevenlabs_api_key']) ? $creds['elevenlabs_api_key'] : '';
                if (empty($key)) { echo json_encode(['ok' => false, 'msg' => 'API key is empty.']); return; }

                // Test against /v1/voices (the endpoint the voice picker actually
                // uses, needing only the "Voices: Read" scope) rather than /v1/user,
                // which requires the extra "User" scope a least-privilege key won't
                // have — a valid ElevenAgents/Voices key was reporting "invalid".
                $ch = curl_init('https://api.elevenlabs.io/v1/voices');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_HTTPHEADER => ['xi-api-key: ' . $key]
                ]);
                $resp = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($code === 200) {
                    $j = json_decode($resp, true);
                    $n = (is_array($j) && !empty($j['voices']) && is_array($j['voices'])) ? count($j['voices']) : 0;
                    echo json_encode(['ok' => true, 'msg' => 'ElevenLabs key is valid (' . $n . ' voice' . ($n === 1 ? '' : 's') . ' available)']);
                } elseif ($code === 401) {
                    echo json_encode(['ok' => false, 'msg' => 'Key rejected — check it has the "Voices: Read" and "ElevenAgents" scopes (HTTP 401).']);
                } else {
                    echo json_encode(['ok' => false, 'msg' => 'Could not verify key (HTTP ' . $code . ').']);
                }
                break;

            case 'twilio':
                $row = $this->db->where(['user_id' => $this->user_id, 'meta_type' => 'twilio'])->get('settings')->row();
                if (!$row) { echo json_encode(['ok' => false, 'msg' => 'No Twilio credentials saved.']); return; }
                $creds = unserialize($this->encryption->decrypt($row->meta_data));
                $sid = isset($creds['twilio_account_sid']) ? $creds['twilio_account_sid'] : '';
                $token = isset($creds['twilio_auth_token']) ? $creds['twilio_auth_token'] : '';
                if (empty($sid) || empty($token)) { echo json_encode(['ok' => false, 'msg' => 'SID or Token is empty.']); return; }

                $ch = curl_init('https://api.twilio.com/2010-04-01/Accounts/' . $sid . '.json');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_USERPWD => $sid . ':' . $token
                ]);
                $resp = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($code === 200) {
                    $acct = json_decode($resp, true);
                    $name = isset($acct['friendly_name']) ? $acct['friendly_name'] : '';
                    echo json_encode(['ok' => true, 'msg' => 'Twilio credentials valid' . ($name ? ' (' . $name . ')' : '')]);
                } else {
                    echo json_encode(['ok' => false, 'msg' => 'Invalid credentials (HTTP ' . $code . ')']);
                }
                break;

            case 'google':
                $row = $this->db->where(['user_id' => $this->user_id, 'meta_type' => 'google'])->get('settings')->row();
                if (!$row) { echo json_encode(['ok' => false, 'msg' => 'No Google API key saved.']); return; }
                $creds = unserialize($this->encryption->decrypt($row->meta_data));
                $key = isset($creds['google_api_key']) ? $creds['google_api_key'] : '';
                if (empty($key)) { echo json_encode(['ok' => false, 'msg' => 'API key is empty.']); return; }

                $url = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json?input=dentist&inputtype=textquery&key=' . urlencode($key);
                $ch = curl_init($url);
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
                $resp = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($code === 200) {
                    $body = json_decode($resp, true);
                    $status = isset($body['status']) ? $body['status'] : '';
                    if ($status === 'OK' || $status === 'ZERO_RESULTS') {
                        echo json_encode(['ok' => true, 'msg' => 'Google Places key is valid']);
                    } elseif ($status === 'REQUEST_DENIED') {
                        $err = isset($body['error_message']) ? $body['error_message'] : 'Request denied — check key restrictions';
                        echo json_encode(['ok' => false, 'msg' => $err]);
                    } else {
                        echo json_encode(['ok' => false, 'msg' => 'Unexpected status: ' . $status]);
                    }
                } else {
                    echo json_encode(['ok' => false, 'msg' => 'Invalid key (HTTP ' . $code . ')']);
                }
                break;

            default:
                echo json_encode(['ok' => false, 'msg' => 'Unknown API type.']);
        }
    }

    /**
     * AJAX: Fetch available phone numbers from Twilio account
     * POST /settings/fetch-twilio-numbers
     */
    public function fetch_twilio_numbers()
    {
        $sid = $this->input->post('sid');
        $token = $this->input->post('token');

        if (empty($sid) || empty($token)) {
            echo json_encode(['ok' => false, 'msg' => 'SID and Token required']);
            return;
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/IncomingPhoneNumbers.json?PageSize=50";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$sid}:{$token}");
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            echo json_encode(['ok' => false, 'msg' => 'Twilio returned ' . $http_code . '. Check your credentials.']);
            return;
        }

        $data = json_decode($response, true);
        $numbers = [];
        if (!empty($data['incoming_phone_numbers'])) {
            foreach ($data['incoming_phone_numbers'] as $num) {
                $numbers[] = [
                    'number' => $num['phone_number'],
                    'friendly' => $num['friendly_name'],
                    'capabilities' => [
                        'voice' => !empty($num['capabilities']['voice']),
                        'sms' => !empty($num['capabilities']['sms']),
                    ],
                ];
            }
        }

        echo json_encode(['ok' => true, 'numbers' => $numbers]);
    }

    /**
     * GET /settings/get-twilio-numbers
     * Fetch Twilio numbers using saved credentials (for wizard, no SID/token POST needed)
     */
    public function get_twilio_numbers()
    {
        $row = $this->db->where(['user_id' => $this->user_id, 'meta_type' => 'twilio'])->get('settings')->row();
        if (!$row) {
            echo json_encode(['ok' => false, 'msg' => 'No Twilio credentials saved. Please add them in Settings first.']);
            return;
        }

        $this->load->library('encryption');
        $creds = unserialize($this->encryption->decrypt($row->meta_data));
        $sid   = isset($creds['twilio_account_sid']) ? $creds['twilio_account_sid'] : '';
        $token = isset($creds['twilio_auth_token'])  ? $creds['twilio_auth_token']  : '';

        if (empty($sid) || empty($token)) {
            echo json_encode(['ok' => false, 'msg' => 'Twilio SID or Token missing. Update in Settings.']);
            return;
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/IncomingPhoneNumbers.json?PageSize=50";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$sid}:{$token}");
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            echo json_encode(['ok' => false, 'msg' => 'Twilio returned ' . $http_code]);
            return;
        }

        $data = json_decode($response, true);
        $numbers = [];
        if (!empty($data['incoming_phone_numbers'])) {
            foreach ($data['incoming_phone_numbers'] as $num) {
                $numbers[] = [
                    'number'   => $num['phone_number'],
                    'friendly' => $num['friendly_name'],
                    'capabilities' => [
                        'voice' => !empty($num['capabilities']['voice']),
                        'sms'   => !empty($num['capabilities']['sms']),
                    ],
                ];
            }
        }

        // Also include the user's saved default
        $default = $this->db->where(['user_id' => $this->user_id, 'meta_type' => 'default_from_number'])->get('settings')->row();
        echo json_encode(['ok' => true, 'numbers' => $numbers, 'default' => !empty($default) ? $default->meta_data : '']);
    }

    /**
     * AJAX: Save default Twilio caller ID number
     * POST /settings/save-default-number
     */
    public function save_default_number()
    {
        $number = $this->input->post('number');
        if (empty($number)) {
            echo json_encode(['ok' => false, 'msg' => 'Number required']);
            return;
        }

        // Persist the default number in settings table — this is the
        // canonical source that survives even if voice_call_config and
        // twilio_call_services are empty (new account, no funnels yet).
        $existing = $this->db->where(['user_id' => $this->user_id, 'meta_type' => 'default_from_number'])->get('settings')->row();
        if ($existing) {
            $this->db->where('id', $existing->id)->update('settings', ['meta_data' => $number]);
        } else {
            $this->db->insert('settings', ['user_id' => $this->user_id, 'meta_type' => 'default_from_number', 'meta_data' => $number]);
        }

        // Only fill voice_call_config rows that have NO from_number set
        // Never overwrite per-funnel numbers that were explicitly chosen
        $this->db->where('user_id', $this->user_id);
        $this->db->group_start();
            $this->db->where('from_number IS NULL', null, false);
            $this->db->or_where('from_number', '');
        $this->db->group_end();
        $this->db->update('voice_call_config', ['from_number' => $number]);

        echo json_encode(['ok' => true]);
    }

    /**
     * AJAX: Toggle DNC / Spam Score check per user.
     * POST /settings/save-dnc-toggle   body: enabled=1|0
     *
     * On enable, triggers Nomorobo add-on install on the user's Twilio account
     * (idempotent — treats HTTP 409 as success). On failure, the flag is NOT
     * persisted so the user can retry.
     */
    public function save_dnc_toggle()
    {
        $enabled = (int) $this->input->post('enabled') === 1 ? 1 : 0;

        $existing = $this->db->where(['user_id' => $this->user_id, 'meta_type' => 'dnc_check_enabled'])
                             ->get('settings')->row();

        if (!$enabled) {
            // Disable is always allowed — just flip the flag off
            if ($existing) {
                $this->db->where('id', $existing->id)->update('settings', ['meta_data' => '0']);
            } else {
                $this->db->insert('settings', ['user_id' => $this->user_id, 'meta_type' => 'dnc_check_enabled', 'meta_data' => '0']);
            }
            echo json_encode(['ok' => true, 'msg' => 'DNC check disabled.']);
            return;
        }

        // Enable path — try to install Nomorobo first. If install fails, don't
        // persist the flag so the UI reverts and the user sees the error.
        $this->load->library('Compliance_lib');

        // If already installed, just flip the flag on
        $check = $this->compliance_lib->is_nomorobo_installed($this->user_id);
        if (!empty($check['installed'])) {
            $this->_persist_dnc_flag($existing, 1);
            echo json_encode(['ok' => true, 'msg' => 'DNC check enabled. (Nomorobo add-on already installed.)']);
            return;
        }

        $install = $this->compliance_lib->install_nomorobo_addon($this->user_id);
        $http    = isset($install['http']) ? (int) $install['http'] : 0;
        $ok      = !empty($install['ok']) || $http === 409;

        if (!$ok) {
            $reason = $install['reason'] ?? 'Install failed.';
            // Common causes: trial account, no billing, invalid creds
            if (stripos($reason, 'trial') !== false || stripos($reason, 'upgrade') !== false) {
                $reason .= ' Upgrade your Twilio account to enable Marketplace add-ons.';
            }
            echo json_encode(['ok' => false, 'msg' => $reason, 'http' => $http]);
            return;
        }

        $this->_persist_dnc_flag($existing, 1);
        echo json_encode(['ok' => true, 'msg' => 'Nomorobo add-on installed. DNC check enabled.']);
    }

    private function _persist_dnc_flag($existing, $value)
    {
        if ($existing) {
            $this->db->where('id', $existing->id)->update('settings', ['meta_data' => (string) $value]);
        } else {
            $this->db->insert('settings', [
                'user_id'   => $this->user_id,
                'meta_type' => 'dnc_check_enabled',
                'meta_data' => (string) $value,
            ]);
        }
    }

    /**
     * Update the API Keys
     */
    public function updateSetting()
    {
        $test = serialize($_POST);
        $data['meta_data'] = $this->encryption->encrypt($test);

        if(isset($_POST['google_api_key']) && isset($_POST['google_api_key'])) 
            $where = array('user_id' => $this->user_id,'meta_type'=>'google');

        if(isset($_POST['Openai_api']))
            $where = array('user_id' => $this->user_id,'meta_type'=>'openai');

        if(isset($_POST['elevenlabs_api_key'])) {
            $this->load->library('access_control_lib');
            if (!$this->access_control_lib->can_use_elevenlabs($this->user)) {
                show_error('Your plan does not include the ElevenLabs voice engine.', 403);
                return;
            }
            $where = array('user_id' => $this->user_id,'meta_type'=>'elevenlabs');
        }

        if(isset($_POST['smtp_username']))
            $where = array('user_id' => $this->user_id,'meta_type'=>'smtp');
        
        if(isset($_POST['pitch_lang']))
            $where = array('user_id' => $this->user_id,'meta_type'=>'lang');

        if(isset($_POST['twilio_account_sid']))
            $where = array('user_id' => $this->user_id,'meta_type'=>'twilio');

        if(isset($data))
            $this->app_lib->updateTable($this->table,$where,$data);
        
    }

        //open autoresponder innerpage
    public function addresponder($type){

        $user_id = $this->user_id;
        if($type=='getresponse'){
            $rtype=1;
        }
        if($type=='activecampaign'){
            $rtype=2;
        }
        if($type=='mailchimp'){
            $rtype=3;
        }
        if($type=='icontact'){
            $rtype=5;
        }
        if($type=='convertkit'){
            $rtype=7;
        }
        $this->data['responderData']=$this->Datamodel->fetchTableData('autoresponder',array('user_id'=>$user_id,'respondertype'=>$rtype,'status'=>1));
        $this->data["ActiveTab"]='settings';
        $this->data['PageTitle']=$type.' Page';
        $this->data['type']=$type;
        $this->app_lib->_render_page('autoresponder',$this->data);
        
    }

    // Save AutoResponder Settings
    public function saveresponder(){

        // print_r($_POST); die;
        $user_id = $this->user_id;
        $data=array();
        // echo $_REQUEST['connect'];exit;
        if(isset($_REQUEST['type'])){
            $type=$_REQUEST['type'];
            $connect=$_REQUEST['connect'];
            if($connect=='0'){
                $status='0';
            }else{
                $status='1';
            }
            if($_REQUEST['type']=='getresponse'){
                $apikey=$this->encryption->encrypt($_REQUEST['apikey']);
                $rtype=1;
                $data=array('user_id'=>$user_id,'respondertype'=>$rtype,'param1'=>$apikey,'status'=>$status);
            }
            if($_REQUEST['type']=='mailchimp'){
                $apikey=$this->encryption->encrypt($_REQUEST['apikey']);
                $rtype=3;
                $data=array('user_id'=>$user_id,'respondertype'=>$rtype,'param1'=>$apikey,'status'=>$status);
            }
            if($_REQUEST['type']=='icontact'){
                $apiuser=$this->encryption->encrypt($_REQUEST['apiuser']);
                $apipass=$this->encryption->encrypt($_REQUEST['apipass']);
                $appid=$this->encryption->encrypt($_REQUEST['appid']);
                $rtype=5;
                $data=array('user_id'=>$user_id,'respondertype'=>$rtype,'param1'=>$appid,'param2'=>$apiuser,'param3'=>$apipass,'status'=>$status);
            }
            if($_REQUEST['type']=='activecampaign'){
                $appkey=$this->encryption->encrypt($_REQUEST['apikey']);
                $appurl=$this->encryption->encrypt($_REQUEST['appurl']);
                $rtype=2;
                $data=array('user_id'=>$user_id,'respondertype'=>$rtype,'param1'=>$appkey,'param2'=>$appurl,'status'=>$status);
            }
            if($_REQUEST['type']=='convertkit'){
                $appkey=$this->encryption->encrypt($_REQUEST['apikey']);
                $rtype=7;
                $data=array('user_id'=>$user_id,'respondertype'=>$rtype,'param1'=>$appkey,'status'=>$status);
            }
            $where = array('user_id'=>$user_id,'respondertype'=>$rtype);
            $RT=$this->Datamodel->checkExit('autoresponder',$where);
            if($RT > 0 ){
                $this->Datamodel->updateTable('autoresponder',$where,$data);
            }else{
                $this->Datamodel->insertTable('autoresponder',$data);
            }
            $this->data['responderData']=$this->Datamodel->fetchTableData('autoresponder',array('user_id'=>$user_id,'respondertype'=>$rtype,'status'=>1));
            $this->data["ActiveTab"]='settings';
            $this->data['PageTitle']=$type.' Page';
            $this->data['type']=$type;
            // $this->app_lib->_render_page('setting',$this->data);

			// $this->load->view('pages/autoresponder/'.$type,$this->data);
            // redirect('setting');
            redirect('addresponder/'.$type);
        } 
    }

    

    

    //AutoResponder Function
	public function getAutoResponder(){
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$rtype=$_REQUEST['rtype'];
		$data1 = $this->Datamodel->fetchTableData('autoresponder',array('user_id'=>$user_id,'respondertype'=>$rtype));
		if($rtype==6){
			$p1 = $data1[0]['Data']->param1;
			$param1 = $this->encryption->decrypt($p1);
			$param1=json_decode($param1,true);
			$data['param1'] = $param1['organizer_key'];
			$data['param2'] = $param1['access_token'];
			$data['param3'] = $param1['refresh_token'];
			$data['param4'] = $user_id;
		}else if($rtype==4){
			$p1 = $data1[0]['Data']->param1;
			$param1 = $this->encryption->decrypt($p1);
			$param1=json_decode($param1,true);
			$data['param1'] = $param1['refresh_token'];
			$data['param2'] = $param1['access_token'];
			$data['param3']= $user_id;
		}else{

			// print_r($data1[0]['Data']);
			$p1 = $data1[0]['Data']->param1;
			$param1 = $this->encryption->decrypt($p1);
			
			$p2 = $data1[0]['Data']->param2;
			$param2 = $this->encryption->decrypt($p2);

			$p3 = $data1[0]['Data']->param3;
			$param3 = $this->encryption->decrypt($p3);

			$data['param1'] = $param1;
			$data['param2'] = $param2;
			$data['param3']= $param3;
		}
		
		echo json_encode($data);
		die;	
	}

    public function encryptSettingsData()
    {
        $settingsData = $this->Datamodel->fetchAll('', '', 'settings');
        foreach ($settingsData as $data) {
            $metaData = $data['Data']->meta_data;
            
        if ($data['Data']->meta_type !== 'whitelabel' || $data['Data']->meta_type !== 'lang') {
            // Check if the data is already encrypted
            if (!$data['Data']->is_encrypted) {
                $encryptedData = $this->encryption->encrypt($metaData);
                
                $updateData = array(
                    'meta_data' => $encryptedData,
                    'is_encrypted' => 1  // Update the is_encrypted field
                );
                
                $this->Datamodel->updateTable('settings', array('id' => $data['Data']->id), $updateData);
            }
        }
     }
    
    }

    /**
     * Save Gemini API Key to user's settings table
     */
    public function saveGeminiKey()
    {
        $key = $this->input->post('gemini_api_key');
        if (empty($key)) {
            echo json_encode(['status' => 0, 'message' => 'API key is required']);
            return;
        }

        $meta_data = serialize(array('gemini_api_key' => trim($key)));
        $encrypted = $this->encryption->encrypt($meta_data);

        $existing = $this->Datamodel->fetchTableData('settings', array('user_id' => $this->user_id, 'meta_type' => 'gemini'));
        if (!empty($existing)) {
            $this->Datamodel->updateTable('settings', array('user_id' => $this->user_id, 'meta_type' => 'gemini'), array('meta_data' => $encrypted));
        } else {
            $this->Datamodel->insertTable('settings', array('user_id' => $this->user_id, 'meta_type' => 'gemini', 'meta_data' => $encrypted));
        }

        echo json_encode(['status' => 1, 'message' => 'Gemini API Key saved successfully']);
    }

    /**
     * Returns the platform-key trial status for the current user.
     * Mirror of Auth::get_platform_key_status — kept here to avoid
     * cross-module controller calls. See Auth.php for the canonical version
     * (the cron and quota-enforcement logic live there).
     *
     * Output:
     *   ['state' => 'active'|'expired'|'own_key'|'na',
     *    'days_left' => int, 'minutes_left' => int,
     *    'reason' => 'time'|'minutes'|null]
     */
    private function _get_platform_key_status($user_id)
    {
        $default = ['state' => 'na', 'days_left' => 0, 'minutes_left' => 0, 'reason' => null];

        $user = $this->db->select('parent_user_id, created_on')
                         ->from('users')->where('id', $user_id)
                         ->limit(1)->get()->row();
        if (!$user || (int) $user->parent_user_id !== 1) return $default;

        $super_rows = $this->db->select('meta_type, meta_data')
                               ->from('settings')->where('user_id', 1)
                               ->where_in('meta_type', array('openai', 'gemini'))
                               ->get()->result();
        $user_rows  = $this->db->select('meta_type, meta_data')
                               ->from('settings')->where('user_id', $user_id)
                               ->where_in('meta_type', array('openai', 'gemini'))
                               ->get()->result();

        $super_blobs = [];
        foreach ($super_rows as $sr) $super_blobs[$sr->meta_type] = $sr->meta_data;

        $has_seeded = false; $has_own = false;
        foreach ($user_rows as $ur) {
            if (isset($super_blobs[$ur->meta_type]) && $super_blobs[$ur->meta_type] === $ur->meta_data) {
                $has_seeded = true;
            } else if (!empty($ur->meta_data)) {
                $has_own = true;
            }
        }

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

        if (!$has_seeded) {
            $reason = ($days_left <= 0) ? 'time' : (($minutes_left <= 0) ? 'minutes' : null);
            return ['state' => 'expired', 'days_left' => 0, 'minutes_left' => 0, 'reason' => $reason];
        }

        return [
            'state'        => 'active',
            'days_left'    => $days_left,
            'minutes_left' => $minutes_left,
            'reason'       => null,
        ];
    }

    /* =====================================================================
     * Agentic Affiliates Settings — delegators
     * ---------------------------------------------------------------------
     * The settings page + its action endpoints are SERVED under setting/* URLs
     * but the working implementation (the full IC dark shell: layout.php +
     * _ic_sidebar + the 6 private render/auth/whitelabel helpers + ~1300 lines
     * of interlocked logic) stays in the instantclose controller. These thin
     * delegators load the instantclose module via HMVC and call the existing
     * public method, so the page renders and the save actions behave EXACTLY
     * as before. The legacy instantclose/* routes still resolve too, so view
     * links that point at the old URLs keep working.
     * ===================================================================== */

    private function _ic()
    {
        $this->load->module('instantclose/instantclose');
        return $this->instantclose;
    }

    // settings / save_settings / save_smtp / save_esp / disconnect_esp / toggle_dnc /
    // dnc_add / dnc_remove / fetch_caller_ids / test_key / twilio_balance now come
    // from Ic_settings_trait (A2) — the bodies live in this module.
    // NOTE: save_default_number is intentionally NOT in the trait — this controller
    // already has its own save_default_number() (the engine wizard's
    // settings/save-default-number endpoint); the IC settings page's
    // save-default-number action routes straight to the instantclose controller in
    // routes.php to avoid a redeclare collision.
    // api_keys/* stay delegated — they're the separate public Lead-Submission API,
    // still owned by the instantclose controller.
    public function api_keys()            { return $this->_ic()->api_keys(); }
    public function api_key_create()      { return $this->_ic()->api_key_create(); }
    public function api_key_toggle($id = 0) { return $this->_ic()->api_key_toggle($id); }
    public function api_key_revoke($id = 0) { return $this->_ic()->api_key_revoke($id); }

}
