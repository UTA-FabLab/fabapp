<?php 

// ---- PAGE SETUP ----
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

if(!$staff) $error = exit_if_error("Please log in", "/index.php");
elseif($staff->roleID < $role["lead"])
	exit_if_error("You are not authorized to see this page", "/index.php");


if ($_SERVER["REQUEST_METHOD"] === "POST") {
	if( filter_input(INPUT_POST, 'offLookupBtn') !== null ){
		if( filter_input(INPUT_POST, 'offLookupField') !== null){
			$transByOffTransId = OfflineTrans::byOffTransId(filter_input(INPUT_POST, 'offLookupField'));
			if( $transByOffTransId != null){
				header("location:/pages/lookup.php?trans_id=$transByOffTransId");
			} else {
				exit_if_error("Invalid Offline Transaction ID.", '/pages/offline_ticket.php');
			}
		}
	}
}
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
											<a href='/pages/lookup.php?trans_id=$row[trans_id]'>$row[trans_id]</a>
										</td>
										<td>
											$row[off_trans_id]
										</td>
										<td>
											$row[device_desc]
										</td>
										<td>
											<a href='?printForm=$row[trans_id]'>Print Receipt</a>
										</td>
									</tr>";
	}
} else {
	exit_if_error($mysqli->error);
}


// if an error message passes, add error to session, redirect (default: home)
function exit_if_error($error, $redirect=null) {
	global $ticket;

	if($error) {
		$_SESSION['error_msg'] = "offline_ticket.php: ".$error;
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


if (isset($_GET['printForm'])){
	$transId = $_GET['printForm'];
	exit_if_error(Transactions::printTicket($transId));
	exit_with_success("Printing ticket for ticket # $transId", '/pages/offline_ticket.php');
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
		<div class="col-md-8">
            <div class="panel panel-default">
				<div class="panel-heading">
                	<i class="fas fa-cubes fa-lg"></i> Current Offline Transactions
            	</div>
			<div class="panel-body">
				<table class='table table-striped table-bordered table-hover' id='off_tickets'>
					<thead>
						<tr class="tablerow">
							<th>Ticket</th>
							<th>Offline ID</th>
							<th>Device</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($offline_transactions as $ticket) {echo $ticket;} ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<div class="col-md-8">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fas fa-cubes fa-lg"></i> Offline Transactions Lookup
			</div>
		<div class="panel-body">
			<td class='col-md-11'>
				<form name="offTransLookupForm" method="POST" action="" autocomplete="off">	
					<div class="input-group custom-search-form">
						<input type="text" name="offLookupField" id="offLookupField" class="form-control" placeholder="Enter ID #">
						<span class="input-group-btn">
						<button class="btn btn-default" type="submit" name="offLookupBtn">
							<i class="fas fa-search"></i>
						</button>
						</span>
					</div>
				</form>
			</td>
		</div>
	</div>
</div>

<?php // Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>

<script type="text/javascript">
	$("#off_tickets").DataTable({
		"iDisplayLength": 25
	});


	function print(trans_id) {
		document.getElementByID("printForm").submit();
	}
</script>
