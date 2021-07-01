<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
	//use PHPMailer\PHPMailer\PHPMailer;


/*
 *	@author 	: Nicodemus Karisa
 *	date		: 6th June, 2018
 *	AFR Staff Recognition system
 *	https://www.compassion.com
 *	NKarisa@ke.ci.org
 */

class Surveys extends CI_Controller
{


	function __construct()
	{
		parent::__construct();
		$this->load->database();
        $this->load->library('session');
		$this->load->helper('create_html_tag');

		/** System Feature Session Tag **/
		$this->session->set_userdata('view_type', "surveys");

       /*cache control*/
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');

		if($this->session->first_login_attempt) redirect(base_url() . 'account/manage_profile', 'refresh');

		//$this->send_survey_invitation();
    }

    /***default functin, redirects to login page if no admin logged in yet***/
    public function index()
    {
        if ($this->session->userdata('user_login') != 1)
            redirect(base_url(), 'refresh');

    }



	public function category_groups($param1="",$param2="",$param3=""){
		if ($this->session->userdata('user_login') != 1)
            redirect(base_url(), 'refresh');


		/**Instatiate CRUD**/
		$crud = new grocery_CRUD();

		/**Set theme to flexigrid**/
		$crud->set_theme('flexigrid');//flexigrid


		/** Grid Subject **/
		$crud->set_subject(get_phrase('category_grouping'));

		/**Select Category Table**/
		$crud->set_table('grouping');

		/** Related Tables to Category **/
		$crud->set_relation('created_by','user','firstname');
		$crud->set_relation('last_modified_by','user','firstname');
		$crud->set_relation('unit_id','unit','name',array('is_active'=>1));

		/** Set required fields **/
		$crud->required_fields(array("name","description",'unit_id',"status"));

		/**Select Fields to Show in the Grid **/
		$crud->columns('name','description','unit_id','categories','status');

		/** Populate User Type **/
		$crud->field_type('status', 'dropdown',array('0'=>"inactive","1"=>"active"));

		/** Assign Privileges **/
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"add_grouping")) $crud->unset_add();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"edit_grouping")) $crud->unset_edit();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"delete_grouping")) $crud->unset_delete();


		/** Callbacks**/
		$crud->callback_column('categories',array($this,'count_of_categories_grouping'));
		$crud->callback_delete(array($this,"grouping_check_on_delete"));


		/** Hide fields from add and edit forms**/
		$crud->add_fields(array('name','description','unit_id','status'));
		$crud->edit_fields(array('name','description','unit_id','status'));
		
		$crud->display_as('unit_id',get_phrase('nominee_type'));


		$output = $crud->render();
		$page_data['view_type']  = "surveys";
		$page_data['page_name']  = __FUNCTION__;
        $page_data['page_title'] = get_phrase(__FUNCTION__);
		$output = array_merge($page_data,(array)$output);
        $this->load->view('backend/index', $output);
	}

	function grouping_check_on_delete($primary_key){
		/**Check if grouping it has categories**/
		$count_of_categories = $this->db->get_where("category",array("grouping_id"=>$primary_key));

		if($count_of_categories->num_rows() === 0){
			$this->db->where(array("grouping_id"=>$primary_key));
			return $this->db->delete("grouping");
		}else{
			return false;
		}
	}

	function count_of_categories_grouping($value,$row){
		return $this->db->get_where("category",array("grouping_id"=>$row->grouping_id))->num_rows();
	}

	public function categories($param1="",$param2="",$param3=""){
		if ($this->session->userdata('user_login') != 1)
            redirect(base_url(), 'refresh');


		/**Instatiate CRUD**/
		$crud = new grocery_CRUD();

		/**Set theme to flexigrid**/
		$crud->set_theme('flexigrid');//flexigrid


		/** Grid Subject **/
		$crud->set_subject(get_phrase('category'));

		/**Select Category Table**/
		$crud->set_table('category');


		/** Set required fields **/
		$crud->required_fields(array("name","description","visibility","grouping_id","assignment","unit","status"));

		/** Related Tables to Category **/
		//$crud->set_relation('visibility','country','name');
		$crud->set_relation('grouping_id','grouping','name');
		$crud->set_relation('assignment','contribution','name');
		//$crud->set_relation('visibility','country','name');
		//$crud->set_relation('unit','unit','name');


		/**Change Visibility Drop down field Based on Scope - Add Form**/
		$user_scope_obj = $this->db->get_where("scope",array("user_id"=>$this->session->login_user_id));
		if($user_scope_obj->num_rows()>0){
			$scope_type = $user_scope_obj->row()->type;
			if($scope_type!=="vote"){
				$crud->where('visibility',$this->session->country_id);
				$crud->or_where('visibility',1);
					$countries  = $this->crud_model->scope_countries($this->session->login_user_id,true);
						$country_names = array();
						foreach($countries as $country){
							$country_names[$country] = $this->db->get_where("country",array("country_id"=>$country))->row()->name;
							if($country!=='1'){
								$crud->or_where('visibility',$country);
							}

						}

						$crud->field_type('visibility', 'dropdown',$country_names);

			}else{
				$crud->where('visibility',$this->session->country_id);
				$crud->field_type('visibility', 'dropdown',array($this->session->country_id=>$this->db->get_where('country',array("country_id"=>$this->session->country_id))->row()->name));
			}
		}else{
			$crud->field_type('visibility', 'dropdown',array($this->session->country_id=>$this->db->get_where('country',array("country_id"=>$this->session->country_id))->row()->name));
			/** Show only Categories of the countries user has a scope for **/
			$crud->where('visibility',$this->session->country_id);
		}


		/** Populate Field Types **/
		$crud->field_type('status', 'dropdown',array('0'=>"inactive","1"=>"active"));

		/**Select Fields to Show in the Grid **/
		$crud->columns('name','grouping_id','visibility','assignment',"unit",'has_votes','status');

		/**Give columns user friendly labels**/
		$crud->display_as('name',get_phrase('title'))
				->display_as('grouping_id',get_phrase('category_group'))
				->display_as('visibility',get_phrase('country_visibility'))
				->display_as('assignment',get_phrase('assigned_user_type'))
				->display_as('status',get_phrase('status'));


		/** Assign Privileges **/
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"add_category")) $crud->unset_add();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"edit_category")) $crud->unset_edit();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"delete_category")) $crud->unset_delete();

    	/**Unset Text Editor**/
    	$crud->unset_texteditor(array('description','full_text'));

		/**Callbacks**/
		$crud->callback_before_update(array($this,'validate_if_category_is_updatable'));
		$crud->callback_before_insert(array($this,'insert_unit_id'));
		$crud->callback_after_insert(array($this,'insert_audit_parameters'));
		$crud->callback_after_update(array($this,'update_audit_parameters'));
		$crud->callback_column("has_votes",function ($value,$row){
			$this->db->join("result","result.result_id=tabulate.result_id");
        	return $this->db->get_where("tabulate",array("category_id"=>$row->category_id,"country_id"=>$this->session->country_id))->num_rows() > 0?get_phrase("yes"):get_phrase("no");
    	});
		$crud->callback_column("visibility",function ($value,$row){
        	return $this->db->get_where("country",array("country_id"=>$row->category_id,"country_id"=>$value))->row()->name;
    	});

    	$crud->callback_delete(array($this,"count_votes_on_category_delete"));



		/** Hide fields from add and edit forms**/
		$crud->fields('name','grouping_id','visibility','assignment','status','created_by','created_date','last_modified_by');
		$crud->add_fields('name','description','grouping_id','visibility','assignment','status');
		$crud->edit_fields('name','description','grouping_id','visibility','assignment','status');

		/** Assign Privileges **/
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"add_category")) $crud->unset_add();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"edit_category")) $crud->unset_edit();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"delete_category")) $crud->unset_delete();

		//$crud->add_action('More', '', 'demo/action_more','ui-icon-plus');

		$output = $crud->render();
		$page_data['view_type']  = "surveys";
		$page_data['page_name']  = __FUNCTION__;
        $page_data['page_title'] = get_phrase(__FUNCTION__);
		$output = array_merge($page_data,(array)$output);
        $this->load->view('backend/index', $output);
	}

  function count_votes_on_category_delete($primary_key){
    /** Check if has votes **/
    $has_votes = $this->db->get_where("tabulate",array("category_id"=>$primary_key));

    if($has_votes->num_rows() === 0){
      $this->db->where(array("category_id"=>$primary_key));
      return $this->db->delete("category");
    }else{
      return false;
    }
  }
  
  /**
   * insert_unit_id
   * 
   * This method inserts the unit_id of the catgegory based on group is associated to
   * This is a call_back_method
   * 
   * @param $post_array array : This variable carries the post array of new category post array
   * @return array
   * 
   */
	function insert_unit_id($post_array)
	{
		$grouping_id=$post_array['grouping_id'];
		
		$unit_id=$this->db->get_where('grouping',array('grouping_id'=>$grouping_id))->row()->unit_id;
		
		$post_array['unit']=$unit_id;
		
		return $post_array;
	}
	
	function validate_if_category_is_updatable($post_array,$primary_key)
	{
		$category_record_in_tabulate=$this->db->get_where('tabulate',array('category_id'=>$primary_key))->num_rows();
		
		if($category_record_in_tabulate>0){
			return false;
		
		}
		else{
			$grouping_id=$post_array['grouping_id'];
		
		    $unit_id=$this->db->get_where('grouping',array('grouping_id'=>$grouping_id))->row()->unit_id;
		
			$post_array['unit']=$unit_id;
			return $post_array;
		}
	}
	public function insert_audit_parameters($post_array,$primary_key){
		$post_array['created_by'] = $this->session->login_user_id;
		$post_array['created_date'] = date('Y-m-d h:i:s');
		$post_array['last_modified_by'] = $this->session->login_user_id;

		$this->db->where(array("category_id"=>$primary_key));
		$this->db->update("category",$post_array);

		return true;
	}

	public function update_audit_parameters($post_array,$primary_key){

		$data['last_modified_by'] = $this->session->login_user_id;

		$this->db->update('category',$data,array('category_id' =>$primary_key));

		return true;
	}

  /**
    *Survey settings allows an authorized user to add/edit or delete a insert_survey_audit_parameters
    * The following are the controls in this Feature
    * a) No more than one active survey is allowed at any given time
    * b) A survey cannot be deleted or editted once users start voting
    * c) A survey cannot be mannually closed if it has unsubmitted vote.
    * d) Unsubmitted votes at closure can be forced to be deleted or submmitted depending on the survey setting
    *
  **/

	public function survey_setting(){
		if ($this->session->userdata('user_login') != 1)
            redirect(base_url(), 'refresh');

		/**Instatiate CRUD**/
		$crud = new grocery_CRUD();

		/**Set theme to flexigrid**/
		$crud->set_theme('datatables');//flexigrid


		/** Grid Subject **/
		$crud->set_subject(get_phrase('survey'));

		/**Select Category Table**/
		$crud->set_table('survey');


		/** Related Tables to Category **/
		$crud->set_relation('country_id','country','name');
		$crud->set_relation('created_by','user','firstname');
		$crud->set_relation('last_modified_by','user','firstname');

		/** Populate Status/Allow User Edit Type **/
		$crud->field_type('status', 'dropdown',array('0'=>"inactive","1"=>"active"));
    	$crud->field_type('allow_user_edit', 'dropdown',array('0'=>"no","1"=>"yes"));
		$crud->field_type('action_on_active_votes', 'dropdown',array('0'=>get_phrase('no_action'),"1"=>get_phrase('force_delete'),"2"=>get_phrase("force_submit")));

		/**Select Fields to Show in the Grid **/
		$crud->columns(array('start_date','end_date','country_id',"allow_user_edit","action_on_active_votes",'status'));

		/** Show add/edit fields**/
		$crud->fields(array('start_date','end_date','allow_user_edit','action_on_active_votes','status'));


		/** Set required fields **/
		$crud->required_fields(array('start_date','end_date','country_id','allow_user_edit','action_on_active_votes','status'));

		/** Set Field Label **/
		$crud->display_as("country_id",get_phrase("country"));

		/**Callbacks**/
		$crud->callback_after_insert(array($this,'insert_survey_audit_parameters'));
		$crud->callback_after_update(array($this,'update_survey_audit_parameters'));
	
		$crud->callback_insert(array($this,'survey_check_on_insert'));
		$crud->callback_update(array($this,"survey_check_on_update"));
    	$crud->callback_delete(array($this,"survey_check_on_delete"));
    	$crud->callback_column('votes',array($this,'count_of_votes_survey'));

		/** Assign Privileges **/
		//if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"survey_status")) $crud->add_action(get_phrase('change_status'), '', '', 'ui-icon-shuffle',array($this,'change_survey_status'));
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"add_survey")) $crud->unset_add();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"edit_survey")) $crud->unset_edit();
		if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"delete_survey")) $crud->unset_delete();
		if($this->crud_model->check_profile_privilege($this->session->profile_id,"survey_results")) $crud->add_action(get_phrase('results'), '', '', 'ui-icon-folder-open',array($this,'show_nomination_results'));

		/**Remove the view button**/
		$crud->unset_read();


		$output = $crud->render();
		$page_data['view_type']  = "surveys";
		$page_data['page_name']  = __FUNCTION__;
        $page_data['page_title'] = get_phrase(__FUNCTION__);
		$output = array_merge($page_data,(array)$output);
        $this->load->view('backend/index', $output);
	}

  function change_survey_status($primary_key , $row){
  	/** The Survey Object**/
	$survey = $this->db->get_where("survey",array("survey_id"=>$primary_key))->row();

	/** Count of Unsubmitted Votes in the Survey **/
	$unsubmitted_votes = $this->db->get_where("result",array("survey_id"=>$primary_key,"status"=>0))->num_rows();

	/** Unsubmitted Votes Action on Deactivate **/
	$action = $survey->action_on_active_votes;

	if($survey->status === '1' && $unsubmitted_votes > 0){

		if($action === 0) {
			//Do nothing
		}elseif($action === '1'){
			//Force Delete
			$unsubmitted_votes = $this->db->get_where("result",array("survey_id"=>$primary_key,"status"=>'0'))->result_object();
			foreach($unsubmitted_votes as $to_delete){
				//Delete votes tabulation
				$this->db->where(array("result_id"=>$to_delete->result_id));
				$this->db->delete("tabulate");

				//Delete the Vote Result
				$this->db->where(array("result_id"=>$to_delete->result_id));
				$this->db->delete("result");
			}

		}elseif($action === '2'){
			//Force Submit
			$this->db->where(array('status'=>0));
			$data['status'] = '1';
			$data['forced_submit'] = '1';

			$this->db->update('result',$data);
		}

		$this->db->where(array("survey_id"=>$primary_key));
		$data2['status'] = '0';

		$this->db->update('survey',$data2);

	}elseif($survey->status === '1' && $unsubmitted_votes === '0'){
		//Close the survey
		$data3['status'] = 0;
		$this->db->where(array('survey_id'=>$primary_key));

		$this->db->update("survey",$data3);

	}elseif($survey->status === 0){
		//Check if active survey exists
		$active_surveys = $this->db->get_where("survey",array("status"=>1))->num_rows();
		if($active_surveys === '0'){
			//Activate the Survey
			$data5['status'] = '1';
			$this->db->where(array('survey_id'=>$primary_key));
			$this->db->update("survey",$data5);
		}
	}


  }

  function count_of_votes_survey($value,$row){


		$user_scope_obj = $this->db->get_where("scope",array("user_id"=>$this->session->login_user_id));
		if($user_scope_obj->num_rows()>0){
			$scope_type = $user_scope_obj->row()->type;
			if($scope_type!=="vote"){

				//$this->db->or_where('country_id',1);
					$countries  = $this->crud_model->scope_countries($this->session->login_user_id,true);

						foreach($countries as $country){

							if($country!=='1'){
								$this->db->or_where('country_id',$country);
							}

						}
						$this->db->where(array("survey_id"=>$row->survey_id));
						$this->db->where('country_id',$this->session->country_id);

			}else{
				$this->db->where('country_id',$this->session->country_id);
			}
		}

    	return $this->db->get("result")->num_rows();
  }

  function survey_check_on_delete($primary_key){
    /** Count of Votes for the survey to be deleted **/
    $count_of_votes = $this->db->get_where("result",array("survey_id"=>$primary_key))->num_rows();

    /** Delete if no vote cast **/
    if($count_of_votes === 0){
      return $this->db->delete("survey",array("survey_id"=>$primary_key));
    }else{
      return false;
    }

  }

	function survey_check_on_update($post_array,$primary_key){
    /** Convert Uk-based dates to MySQL Date format **/
    $post_array['start_date'] = date("Y-m-d",strtotime($post_array['start_date']));
    $post_array['end_date'] = date("Y-m-d",strtotime($post_array['end_date']));

    /** Check if an active survey exists  **/
    $check_active_survey = $this->db->get_where("survey",array("status"=>"1"))->num_rows();

    /** Count of Votes for the survey to be deleted **/
    $count_of_votes = $this->db->get_where("result",array("survey_id"=>$primary_key))->num_rows();

    /** Check status of current of the updated survey **/
    $previous_survey_status = $this->db->get_where("survey",array("survey_id"=>$primary_key))->row()->status;

    if($check_active_survey > 0 && $previous_survey_status === "0" && $post_array['status'] = "1" && $count_of_votes > 0){
      return false;
    } else{
      /** Update record if not more than one active survey will be active **/
      $this->db->where(array("survey_id"=>$primary_key));
      return $this->db->update("survey",$post_array);
    }

	}


	function show_nomination_results($primary_key , $row){
		return base_url().'surveys/survey_results/'.$primary_key;
	}

	function survey_check_on_insert($post_array){
		$all_active_surveys = $this->db->get_where("survey",array("status"=>"1"))->num_rows();
print_r($all_active_surveys); exit;

		if($all_active_surveys == 0){
      		$post_array['start_date'] = date("Y-m-d",strtotime($post_array['start_date']));
      		$post_array['end_date'] = date("Y-m-d",strtotime($post_array['end_date']));

			$this->db->insert("survey",$post_array);
			//$this->email_model->send_batch_emails('survey_invite');
		}else{
      		return false;
    	}

	}

	function send_survey_invitation(){
		$valid_for_email_notification_obj = $this->db->get_where('user',array('auth'=>1,"email_notify"=>1));

		$template_trigger = "survey_invite";

		if($valid_for_email_notification_obj->num_rows() > 0){
			foreach($valid_for_email_notification_obj->result_object()  as $user){
				$this->email_model->manage_account_email($user->user_id,$template_trigger);
			}
		}
	}

	function insert_survey_audit_parameters($post_array,$primary_key){

		//Send Invite email
		$this->send_survey_invitation();

		$post_array['created_by'] = $this->session->login_user_id;
		$post_array['created_date'] = date('Y-m-d h:i:s');
		$post_array['last_modified_by'] = $this->session->login_user_id;

		$this->db->where(array("survey_id"=>$primary_key));
		$this->db->update("survey",$post_array);

		return true;
	}

	public function update_survey_audit_parameters($post_array,$primary_key){

		$data['last_modified_by'] = $this->session->login_user_id;

		$this->db->update('survey',$data,array('survey_id' =>$primary_key));

		return true;
	}

/*
*End of Survey settings feature
*/

	function votes(){
	     if ($this->session->userdata('user_login') != 1)
            redirect(base_url(), 'refresh');

		$survey_id = !$this->input->post()?$this->db->get_where('survey',array('status'=>1))->row()->survey_id:$this->input->post('survey_id');

		$this->db->join('survey','survey.survey_id=result.survey_id');
		$this->db->join('country','country.country_id=result.country_id');
		$this->db->select(array('country.name','count("userid") as count','result.status'));
		$this->db->group_by('result.country_id,result.status');
		$results_raw = $this->db->get_where('result',array('survey.survey_id'=>$survey_id))->result_object();

		$results = array();

		foreach($results_raw as $row){
			$status = 'active';
			if($row->status == 1){
				$status = 'submitted';
			}
			$results[$row->name][$status] = $row->count;
		}
        $page_data['selected_survey_id']=$survey_id;
		$page_data['all_surveys']=$this->db->select(array('survey_id','start_date','end_date'))->get('survey')->result_object();
		$page_data['results'] = $results;
        $page_data['page_name']  = "votes";
        $page_data['view_type']  = "surveys";
        $page_data['page_title'] = get_phrase('votes_summary');
        $this->load->view('backend/index', $page_data);
	}

	// public function votes($param1="",$param2="",$param3=""){
		// if ($this->session->userdata('user_login') != 1)
            // redirect(base_url(), 'refresh');
//
		// /**Instatiate CRUD**/
		// $crud = new grocery_CRUD();
//
		// /**Set theme to flexigrid**/
		// $crud->set_theme('flexigrid');//flexigrid
//
//
		// /** Grid Subject **/
		// $crud->set_subject(get_phrase('votes'));
//
		// /**Select Category Table**/
		// $crud->set_table('result');
		// //$crud->set_relation("country_id", "country", "name");
		// /** Use only the user resident country - Only show votes cast for the country or with user admin scope**/
		// $user_scope_obj = $this->db->get_where("scope",array("user_id"=>$this->session->login_user_id));
		// if($user_scope_obj->num_rows() > 0){
			// $scope_type = $user_scope_obj->row()->type;
			// if($scope_type!=="vote"){
					// $countries  = $this->crud_model->scope_countries($this->session->login_user_id,true);
					// $crud->where('country_id',$this->session->country_id);
						// foreach($countries as $country){
//
							// if($country!=='1'){
								// $crud->or_where('country_id',$country);
							// }
//
						// }
//
			// }
//
		// }else{
			// $crud->where('country_id',$this->session->country_id);
		// }
		// //$crud->set_relation("country_id", "country", "name");
//
		// /** SET WHERE **/
		// $survey_id = 0;
		// $survey = $this->db->get_where("survey",array("status"=>"1"));
		// if($survey->num_rows() > 0) $survey_id = $survey->row()->survey_id;
		// $crud->where('survey_id',$survey_id);
