<?php
error_reporting(1);
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/* load the MX_Router class */
require APPPATH . "third_party/MX/Controller.php";

class MY_Controller extends MX_Controller
{	
	// public $data;
	function __construct(){
		parent::__construct();
	}

	// exit;
}

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */