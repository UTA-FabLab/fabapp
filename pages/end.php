<?php


/***********************************************************************************************************
*	
*	@author Jon Le
*	Overhauled by: MPZinke on 07.21.19 to add Multiple Material (MultiMaterial Project) 
*	 editing functionalities. Improved commenting an logic/functionality of page; update in 
*	 accordance with future class changes.
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
	$ticket_status = filter_input(INPUT_POST, "ticket_status");  // update ticket
	// prevent frontend changing of variables to cheat backend
	if($ticket_status >= $status['charge_to_acct']) exit_if_error("End: Ticket status is invalid.");
	$error = $ticket->end_transaction($staff, $ticket_status);
	exit_if_error($error, "./end.php?trans_id=$trans_id");

	$ticket_notes = htmlspecialchars(filter_input(INPUT_POST, "ticket_notes"));
	if($ticket_notes) exit_if_error($ticket->edit_transaction_information(array("notes" => $ticket_notes)));

	// completely failed ticket; nothing to pay for
	if($ticket_status == $status['total_fail']) {
		$_SESSION['success_msg'] = "Ticket successfully ended.";
		header("Location:./lookup.php?trans_id=$trans_id");
	}
	// store object
	elseif($ticket_status == $status['stored']) {
		if(!$location = filter_input(INPUT_POST, "storage")) exit_if_error("End: Could not retrieve storage location from page");
		$error = StorageObject::add_object_to_location_from_possible_previous($location, $staff, $trans_id);
		exit_if_error($error, "./lookup.php?trans_id=$trans_id");
		// nothing more to do for ticket; print message and go to home
		$_SESSION['success_msg'] == "Successfully ended ticket #$trans_id";
		header("Location:/index.php");
	}
	// already paid for; process is finished
	elseif(!$ticket->remaining_balance()) {
		$ticket->edit_transaction_information(array("status_id" => $status['charge_to_acct']));
		$_SESSION['success_msg'] = 	"There is no balance on the ticket. It is finished and ".
											"learner is good to go.";
		header("Location:./lookup.php?trans_id=$trans_id");
	}
	// proceed to payment; if balance is negative, this is where they should be refunded
	else {
		$_SESSION['success_msg'] == "Please proceed to payment";
		header("Location:./pay.php?trans_id=$trans_id");
	}
}




// ——————————— MATERIAL GROUPING AND PRINTING ———————————

// function called to create inputs and statuses
function group_materials_by_parent_and_create_inputs($mats_used) {
	// group materials by parent
	$m_parents = $group_quantity_used = $parentless = array();
	foreach($mats_used as $mu) {
		// combine with previously added 
		if(array_key_exists($mu->material->m_parent->m_name, $m_parents)) {
			$m_parents[$mu->material->m_parent->m_name][] = $mu;
			$group_quantity_used[$mu->material->m_parent->m_name] += $mu->quantity_used;
		}
		// create new parent
		elseif($mu->material->m_parent->m_name) {
			$m_parents[$mu->material->m_parent->m_name] = array($mu);
			$group_quantity_used[$mu->material->m_parent->m_name] = $mu->quantity_used;
		}
		else $parentless[] = $mu;
	}

	// create grouped inputs
	$material_groups = array();
	foreach($m_parents as $parent_name => $group)
		if(count($group) == 1)
			$material_groups[] =	"<table class='table table-bordered' style='margin-bottom:0px !important;'>".
										create_material_html_block($group).
									"</table>";
		else
			$material_groups[] = materials_group_table_block($group, $parent_name, $group_quantity_used[$parent_name]);

	// create ungrouped inputs
	foreach($parentless as $single_material)
		$material_groups[] = 	"<table class='table table-bordered' style='margin-bottom:0px !important;'>".
									create_material_html_block(array($single_material)).
								"</table>";
	return $material_groups;
}


// create a material group with a parent element and the children material blocks to the right
function materials_group_table_block($group, $parent_name, $quantity) {
	global $sv;

	$parent_code = str_replace(" ", "_", $parent_name);  // name excluding spaces for id's and classes
	return 	"<table width='100%' class='table table-bordered' style='margin-bottom:0px !important;'>
				<tr class='tablerow info'>
					<td colspan='2'>$parent_name</td>
				</tr>
				<tr>
					<td>
						<div class='input-group'>
							<span class='input-group-addon'>MATERIAL-GROUP <i class='$sv[currency]'></i> ".sprintf("%0.2f", $group[0]->material->price)." x </span>
							<input type='number' id='$parent_code' class='form-control' autocomplete='off' value='$quantity' style='text-align:right;'
							onkeyup='adjust_children_input(this); adjust_balances();' onchange='adjust_children_input(this); adjust_balances();' >
							<span class='input-group-addon'>".$group[0]->material->unit."</span>
						</div>
					</td>
					<td style='padding:0px;'>
						<table class='table table-striped' style='margin-bottom:0px !important;'>".
							create_material_html_block($group).
						"</table>
					</td>
				</tr>
			</table>\n";
}


// create a <tr> for a mat used instance: include name, input, status selection
function create_material_html_block($group) {
	global $status, $sv;
	$individual_materials = array();  // html blocks (rows of tables) to be imploded together

	foreach($group as $mu) {
		$mat = $mu->material;  // sugar

		// —— SELECT ——
		// if(!$mu->quantity_used) $default_selection = "selected";  // if not mats used, change selected to Not Used
		$measurability = $mu->material->is_measurable ? "measurable" : "immeasurable";
		$select = 	"<select id='$mu->mu_id-select' class='form-control mat_used_select $measurability' 
					onchange='adjust_ticket_status(this); adjust_input_for_status(this);'>
						<option selected hidden>SELECT</option>
						<option value='$status[used]'>Used</option>
						<option $default_selection value='$status[unused]'>Not Used</option>
						<option value='$status[failed_mat]'>Failed Material</option>
					</select>";

		// —— MAT QUANTITY INPUT ——
		if($mu->material->is_measurable) 
			$quantity_input =	"<tr>
									<td>
										".material_quantity_input($mu)."
									</td>
								</tr>";
		// name excluding spaces for id's and classes
		$parent_code = $mu->material->m_parent ? str_replace(" ", "_", $mu->material->m_parent->m_name) : "";

		$individual_materials[] = 	"<td>
										<table class='table $parent_code' width='100%' style='margin-bottom:0px !important;'>
											<tr class='tablerow info'>
												<td>
													$mat->m_name".
													($mat->color_hex ? " <div class='color-box' style='background-color:#$mat->color_hex;' align='left'/>" : null).
												"</td>
											</tr>
											<!-- row for quantity if material is measurable -->
											$quantity_input
											<tr class='mat_select_row'>
												<td>
													$select
												</td>
											</tr>
										</table>
									</td>";
	}
	return "<tr>".implode("</tr>\n<tr>", $individual_materials)."</tr>";
}


// create input html for quanities (either time or standard): used by create_material_html_block(.)
function material_quantity_input($mat_used) {
	global $sv;

	// name excluding spaces for id's and classes
	$parent_code = $mat_used->material->m_parent ? str_replace(" ", "_", $mat_used->material->m_parent->m_name) : "";

	if($mat_used->material->unit == "hour(s)") {
		$min_hours = intval($sv['minTime']);

		$hour = floor($mat_used->quantity_used);
		$minute = ($mat_used->quantity_used - $hour) * 60;
		return	"<div class='input-group'>
					<span class='input-group-addon'><i class='$sv[currency]'></i> ".sprintf("%0.2f", $mat_used->material->price)." x </span>
					<input type='number' id='$mat_used->mu_id-input' class='form-control mat_used_input time $parent_code-child' 
					onkeyup='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' 
					onchange='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' 
					autocomplete='off' style='text-align:right;' min='$min_hours' step='1' value='$hour'>
					<span class='input-group-addon'>Hours</span>

					<input type='number' id='$mat_used->mu_id-minute' class='form-control time' 
					onkeyup='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' 
					onchange='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' 
					autocomplete='off' style='text-align:right;' min='0' max='59' value='$minute'>
					<span class='input-group-addon'>Minutes</span>
				</div>";
	}
	return 	"<div class='input-group'>
				<span class='input-group-addon'><i class='$sv[currency]'></i> ".sprintf("%0.2f", $mat_used->material->price)." x </span>
				<input type='number' id='$mat_used->mu_id-input' class='form-control mat_used_input $parent_code-child' 
				onkeyup='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' 
				onchange='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' 
				autocomplete='off' value='".sprintf("%0.2f", $mat_used->quantity_used)."' style='text-align:right;' min='0'/>
				<span class='input-group-addon'>".$mat_used->material->unit."</span>
			</div>";
}



// —————————————————— UTILITY  ——————————————————


// dynamically get the materials (mu_id, status, quantity) from page 
function get_material_statuses_from_page($mats_used) {
	$materials = array();
	foreach($mats_used as $mat_used) {
		$material = array();
		foreach(array("status", "quantity") as $header)
			$material[$header] = floatval(filter_input(INPUT_POST, $mat_used->mu_id."-".$header));
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
					<table class="table table-striped table-bordered">
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
											<select id='ticket_status' name='ticket_status' class='form-control' onchange='adjust_materials_status(this);'>
												<option selected hidden>SELECT</option>
												<?php if($ticket->device->device_group->is_storable) { ?>
													<option value='<?php echo $status['stored']; ?>'>Storage</option>
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
										<td id='storage_location' style='padding:4px;align:right' hidden>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td><i class="fas fa-edit"></i>Notes</td>
							<td>
								<textarea name='ticket_notes' id='ticket_notes'
								class="form-control"><?php echo $ticket->notes;?></textarea>
							</td>
						</tr>
					<!------------------ MATERIALS ------------------>
						<?php
						$material_groups = group_materials_by_parent_and_create_inputs($ticket->mats_used);
						foreach($material_groups as $mat_used) {
							echo	"<tr>
										<td colspan='2'>
											$mat_used
										</td>
									</tr>";
						 } ?>
						 <!-- NEW MATERIAL -->
						<tr>
							<td colspan='2'>
								<table class='table table-bordered table-striped'>
									<tr>
										<td colspan='3'> Add material </td>
									</tr>
									<tr>
										<td class='col-sm-4'>Material</td>
										<td class='col-sm-5'>
											<select id='new_material' name='new_material' class='form-control'>
												<?php
												// allows for materials to added twice
												foreach($ticket->device->device_group->optional_materials as $optional_material)
													echo "<option value='$optional_material->m_id'>$optional_material->m_name</option>";
												?>
											</select>
										</td>
										<td class='col-sm-4'>
											<button type='button' name='new_material_button' class='btn btn-success' onclick='add_new_material_used();'>Add Material</button>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
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
					<div id='storage_confirmation' hidden>
						<h3 id='storage_confirmation_location'></h3>
					</div>
					<div id='notes_confirmation'>
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
<div id='storage_modal' class='modal'>
	<div class='modal-dialog'>
		<div class='modal-content'>
			<div class='modal-header'>
				<button type='button' class='close' onclick='$("#storage_modal").hide();'>&times;</button>
				<h4 class='modal-title'>Store Object</h4>
			</div>
			<div class='modal-body'>
				<div class='input-group'>
					<span class='input-group-addon'>Object Type</span>
					<select class='form-control' onchange='get_box_for_type(this);'>
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

	// bring status numbers (dynamically) to front end
	var global_status = {<?php echo "'total_fail' : $status[total_fail], 'partial_fail' : $status[partial_fail], 
										'cancelled' : $status[cancelled], 'complete' : $status[complete], 
										'stored' : $status[stored], 'failed_mat' : $status[failed_mat], 
										'used' : $status[used], 'unused' : $status[unused]"; ?>};


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
			this.parent = this.parent_from_classes(input);
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
		quantity() {
			if(!this.is_time_based) return parseFloat(this.element.value);
			return parseFloat(this.element['hour'].value) + parseFloat(this.element['min'].value) / 60;

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


		// retrieve the parent name of a class (<parent_name>-child); return null if no parent name
		parent_from_classes(input) {
			for(var x = 0; x < input.classList.length; x++)
				if(input.classList[x].includes("-child")) 
					return document.getElementById(input.classList[x].substr(0, input.classList[x].indexOf('-child')));
			return null;
		}
	}


	// {'mu_id' : quantity, etc.} to hold values before status switching causes value to equal 0
	var previous_mats_used_quantities = {};


	// ————–———— QUANTITY CHILD-PARENT CALCULATION ——————————

	// as group total is changed, change individual units used proportionally for new total
	function adjust_children_input(parent_input) {
		var children = children_input_objects(parent_input.id);
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
		if(!mu_input.parent) return;  // ignore ungrouped elements

		var children = children_input_objects(mu_input.parent.id);
		var group_total = 0;
		for(var x = 0; x < children.length; x++) {
			if(!isNaN(children[x].quantity()))
				group_total += children[x].quantity();
			else console.log(children[x]);  // error checking
		}

		mu_input.parent.value = group_total;
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
			if(mats_used[x].status.value != global_status['unused'] && mats_used[x].status.value != global_status['failed_mat'])
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

	// ———— TICKET-MAT_USED RELATIONSHIP ————

	// material status changed: adjust ticket status
	function adjust_ticket_status(status_element) {
		// all materials being used (!failed) means ticket was complete
		if(all_material_status_are(global_status['used'])) 
			document.getElementById("ticket_status").value = global_status['complete'];
		// if no materials were used then nothing is usable and is a total fail
		else if(all_material_status_are(global_status['unused'])) 
			document.getElementById("ticket_status").value = global_status['total_fail'];
	}


	// ticket status has been changed: adjust materials' statuses
	function adjust_materials_status(ticket_status_object) {
		document.getElementById("storage_location").innerHTML = "";  // clear prior storage location
		var ticket_status = ticket_status_object.value;

		// cancelled included b/c user more willing to check off what they didn't use
		if(ticket_status == global_status["stored"] || ticket_status == global_status["complete"] ||
		ticket_status == global_status["cancelled"])
			set_status_for_all_materials_to_used_if_status_quantity_not_null();

		// cancelled tickets are not allowed to have any failed_mat statuses
		if(ticket_status == global_status['cancelled'])
			$(`.mat_used_select option[value='${global_status['failed_mat']}']`).hide();
		else $(`.mat_used_select option[value='${global_status['failed_mat']}']`).show();

		if(ticket_status == global_status["stored"])
			$("#storage_modal").show();
	}


	// ———— INPUT-STATUS RELATIONSHIP ————

	// change value to 0 if not used; reset value if changed back
	function adjust_input_for_status(status_element) {
		var input = input_for_status(status_element);
		// prevent unused materials from having any quantity
		if(parseInt(status_element.value) == global_status['unused']) {
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
		if(mu_input.quantity() && mu_input.status.value == global_status['unused'])
			mu_input.status.selectedIndex = "0";
		// don't allow zero values to have status of used or failed
		else if(!mu_input.quantity())
			mu_input.status.value = global_status['unused'];
	}


	// ———— STATUS UTILITY ————

	// check if all of the materials have the same status as status passed
	function all_material_status_are(status) {
		var materials_statuses = document.getElementsByClassName("mat_used_select");
		for(var x = 0; x < materials_statuses.length; x++) 
			if(parseInt(materials_statuses[x].value) != status) return false;
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
				materials_statuses[x].value = global_status["used"];
	}


	// ————————————— ADD NEW MATERIAL USED —————————————

	/* AJAX: add mat_used to DB for transaction.  If preexisting group, add material to group.
	If preexisting (ungrouped) material, create group.  Otherwise, add material input to end
	of table. */
	function add_new_material_used() {
		var m_id = document.getElementById("new_material").value;
		if(isNaN(parseInt(m_id))) return;
		if(!confirm("Are you sure you would like to add another material to this transaction?"));
		
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
				if(response["error"]) {
					alert(response["error"]);
					return;
				}

				// add material to preexisting parent
				if(document.getElementById(response["parent_id"])) {
					var parent = document.getElementById(response["parent_id"]);
					var new_material_row = parent.closest("table").rows[1].children[1].children[0].insertRow(-1);
					new_material_row.innerHTML = `<td style='background-color:#5CB85C'> ${response["material_HTML"]} </td>`;
				}
				// create new parent group, from single existing element
				else if(document.getElementsByClassName(`${response["parent_id"]}-child`).length) {
					var preexisting_single_element = document.getElementsByClassName(`${response["parent_id"]}-child`)[0];
					var new_material_group = 	`<table width='100%' class='table table-bordered' style='margin-bottom:0px !important;'>
														<tr class='tablerow info'>
															<td colspan='2'>Vinyl (Generic)</td>
														</tr>
														<tr>
															<td>
																<div class='input-group'>
																	<span class='input-group-addon'>MATERIAL-GROUP <i class='fas fa-dollar-sign'></i> 0.25 x </span>
																	<input type='number' id='Vinyl_(Generic)' class='form-control' autocomplete='off' value='6' style='text-align:right;'
																	onkeyup='adjust_children_input(this); adjust_balances();' onchange='adjust_children_input(this); adjust_balances();' >
																	<span class='input-group-addon'>inch(es)</span>
																</div>
															</td>
															<td style='padding:0px;'>
																<table class='table table-striped' style='margin-bottom:0px !important;'>
																	<tr>
																		<td>
																			${preexisting_single_element.closest("table").outerHTML}
																		</td>
																	</tr>
																	<tr>
																		<td>
																			${response["material_HTML"]}
																		</td>
																	</tr>
																</table>
															</td>
														</tr>
													</table>`;
					preexisting_single_element.closest("table").closest("tr").remove();
					var new_mat_row = document.getElementById("material_table").insertRow(-1);
					new_mat_row.innerHTML  = "<td style='background-color:#5CB85C'>"+new_material_group+"</td>";
				}
				// add material to end of table
				else {
					var new_mat_row = document.getElementById("material_table").insertRow(-1);
					new_mat_row.innerHTML  = "<td style='background-color:#5CB85C'>"+response["material_HTML"]+"</td>";
				}

				alert("Successfully added "+response["material_name"]+" to materials");
				$(`#new_material`).val("");
			}
		});
	}


	// ————————————–—— END CONFIRMATION ——————————————
	// ———————————————————————————————————————


	// get information from page and put into confirmation modal
	function populate_end_modal() {
		// ---- ticket ----
		var ticket_status = document.getElementById("ticket_status");
		var ticket_status_name = ticket_status.options[ticket_status.selectedIndex].text;
		if(isNaN(ticket_status.value)) {
			alert("Please select a ticket status");
			return;
		}
		else if(ticket_status.value == global_status["partial_fail"] && document.getElementById("ticket_notes") < 10) {
			alert("You must state how the ticket failed")
		}
		document.getElementById("ticket_status_confirmation").innerHTML = 
			confirmation_cell_format('ticket_status', `<h5>${ticket_status_name}</h5>`, ticket_status.value);
		
		// ---- materials ----
		var materials = get_and_sort_materials();
		// mats_used listed but none accounted for: error in get_and_sort_materials()
		if(!materials && document.getElementsByClassName("mat_used_select").length) return;

		// no material is marked as failed && not all are unused: a failed ticket requires a fail material or all to be null
		if((ticket_status.value == global_status['partial_fail'] || ticket_status.value == global_status['total_fail'])
		&& (!any(materials, function(part, value) {return part['status'].value == value;}, global_status['failed_mat']) 
		  && any(materials, function(part, value) {return part['status'].value != value;}, global_status['unused']))) {
			alert(	"Ticket is marked as failed, but no failed material is indicated.\n"+
					"Please indicate which material was failed on usage.\n"+
					"If no materials were used, please mark all materials as unused.");
			return;
		}

		populate_material_table(materials);

		// ---- storage ----
		var storage_confirmation = document.getElementById("storage_confirmation_location");
		storage_confirmation.innerHTML = "";  // clear prior storage location
		if(document.getElementById("storage_location").children.length) {
			$("#storage_confirmation").show();
			var box_id = document.getElementById("storage_location").children[0].innerHTML;
			var span = 	"<span style='background-color:#0055FF;border:4px solid #0055FF;"+
							"border-radius:4px;padding:8px;margin:auto;color:#FFFFFF;'>"+
							"Currently stored in "+box_id+"</span>";
			storage_confirmation.innerHTML = confirmation_cell_format("storage", span, box_id.replace("-", ""));
		}

		if(document.getElementById("ticket_notes").value)
			document.getElementById("notes_confirmation").innerHTML = `<b>NOTES: </b>
			${document.getElementById("ticket_notes").value}`;
		else document.getElementById("notes_confirmation").innerHTML = "";

		$("#confirmation_modal").show();
	}


	//  ———— MATERIALS ————
		// — DATA COLLECTION —
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


	// 
	function dictionary_of_measurable_material(material) {
		var cost = material.quantity() * material.price;
		var name = material_name(material.status);

		// --error/logic checking
		if(isNaN(parseFloat(material.status.value))) {
			 alert("Please select a status for "+name);
			 return null;
		}
		else if(material.status.value == global_status['used'] && !material.quantity()) { 
			alert("Material status cannot be used with a 0 quantity for "+name);
			return null;
		}

		return {'mu_id' : material.mu_id, 'name' : name, 'cost' : cost, 'quantity' : material.quantity(), 'status' : material.status};
	}


		// — MODAL BUILDING —
	// using the material dictionary, add values to material table in modal
	function populate_material_table(materials) {
		$("#material_confirmation_table tr").remove();  // clear previous entries
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
		var total_title = total_row.insertCell(0);
		total_title.innerHTML = "<h5>Total</h5>";
		total_title.colSpan = "3";
		var total_value = total_row.insertCell(1);
		total_value.innerHTML = `<h5><i class='<?php echo $sv["currency"]; ?>'></i>${document.getElementById("total").innerHTML}</h5>`;

		$("#material_confirmation_table tr td:not(:first-child)").attr("align", "center");
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


	// create innerHTML for a cell using text and a hidden input
	function confirmation_cell_format(name, text, value) {
		return `${text}<input name='${name}' value='${value}' hidden/>`;
	}


	// retrieve the name of a material based on a given input
	// ascend up: status -> td -> tr -> table; down: tr[0] -> td [0] -> text; remove newline
	function material_name(status_element) {
		var ancestor = status_element.parentElement.parentElement.parentElement
		return ancestor.children[0].children[0].textContent.trim();
	}


	// ——————————————— OBJECT STORAGE ———————————————
	// ———————————————————————————————————————

	// call to modal to get first available box with type; create drawer layout & insert into modal
	function get_box_for_type(unit_type) {
		$.ajax({
			url: "./sub/storage_ajax_requests.php",
			type: "POST",
			dataType: "json",
			data: {"drawer_for_type" : true, "unit_type" : unit_type.value},
			success: function(response) {
				if(response["error"]) {
					alert(response["error"]);
					return;
				}

				// add stuff to page
				var message =	`Please place the object into drawer ${response["drawer_label"]} box ${response["unit_label"]}, `+ 
									`then confirm its placement by clicking the highlighted box on the screen`;
				var drawer_label = 	`<h3>Drawer ${response["drawer_label"]} </h3>`;
				document.getElementById("drawer_fill").innerHTML = message + drawer_label + response["drawer_HTML"];
			}
		});
	}


	// called by box click on confirmation of object placement
	function storage_selected(element, box_id) {
		element.style.backgroundColor = "#0055FF";
		element.style.color = "#FFFFFF";
		var cell = document.getElementById("storage_location");
		cell.innerHTML = "<span style='background-color:#0055FF;border:4px solid #0055FF;border-radius:4px;padding:8px;margin:auto;color:#FFFFFF;'>"+box_id+"</span>";
		cell.hidden = false;
		setTimeout(function() {
			$('#storage_modal').fadeOut('fast');
		}, 500);
	}


	// —————————————————— UTILITY —————————————————
	// ———————————————————————————————————————

	// if any of the items is relevant to the /usage function (eg contains /value), return true
	function any(list, usage, value) {
		for(var x = 0; x < list.length; x++)
			if(usage(list[x], value)) return true;
		return false;
	}


	function children_input_objects(parent_id) {
		var children = [];
		var elements = document.getElementsByClassName(parent_id+"-child");
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