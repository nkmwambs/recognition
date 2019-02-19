<?php
/**
 * Creates an object for users who have a ability to vote or be voted or 
 * administrate the system aoutside the country the user resides in.
 * If the user is not found in this object, then he/she is restricted to 
 * vote or be voted in his/her country, He/has no ability to administrate 
 * outside his country.
 */

$scope = $this->db->get_where("scope",array("user_id"=>$this->session->login_user_id));

?>

<div class="row">

	<div class="col-sm-10">
		
		<?php
			/**
			 * Before initiating a vote, the HRBPs should create special teams.
			 */
			if($this->db->get_where("team",array("country_id"=>$this->session->country_id))->num_rows() === 0){
		?>
						<div class="row">
							<div class="col-sm-12" style="text-align: center;">
								<div class="well"><?=get_phrase("your_HRBP_has_not_set_teams_in_your_country");?></div>
							</div>
						</div>
		
		<?php
			}else{
			/**
			 * Check if an active survey exists. If no show the message there is no 
			 * active survey or else check if a vote has been initiated
			 */ 
			 
			$active_survey = $this->db->get_where("survey",
				array("status"=>"1",'end_date >'=>date('Y-m-d h:m:i')));

			if($active_survey->num_rows() === 0){
		?>
			<div class="row">
				<div class="col-sm-12" style="text-align: center;">
					<div class="well"><?=get_phrase("there_is_no_active_survey");?></div>
				</div>
			</div>
		<?php
			}else{

				/** Check if a vote has been initiated for the current active survey. 
				 * If no, show the start voting button 
				 * 
				 * */

				$survey_result = $this->db->get_where("result",array("user_id"=>$this->session->login_user_id,
				"survey_id"=>$active_survey->row()->survey_id));

				if($survey_result->num_rows() === 0){
		?>
				<div class="row">
					<div class="col-sm-12" style="text-align: center;">
						<a href="<?=base_url();?>surveys/nominate/start_nomination/<?=$this->session->login_user_id;?>" class="btn btn-danger btn-icon"><i class="fa fa-hourglass-start"></i><?=get_phrase("start_voting");?></a>
					</div>
				</div>
		<?php
				}else{

				/** Check if a vote has not been submitted. If submitted check if the survey 
				 * allows edit and show the survey edit link
				 *  Status = 1 => Vote has been submitted, 0=> Not submitted the vote
				 * **/

				$survey_result_voted = $this->db->get_where("result",
				array("user_id"=>$this->session->login_user_id,"survey_id"=>$active_survey->row()->survey_id,
				"status"=>'0'));// Returns not submitted votes
				
				/**
				 * Return number of rows == 0 means the user has submitted their votes
				 */
				
				if($survey_result_voted->num_rows() === 0 ){
		?>
				<div class="row">
					<div class="col-sm-12" style="text-align: center;">
						<div class="well"><?=get_phrase("you_have_already_participated_in_voting");?></div>
						<?php 
								/**
								 * Check if the survey settings allows the user to edit 
								 * the votes already submitted.
								 * If allowed, the user is provided to renominate and vote.
								 */
								 
								if($active_survey->row()->allow_user_edit == '1'){?>
									<a href="<?=base_url();?>surveys/nominate/edit_nomination/<?=$active_survey->row()->survey_id;?>" 
										class="btn btn-primary btn-block"><?=get_phrase('re_do_nomination')?></a>
									<?php }
						?>
					</div>
				</div>
		
				
				<?php		
					}else{

					//print_r($groupings);
				?>

				<!-- Show nomination table if a vote has not been submitted -->

					<table class="table">
						<thead>

						</thead>
						<tbody>
							<?php foreach($groupings as $grouping_id=>$categories){?>
									<tr>
										<td colspan="6" style="background-color:#F5F5F5;font-weight: bolder;text-align: center;">
											<?=$this->crud_model->get_type_name_by_id("grouping",$grouping_id);?>
										</td>
									</tr>
									<tr style="font-style: italic;">
										<td><?=get_phrase("category");?></td>
										<td><?=get_phrase("assignment");?></td>
										<td><?=get_phrase("visibility");?></td>
										<td><?=get_phrase("unit");?></td>
										<td><?=get_phrase("nominate_unit");?></td>
										<td><?=get_phrase("comment");?></td>
									</tr>

									<?php
										/** Populate nominating Units Select form control. Derived from the static table Unit**/
										foreach($categories as $category){
											//Get the table name of the units to nominate Ex. User/ Staff, Department, Team or Country
											$unit_table_name = $this->db->get_where("unit",array("unit_id"=>$category->unit))->row()->name;

											/** Set country scope filter if a user has scope set **/

											if($scope->num_rows() >0 ){
												//Condition string to be used in a query where clause 
												$cond = $this->crud_model->country_scope_where($this->session->login_user_id,$scope->row()->type);
											}


											/** Add Unit filter controls here - Start **/

											if($unit_table_name === "user"){
												/** User Filters Set here
												 *
												 * Users cannot nominate themselves
												 * Cannot vote inactive users
												 * Users can only nominate users in the country and those assigned to the countries with Scope type of Both or Vote and Two Way set to Yes
												 * Users can nominate other country staff if have a scope of either Two Way set as yes or no and Type set as Voting.
												 *
												 * **/

												 /**
												  * Prevent listing yourself to the moninate unit dropdown and Inactive Users
												  * Filter users from other countries with the current user country within their scope and of type not equal to admin and scope two way set as yes
												  * Show scoped users only for categories with visibility set as All i.e. 1
												  * **/

												 /** Set Manager User List here. Find managers categories (assignemnt == 2 ) 
												  * and then set users as the nominees fot the manager**/
												if($category->assignment == '2'){
													/** Only show staff that are managed by the current user for categories that 
													 * require managers contribution **/
													$this->db->where(array("manager_id"=>$this->session->login_user_id));
												}else{
													/** List all staff for the country and those with scope to the country for voting **/
													 $cond2 = "user_id != ".$this->session->login_user_id." AND auth = 1";
													if($category->visibility === '1'){
														$user_ids_query = $this->crud_model->users_with_country_scope_for_voting($this->session->country_id);
														if($user_ids_query !==""){
															$cond2 = $user_ids_query." or ".$cond2;
														}

													}
													$this->db->order_by("country_id,firstname");
													$this->db->where($cond2);
												}

												 /** Prevent listing users from other countries if the current logged user have no scope set**/

												 if($scope->num_rows() > 0){
												 	/** Check if the Scope allows Voting i.e. Not Admin type. Allow voting other country staff if scope type is not admin **/
												 	if($scope->row()->type == "admin"){
												 		$this->db->where(array("country_id"=>$this->session->country_id));
												 	}else{
												 		/** Filter in all countries the current user has a voting scope for but for categories with all countries visibility**/
												 		if($category->visibility === '1'){
												 			$this->db->where($cond);
												 		}else{
												 			$this->db->where(array("country_id"=>$this->session->country_id));
												 		}

												 	}

												 }else{
												 	$this->db->where(array("country_id"=>$this->session->country_id));
												 }


											}

											if($unit_table_name === "team"){
												/** Team Filters Set here
												 * A user can only nominate a team from his or her residence country
												 * A user is not allowed to nominate teams the belong to
												 *
												 * **/


												 /** Only list the current users country teams **/
												 	$cond4 = " country_id = ".$this->session->country_id;
													if($this->crud_model->user_teams_to_vote($this->session->login_user_id) !==""){
														$cond4 = $this->crud_model->user_teams_to_vote($this->session->login_user_id);
													}

													$this->db->order_by("name");
												 	$this->db->where($cond4);


											}

											if($unit_table_name === "department"){
												/** Department Filters Set here**/
													$user_department_id = $this->db->get_where("role",array("role_id"=>$this->session->role_id))->row()->department_id;
													$this->db->order_by("name");
													$this->db->where(array("department_id<>"=>$user_department_id));
											}

											 /** Add Unit filter controls here - End **/

											$units = $this->db->get($unit_table_name)->result_object();

											$options ='<select class="form-control nominate validate" id="'.$category->category_id.'">';
											//if() $options .="<optgroup label='".$this->crud_model->get_type_name_by_id("country",$unit->country_id)."'";
											$options .='<option value="0">'.get_phrase("no_viable_option").'</option>';

											if(count($results) > 0){
												$select_none_viable = "";


													foreach($units as $unit){

														$options_html = "";
															if($unit_table_name === "user"){
																$options_html = $unit->firstname.' '.$unit->lastname.' ['.$this->crud_model->get_type_name_by_id("country",$unit->country_id).']';
															}else{
																$options_html = $unit->name;
															}
														$val = $unit_table_name;
														$id = $unit_table_name.'_id';
														$show_choice = "";
														$selected = "";
														foreach($results as $result){
															if($category->category_id === $result->category_id){
																$unit_trace = $this->db->get_where("unit",array("unit_id"=>$result->nominated_unit))->row()->name;

																if($unit_trace === $unit_table_name && $result->nominee_id !== '0'){
																	$show_choice = $this->db->get_where($unit_table_name,array($id=>$result->nominee_id))->row()->$id;

																}elseif($unit_trace === $unit_table_name && $result->nominee_id === '0') {
																	$show_choice = '0';
																	$select_none_viable ="selected='selected'";

																}

															}
														}


														if($show_choice === $unit->$id ){
															$selected ="selected='selected'";
														}

														$options .= '<option value="'.$unit->$id.'" '.$selected.'>'.$options_html.'</option>';


													}
													$options .='<option value="0" '.$select_none_viable.'>'.get_phrase("no_viable_option").'</option>';
												}else{

													foreach($units as $unit){
														$options_html = "";
															if($unit_table_name === "user"){
																$options_html = $unit->firstname.' '.$unit->lastname.' ['.$this->crud_model->get_type_name_by_id("country",$unit->country_id).']';
															}else{
																$options_html = $unit->name;
															}
														$val = $unit_table_name;
														$id = $unit_table_name.'_id';

														$options .= '<option value="'.$unit->$id.'">'.$options_html.'</option>';

													}

														$options .='<option value="0">'.get_phrase("no_viable_option").'</option>';
												}
											$options .="</select>";

									?>

										<tr>
											<td>
												<a href="#" data-html="true" data-toggle="tooltip" title="<?=$category->description;?>"><?=$category->name;?></a>
											</td>
											<td><?=$this->crud_model->get_type_name_by_id("contribution",$category->assignment);?></td>
											<td><?=$this->crud_model->get_type_name_by_id("country",$category->visibility);?></td>
											<td><?=ucfirst($unit_table_name);?></td>
											<td><?=$options;?></td>
											<?php
												$comment = "";
												if(count($results) > 0){
													foreach($results as $result){
														if($result->category_id === $category->category_id){
															$comment = $result->comment;
														}
													}
												}
											?>
											<td><textarea readonly="readonly" id="comment_<?=$category->category_id;?>" class="form-control validate comment" placeholder="<?=get_phrase("comment_here")?>"><?=$comment;?></textarea></td>
										</tr>
									<?php
										}
									?>

							<?php }?>

							<tr>
								<td colspan="6" style="text-align: center;"><button id="submit_vote"  class="btn btn-success btn-icon"><i class="fa fa-star"></i><?=get_phrase("submit");?></button></td>
							</tr>
						</tbody>
					</table>
		<?php
				}
			}
			}
		}
		?>
	</div>



	<div class="col-sm-2">
		<div class="row">
			<div style="text-align: center;font-style: italic;font-weight: bold;" class="col-sm-12"><?=get_phrase("your_voting_privileges");?></div>
		</div>
		<hr/>
		<div class="row">
			<div style="text-decoration: underline;font-weight: bold;" class="col-sm-12"><?=get_phrase("contribution");?>:</div>


			<div class="col-sm-12"><span style="font-weight: bold;"><?=get_phrase("role");?>:</span> <?=$this->session->role_name;?></div>
			<div class="col-sm-12"><span style="font-weight: bold;"><?=get_phrase("position");?>:</span> <?=$this->db->get_where("contribution",array("contribution_id"=>$this->session->staff_position))->row()->name;?></div>
			<div class="col-sm-12"><span style="font-weight: bold;"><?=get_phrase("department");?>:</span> <?=$this->db->get_where("department",array("department_id"=>$this->db->get_where("role",array("role_id"=>$this->session->role_id))->row()->department_id))->row()->name;;?></div>
				<?php
						$team_array = array();
						$teamset = $this->db->get_where("teamset",array("user_id"=>$this->session->login_user_id))->result_object();
						foreach($teamset as $team){
							$team_array[] = $this->db->get_where("team",array("team_id"=>$team->team_id))->row()->name;
						}

						$team_str = implode(",", $team_array);

						if($team_str==""){
							$team_str = get_phrase("not_set");
						}
						//echo $team_str;
				?>
				<div class="col-sm-12"><span style="font-weight: bold;"><?=get_phrase("teams");?>:</span> <?=$team_str;?></div>
		</div>
		<hr/>
		<div class="row">
			<div style="text-decoration: underline;" class="col-sm-12"><?=get_phrase("scope");?>:</div>
			<?php


				if($scope->num_rows() > 0 ){
			?>
			<div class="col-sm-12"><span style="font-weight: bold;"><?=get_phrase("two_way");?>:</span> <?=$scope->row()->two_way == "1"?get_phrase("yes"):get_phrase("no");?></div>
			<!-- <div class="col-sm-6"><span style="font-weight: bold;"><?=get_phrase("strict");?>:</span> <?=$scope->row()->strict == "1"?get_phrase("yes"):get_phrase("no");;?></div> -->
			<div class="col-sm-12"><span style="font-weight: bold;"><?=get_phrase("type");?>: </span> <?=ucfirst($scope->row()->type);?></div>

			<div class="col-sm-12"><span style="font-weight: bold;"><?=get_phrase("countries");?>:</span>
				<?php
					$countries  = $this->crud_model->scope_countries($this->session->login_user_id,true);
					$country_names = array();
					foreach($countries as $country){
						$country_names[] = $this->db->get_where("country",array("country_id"=>$country))->row()->name;
					}
					echo implode(",", $country_names);
				?>
			</div>

			<?php
				}
			?>

			<div class="col-sm-12"><span style="font-weight: bold;"><?=get_phrase("your_country");?>:</span> <?=$this->crud_model->get_type_name_by_id("country",$this->session->country_id);?>
		</div>
		<hr/>
	</div>
