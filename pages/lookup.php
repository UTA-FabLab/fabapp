<?php

/***********************************************************************************************************
*	
*	@author Jon Le
*	Edited by: MPZinke on 06.08.19 to improve commenting an logic/functionality of page;
*	 update in accord with class changes
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.94
*
*	DESCRIPTION: look up a ticket by trans_id or operator. Direct to other pages for other 
*	 processes.  Reassign location of object.  Add additional material to ticket if not already
*	 ended.  Unend a ticket by reverting status, t_end
*	FUTURE: 	-add authorized recipients with looking up ticket
*				-remove authorized recipients ¿tags?
*
***********************************************************************************************************/


// ---- PAGE SETUP ----
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

if(!$staff) $error = exit_if_error("Please log in", "/index.php");
elseif(!isset($_GET["operator"]) && !isset($_GET["trans_id"])) exit_if_error("Search parameter is missing", "/index.php");

// lookup by ticket ID
if(isset($_GET["trans_id"])) {
	$trans_id = $_GET['trans_id'];
	if(!Transactions::regexTrans($trans_id)) exit_if_error("Ticket ID #$trans_id is invalid", "/index.php");
	$ticket = new Transactions($trans_id);
}
// lookup by user ID
elseif(isset($_GET["operator"])) {
	if(!Users::regexUSER ($_GET['operator'])) exit_if_error("Operator ID is invalid", "/index.php");
	else {
		$operator = $_GET['operator'];
		// last Ticket of that ID; does not search for AUTH RECIP.
		if($result = $mysqli->query("
			SELECT trans_id
			FROM transactions
			WHERE transactions.operator = '$operator'
			ORDER BY t_start DESC
			Limit 1
		")) {
			if(!$result->num_rows) exit_if_error("No Transactions Found for ID# $operator");
			else {
				$row = $result->fetch_assoc();
				$ticket = new Transactions($row['trans_id']);
			}
		}
		else exit_if_error("DB error");
	} 
}

// check permission
if($staff->operator != $ticket->user->operator && $staff->roleID < $role["staff"])
	exit_if_error("You are not authorized to see this ticket", "/index.php");

// Transactions::printTicket($ticket->trans_id);

// --- BUTTON PRESSED ---
// add authorized recipient
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aarBtn'])) {
	exit_if_error(AuthRecipients::add($ticket, filter_input(INPUT_POST, "operator")));
	exit_with_success("Authorized Recipient has been added");
}

elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['printForm'])) {
	exit_if_error(Transactions::printTicket($ticket->trans_id));
	exit_with_success("Printing ticket for ticket #$ticket->trans_id");
}
elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['undo_button'])) {
	// double check that ticket may be ended
	if($staff->roleID < $role["staff"])
		exit_if_error("You do not have permission to unend this ticket");
	elseif($ticket->status->status_id <= $status["moveable"])
		exit_if_error("Ticket cannot be unended because it has not been ended");
	elseif($staus["charge_to_acct"] <= $ticket->status->status_id)
		exit_if_error("Ticket cannot be unended because it has been paid for");
	elseif(date("Y-m-d H:i:s") - $ticket->t_end > 1800)
		exit_if_error("More than 30 minutes have passed. Ticket must now be edited to change this information");

	// unend ticket
	$revert_status = $ticket->device->device_group->is_storable ? new Status($status["moveable"]) : new Status($status["active"]);
	exit_if_error($ticket->edit_transaction_information(array("t_end" => null, "status" => $revert_status)));

	// assume that all materials are used
	foreach($ticket->mats_used as $mat_used)
		exit_if_error($mat_used->edit_material_used_informations(array("status" => new Status($status["used"]))));
}
elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_material_button'])) {
	$new_material = filter_input(INPUT_POST, "new_material");

	if($staff->roleID < $role["staff"])  // double check permission
		exit_if_error("You do not have permission to unend this ticket");
	elseif(!Materials::regexID($new_material))
		exit_if_error("Material ID $new_material is not valid");
	elseif($status["moveable"] < $ticket->status->status_id)
		exit_if_error("You cannot add a material to an ended ticket");

	if(!is_int($mu_id = Mats_Used::insert_material_used($ticket->trans_id, $new_material, $status["used"], $staff->operator)))
		exit_if_error("Problem associating materials to ticket–$mu_id");
	exit_with_success("Material added");
}


