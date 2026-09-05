<?php 
// error_reporting(1);
class Users extends App_Controller{
   
    function __construct(){
        parent::__construct();
        $this->lang->load('page');
        $this->data['PageTitle'] = $this->lang->line('PageTitle')['users'];

        // Manage Users is ADMIN ONLY — the superadmin (user 1) or a JV Admin
        // (access code '51'). NOT the Ion-Auth 'admin' group (most accounts were
        // wrongly provisioned into it, so the group would let JV/buyer users in
        // via a direct URL even with the menu hidden). Profile + self-service
        // methods stay open for every user, so guard per-method here.
        $method = strtolower($this->router->fetch_method() ?? '');
        $self_service = ['profile', 'updateprofile'];
        if (! in_array($method, $self_service, TRUE)) {
            $access = array_map('trim', explode(',', (string) ($this->user->access ?? '')));
            $is_admin = (int) $this->user_id === 1 || in_array('51', $access, true);
            if (!$is_admin) {
                $this->session->set_flashdata('error', 'That area is for administrators only.');
                redirect('app');
            }
        }
    }

     /*
    	List All USers
    */
    public function index($search = null , $page = null) {
		
		// echo $page;exit;
		$user = $this->ion_auth->user()->row();
		$userID = $user->id;
		$itemsPerPage = 10;
		
		if ((isset($_REQUEST['search']) && !is_null($_REQUEST['search'])) || (!empty($search) && !empty($page))){

			$page = ($this->uri->segment(3)) ? ($this->uri->segment(3)-1) : 0;
            
            $search = trim($_POST['search']);
            // get search string
            $search = ($this->input->post("search"))? $this->input->post("search") : "NIL";

            $search = ($this->uri->segment(2)) ? $this->uri->segment(2) : $search;

			$this->data['users'] = $this->user_lib->getSearchUser($search,$itemsPerPage,$page);
		    $total_row =$this->user_lib->record_search_count_all($search);
		    // echo "string";exit;
		    $config['base_url'] =base_url('users/'.$search);
		}
		else if( $userID ==1)
		{
			// echo "string";
			// print_r($this->data['users']);
			$config['base_url'] =base_url('users/');
			$page = ($this->uri->segment(2)) ? ($this->uri->segment(2) - 1) : 0;
			$this->data['users'] = $this->user_lib->getAllUsers($itemsPerPage,$page);
			$total_row =$this->user_lib->user_record_count_all();
		}
		else{
			
			$config['base_url'] =base_url('users/');
			$page = ($this->uri->segment(2)) ? ($this->uri->segment(2) - 1) : 0;
			$this->data['users'] = $this->user_lib->getAll($itemsPerPage,$page);
		  	$total_row =$this->user_lib->user_record_count();
		}

	
	  	//pagination
		
		$config['total_rows'] = $total_row;
		$config["per_page"] = $itemsPerPage;
		$config['use_page_numbers'] = TRUE;
		$config['num_links'] = 2;
		// Clean markup so the pager matches the dark shell. The old config emitted
		// bare <li class="page-item"> tags with NO <ul> wrapper, which browsers render
		// as bulleted list items — that was the stray dots in the pager. We strip the
		// <li> wrappers and lean on the .page-link class the library already hardcodes
		// on every anchor, giving the current-page span the same class; the whole set
		// is wrapped in <nav class="pg">, styled by the .pg rules in the users view.
		$config['full_tag_open']   = '<nav class="pg" aria-label="Users pages">';
		$config['full_tag_close']  = '</nav>';
		$config['cur_tag_open']    = '<span class="page-link pg-cur" aria-current="page">';
		$config['cur_tag_close']   = '</span>';
		$config['num_tag_open']    = '';  $config['num_tag_close']   = '';
		$config['first_tag_open']  = '';  $config['first_tag_close'] = '';
		$config['last_tag_open']   = '';  $config['last_tag_close']  = '';
		$config['next_tag_open']   = '';  $config['next_tag_close']  = '';
		$config['prev_tag_open']   = '';  $config['prev_tag_close']  = '';
		$config['first_link']      = 'First';
		$config['last_link']       = 'Last';
		$config['next_link']       = '&rsaquo;';
		$config['prev_link']       = '&lsaquo;';

		  $this->pagination->initialize($config);

		// create_links() returns '' when there's only one page; the view guards on that.
		$this->data["links"] = $this->pagination->create_links();
		 //END - config the pagination 
        $this->data['PageTitle'] = 'Users';
		$this->data['ActiveTab'] = 'users';
		$this->data['total_row'] = $total_row;

		// print_r($this->data["users"]);exit;
		// Render inside the Agentic Affiliates dark shell (sidebar 'users' = Manage Users).
		// legacy=true pulls jQuery + SweetAlert2 + FontAwesome the view's JS needs.
		$this->load->library('oc_render_lib');
		$this->oc_render_lib->render('users', $this->data, [
			'page'       => 'users',
			'page_title' => 'Users',
			'legacy'     => true,
		]);

	}



