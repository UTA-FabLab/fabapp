<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
 //This will import all of the CSS and HTML code necessary to build the basic page
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

if($_SERVER["REQUEST_METHOD"] == "POST")
{
	// check that user is in ticket
	$Q_id = $_POST["__WAITQ__input"];
	if(!preg_match("/^\d+$/", $Q_id))
	{
		$_SESSION["error_msg"] = "The wait queue ID: $Q_id is invalid";
		header("Location:/pages/info.php");
		exit();
	}

	if(!Wait_queue::wait_ticket_belongs_to_user($staff->operator, $Q_id))
	{
		$_SESSION["error_msg"] = "The user for wait queue ID: $Q_id is incorrect";
		header("Location:/pages/info.php");
		exit();
	}

	$operator = $staff->operator;
	// cancel ticket
	$queue_ticket = new Wait_queue($Q_id);
	echo "WAIT QUEUE: ".$queue_ticket->getQ_ID();
	if($error = Wait_queue::deleteFromWaitQueue($queue_ticket)) $_SESSION["error_msg"] = $error;
	else $_SESSION["success_msg"] = "Successfully removed you from wait_queue ID: $Q_id";

	// prompt & refresh
	header("Location:/pages/info.php");
}


?>
<title><?php echo $sv['site_name']; ?> Information</title>
<?php
	if(!is_object($user))
	{
		?>
		<div id="page-wrapper">
			<div class="row">
				<div class="col-md-12">
					<h1 class="page-header">Please Sign In</h1>
				</div>
				<!-- /.col-md-12 -->
			</div>
		</div>
		<?php
	}
	else
	{
		?>
		<div id="page-wrapper">
			<div class="row">
				<div class="col-md-12">
					<h1 class="page-header">Information</h1>
				</div>
				<!-- /.col-md-12 -->
			</div>
			<!-- /.row -->
			<?php
		// added by MPZinke on 2020.07.17 to allow for learners to remove themselves from wait_queue
		if(Wait_queue::isOperatorWaiting($staff->operator))
		{
			?>
			<form method='POST' id='__WAITQ__form' name='__WAITQ__form'>
				<div class="row">
					<div class="col-md-8">
						<div class="panel panel-default">
							<div class="panel-heading">
								<i class="fas fa-ticket-alt fa-fw"></i> Tickets
							</div>  <!-- <div class="panel-heading"> -->
							<div class="panel-body">
								<!-- holds which Q_id is selected -->
								<input id='__WAITQ__input' name='__WAITQ__input' value='0' hidden>  
								<table class='table'>
									<thead>
										<tr>
											<th>Wait Queue ID</th>
											<th>Device Group</th>
											<th>Device</th>
											<th>Start</th>
											<th></th>
										</tr>
									</thead>
									<?php
										$tickets = Wait_queue::all_wait_tickets_for_user($staff->operator);
										foreach($tickets as $ticket)
										{
											?>
											<tr>
												<td> <?php echo $ticket["Q_id"]; ?> </td>
												<td> <?php echo $ticket["dg_desc"]; ?> </td>
												<td> <?php echo $ticket["device_desc"]; ?> </td>
												<td> <?php echo $ticket["Start_date"]; ?> <td>
												<td>
													<button type='button' onclick='__WAITQ__cancel_ticket(<?php echo $ticket["Q_id"]; ?>);'>
														Cancel Ticket
													</button>
												</td>
											</tr>
											<?php
										}
									?>
								</table>
							</div>  <!-- <div class="panel-body"> -->
						</div>  <!-- <div class="panel panel-default"> -->
					</div>  <!-- <div class="col-md-8"> -->
				</div>  <!-- <div class="row"> -->
			</form>
			<?php	
		}
		?>
			<div class="row">
				<div class="col-md-8">
					<div class="panel panel-default">
						<div class="panel-heading">
							<i class="fas fa-ticket-alt fa-fw"></i> Tickets
						</div>
						<div class="panel-body">
							<table class="table table-striped table-bordered table-hover" id="ticketsTable">
								<thead>
									<tr class="tablerow">
										<th align="right">Ticket</th>
										<th>Device</th>
										<th>Start Time</th>
										<th>Status</th>
										<th>Amount</th>
									</tr>
								</thead>
								<?php
									foreach($user->transaction_history() as $ticket)
									{
										?>
										<tr>
											<td align="Center"><a href="/pages/lookup.php?trans_id=<?php echo $ticket[0]; ?>">
												<?php echo $ticket["trans_id"]; ?></a>
											</td>
											<td><?php echo $ticket["device_name"]; ?></td>
											<td><?php echo $ticket["t_start"]; ?></td>
											<td><?php echo $ticket["message"]; ?></td>
											<td><?php echo $ticket["amount"]; ?></td>
										</tr>
										<?php
									}
								?>
							</table>
						</div>
					</div>
				</div>
				<!-- /.col-md-8 -->
				<div class="col-md-4">
					<div class="panel panel-default">
						<div class="panel-heading">
							<i class="fas fa-chart-bar fa-fw"></i> Stats
						</div>
						<div class="panel-body">
							<table class="table table-striped table-bordered table-hover">
								<tr>
									<td align="Center">Total Tickets</td>
									<td><p title="Ranking: <?php echo $user->total_transactions_rank(); ?>">
										<?php echo $user->total_transactions(); ?></p>
									</td>
								</tr>
								<tr>
									<td align="Center">Tickets Assisted</td>
									<td><p title="Ranking: <?php echo $user->transaction_assists_rank(); ?>">
										<?php echo $user->transaction_assists(); ?></p>
									</td>
								</tr>
								<tr>
									<td align="Center">Assigned Role</td>
									<td><p><?php echo Role::to_title($user->r_id); ?></p></td>
								</tr>
								<tr>
									<!-- TODO: permissions -->
									<td align="Center">LC</td>
									<td><p><?php echo $user; ?></p></td>
								</tr>
							</table>
						</div>  <!-- /.panel-body -->
					</div>  <!-- /.panel -->
				</div>  <!-- /.col-md-4 -->
			</div>  <!-- /.row -->
		</div>  <!-- /#page-wrapper -->
		<?php
	}
?>
<?php

	//Standard call for dependencies
	include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');

?>

<script>
	$('#ticketsTable').DataTable({
		"iDisplayLength": 25,
		"order": []
	});



	// added by MPZinke on 2020.07.17 to allow for learners to remove themselves from wait_queue
	// selects Q_id & submits cancelation.
	// takes Q_id int.
	// checks Q_id, confirms action, submits form
	function __WAITQ__cancel_ticket(Q_id)
	{
		if(!Q_id) return alert(`Bad Q_id: ${Q_id}`);
		else if(!Number.isInteger(Q_id)) return alert(`Bad Q_id type: ${typeof Q_id}`);

		if(!confirm("Are you sure you want to cancel this wait ticket? This cannot be undone")) return;

		document.getElementById("__WAITQ__input").value = Q_id;
		document.__WAITQ__form.submit();
	}
</script>