function material_not_already_used($material, $used_materials) {
	foreach ($used_materials as $mat_used)
		if($mat_used->material->m_id == $material->m_id) return false;
	return true;
}


// if an error message passes, add error to session, redirect (default: home)
function exit_if_error($error, $redirect=null) {
	global $ticket;

	if($error) {
		$_SESSION['error_msg'] = "Lookup.php: ".$error;
		if($redirect) header("Location:$redirect");
		else header("Location:./lookup.php?trans_id=$ticket->trans_id");
		exit();
	}
}


function exit_with_success($message, $redirect=null) {
	global $ticket;

	$_SESSION["success_msg"] = $message;
	if($redirect) header("Location:$redirect");
	else header("Location:./lookup.php?trans_id=$ticket->trans_id");
	exit();
}

?>
<title><?php echo $sv['site_name']; ?> Ticket Detail</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-lg-12">
			<?php // ticket finished && not sheetgood device, allow shortcut to make new ticket 
			if($ticket->status->status_id >= $status['total_fail'] && $ticket->device->device_id != $sv["sheet_device"]) {
			?>
				<h1 class="page-header">Ticket Details
					<!-- //ADD WITH UPDATE -->
					<!-- <a href='<?php // echo $ticket->device->url ? $ticket->device->url : "/pages/create.php?device_id=".$ticket->device->device_id; ?>'> -->
					<!-- //REMOVE WITH UPDATE -->
					<a href='<?php echo $ticket->device->url ? $ticket->device->url : "/pages/create.php?d_id=".$ticket->device->device_id; ?>'>
						<button type='button' class='btn'>New <?php echo $ticket->device->name; ?></button>
					</a>
				</h1>
			<?php }
			else { ?>
				<h1 class="page-header">Ticket Details</h1>
			<?php } ?>
		</div> <!-- /.col-lg-12 -->
	</div> <!-- /.row -->
	<div class="row">
		<div class="col-lg-5">
			<?php if($staff->roleID >= $role["staff"]) { ?>
				<div class="panel panel-default">
					<div class="panel-heading clearfix">
						<i class="fas fa-ticket-alt fa-lg"></i> Ticket # <b><?php echo $ticket->trans_id; ?></b>
						<div class="pull-right">
							<div class="btn-group">
								<button type="button" class="btn btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
									<span class="caret"></span>
								</button>
								<ul class="dropdown-menu pull-right" role="menu">
									<?php if($staff->operator == $ticket->user->operator) { ?>
										<li>
											<a href="javascript: addBtn()" />Add Authorized Recipient</a>
										</li>
									<?php
									}
									if($staff->roleID >= $sv['editTrans']) { ?>
										<li>
											<a href='/pages/edit.php?trans_id=<?php echo $ticket->trans_id; ?>' class="bg-warning"/>Edit</a>
										</li>
									<?php } ?>
									<li>
										<a href="javascript: printBtn();"/>Print</a>
									</li>
								</ul>
							</div>
						</div>
					</div>
					<div class="panel-body">
						<table class ="table table-bordered table-striped">
							<tr>
								<td>Device</td>
								<td>
									<?php echo $ticket->device->name; ?>	
								</td>
							</tr>
							<tr>
								<td>Time</td>
								<td>
									<?php echo $ticket->t_start." – ".$ticket->t_end; ?>
								</td>
							</tr>
							<?php if($ticket->est_time) { ?>
								<tr>
									<td>Estimated Time</td>
									<td>
										<div class="btn-group">
											<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
												<i class="fas fa-stopwatch fa-lg" title="<?php echo $ticket->est_time; ?>"></i>
											</button>
											<ul class="dropdown-menu" role="menu">
												<li style="padding-left: 5px;">Estimated Time <?php echo $ticket->est_time; ?></li>
											</ul>
										</div>
									</td>
								</tr>
							<?php 
							} 
							if($ticket->filename) {
							?>
								<tr>
									<td>Filename</td>
									<td>
										<?php echo $ticket->filename; ?>
									</td>
								</tr>
							<?php } ?>
							<tr>
								<td>Operator</td>
								<td>
									<div class="btn-group">
										<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
											<i class="<?php echo $ticket->user->icon; ?> fa-lg" title="<?php echo $ticket->user->operator; ?>"></i>
										</button>
										<ul class="dropdown-menu" role="menu">
											<li style="padding-left: 5px;"><?php echo $ticket->user->operator; ?></li>
										</ul>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									Status
								</td>
								<td>
									<?php echo $ticket->status->message; ?>
								</td>
							</tr>
							<?php if(is_object($ticket->staff)) { ?>
								<tr>
									<td>Staff</td>
									<td>
										<div class="btn-group">
											<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
												<i class="<?php echo $ticket->staff->icon; ?> fa-lg" title="<?php echo $ticket->staff->operator; ?>"></i>
											</button>
											<ul class="dropdown-menu" role="menu">
												<li style="padding-left: 5px;"><?php echo $ticket->staff->operator; ?></li>
											</ul>
										</div>
									</td>
								</tr>
							<?php 
							} 
							if($ticket->notes) { ?>
								<tr>
									<td><i class="fas edit fa-lg"></i>Notes</td>
									<td><?php echo $ticket->notes; ?></td>
								</tr>
							<?php } ?>
						</table>
					</div>
					<!-- /.panel-body -->
					<?php if($ticket->status->status_id <= $status['moveable']) { ?>
						<div class="panel-footer">
							<div align="right">
								<a href='/pages/end.php?trans_id=<?php echo $ticket->trans_id; ?>'>
									<button type="button" class="btn btn-primary" name="endBtn">End</button>
								</a>
							</div>
						</div>
					<?php 
					}
					elseif($ticket->status->status_id < $status["charge_to_acct"] && $ticket->remaining_balance()) { ?>
						<div class="panel-footer">
							<div align="right">
								<?php 
								if($ticket->device->device_group->is_storable)
									echo "<a href='/pages/pickup.php?operator=".$ticket->user->operator."'>";
								else
									echo "<a href='/pages/pay.php?trans_id=".$ticket->trans_id."'>";
								?>
									<button type="button" class="btn btn-primary">
										<?php echo $ticket->device->device_group->is_storable? "Pick Up" : "Pay"; ?>
									</button>
								</a>
							</div>
						</div>
					<?php } ?>
				</div> <!-- /.panel -->
			<!-- end staff ticket info -->
			<?php
			}
			else { ?>
				<div class="panel panel-default">
					<div class="panel-heading clearfix">
						<i class="fas fa-ticket-alt fa-lg"></i> Ticket # <b><?php echo $ticket->trans_id; ?></b>
						<div class="pull-right">
							<div class="btn-group">
								<button type="button" class="btn btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
									<span class="caret"></span>
								</button>
								<ul class="dropdown-menu pull-right" role="menu">
									<li>
										<a href="javascript: addBtn()" />Add Authorized Recipient</a>
									</li>
								</ul>
							</div>
						</div>
					</div>
					<div class="panel-body">
						<table class ="table table-bordered table-striped">
							<tr>
								<td>Device</td>
								<td>
									<?php echo $ticket->device->name; ?>	
								</td>
							</tr>
							<tr>
								<td>Time</td>
								<td>
									<?php echo $ticket->t_start." – ".$ticket->t_end; ?>
								</td>
							</tr>
							<?php if($ticket->est_time) { ?>
								<tr>
									<td>Estimated Time</td>
									<td>
										<div class="btn-group">
											<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
												<i class="fas fa-stopwatch fa-lg" title="<?php echo $ticket->est_time; ?>"></i>
											</button>
											<ul class="dropdown-menu" role="menu">
												<li style="padding-left: 5px;">Estimated Time <?php echo $ticket->est_time; ?></li>
											</ul>
										</div>
									</td>
								</tr>
							<?php } ?>
							<tr>
								<td>Operator</td>
								<td>
									<div class="btn-group">
										<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
											<i class="<?php echo $ticket->user->icon; ?> fa-lg" title="<?php echo $ticket->user->operator; ?>"></i>
										</button>
										<ul class="dropdown-menu" role="menu">
											<li style="padding-left: 5px;"><?php echo $ticket->user->operator; ?></li>
										</ul>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									Status
								</td>
								<td>
									<?php echo $ticket->status->message; ?>
								</td>
							</tr>
							<?php if(is_object($ticket->staff)) { ?>
								<tr>
									<td>Staff</td>
									<td>
										<i class='<?php echo $ticket->staff->icon; ?>'></i>";
									</td>
								</tr>
							<?php 
							}
							if($ticket->notes) { ?>
								<tr>
									<td><i class="fas edit fa-lg"></i>Notes</td>
									<td><?php echo $ticket->notes; ?></td>
								</tr>
							<?php } ?>
						</table>
					</div> <!-- /.panel-body -->
				</div> <!-- /.panel -->
				<!-- end learner ticket info -->
			<?php } ?>

		<!-------------- MATERIALS -------------->
			<?php if($ticket->mats_used) { ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="far fa-life-ring fa-lg"></i> Material
					</div>
					<div class="panel-body">
						<?php foreach ($ticket->mats_used as $mu) {?>
							<table class="table table-bordered table-striped" style="table-layout:fixed">
								<tr>
									<td colspan='2'>
										<?php
										echo $mu->material->m_name;
										if($mu->material->color_hex) 
											echo "<div class=\"color-box\" style=\"background-color: #".$mu->material->color_hex."\"/>\n";
										?>
									</td>
								</tr>
								<tr class="tablerow">
									<td>Material Status</td>
									<td><?php echo $mu->status->message; ?></td>
								</tr>
								<?php if($mu->material->is_measurable) { ?>
									<tr class="tablerow">
										<td> Price </td>
										<td> 
											<?php printf("<i class='$sv[currency]'></i> %.2f", $mu->material->price); ?>
										</td>
									</tr>
									<tr>
										<td class="col-md-5"> Quantity </td>
										<td class="col-md-7">
											<?php echo "$mu->quantity_used ".$mu->material->unit; ?>
										</td>
									</tr>
									<tr>
										<td> Cost </td>
										<td> 
											<?php echo sprintf("<i class='$sv[currency]'></i> %.2f", $mu->material->price * $mu->quantity_used); ?>
										</td>
									</tr>
								<?php } ?>
							</table>
						<?php } ?> 
					</div> <!-- /.panel-body -->
					<?php if($staff->roleID >= $role["staff"] && $ticket->status->status_id <= $status["moveable"] &&
							count($ticket->device->device_group->optional_materials) 
							+ count($ticket->device->device_group->required_materials) > count($ticket->mats_used)) { ?>
						<div class="panel-body">
							<form method='post'>
								<table class='table table-bordered table-striped'>
									<tr>
										<td colspan='3'> Add material </td>
									</tr>
									<tr>
										<td>Material</td>
										<td>
											<select id='new_material' name='new_material' class='form-control'>
												<?php
												foreach($ticket->device->device_group->optional_materials as $optional_material)
													if(material_not_already_used($optional_material, $ticket->mats_used))
														echo "<option value='$optional_material->m_id'>$optional_material->m_name</option>";
												?>
											</select>
										</td>
										<td>
											<button type='submit' name='new_material_button' class='btn btn-success'>Add Material</button>
										</td>
									</tr>
								</table>
							</form>
						</div>
					<?php } ?>
				</div> <!-- /.panel -->
			<?php } ?>
		</div>

		<!-------------- UNDO -------------->


		<div class="col-lg-5">
			<?php // rework to rollback transaction, mats_used, ac_charge...user gets to put it back in storage manually (limit 30 minutes)
			if($sv['LvlOfStaff'] <= $staff->roleID && -1800 < (date("Y-m-d H:i:s") - strtotime($ticket->t_end)) && $ticket->status->status_id > $status["moveable"]) {
				if($ticket->device->device_id != $sv["sheet_device"]) { ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fas fa-undo fa-lg" title="Undo"></i> Undo...
					</div>
					<div class="panel-body">
						Did you accidentally close the wrong ticket?
						<i class="fas fa-info-circle" title="The previous state of this ticket is stored in memory."></i>
						<form name="undoForm" method="post" action="">
							<input type="submit" name="undo_button" class="btn" value="Unend this Ticket"/>
						</form>
					</div> <!-- /.panel-body -->
				</div> <!-- /.panel -->
				<?php } } 

			// —————— REPORT ISSUE ——————

			if($staff->roleID >= $sv['LvlOfStaff'] && ($ticket->status->status_id == $status['total_fail'] 
			|| $ticket->status->status_id == $status['partial_fail'])) { ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fas fa-wrench fa-lg" title="Undo"></i> Service Ticket
					</div>
					<div class="panel-body">
						Since it failed, should we Report an Issue with <?php echo $ticket->device->description; ?>
						<form name="undoForm" method="post" action="/pages/sr_issue.php?d_id=<?php echo $ticket->device->device_id; ?>">
							<input type="submit" name="issueBtn" class="btn btn-warning" value="Report Issue"/>
						</form>
					</div> <!-- /.panel-body -->
				</div> <!-- /.panel -->
			<?php } 

			// ———————— AUTH RECIPIENTS ———————— 

			if($authorized_recipients = AuthRecipients::listArs($ticket)) { ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="far fa-address-book fa-lg"></i> Authorized Recipients
					</div>
					<div class="panel-body">
						<?php echo $authorized_recipients; ?>
					</div>
				</div>
			<?php } 

			// —————————— STORAGE —————————— 

			if(StorageObject::object_is_in_storage($ticket->trans_id)) {
				$storage = new StorageObject($ticket->trans_id) ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fas fa-gift fa-lg"></i> Storage
					</div>
					<div class="panel-body">
						<table class="table table-bordered table-striped">
							<tr>
								<td>
									Placed Into Storage
								</td>
								<td>
									<?php echo $storage->storage_start; ?>
								</td>
							</tr>
							<?php if($staff->roleID >= $role["staff"]) { ?>
								<tr>
									<td>
										Address
									</td>
									<td>
										<input id="address" type="button" value="<?php echo $storage->box_id; ?>" class="btn btn-success"
										data-toggle="modal" data-target="#storage_modal">
									</td>
								</tr>
								<tr>
									<td> Staff </td>
									<td>
										<div class="btn-group">
											<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
												<i class="<?php echo $storage->staff->icon; ?> fa-lg" title="<?php echo $storage->staff->operator; ?>"></i>
											</button>
											<ul class="dropdown-menu" role="menu">
												<li style="padding-left: 5px;"><?php echo $storage->staff->operator; ?></li>
											</ul>
										</div>
									</td>
								</tr>
							<?php
							}
							else { ?>
								<tr>
									<td> Staff </td>
									<td>
										<i class='<?php echo $storage->staff->icon; ?> fa-lg' /></i>
									</td>
								</tr>
							<?php } ?>
						</table>
					</div> <!-- /.panel-body -->
				</div> <!-- /.panel -->
			<?php
			}
			// information about when picked up
			elseif($ticket->pickup_time) { ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fas fa-gift fa-lg"></i> Storage
					</div>
					<div class="panel-body">
						<table class="table table-bordered table-striped">
							<tr>
								<td>
									Picked Up By
								</td>
								<td>
									<div class="btn-group">
										<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
											<i class="<?php echo $ticket->pickedup_by->icon; ?> fa-lg" title="<?php echo $ticket->pickedup_by->operator; ?>"></i>
										</button>
										<ul class="dropdown-menu" role="menu">
											<li style="padding-left: 5px;"><?php echo $ticket->pickedup_by->operator; ?></li>
										</ul>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									Picked Up On
								</td>
								<td>
									<?php echo $ticket->pickup_time; ?>
								</td>
							</tr>
						</table>
					</div>
				</div>
			<?php }

		// ————————— ACCT CHARGES ————————— 

			//Look for associated charges
			if($ticket->acct_charge) { ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fas fa-credit-card fa-lg"></i> Related Charges
					</div>
					<div class="panel-body">
						<table class="table table-bordered">
							<tr>
								<td class="col-sm-2">Paid By</td>
								<td class="col-sm-2">Amount</td>
								<td class="col-sm-3">Account</td>
								<td class="col-sm-3">Staff</td>
							</tr>
							<?php foreach ($ticket->acct_charge as $ac) {
								if($ac->account->a_id == 1) echo"\n\t\t<tr class=\"danger\">";
								else echo"\n\t\t<tr>";		
									if(is_object($ac->user)) { ?>
										<td>
											<div class="btn-group">
												<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
													<i class="<?php echo $ac->user->icon; ?> fa-lg" title="<?php echo $ac->user->operator; ?>"></i>
												</button>
												<ul class="dropdown-menu" role="menu">
													<li style="padding-left: 5px;"><?php echo $ac->user->operator; ?></li>
												</ul>
											</div>
										</td>
									<?php
									}
									else echo "<td>-</td>";

									echo "<td><i class='".$sv['currency']."'></i> ".number_format($ac->getAmount(), 2)."</td>"; ?>
									<td>
										<div class="btn-group">
											<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
												<i class='far fa-calendar-alt' title="<?php echo $ac->getAc_date(); ?>"></i>
											</button>
											<ul class="dropdown-menu" role="menu">
												<li style="padding-left: 5px;"><?php echo $ac->getAc_date(); ?></li>
											</ul>
										</div>
										<?php echo $ac->account->name; ?>
									</td>
									<td>
										<?php if($staff->roleID >= $sv['LvlOfStaff']) { ?>
											<div class="btn-group">
												<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
													<i class="<?php echo $ac->staff->icon; ?> fa-lg" title="<?php echo $ac->staff->operator; ?>"></i>
												</button>
												<ul class="dropdown-menu" role="menu">
													<li style="padding-left: 5px;"><?php echo $ac->staff->operator; ?></li>
												</ul>
											</div>
											<?php if($ac->ac_notes) { ?>
												<div class="btn-group">
													<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
														<span class="fas fa-music" title="Notes"></span>
													</button>
													<ul class="dropdown-menu pull-right" role="menu">
														<li style="padding-left: 5px;"><?php echo $ac->ac_notes; ?></li>
													</ul>
												</div>
											<?php 
											} 
										}
										else echo "<i class='".$ac->staff->icon." fa-lg'></i>"; ?>
									</td>
								</tr>
							<?php } ?>
						</table>
					</div> <!-- /.panel-body -->
					<?php //Determine if there is a balance owed on this ticket
					$ac_owed = Acct_charge::checkOutstanding($ticket->user->operator);
					if(isset($ac_owed[$ticket->trans_id])) { ?>
						<div align="right" class="panel-footer">
							<form name="payForm" method="post" action="">
								<button type="submit" name="payBtn" class="btn btn-danger">
									Pay <?php echo "<i class='".$sv['currency']."'></i> ".number_format($ac_owed[$ticket->trans_id], 2); ?>
								</button>
							</form>
						</div> <!-- /.panel-footer -->
					<?php } ?>
				</div> <!-- /.panel -->
			<?php } ?>
		</div> <!-- /.col-lg-5 -->
	</div> <!-- /.row -->
