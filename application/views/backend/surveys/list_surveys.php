<?php

?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Datatable</title>
	<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" />
</head>

<body>

	<div class="form-group">
		
		<div class="col-sm-2">
		    <a href="<?=base_url()?>surveys/add_survey_setting"  class="btn-success form-control" >Add Survey</a>
			<!-- <input type="submit" class="btn-primary form-control" name="Add_Survey" id='Add_survey' value='Add Survey' /> -->
		</div>
	</div>
	<table id="userTable">
		<thead>
			<th>Start Date</th>
			<th>End Date</th>
			<th>Country</th>

			<th>Allow User Edit</th>
			<th>Action on active votes</th>
			<th>Status</th>
			<th>Actions</th>

		</thead>
		<tbody>
			<tr>
				<td>
					01/01/2021-00:00
				</td>

				<td>
					30/06/2021 - 00:00
				</td>

				<td>
					All
				</td>

				<td>
					Yes
				</td>

				<td>
					Force Submit
				</td>

				<td>
					Status
				</td>

				<td>
					edit/delete/results
				</td>

			</tr>
			<!-- <?php if (!empty($arr_users)) { ?>
            <?php foreach ($arr_users as $user) { ?>
                <tr>
                    <td><?php echo $user['first_name']; ?></td>
                    <td><?php echo $user['last_name']; ?></td>
                    <td><?php echo $user['age']; ?></td>
                </tr>
            <?php } ?>
        <?php } ?> -->
		</tbody>
	</table>

	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script type="text/javascript" src="//cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
	<script>
		$(document).ready(function() {
			$('#userTable').DataTable();
		});
	</script>
</body>

</html>