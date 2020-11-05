<?php


/***********************************************************************************************************
*	
*	@author Jon Le
*	Overhauled by: MPZinke on 07.21.19 to add Multiple Material (MultiMaterial Project) 
*	 editing functionalities. Improved commenting an logic/functionality of page; update in 
*	 accordance with future class changes.
*	Edited by: MPZinke on 02.17.20 to improve usability of material status autoselect. 
*	 Added ability to select storage box.
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.94
*		-House Keeping (DB cleanup, $status variable, class syntax/functionality)
*		-Multiple Materials
*		-Off-line Mode
*		-Sheet Goods
*		-Storage Box
*
*	DESCRIPTION: Complete active tickets without charge.  Prepare active tickets for 
*	 payment by marking material usage, and ticket status.  This is the last step for total failed
*	 tickets.  Displays additional transaction information
*	FUTURE:	-Add ability to add additional materials
*				-Add min time $sv force JS to time based mats_used
*				-Move material HTML creation to class
*	BUGS:
*
***********************************************************************************************************/

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');


// check inputs and success of ticket creation
if(!$_GET["trans_id"]) exit_if_error("End: Ticket ID not supplied");
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
	exit_if_error("You do not have permission to end this ticket.  Please ask a staff member", "/pages/lookup.php?trans_id=$trans_id");

// prevent ending ended tickets except those that are prepaid—this way the materials used are confirmed
if($ticket->status->status_id > $status['moveable'] && !$ticket->device_group->is_pay_first) 
	exit_if_error("Transaction #$trans_id already ended");

// no cost associated with ticket && not assign materials after ticket; auto close
if($ticket->no_associated_materials_have_a_price() && $ticket->device_group->is_select_mats_first) {
	exit_if_error($ticket->end_transaction($staff, $status['complete']));
	$_SESSION['success_msg'] = "End: Ticket successfully ended";
	header("Location:/pages/lookup.php?trans_id=$ticket->trans_id");
}
// cost associated with ticket; not staff
elseif($staff->getRoleID() < $role['staff']) 
	exit_if_error("This ticket may have a cost. Please ask a staff member to help you close this ticket.");

// cost associated; get attributes from user
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['end_button'])) {
	// get material status and quanity
	$materials_values = get_material_statuses_from_page($ticket->mats_used);
	if($ticket->mats_used && !$materials_values)  // materials to be gathered but none gathered
		exit_if_error("End: Could not get all values for materials", "./end.php?trans_id=$trans_id");
	foreach($ticket->mats_used as $mat_used) {
		$material_values = $materials_values[$mat_used->mu_id];
		exit_if_error($mat_used->end_material_used($staff, $material_values['status'], $material_values['quantity']));
	}

	// end ticket
	$ticket_status = filter_input(INPUT_POST, "ticket_status_input_modal");  // update ticket
	// prevent frontend changing of variables to cheat backend
	if($ticket_status >= $status['charge_to_acct']) exit_if_error("End: Ticket status is invalid.");
	$error = $ticket->end_transaction($staff, $ticket_status);
	exit_if_error($error, "./end.php?trans_id=$trans_id");

	$ticket_notes = htmlspecialchars(filter_input(INPUT_POST, "ticket_notes_textarea_modal"));
	if($ticket_notes) exit_if_error($ticket->edit_transaction_information(array("notes" => $ticket_notes)));

	// completely failed ticket; nothing to pay for
	if($ticket_status == $status['total_fail']) {
		$_SESSION['success_msg'] = "Ticket successfully ended.";
		header("Location:./lookup.php?trans_id=$trans_id");
	}
	// store object
	elseif($ticket_status == $status['stored'])
	{
		//look in here for where to deposit the unpaid charge into acct_charge 
		if(!$location = filter_input(INPUT_POST, "storage_location_input_modal"))
			exit_if_error("End: Could not retrieve storage location from page");
		$error = StorageObject::add_object_to_location_from_possible_previous($location, $staff, $trans_id);
		exit_if_error($error, "./lookup.php?trans_id=$trans_id");
		// nothing more to do for ticket; print message and go to home
		$_SESSION['success_msg'] = "Successfully ended and stored ticket #$trans_id";
		header("Location:/index.php");
	}
	// already paid for; process is finished
	elseif(!$ticket->remaining_balance())
	{
		$ticket->edit_transaction_information(array("status_id" => $status['charge_to_acct'], "notes" => $ticket_notes));
		$_SESSION['success_msg'] = 	"There is no balance on the ticket. It is finished and ".
											"learner is good to go.";
		header("Location:./lookup.php?trans_id=$trans_id");
	}
	// proceed to payment; if balance is negative, this is where they should be refunded
	else
	{
		$_SESSION['success_msg'] = "Please proceed to payment";
		header("Location:./pay.php?trans_id=$trans_id");
	}
}


// —————————————————— UTILITY  ——————————————————


// dynamically get the materials (mu_id, status, quantity) from page 
function get_material_statuses_from_page($mats_used) {
	$materials = array();
	foreach($mats_used as $mat_used) {
		$material = array();
		foreach(array("status", "quantity") as $header)
			$material[$header] = floatval(filter_input(INPUT_POST, $mat_used->mu_id."-".$header."_input_modal"));
		if(!$material["status"]) return null;  // check to make sure a value is always gotten
		$materials[$mat_used->mu_id] = $material;
	}
	return $materials;
}


