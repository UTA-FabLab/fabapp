<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
 //This will import all of the CSS and HTML code necessary to build the basic page
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
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
									foreach ($user->history() as $ticket)
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
									<td><p title="<?php echo "$sv[rank_period] Month Rank : ".$user->ticketsTotalRank(); ?>">
										<?php echo $user->ticketsTotal(); ?></p>
									</td>
								</tr>
								<tr>
									<td align="Center">Tickets Assisted</td>
									<td><p title="<?php echo "$sv[rank_period] Month Rank : ".$user->ticketsAssistRank(); ?>">
										<?php echo $user->ticketsAssist(); ?></p>
									</td>
								</tr>
								<tr>
									<td align="Center">Assigned Role</td>
									<td><p><?php echo Role::getTitle($user->r_id); ?></p></td>
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
</script>