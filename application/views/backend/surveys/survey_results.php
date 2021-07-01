
<div class="row">
	
	<div class="col-md-12">
		<div class="panel panel-primary" data-collapsed="0">
        	<div class="panel-heading">
            	<div class="panel-title" >
            		<i class="entypo-book"></i>
					<?php echo get_phrase('survey_results');?>: 
            	</div>
            </div>
			<div class="panel-body">
				
			<div class="row">
				
				
				<!-- <form action="<?=base_url();?>surveys/survey_results/<?=$results[0]->survey_id;?>/search" method="POST"> -->					
				<?php echo form_open(base_url() . 'surveys/survey_results/'.$survey_id , array('id'=> 'frm_search',  'class' => 'form-vertical form-groups-bordered validate','target'=>'_top', 'enctype' => 'multipart/form-data'));?>
					<div class="form-group">
						
						<label class="control-label col-xs-2"><?=get_phrase('results_filters');?></label>
						<?php
							if($user_has_scope){
						?>
						<div class="col-xs-2">
							<select class="form-control" id="" name="country_id">
								<option value="0"><?=get_phrase("select_all_countries");?></option>
								<?php
									$this->db->where($this->crud_model->country_scope_where($this->session->login_user_id));
									$countries = $this->db->get("country");
									if($countries->num_rows() > 0 ){
										foreach($countries->result_object() as $country){
								?>
										<option value="<?=$country->country_id;?>" <?php if($default_country == $country->country_id) echo 'selected'; ?>><?=$country->name;?></option>	
								<?php
										}
									}
								?>
							</select>
						</div>
						<?php
							}
						?>
					
						
						<div class="col-xs-2">
							<select class="form-control" id="" name="unit_id">
								<option value="0"><?=get_phrase('select_all_units');?></option>
								<?php
									foreach($units as $unit){
								?>
									<option value="<?=$unit->unit_id;?>" <?php if($default_unit_id == $unit->unit_id) echo "selected"; ?>><?=ucfirst($unit->name);?></option>
								<?php
									}
								?>
							</select>
						</div>
						
						<div class="col-xs-2">
							<select class="form-control" id="" name="contribution_id">
								<option value="0"><?=get_phrase('select_all_voters');?></option>
								<?php
									foreach($staff_positions as $staff_position){
								?>
									<option value="<?=$staff_position->contribution_id;?>" <?php if($default_staff_position_id == $staff_position->contribution_id) echo "selected"; ?>><?=ucfirst($staff_position->name);?></option>
								<?php
									}
								?>
							</select>
						</div>
						
						<div class="col-xs-2">
							<select class="form-control" id="" name="category_id">
								<option value="0"><?=get_phrase('select_all_categories');?></option>
								<?php
									foreach($all_categories as $grouping_name=>$categories){
								?>
									
									<optgroup label="<?=$grouping_name;?>">
										<?php
											foreach($categories as $category){
										?>
											<option value="<?=$category->category_id;?>" <?php if($default_category_id == $category->category_id ) echo "selected"; ?> ><?=$category->name;?></option>
										<?php
											}
										?>
									</optgroup>
									
								<?php
									}
								?>
							</select>
						</div>
					
					
					<div class="col-xs-2">
						<button class="btn btn-default"><?=get_phrase('search');?></button>
					</div>
					
					</div>	
				</form>
				
				</div>
		
				<hr />
				
				<table class="table table-striped datatable" id="table_export">
					<thead>
						<tr>
							<!-- <th><?=get_phrase("voter_staff_number");?></th>
							<th><?=get_phrase("voting_staff");?></th> -->
							<th><?=get_phrase("voting_staff_country");?></th>
							<th><?=get_phrase("vote_status");?></th>
							<th><?=get_phrase("voting_start_date");?></th>
							<th><?=get_phrase("voting_end_date");?></th>
							<th><?=get_phrase("category");?></th>
							<th><?=get_phrase("nomination_type");?></th>
							<th><?=get_phrase("voting_unit");?></th>
							<th><?=get_phrase("nominee");?></th>
							<th><?=get_phrase("nominee_country");?></th>
							<th><?=get_phrase("comment");?></th>
							
						</tr>
					</thead>
					
					<tbody>
						<?php 
							//if(count($results) > 0){
							foreach($results as $result){
								
						?>
							<tr>
								<!-- <td><?=$this->db->get_where("user",array("user_id"=>$result->user_id))->row()->employee_id;?></td>
								<td><?php $user = $this->db->get_where("user",array("user_id"=>$result->user_id))->row(); echo $user->firstname.' '.$user->lastname;?></td> -->
								<td><?=$this->crud_model->get_type_name_by_id("country",$user->country_id);?></td>
								<td><?=$result->status === '0' ? get_phrase("in_progress"):get_phrase("completed");?></td>
								<td><?=$result->created_date;?></td>
								<td><?=$result->last_modified_date;?></td>
								<td><?=$result->category_name;?></td>
								<td><?=$result->assignment;?></td>
								<td><?=$unit = $result->unit;?></td>
								
									<?php
										$nominee_name = get_phrase("no_viable_option");
										$nominee_country = $this->crud_model->get_type_name_by_id("country",$user->country_id);
											
										if($result->nominee_id > 0){
											$nominee_name = "";
											$nominee_country = $this->crud_model->get_type_name_by_id("country",$user->country_id);
											 if($unit === "user") {
											 	$nominee = $this->db->get_where("user",array("user_id"=>$result->nominee_id))->row(); 
											 	 if(isset($nominee->firstname)){
													$nominee_name = $nominee->firstname.' '.$nominee->lastname;
													$nominee_country = $this->db->get_where("country",array("country_id"=>$nominee->country_id))->row()->name; 	
												 } 
											 }else{
											 	 if($this->db->get_where($unit,array($unit.'_id'=>$result->nominee_id))->num_rows() > 0) 
											 	 	$nominee_name = $this->db->get_where($unit,array($unit.'_id'=>$result->nominee_id))->row()->name;
											 }		
										}
										
										$comment = $result->comment;
																				
										$comment_with_sub_team_exploded = explode("|",$comment);
										
										if(isset($comment_with_sub_team_exploded[1])){
											$nominee_name = $nominee_name.": ".$comment_with_sub_team_exploded[0];
											$comment = $comment_with_sub_team_exploded[1];
										}
										
									?>
								
								<td><?=$nominee_name;?></td>
								<td><?=$nominee_country;?></td>
								<td><?=$comment;?></td>
							</tr>
				
						<?php 
							}	
						?>			
					</tbody>
				</table>
				
			
			</div>
		</div>
	</div>
</div>		



<script>
	$(document).ready(function(){
		//var datatable = $('.table').DataTable();
		
		
		    if (location.hash) {
			        $("a[href='" + location.hash + "']").tab("show");
			    }
			    $(document.body).on("click", "a[data-toggle]", function(event) {
			        location.hash = this.getAttribute("href");
			    });
		
			$(window).on("popstate", function() {
			    var anchor = location.hash || $("a[data-toggle='tab']").first().attr("href");
			    $("a[href='" + anchor + "']").tab("show");
		
		});
		
		
		
		var datatable = $('#table_export').DataTable({
		       //dom: '<Bf><"col-sm-12"rt><ip>',
		       dom: '<"row"l><Bf><"col-sm-12"rt><ip>',
		       pagingType: "full_numbers",
		       buttons: [
		           'csv', 'excel', 'print'
		       ],
		       stateSave: true,
		       lengthMenu: [[25,50, 100, 150,-1], [25,50 ,100,150 ,"All"]],
			   pageLength: 25
		           
		   });
		   
		   
	   
	});
	
</script>