// if an error message passes, add error to session, redirect (default: home)
function exit_if_error($error, $redirect=null) {
	if($error) {
		$_SESSION['error_msg'] = $error;
		if($redirect) header("Location:$redirect");
		else header("Location:/index.php");
		exit();
	}
}

?>

<title><?php echo $sv['site_name'];?> End Ticket</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-lg-12">
			<h1 class='page-header'>End Ticket</h1>
		</div>
	</div>
	<div class="row">
		<div class="col-md-10">
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fas fa-ticket-alt fa-fw"></i> Ticket #<?php echo $ticket->trans_id;?>
				</div>
				<div class="panel-body">
					<table id='main_table' class="table table-striped table-bordered">
						<tr>
							<td class='col-md-3'>Device</td>
							<td class='col-md-9'><?php echo $ticket->device->name;?></td>
						</tr>
						<tr>
							<td>Operator</td>
							<td>
								<div class="btn-group">
									<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
										<i class="<?php echo $ticket->user->icon;?> fa-lg" title="<?php echo $ticket->user->operator;?>"></i>
									</button>
									<ul class="dropdown-menu" role="menu">
										<li style="padding-left: 5px;"><?php echo $ticket->user->operator;?></li>
									</ul>
								</div>
							</td>
						</tr>
						<tr>
							<td>Ticket</td>
							<td><?php echo $ticket->trans_id;?></td>
						</tr>
						<tr>
							<td>Time</td>
							<td><?php echo $ticket->t_start." - ".$ticket->t_end; ?></td>
						</tr>
						<?php if($ticket->est_time) { ?>
							<tr>
								<td>Estimated Time</td>	
								<td><?php echo $ticket->est_time; ?></td>
							</tr>
						<?php 
						}
						if($ticket->duration) { ?>
							<tr>
								<td>Duration</td>
								<td><?php echo $ticket->duration; ?>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<td>Current Status</td>
							<td><?php echo $ticket->status->message; ?></td>
						</tr>
						<tr>
							<td>End Status</td>
							<td>
								<table width="100%">
									<tr>
										<td>
											<select id='ticket_status_select' class='form-control' onchange='adjust_materials_status(this);'>
												<option selected hidden>SELECT</option>
												<?php if($ticket->device->device_group->is_storable) { ?>
													<option value='<?php echo $status['stored']; ?>'
													<?php if(StorageObject::object_is_in_storage($ticket->trans_id)) echo "selected"; ?>>
														Storage
													</option>
													<option value='<?php echo $status['complete']; ?>'>Pick Up</option>
												<?php } 
												else {?>
													<option value='<?php echo $status['complete']; ?>'>Complete</option>
												<?php } ?>
												<option value='<?php echo $status['partial_fail']; ?>'>Partial Fail</option>
												<option value='<?php echo $status['total_fail']; ?>'>Total Fail</option>
												<option value='<?php echo $status['cancelled']; ?>'>Cancelled</option>
											</select>
										</td>
										<!-- current or selected storage information -->
										<?php 
											$in_storage = StorageObject::object_is_in_storage($ticket->trans_id);
											$hidden = $in_storage ? "" : "hidden";
											$storage_location = $in_storage ? StorageObject::get_unit_for_trans_id($ticket->trans_id) : "";
										?>
										<td id='storage_location_td' style='padding:4px;align:right' <?php echo $hidden; ?>>
											<span id='storage_location_span' onclick='reset_and_show_storage_modal();' style='background-color:#0055FF;
											border:4px solid #0055FF;border-radius:4px;padding:8px;margin:auto;color:#FFFFFF;'>
												<?php echo $storage_location; ?>
											</span>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td><i class="fas fa-edit"></i>Notes</td>
							<td>
								<textarea id='ticket_notes_textarea'
								class="form-control"><?php echo $ticket->notes;?></textarea>
							</td>
						</tr>
					</table>
				</div>
			</div>
		<!------------------ MATERIALS ------------------>
			<?php
			// if materials associated with ticket, or materials can be associated with ticket
			if($ticket->mats_used || $ticket->device->device_group->all_device_group_materials()) { ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fas fa-ticket-alt fa-fw"></i> Materials for Ticket #<?php echo $ticket->trans_id;?>
					</div>
					<div class="panel-body">
						<div id='mats_used'>
							<?php
							if($ticket->mats_used) 
								echo Mats_Used::group_mats_used_HTML($ticket->mats_used); 		
							?>
						</div>
						<?php if($ticket->device->device_group->all_device_group_materials())
						{
							?>
							<table class='table table-bordered table-striped'>
								<tr class='warning'>
									<td colspan='3'> Add material </td>
								</tr>
								<tr>
									<td class='col-sm-4'>Material</td>
									<td class='col-sm-5'>
										<select id='new_material' name='new_material' class='form-control'>
											<?php
											// allows for materials to added twice
											foreach($ticket->device->device_group->all_device_group_materials() as $material)
												echo "<option value='$material->m_id'>$material->m_name</option>";
											?>
										</select>
									</td>
									<td class='col-sm-4'>
										<button type='button' class='btn btn-info' 
										onclick='add_new_material_used(document.getElementById("new_material").value);'>
											Add Material
										</button>
									</td>
								</tr>
							</table>
							<?php
						}
					?>
					</div>
				</div>
			<?php } ?>
				<!------------------ COST AND SUBMIT ------------------>
					<table width='100%'>
						<tr>
							<td>
								<input type="button" value="End" class="btn btn-success" onclick='populate_end_modal();'/>
							</td>
							<td align='right'>
								<table>
									<tr>
										<td align='right'>Total:   <i class='<?php echo $sv['currency']; ?>'></i> </td>
										<td id='total' align='right'> <?php echo sprintf("%0.2f", $ticket->quote_cost()); ?> </td>
									</tr>
									<?php if($ticket->current_transaction_credit()) { ?>
										<tr>
											<td align='right'> Credit:   <i class='<?php echo $sv['currency']; ?>'></i> </td>
											<td id='credit' align='right'> <?php echo sprintf("%0.2f", $ticket->current_transaction_credit()); ?>  </td>
										</tr>
										<tr>
											<td align='right'>Remaining Balance:   <i class='<?php echo $sv['currency']; ?>'></i></td>
											<td id='remaining_balance' align='right'> <?php echo sprintf("%0.2f", $ticket->remaining_balance()); ?> </td>
										</tr>
									<?php } ?>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- ————————————————— MODALS ————————————————— -->

<!-- modal for selecting storage location -->
<div id='confirmation_modal' class='modal'>
	<div class='modal-dialog'>
		<div class='modal-content'>
			<form method='post'>
				<div class='modal-header'>
					<button type='button' class='close' onclick='$("#confirmation_modal").hide();'>&times;</button>
					<h4 class='modal-title'>End Ticket #<?php echo "$trans_id on ".$ticket->device->name; ?></h4>
				</div>
				<div id='confirmation_body' class='modal-body'>
					<table class='table'>
						<tr class='info'>
							<td>
								<h5> Ticket Status </h5>
							</td>
							<td id='ticket_status_confirmation'>
							</td>
						</tr>
					</table>
					<h5> Materials </h5>
					<table id='material_confirmation_table' class='table'>
					</table>

					<div id='storage_location_div_modal'>
						<h4 align='center'>
							<span style='background-color:#0055FF;border:4px solid #0055FF;
							border-radius:4px;padding:8px;margin:auto;color:#FFFFFF;margin:auto'>
								Currently stored in <span id='storage_location_span_modal'></span>
							</span>
							<!-- storage loaction posted from input -->
							<input id='storage_location_input_modal' name='storage_location_input_modal' 
							value='<?php echo $storage_location; ?>' hidden>
						</h4>
					</div>

					<div id='notes_confirmation' name='notes_confirmation' >
						<strong>NOTES: </strong>
						<!-- ticket notes posted from textarea -->
						<textarea id='ticket_notes_textarea_modal' name='ticket_notes_textarea_modal' 
						class='form-control' width='100%' readonly></textarea>
					</div>
				</div>
				<div class='modal-footer'>
					  <button type='button' class='btn btn-default' onclick='$("#confirmation_modal").hide();'>Change Info</button>
					  <button type='submit' name='end_button' class='btn btn-success'>Submit</button>
				</div>
			</form>
		</div>
	</div>
</div>



<!-- modal for selecting storage location -->
<div id='storage_modal' class='modal' style='overflow-y:auto;'>
	<div class='modal-dialog'>
		<div class='modal-content'>
			<div class='modal-header'>
				<button type='button' class='close' onclick='$("#storage_modal").hide();'>&times;</button>
				<h4 class='modal-title'>Store Object</h4>
			</div>
			<div class='modal-body'>
				<div class='input-group'>
					<span class='input-group-addon'>Object Type</span>
					<select id='storage_box_type_select' class='form-control' onchange ='get_unit_for_type(this.value);'>
						<option selected disabled hidden>—</option>
						<?php 
						$types = StorageUnit::types();
						if($types)
							foreach($types as $option) 
								echo "<option value='$option'>$option</option>";
						else echo "<option>ERROR: Could not get drawer types from DB</option>";
						?>
					</select>
				</div>
				<hr>
				<div class='input-group'>
					<span class='input-group-addon'>Drawer</span>
					<select id='storage_drawer_select' class='form-control' onchange ='get_drawer_availability(this.value);' >
						<option selected disabled hidden>—</option>
						<?php 
						$drawers = StorageDrawer::get_unique_drawers();
						if($drawers)
							foreach($drawers as $drawer) 
								echo "<option value='$drawer'>$drawer</option>";
						else echo "<option>ERROR: Could not get drawer types from DB</option>";
						?>
					</select>
				</div>
				<div id='drawer_fill' align='center'>
					<!-- AJAX: unit selection field -->
				</div>
				<div class='modal-footer'>
					  <button type='button' class='btn btn-default' onclick='$("#storage_modal").hide();'>Cancel</button>
				</div>
			</div>
		</div>
	</div>
</div>

<?php 
include_once($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>

<script>
	var COLORS = ["DD00DD", "0F8DFF", "339933", "FFFF00", "888800", "FF0000"];


	// ————————————— INPUT-STATUS CONTROL —————————————–
	// ———————————————————————————————————————
	/* This is a large chunk of the JS.  Its gloal is to control the values of inputs based on 
	statuses and statuses based on inputs.
	CONTROLS:
		TICKET:
		-A ticket status of cancelled with default to all materials being used.  This way they 
		 must justify why/how a material is not used.  Gives inventory/FabLab benefit of the 
		 doubt.
		-A ticket status of cancelled may not have any failed_mat statuses, because a failed &
		 sellable ticket means it should be marked as partial_fail.
		-If no materials are used, then default to total fail, because we assume that a ticket 
		 failed before it even started is more likely than individual starting & cancelling before
		 any cost accrued.
		MATERIALS:
		-If all materials are marked as not failed, the ticket status is marked as complete.  B/c
		 it is materials that are being marked, page assumes that the user is completing materials
		 individually and is unsure of ticket status.  Otherwise, they would either mark it initially
		 as complete, or have an unused/failed material.
		-If a mat_used has a status of unused, the quantity is set to 0. If the quantity is 
		 changed then the status must be reselected, because it is no longer unused.
		-When a material status is set to unused, the input value is stored in a dictionary, in
		 case the user decides to revert the status or misclicked.
	*/

	/* 
	object to store data for quantity inputs and calculate/set quantities with methods.
	Object was chosen because it allows for its quantity to be available and set regardless of 
	whether the input is a time.  It takes the parameter of a input element, finds it (and its 
	associated elements ie time, parent, status).
	*/
	class Input {
		constructor(input) {
			this.mu_id = input.id.substr(0, input.id.indexOf('-'));
			this.element; 
			this.is_time_based;
			this.initialize_element_and_type(input);
			this.subtotal = this.subtotal_from_classes(input);
			this.price = parseFloat(input.parentElement.children[0].innerHTML.match(/\d+(\.\d+)?/g));
			this.status = document.getElementById(this.mu_id+"-select");
		}


		// if input is time based assign appropriate elements for it
		initialize_element_and_type(input) {
			if(input.classList.contains("time")) 
				this.element = {	"hour" : document.getElementById(this.mu_id+"-input"), 
									"min" : document.getElementById(this.mu_id+"-minute")}, 
				this.is_time_based = true;
			else {
				this.element = input
				this.is_time_based = false;
			}
		}


		// return numeric quantity for input(s) associated
		// because it filters for NAN, there is a possibility that a text value slips through
		quantity() {
			if(this.is_time_based)
			{
				var hour_quant = parseFloat(this.element['hour'].value);
				var min_quant = parseFloat(this.element['min'].value) / 60;
				if(isNaN(hour_quant)) hour_quant = 0;  // Esau found this bug
				if(isNaN(min_quant)) min_quant = 0;
				return hour_quant + min_quant / 60;
			}
			else
			{
				var quant = parseFloat(this.element.value);
				if(isNaN(quant)) return 0;
				return quant;
			}

		}


		// set quantity based on quantity passed and parse into hour/minute if necessary
		set_val(quantity) {
			if(!this.is_time_based) this.element.value = parseFloat(quantity);
			else {
				var hour = parseInt(quantity);
				var minute = (quantity - hour) * 60;
				this.element["hour"].value = hour;
				this.element["min"].value = round(minute, 2);
			}
		}


		// retrieve the parent name of a class (<parent_name>-input); return null if no parent name
		subtotal_from_classes(input) {
			for(var x = 0; x < input.classList.length; x++)
				if(input.classList[x].includes("-input"))
					return document.getElementById(input.classList[x].substr(0, input.classList[x].indexOf('-input'))+"-subtotal");
			return null;
		}
	}





	// {'mu_id' : quantity, etc.} to hold values before status switching causes value to equal 0
	var previous_mats_used_quantities = {};


	// ————–———— QUANTITY CHILD-PARENT CALCULATION ——————————

	// as group total is changed, change individual units used proportionally for new total
	function adjust_children_input(parent_input) {
		var children = children_input_objects(parent_input.id.substr(0, parent_input.id.indexOf("-subtotal")));
		var quantity_sum = 0;
		// get previous sum & create input objects
		for(var x = 0; x < children.length; x++) {
			if(!isNaN(children[x].quantity()))
				quantity_sum += children[x].quantity();
			else console.log(children[x]);  // error checking
		}

		for(var x = 0; x < children.length; x++)
			children[x].set_val(round(children[x].quantity() / quantity_sum * parent_input.value, 2));

		adjust_balances();
	}


	// as individual units used are changed, change group total units for new individual; adjust statuses
	function adjust_parent_input(child_input_element) {
		var mu_input = new Input(child_input_element);
		if(!mu_input.subtotal) return;  // ignore ungrouped elements

		var children = children_input_objects(mu_input.subtotal.id.substr(0, mu_input.subtotal.id.indexOf("-subtotal")));
		var group_total = 0;
		for(var x = 0; x < children.length; x++) {
			if(!isNaN(children[x].quantity()))
				group_total += children[x].quantity();
			else console.log(children[x]);  // error checking
		}

		mu_input.subtotal.value = group_total;
	}


	// ——————————–——— COST CALCULATION ———————————————

	// as each mat used changes, calculate total cost (& remaining balance) for units provided
	function adjust_balances() {
		var total = calculate_total();
		var remaining_balance = calculate_remaining_balance(total);

		document.getElementById("total").innerHTML = round(total, 2);
		if(document.getElementById("credit"))
			document.getElementById("remaining_balance").innerHTML = round(remaining_balance, 2);
	}


	// total cost for all materials used without fail (does not include credit)
	function calculate_total() {
		var mats_used = create_inputs_by_class_name("mat_used_input");
		var total = 0;
		for(var x = 0; x < mats_used.length; x++)
			if(mats_used[x].status.value != <?php echo $status['unused']; ?> && mats_used[x].status.value != <?php echo $status['failed_mat']; ?>)
				total += mats_used[x].quantity() * mats_used[x].price;
		return total;
	}


	// amount to be charged 
	function calculate_remaining_balance(total=null) {
		var credit = document.getElementById("credit");
		if(!credit) return;  // no credit found; no need to try to caluclate remaining balance

		credit = parseFloat(credit.innerHTML);
		if(total) return total - credit;  // option to save the resources
		return calculate_total() - credit;
	}


	// ————————————————— STATUS —————————————————
	// Ticket Status-Material Status Relationship:
	//	Complete, Stored, Cancelled:
	//		- Used -or- Unused
	//		DEFAULT: all to Used
	//	Partial:
	//		- 1 <= Failed
	//		- 1 < Materials
	//		- 1 Used
	//	Total:
	//		- Unused -or- Failed
	//		- 1 <= Failed

	// ———— TICKET-MAT_USED RELATIONSHIP ————

	// ticket status has been changed: adjust materials' statuses
	function adjust_materials_status(ticket_status_object) {
		document.getElementById("storage_location_td").hidden = true;  // clear prior storage location
		var ticket_status = ticket_status_object.value;

		if(ticket_status == <?php echo $status["partial_fail"]; ?>
		|| ticket_status == <?php echo $status["total_fail"]; ?>)
			default_all_material_statuses_to_status();
		// stored, cancelled, complete
		// cancelled included b/c user more willing to check off what they didn't use
		else
		{
			default_all_material_statuses_to_status(<?php echo $status["used"]; ?>);
			if(ticket_status == <?php echo $status["stored"]; ?>) reset_and_show_storage_modal();
		}
	}


	//NOTE: the logic is still good, but to prevent confusion, this feature has been disabled
	// material status changed: adjust ticket status
	function adjust_ticket_status(status_element) {
		return;
		// // all materials being used (!failed) means ticket was complete
		// if(all_material_status_are(<?php echo $status['used']; ?>)) 
		// 	document.getElementById("ticket_status_select").value = <?php echo $status['complete']; ?>;
		// // if no materials were used then nothing is usable and is a total fail
		// else if(all_material_status_are(<?php echo $status['unused']; ?>)) 
		// 	document.getElementById("ticket_status_select").value = <?php echo $status['total_fail']; ?>;
	}


	// ———— INPUT-STATUS RELATIONSHIP ————

	// change value to 0 if not used; reset value if changed back
	function adjust_input_for_status(status_element) {
		var input = input_for_status(status_element);
		// prevent unused materials from having any quantity
		if(parseInt(status_element.value) == <?php echo $status['unused']; ?>) {
			// store previous value into dictionary for mu_id for reverting when changing status back from unused
			previous_mats_used_quantities[input.mu_id] = input.quantity();
			input.set_val(0);
		}
		else if(!input.quantity() && previous_mats_used_quantities[input.mu_id]) {
			input.set_val(previous_mats_used_quantities[input.mu_id]);
			delete previous_mats_used_quantities[input.mu_id];  // inaccurate replacements
		}
		// recalulate amounts
		var input_element = document.getElementById(input.mu_id+"-input");
		adjust_parent_input(input_element);
		adjust_balances();		
	}


	// auto select statuses based on input values
	function adjust_status_for_input(input_element) {
		var mu_input = new Input(input_element);

		// don't allow non-zero elements to have "unused" status
		if(mu_input.quantity() && mu_input.status.value == <?php echo $status['unused']; ?>)
			mu_input.status.selectedIndex = "0";
		// don't allow zero values to have status of used or failed
		else if(!mu_input.quantity())
			mu_input.status.value = <?php echo $status['unused']; ?>;
	}


	// ———— STATUS UTILITY ————

	// check if all of the materials have the same status as status passed
	function all_material_status_are(status)
	{
		var materials_statuses = document.getElementsByClassName("mat_used_select");
		for(var x = 0; x < materials_statuses.length; x++)
		{
			var material_status_value = parseInt(materials_statuses[x].value);
			// if multiple statuses acceptable
			if(typeof status === typeof [])
			{
				// material_status is not in desired statuses
				if(!any(	status, 
						function(stat_x, mat_stat){return stat_x == mat_stat;},
						material_status_value
						)
				) return false;
			}
			else if(material_status_value != status) return false;  // single status accepted
		}
		return true;
	}


	// return Input object for a status element
	function input_for_status(status_object) {
		var mat_used_id = mat_used_id_of_element(status_object);
		return new Input(document.getElementById(mat_used_id+"-input"));
	}


	// 
	function set_status_for_all_materials_to_used_if_status_quantity_not_null() {
		var materials_statuses = document.getElementsByClassName("mat_used_select");
		for(var x = 0; x < materials_statuses.length; x++)
			// a material is measurable && not used if its value is 0
			if(materials_statuses[x].classList.contains("measurable") && input_for_status(materials_statuses[x]).quantity())
				materials_statuses[x].value = <?php echo $status["used"]; ?>;
	}

	function default_all_material_statuses_to_status(status=null)
	{
		var materials_statuses = document.getElementsByClassName("mat_used_select");
		for(var x = 0; x < materials_statuses.length; x++)
		{
			if(status) materials_statuses[x].value = status;
			else materials_statuses[x].selectedIndex = 0;
		}
	}


	// ————————————— ADD NEW MATERIAL USED —————————————
	// ———————————————————————————————————————

	/* AJAX: add mat_used to DB for transaction.  If preexisting group, add material to group.
	If preexisting (ungrouped) material, create group.  Otherwise, add material input to end
	of table. */
	function add_new_material_used(m_id) {
		if(isNaN(parseInt(m_id))) return;
		if(!confirm("Are you sure you would like to add another material to this transaction?")) return;
		
		// add_new_material: request function from page (new material instance created, return HTML)
		// edit_request: request coming from edit.php (add functions/staff row)
		$.ajax({
			url: "./sub/material_ajax_requests.php",
			type: "POST",
			dataType: "json",
			data: {	"add_new_material" : true, 
					"m_id" : m_id,
					"trans_id" : <?php echo $ticket->trans_id; ?>
			},
			success: function(response) {
				console.log(response);
				if(response["error"]) {
					alert(response["error"]);
					return;
				}

				update_material_display(response);

				alert("Successfully added "+response["material_name"]+" to materials");
				$(`#new_material`).val("");
			}
		});
	}


	// split material used into two mat_used instances with the same material
	function split(m_id) {
		add_new_material_used(m_id);
	}


	// add mat_used to page
	function update_material_display(response) {
		// delete newly grouped prior instance from page
		if(response["grouplength"] == 2)
			document.getElementsByClassName(`${response["parent_id"]}-child`)[0].parentElement.closest("table").remove();

		// add to table (1, 2) (3)
		if(response["grouplength"] < 3)
			document.getElementById("mats_used").innerHTML += response["material_HTML"];
		else
			document.getElementById(`${response["parent_id"]}-children_display_row`).children[0].innerHTML += response["material_HTML"];

		var new_mat_used = document.getElementById(response["mu_id"]+"-table");
		// color if new group (2) (do not need for grouplength of 1 b/c automatically highlighted)
		if(response["grouplength"] == 2) {
			var new_entrant = new_mat_used.parentElement.closest("table");
			var color = COLORS[new_entrant.parentElement.children.length-1 % COLORS.length];
			new_entrant.style["border-left"] = `#${color} 2px solid`;  // add color to part
			new_entrant.getElementsByTagName("td")[0].style["background-color"] = `#${color}`;  // color top bar (row) of table
		}

		// highlight table and scroll to change (*)
		new_mat_used.style.border = "#00FF00 8px solid";
		location.href = `#${response["mu_id"]}-table`;
	}


	// ————————————–—— END CONFIRMATION ——————————————
	// ———————————————————————————————————————

	// ——————————————— POPULATE MODAL ———————————————

	// get information from page and put into confirmation modal
	function populate_end_modal() {
		// ---- ticket ----
		var ticket_status_select = document.getElementById("ticket_status_select");
		var ticket_status_name = ticket_status_select.options[ticket_status_select.selectedIndex].text;
		var ticket_status = ticket_status_select.value;
		document.getElementById("ticket_status_confirmation").innerHTML = 
			confirmation_cell_format('ticket_status', `<h5>${ticket_status_name}</h5>`, ticket_status_select.value);

		// ---- materials ----
		var materials = get_and_sort_materials();
		if(ticket_or_material_status_not_properly_populated(materials, ticket_status)) return;  // error checking
		populate_material_table(materials);

		// ---- storage ----
		// status is not storage
		if(ticket_status != <?php echo $status["stored"] ?>) $("#storage_location_div_modal").hide();
		// status is storage and location selected
		else if(document.getElementById("storage_location_input_modal").value)
			$("#storage_location_div_modal").show();
		// status is storage but location not selected
		else
		{
			ticket_status_select.selectedIndex = 0;
			return alert("Status marked as storage but no location was selected");
		}

		// ---- notes ----
		document.getElementById("ticket_notes_textarea_modal").value = 
			document.getElementById("ticket_notes_textarea").value;

		$("#confirmation_modal").show();
	}


	// using the material dictionary, add values to material table in modal
	function populate_material_table(materials) {
		$("#material_confirmation_table tr").remove();  // clear previous entries
		if(!materials) return;

		var table = document.getElementById("material_confirmation_table");
		
		// create/add table headers
		var header = table.insertRow(-1);
		var header1 = header.insertCell(0);
		var header2 = header.insertCell(1);
		var header3 = header.insertCell(2);
		var header4 = header.insertCell(3);

		header1.innerHTML = "<h5>Material</h5>";
		header2.innerHTML = "<h5>Status</h5>";
		header3.innerHTML = "<h5>Quantity</h5>";
		header4.innerHTML = "<h5>Cost</h5>";

		// add values
		for(var x = 0; x < materials.length; x++)
			populate_material_table_row(materials[x], table.insertRow(-1));

		// add total to modal
		var total_row = table.insertRow(-1);
		total_row.classList.add("warning");
		var total_title = total_row.insertCell(0);
		total_title.innerHTML = "<h5 align='left'>Total</h5>";
		total_title.colSpan = "3";
		var total_value = total_row.insertCell(1);
		total_value.innerHTML = `<h5><i class='<?php echo $sv["currency"]; ?>'></i>${document.getElementById("total").innerHTML}</h5>`;

		if(materials.length)  // center values
			$("#material_confirmation_table tr td").not("first-input").attr("align", "center");
	}


	// used by populate_material_table(.) to create a row and store values in it
	function populate_material_table_row(material, row) {
		var status_name = material['status'].options[material['status'].selectedIndex].text;

		var mat = row.insertCell(0);
		var status = row.insertCell(1);
		var quantity = row.insertCell(2);
		var cost = row.insertCell(3);

		mat.innerHTML = material['name'];
		status.innerHTML = confirmation_cell_format(material['mu_id']+'-status', status_name, material['status'].value);

		if(!material["immeasurable"]) {
			quantity.innerHTML = confirmation_cell_format(material['mu_id']+'-quantity', material['quantity'], material['quantity']);
			cost.innerHTML = `<i class='<?php echo $sv["currency"]; ?>'></i>${round(material['cost'], 2)}`;
		}
	}


	// ————————————— PULL & ORGANIZE DATA ——————————————

	// get materials by class; get inputs, selects, m_name, mu_id & add to dict; dict to array
	function get_and_sort_materials() {
		var materials = [];

		// add measurable materials to mat list
		var materials_inputs = create_inputs_by_class_name("mat_used_input");
		for(var x = 0; x < materials_inputs.length; x++) {
			var material = dictionary_of_measurable_material(materials_inputs[x]);
			if(!material) return null;  // submission error: end process
			materials.push(material);
		}

		// add immeasurable materials to mat list
		var immeasurable_select = document.getElementsByClassName("immeasurable");
		for(var x = 0; x < immeasurable_select.length; x++)
			materials.push(dictionary_for_immeasurable_material(immeasurable_select[x]));

		return materials;
	}


	function dictionary_for_immeasurable_material(immeasurable_select) {
		var name = material_name(immeasurable_select);
		var status = immeasurable_select;
		var mu_id = immeasurable_select.id.substr(0, immeasurable_select.id.indexOf('-'));

		return {"mu_id" : mu_id, "name" : name, "status" : status, "immeasurable" : true};
	}


	function dictionary_of_measurable_material(material) {
		var cost = material.quantity() * material.price;
		var name = material_name(material.status);

		// --error/logic checking
		if(isNaN(parseFloat(material.status.value))) {
			 alert("Please select a status for "+name);
			 return null;
		}
		else if(material.status.value == <?php echo $status['used']; ?> && !material.quantity()) { 
			alert("Material status cannot be used with a 0 quantity for "+name);
			return null;
		}

		return {'mu_id' : material.mu_id, 'name' : name, 'cost' : cost, 'quantity' : material.quantity(), 'status' : material.status};
	}



	// ——————————————— DATA VALIDATION ———————————————

	// check statuses and quantities as being properly (logically) filled
	function ticket_or_material_status_not_properly_populated(materials, ticket_status)
	{
		if(isNaN(ticket_status))
			return alert_and_return_true("Please select a ticket status");
		if(!materials && document.getElementsByClassName("mat_used_select").length)
			return true;
		// check that all material statuses are valid
		if(!all_material_status_are([<?php echo $status["failed_mat"]; ?>, 
									<?php echo $status["used"]; ?>,
									<?php echo $status["unused"]; ?>])
		) return alert_and_return_true("One or more materials' status is not populated");

		// check material statuses for failed tickets
		if(ticket_status == <?php echo $status["partial_fail"]; ?>
		|| ticket_status == <?php echo $status["total_fail"]; ?>)
			return failed_ticket_material_statuses_are_invalid(materials, ticket_status);
		else return nonfailed_ticket_material_statuses_are_invalid();
	}


	function failed_ticket_material_statuses_are_invalid(materials, ticket_status)
	{
		// make sure a note is stated
		if(document.getElementById("ticket_notes_textarea").value.length < 10)
			return alert_and_return_true("You must state how the ticket failed");
		// require 1 failed material: none found
		console.log(materials);
		if(!any(	materials,
				function(mat, value){return mat["status"].value == value;},
				<?php echo $status["failed_mat"]; ?>)
		) return alert_and_return_true("Any failed ticket requires at least 1 failed material");

		// cover paritally failed ticket specific requirements
		if(ticket_status == <?php echo $status["partial_fail"]; ?>)
		{
			// require 2 or more materials (otherwise one failed material sold is complete)
			if(materials.length < 2) 
				return alert_and_return_true("A partially failed material must have more than one material");
			// check that one material is marked as used to make it sellable
			if(!any(	materials,
					function(mat, value){return mat["status"] == value;},
					<?php echo $status["used"]; ?>)
			) return alert_and_return_true("To be sellable, a partially failed material must have a used material");
		}
		// cover totally failed ticket specific requirements: all mats are unused or failed
		else if(!all_material_status_are([<?php echo $status["failed_mat"]; ?> , <?php echo $status["unused"]; ?>]))
			return alert_and_return_true("Failed materials may only have a status of failed or unused");
	}


	function nonfailed_ticket_material_statuses_are_invalid(materials, ticket_status)
	{
		// materials are either used or unused
		return !all_material_status_are([<?php echo $status["used"]; ?>, <?php echo $status["unused"]; ?>]);
	}




	// create innerHTML for a cell using text and a hidden input
	function confirmation_cell_format(name, text, value) {
		return `${text}<input name='${name}_input_modal' value='${value}' hidden/>`;
	}


	// retrieve the name of a material based on a given input
	// ascend up: status -> td -> tr -> table; down: tr[0] -> td [0] -> text; remove newline
	function material_name(status_element) {
		var ancestor = status_element.parentElement.parentElement.parentElement
		return ancestor.children[0].children[0].textContent.trim();
	}


	// ——————————————— OBJECT STORAGE ———————————————
	// ———————————————————————————————————————

	// a drawer is selected; load drawer data
	function get_drawer_availability(drawer_label)
	{
		document.getElementById("storage_box_type_select").selectedIndex = 0;

		$.ajax({
			url: "./sub/storage_ajax_requests.php",
			type: "POST",
			dataType: "json",
			data: {"choose_unit_in_drawer" : true, "drawer_label" : drawer_label},
			success: function(response) {
				if(response["error"]) {
					alert(response["error"]);
					return;
				}
				console.log(response);

				// add stuff to page
				var message =	`Please place the object into a box in drawer ${response["drawer_label"]}, `+ 
									`then confirm its placement by clicking the box on the screen`;
				var drawer_title = 	`<h3>Drawer ${response["drawer_label"]} </h3>`;
				document.getElementById("drawer_fill").innerHTML = message + drawer_title + response["drawer_HTML"];
			},
			error: function(error)
			{
				console.log(error);
			}
		});		
	}


	// call to modal to get first available box with type; create drawer layout & insert into modal
	function get_unit_for_type(unit_type)
	{
		document.getElementById("storage_drawer_select").selectedIndex = 0;

		$.ajax({
			url: "./sub/storage_ajax_requests.php",
			type: "POST",
			dataType: "json",
			data: {"drawer_for_type" : true, "unit_type" : unit_type},
			success: function(response) {
				if(response["error"])
				{
					alert(response["error"]);
					return;
				}

				// add stuff to page
				var message =	`Please place the object into drawer ${response["drawer_label"]} box ${response["unit_label"]}, `+ 
									`then confirm its placement by clicking the highlighted box on the screen`;
				var drawer_title = 	`<h3>Drawer ${response["drawer_label"]} </h3>`;
				document.getElementById("drawer_fill").innerHTML = message + drawer_title + response["drawer_HTML"];
			},
			error: function(error)
			{
				console.log(error);
			}
		});
	}


	function add_to_location(element, box_id)
	{

		// set and show the blue tag for storage location
		// called by box click on confirmation of object placement
		document.getElementById("storage_location_td").hidden = false;
		document.getElementById("storage_location_span").innerHTML = box_id;

		// set value for PHP and modal
		document.getElementById("storage_location_span_modal").innerHTML = box_id;
		document.getElementById("storage_location_input_modal").value = box_id;

		setTimeout(function() {
			$('#storage_modal').fadeOut('fast');
		}, 250);
	}


	// clear values in storage modal when opened to allow for resetting of values
	function reset_and_show_storage_modal() {
		document.getElementById("drawer_fill").innerHTML = "";
		document.getElementById("storage_drawer_select").value = "—";
		document.getElementById("storage_box_type_select").value = "—";
		$("#storage_modal").show();
	}



	// —————————————————— UTILITY —————————————————
	// ———————————————————————————————————————

	function alert_and_return_true(message)
	{
		alert(message);
		return true;
	}


	// if any of the items is relevant to the /usage function (eg contains /value), return true
	function any(list, usage, value) {
		for(var x = 0; x < list.length; x++)
			if(usage(list[x], value)) return true;
		return false;
	}


	function children_input_objects(parent_id) {
		var children = [];
		var elements = document.getElementsByClassName(parent_id+"-input");
		for(var x = 0; x < elements.length; x++)
			if($(elements[x]).find("input"))
				children.push(new Input(elements[x]));
		return children;
	}


	// for every element in class_name passed, create an input object
	function create_inputs_by_class_name(class_name) {
		var inputs = [];
		var elements = document.getElementsByClassName(class_name);
		for(var x = 0; x < elements.length; x++)
			inputs.push(new Input(elements[x]));
		return inputs;
	}


	// get the mat_used id number from an element
	function mat_used_id_of_element(element) {
		return element.id.substr(0, element.id.indexOf('-'));
	}


	// because JS does not have a good rounding function, copied one from StackOverflow
	function round(float, decimal) {
		if(!float) return 0;
		return Number(Math.round(float+`e${decimal}`)+`e-${decimal}`).toFixed(decimal);
	}

</script>