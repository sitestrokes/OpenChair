<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ic_settings_trait — the Agentic Affiliates settings-page actions, moved here (A2) so the
 * `setting` module owns the bodies. Both Setting and Instantclose `use` this trait.
 * The shared helpers are included as copies; Instantclose also defines its own (used
 * 15-31x elsewhere), and PHP class-method precedence means IC's own win there — so this
 * move has zero blast radius on the rest of the IC controller.
 */
trait Ic_settings_trait
{


    public function fetch_caller_ids()
    {
        $this->_json_headers();
        $creds = $this->_get_twilio_creds((int) $this->user_id);
        if (!$creds) { echo json_encode(['ok' => false, 'msg' => 'No Twilio key on file.', 'numbers' => []]); return; }

        list($json) = $this->_twilio_get($creds, 'IncomingPhoneNumbers.json', 'PageSize=50');
        if (!$json || !isset($json['incoming_phone_numbers'])) {
            echo json_encode(['ok' => false, 'msg' => 'Could not reach Twilio.', 'numbers' => []]); return;
        }
        $numbers = [];
        foreach ($json['incoming_phone_numbers'] as $n) {
            $cap = isset($n['capabilities']) && is_array($n['capabilities']) ? $n['capabilities'] : [];
            $numbers[] = [
                'number'   => isset($n['phone_number']) ? (string) $n['phone_number'] : '',
                'friendly' => isset($n['friendly_name']) ? (string) $n['friendly_name'] : '',
                'voice'    => !empty($cap['voice']),
                'sms'      => !empty($cap['sms']),
            ];
        }
        // Saved default caller-ID is the funnel-level from_number; the picker
        // preselects it. Here we just hand back the roster.
        echo json_encode(['ok' => true, 'numbers' => $numbers]);
    }


    public function twilio_balance()
    {
        $this->_json_headers();
        $creds = $this->_get_twilio_creds((int) $this->user_id);
        if (!$creds) { echo json_encode(['ok' => false]); return; }

        list($json) = $this->_twilio_get($creds, 'Balance.json');
        if (!$json || !isset($json['balance'])) { echo json_encode(['ok' => false]); return; }
        echo json_encode([
            'ok'       => true,
            'balance'  => (string) $json['balance'],
            'currency' => isset($json['currency']) ? (string) $json['currency'] : 'USD',
        ]);
    }


    public function save_esp()
    {
        $this->_json_headers();
        $uid  = (int) $this->user_id;
        $type = strtolower(trim((string) $this->input->post('type', true)));
        $meta = $this->_esp_meta();
        if (!isset($meta[$type])) { echo json_encode(['ok' => false, 'msg' => 'Unknown ESP.']); return; }

        $apikey = trim((string) $this->input->post('apikey', true));
        $appurl = trim((string) $this->input->post('appurl', true));
        if ($apikey === '') { echo json_encode(['ok' => false, 'msg' => 'Paste your API key first.']); return; }
        if ($meta[$type]['needs_url']) {
            if ($appurl === '') { echo json_encode(['ok' => false, 'msg' => 'ActiveCampaign needs your account API URL.']); return; }
            if (!$this->_is_ac_url($appurl)) {
                echo json_encode(['ok' => false, 'msg' => 'That doesn’t look like an ActiveCampaign API URL (https://you.api-us1.com).']); return;
            }
        }

        $this->load->library('encryption');
        $rtype = (int) $meta[$type]['rtype'];
        $data  = [
            'user_id'       => $uid,
            'respondertype' => $rtype,
            'param1'        => $this->encryption->encrypt($apikey),
            'status'        => 1,
        ];
        if ($meta[$type]['needs_url']) { $data['param2'] = $this->encryption->encrypt($appurl); }

        // v5.1 cap — FE connects 1 autoresponder; OTO1/Bundle/Fastpass unlimited
        // (full-access too). Block connecting a NEW ESP once other active
        // connections already fill the cap; updating the current one is fine.
        $ar_max = $this->access_control_lib->max_autoresponders($this->user);
        if ($ar_max !== -1) {
            $active_others = (int) $this->db->where('user_id', $uid)->where('status', 1)
                ->where('respondertype !=', $rtype)->count_all_results('autoresponder');
            if ($active_others >= $ar_max) {
                echo json_encode(['ok' => false, 'locked' => true, 'msg' => 'Your plan connects ' . $ar_max . ' autoresponder' . ($ar_max === 1 ? '' : 's') . '. Disconnect the current one, or upgrade to the Automated Funnel Engine (OTO3) to connect more.']);
                return;
            }
        }

        $exists = $this->db->where('user_id', $uid)->where('respondertype', $rtype)
                           ->count_all_results('autoresponder');
        if ($exists > 0) {
            $this->db->where('user_id', $uid)->where('respondertype', $rtype)->update('autoresponder', $data);
        } else {
            $this->db->insert('autoresponder', $data);
        }
        echo json_encode(['ok' => true, 'connected' => $type, 'msg' => $meta[$type]['label'] . ' connected.']);
    }


    public function disconnect_esp()
    {
        $this->_json_headers();
        $uid  = (int) $this->user_id;
        $type = strtolower(trim((string) $this->input->post('type', true)));
        $meta = $this->_esp_meta();
        if (!isset($meta[$type])) { echo json_encode(['ok' => false, 'msg' => 'Unknown ESP.']); return; }
        $this->db->where('user_id', $uid)->where('respondertype', (int) $meta[$type]['rtype'])
                 ->update('autoresponder', ['status' => 0]);
        echo json_encode(['ok' => true, 'msg' => $meta[$type]['label'] . ' disconnected.']);
    }


    public function save_smtp()
    {
        $this->_json_headers();
        $uid = (int) $this->user_id;
        $this->load->library('encryption');

        $existing = $this->db->where('user_id', $uid)->where('meta_type', 'smtp')->get('settings')->row();
        $prev = ($existing && $existing->meta_data !== '') ? @unserialize($this->encryption->decrypt($existing->meta_data)) : [];
        if (!is_array($prev)) { $prev = []; }

        $pass = (string) $this->input->post('smtp_password', true);
        if ($pass === '' && isset($prev['smtp_password'])) { $pass = (string) $prev['smtp_password']; } // keep prior on blank
        $creds = [
            'smtp_host'     => trim((string) $this->input->post('smtp_host', true)),
            'smtp_port'     => (string) (int) $this->input->post('smtp_port', true),
            'smtp_username' => trim((string) $this->input->post('smtp_username', true)),
            'smtp_password' => $pass,
            'sender_name'   => trim((string) $this->input->post('sender_name', true)),
            'sender_email'  => trim((string) $this->input->post('sender_email', true)),
        ];
        if ($creds['smtp_host'] === '' || $creds['smtp_username'] === '' || $creds['smtp_password'] === '') {
            echo json_encode(['ok' => false, 'msg' => 'Host, username and password are all required.']); return;
        }
        $meta_data = $this->encryption->encrypt(serialize($creds));
        if ($existing) {
            $this->db->where('id', $existing->id)->update('settings', ['meta_data' => $meta_data]);
        } else {
            $this->db->insert('settings', ['user_id' => $uid, 'meta_type' => 'smtp', 'meta_data' => $meta_data]);
        }
        echo json_encode(['ok' => true, 'msg' => 'SMTP saved. Send a test to confirm it delivers.']);
    }


    public function test_key()
    {
        $this->_json_headers();
        $uid  = (int) $this->user_id;
        $type = strtolower(trim((string) $this->input->post('type', true)));
        if (!in_array($type, ['openai', 'gemini', 'places', 'twilio', 'elevenlabs'], true)) {
            echo json_encode(['ok' => false, 'msg' => 'Unknown key type.']); return;
        }

        if ($type === 'twilio') {
            $creds = $this->_get_twilio_creds($uid);
            if (!$creds) { echo json_encode(['ok' => false, 'msg' => 'No Twilio SID/token saved.']); return; }
            list($json, $code) = $this->_twilio_get($creds, '');
            if ($json && isset($json['sid'])) {
                echo json_encode(['ok' => true, 'msg' => 'Twilio credentials valid' . (isset($json['friendly_name']) ? ' (' . $json['friendly_name'] . ')' : '') . '.']);
            } elseif ($code === 401 || $code === 403) {
                echo json_encode(['ok' => false, 'msg' => 'Twilio rejected the credentials — check your Account SID + Auth Token.']);
            } else {
                echo json_encode(['ok' => false, 'msg' => 'Could not verify Twilio credentials (HTTP ' . $code . '). Try again in a moment.']);
            }
            return;
        }

        // openai / gemini / places / elevenlabs — read IC's encrypted serialize(['key'=>..]) row.
        $row = $this->db->where('user_id', $uid)->where('meta_type', $type)->get('settings')->row();
        $data = ($row && $row->meta_data !== '') ? $this->_read_key_meta($row->meta_data) : null;
        $key  = (is_array($data) && isset($data['key'])) ? (string) $data['key'] : '';
        if ($key === '') { echo json_encode(['ok' => false, 'msg' => 'No ' . $type . ' key saved.']); return; }

        if ($type === 'places') {
            // One real Text Search with the cheapest field mask: proves the key
            // AND that the Places API (New) is enabled on its project.
            $this->load->library('openchair/oc_places_lib');
            $res = $this->oc_places_lib->text_search($key, 'barbershop in Tampa, FL', 1);
            echo json_encode($res['ok']
                ? ['ok' => true, 'msg' => 'Google Places key is valid.']
                : ['ok' => false, 'msg' => 'Google Places check failed: ' . $res['error']]);
            return;
        }

        $ch = curl_init();
        if ($type === 'openai') {
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.openai.com/v1/models', CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key], CURLOPT_TIMEOUT => 12,
            ]);
        } elseif ($type === 'gemini') {
            // Gemini authenticates via ?key= (no Bearer header). A lightweight
            // models list call validates the key without consuming generation quota.
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://generativelanguage.googleapis.com/v1beta/models?key=' . rawurlencode($key),
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12,
            ]);
        } else { // elevenlabs
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.elevenlabs.io/v1/user', CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['xi-api-key: ' . $key], CURLOPT_TIMEOUT => 12,
            ]);
        }
        $body = curl_exec($ch); $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($code === 200) {
            echo json_encode(['ok' => true, 'msg' => ucfirst($type) . ' key is valid.']);
        } elseif ($code === 401 || $code === 403) {
            echo json_encode(['ok' => false, 'msg' => 'Invalid ' . $type . ' key (unauthorized).']);
        } else {
            echo json_encode(['ok' => false, 'msg' => ucfirst($type) . ' check failed (HTTP ' . $code . ').']);
        }
    }


    public function toggle_dnc()
    {
        $this->_json_headers();
        $on = (string) $this->input->post('enabled', true) === '1' ? '1' : '0';
        $this->_save_plain_setting((int) $this->user_id, 'dnc_check_enabled', $on);
        echo json_encode(['ok' => true, 'enabled' => $on === '1', 'msg' => $on === '1' ? 'Spam-score check on.' : 'Spam-score check off.']);
    }


    public function settings()
    {
        $uid = (int) $this->user_id;

        $this->data['page']        = 'settings';
        $this->data['page_title']  = 'Settings';
        $this->data['openai_set']  = $this->_has_key($uid, 'openai');
        $this->data['gemini_set']  = $this->_has_key($uid, 'gemini');
        $this->data['places_set']  = $this->_has_key($uid, 'places');
        $this->data['twilio_set']  = $this->_has_key($uid, 'twilio');
        $this->data['eleven_set']  = $this->_has_key($uid, 'elevenlabs');
        $this->data['bridge_base'] = $this->_ic_settings_bridge_base();

        // Masked previews so you can confirm a key is saved (and which one)
        // without ever exposing it in full — last 4 chars only, rest dotted.
        // Twilio shows the SID's tail too (the SID is an account identifier,
        // not the secret — the auth token is what's masked under 'twilio').
        $this->data['openai_mask'] = $this->_masked_key($uid, 'openai');
        $this->data['gemini_mask'] = $this->_masked_key($uid, 'gemini');
        $this->data['places_mask'] = $this->_masked_key($uid, 'places');
        $this->data['eleven_mask'] = $this->_masked_key($uid, 'elevenlabs');
        $this->data['twilio_mask'] = $this->_masked_key($uid, 'twilio');          // auth token
        $this->data['twilio_sid_mask'] = $this->_masked_key($uid, 'twilio', 'sid'); // account SID

        // SET-2: which ESPs are connected (connect/disconnect only — no list/tag picker).
        $esp_state = [];
        $this->load->library('encryption');
        foreach ($this->_esp_meta() as $type => $m) {
            $row = $this->db->where('user_id', $uid)->where('respondertype', (int) $m['rtype'])
                            ->where('status', 1)->limit(1)->get('autoresponder')->row();
            $esp_state[$type] = [
                'connected' => (bool) $row,
            ];
        }
        $this->data['esp_state']  = $esp_state;
        $this->data['page_title'] = 'Settings';

        // SET-5: SMTP connected? (settings meta_type='smtp', engine encrypt+serialize.)
        $smtp_row = $this->db->where('user_id', $uid)->where('meta_type', 'smtp')->get('settings')->row();
        $smtp_creds = ($smtp_row && $smtp_row->meta_data !== '') ? @unserialize($this->encryption->decrypt($smtp_row->meta_data)) : null;
        $this->data['smtp_set']         = is_array($smtp_creds) && !empty($smtp_creds['smtp_host']);
        $this->data['smtp_host']        = is_array($smtp_creds) && isset($smtp_creds['smtp_host'])     ? (string) $smtp_creds['smtp_host']     : '';
        $this->data['smtp_port']        = is_array($smtp_creds) && isset($smtp_creds['smtp_port'])     ? (string) $smtp_creds['smtp_port']     : '587';
        $this->data['smtp_username']    = is_array($smtp_creds) && isset($smtp_creds['smtp_username']) ? (string) $smtp_creds['smtp_username'] : '';
        $this->data['smtp_from']        = is_array($smtp_creds) && isset($smtp_creds['sender_email'])  ? (string) $smtp_creds['sender_email']  : '';
        $this->data['smtp_sender_name'] = is_array($smtp_creds) && isset($smtp_creds['sender_name'])   ? (string) $smtp_creds['sender_name']   : '';

        // OpenChair port: the host filled a default caller-ID and a Do-Not-Contact
        // suppression list here, both read by the Phone and Pocket lanes before
        // dialling. OpenChair has no lanes and never contacts anyone — 05-prd.md
        // §8 rule 7 — so ic_dnc_suppression is not in this schema and these panels
        // have nothing to show.
        //
        // The keys are still set, empty, because the ported view reads them. The
        // view hides the panels when they are empty; the settings surface is
        // rebuilt for OpenChair properly in a later phase.
        $this->data['default_from_number'] = '';
        $this->data['dnc_check_enabled']   = false;
        $this->data['dnc_list']            = [];

        // Render inside the Agentic Affiliates dark shell (sidebar key 'settings') via
        // the shared Ic_render_lib — the SAME path whitelabel/config/users use.
        // The lib draws ic_shell/layout + _ic_sidebar from ANY controller, so the
        // settings page no longer needs the Instantclose controller's private
        // _render(); the plain Setting controller renders the full shell itself.
        // legacy=true pulls jQuery + SweetAlert2 the inline settings JS uses.
        // The VIEW lives in this module, so address it module-qualified
        // ('setting/pages/settings') — a bare 'pages/settings' would resolve
        // against the running controller's own module and 404.
        $this->load->library('oc_render_lib');
        $this->oc_render_lib->render('setting/pages/settings', $this->data, [
            'page'       => 'settings',
            'page_title' => 'Settings',
            'legacy'     => true,
        ]);
    }


    public function dnc_add()
    {
        $uid = (int) $this->user_id;
        $raw = (string) $this->input->post('phone', true);
        $reason = trim((string) $this->input->post('reason', true));

        $phone = $this->_normalize_phone($raw);
        if ($phone === '' || strlen(preg_replace('/\D/', '', $phone)) < 7) {
            $this->session->set_flashdata('ic_error', 'Enter a valid phone number to suppress (e.g. +14155551234).');
            redirect('app/settings#compliance');
            return;
        }
        if ($reason !== '') { $reason = substr($reason, 0, 255); }

        // Already on the list? Treat as success (idempotent), no duplicate row.
        $exists = $this->db->where('user_id', $uid)->where('phone', $phone)
                           ->limit(1)->get('ic_dnc_suppression')->row();
        if ($exists) {
            $this->session->set_flashdata('ic_success', $phone . ' is already on your Do-Not-Contact list.');
            redirect('app/settings#compliance');
            return;
        }

        $this->db->insert('ic_dnc_suppression', [
            'user_id' => $uid,
            'phone'   => $phone,
            'reason'  => ($reason !== '' ? $reason : null),
        ]);

        $this->session->set_flashdata('ic_success',
            $phone . ' added — no Bridge will call or text this number.');
        redirect('app/settings#compliance');
    }


    public function dnc_remove()
    {
        $uid = (int) $this->user_id;
        $id  = (int) $this->input->post('id', true);

        if ($id <= 0) {
            $this->session->set_flashdata('ic_error', 'Invalid entry.');
            redirect('app/settings#compliance');
            return;
        }

        // Ownership-scoped delete: id AND user_id must both match.
        $this->db->where('id', $id)->where('user_id', $uid)
                 ->delete('ic_dnc_suppression');

        $this->session->set_flashdata('ic_success',
            $this->db->affected_rows() > 0
                ? 'Removed — this number can be contacted again (subject to consent + quiet hours).'
                : 'That entry was not found on your list.');
        redirect('app/settings#compliance');
    }


    public function save_settings()
    {
        $this->_json_headers();
        $uid = (int) $this->user_id;
        $this->load->library('encryption');

        $allowed = ['openai', 'gemini', 'places', 'twilio', 'elevenlabs'];
        $type    = strtolower(trim((string) $this->input->post('meta_type', true)));
        $value   = (string) $this->input->post('value', true);

        if (!in_array($type, $allowed, true)) {
            echo json_encode(['ok' => false, 'msg' => 'Unknown key type.']);
            return;
        }

        // Store as a serialized assoc so we can grow the payload later
        // (mirrors how the engine stores e.g. the 'whitelabel' meta_type).
        // Twilio carries TWO secrets — Account SID + Auth Token. We keep 'key'
        // = the token so _has_key() still works, and add 'sid'/'token' for the
        // caller-ID + balance endpoints. A field left blank on re-save keeps the
        // previously stored value (so you can rotate one without re-entering both).
        $existing = $this->db
            ->where('user_id', $uid)
            ->where('meta_type', $type)
            ->get('settings')->row();

        if ($type === 'twilio') {
            $sid   = trim((string) $this->input->post('sid', true));
            $token = trim($value);
            $prev  = ($existing && $existing->meta_data !== '') ? $this->_read_key_meta($existing->meta_data) : [];
            if (!is_array($prev)) { $prev = []; }
            if ($sid === '')   { $sid   = isset($prev['sid']) ? (string) $prev['sid'] : ''; }
            if ($token === '') { $token = isset($prev['token']) ? (string) $prev['token'] : (isset($prev['key']) ? (string) $prev['key'] : ''); }
            $value     = $token;                       // for the isSet flag below
            // Encrypted at rest (byte-compatible with the bridge-service ciCrypt.js).
            $meta_data = $this->encryption->encrypt(serialize(['key' => $token, 'sid' => $sid, 'token' => $token]));
        } else {
            $meta_data = $this->encryption->encrypt(serialize(['key' => $value]));
        }

        if ($existing) {
            $this->db->where('id', $existing->id)
                     ->update('settings', ['meta_data' => $meta_data]);
        } else {
            $this->db->insert('settings', [
                'user_id'   => $uid,
                'meta_type' => $type,
                'meta_data' => $meta_data,
            ]);
        }

        echo json_encode([
            'ok'    => true,
            'type'  => $type,
            'isSet' => $value !== '',
            'msg'   => 'Saved.',
        ]);
    }

    /**
     * Delete one of this user's saved API keys (the row itself, not an emptied
     * value), so the card goes back to "not connected" and nothing stays at rest.
     */
    public function remove_key()
    {
        $this->_json_headers();
        $type = strtolower(trim((string) $this->input->post('meta_type', true)));
        if (!in_array($type, ['openai', 'gemini', 'places', 'twilio', 'elevenlabs'], true)) {
            echo json_encode(['ok' => false, 'msg' => 'Unknown key type.']); return;
        }
        $this->db->where('user_id', (int) $this->user_id)->where('meta_type', $type)->delete('settings');
        $removed = $this->db->affected_rows() > 0;
        echo json_encode([
            'ok'  => true,
            'msg' => $removed ? 'Key removed.' : 'No key was saved.',
        ]);
    }

    // ---- helpers (copies; IC keeps its own via class precedence) ----


    private function _get_twilio_creds($uid)
    {
        $row = $this->db->where('user_id', (int) $uid)->where('meta_type', 'twilio')
                        ->get('settings')->row();
        if (!$row || $row->meta_data === '') { return null; }
        $d = $this->_read_key_meta($row->meta_data);
        if (!is_array($d)) { return null; }
        $sid   = isset($d['sid']) ? trim((string) $d['sid']) : '';
        $token = isset($d['token']) ? trim((string) $d['token'])
               : (isset($d['key']) ? trim((string) $d['key']) : '');
        // Legacy rows: a token packed as "SID:token" in a single field.
        if ($sid === '' && strpos($token, ':') !== false && strncmp($token, 'AC', 2) === 0) {
            list($sid, $token) = explode(':', $token, 2);
            $sid = trim($sid); $token = trim($token);
        }
        if ($sid === '' || $token === '') { return null; }
        return ['sid' => $sid, 'token' => $token];
    }

