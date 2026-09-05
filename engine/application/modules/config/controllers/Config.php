<?php
defined('BASEPATH') OR exit('No direct script access allowed');



class Config extends App_Controller{


    public function __construct(){
        parent::__construct();
        
        $this->load->library('encryption');

        if($this->user_id != 1){
            // Superadmin (user 1) only — send everyone else to the IC home.
            redirect('app');
        }

    }

    public function index(){

        $this->data['configData'] = $this->app_lib->fetchTableData('config');

        // Render inside the Agentic Affiliates dark shell (sidebar + topbar) instead of
        // the legacy Agentic Agency chrome. 'config' is the active sidebar key.
        $this->load->library('oc_render_lib');
        $this->oc_render_lib->render('config/allconfig', $this->data, [
            'page'       => 'config',
            'page_title' => 'API Config',
        ]);

    }


    public function saveKey(){

        $this->form_validation->set_rules('key', 'Key', 'required');
        $this->form_validation->set_rules('value', 'Value', 'required');
        if ($this->form_validation->run() == FALSE)
        {
            $this->session->set_flashdata('error', 'Please fill all the fields');
            $res['status'] = '400';
            $res['msg'] = 'Please fill all the fields';
            echo json_encode($res);
        }
        else
        {
            $key = $this->input->post('key');
            $value = $this->input->post('value');

            

            $cid = $this->input->post('cid');

            $encrypted_value = $this->encryption->encrypt($value);
            $data = array(
                'key_name' => $key,
                'key_value' => $encrypted_value
            );

            if(empty($cid)){
               $insertId = $this->app_lib->insertTable('config', $data);
            }else{
                $this->app_lib->updateTable('config',array('id' => $cid), $data );
            }

            $res['status'] = '200';
            $res['msg'] = 'Data saved successfully';
            echo json_encode($res);
        }


    }


    public function deleteKey(){
        $id = $this->input->post('id');
        $this->app_lib->deleteTableData('config', array('id' => $id));
        $res['status'] = '200';
        $res['msg'] = 'Data deleted successfully';
        echo json_encode($res);
    }


// $encrypted_api_key = $this->encryption->encrypt('your_api_key');
//         $api_key = $this->encryption->decrypt($encrypted_api_key);



    /**
     * Check if an API key is configured (without exposing the actual key)
     * Returns JSON: {configured: true/false}
     */
    public function checkKey($key = ''){

        $this->load->library('config_lib');
        $value = $this->config_lib->getKey($key);
        // ── Security: Never echo raw API keys to the browser ──
        echo json_encode(['configured' => !empty($value), 'key_name' => $key]);
    }









}



































?>