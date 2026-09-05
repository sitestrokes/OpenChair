<?php



defined('BASEPATH') OR exit('No direct script access allowed');


class App_lib
{
	public function __construct()
	{
		
		$this->load->model('sites/datamodel');
	}

	/**
	 * __call
	 *
	 * Acts as a simple way to call model methods without loads of stupid alias'
	 *
	 * @param string $method
	 * @param array  $arguments
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($method, $arguments)
	{
		// echo $method;exit;

		if (!method_exists( $this->datamodel, $method) )
		{
			throw new Exception('Undefined method User_lib::' . $method . '() called');
		}
		return call_user_func_array( [$this->datamodel, $method], $arguments);
	}



	/**
	 * __get
	 *
	 * Enables the use of CI super-global without having to define an extra variable.
	 *
	 * I can't remember where I first saw this, so thank you if you are the original author. -Militis
	 *
	 * @param    string $var
	 *
	 * @return    mixed
	 */
	public function __get($var)
	{
		return get_instance()->$var;
	}


	public function validateURL($param,$user_id)
    {
        
        $param_data = explode("+", base64_decode($param));
        $subdomain_id = $param_data[1];
        
        if($user_id !=1)
        {
            $where = array(
                'id' => $subdomain_id,
                'user_id' => $user_id
            );
        }
        else
        {
            $where = array(
                'id' => $subdomain_id,
            );
        }

        $checkValid = $this->Datamodel->checkExit('subdomains',$where);

        return $checkValid ? $subdomain_id : 0;
        
    }

    public function validateCampURL($param,$user_id)
    {
        
        $param_data = explode("+", base64_decode($param));
        $cam_id = $param_data[1];
        
        if($user_id !=1)
        {
            $where = array(
                'id' => $cam_id,
                'user_id' => $user_id
            );
        }
        else
        {
            $where = array(
                'id' => $cam_id,
            );
        }

        $checkValid = $this->Datamodel->checkExit('campaigns',$where);

        return $checkValid ? $cam_id : 0;
        
    }


    public function _render_page($page,$data)
    {
        $this->load->view('shared/header',$data);
        // $this->load->view('shared/sidebar',$data);
        $this->load->view($page,$data);
        $this->load->view('shared/antispam',$data);
        // $this->load->view('shared/splitpayPopup',$data);
        

    }

    public function processURL($frameurl)
	{
		// echo $frameurl;
			$ch = curl_init();
	        curl_setopt_array($ch, array(
	        CURLOPT_URL => trim($frameurl),
	        CURLOPT_RETURNTRANSFER => true,
	        CURLOPT_SSL_VERIFYPEER => false,
	        CURLOPT_SSL_VERIFYHOST => 2
	        ));

	        $result = curl_exec($ch);
	        curl_close($ch);

	        // print_r($result);exit;
	        return $result;
	}

	public function processURL_postData($url,$data){
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($curl, CURLOPT_HTTPHEADER,
		        array("Content-type: application/json"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

		$result = curl_exec($curl);
		curl_close($curl);

		return $result;

	}


	public function removeQuotes($string)
	{
		return preg_replace("/[^a-zA-Z0-9]+/", " ", html_entity_decode($string, ENT_QUOTES));
    }

    public function convertURLs($string)
	{
	    $url = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';   
	    return preg_replace($url, '<a href="$0" target="_blank" title="$0">$0</a>', $string);
	} 

	//curl for 400 bad request
	public function processURL_UserAgent($url2)
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
        CURLOPT_URL => $url2,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 2
        ));
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)');
		 // curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36');
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    //curl for 400 bad request
	public function processURL_getData($url2)
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
        CURLOPT_URL => $url2,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 2
        ));
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)');
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

     public function processURL3($frameurl, $apikey)
	{
 		$curl = curl_init();

        $headers = [
            "Accept: application/json",
            "Authorization: " . $apikey,
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => trim($frameurl),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        // echo "<pre>";
        // print_r(json_decode($response,true));
        // exit;
	    return $response;

	}

	public function processURL_all($url, $type, $header = array(), $post_data = ''){
		

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if (!empty($post_data)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}

		if (!empty($header)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header );
		}


		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}



	public function gettime_byzone($time, $format = 'Y-m-d H:i:s' )
	{
		$timezone1 = $this->session->userdata('timezone');

		$timezone = $timezone1;
		if($_SERVER['HTTP_HOST'] == 'localhost' || empty($timezone) ){
			$timezone = 'UTC';
		}

		//check the $time format is it a timestamp or a datetime string
		if (is_numeric($time)) {
			
			$time = gmdate('Y-m-d H:i:s', $time);

			if($_SERVER['HTTP_HOST'] == 'localhost' && !empty($timezone1) ){
				$timezone = $timezone1;
			}
		}
		
		$utcDate = new DateTime($time, new DateTimeZone('UTC'));
        $utcDate->setTimezone(new DateTimeZone($timezone));

		return $utcDate->format($format);
	}


	/**
	 * Convert a UTC datetime to the user's local timezone for display.
	 *
	 * @param string $utc_datetime  UTC datetime string (e.g. "2026-04-13 14:30:00")
	 * @param string $format        PHP date format (default: 'd M Y, h:i A')
	 * @param string $timezone      IANA timezone (default: from session)
	 * @return string               Formatted local datetime
	 */
	public function utc_to_local($utc_datetime, $format = 'd M Y, h:i A', $timezone = null)
	{
		if (empty($utc_datetime)) return '';
		$CI =& get_instance();
		if (empty($timezone)) {
			if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
				$timezone = 'UTC';
			} else {
				$timezone = $CI->session->userdata('user_timezone')
					?: $CI->session->userdata('timezone')
					?: 'UTC';
			}
		}
		try {
			$dt = new DateTime($utc_datetime, new DateTimeZone('UTC'));
			$dt->setTimezone(new DateTimeZone($timezone));
			return $dt->format($format);
		} catch (Exception $e) {
			return $utc_datetime; // fallback to raw value
		}
	}


}


?>