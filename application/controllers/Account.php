<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	date		: 6th June, 2018
 *	AFR Staff Recognition system
 *	https://www.compassion.com
 *	NKarisa@ke.ci.org
 */

class Account extends CI_Controller
{
    private $extra_callback_parameter = "";

	function __construct()
	{
		parent::__construct();
		$this->load->database();
        $this->load->library('session');
		$this->load->model('user_model','user');

		/** System Feature Session Tag **/
		$this->session->set_userdata('view_type', "account");

       /*cache control*/
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');

    }

	public function ajax_list()
	{
		$list = $this->user->get_datatables();
		$data = array();
		$no = $_POST['start'];
		foreach ($list as $user) {

			$row = array();

			$user_data['user'] = $user;
			$row[] = $this->load->view('backend/account/user_table_action_list',$user_data,true);
			$row[] = $user->username;
			$row[] = $user->email;
			$row[] = $user->country;
			$row[] = $user->role;
			$row[] = $this->crud_model->get_type_name_by_id('user',$user->manager_id,'firstname');
			$row[] = $user->profile;
			$row[] = $user->auth == 1?get_phrase('active'):get_phrase('suspended');

			$data[] = $row;
		}

		$output = array(
						"draw" => $_POST['draw'],
						"recordsTotal" => $this->user->count_all(),
						//"recordsTotal" => $this->user->count_filtered(),
						"recordsFiltered" => $this->user->count_filtered(),
						"data" => $data,
				);
		//output to json format
		echo json_encode($output);
	}

    /***default functin, redirects to login page if no admin logged in yet***/
    public function index()
    {
        if ($this->session->userdata('user_login') != 1)
            redirect(base_url() . 'login', 'refresh');
    }

	/** AJAX LOADED CONTENT START**/

