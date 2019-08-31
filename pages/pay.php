<?php

/***********************************************************************************************************
*	
*	@author Jon Le
*	Edited by: MPZinke on 08.05.19 to allow for trans_id ticket creation; improve commenting 
*	 and logic/functionality of page; update in accord with class changes & StorageBox
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.94
*		-Multiple Materials
*		-Off-line Mode
*		-Sheet Goods
*		-Storage Box
*		-House Keeping (DB cleanup, $status variable, class syntax)
*
*	DESCRIPTION: For pay first tickets, this is the step after ticket creation.  For other tickets
*	 with cost or stored objects, this is the final step.  The page displays information for the 
*	 ticket, including transaction data, materials, associated charges, & storage.  The primary
*	 function of the page is process account charges.  The payer's id is taken in via an input
*	 which uses an AJAX call to ./sub/pay_authenticate_payer.php with POST methods. If the
*	 payer is valid then they may pick up the stored object (given it exists), else they must 
*	 also select the account to charge to.  The pay service used comes from the DB and is 
*	 opened in a window if selected—before the backend initiates everything, the operator 
*	 must confirm that they have accepted payment and logged out.  To other accounts, they
*	 are charged without additional confirmation.
*	 Additionally, any last minute notes is appended to prior notes.
*	FUTURE: If a ticket is storable, not currently in storage, and the payer is invalid, pay 
*	 button adds ticket to storage (because we cannot release the object to them).  Once 
*	 payer is validated, button changes to Submit.
*	BUGS: Does not add transactions to DB if not 2
*
***********************************************************************************************************/

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');


// check inputs and success of ticket creation
if(!$_GET["trans_id"]) exit_if_error("Pay: Ticket ID not supplied");
elseif(!$staff) exit_if_error("Please log in");
else {
	$trans_id = filter_input(INPUT_GET, "trans_id", FILTER_VALIDATE_INT);
	try {
		$ticket = new Transactions($trans_id);
	}
	catch(Exception $e) {
		exit_if_error($e->getMessage());
	}
}

// authenticate user
if($staff->roleID < $role['staff'] || ($staff->operator == $ticket->user->operator && $staff->roleID < $sv['editTrans']))
	exit_if_error("You do not have permission to end this ticket.  Please ask a staff member");



// check that ticket is ended and not pay first
if($ticket->status->status_id < $status['moveable'] && !$ticket->device->device_group->is_pay_first)
	exit_if_error("The ticket is still active and must be ended first", "./end.php?trans_id=$trans_id");
// total failed: can't do anything to total failed tickets
elseif($ticket->status->status_id == $status['total_fail']) 
	exit_if_error("Ticket #$trans_id is marked as a complete fail and cannot be payed for");
elseif(!StorageObject::object_is_in_storage($trans_id)) {
	// ticket already payed && not in FabLab
	if($ticket->status->status_id >= $status['charge_to_acct'])
		exit_if_error("Ticket #$trans_id already payed");
	// ticket has no debit (or credit) && not in FabLab
	elseif($ticket->no_associated_materials_have_a_price() || !$ticket->remaining_balance()) {
		// if there is a cost associated, but already paid and status is not charge_to_acct: change status to charge_to_acct
		if($ticket->quote_cost()) exit_if_error($ticket->edit_transaction_information(array("status_id" => new Status($status["charge_to_acct"]))));
		exit_with_success("There is no cost associated with this ticket and nothing in storage. There is nothing else to do for ticket #$trans_id");
	}
}


if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pay_button'])) {
	// check user allowed to pickup ticket
	$operator = filter_input(INPUT_POST, "payer");
	if(!Users::regexUser($operator))
		exit_if_error("The payer ID supplied is not a valid ID number");
	$receiver = Users::withID($operator);
	if(!AuthRecipients::validatePickUp($ticket, $receiver))
		exit_if_error("The person is not allowed to pick up this ticket");

	// update notes if changed
	$notes = htmlspecialchars(filter_input(INPUT_POST, "notes"));
	if($notes != $ticket->notes) exit_if_error($ticket->edit_transaction_information(array("notes" => $ticket->notes." ".$notes)));

	// payment execution
	if($ticket->remaining_balance()) {
		$account_id = filter_input(INPUT_POST, "account_select");
		if(!is_numeric($account_id)) exit_if_error("Account ID $account_id supplied is not valid", "pay.php?trans_id=$trans_id");


		$response = Acct_charge::insertCharge($ticket, $account_id, $receiver, $staff);
		if(is_string($response))
			exit_if_error($response, "pay.php?trans_id=$trans_id");

		// prevent hardcoding status_IDs by having the acct ID order align with status
		$acct_charge_status_id = $account_id - 2 + $status["charge_to_acct"];
		exit_if_error($ticket->edit_transaction_information(array("status" => new Status($acct_charge_status_id))));
		$success_message = "Successfully paid for ticket #$trans_id";
	}

	// remove from storage if in storage
	if(StorageObject::object_is_in_storage($trans_id)) {
		exit_if_error($ticket->record_pickup($reciever, $staff));
		exit_if_error(StorageObject::remove_object_from_storage($staff, $trans_id));
		if(!$success_message) $success_message = "Ticket #$trans_id is complete";
	}

	exit_with_success($success_message);
}



