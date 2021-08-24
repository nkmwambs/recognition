<div class="row">
	<div class="row">
		<div class="col-sm-12 inner-progress"></div>
	</div>
	<div class="col-md-12">

    	<!------CONTROL TABS START------>
		<ul class="nav nav-tabs bordered">

			<li class="">
            	<a href="#list" data-toggle="tab"><i class="entypo-user"></i>
					<?php echo get_phrase('manage_profile');?>
                    	</a>
            </li>

			<li class="">
            	<a href="#password" data-toggle="tab"><i class="entypo-lock"></i>
					<?php echo get_phrase('change_password');?>
                    	</a>
            </li>


            <li class="active">
            	<a href="#users" data-toggle="tab"><i class="fa fa-user-plus"></i>
					<?php echo get_phrase('manage_users');?>
                    	</a>
            </li>



		</ul>
    	<!------CONTROL TABS END------>


		<div class="tab-content">

			<div class="tab-pane box" id="list" style="padding: 5px">
               <div class="box-content">
					<?php
                     	echo form_open(base_url() . 'account/manage_users/update_profile_info/'.$user->user_id  , array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top' , 'enctype' => 'multipart/form-data'));
                    ?>

                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('first_name');?></label>
                                <div class="col-sm-5">
                                    <input type="text" readonly="readonly" class="form-control"  value="<?php echo $user->firstname;?>"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('last_name');?></label>
                                <div class="col-sm-5">
                                    <input type="text" readonly="readonly" class="form-control"  value="<?php echo $user->lastname;?>"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('email');?></label>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" name="email" value="<?php echo $user->email;?>"/>
                                </div>
                            </div>

                            <div class="form-group">
                              <div class="col-sm-offset-3 col-sm-5">
                                  <button type="submit" class="btn btn-info"><?php echo get_phrase('update_profile');?></button>
                              </div>
								</div>
                        </form>
             </div>
			</div>

			<!--End Profile -->

			<div class="tab-pane box" id="password" style="padding: 5px">
               <div class="box-content padded">
					<?php
                     	echo form_open(base_url() . 'account/manage_users/change_password/'.$user->user_id , array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top'));
                     ?>
                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('current_password');?></label>
                                <div class="col-sm-5">
                                    <input type="password" class="form-control" name="password" value=""/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('new_password');?></label>
                                <div class="col-sm-5">
                                    <input type="password" class="form-control" name="new_password" value=""/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo get_phrase('confirm_new_password');?></label>
                                <div class="col-sm-5">
                                    <input type="password" class="form-control" name="confirm_new_password" value=""/>
                                </div>
                            </div>
                            <div class="form-group">
                              <div class="col-sm-offset-3 col-sm-5">
                                  <button type="submit" class="btn btn-info"><?php echo get_phrase('update_profile');?></button>
                              </div>
								</div>
                        </form>
             </div>
			</div>

            <!-- END PASSWORD --->

			          <!--Manage Users-->
            <div class="tab-pane box active" id="users" style="padding: 5px">
            	<div class="add_user">
					<a href="<?=base_url();?>account/add_new_user" class="btn btn-primary pull-right">
						<i class="entypo-plus-circled"></i>
							<?php echo get_phrase('add_user');?>
					</a>
				</div>
				<br><hr>
				<div class='row'>
					<div class='col-xs-12'>
						<div id='response_message'></div>
					</div>
				</div>
				<?php echo form_open('' , array('id'=>'form-filter','class' => 'form-vertical form-groups-bordered validate', 'enctype' => 'multipart/form-data'));?>

				<table class="table table-striped" id="table_export">
					<thead>
						<tr>
							<th><?php echo get_phrase('action');?></th>
							<th><?php echo get_phrase('user_name');?></th>
							<th><?php echo get_phrase('email');?></th>
							<th><?php echo get_phrase('country');?></th>
							<th><?php echo get_phrase('role');?></th>
							<th><?php echo get_phrase('manager');?></th>
							<th><?php echo get_phrase('profile');?></th>
							<th><?php echo get_phrase('status');?></th>
						</tr>
					</thead>
					
				</table>
				</form>
         </div>

		</div>
	</div>

</div>

<script>

    
	$(document).ready(function(){
		
		   

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
		   
		   
		var datatable = $('#table_export,table.display').DataTable({
		       dom: '<Bf><"col-sm-12"rt><ip>',
		       //sDom:'r',
		       pagingType: "full_numbers",
		       buttons: [
		           'csv', 'excel', 'print'
		       ],
		       stateSave: true,
		       oLanguage: {
			        sProcessing: "<img src='<?php echo base_url();?>uploads/preloader4.gif'>"
			    },
			   processing: true, //Feature control the processing indicator.
		       serverSide: true, //Feature control DataTables' server-side processing mode.
		       order: [], //Initial no order.
		
		       // Load data for the table's content from an Ajax source
		       "ajax": {
		           "url": "<?php echo base_url();?>account/ajax_list",
		           "type": "POST",
		           "data": function(data){
		           		var x = $("#form-filter").serializeArray();

					    $.each(x, function(i, field){
					            data[field.name] = field.value;
					     });
		           }
		       },
		
		       
		   });


   
	});
	
	
</script>