</div>

<script>
$(document).ready(function(){
	$.each($(".nominate"),function(i,el){
			if($(el).val() == "0"){
				$("#comment_"+$(el).attr("id")).val("No viable option");
			}else{
				$("#comment_"+$(el).attr("id")).removeAttr("readonly");
				if($("#comment_"+$(el).attr("id")).val() == 'No viable option'){
					$("#comment_"+$(el).attr("id")).val(" ");
				}
				
			}
	});
});

	$("#submit_vote").click(function(ev){

		var req_validate = $(".validate");
		var cnt = 0;

		$.each(req_validate,function(i,el){
			if($(el).val() === ""){
				cnt++;
				$(el).css("border","1px solid red");
			}
		});

		if(cnt > 0){
			alert("<?=get_phrase("you_have_missing_fields");?>");
		}else{
			var url = "<?=base_url();?>surveys/nominate/submit_vote/<?=$this->session->login_user_id;?>";
			$.ajax({
				url:url,
				success:function(){
					alert("<?=get_phrase("submit_successful");?>");
					window.location.reload();
				},
				error:function(){

				}
			})
		}

		ev.preventDefault();
	});


	$(".nominate").change(function(ev){
		var category_id = $(this).attr('id');
		var nominee_id = $(this).val();
		var user_id = '<?=$this->session->login_user_id;?>';

		var url = "<?=base_url();?>surveys/post_nomination_choice/" + category_id + '/' + nominee_id + '/' + user_id;


		$.ajax({
			url:url,
			success:function(response){
				//alert(response);
				$("#comment_"+category_id).removeAttr("readOnly");
			},
			error:function(){

			}
		});

		ev.preventDefault();
	});

	$(".comment").change(function(ev){
		var comment = $(this).val();
		var id = $(this).attr("id");
		var category_id = id.split("_")[1];
		var comment = $(this).val();
		var user_id = '<?=$this->session->login_user_id;?>';
		//alert(comment);
		var data = {"category_id":category_id,"comment":comment,"user_id":user_id}

		var url = "<?=base_url();?>surveys/post_nomination_comment";


		$.ajax({
			url:url,
			data:data,
			type:"POST",
			success:function(response){


			},
			error:function(){

			}
		});

		ev.preventDefault();

	});

</script>
