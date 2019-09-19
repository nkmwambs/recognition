<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Email_model extends CI_Model {

	private $to = "";
	private $from = "";
	private $msg = "";
	private $sub = "";

	function __construct()
    {
        parent::__construct();
		$this->load->database();

		if($this->from == NULL)
				$this->from		=	$this->db->get_where('settings' , array('type' => 'system_email'))->row()->description;

    }

	function account_opening_email($account_type = '' , $email = '', $fsockopen = true)
	{
		$system_name	=	$this->db->get_where('settings' , array('type' => 'system_name'))->row()->description;
		$query			=	$this->db->get_where(users , array('email' => $email));

		$email_msg		=	"Dear ".$query->row()->firstname.",<br />";
		$email_msg		.=	"Welcome to ".$system_name."<br />";
		$email_msg		.=	"Your account type : ".$account_type."<br />";
		$email_msg		.=	"Your login password : ".$this->db->get_where("user" , array('email' => $email))->row()->password."<br />";
		$email_msg		.=	"Login Here : ".base_url()."<br />";

		$this->msg     	= 	$email_msg;
		$this->sub		=	"Account opening email";
		$this->to		=	$email;

		//$this->do_email($email_msg , $email_sub , $email_to);
		if($fsockopen) return $this->do_email(); else 	return $this->debugger();
	}

	function password_reset_email($new_password = '' , $email = '' , $fsockopen = true)
	{
		$query			=	$this->db->get_where(user , array('email' => $email));
		if($query->num_rows() > 0)
		{

			$this->msg	=	"Dear ".$query->row()->firstname.",<br />";
			$this->msg	.=	"Your password is : ".$new_password."<br />";

			$this->sub	=	"Password reset request";
			$this->to	=	$email;

			if($fsockopen) return $this->do_email(); else 	return $this->debugger();

			return true;
		}
		else
		{
			return false;
		}
	}

	//Log user email sent history
	// private function sent_emails_log ($user_id,$template_trigger,$errors = "Success"){
	// 	$template = $this->db->get_where("template",
	// 	array("template_trigger"=>$template_trigger));
	//
	// 	$template_id = $template->row()->template_id;
	//
	// 	//Check if the log exists with the user_id and template_id
	//
	// 	$log_exists_obj = $this->db->get_where('sent_emails_log',
	// 	array('user_id'=>$user_id,'template_id'=>$template_id));
	//
	// 	if($log_exists_obj->num_rows()>0){
	// 		$data['created_date'] = date('Y-m-d h:i:s');
	// 		$data['errors'] = $errors;
	// 		$this->db->where(array('user_id'=>$user_id,'template_id'=>$template_id));
	// 		$this->db->update('sent_emails_log',$data);
	// 	}else{
	// 		$data['errors'] = $errors;
	// 		$data['user_id'] = $user_id;
	// 		$data['template_id'] = $template_id;
	// 		$data['created_date'] = date('Y-m-d h:i:s');
	//
	// 		$this->db->insert('sent_emails_log',$data);
	// 	}
	//
	// }

	/*** Mail Templates  ***/

	function manage_account_email($user_id,$template_trigger,$fsockopen = true,$password = ""){

		//Template subject and body
		$template = $this->db->get_where("template",array("template_trigger"=>$template_trigger));
		$template_subject = $template->row()->template_subject;
		$template_body = $template->row()->template_body;

		//User Names/ System name/ User Email/ Password
		$user = $this->db->get_where("user",array("user_id"=>$user_id))->row();
		$tags['{user}'] = $user->firstname." ".$user->lastname;
		$tags['{system_name}'] = $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;
		$tags['{user_email}'] = $user->email;
		$tags['{user_password}'] = $password;

		//User role
		$role_obj = $this->db->get_where("role",array("role_id"=>$user->role_id));
		$tags['{user_role}'] = $role_obj->row()->name;

		//User profile/site url/ admin email
		$tags['{user_profile}'] = $this->db->get_where("profile",array("profile_id"=>$user->profile_id))->row()->name;
		$tags['{site_url}'] = base_url();
		$tags['{system_admin_email}'] = $this->db->get_where('settings' , array('type'=>'system_email'))->row()->description;

		//User Scope
		$this->db->join('country','country.country_id=scope_country.country_id');
		$this->db->join('scope','scope.scope_id=scope_country.scope_id');
		$scope = $this->db->select(array('country.name'))->get_where('scope_country',array('user_id'=>$user_id))
		->result_array();
		$countries = array_column($scope, 'name');
		$tags['{system_user_scope}'] = implode(",", $countries);

		//User Teams
		$this->db->join('team','team.team_id=teamset.team_id');
		$team_result = $this->db->select(array('team.name'))->get_where('teamset',array('user_id'=>$user_id))
		->result_object();
		$teams = array_column($team_result, 'name');
		$tags['{user_team}'] = implode(",", $teams);

		//User department
		$tags['{user_department}'] = $this->db->get_where('department',
		array('department_id'=>$role_obj->row()->department_id))->row()->name;

		//User status
		$tags['{user_status}'] = $user->auth = 1?get_phrase('active'):get_phrase('suspended');

		//Survey Start/ End date
		$active_survey_obj = $this->db->get_where('survey',array('status'=>1));
		$tags['{survey_start_date}'] = get_phrase('no_active_survey');
		$tags['{survey_end_date}'] = get_phrase('no_active_survey');

		if($active_survey_obj->num_rows() > 0){
			$start_date = date('jS F Y h:m:i',strtotime($active_survey_obj->row()->start_date));
			$end_date = date('jS F Y h:m:i',strtotime($active_survey_obj->row()->end_date));
			$tags['{survey_start_date}'] 	= $start_date;
			$tags['{survey_end_date}'] 		= $end_date;
		}

		//User nominated categories
		$nominee_obj = $this->db->select('category_id')->get_where('tabulate',array('nominee_id'=>$user_id));

		$tags['{nominated_categories}'] = "None";

		if($nominee_obj->num_rows() > 0){

			$this->db->join('result','result.result_id=tabulate.result_id');
			$this->db->join('category','category.category_id=tabulate.category_id');
			$category_result = $this->db->select(array('category.name'))->group_by('category.name')
			->get_where('tabulate',array('nominee_id'=>$user_id,'nominated_unit'=>4,'survey_id'=>$active_survey_obj->row()->survey_id))
			->result_array();

			$categories = array_column($category_result, 'name');

			$list = "<ul>";
				foreach($categories as $row){
					$list .= "<li>".$row."</li>";
				}
			$list .= "</ul>";

			$tags['{nominated_categories}'] = $list;//implode(",", $categories);
		}

		//Surveys days remaining
		$datetime1 = date_create(date('Y-m-d'));
		$datetime2 = date_create($active_survey_obj->row()->end_date);
		$interval = date_diff($datetime1, $datetime2);

		$tags['{days_to_go}'] = $interval->format('%r%a');

		/**
		 * 	$a = array( 'truck', 'vehicle', 'seddan', 'coupe' );
		 *	$str = 'Honda is a truck. Toyota is a vehicle. Nissan is a sedan. Scion is a coupe.';
		 *	echo str_replace($a,'car',str_replace('Lexus','Toyota',$str));
		 * echo str_replace($a,'car',$str);
		 */

		//Replace tags into the template body
		$tag_keys = array_keys($tags);
		$tag_values = array_values($tags);

		//Assign values to the class properties (msg, sub, to)
		$this->msg = str_replace($tag_keys,$tag_values,$template_body);
		$this->sub = str_replace($tag_keys,$tag_values,$template_subject);
		$this->to	=	$user->email;

		//Call return methods
		if($fsockopen) {
			if($user->email_notify == 1){
				return $this->do_email($user_id,$template_trigger);
			}else{
				return "Mail not sent. User switch notifications off";
			}

		}else{
			return $this->debugger();
		}

 	}

	//Sending batch email to users with email notify set to on

	function send_batch_emails($template_trigger = ""){
		$users  = $this->db->get_where("user",array("auth"=>1,"email_notify"=>1))->result_object();

		foreach($users as $user){
			$this->manage_account_email($user->user_id,$template_trigger);
		}
	}

	/***custom email sender****/
	function do_email()
	{
			$config = array();
	        $config['useragent']	= "CodeIgniter";
	        $config['mailpath']		= "/usr/bin/sendmail"; // or "/usr/sbin/sendmail"
	        $config['protocol']		= "smtp";
	        $config['smtp_host']	= "10.8.1.127";
	        $config['smtp_port']	= "25";
	        $config['mailtype']		= 'html';
	        $config['charset']		= 'utf-8';
	        $config['newline']		= "\r\n";
	        $config['wordwrap']		= TRUE;

	        $this->load->library('email');

	        $this->email->initialize($config);

			$system_name	=	$this->db->get_where('settings' , array('type' => 'system_name'))->row()->description;

			$this->email->from($this->from, $system_name);
			//$this->email->from($from, $system_name);
			$this->email->to($this->to);
			$this->email->subject($this->sub);

			$msg	=	$this->msg."<br /><br /><br /><br /><br /><br /><br /><hr /><center><a href=\"https://www.compassion-africa.org\">&copy; 2018 ".get_phrase("AFR_staff_recognition_system")."</a></center>";
			$this->email->message($msg);

			$this->email->send();

			return "Mail Sent";//echo $this->email->print_debugger();

	}

	function debugger(){
		$str = "From: ".$this->from."</br>";
		$str .= "To: ".$this->to."</br>";
		$str .= "<p></p>";
		$str .= "Subject: ".$this->sub."</br>";
		$str .= "<p></p>";
		$str .= $this->msg."</br>";

		return $str;
	}
}
