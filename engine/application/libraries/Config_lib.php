<?php



defined('BASEPATH') OR exit('No direct script access allowed');


class Config_lib
{
    public $CI;
	public function __construct()
	{
        $this->CI =& get_instance();
		$this->CI->load->model('sites/datamodel');
        $this->CI->load->library('encryption');
	}



    public function getKey($key)
    {

        $configData = $this->CI->Datamodel->fetchTableData('config',array('key_name'=>$key));
        $key_value = $configData[0]['Data']->key_value;

        if(!empty($configData)){
            return $this->CI->encryption->decrypt($key_value);
        }else{
            return "invalid key";
        }
    }

    public function setKey($key, $value)
    {
        $encrypted = $this->CI->encryption->encrypt($value);
        $configData = $this->CI->Datamodel->fetchTableData('config', array('key_name' => $key));

        if (!empty($configData) && isset($configData[0]['Data']->id)) {
            $this->CI->Datamodel->updateTable('config', array('key_name' => $key), array('key_value' => $encrypted));
        } else {
            $this->CI->Datamodel->insertTable('config', array('key_name' => $key, 'key_value' => $encrypted));
        }
    }










}






?>