/** Low-level GET against the Twilio REST API with the user's BYOK creds. */
    private function _twilio_get($creds, $path, $query = '')
    {
        // Base account resource. An empty $path means "the account itself" — hit
        // Accounts/{sid}.json (NOT Accounts/{sid}/, which returns XML by default
        // and would fail json_decode even on a valid HTTP 200). Sub-resources
        // pass their own ".json" path (e.g. IncomingPhoneNumbers.json).
        $base = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($creds['sid']);
        $url  = ($path === '' || $path === null) ? $base . '.json' : $base . '/' . $path;
        if ($query !== '') { $url .= '?' . $query; }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $creds['sid'] . ':' . $creds['token'],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code < 200 || $code >= 300) { return [null, $code]; }
        $json = json_decode($body, true);
        return [is_array($json) ? $json : null, $code];
    }


    private function _esp_meta()
    {
        return [
            'convertkit'     => ['rtype' => 7, 'label' => 'ConvertKit',      'needs_url' => false],
            'mailchimp'      => ['rtype' => 3, 'label' => 'Mailchimp',       'needs_url' => false],
            'getresponse'    => ['rtype' => 1, 'label' => 'GetResponse',     'needs_url' => false],
            'activecampaign' => ['rtype' => 2, 'label' => 'ActiveCampaign',  'needs_url' => true],
        ];
    }


    private function _is_ac_url($url)
    {
        return (bool) preg_match('#^https://[a-z0-9][a-z0-9-]*\.api-us[0-9]+\.com/?$#i', trim((string) $url));
    }


    private function _save_plain_setting($uid, $meta_type, $value)
    {
        $existing = $this->db->where('user_id', (int) $uid)->where('meta_type', $meta_type)->get('settings')->row();
        if ($existing) {
            $this->db->where('id', $existing->id)->update('settings', ['meta_data' => $value]);
        } else {
            $this->db->insert('settings', ['user_id' => (int) $uid, 'meta_type' => $meta_type, 'meta_data' => $value]);
        }
    }