	public function countries($param1="",$param2=""){
		if ($this->session->userdata('user_login') != 1)
            redirect(base_url() . 'login', 'refresh');

			/**Instatiate CRUD**/
		$crud = new grocery_CRUD();

		/**Set theme to flexigrid**/
		$crud->set_theme('flexigrid');//flexigrid


		/** Grid Subject **/
		$crud->set_subject(get_phrase('countries'));

		/**Select Category Table**/
		$crud->set_table('country');

		/** Set required fields **/
		$crud->required_fields(array("name"));

		/**Select Fields to Show in the Grid **/
		$crud->columns('name');


		/**Callbacks**/
		$crud->callback_after_insert(array($this,'insert_country_audit_parameters')); /** Update Created by, Created Date and Last Modified By Date fields in database**/
		$crud->callback_after_update(array($this,'update_country_audit_parameters')); /** Update last modified by database field ***/
		$crud->callback_delete(array($this,'delete_country')); /** Delete countries with no staff registered **/
		$crud->callback_insert(array($this,'insert_country')); /** Disallow duplicate countries before insert **/
    	$crud->callback_column('staff',array($this,'count_of_staff_country'));

		/** Hide fields from add and edit forms**/
		$crud->columns('name',"staff");
		$crud->add_fields('name');
		$crud->edit_fields('name');

		/** Assign Privileges **/
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"add_country")) $crud->unset_add();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"edit_country")) $crud->unset_edit();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"delete_country")) $crud->unset_delete();

		//Remove the country with ID 1 from listing. This country has been used in the code as a control.
		$crud->where("country_id!=","1");


		$output = $crud->render();
		$page_data['view_type']  = "account";
		$page_data['page_name']  = __FUNCTION__;
        $page_data['page_title'] = get_phrase(__FUNCTION__);
		$output = array_merge($page_data,(array)$output);
        $this->load->view('backend/index', $output);
	}

	function count_of_staff_country($value,$row){
		return $this->db->get_where("user",array("country_id"=>$row->country_id))->num_rows();
	}

	function insert_country($post_array){
		if($this->db->get_where("country",array("name"=>$post_array["name"]))->num_rows() == 0 ){
			return $this->db->insert("country",$post_array);
		}else{
			return $this->db->get_where("country",array("name"=>$post_array["name"]))->row();
		}
	}

	function delete_country($primary_key){
		if($this->db->get_where("user",array("country_id"=>$primary_key))->num_rows() === 0){
			$this->db->where(array("country_id"=>$primary_key));

			return $this->db->delete("country");
		}else{

			return FALSE;
		}
	}

	public function insert_country_audit_parameters($post_array,$primary_key){
		$post_array['created_by'] = $this->session->login_user_id;
		$post_array['created_date'] = date('Y-m-d h:i:s');
		$post_array['last_modified_by'] = $this->session->login_user_id;

		$this->db->where(array("category_id"=>$primary_key));
		$this->db->update("country",$post_array);

		return true;
	}

	public function update_country_audit_parameters($post_array,$primary_key){

		$data['last_modified_by'] = $this->session->login_user_id;

		$this->db->update('country',$data,array('category_id' =>$primary_key));

		return true;
	}

    public function departments($param1="",$param2="",$param3=""){
    	if ($this->session->userdata('user_login') != 1)
            redirect(base_url() . 'login', 'refresh');


			/**Instatiate CRUD**/
		$crud = new grocery_CRUD();

		/**Set theme to flexigrid**/
		$crud->set_theme('flexigrid');//flexigrid


		/** Grid Subject **/
		$crud->set_subject(get_phrase('departments'));

		/**Select Category Table**/
		$crud->set_table('department');

		/** Set required fields **/
		$crud->required_fields(array("name"));

		/**Select Fields to Show in the Grid **/
		$crud->columns('name',"staff");


		/**Callbacks**/
		$crud->callback_after_insert(array($this,'insert_department_audit_parameters'));
		$crud->callback_after_update(array($this,'update_department_audit_parameters'));
		$crud->callback_column("staff",array($this,"count_staff_department")); /** Count number of staff per department **/
		$crud->callback_delete(array($this,"delete_department")); /** Delete department that lacks staff **/
		$crud->callback_insert(array($this,'insert_department')); /** Insert department if name does not exist **/


		/** Hide fields from add and edit forms**/
		$crud->add_fields('name');
		$crud->edit_fields('name');

		/** Assign Privileges **/
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"add_department")) $crud->unset_add();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"edit_department")) $crud->unset_edit();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"delete_department")) $crud->unset_delete();

		//$crud->add_action('More', '', 'demo/action_more','ui-icon-plus');

		$output = $crud->render();
		$page_data['view_type']  = "account";
		$page_data['page_name']  = __FUNCTION__;
        $page_data['page_title'] = get_phrase(__FUNCTION__);
		$output = array_merge($page_data,(array)$output);
        $this->load->view('backend/index', $output);
	}

	function insert_department($post_array){
		if($this->db->get_where("department",array("name"=>$post_array["name"]))->num_rows() == 0 ){
			return $this->db->insert("department",$post_array);
		}else{
			return $this->db->get_where("department",array("name"=>$post_array["name"]))->row();
		}
	}

	function delete_department($primary_key){

		$this->db->join("role","role.role_id=user.role_id");
		$this->db->join("department","department.department_id=role.department_id");
		$this->db->where(array("department.department_id"=>$primary_key));
		$rows = $this->db->get("user")->num_rows();

		if($rows === 0){
			$this->db->where(array("department_id"=>$primary_key));

			return $this->db->delete("department");
		}else{

			return FALSE;
		}
	}

	function count_staff_department($value,$row){
		$this->db->join("role","role.role_id=user.role_id");
		$this->db->join("department","department.department_id=role.department_id");
		$this->db->where(array("department.department_id"=>$row->department_id));
		return $this->db->get("user")->num_rows();
	}

	public function insert_department_audit_parameters($post_array,$primary_key){
		$post_array['created_by'] = $this->session->login_user_id;
		$post_array['created_date'] = date('Y-m-d h:i:s');
		$post_array['last_modified_by'] = $this->session->login_user_id;

		$this->db->where(array("department_id"=>$primary_key));
		$this->db->update("department",$post_array);

		return true;
	}

	public function update_department_audit_parameters($post_array,$primary_key){

		$data['last_modified_by'] = $this->session->login_user_id;

		$this->db->update('department',$data,array('department_id' =>$primary_key));

		return true;
	}

	public function profiles($param1="",$param2="",$param3=""){
		if ($this->session->userdata('user_login') != 1)
	            redirect(base_url() . 'login', 'refresh');

					/**Instatiate CRUD**/
		$crud = new grocery_CRUD();

		/**Set theme to flexigrid**/
		$crud->set_theme('flexigrid');//flexigrid


		/** Grid Subject **/
		$crud->set_subject(get_phrase('profiles'));

		/**Select Category Table**/
		$crud->set_table('profile');

		/** Required Fields **/
		$crud->required_fields(array("name","description","privileges"));

		/** Set relationship n_n **/
		$crud->set_relation_n_n(get_phrase("privileges"), 'access', 'entitlement', 'profile_id', 
		'entitlement_id', 'name','access_id');

		//$crud->set_relation_n_n(get_phrase("members"), 'assign_profile', 'user', 'profile_id',
		//'user_id', '{firstname} {lastname}');

		/** Set drop down fields**/

		$crud->field_type("assignable", "dropdown",array('0'=>get_phrase("none_system_admin_can_assign"),"1"=>get_phrase("only_system_admin_can_assign")));

		/** Assign Privileges **/
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"add_profile")) $crud->unset_add();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"edit_profile")) $crud->unset_edit();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"delete_profile")) $crud->unset_delete();


		$output = $crud->render();
		$page_data['view_type']  = "account";
		$page_data['page_name']  = __FUNCTION__;
        $page_data['page_title'] = get_phrase(__FUNCTION__);
		$output = array_merge($page_data,(array)$output);
        $this->load->view('backend/index', $output);
	}

	public function roles($param1="",$param2="",$param3=""){
	  	if ($this->session->userdata('user_login') != 1)
            redirect(base_url() . 'login', 'refresh');


		try {
				/**Instatiate CRUD**/
		$crud = new grocery_CRUD();

		/**Set theme to flexigrid**/
		$crud->set_theme('flexigrid');//flexigrid


		/** Grid Subject **/
		$crud->set_subject(get_phrase('roles'));

		/**Select Category Table**/
		$crud->set_table('role');

		/** Set required fields **/
		$crud->required_fields(array("name","contribution_id","department"));

		/** Related Tables to Category **/
		$crud->set_relation('contribution','contribution','name');
		$crud->set_relation('department_id','department','name');
		$crud->field_type('is_bt_role','dropdown',array('1' => get_phrase('no'), '2' => get_phrase('yes')));
		//$crud->set_relation_n_n("members", "user", "role_id","role_id","email");
		//$crud->set_relation_n_n("members", "teamset", "user", "team_id", "user_id", "email");
		/**Select Fields to Show in the Grid **/
		$crud->columns(array("name","contribution","department_id",'last_line_manager','is_bt_role'));

		//dropdown for vote_all_in_user_scope and last_line_manager
		$crud->set_relation('last_line_manager','yes_no_option','name');

		/**Give columns user friendly labels**/
		$crud->display_as('contribution_id',get_phrase('contribution'))
		        ->display_as('is_bt_role',get_phrase('is_transnational_role?'))
				->display_as('department_id',get_phrase('department'));

		/**Callbacks**/
		$crud->callback_after_insert(array($this,'insert_role_audit_parameters'));
		$crud->callback_after_update(array($this,'update_role_audit_parameters'));
		//$crud->callback_insert(array($this,'catch_invalid_last_line_manager_and_vote_all_in_scope_choice'));

		/** Hide fields from add and edit forms**/
		$crud->add_fields(array("name","contribution","department_id",'last_line_manager','is_bt_role'));
		$crud->edit_fields(array("name","contribution","department_id",'last_line_manager','is_bt_role'));

		/** Assign Privileges **/
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"add_role")) $crud->unset_add();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"edit_role")) $crud->unset_edit();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"delete_role")) $crud->unset_delete();


		} catch (Exception $e) {
			show_error($e->getMessage() . ' --- ' . $e->getTraceAsString());
		}
		
		//$crud->add_action('More', '', 'demo/action_more','ui-icon-plus');

		$output = $crud->render();
		$page_data['view_type']  = "account";
		$page_data['page_name']  = __FUNCTION__;
        $page_data['page_title'] = get_phrase(__FUNCTION__);
		$output = array_merge($page_data,(array)$output);
        $this->load->view('backend/index', $output);
	}
