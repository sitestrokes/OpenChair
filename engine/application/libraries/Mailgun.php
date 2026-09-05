<?php 

defined('BASEPATH') OR exit('No direct script access allowed');


class Mailgun
{
	public $CI;
	public function __construct()
	{

        $this->CI =& get_instance();
		$this->CI->load->config('ion_auth');
		// Load config library to fetch Mailgun settings from config table
		$this->CI->load->library('config_lib');


	}

	/**
	 * Send The mail
	 *
	 * @param Array() //access,product_name,email,password subject, others
	 *
	 * @return array|bool
	 * @author Akash
	 */
	public function sendMail($email='',$first_name = '',$param=array(),$from = '', $app = 'AILocalFunnel')
	{
	
		$subject = $param['subject'];

		$message = $param['message'];


		// Fetch Mailgun credentials from config table using Config_lib (getKey
		// already DECRYPTS the value). Historically these were also base64-encoded
		// before encryption, so the code base64_decode()'d after decrypt. But when a
		// value is stored plain (encrypt only, no base64), that extra decode CORRUPTS
		// it — e.g. a real hostname like "mg.example.net" becomes 15 bytes of binary
		// garbage → curl error 3 (bad URL) → the email silently fails. So decode ONLY
		// when the value actually looks base64-encoded; otherwise use it as-is.
		$key    = $this->_maybe_base64_decode($this->CI->config_lib->getKey('mailgun_key'));
		$domain = $this->_maybe_base64_decode($this->CI->config_lib->getKey('mailgun_domain'));

		// Check if Mailgun credentials are valid
		if ($key === 'invalid key' || $domain === 'invalid key') {
			log_message('error', 'Mailgun credentials not found in config table. Please add mailgun_key and mailgun_domain.');
			return false;
		}

		$to=$first_name.' <'.$email.'>';

		if(!empty($from))
		{
			$from=$this->CI->config->item('site_title', 'ion_auth').' <'.$from.'>';
		}
		else
		{
			$from='Prady From Engageleads <'.$this->CI->config->item('admin_email', 'ion_auth').'>';
			
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, 'api:'.$key);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_URL, 
          'https://api.mailgun.net/v3/'.$domain.'/messages');
		curl_setopt($ch, CURLOPT_POSTFIELDS, 
            array('from' => $from,
                  'to' => $to,
                  'subject' =>$subject,
                  'html' => $message));

		$result = curl_exec($ch);
		// TEMP DIAGNOSTIC (remove after debugging the reset-email failure):
		$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$cerr = curl_error($ch);
		$eff  = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		// Is the domain a sane hostname after decrypt+base64_decode? (log shape, not the key)
		$dom_ok = (bool) preg_match('~^[a-z0-9.\-]+\.[a-z]{2,}$~i', (string) $domain);
		log_message('error', 'Mailgun sendMail → http=' . $http . ' curl_errno=' . curl_errno($ch)
			. ' curl_err=' . ($cerr ?: 'none')
			. ' domain_len=' . strlen((string) $domain) . ' domain_valid_host=' . ($dom_ok ? 'yes' : 'NO')
			. ' key_len=' . strlen((string) $key)
			. ' url_host=' . parse_url((string) $eff, PHP_URL_HOST)
			. ' resp=' . substr((string) $result, 0, 200));
		curl_close($ch);

		// print_r($result);exit;
		return $result;

	}

	/**
	 * base64_decode a value ONLY when it is genuinely base64-encoded. getKey()
	 * already returns the decrypted value; some environments stored it plain
	 * (no base64), and blindly decoding those corrupts a real hostname/key. We
	 * decode only when: strict base64 validation passes AND the decoded result is
	 * printable ASCII AND it differs from the input. Otherwise the value is used
	 * as-is. This makes the lib work whether creds were stored plain or base64'd.
	 */
	private function _maybe_base64_decode($val)
	{
		$val = (string) $val;
		if ($val === '' || $val === 'invalid key') {
			return $val;
		}
		// Must look like base64 (charset + length multiple of 4) to even try.
		if (!preg_match('~^[A-Za-z0-9+/]+={0,2}$~', $val) || (strlen($val) % 4) !== 0) {
			return $val;
		}
		$decoded = base64_decode($val, true); // strict: false on invalid input
		if ($decoded === false || $decoded === '') {
			return $val;
		}
		// Decoded must be printable (a real key/hostname) — reject binary garbage.
		if (!ctype_print($decoded)) {
			return $val;
		}
		return $decoded;
	}

}

?>