	/*
	
		creates a new user account page
	
	*/
	public function createUser($user_id = null)
	{
		$user = $this->ion_auth->user()->row();

		if(!empty($user_id) && $user_id !=1 )
		{
			$access = explode(",", $this->user->access);

			
			if( $user->id == 1 )
			{
				$where = array('id' => $user_id );
				$this->data['userData'] = $this->user_lib->getUser($where);
			}
			elseif( (in_array(7, $access) || in_array(71, $access) || in_array(72, $access) || in_array(9, $access) || in_array(91, $access) || in_array(94, $access) )){

				$where = array('id' => $user_id , 'parent_user_id' => $user->id);
				$this->data['userData'] = $this->user_lib->getUser($where);
			}
			else
			{
				echo "Not Found URL";
			}

			
		}
		// echo '<pre>';
		// print_r($this->data);exit();
		// $this->load->view("add_user",$this->data);
		
		// Dark shell render (sidebar 'monitoring' = Users). legacy=true → jQuery + Swal.
		$is_edit = !empty($this->data['userData']);
		$this->load->library('oc_render_lib');
		$this->oc_render_lib->render('add_user', $this->data, [
			'page'       => 'users',
			'page_title' => $is_edit ? 'Edit User' : 'New User',
			'legacy'     => true,
		]);
	}



	public function userStatus(){

		$id = $_POST['userid'];
		$status = $_POST['status'];
		$user = $this->ion_auth->user()->row();
		$userID = $user->id;

		$userdata = $this->user_lib->getUser($id);
		$parent_user_id = $userdata['user']->parent_user_id ;
		
		if ( $userID == 1 || ( $userID == $parent_user_id) ) {
			echo $status;
			if ($status == 1 ) {
				$this->user_lib->user_activate_Manually($id);
			}else{
				$this->user_lib->user_deactivate_Manually($id);
			}
		}

	
		
	}
	/**
	 * Builds the comma-separated users.access string from the posted access[] boxes.
	 *
	 * This used to be a hand-written if-chain with one branch per code, in both
	 * create() and update(). Any code the form offered but nobody remembered to
	 * add a branch for was silently dropped on save, which is why ticking OTO5
	 * (code 10) came back unticked on refresh. The allow-list is now the
	 * access_specifier config plus the legacy engine codes, so declaring a new
	 * product in app_config.php is all it takes.
	 *
	 * Admin (5) is deliberately NOT grantable here: the form never offers it and
	 * the old chains never wrote it, so a crafted POST must not be able to either.
	 *
	 * @param  array $posted  raw $_POST['access']
	 * @return string         e.g. "1,2,10,9"
	 */
	private function _build_access($posted)
	{
		if (!is_array($posted)) { return ''; }

		// Product codes come from config. The rest are legacy engine codes that
		// predate access_specifier and still sit on existing accounts.
		$products = array_keys((array) $this->config->item('access_specifier'));
		$legacy   = array('11', '21', '211', '31', '41', '61', '71', '72',
		                  '811', '822', '833', '911', '101');
		$allowed  = array_diff(array_map('strval', $products), array('5'));
		$allowed  = array_merge($allowed, $legacy);

		$posted = array_map('strval', $posted);
		$codes  = array();
		foreach ($allowed as $code) {
			if (in_array($code, $posted, true)) { $codes[] = $code; }
		}

		// JV access has always implied the bundle level as well.
		if (in_array('93', $codes, true) && !in_array('9', $codes, true)) {
			array_unshift($codes, '9');
		}

		return implode(',', array_unique($codes));
	}




