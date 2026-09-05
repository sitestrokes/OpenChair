<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Datamodel extends CI_Model 
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->database();
        $this->load->library('session');
        $this->load->library('ion_auth');
  	}

	public function get_profile($user_id)
	{
		$this->db->select("*");
        $this->db->from("users");
        $this->db->where("id", $user_id);
        $query = $this->db->get();
        if (count($query->result()) > 0) {
        	return $query->row();
        }
	}

	public function update_profile($data)
    {
		return $this->db->update('users', $data, array('id' => $data['id']));
    }

    /* 
        fetch table data from given condition
    */
    public function fetchTableData($table,$where = array(),$select = '')
    {
        //print_r($where);
        if(!empty($where))
        {
            $this->db->where($where);
        }

        if(!empty($select))
        {
            $this->db->select($select);
        }

        $this->db->from($table);
        $query = $this->db->get();
        $res = $query->result();
        $result=array();
        foreach($res as $r)
        {
            $temp=array();
            $temp['Data']=$r;
            $result[]=$temp;
        }
		
        // echo $this->db->last_query();exit; 
        return $result;
    }

    public function fetchTableDataArray($table,$where = array(),$select = ''){
        //print_r($where);
        if(!empty($where))
        {
            $this->db->where($where);
        }

        if(!empty($select))
        {
            $this->db->select($select);
        }

        $this->db->from($table);
        $query = $this->db->get();
        $res = $query->result_array();
        
        // echo $this->db->last_query();exit; 
        return $res;
    }


    public function fetchTableDataLike($table,$where = array(),$where_like = array(),$select = '')
    {
        //print_r($where);
        if(!empty($where))
        {
            $this->db->where($where);
        }

        if(!empty($where_like))
        {
            $this->db->like($where_like);
        }

        if(!empty($select))
        {
            $this->db->select($select);
        }

        $this->db->from($table);
        $query = $this->db->get();
        $res = $query->result();
        $result=array();
        foreach($res as $r)
        {
            $temp=array();
            $temp['Data']=$r;
            $result[]=$temp;
        }
        
        // echo $this->db->last_query();exit; 
        return $result;
    }

    public function fetchTableDataIn($table,$where = array(),$where_in = array(), $where_like = array(), $select = '')
    {
        //print_r($where);
        if(!empty($where))
        {
            $this->db->where($where);
        }

        if(!empty($where_in))
        {
            $this->db->where_in($where_in['column'], $where_in['values']);
        }

        if(!empty($select))
        {
            $this->db->select($select);
        }

        if(!empty($where_like))
        {
            $this->db->group_start();
            foreach ($where_like as $column => $value) {
            $this->db->or_like($column, $value);
            }
            $this->db->group_end();
        }

        $this->db->from($table);
        $query = $this->db->get();
        $res = $query->result();
        $result=array();
        foreach($res as $r)
        {
            $temp=array();
            $temp['Data']=$r;
            $result[]=$temp;
        }
        
        // echo $this->db->last_query();exit; 
        return $result;
    }

    public function getAllwhere_ob($table,$where,$colomn,$type)
    {
        $this->db->select('*');
		$this->db->order_by($colomn,$type);
        $q = $this->db->get_where($table, $where);
        //echo $this->db->last_query();exit;
        $num_rows = $q->num_rows();
   		$result=array();
        if ($num_rows > 0) {
            foreach ($q->result() as $rows) {
                 $temp=array();
            	$temp['Data']=$rows;
            	$result[]=$temp;
            }
           
            return $result;
        }
    }

    public function fetchTableDataInLimit( $limit, $start, $table, $where , $where_in = array(), $where_like = array(),  $select = '*', $order_by = ''){

        $this->db->select($select);
        $this->db->from($table);
        $this->db->where($where);
        if(!empty($where_in))
        {
            $this->db->where_in($where_in['column'], $where_in['values']);
        }
        $this->db->limit($limit, $start*$limit);

        if(!empty($where_like))
        {
            $this->db->group_start();
            foreach ($where_like as $column => $value) {
            $this->db->or_like($column, $value);
            }
            $this->db->group_end();
        }

        if(!empty($order_by))
        {
            $this->db->order_by($order_by,'DESC');
        }


        $query = $this->db->get();
        $res = $query->result();
        $result=array();
        foreach($res as $r)
        {
            $temp=array();
            $temp['Data']=$r;
            $result[]=$temp;
        }
        
        // echo $this->db->last_query();exit; 
        return $result;
    }

     /*
        check record exists or not
    */
    public function checkExit($table,$where){
        $this->db->where($where);
        $this->db->from($table);
        $query = $this->db->get();

        // echo $this->db->last_query();exit;
        return $query->num_rows();
    }

    /*
        up[date the table
    */
    public function updateTable($table,$where,$data)
    {
        $this->db->where($where);
        $this->db->update($table,$data);
    }

    //get all campaigns
    public function fetchFunnels($limit = '', $start = '')
    {
        $user = $this->ion_auth->user()->row();
        $userID = $user->id;

        if($userID !=1)
        {
            $this->db->where("user_id",$userID);
        }

	    if ($limit !== '' && $start !== '')
        {
    		$this->db->limit($limit,$start*$limit);
        }

        $this->db->order_by("id", "DESC");

        $this->db->from("funnels");

        $query = $this->db->get();
        if ($query->num_rows() > 0) 
        {
            $keyData = array();
            foreach ($query->result() as $row) 
            {
                //print_r($query->result());
                $data = array();
                $data['funnels']=$row;
                $keyData[]=$data;
                //print_r($allSites);
            }
            return $keyData;
        }
        return false;
    }


    //get all count
	public function record_count_all(){
		
		$user = $this->ion_auth->user()->row();
		$userID = $user->id;

		if($userID != 1 || $userID != '1')
		{
			return $this->db->join('users', 'funnels.user_id = users.id')->where('users.id',$userID)->count_all_results("funnels");
		}else{
			return $this->db->count_all_results("funnels");
		}
		
	}

    public function getallCount($table,$where,$select='',$other='')
    {
		$user = $this->ion_auth->user()->row();
		if(!empty($where))
        {
            $this->db->where($where);
        }

        if(!empty($select))
        {
            $this->db->select($select);
        }
		if(!empty($other))
        {
			if($other=='distinct')
            {
				$this->db->distinct();
			}
			
			if($other=='30daycount')
            {
				$startdate=$user->created_start_date;
				$enddate=$user->created_end_date;
				//$get30daycount='created_date BETWEEN DATE_SUB('.$startdate.','.$enddate.') AND '.$startdate;
				
				$this->db->where('created_date >= "'.$startdate.'" and created_date <= "'.$enddate.'"');
			}
            
        }
		
		return $this->db->count_all_results($table);
		//echo $this->db->last_query();exit;
	}

    /*
        insert table data to table 

    */
    public function insertTable($table,$data)
    {
        $this->db->insert($table,$data);

        return $this->db->insert_id();
    }

    public function get_last_funnel_row($table,$where,$order)
    {
        $this->db->order_by($order, 'desc');
        $query = $this->db->get_where($table, $where);
        # echo $this->db->last_query(); exit;
        return $query->result();
    }

    /*
        delete the database data
    */
    public function deleteTableData($table,$where)
    {
        $this->db->where($where);
        $this->db->delete($table);
    }

    public function getAverageTimeSpent($id){

		// analytics is an event log now (migration ic_037): average the numeric
		// time_spent over time_on_page rows only.
		$query = $this->db->select('AVG(time_spent) as timespents')
			->where('site_uid',$id)
			->where('event','time_on_page')
			->where('time_spent >',0)
			->get("analytics");
			if ($query->num_rows() > 0) {
			$allSites = array();
			foreach ($query->result() as $row) {
			$data = array();
			$data['siteData']=$row;
			$allSites[]=$data;
			}

			$avgTime = $allSites[0]['siteData']->timespents;
			
			$hours = floor($avgTime / 60);
			$minutes = ((int)$avgTime % 60);
			return $hours.':'.$minutes;
			}
			return false;		
		
	}

    public function fetchAllData($limit='',$start='',$table1='',$table2='',$select='',$set_conditions=array(),$where = array(),$order_by = '',$where_like = '',$grop_by = '')
    {
        // echo $this->session->userdata('access');

        $this->db->select($select);

        if(!empty($order_by))
        {
            $this->db->order_by($order_by,'DESC');

        }   

        if(!empty($grop_by))
        {
            $this->db->group_by($grop_by);

        }   

        if(!empty($where_like))
        {
            $this->db->like($where_like);
        }     
       
        $this->db->from($table1);
        $this->db->join($table2, $set_conditions);
        
        $this->db->where($where);
        if ($limit !== '' && $start !== ''){
             
            $query= $this->db->limit($limit,$start*$limit);

        }
        $query = $this->db->get();
                if ($query->num_rows() > 0) {
                        $allSites = array();
                        foreach ($query->result() as $row) {
                        $data = array();
                        $data['Data']=$row;
                        $allSites[]=$data;
        }
        // echo $this->db->last_query();exit;
        return $allSites;
        }
        return false;

    }

        /*
        Joins Total rows
    */

    public function total_rows($table1,$table2,$set_conditions,$where = array(),$where_like = array())
    {
        $this->db->from($table1);
        $this->db->join($table2, $set_conditions);
        

        if(!empty($where))
        {
            $this->db->where($where);
        }

        if(!empty($where_like))
        {
            $this->db->like($where_like);
        }
         $query = $this->db->get();

         return $query->num_rows();

    }   

    public function exportTableData($table, $select, $where)
    {
        // Ensure output buffer is clean before starting
        if (ob_get_level()) {
            ob_end_clean();
        }
    
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ExportData_placedetails.csv');
    
        $query = "SELECT $select FROM $table WHERE $where";
        $result = $this->db->query($query);
        $arraydata = $result->result_array();
    
        if ($result->num_rows() > 0) {
            // Open output stream
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new Exception("Could not open php://output for writing CSV");
            }
    
            // Header row for CSV
            fputcsv($output, ['city_name', 'business_title', 'contact', 'rating', 'address', 'website', 'email', 'images', 'facebook_url', 'twitter_url', 'instagram_url', 'search_keywords']);
    
            // Data rows for CSV
            foreach ($arraydata as $row) {
                $title = $row['title'] ?? '';
                $contact = $row['contact'] ?? '';
                $rating = $row['rating'] ?? '';
                $address = $row['address'] ?? '';
                $website = $row['website'] ?? '';
                $email = $row['email'] ?? '';
                $place_hours = isset($row['place_hours']) ? implode(', ', unserialize($row['place_hours'])) : '';
                $images = isset($row['images']) ? implode(', ', unserialize($row['images'])) : '';
                $additional_data = isset($row['additional_content']) ? unserialize($row['additional_content']) : [];
                $facebook_url = $additional_data['facebook'] ?? '';
                $twitter_url = $additional_data['twitter'] ?? '';
                $instagram_url = $additional_data['instagram'] ?? '';
                $search_keywords = $row['search_keywords'] ?? '';
    
                fputcsv($output, [$address, $title, $contact, $rating, $address, $website, $email, $images, $facebook_url, $twitter_url, $instagram_url, $search_keywords]);
            }
    
            fclose($output);
            exit; // Ensure PHP script ends after output
        } else {
            echo "No data found for export.";
        }
    }

    public function getSingleRow($table,$where)
    {
        $this->db->where($where);
        $this->db->from($table);
        $query = $this->db->get();
        // echo $this->db->last_query();exit;
        $ret = $query->row();
        return $ret;
    }
    
    /*
        pagination Query
    */
    public function fetchAll($limit='',$start='',$table='',$where = array(),$order_by = '',$where_like = '')
    {
        // echo $this->session->userdata('access');

        $this->db->select('*');

        if(!empty($order_by))
        {
            $this->db->order_by($order_by,'DESC');

        } 

        if(!empty($where_like))
        {
            $this->db->like($where_like);
        }           
       
        $this->db->from($table);
        // $this->db->join('videos', 'campaigns.camp_id = videos.camp_id');
        
        $this->db->where($where);
        if ($limit !== '' && $start !== ''){
             
            $query= $this->db->limit($limit,$start*$limit);

        }
        $query = $this->db->get();

        // echo $this->db->last_query();exit;

                if ($query->num_rows() > 0) {
                        $allSites = array();
                        foreach ($query->result() as $row) {
                        $data = array();
                        $data['Data']=$row;
                        $allSites[]=$data;
        }

        return $allSites;
        }
        return false;

    }
}