//
		// /** Callbacks**/
    	// $crud->callback_before_delete(array($this,'delete_result_tabulation'));
//
		// /** Populate Status Type **/
		// $crud->field_type('status', 'dropdown',array('0'=>"active","1"=>"submitted"));
//
		// /** Assign Privileges **/
		// if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"edit_vote")) $crud->unset_edit();
		// if(!$this->crud_model->check_profile_privilege($this->session->profile_id,"delete_vote")) $crud->unset_delete();
		// $crud->unset_add();
//
//
//
		// /** Show in Edit or Add form**/
		// $crud->columns('user_id','country_id','status');
		// $crud->add_fields('survey_id','user_id','status');
		// $crud->edit_fields('status');
//
//
		// $output = $crud->render();
		// $page_data['view_type']  = "surveys";
		// $page_data['page_name']  = __FUNCTION__;
        // $page_data['page_title'] = get_phrase(__FUNCTION__);
		// $output = array_merge($page_data,(array)$output);
        // $this->load->view('backend/index', $output);
	// }

  function delete_result_tabulation($primary_key){
    /** Count Tabulated results **/
    $tabulated_results = $this->db->get_where("tabulate",array("result_id"=>$primary_key))->num_rows();

    if($tabulated_results > 0){
      $this->db->delete("tabulate",array("result_id"=>$primary_key));
    }

    return true;
  }
  public function do_email($to_emails, $reciever_name=''){
	

	// $mail = new PHPMailer();

	// try {
	// 	//Server settings
	// 	//$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
	// 	$mail->isSMTP();                                            //Send using SMTP
	// 	$mail->Host       = 'smtp.office365.com';                     //Set the SMTP server to send through
	// 	$mail->SMTPAuth   = true;                                   //Enable SMTP authentication
	// 	$mail->Username   = 'afrstaffrecognition@ke.ci.org';//'gtsafrdevteam';                     //SMTP username
	// 	$mail->Password   = 'Comp@ss1on@321!!@**@';//'@Compassion123';                               //SMTP password
	// 	$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption
	// 	$mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
	
	// 	//Recipients
	// 	$mail->setFrom('afrstaffrecognition@ke.ci.org', 'Mailer');
	// 	$mail->addAddress($to_emails,$reciever_name);
	// 	// $mail->addAddress('livingstoneonduso@gmail.com', 'Onduso');     //Add a recipient
	// 	// $mail->addAddress('londuso@ke.ci.org');   //Name is optional
	// 	// $mail->addAddress('nkmwambs@gmail.com'); //Name is optional
	// 	// $mail->addAddress('nkarisa@ke.ci.org');    //Name is optional
	// 	// $mail->addAddress('jmuchori@ke.ci.org'); //Name is optional
	// 	// $mail->addAddress('gkimani@ke.ci.org');  //Name is optional
	// 	//$mail->addReplyTo('info@example.com', 'Information');
	// 	//$mail->addCC('cc@example.com');
	// 	//$mail->addBCC('bcc@example.com');
	
	// 	//Attachments
	// 	//$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
	// 	//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name
	
	// 	//Content
	// 	$mail->isHTML(true);                                  //Set email format to HTML
	// 	$mail->Subject = 'Testing PhpMailer';
	// 	$mail->Body    = 'Recieve EMAIL From <b>Amazon Web Services</b>';
	// 	$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
	
	// 	$mail->send();
	// 	echo 'Message has been sent';
	// } catch (Exception $e) {
	// 	echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
	// }

}
	public function nominate($param1="",$param2="",$param3=""){
		if ($this->session->userdata('user_login') != 1)
            redirect(base_url(), 'refresh');

		$this->do_email('londuso@ke.ci.org');


		$msg = get_phrase("success");

		$survey = $this->db->get_where("survey",array("status"=>"1"));

		if($param1=="start_nomination"){


			$result = $this->db->get_where("result",array("survey_id"=>$survey->row()->survey_id,"user_id"=>$param2));


			if($result->num_rows() === 0){
				$data['survey_id'] = $survey->row()->survey_id;
				$data['user_id'] = $param2;
				$data['country_id'] = $this->db->get_where("user",array("user_id"=>$param2))->row()->country_id;
				$data['status'] = "0";
				$data['created_date'] = date("Y-m-d h:i:s");
				$data['created_by'] = $param2;
				$data['last_modified_by'] = $param2;
				$this->db->insert("result",$data);
				$this->email_model->manage_account_email($this->session->login_user_id,'vote_initiated');
			}else{
				$msg = get_phrase("failed");
			}

			$this->session->set_flashdata('flash_message',$msg);

			redirect(base_url()."surveys".'/'.__FUNCTION__,"refresh");
		}

		if($param1=="submit_vote"){

			$result = $this->db->get_where("result",array("status"=>"0","user_id"=>$param2));

			if($result->num_rows() > 0){
				$this->db->where(array("result_id"=>$result->row()->result_id));
				$data['status'] = "1";
				$this->db->update("result",$data);
				$this->email_model->manage_account_email($this->session->login_user_id,'vote_submitted');

			}else{$msg = get_phrase("failed");}

			$this->session->set_flashdata('flash_message',$msg);

			redirect(base_url()."surveys".'/'.__FUNCTION__,"refresh");
		}

    if($param1=="edit_nomination"){
      $data['status'] = "0";

      $this->db->where(array("survey_id"=>$param2,"user_id"=>$this->session->login_user_id));
      $this->db->update("result",$data);

      $this->session->set_flashdata('flash_message',get_phrase("success"));
      redirect(base_url()."surveys".'/'.__FUNCTION__,"refresh");

    }

		$contribution = $this->session->staff_position;

		$categories = $this->crud_model->categories_in_grouping($this->session->login_user_id,$contribution);

		$page_data['controller_nominees']  = array();

		//$this->db->join('survey','survey.survey_id=result_id');
		$this->db->join('result','result.result_id=tabulate.result_id');

		$nominees_before_voting_object =  $this->db->get_where("tabulate",
			array("result.user_id"=>$this->session->login_user_id,"result.status"=>'0'));


		if($nominees_before_voting_object->num_rows() > 0){
			$page_data['controller_nominees'] = $nominees_before_voting_object->result_object();
		}

		$page_data['controller_groupings'] =(OBJECT) $categories;
		$page_data['contribution'] = $contribution;
		$page_data['view_type']  = lcfirst(__CLASS__);// Returns the name of the class
		$page_data['page_name']  = __FUNCTION__; //Return the name of the method
        $page_data['page_title'] = get_phrase(__FUNCTION__);
        $this->load->view('backend/index', $page_data);
	}

	function post_nomination_choice($category_id="",$nominee_id="",$voting_user_id=""){
		//echo $category_id;
    $this->db->join('survey','survey.survey_id=result.survey_id');
    $result = $this->db->get_where("result",array("user_id"=>$voting_user_id,"survey.status"=>1))->row();
		$category = $this->db->get_where("category",array("category_id"=>$category_id))->row();

		$data['result_id'] = $result->result_id;
		$data['category_id'] = $category_id;
		$data['nominated_unit'] = $category->unit;
		$data['nominee_id'] = $nominee_id;
		$data['nominee_country_id']=$this->session->country_id;
		$data['subteam_manager_id']=-1;
		
		if($category->unit==4){
			
		  $user=$this->db->get_where('user',array('user_id'=>$nominee_id));
			
		  if($user->num_rows()>0){
		  	$data['nominee_country_id']=$user->row()->country_id;
		  }
		}
		$data['created_by'] = $voting_user_id;
		$data['created_date'] = date("Y-m-d h:i:s");
		$data['last_modified_by'] = $voting_user_id;

		if($this->db->get_where("tabulate",array("result_id"=>$result->result_id,"category_id"=>$category_id))->num_rows() === 0){
			$this->db->insert("tabulate",$data);

		}else{
			$data['comment'] = get_phrase('no_viable_option');
			$this->db->where(array("result_id"=>$result->result_id,"category_id"=>$category_id));
			$this->db->update("tabulate",$data);
		}
	}

  function post_subteam_manager_id(){
  	
	//$test=$this->db->get_where("tabulate",array("result_id"=>$result->result_id,"category_id"=>$category_id))->num_rows();
	
    $category_id = $_POST['category_id'];
	$user_id = $_POST['user_id'];
    $manager_id = $_POST['subteam_manager_id'];
   
    $nominee_id = $_POST['nominee_id']; 
	
    $data['subteam_manager_id'] = $manager_id; 
	$data['comment']='';
	 
    //Gets the result of a user for the active survey. This tells the system if the user has began nomination and voting 
    $this->db->join('survey','survey.survey_id=result.survey_id');
    $result = $this->db->get_where("result",array("user_id"=>$user_id,"survey.status"=>1))->row();
	 
	//Update the subteam for that particular category the user is voting on an exiting vote record in tabulate table 
	if($this->db->get_where("tabulate",array("result_id"=>$result->result_id,"category_id"=>$category_id))->num_rows() > 0){
			$this->db->where(array("result_id"=>$result->result_id,"category_id"=>$category_id));
			$this->db->update("tabulate",$data);
		}else{
			$this->post_nomination_choice($category_id,$nominee_id,$user_id);
			$this->db->where(array("result_id"=>$result->result_id,"category_id"=>$category_id));
			$this->db->update("tabulate",$data);
		}
  }

	function post_nomination_comment(){
		$comment = $_POST['comment'];
		$category_id = $_POST['category_id'];
		$user_id = $_POST['user_id'];

		$data['comment'] = $comment;
	//Update the comment in tabulate table for a given user and category  
    $this->db->trans_start();
    $this->db->join('survey','survey.survey_id=result.survey_id');
    $result = $this->db->get_where("result",array("user_id"=>$user_id,"survey.status"=>1))->row();
		$category = $this->db->get_where("category",array("category_id"=>$category_id))->row();
        
		if($this->db->get_where("tabulate",array("result_id"=>$result->result_id,"category_id"=>$category_id))->num_rows() > 0){
				
			$this->db->where(array("result_id"=>$result->result_id,"category_id"=>$category_id));
			$this->db->update("tabulate",$data);
		}
		$this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
        		// generate an error... or use the log_message() function to log your error
        }
		
	}

	public function survey_results($survey_id="",$param2=""){
		if ($this->session->userdata('user_login') != 1)
            redirect(base_url(), 'refresh');

		if($survey_id=="") redirect(base_url().'surveys/survey_setting', 'refresh');

		$survey = array();
		$survey_arr = $this->db->get_where("survey",array("survey_id"=>$survey_id));
		$results = array();
		$set_default_country = $this->session->country_id;
		$set_default_unit_id = 0;
		$set_default_staff_position_id = 0;
		$set_default_category_id = 0;

		if($survey_arr->num_rows() > 0){
			$survey = $survey_arr->row();

			$this->db->where(array("result.survey_id"=>$survey_id));

			if($this->input->post('country_id')){

				$set_default_country = $this->input->post('country_id');
                 
				 //$this->db->where(array("tabulate.nominated_unit<>"=>4));
				$this->db->where(array("survey_id"=>$survey_id,"tabulate.nominee_country_id"=>$set_default_country));

			}else{
				//$this->db->where(array("tabulate.nominated_unit<>"=>4));
				$this->db->where(array("survey_id"=>$survey_id,"tabulate.nominee_country_id"=>$set_default_country));
				
			}

			if($this->input->post('unit_id')){
				$set_default_unit_id = $this->input->post('unit_id');

				if ($this->input->post('unit_id') != 0) {
					//$this->db->where(array("tabulate.nominated_unit<>"=>4));
					$this->db->where(array("tabulate.nominated_unit "=>$set_default_unit_id));
				}
				

			}

			if($this->input->post('contribution_id')){
				$set_default_staff_position_id = $this->input->post('contribution_id');

				if ($this->input->post('contribution_id') != 0){

					$this->db->where(array('category.assignment'=>$this->input->post('contribution_id')));
				}

			}

			if($this->input->post('category_id')){
				$set_default_category_id = $this->input->post('category_id');

				if ($this->input->post('category_id') != 0){

					$this->db->where(array('category.category_id'=>$this->input->post('category_id')));
				}

			}

			$this->db->select(array('result.result_id','result.survey_id','result.user_id','result.country_id','result.status',
			'result.created_date','result.created_by','result.last_modified_by','result.last_modified_date',
			'tabulate.category_id','category.name as category_name','contribution.name as assignment',
			'unit.name as unit','tabulate.nominated_unit','tabulate.nominee_id','tabulate.comment'));


			$this->db->join("tabulate","tabulate.result_id=result.result_id");
			$this->db->join('category','category.category_id=tabulate.category_id');
			$this->db->join("contribution","contribution.contribution_id=category.assignment");
			$this->db->join("unit","unit.unit_id=tabulate.nominated_unit");
			$results = $this->db->get_where("result",array('nominee_id>'=>0))->result_object();

		}

		//Check the scope the logged in user
		$user_scope = $this->crud_model->scope_countries($this->session->login_user_id);

		$page_data['user_has_scope'] = count($user_scope)>0?true:false;
		$page_data['default_unit_id'] = $set_default_unit_id;
		$page_data['default_staff_position_id'] = $set_default_staff_position_id;
		$page_data['default_country'] = $set_default_country;
		$page_data['default_category_id'] = $set_default_category_id;
		$page_data['units'] = $this->db->order_by('unit_id','DESC')->get_where('unit',array('unit_id>'=>1))->result_object();
		$page_data['staff_positions'] = $this->db->order_by('contribution_id','DESC')->get('contribution')->result_object();
		$page_data['all_categories'] = $this->crud_model->categories_in_grouping($this->session->login_user_id,2,true);
		$page_data['results']  =  $results;
		$page_data['survey']  =  $survey;
		$page_data['survey_id'] = $survey_id;
		$page_data['view_type']  = "surveys";
		$page_data['page_name']  = __FUNCTION__;
        $page_data['page_title'] = get_phrase(__FUNCTION__);
        $this->load->view('backend/index', $page_data);
	}
  /*This method gets managers in a department in the country of logged in user
   * Pamerater: department_id
   * Return value: sting
   * Authors: Karisa & Onduso
   * 
   */
  public function get_managers_in_a_department($department_id = ""){
    	
    //Get the all managers
    $managers = $this->crud_model->get_managers()[$department_id];

    $options = "<option value='-1'>".get_phrase('select_subteam')."</option>";
	
    $options .= "<option value='0'>Entire Department</option>";

    foreach ($managers as $manager_id => $team_label) {
      $options .= "<option value='".$manager_id."'>".$team_label."</option>";
    }

    echo $options;
  }
  

}
