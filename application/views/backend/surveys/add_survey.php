<?php echo form_open('', array('class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

<hr />
<div class="form-group">
    <label for="" class="control-label col-sm-2">Start Date *:</label>
    <div class="col-sm-6">
        <input type="text" class="form-control datepicker" name="start_date" id="start_date" readonly="readonly" data-format="yyyy-mm-dd" />
    </div>
</div>

<div class="form-group">
    <label for="" class="control-label col-sm-2">End Date*:</label>
    <div class="col-sm-6">
        <input type="text" class="form-control datepicker" name="end_date" id="end_date" readonly="readonly" data-format="yyyy-mm-dd" />
    </div>
</div>


<div class="form-group">
    <label for="" class="control-label col-sm-2">Allow user edit* :</label>
    <div class="col-sm-6">

        <select class="form-control" name="allow_user_edit" id="allow_user_edit" readonly="readonly">
            <option value="0">Select allow user edit</option>
            <option value="1">Yes</option>
            <option value="2">No</option>
        </select>
    </div>
</div>



<div class="form-group">
    <label for="" class="control-label col-sm-2">Action on active votes* :</label>
    <div class="col-sm-6">
        <select class="form-control" name="action_on_active_votes" id="action_on_active_votes" readonly="readonly">
            <option value="0">Select action active votes</option>
            <option value="1">No action</option>
            <option value="2">Force Delete</option>
            <option value="3">Force Submit</option>
        </select>
    </div>
</div>

<div class="form-group">
    <label for="" class="control-label col-sm-2">Status* :</label>
    <div class="col-sm-6">
        <select class="form-control" name="status" id="status" readonly="readonly">
            <option value="0">Select status</option>
            <option value="1">Active</option>
            <option value="2">Inactive</option>
        </select>
    </div>
</div>

<div class="form-group">

    <div class="col-sm-2">
        <input type="submit" class="btn-success form-control" name="Save" id='save' value='Save' />
    </div>
    <div class="col-sm-2">
        <input type="submit" class="btn-success form-control" name="Save_and_list" id='save_and_list' value='Save and list' />
    </div>
</div>

</form>
<script>



$('#save').on('click',function(){

    var start_date=$('#start_date').val();

    var end_date=$('#end_date').val();

    var allow_user_edit=$('#allow_user_edit').val();

    var action_on_active_votes=$('#action_on_active_votes').val();

    
    var status=$('#status').val();

    var url='<?=base_url()?>surveys/add_new_survey';

    var data = {
               'start_date':start_date,
               'end_date':end_date,
                'allow_user_edit':allow_user_edit,
                'action_on_active_votes':action_on_active_votes,
                'status':status
            };

    $.post(url, data, function(response){ 
      alert(response);
     // $("#mypar").html(response.amount);
});
});



// function post_using_ajax() {
// 		var frm = $("#frm_voucher");
// 		var postData = frm.serializeArray();
// 		var formURL = "<?= base_url() ?>ifms.php/partner/post_voucher/";
// 		$.ajax({
// 			url: formURL,
// 			type: "POST",
// 			data: postData,
// 			beforeSend: function() {
// 				$('#error_msg').html('<div style="text-align:center;"><img style="width:60px;height:60px;" src="<?php echo base_url(); ?>uploads/preloader4.gif" /></div>');
// 			},
// 			success: function(data, textStatus, jqXHR) {

// 				$('#load_voucher').html(data);

// 				//$('#voucher_count').html(parseInt($('#voucher_count').html())+1);

// 			},
// 			error: function(jqXHR, textStatus, errorThrown) {
// 				//if fails
// 				alert(textStatus + ' Test');
// 			}
// 		});

// 	}

    </script>