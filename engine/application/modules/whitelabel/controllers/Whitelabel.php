<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Whitelabel extends App_Controller {

    public $table;
    public function __construct(){
        parent::__construct();

        // White-label controls the whole app shell — ADMIN ONLY: the superadmin
        // (user 1) or a JV Admin (access code '51'). NOT the Ion-Auth 'admin'
        // group (most accounts were wrongly provisioned into it, which would let
        // a non-admin reach this page by direct URL even with the menu hidden).
        $access = array_map('trim', explode(',', (string) ($this->user->access ?? '')));
        $is_admin = (int) $this->user_id === 1 || in_array('51', $access, true);
        if (!$is_admin) {
            $this->session->set_flashdata('error', 'That area is for administrators only.');
            redirect('app');
        }

        $this->table = 'settings';
        /*
            redirect subdomain creation if user not have subdomain
        */
        if($this->user_id!=1)
            $this->where['user_id'] = $this->user_id;

        $this->load->config('app_config');
    }
    
    /**
     * List All Blogs for particular users
     */
    public function index()
    {
        $Whitelabel = $this->Datamodel->fetchTableData($this->table,array("user_id"=>$this->user_id,'meta_type'=>'whitelabel'));

        if (empty($Whitelabel)) {

            $globaldata = $this->config->item('global_data');
            $whiteLabelData['logo'] = base_url($globaldata['logo_url']);
            $whiteLabelData['fav_url'] = base_url($globaldata['fav_url']);
            $whiteLabelData['sidebar_logo'] = base_url($globaldata['sidebar_logo']);
            $whiteLabelData['site_title'] = $this->config->item('site_title','ion_auth');
            $whiteLabelData['footer_text'] = $globaldata['footer_text'];

            $this->data['whitelabel_Data'] = $whiteLabelData ;
        }else{
            $this->data['whitelabel_Data'] = unserialize($Whitelabel[0]['Data']->meta_data);
        }
        

        
        // Render inside the Agentic Affiliates dark shell (sidebar key 'whitelabel').
        // legacy=true pulls jQuery + SweetAlert2 the view's JS needs.
        $this->load->library('oc_render_lib');
        $this->oc_render_lib->render('whitelabel', $this->data, [
            'page'       => 'whitelabel',
            'page_title' => 'White-label',
            'legacy'     => true,
        ]);
    }


    /*
        Insert or Update the whitelabel Data
    */

    public function cURD()
    {
        // print_r($_POST);exit;
        $data = $_POST;
        // Trim the free-text brand fields so stray leading/trailing whitespace can't
        // produce a malformed logo alt ("77Agentic Affiliates") or footer (L-04).
        if (isset($data['site_title']))  { $data['site_title']  = trim((string) $data['site_title']); }
        if (isset($data['footer_text'])) { $data['footer_text'] = trim((string) $data['footer_text']); }
        $check = $this->app_lib->checkExit($this->table,['user_id' => $this->user_id,'meta_type' => 'whitelabel']);

        if(array_key_exists('tutorial_tab', $data)) { $data['tutorial_tab'] = 1; }else{ $data['tutorial_tab'] = 0;}
        if(array_key_exists('bonus_tab', $data)) { $data['bonus_tab'] = 1; }else{ $data['bonus_tab'] = 0;}
        if(array_key_exists('support_tab', $data)) { $data['support_tab'] = 1; }else{ $data['support_tab'] = 0;}


        echo $check;
        if($check == 0)
        {
            $this->app_lib->insertTable($this->table,['user_id' => $this->user_id,'meta_type' => 'whitelabel','meta_data' => serialize($data)]);
        }
        else
        {
            $this->app_lib->updateTable($this->table,['user_id' => $this->user_id,'meta_type' => 'whitelabel'],['meta_data' => serialize($data)]);

        }
    }


    public function image_upload()
    {
        $this->load->library('s3_lib');   
        echo json_encode($this->s3_lib->imageUpload($this->user_id));
    }

    
}