// if an error message passes, add error to session, redirect (default: home)
function exit_if_error($error, $redirect=null) {
	global $trans_id;

	if($error) {
		$_SESSION['error_msg'] = $error;
		if($redirect) header("Location:$redirect");
		else header("Location:./lookup.php?trans_id=$trans_id");
		exit();
	}
}


function exit_with_success($message, $redirect=null) {
	global $trans_id;

	$_SESSION['success_msg'] = $message;
	if($redirect) header("Location:$redirect");
	else header("Location:./lookup.php?trans_id=$trans_id");
	exit();
}

?>

<title><?php echo $sv['site_name']; ?> Pay</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-md-12">
			<h1 class="page-header">Summary for <i class="fas fa-ticket-alt"></i> # <?php echo $ticket->getTrans_id(); ?></h1>
			This ticket has not been finalized until you confirm payment.
		</div>
		<!-- /.col-md-12 -->
	</div>
	<!-- /.row -->
	<div class="row">
		<div class="col-md-6">
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fas fa-ticket-alt fa-lg"></i> Ticket # <strong><?php echo $ticket->trans_id; ?> </strong>
				</div>
				<div class="panel-body">
					<table class ="table table-bordered table-striped">
						<tr>
							<td>Device</td>
							<td><?php echo $ticket->device->name; ?></td>
						</tr>
						<tr>
							<td>Status</td>
							<td><?php echo $ticket->status->message; ?></td>
						</tr>
						<tr>
							<td>Time</td>
							<td><?php echo "$ticket->t_start <strong>–</strong> $ticket->t_end"; ?></td>
						</tr>
						<tr>
							<td>Operator</td>
							<td><i class="<?php echo $ticket->user->icon; ?> fa-lg" title="<?php echo $ticket->user->operator; ?>"></i></td>
						</tr>
						<tr>
							<td>Staff</td>
							<td>
								<?php echo "<i class='".$ticket->staff->icon." fa-lg' title='".$ticket->staff->operator."'></i>"; ?>
							</td>
						</tr>
						<!-- IN LOVING MEMORY OF SAMUEL LAW -->
						<tr>
							<td>Additional Notes</td>
							<td>
								<textarea name='notes' class='form-control'><?php echo $ticket->notes; ?></textarea>
							</td>
						</tr>
					</table>
				</div>
				<!-- /.panel-body -->
			</div>
			<!-- /.panel -->

		<!---------------- MATERIALS ---------------->
			<div class="panel panel-default">
				<div class="panel-heading">
					Materials
				</div>
				<div class="panel-body">
					<table class ="table">
						<?php foreach($ticket->mats_used as $mat_used) { 
							$material = $mat_used->material;
							?>
							<tr>
								<table class='table table-striped table-bordered'>
									<thead>
										<td colspan='2'>
											<?php 
												echo "<b>$material->m_name</b>"; 
												if($material->color_hex) 
													echo "<div class='color-box' style='background-color:#$material->color_hex;' align='right'/>";
											?>
										</td>
									</thead>
									<tr>
										<td> Status </td>
										<td>
											<?php echo $mat_used->status->message; ?>
										</td>
									</tr>
									<?php if($material->is_measurable) { ?>
										<tr>
											<td>
												Price
											</td>
											<td>
												<?php echo "<i class='$sv[currency]'></i>".sprintf("%0.2f", $material->price); ?>
											</td>
										</tr>
										<tr>
											<td>
												Amount Used
											</td>
											<td>
												<?php echo $mat_used->quantity_used."  $material->unit"; ?>
											</td>
										</tr>
										<tr>
											<td>
												Cost
											</td>
											<td>
												<?php 
												echo "<i class='$sv[currency]'></i>";
												printf("%0.2f <br/>", $mat_used->quantity_used * $material->price);
												?>
											</td>
										</tr>
									<?php } ?>
								</table>
							</tr>
						<?php } 
						if($ticket->mats_used) { ?>
							<tr>
								<td>
									Total
								</td>
								<td>
									<?php echo "<i class='$sv[currency]'></i>".sprintf("%0.2f", $ticket->quote_cost()); ?>
								</td>
							</tr>
						<?php } ?>	
					</table>
				</div>
			</div>
		</div>
	<!---------------- PAYMENT ---------------->
		<div class="col-md-6">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<i class="fas fa-calculator"></i> Method of Payment
				</div>
				<form id='payment_form' name='payment_form' method="post" action="" onsubmit="return confirm_payment()" autocomplete="off">
					<div class="panel-body">
						<table class="table table-bordered">
							<tr>
								<td>Payer</td>
								<td>
									<div class='input-group'>
										<input type="text" class="form-control danger" placeholder="Enter ID #" style='color:#d9534f;'
										onkeyup='authenticate_payer();' maxlength="10" name="payer" id="payer">
										<span id='payer_acceptance_display' class='input-group-addon label-danger'>&#10007;</span>
									</div>
								</td>
							</tr>
							<?php if($ticket->remaining_balance()) { ?>
								<tr>
									<td>Payment </td>
									<td>
										<select name="account_select" id="account_select" class='form-control' 
										onchange="authenticate_payer(); adjust_submit_button_for_payment_type(this);">
											<option hidden selected value=''>Select</option>
											<?php
												$accounts = Accounts::listAccts($ticket->user, $staff);
												$ac_owed = Acct_charge::checkOutstanding($ticket->user->operator);
												foreach($accounts as $a)
													// show accounts available ()
													if(!$ac_owed[$ticket->trans_id] && $a->a_id != 1)
														echo " <option value='$a->a_id' title='$a->description'>$a->name</option>";
											?>
										</select>
									</td>
								</tr>
							<?php } ?>
							<tr>
								<td>Ticket #</td>
								<td><b><?php echo $ticket->trans_id; ?></b></td>
							</tr>
							<tr>
								<td>Total</td>
								<td>
									<b><?php printf("<i class='$sv[currency]'></i> %.2f", $ticket->remaining_balance()); ?></b>
								</td>
							</tr>
						</table>
					</div>
					<!-- /.panel-body -->
					<div class="panel-footer" align="right">
							<?php
							// nothing to do for ticket aside from pickup  
							if(!$ticket->remaining_balance()) { ?>
								<button id="pay_button" name="pay_button" class="btn btn-primary" disabled>
									Pick Up
								</button>
							<?php 
							// }
							//FUTURE: for storable tickets that are not in storage, allow option to add item to storage if user !valid
							// elseif($ticket->device->device_group->is_storable && !StorageObject::object_is_in_storage($trans_id)) { 
							?>
								<!-- <button id="pay_button" name="pay_button" type='button' class="btn btn-primary" onclick='open_storage_modal()'>
									Add to Storage
								</button> -->
							<?php
							}
							else { ?>
								<button id="pay_button" name="pay_button" class="btn btn-primary" disabled>
									Submit
								</button>
							<?php } ?>
					</div>
				</form>
			</div>
			<!-- /.panel -->
		<!-- RELATED CHARGES -->
			<?php //Look for associated charges Panel
			if($staff && $ticket->acct_charge && (($ticket->user->operator == $staff->operator) || $staff->roleID >= $sv['LvlOfStaff']) ){ ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fas fa-credit-card fa-lg"></i> Related Charges
					</div>
					<div class="panel-body">
						<table class="table table-bordered">
							<tr>
								<td class="col-sm-1">By</td>
								<td class="col-sm-2">Amount</td>
								<td class="col-sm-7">Account</td>
								<td class="col-sm-2">Staff</td>
							</tr>
							<?php foreach ($ticket->acct_charge as $ac){
								echo $ac->account->a_id == 1 ? "<tr class='danger'>" : "<tr>";

								if(is_object($ac->user) ) {
									if (($ac->user->operator == $staff->operator) || $staff->roleID >= $sv['LvlOfStaff'] )
										echo "<td><i class='".$ac->user->icon." fa-lg' title='".$ac->user->operator."'></i></td>";
									else echo "<td><i class='".$ac->user->icon." fa-lg'></i></td>";
								}
								else echo "<td>-</td>";

								if($ticket->user->operator == $staff->operator || $staff->roleID >= $sv['LvlOfStaff'])
									echo "<td><i class='$sv[currency]'></i> ".number_format($ac->amount, 2)."</td>";

								echo "<td><i class='far fa-calendar-alt' title='$ac->ac_date'> ".$ac->account->name."</i></td>";
								echo "<td><i class='".$ac->staff->icon." fa-lg' title='".$ac->staff->operator."'></i>";
								if($ac->ac_notes) { ?>
									<div class="btn-group">
										<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
											<span class="fas fa-music" title="Notes"></span>
										</button>
										<ul class="dropdown-menu pull-right" role="menu">
											<li style="padding-left: 5px;"><?php echo $ac->ac_notes;?></li>
										</ul>
									</div>
								<?php }
								echo "</td>";
								} ?>
							</tr>
						</table>
					</div>
					<!-- /.panel-body -->
				</div>
				<!-- /.panel -->
			<?php } ?>
		</div>
	<!---------------- STORAGE ---------------->
		<?php if(StorageObject::object_is_in_storage($trans_id)) { 
			$storage_object = new StorageObject($trans_id); ?>	
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">
						Storage
					</div>
					<div class="panel-body" align='center'>
						<h3>
							Currently Stored in <?php echo $storage_object->box_id; ?>
						</h3>
						<?php  // display drawer and selected unit 
							$id = StorageDrawer::drawer_and_unit_dict_from_combined_box_label($storage_object->box_id);
							$unit_behavior = array("style" => "background-color:#00FF00;border:solid;border-width:2px;");
							$select_unit_callback = function($drawer_unit) use ($trans_id) {return $trans_id == $drawer_unit->trans_id;};
							$select_unit_behavior = array("style" => "background-color:#0000FF;border:solid 2px;");
							$Drawer = new StorageDrawer($id["drawer"], $unit_behavior, null, $select_unit_callback, $select_unit_behavior);
							echo $Drawer->HTML_display();
						?>
					</div>
				</div>
			</div>
		<?php } ?>
		</div>
	</div>