	/*

		creates a new user account

	*/

	public function create() {
	
		$this->form_validation->set_rules('firstname', 'First name', 'required');
		$this->form_validation->set_rules('lastname', 'Last name', 'required');
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email');
		$this->form_validation->set_rules('password', 'Password', 'required');
		// $this->form_validation->set_rules('access[]', 'Access', 'required');
	
		if ($this->form_validation->run() == FALSE) {
			
			//all did not go well
			$return = array();
			
			$temp = array();
			$temp['header'] = $this->lang->line('users_create_error1_heading');
			$temp['content'] = $this->lang->line('users_create_error1_message').validation_errors();
			
			$return['responseCode'] = 0;
			$return['displayMessage'] = validation_errors();
			$return['responseHTML'] = $this->load->view('partials/error', array('data'=>$temp), true);
			
			die( json_encode( $return ) );
		
		} else {

			$creatorAccess = explode(',', $this->user->access);
			$isSuperAdmin  = in_array('5', $creatorAccess);
			$isJvAdmin     = in_array('51', $creatorAccess) && !$isSuperAdmin;

			if ($isJvAdmin && !empty($_POST['access']) && is_array($_POST['access'])) {
				$allowed = ['1', '93'];
				$_POST['access'] = array_values(array_intersect($_POST['access'], $allowed));
			}

			// print_r($_POST['access']);exit;
			$access      = $this->_build_access($_POST['access']);
			$product_ids = '';
			// echo $access;exit;
			// if(empty($access)){$access = '1';}
			//make sure the email address is allowed
			if( $this->ion_auth->email_check($_POST['email']) ) {
			
				//all did not go well
				$return = array();
				
				$temp = array();
				$temp['header'] = $this->lang->line('users_create_error1_heading');
				$temp['content'] = $this->lang->line('users_create_error2_message');
				
				$return['responseCode'] = 0;
				$return['displayMessage'] = "The email address you're trying to use is already used by different account. Please choose a different email address.";
				$return['responseHTML'] = $this->load->view('partials/error', array('data'=>$temp), true);
				
				die( json_encode( $return ) );
			
			}
			$user = $this->ion_auth->user()->row();
			$userID = $user->id;
			
			$userAccess = explode(",", $this->user->access);

			// print_r($userAccess);exit;
			//create the account

			if( $userID != 1){

				$temp = $_POST;
				unset($temp['password']);

				if(in_array('9', $_POST['access']) || in_array('91', $_POST['access']) || in_array('92', $_POST['access']) || in_array('94', $_POST['access']) ){
					$subject = 'Try to create bundle account';
				}else{
					$subject = 'New User Account Created';
				}

				$message = 'One Account has been created By '.$this->user->first_name.' '.$this->user->last_name.'.<br> Email : '.$this->user->email.'<br> User Details : '.json_encode($temp);
				$param = array('message' => $message,'subject' => $subject);

				$adminNotifyList = [
					'pintub@onlightenmedia.com' => 'Pintu',
					'nakul@onlightenmedia.com'  => 'Nakul',
				];
				foreach ($adminNotifyList as $notifyEmail => $notifyName) {
					$this->mailgun->sendMail($notifyEmail, $notifyName, $param);
				}

				if(in_array('9', $_POST['access']) || in_array('91', $_POST['access']) || in_array('92', $_POST['access']) || in_array('94', $_POST['access']) ){
					exit;
				}
			}

			
			// New accounts created here are regular MEMBERS (group '2'), NOT admins.
			// (This form previously assigned group '1' = admin to every account it
			// created, which is why non-admin/JV users saw the Admin menu and could
			// reach Manage Users / White-label. Admin is granted explicitly via the
			// edit form's "isAdmin" checkbox — add_to_group(1,...) below.)
			//email activation?
			if( isset($_POST['notify']) && $_POST['notify'] == 'yes' ) {

				$id = $this->ion_auth->registerManually($_POST['email'], $_POST['password'], $_POST['email'], array('first_name'=>$_POST['firstname'], 'last_name'=>$_POST['lastname']), array('2'), true, $access);

			} else {

				$id = $this->ion_auth->registerManually($_POST['email'], $_POST['password'], $_POST['email'], array('first_name'=>$_POST['firstname'], 'last_name'=>$_POST['lastname'],'product_id' => $product_ids), array('2'), false, $access);

			}

			if (!empty($id) && in_array('93', $_POST['access'])) {
				$this->copyAdminSettingsToUser($id);
			}

			

			
			//all good then
			$return = array();
			
			//include users in the return as well
			//$return['users'] = $this->load->view('partials/users', array('users'=>$this->Usermodel->getUsersPlusSites()), true);
			
			$temp = array();
			$temp['header'] = $this->lang->line('users_create_success_heading');
			$temp['content'] = $this->lang->line('users_create_success_message');
			
			$return['responseCode'] = 1;
			$return['displayMessage'] = 'The new account was created successfully!';
			$return['responseHTML'] = $this->load->view('partials/success', array('data'=>$temp), true);
			
			die( json_encode( $return ) );
		
		
	
		}
	}


