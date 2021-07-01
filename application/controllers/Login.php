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


require_once ('vendor/autoload.php');

class Login extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('crud_model');
        $this->load->database();
        $this->load->library('session');
        $this->config->load('sso');

        /* cache control */
    		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    		$this->output->set_header('Pragma: no-cache');
    		//$this->db->cache_on();
    		//$this->output->cache(60);
    		$this->db->cache_delete_all();
    }

    //Default function, redirects to logged in user area
    public function index() {

        if ($this->session->userdata('user_login') == 1 && !$this->session->first_login_attempt) {
        	redirect(base_url() . 'surveys/nominate', 'refresh');
        }elseif($this->session->first_login_attempt && $this->session->userdata('user_login') == 1){
        	redirect(base_url() . 'account/manage_profile', 'refresh');
        }

        if($this->db->get_where('settings',array('type'=>'ssoservice_activated'))
        ->row()->description == 1 && !$this->session->sso_login){
            redirect($this->config->item('idpEntity'),'refresh');
        }

        if($this->session->sso_login == 1){
          $this->logout();
        }else{
          $this->load->view('backend/login');
        }

    }

    //Ajax login function
    function ajax_login() {
        $response = array();

        //Recieving post input of email, password from ajax request
        $email = $_POST["email"];
        $password = $_POST["password"];
        $response['submitted_data'] = $_POST;

        //Validating login
        $login_status = $this->validate_login($email, $password);
        $response['login_status'] = $login_status;
        if ($login_status == 'success') {
            $response['redirect_url'] = '';
        }

        //Replying ajax request with validation response
        echo json_encode($response);
    }

  function getSSOService($encrypted_email){

      $this->session->set_userdata('sso_login', '1');

      $email = \Base32\Base32::decode(urldecode($encrypted_email));

      if($this->validate_login($email) == 'success'){

        redirect(base_url().'login', 'refresh');

      }else{
         $this->load->view('backend/sso_error_message');
      }

  }

	function create_user_session ($row,$first_login_attempt = false){

			$role = $this->db->get_where("role",array("role_id"=>$row->role_id))->row();

		    $this->session->set_userdata('user_login', '1');
		    $this->session->set_userdata('login_user_id', $row->user_id);
		    $this->session->set_userdata('name', $row->firstname);
			$this->session->set_userdata('login_type', $role->name);
			$this->session->set_userdata('role_name', $role->name);
			$this->session->set_userdata('profile_id', $row->profile_id);
			$this->session->set_userdata('manager_id', $row->manager_id);
			$this->session->set_userdata('country_id', $row->country_id);
			$this->session->set_userdata('role_id', $row->role_id);
			$this->session->set_userdata('staff_position', $role->contribution);
			$this->session->set_userdata('staff_position_name', $this->crud_model->get_type_name_by_id('contribution',$role->contribution));
			$this->session->set_userdata('department_id', $role->department_id);
			$this->session->set_userdata('department_name', $this->crud_model->get_type_name_by_id('department',$role->department_id));
			$this->session->set_userdata('profile_name', $this->crud_model->get_type_name_by_id('profile',$row->profile_id));
			$this->session->set_userdata('country_name',$this->crud_model->get_type_name_by_id("country",$row->country_id));

      $this->session->set_userdata('manage_staff_in_your_country', 0);
      //$this->session->set_userdata('manage_staff_in_your_country', $row->manage_staff_in_your_country);
			//$this->session->set_userdata('vote_all_in_user_scope',$role->vote_all_in_user_scope);
			$this->session->set_userdata('last_line_manager',$role->last_line_manager);
			$this->session->set_userdata('is_bt_role', $role->is_bt_role==2?true:false);

			$this->session->set_userdata('first_login_attempt',$first_login_attempt);

			return 'success';
	}
    //Validating login from ajax request
    function validate_login($email = '', $password = '') {
        $credential = array('email' => $email,"auth"=>1,"password"=>md5($password),'first_login_attempt'=>0);
      if($this->session->sso_login == '1'){
          $credential = array('email' => $email,"auth"=>1);
      }



        // Checking login credential for admin
        $query = $this->db->get_where('user', $credential);

        if ($query->num_rows() > 0) {
			$row = $query->row();
		  	return $this->create_user_session($row);

        }else{

			$split_email = explode("@", $email);

        	$query = $this->db->get_where('user', array('email'=>$email,'first_login_attempt'=>1));

			if($query->num_rows() > 0 && strtolower($split_email[0]) == strtolower($password)){
				$row = $query->row();
				return $this->create_user_session($row,true);
			}
        }

		/**
		 * If the if conditions above are not met, the method returns invalid back to the calling
		 * ajax method bound to the key $login_status = invalid
		 * Invalid success returns to the login page.
		 **/
        return 'invalid';
    }

    /*     * *DEFAULT NOR FOUND PAGE**** */

    function four_zero_four() {
        $this->load->view('four_zero_four');
    }

    // PASSWORD RESET BY EMAIL
    function forgot_password()
    {
        $this->load->view('backend/forgot_password');
    }

    function ajax_forgot_password()
    {
        $resp                   = array();
        $resp['status']         = 'false';
        $email                  = $_POST["email"];
        //$reset_account_type     = '';
        //resetting user password here
        $new_password           =   substr( md5( rand(100000,200000) ) , 0,7);

        // Checking credential for user
        $query = $this->db->get_where('user' , array('email' => $email));
        if ($query->num_rows() > 0)
        {

            //Set the first_login_attempt to 0 when the user requests for a password reset
            //Update the password

            $this->db->where('email' , $email);
            $this->db->update('user' , array('password' => md5($new_password),'first_login_attempt'=>0));
            $resp['status']         = 'true';


        }


        // send new password to user email
        $this->email_model->password_reset_email($new_password , $email);
        //$this->email_model->manage_account_email($query->row()->user_id,"password_reset",true,$new_password);

        $resp['submitted_data'] = $_POST;

        echo json_encode($resp);
    }

    /*     * *****LOGOUT FUNCTION ****** */

    function logout() {
      if($this->session->sso_login == 1){
        $this->session->sess_destroy();
        redirect('https://compassion.okta.com/', 'refresh');
      }else{
        $this->session->sess_destroy();
        $this->session->set_flashdata('logout_notification', 'logged_out');
        redirect(base_url(), 'refresh');
      }

    }

}