</div>

<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>

<script>

	var pay_window;  // hold pay window info to close window on payment confirmation




	// ————————————————— PAYER ——————————————————

	function authenticate_payer() {
		var payer_input = document.getElementById("payer");
		var payment_select = document.getElementById("account_select");
		if(payer_input.value.length != 10) {
			document.getElementById("pay_button").disabled = true;
			change_payer_display(false);
			return;
		}
		$.ajax({
			url: './sub/authenticate_payer.php',
			type: 'POST',
			dataType: "json",
			data: {"trans_id" : <?php echo $trans_id;?>, "payer" : payer_input.value},
			success: function(response) {
				if(response["approved"]) change_payer_display(true);
				else change_payer_display(false);

				if(response["approved"] && 
				(!document.getElementById("account_select") || document.getElementById("account_select").value))
					document.getElementById("pay_button").disabled = false;
			}
		});
	}


	function change_payer_display(approved) {
		var payer_acceptance_display = document.getElementById("payer_acceptance_display");
		var payer_input = document.getElementById("payer");
		if(approved) {
			payer_acceptance_display.innerHTML = "&#10004;";
			payer_acceptance_display.style.backgroundColor = "#00CC00";
			payer_input.style.color = "#00CC00";
		}
		else {
			payer_acceptance_display.innerHTML = "&#10007;";
			payer_acceptance_display.style.backgroundColor = "#FF0000";
			payer_input.style.color = "#FF0000";
		}
	}


	function adjust_submit_button_for_payment_type(payment_select) {
		var button = document.getElementById("pay_button")
		// pay site
		if(payment_select.value == 2 && <?php echo $ticket->remaining_balance() ?>) {
			button.innerHTML = "Launch <?php echo $sv['paySite_name'];?>";
			button.onclick = launch_pay_site;
			button.type = "button";
		}
		else {
	            button.classList.toggle("btn-danger");
			button.type = "submit";
			button.innerHTML = "Submit";
			button.onclick = "";
		}
	}


	// open window for paysite; set up button for POST
	function launch_pay_site() {
            var button = document.getElementById("pay_button");

            pay_window = window.open("<?php echo $sv['paySite'];?>", "pay_window", "top=100,width=750,height=500");
            button.type = "submit";
            button.classList.add("btn-danger");
            button.innerHTML = "Confirm Payment";
            button.onclick = null;
	}


	function confirm_payment() {
		if(document.getElementById("pay_button").disabled) return false;
		if(!document.getElementById("account_select") || document.getElementById("account_select").value != 2) return true;

            var message = 	"Did you take payment from CSGold?\n"+
            					"Did you logout of <?php echo $sv['paySite_name'];?>?";
		if(confirm(message)) {
			pay_window.close();
			return true;
		}
		return false;
	}

</script>