	/*
	
		updates existing user
		
	*/
	
	public function update() {
	
		$this->form_validation->set_rules('userID', 'User ID', 'required');
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email');
		// $this->form_validation->set_rules('password', 'Password', 'required');
		$this->form_validation->set_rules('access[]', 'Access', 'required');

		if ($this->form_validation->run() == FALSE) {
			
			//all did not go well
			$return = array();
			
			$temp = array();
			$temp['header'] = $this->lang->line('users_update_error1_heading');
			$temp['content'] = $this->lang->line('users_update_error1_message').validation_errors();
			
			$return['responseCode'] = 0;
			$return['displayMessage'] = validation_errors();
			$return['responseHTML'] = $this->load->view('partials/error', array('data'=>$temp), true);
			
			die( json_encode( $return ) );
		
		} else {
			
			// print_r($_POST['access']);exit;
			$access = $this->_build_access($_POST['access']);
			// echo $access;exit;
			//make sure the email address is allowed
			
			$user = $this->ion_auth->user($_POST['userID'])->row();
			
			if( $_POST['email'] != $user->email && $this->ion_auth->email_check($_POST['email']) ) {
			
			//if( $_POST['email'] === $user->username || $_POST['email'] === $user->email || $this->ion_auth->identity_check($_POST['email']) === FALSE ) {
			
				//all did not go well
				$return = array();
				
				$temp = array();
				$temp['header'] = $this->lang->line('users_update_error2_heading');
				$temp['content'] = $this->lang->line('users_update_error2_message');
				
				$return['responseCode'] = 0;
				$return['displayMessage'] = "The email address you're trying to use is already used by different account. Please choose a different email address";
				$return['responseHTML'] = $this->load->view('partials/error', array('data'=>$temp), true);
				
				die( json_encode( $return ) );
			
			}


			if( $this->user_id != 1){

				$temp = $_POST;
				unset($temp['password']);

				$email = 'pintub@onlightenmedia.com';
				$name = 'Pintu';
				$subject = 'User Account Updated';
				$message = 'One Account details have been updated By '.$this->user->first_name.' '.$this->user->last_name.'.<br> Email : '.$this->user->email.'<br> User Details : '.json_encode($temp);

				$param = array('message' => $message,'subject' => $subject);
				$this->mailgun->sendMail($email, $name, $param);

				if(in_array('9', $_POST['access']) || in_array('91', $_POST['access']) || in_array('92', $_POST['access']) || in_array('94', $_POST['access']) ){
					exit;
				}
			}

			



			//update the account details
			$this->ion_auth_model->update($_POST['userID'], array('email'=>$_POST['email'], 'password'=>$_POST['password'], 'access'=>$access,'first_name'=>$_POST['firstname'],'last_name'=>$_POST['lastname']));

			//admin?
			
			if( isset($_POST['isAdmin']) && $_POST['isAdmin'] == 'yes' ) {
			
				$this->ion_auth->add_to_group(1, $_POST['userID']);
			
			} else {
			
				$this->ion_auth->remove_from_group(1, $_POST['userID']);
				
			}



			



			
			//all good then
			$return = array();
						
			$temp = array();
			$temp['header'] = $this->lang->line('users_update_success_heading');
			$temp['content'] = $this->lang->line('users_update_success_message');
			
			$return['responseCode'] = 1;
			$return['displayMessage'] = "The account was updated successfully!";
			$return['responseHTML'] = $this->load->view('partials/success', array('data'=>$temp), true);
			
			die( json_encode( $return ) );
		
		}
		
	}