/**
     * Normalize a user-typed phone to E.164-ish: a single leading '+' (kept
     * only if present) followed by digits. Strips spaces, dashes, parens, etc.
     * so it matches the format subscribers are stored in (e.g. +14155551234).
     */
    private function _normalize_phone($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') { return ''; }
        $plus   = (substr($raw, 0, 1) === '+');
        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') { return ''; }
        return ($plus ? '+' : '') . $digits;
    }

/**
     * Decode a stored BYOK key row's meta_data. Keys are encrypted at rest
     * (serialize → encryption->encrypt). Rows saved before encryption was added
     * are plaintext serialize(), so we fall back to a plain unserialize when
     * decrypt fails — making the plaintext→encrypted migration read-time and
     * lossless (no separate backfill needed; rows re-encrypt on next save).
     */
    private function _read_key_meta($raw)
    {
        if ($raw === null || $raw === '') { return null; }
        $this->load->library('encryption');
        $dec  = @$this->encryption->decrypt($raw);
        $data = ($dec !== false) ? @unserialize($dec) : false;
        if (!is_array($data)) { $data = @unserialize($raw); } // legacy plaintext row
        return is_array($data) ? $data : null;
    }

/** Has the user stored a non-empty key of this meta_type? */
    private function _has_key($uid, $type)
    {
        $row = $this->db
            ->where('user_id', (int) $uid)
            ->where('meta_type', $type)
            ->get('settings')->row();
        if (!$row || $row->meta_data === '') {
            return false;
        }
        $data = $this->_read_key_meta($row->meta_data);
        return is_array($data) && isset($data['key']) && $data['key'] !== '';
    }



    /**
     * Mask a secret for display: keep the last $keep characters, replace the
     * rest with dots (capped so a long key doesn't render a giant dot run).
     * A very short secret (<= $keep) is fully dotted so nothing leaks.
     * Returns '' for an empty/missing value.
     */
    private function _mask_secret($secret, $keep = 4)
    {
        $secret = (string) $secret;
        $len = strlen($secret);
        if ($len === 0) { return ''; }
        if ($len <= $keep) { return str_repeat('•', $len); }
        $dots = str_repeat('•', min($len - $keep, 8));
        return $dots . substr($secret, -$keep);
    }

    /**
     * Decrypt the user's stored key of $type and return a masked preview of the
     * given $field ('key' by default; 'sid' for the Twilio account SID). Returns
     * '' when no key is saved. Never returns the plaintext to the view.
     */
    private function _masked_key($uid, $type, $field = 'key')
    {
        $row = $this->db
            ->where('user_id', (int) $uid)
            ->where('meta_type', $type)
            ->get('settings')->row();
        if (!$row || $row->meta_data === '') { return ''; }
        $data = $this->_read_key_meta($row->meta_data);
        if (!is_array($data)) { return ''; }
        // Twilio token may live under 'token' (newer) or 'key' (compat).
        $val = isset($data[$field]) ? $data[$field]
             : ($field === 'key' && isset($data['token']) ? $data['token'] : '');
        return $this->_mask_secret((string) $val);
    }


    private function _json_headers()
    {
        $this->output->set_content_type('application/json');
    }

    /** Base URL of the companion Node service, for the settings page's
     *  diagnostics link.
     *
     *  OpenChair port: the host resolved this through Bridge_service, the voice
     *  product's library, which is not ported here — OpenChair's sibling service
     *  is openchair-service on :4100 and it does no telephony. Read straight from
     *  app_config rather than pulling in a library for one string. */
    private function _ic_settings_bridge_base()
    {
        return (string) $this->config->item('oc_service_internal_base');
    }
}