private  function catch_invalid_last_line_manager_and_vote_all_in_scope_choice($post_array)
{
	if($post_array['last_line_manager']==2 && $post_array['vote_all_in_user_scope']==2)
	{
		return false;
	}
else{
	return $this->db->insert('role',$post_array);
}
	//return true;
}

public function insert_role_audit_parameters($post_array,$primary_key){
		$post_array['created_by'] = $this->session->login_user_id;
		$post_array['created_date'] = date('Y-m-d h:i:s');
		$post_array['last_modified_by'] = $this->session->login_user_id;

		$this->db->where(array("role_id"=>$primary_key));
		$this->db->update("role",$post_array);

		return true;
	}

	public function update_role_audit_parameters($post_array,$primary_key){

		$data['last_modified_by'] = $this->session->login_user_id;

		$this->db->update('role',$data,array('role_id' =>$primary_key));

		return true;
	}

	public function get_country_teams($param1=""){

		$data['teams'] = $this->db->get_where("team",array("country_id"=>$param1))->result_object();

		echo $this->load->view("backend/account/get_country_teams",$data,true);
	}

	public function teams($param1="",$param2="",$param3=""){
	    if ($this->session->userdata('user_login') != 1)
            redirect(base_url() . 'login', 'refresh');

		/**Instatiate CRUD**/
		$crud = new grocery_CRUD();

		/**Set theme to flexigrid**/
		$crud->set_theme('flexigrid');//flexigrid


		/** Grid Subject **/
		$crud->set_subject(get_phrase('teams'));

		/**Select Category Table**/
		$crud->set_table('team');

		$crud->set_relation_n_n("members", "teamset", "user", "team_id", "user_id", "{firstname} {lastname}",null,array("country_id"=>$this->session->country_id));

    //Change status field type to dropdown of elements suspended and active
    $crud->field_type('status','dropdown',array(get_phrase('suspended'),get_phrase('active')));

		/**Change Visibility Field Based on Scope**/
		$user_scope_obj = $this->db->get_where("scope",array("user_id"=>$this->session->login_user_id));
		if($user_scope_obj->num_rows()>0){
			$scope_type = $user_scope_obj->row()->type;
			if($scope_type!=="vote"){
				$crud->where('team.country_id',$this->session->country_id);
					$countries  = $this->crud_model->scope_countries($this->session->login_user_id,true);
						$country_names = array();
						foreach($countries as $country){
							$country_names[$country] = $this->db->get_where("country",array("country.country_id"=>$country))->row()->name;
							if($country!=='1'){
								$crud->or_where('team.country_id',$country);
							}

						}

						$crud->field_type('country_id', 'dropdown',$country_names);


			}else{
				$crud->where('team.country_id',$this->session->country_id);
				$crud->field_type('team.country_id', 'dropdown',array($this->session->country_id=>$this->db->get_where('country',array("country_id"=>$this->session->country_id))->row()->name));
				$crud->set_relation('country_id','country','name',array("country_id"=>$this->session->country_id));

			}
		}else{
			$crud->field_type('team.country_id', 'dropdown',array($this->session->country_id=>$this->db->get_where('country',array("country_id"=>$this->session->country_id))->row()->name));
			/** Show only Categories of the countries user has a scope for **/
			$crud->where('team.country_id',$this->session->country_id);

			/** Related Tables to Category **/
			$crud->set_relation('country_id','country','name',array("country_id"=>$this->session->country_id));

		}

		/** Set required fields **/
		$crud->required_fields(array("name","country_id","description","user_id"));


		/**Select Fields to Show in the Grid **/
		$crud->columns(array("name","country_id","description","staff","members",'status'));


		/**Give columns user friendly labels**/
		$crud->display_as('country_id',get_phrase('country'));


		/**Callbacks**/
		$crud->callback_after_insert(array($this,'insert_team_audit_parameters'));
		$crud->callback_after_update(array($this,'update_team_audit_parameters'));
		$crud->callback_column("staff",array($this,"count_staff_teams"));
		$crud->callback_delete(array($this,"delete_team"));

		/** Hide fields from add and edit forms**/
		$crud->add_fields(array("name","country_id","description"));
		$crud->edit_fields(array("name","country_id","description","members",'status'));

		/** Assign Privileges **/
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"add_team")) $crud->unset_add();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"edit_team")) $crud->unset_edit();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"delete_team")) $crud->unset_delete();

		$output = $crud->render();
		$page_data['view_type']  = "account";
		$page_data['page_name']  = __FUNCTION__;
        $page_data['page_title'] = get_phrase(__FUNCTION__);
		$output = array_merge($page_data,(array)$output);
        $this->load->view('backend/index', $output);
	}


	function delete_team($primary_key){
		$this->db->join("teamset","teamset.user_id=user.user_id");
		$this->db->where(array("team_id"=>$primary_key));
		$users = $this->db->get("user")->num_rows(); /** Check how many users are in the team **/

		//$votes_cast_for_team = $this->db->get_where("tabulate",array())

		if($users === 0){
			$this->db->where(array("team_id"=>$primary_key));

			return $this->db->delete("team");
		}else{

			return FALSE;
		}
	}

	function count_staff_teams($value,$row){

		$this->db->join("teamset","teamset.user_id=user.user_id");
		$this->db->where(array("team_id"=>$row->team_id));
		return $this->db->get("user")->num_rows();
	}


	public function insert_team_audit_parameters($post_array,$primary_key){
		$post_array['created_by'] = $this->session->login_user_id;
		$post_array['created_date'] = date('Y-m-d h:i:s');
		$post_array['last_modified_by'] = $this->session->login_user_id;

		$this->db->where(array("category_id"=>$primary_key));
		$this->db->update("team",$post_array);

		return true;
	}

	public function update_team_audit_parameters($post_array,$primary_key){

		$data['last_modified_by'] = $this->session->login_user_id;

		$this->db->update('team',$data,array('category_id' =>$primary_key));

		return true;
	}

	function add_new_user(){
		if ($this->session->userdata('user_login') != 1)
            redirect(base_url(), 'refresh');

		$page_data['view_type']  =  lcfirst(__CLASS__);
		$page_data['page_name']  = __FUNCTION__;
        $page_data['page_title'] = get_phrase(__FUNCTION__);
        $this->load->view('backend/index', $page_data);
	}

	function edit_user($user_id = ""){
		if ($this->session->userdata('user_login') != 1)
            redirect(base_url(), 'refresh');

		$page_data['user_id'] = $user_id;
		$page_data['view_type']  =  lcfirst(__CLASS__);
		$page_data['page_name']  = __FUNCTION__;
        $page_data['page_title'] = get_phrase(__FUNCTION__);
        $this->load->view('backend/index', $page_data);
	}

  	/** MANAGE USER INFORMATION **/
	public function manage_users($param1="",$param2="",$param3=""){
		if ($this->session->userdata('user_login') != 1)
            redirect(base_url(), 'refresh');

		$msg = get_phrase("failed");

		if($param1==='add_user'){
			$data['firstname'] = $this->input->post('firstname');
			$data['lastname'] = $this->input->post('lastname');
			$data['email'] = $this->input->post('email');
			$data['gender'] = $this->input->post('gender');
			$data['phone'] = $this->input->post('phone');
			$data['employee_id'] = $this->input->post('employee_id');
			$data['role_id'] = $this->input->post('role_id');
			//$data['manage_staff_in_your_country'] = $this->input->post('manage_staff_in_your_country');

			$data['profile_id'] = $this->input->post('profile_id');
			$data['manager_id'] = $this->input->post('manager_id');
			$data['auth'] = "1";
			$data['country_id'] = $this->input->post('country_id');
			$password = substr( md5( rand(100000000,20000000000) ) , 0,7);
			$data['password'] = md5($password);



			//Check if email exists
			$users_with_email = $this->db->get_where('user',array('email'=>$this->input->post('email')))->num_rows();

			if($users_with_email === 0){
				$this->db->insert('user',$data);

				$insert_id = $this->db->insert_id();


				/** Send an Email to the user on success with login instructions here**/

				$this->email_model->manage_account_email($insert_id,"user_invite",$password);

				if($this->db->affected_rows() > 0) $msg = get_phrase("success");
			}

			$this->session->set_flashdata('flash_message',$msg);
			redirect(base_url()."account/manage_users","refresh");
		}

		if($param1==='edit_user'){

			$this->db->where(array('user_id'=>$param2));

			$data['firstname'] = $this->input->post('firstname');
			$data['lastname'] = $this->input->post('lastname');
			$data['email'] = $this->input->post('email');
			$data['gender'] = $this->input->post('gender');
			$data['phone'] = $this->input->post('phone');
			$data['employee_id'] = $this->input->post('employee_id');
			$data['role_id'] = $this->input->post('role_id');
			$data['profile_id'] = $this->input->post('profile_id');
			$data['manager_id'] = $this->input->post('manager_id');
			$data['country_id'] = $this->input->post('country_id');
			//$data['manage_staff_in_your_country'] = $this->input->post('manage_staff_in_your_country');

			$this->db->update('user',$data);

			if($this->db->affected_rows()>0) $msg = get_phrase("success");

			$this->session->set_flashdata('flash_message',$msg);
			redirect(base_url()."account/manage_users","refresh");
		}

    if($param1=="update_profile_info"){
      $data['email'] = $this->input->post("email");

      /** Set deafult message - Failure **/

      $message = get_phrase('failure');

      /** Checking if email exists  **/
      if($this->db->get_where("user",array("email"=>$this->input->post("email"),"user_id"=>$param2))->num_rows() == 0 ){
        $this->db->where(array("user_id"=>$param2));
        $this->db->update("user",$data);
        /** Set success message **/
        if($this->db->affected_rows()>0) $msg = get_phrase("success");

      }


			$this->session->set_flashdata('flash_message',$msg);
			redirect(base_url()."account/manage_users","refresh");
    }

    if($param1 == "change_password"){


      $data['password'] = md5($this->input->post("new_password"));
      $current_password =  $this->input->post("password");
      $new_password = $this->input->post("new_password");
      $confirm_new_password = $this->input->post("confirm_new_password");
      /** Does the Current password exists & Has password been confirmed? **/
      if((md5($current_password)  == $this->db->get_where("user",array("user_id"=>$param2))->row()->password) &&
         ($new_password == $confirm_new_password)){
          $this->db->update("user",$data,array("user_id"=>$param2));

          /** Set success message **/
         if($this->db->affected_rows()>0) $msg = get_phrase("success");
      }

      	$this->session->set_flashdata('flash_message',$msg);
		redirect(base_url()."account/manage_users/","refresh");
    }

	if($param1==="assign_scope"){

			if($this->db->get_where("scope",array("user_id"=>$param2))->num_rows() > 0){

				$scope_id = $this->db->get_where("scope",array("user_id"=>$param2))->row()->scope_id;

				//Delete scope countries

				$this->db->where(array("scope_id"=>$scope_id));
				$this->db->delete("scope_country");

				//Update Scope Record
				$this->db->where(array("scope_id"=>$scope_id));

				$data0["two_way"] = $this->input->post("two_way");
				//$data0["strict"] = $this->input->post("strict");
				$data0['user_id'] = $param2;
				$data0['type'] = $this->input->post("type");
				$this->db->update("scope",$data0);

				//Insert Countries

				$countries  = $this->input->post("country_id");

				foreach($countries as $country){
					$data['country_id'] = $country;
					$data['scope_id'] = $scope_id;

					$this->db->insert("scope_country",$data);
				}
			}else{
				$countries  = $this->input->post("country_id");

				if(sizeof($countries) > 0){
					$data["two_way"] = $this->input->post("two_way");
					//$data["strict"] = $this->input->post("strict");
					$data['user_id'] = $param2;
					$data['type'] = $this->input->post("type");

					$this->db->insert("scope",$data);

					$scope_id = $this->db->insert_id();



					foreach($countries as $country){
						$data2['country_id'] = $country;
						$data2['scope_id'] = $scope_id;

						$this->db->insert("scope_country",$data2);
					}
				}
			}


			//$this->session->set_flashdata('flash_message',get_phrase("success"));
			//redirect(base_url()."account/manage_users/","refresh");
			echo 'success';
			exit();
		}


		if($param1==='user_suspend'){

		      /**  Check current user auth **/

		      $current_auth = $this->db->get_where("user",array("user_id"=>$param2))->row()->auth;


		      if($current_auth  === '0'){
		        $data['auth'] = "1";
						$this->db->where(array('user_id'=>$param2));

						$this->db->update('user',$data);

						/** Set success message **/
		      			if($this->db->affected_rows() > 0 ) $msg = get_phrase('success');
		      } else {
		        $data['auth'] = "0";
						$this->db->where(array('user_id'=>$param2));

						$this->db->update('user',$data);

						/** Set success message **/
		      			if($this->db->affected_rows() > 0 ) $msg = get_phrase('success');
		      }

		      $this->session->set_flashdata('flash_message',$msg);
		      redirect(base_url()."account/manage_users/","refresh");
		}


		if($param1==='user_delete'){

				/**Consider checking on dependants here**/

				$this->db->where(array('user_id'=>$param2));

				$this->db->delete('user');

				if($this->db->affected_rows() > 0 ) $msg = get_phrase('success');

				$this->session->set_flashdata('flash_message',$msg);
		      	redirect(base_url()."account/manage_users/","refresh");
		}

		$this->db->where(array("country_id"=>$this->session->country_id));
		$page_data['users']  = $this->db->get("user")->result_object();

		$user_scope_type = $this->db->get_where("scope",array("user_id"=>$this->session->login_user_id));

		if($user_scope_type->num_rows()>0){
			$type = $user_scope_type->row()->type;
			if($type == "admin" || $type == "both"){
				$scope_country_ids = $this->crud_model->scope_countries($this->session->login_user_id,true);

				foreach($scope_country_ids as $country_ids){
					$this->db->or_where(array("country_id"=>$country_ids));
				}
				$page_data['users']  = $this->db->get("user")->result_object();
			}

		}



		$page_data['user']  = $this->db->get_where("user",array('user_id'=>$this->session->login_user_id))->row();
		$page_data['view_type']  = "account";
		$page_data['page_name']  = "manage_users";
        $page_data['page_title'] = get_phrase("manage_users");
        $this->load->view('backend/index', $page_data);
	}