	/*
	
		Delete existing user
		
	*/
	
	public function deleteUser()
	{
		$where1 = array('id' => $_POST['id'] );
		$usedData = $this->user_lib->getUser($where1);

 	 	if(in_array(833, explode(",",$usedData['user']->access))){

 	 		$this->app_lib->updateTable("place_details",['listing_user_id'=>$_POST['id']],['listing_user_id'=>0]);
 	 	}

 	 	// print_r($usedData['user']->parent_user_id);
 	 	$parent_user_id = $usedData['user']->parent_user_id ;
 	 	$user = $this->ion_auth->user()->row();

		if ($user->id == $parent_user_id || $user->id == 1) {
			$this->user_lib->deleteUser($_POST['id']);
		}
 	}

 	/*
 		Reset the usres Password 
 	*/

 	public function resetUser()
	{
		$userId = $_POST['userID'];
		$email = $_POST['email'];
        if( $userId == '' || $userId =='undefined' ){
            
            //echo "UserId is Missing";
            
        $return = array();
            
            $temp = array();
            $temp['header'] = $this->lang->line('users_userAjax_error1_heading');
            $temp['content'] = $this->lang->line('users_userAjax_error1_message');
            
            $return['responseCode'] = 0;
            $return['responseHTML'] = $this->load->view('partials/error', array('data'=>$temp), true);
            
            die( json_encode( $return ) );
        
        }

		$password = $this->random_password();
		

		$user = $this->ion_auth->user()->row();

		$where = array('id' => $userId );
		$usedData = $this->user_lib->getUser($where);
		$parent_user_id = $usedData['user']->parent_user_id ;

		if ($user->id == $parent_user_id || $user->id == 1) {
			$reset = $this->ion_auth->resetUser($userId , $password);
		}



		if($reset)
		{
			$return = array();
						
			$temp = array();
			$temp['header'] = "";
			$temp['content'] = $password;
						
			$return['responseCode'] = 1;
			$return['responseHTML'] = $temp['content'];
			
			die( json_encode( $return ) );			
			
		}
	
		
	}

	private function random_password( $length = 8 ) {
	    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
	    $password = substr( str_shuffle( $chars ), 0, $length );
	    return $password;
	}


	/**
	 * JV Access accounts (access code '93') must NOT edit their profile
	 * (name / password / email). Everyone else may. Central gate for the
	 * My Profile page + its save action; the menu items are hidden to match.
	 */
	private function _profile_allowed()
	{
		$access = array_map('trim', explode(',', (string) ($this->user->access ?? '')));
		return !in_array('93', $access, true);
	}