</div> <!-- /#page-wrapper -->


<form id="printForm" action="" method="post">
    <input type="text" name="printForm" hidden/>   
</form>

<!--————————————————— MODAL ———————————————–——-->
<!--————————————————————————————————————–——-->

<div id="storage_modal" class="modal">
	<div class="modal-dialog">
		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Change Address</h4>
			</div>
			<div class="modal-body">
				<div class='input-group'>
					<span class='input-group-addon'>Drawer</span>
					<select id='drawer' class='form-control' onchange='add_to_storage(this);'>
						<option selected disabled hidden>—</option>
						<?php 
						$drawers = StorageDrawer::get_unique_drawers();
						if($drawers)
							foreach($drawers as $option) 
								echo "<option value='$option'>$option</option>";
						else echo "<option>ERROR: Could not get drawer types from DB</option>";
						?>
					</select>
				</div>
				<div id='drawer_table' align='center'>
					<!-- New Box Magic Goes Here -->
				</div>
			</div>
			<div class="modal-footer">
				  <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
			</div> 
		</div>
	</div>
</div>
<!-- Modal -->

<!-- ADD AUTHORIZED RECIPIENT MODAL -->
<div id="AARModal" class="modal">
	<div class="modal-dialog">
		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Add Authorized Recipient</h4>
			</div>
			<form name="aarForm" method="post" action="" onsubmit="return validateAAR()">
				<div class="modal-body">
					<p>Authorized the following recipient to pick up and pay for this ticket.
					At the time of pickup, only the person & ID present can pay.</p>
					<input type="text" name="operator" id="operator" placeholder="1000000000"
								maxlength="10" size="10" tabindex="1">
				</div>
				<div class="modal-footer">
					  <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					  <button type="submit" class="btn btn-primary" name="aarBtn">Add</button>
				</div> 
			</form>
		</div>
	</div>
