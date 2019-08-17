<?php 

// ---- PAGE SETUP ----
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

if(!$staff) $error = exit_if_error("Please log in", "/index.php");
elseif($staff->roleID < $role["lead"])
	exit_if_error("You are not authorized to see this ticket", "/index.php");


$offline_transactions = array();
if($results = $mysqli->query("	
								SELECT *
								FROM `transactions`
								JOIN `offline_transactions`
								ON `offline_transactions`.`trans_id` = `transactions`.`trans_id`
								JOIN `devices`
								ON `devices`.`d_id` = `transactions`.`d_id`
								WHERE `status_id` = '$status[offline]'
								OR `status_id` = '$status[moveable]';
")){
	while($row = $results->fetch_assoc()){
		$offline_transactions[] = 	"<tr>
										<td>
											<a href='/pages/lookup.php?trans_id=$row[trans_id]'>
												$row[trans_id]
											</a>
										</td>
										<td>
											$row[off_trans_id]
										</td>
										<td>
											$row[device_desc]
										</td>
										<td>
											<button onclick='print($row[trans_id]);'>Print Ticket</button>
										</td>
									</tr>";
	}
} else {
	$mysqli->getMessage();
}


// if an error message passes, add error to session, redirect (default: home)
function exit_if_error($error, $redirect=null) {
	global $ticket;

	if($error) {
		$_SESSION['error_msg'] = "Lookup.php: ".$error;
		if($redirect) header("Location:$redirect");
		else header("Location:/index.php");
		exit();
	}
}


function exit_with_success($message, $redirect=null) {
	global $ticket;

	$_SESSION["success_msg"] = $message;
	if($redirect) header("Location:$redirect");
	else header("Location:/index.php");
	exit();
}


?>

<title><?php echo $sv['site_name']; ?> Ticket Detail</title>
<div id="page-wrapper">
	<div class="row">
        <div class="col-md-12">
            <h1 class="page-header">Offline Transactions Dashboard</h1>
        </div>
        <!-- /.col-md-12 -->
    </div>
	<div class="row">
		<div class="col-md-6">
            <div class="panel panel-default">
				<div class="panel-heading">
                	<i class="fas fa-cubes fa-lg"></i> Current Offline Transactions
            	</div>
		<div class="panel-body">
			<table class='table table-striped table-bordered table-hover' id='tickets'>
				<thead>
					<tr class="tablerow">
						<th>Ticket ID</th>
						<th>Offline ID</th>
						<th>Device</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($offline_transactions as $ticket) {echo $ticket;} ?>
				</tbody>
			</table>

			<form method='post' name='print_form'>
				<input name='print_trans_id'/>
			</form>
		</div>
		</div>
	</div>
</div>


<?php // Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>

<script>
	$("#tickets").DataTable({
		"iDisplayLength": 25
	});


	function print(trans_id) {
		document.getElementByID("print_trans_id").value = trans_id;
		document.getElementByID("print_form").submit();
	}
</script>