	public function profile()
	{
		// JV Access (93) may not view/edit their profile — send them home.
		if (!$this->_profile_allowed()) {
			redirect('app');
			return;
		}
		// Dark shell render. profile is self-service (any tier); sidebar key 'profile'.
		$this->load->library('oc_render_lib');
		$this->oc_render_lib->render('update_profile', $this->data, [
			'page'       => 'profile',
			'page_title' => 'My Profile',
			'legacy'     => true,
		]);

	}



	/*
	
		update account first name and last name
	
	*/
	
	public function updateProfile() {

		// JV Access (93) may not edit their profile — reject the save even if they
		// POST directly (the page + menu are hidden for them; this is the backstop).
		if (!$this->_profile_allowed()) {
			die(json_encode(array('responseCode' => 0, 'responseHTML' => 'Profile editing is not available for this account.')));
		}

		$this->form_validation->set_rules('userID', 'User ID', 'required');
		$this->form_validation->set_rules('firstname', 'First name', 'required|trim');
		$this->form_validation->set_rules('lastname', 'Last name', 'required|trim');
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim');
		// $this->form_validation->set_rules('password', 'Password', 'required|trim');
		
		if ($this->form_validation->run() == FALSE) {
			
			//all did not go well
			$return = array();
			
			$temp = array();
			$temp['header'] = "Ouch! Something went wrong:";
			$temp['content'] = "Something went wrong when trying to update your details, please see the errors below:<br><br>".validation_errors();
			
			$return['responseCode'] = 0;
			$return['responseHTML'] = $this->load->view('partials/error', array('data'=>$temp), true);
			
			die( json_encode( $return ) );
		
		} else {
		
			//all well, update user details
			//
			// SECURITY: self-service only — always update the CURRENTLY LOGGED-IN
			// user, never the request-supplied $_POST['userID'] (which a regular
			// user could set to any victim id to reset their email/password =
			// account takeover). Email is not changed here (it is the login and is
			// shown read-only in the form); only name + password are self-editable.
			$target_id = (int) $this->user_id;

			$data = array(
				'first_name' => $_POST['firstname'],
				'last_name' => $_POST['lastname'],
			);
			// Only set a password when the user actually supplied a new one.
			if (isset($_POST['password']) && $_POST['password'] !== '') {
				$data['password'] = $_POST['password'];
			}

			if( $this->ion_auth->update($target_id, $data) ) {
			
				//saved ok
				$return = array();
							
				$temp = array();
				$temp['header'] = "Hooray!";
				$temp['content'] = "Your account details were updated successfully and it will be correctly shown once you reload the page.";
				
				$return['responseCode'] = 1;
				$return['responseHTML'] = $this->load->view('partials/success', array('data'=>$temp), true);
				
				die( json_encode( $return ) );
			
			} else {
			
				//not saved
				//all did not go well
				$return = array();
				
				$temp = array();
				$temp['header'] = "Ouch! Something went wrong:";
				$temp['content'] = "We weren't able to save your details just now. Please reload the page and try again.";
				
				$return['responseCode'] = 0;
				$return['responseHTML'] = $this->load->view('partials/error', array('data'=>$temp), true);
				
				die( json_encode( $return ) );
			
			}
		
		}
	
	}

	private function copyAdminSettingsToUser($new_user_id, $admin_user_id = 1)
	{
		$meta_types = ['openai', 'gemini', 'elevenlabs', 'twilio', 'google', 'smtp', 'default_from_number'];

		$this->db->where('user_id', $admin_user_id);
		$this->db->where_in('meta_type', $meta_types);
		$admin_rows = $this->db->get('settings')->result();

		if (empty($admin_rows)) {
			return;
		}

		foreach ($admin_rows as $row) {
			$exists = $this->db
				->where(['user_id' => $new_user_id, 'meta_type' => $row->meta_type])
				->count_all_results('settings');

			if ($exists > 0) {
				continue;
			}

			$this->db->insert('settings', [
				'user_id'   => $new_user_id,
				'meta_type' => $row->meta_type,
				'meta_data' => $row->meta_data,
			]);
		}
	}
}

?>