function authenticate_user_add(){
	 $return_duplicate_false_tag = "0";

	 if($this->input->post('field')){
	 	$field = $this->input->post('field');
	 	$value = $this->input->post('value');
		$rec = $this->db->get_where("user",array($field=>$value));
		$return_duplicate_false_tag = $rec->num_rows()> 0? "0":"1";
	 }

	 echo $return_duplicate_false_tag;
}

public function mail_templates(){
		if ($this->session->userdata('user_login') != 1)
            redirect(base_url(), 'refresh');

		/**Instatiate CRUD**/
		$crud = new grocery_CRUD();

		/**Set theme to flexigrid**/
		$crud->set_theme('flexigrid');//flexigrid


		/** Grid Subject **/
		$crud->set_subject(get_phrase('templates'));

		/**Select Category Table**/
		$crud->set_table('template');

		$crud->callback_edit_field('mail_tags', array($this,"mail_tags_readonly"));	//template_name_readonly
		$crud->callback_edit_field('name', array($this,"template_name_readonly"));
		$crud->callback_edit_field('template_trigger', array($this,"template_trigger_readonly"));
		/** Related Tables to Category **/
		//$crud->set_relation('country_id','country','name');
		//$crud->set_relation('created_by','user','firstname');
		//$crud->set_relation('last_modified_by','user','firstname');


		/** Populate Status Type **/
		//$crud->field_type('status', 'dropdown',array('0'=>"inactive","1"=>"active"));

		/**Select Fields to Show in the Grid **/
		//$crud->columns(array('start_date','end_date','country_id','status'));

		/** Show add/edit fields**/
		//$crud->fields(array('start_date','end_date','status'));


		/** Set required fields **/
		//$crud->required_fields(array('start_date','end_date','country_id','status'));

		/** Set Field Label **/
		//$crud->display_as("country_id",get_phrase("country"));

		/**Callbacks**/
		$crud->callback_after_insert(array($this,'insert_survey_audit_parameters'));
		$crud->callback_after_update(array($this,'update_survey_audit_parameters'));

		/** Assign Privileges **/
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"add_survey")) $crud->unset_add();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"edit_survey")) $crud->unset_edit();
		$crud->unset_delete();


		$output = $crud->render();
		$page_data['view_type']  = "account";
		$page_data['page_name']  = __FUNCTION__;
        $page_data['page_title'] = get_phrase(__FUNCTION__);
		$output = array_merge($page_data,(array)$output);
        $this->load->view('backend/index', $output);
	}

	function manage_profile($param1="",$param2=""){
		if ($this->session->userdata('user_login') != 1)
            redirect(base_url(), 'refresh');



		$page_data['user'] = $this->db->get_where("user",array("user_id"=>$this->session->login_user_id))->result_object();
		$page_data['view_type']  = "account";
		$page_data['page_name']  = __FUNCTION__;
        $page_data['page_title'] = get_phrase(__FUNCTION__);
        $this->load->view('backend/index', $page_data);
	}

	function edit_user_profile(){

		/**Update the password
		* Update the first_login_attepmt field in user table to zero if first_login_attepmt is true
		 * */
		$this->db->where(array('user_id'=>$this->session->login_user_id));
		$data['password'] = md5($this->input->post('password'));
		if($this->session->first_login_attempt) {
			//Update first_login_attempt session to false
			$this->session->set_userdata('first_login_attempt',false);
			$data['first_login_attempt'] = 0;
		}

		$this->db->update('user',$data);

		$return_url = base_url().'surveys/nominate';

		if($this->db->affected_rows() > 0){
			$this->session->set_flashdata('flash_message',get_phrase('password_has_been_changed'));
		}else{
			$this->session->set_flashdata('flash_message',get_phrase('password_has_not_been_changed'));
			$return_url = base_url().'account/manage_profile';
		}


		echo $return_url;

			// $data = array();
//
			// $msg = get_phrase('no_changes_made');
//
			// if(!preg_match('/^[a-f0-9]{32}$/', $this->input->post("password"))){
				// $data['password'] = md5($this->input->post("password"));
			// }
//
			// if($this->db->get_where("user",array("user_id"=>$this->session->login_user_id))->row()->phone !== $this->input->post("phone")){
				// $data['phone'] = $this->input->post("phone");
			// }
//
//
			// if(count($data)>0){
				// $this->db->where(array("user_id"=>$this->session->login_user_id));
				// $this->db->update("user",$data);
				// $msg = get_phrase('update_successful');
			// }
//
			// echo $msg;
		}

	function mail_tags_readonly($value, $primary_key) {
			$tags_array = explode(",", $value);
			$tag_str = "";
			foreach($tags_array as $tag){
				$tag_str .= "<div class='label label-primary'>".$tag."</div>&nbsp;";
			}
			return $tag_str;
	}

	function template_name_readonly($value, $primary_key) {
			return '<span>'.$value.'</span>';
	}

	function template_trigger_readonly($value, $primary_key) {
			return '<span>'.$value.'</span>';
	}

	/** AJAX LOADED CONTENT END**/

	function get_user_role_contribution($role_id){
		echo $this->db->get_where('role',array('role_id'=>$role_id))->row()->contribution;
	}
}
