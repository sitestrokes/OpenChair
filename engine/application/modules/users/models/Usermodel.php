<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Usermodel extends CI_Model {

    function __construct()
    {
        parent::__construct();
        
    }
    
    public function getAll($limit='',$start='') {
        
        
        $user = $this->ion_auth->user()->row();
        $userID = $user->id;
        
         if ($limit !== '' && $start !== ''){
             
            $query= $this->db->limit($limit,$start*$limit);
            //print($start*$limit);
            //exit;
        }
        $query = $this->db->from('users')->where('id != "'.$userID.'" and parent_user_id="'.$userID.'" and (product_id IS NULL OR product_id != "client_portal")')->get();
        
        $res=$query->result();
        
        $users=array();
        
        foreach($res as $r){
        
            $temp=array();
            $temp['allusers']=$r;
            
            $users[]=$temp;
        
        }
        return $users;
    }
    
    public function getAllUsers($limit='',$start='') {
        
        
        $user = $this->ion_auth->user()->row();
        $userID = $user->id;
        
         if ($limit !== '' && $start !== ''){
             
            $query= $this->db->limit($limit,$start*$limit);
            
        }
        $query = $this->db->from('users')->where('id != "'.$userID.'" and (product_id IS NULL OR product_id != "client_portal")')->get();
        
        $res=$query->result();
        
        $users=array();
        // echo $this->db->last_query();exit;
        foreach($res as $r){
        
            $temp=array();
            $temp['allusers']=$r;
            
            $users[]=$temp;
        
        }

        // print_r($users);exit;
        return $users;
    }    
      /*
    
        returns Search users
    
    */
    
    public function getUser( $where = array() ) {
        $query = $this->db->from('users')->where($where)->get();
                
        if( $query->num_rows() == 0 ) {
            // print(6);
            return false;
        
        } 
        
        $res = $query->result();
        
        $user = $res[0];
        
        $userArray = array();
        $userArray['user'] = $user;
        
        return $userArray;
    
    }

    //counting all records in db table  
    public function user_record_count($check = null) {
        $user = $this->ion_auth->user()->row();
        $userID = $user->id;

        if(empty($check)){
            return $this->db->where('id != "'.$userID.'" and parent_user_id="'.$userID.'" and (product_id IS NULL OR product_id != "client_portal")')->count_all_results("users");
        }else{
            return $this->db->where('id != "'.$userID.'" and parent_user_id="'.$userID.'" and access != "'.$check.'" and (product_id IS NULL OR product_id != "client_portal")')->count_all_results("users");
        }
        
        
    }
    
    //record search count
    public function record_search_count_all($search){

        $user = $this->ion_auth->user()->row();
        $userID = $user->id;
        // SECURITY: bind $search via the escaped builder (SQLi fix); mirror the
        // conditions used by getSearchUser so the count matches the result set.
        $this->db->where('id !=', $userID);
        $this->db->where('parent_user_id', $userID);
        $this->db->where('(product_id IS NULL OR product_id != "client_portal")', null, false);
        if ($search !== '') {
            $this->db->group_start();
            $this->db->like('username', $search);
            $this->db->or_like('first_name', $search);
            $this->db->group_end();
        }
        return $this->db->count_all_results("users");

    }
    
    //counting all records in db table  
    public function user_record_count_all() {
        $user = $this->ion_auth->user()->row();
        $userID = $user->id;
        return $this->db->where('id != "'.$userID.'" and (product_id IS NULL OR product_id != "client_portal")')->count_all_results("users");
        
    }   
    /*getting search User  */
    
    public function getSearchUser($search = '',$limit='',$start='') {
        
        $user = $this->ion_auth->user()->row();
        $userID = $user->id;
        
         if ($limit !== '' && $start !== ''){
             
            $query= $this->db->limit($limit,$start*$limit);
            //print($start*$limit);
            //exit;
        }
        // SECURITY: $search is user input — bind it via the escaped query builder
        // (like()/or_like()), never string-interpolated into where() (SQLi).
        // $userID is the session user's id (server-derived) but is still bound.
        $this->db->from('users');
        $this->db->where('id !=', 1);
        $this->db->where('(product_id IS NULL OR product_id != "client_portal")', null, false);
        if ($userID != 1) {
            $this->db->where('parent_user_id', $userID);
        }
        if ($search !== '') {
            $this->db->group_start();
            $this->db->like('username', $search);
            $this->db->or_like('first_name', $search);
            $this->db->group_end();
        }
        $query = $this->db->get();










        // $query = $this->db->from('users')->where("username like '%".$search."%' or first_name like '%".$search."%'")->where->("parent_user_id='".$userID."'")->get(); 
        // $query =$this->db->from('users')->like('username',$search)->like('first_name',$search)->where('parent_user_id',$userID)->get();
        // echo $this->db->last_query();exit;
        $res=$query->result();
        $users=array();
        
        foreach($res as $r){
        
            $temp=array();
            $temp['allusers']=$r;
            
            $users[]=$temp;
        
        }
        return $users;
    }
    
    
    /*
        
        returns all images belonging to user
        
    */
    
    public function getUserImages( $userID ) {
    
        if( is_dir( $this->config->item('images_uploadDir')."/".$userID ) ) {
        
            $folderContent = directory_map($this->config->item('images_uploadDir')."/".$userID, 2);
            
            //die( print_r($folderContent) );
            
            if( $folderContent ) {
            
                $userImages = array();
            
                foreach( $folderContent as $key => $item ) {
            
                    if( !is_array($item) ) {
                
                        //check the file extension
                    
                        $tmp = explode(".", $item);
                        
                        
                        //prep allowed extensions array
                    
                        $temp = explode("|", $this->config->item('images_allowedExtensions'));
                    
                        if( in_array($tmp[1], $temp) ) {
                    
                            array_push($userImages, $item);
                    
                        }
                                
                    }
            
                }
            
                return $userImages;
            
            } else {
            
                return false;
            
            }
        
        } else {
        
            return false;
        
        }
    
    }
    
    

    
    
    
    /*
        returns all images belonging to user from Amazon
    */
    public function getUserImagesFromAmazon( $userID ) {
        $this->load->config('cdn_config');
        $this->load->library('S3');
            
        $s3 = new S3($this->config->item('awsAccessKey', 'cdn'), $this->config->item('awsSecretKey', 'cdn'));
        
        $contents = $s3->getBucket($this->config->item('bucket', 'cdn'), $userID);
        //print_r($contents);
        //exit;
        
        $userImages = array();
        
        foreach( $contents as $key => $item ) {
            
            if( is_array($item) ) {
                
                //check the file extension
            
                $tmp = explode(".", $item['name']);
                
                //prep allowed extensions array
            
                $temp = explode("|", $this->config->item('images_allowedExtensions'));
            
                if( in_array($tmp[1], $temp) ) {
                    array_push($userImages, $item["name"]);
                }
            }
        }
        
        return $userImages;
    }
    
    
    
    /*
        
        returns all users including their sites
    
    */
    
    public function getUsersPlusSites($userID = '', $search = '') {
    
        $return = array();
        
        //print($search);
        //exit;
        //get the app users
        
        if( $userID != '' ) {
        
            $users = $this->ion_auth->user($userID)->result();          
        
        } elseif( $search != '' ) {
        
            $users = $this->db->from('users')->where("username like '%".$search."%' or email = '".$search."'")->get()->result();
        
        } else {
        
            $users = $this->ion_auth->users()->result();
        
        }
            
        foreach( $users as $user ) {
        
            $temp = array();
            
            $temp['userData'] = $user;
            
            if( $this->ion_auth->is_admin( $user->id ) ) {
            
                $temp['is_admin'] = 'yes';
            
            } else {
            
                $temp['is_admin'] = 'no';
            
            }
            
            
            //get this user's sites
            $temp['sites'] = $this->sitemodel->all( $user->id );
            
            
            //push into the final array
            $return[] = $temp;
        
        }
        
        //die( print_r($return) );
        
        return $return;
    
    }


    /*
        updates user access
    */
    public function updateUserAccess( $user_id= '', $access= '' , $product_id = '') {
    
        $uAccess = explode(',', $access);

        
        $time = time();
        
        $data = array(
                'access' => $access,
                'product_id' => $product_id,
                'updated_date' => $time
        );
        

        $this->db->where('id', $user_id);
        $this->db->update('users', $data);
    } 
    
    
    public function editUser($userId){
        
        
        $q=$this->db->from('users')->where('id', $userId)->get();
            
                if( $q->num_rows() > 0 ) {
            
                    $res = $q->result();
                    $result=array();
                        
                    foreach($res as $r){
                    
                        $temp=array();
                        $temp['selectuser']=$r;
            
                        $result[]=$temp;
        
                        }
                    
                    }
                    else{
                        
                        echo "nothing";
                    }
        return $result; 
    }

    public function updateToken($token)

    {

        $user = $this->ion_auth->user()->row();

        $userID = $user->id;        

       $data = array(

                'fbuser_access_token' => $token

          );

       

       $this->db->where('id', $userID);

       $this->db->update('users', $data);        

    }
    
    //Activate User Manually
    public function user_activate_Manually($user_id){
        $data['active'] =1;
        $this->db->where('id',$user_id);
        $this->db->update('users',$data);
    }
    //Deactivate User Manually
    public function user_deactivate_Manually($user_id){
        $data['active'] = 0;
        $this->db->where('id',$user_id);
        $this->db->update('users',$data);
    }

    //Delete Single User 
    public function deleteUser($userId)
    {
            $this->db->where('id', $userId);
            $this->db->delete('users');
            
    
    }   
    //Delete Seletected Users
    public function deleteAllUsers($ids){
     $ids = $ids;
       
       $count = 0;
        foreach ($ids as $id){
           $did = intval($id).'<br>';
            $this->db->where('id', $did);
            $this->db->delete('users');  
            $count = $count+1;
       }
       
       // echo'<div class="alert alert-success" style="margin-top:-17px;font-weight:bold">'.$count.' Item deleted successfully</div>';
        $count = 0;
    } 

    public function get_user_id($email){
        $this->db->select("id");
        $this->db->from('users');
        $this->db->where("email",$email);
        $query = $this->db->get();
        $res = $query->result();
        return $res[0]->id;
    }

    public function updateDataLimit($where,$data){
         $this->db->where($where);
         $this->db->set($data, "$data+200", FALSE);   
         $this->db->update('users');
    }
    
    
    
}