</div>
<!-- Modal -->


<?php // Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript">

	function validateAAR() {
		return stdRegEx("operator", /^\d{10}/, "Invalid Operator ID")
	}


	function add_to_storage(drawer) {
		$.ajax({
			url: "./sub/storage_ajax_requests.php",
			type: "POST",
			dataType: "json",
			data: {"choose_unit_in_drawer" : true, "drawer_label" : drawer.value},
			success: function(response) {
				var drawer_label = `<h3>Drawer ${drawer.value} </h3>`;
				document.getElementById("drawer_table").innerHTML = drawer_label+response["drawer_HTML"];
			}
		});
	}


	function add_to_location(unit) {
		var drawer = document.getElementById("drawer").value;
		$("#storage_modal").modal("hide");
		$.ajax({
			url: "./sub/storage_ajax_requests.php",
			type: "POST",
			dataType: "json",
			data: {	"move_to_new_location" : true, 
					"drawer" : drawer, 
					"unit" : unit.innerHTML,
					"trans_id" : <?php echo $ticket->trans_id; ?>
			},
			success: function(response) {
				if(response["error"]) alert(`Unable to move object to location ${drawer}${unit.innerHTML}`);
				else {
					document.getElementById("address").value = drawer+unit.innerHTML;
					alert(`Successfully moved object to location ${drawer}${unit.innerHTML}`);
					document.getElementById("drawer").selectedIndex = 0;
					document.getElementById("drawer_table").innerHTML = "";
				}
			}
		});
	}


	function addBtn() {
		$("#AARModal").modal();
	}


	function printBtn() {
		if(confirm("Print?")) document.getElementById("printForm").submit();
	}

</script>