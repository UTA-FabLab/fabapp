<?php

/***********************************************************************************************************
*	
*	@author MPZinke
*	created on 08.07.19 
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.94
*		-House Keeping (DB cleanup, $status variable, class syntax/functionality)
*		-Multiple Materials
*		-Off-line Mode
*		-Sheet Goods
*		-Storage Box
*
*	DESCRIPTION: edit transaction, storage, account, material information passed by $_GET.  
*	 Populate fields on page load and retrieve & write information on "Save".  Add to storage
*	 through AJAX call to ./sub/storage_ajax_requests.php.  The ID number of the person 
*	 who edits a field (eg Material) is placed in the staff input, as they assume responsibility.
*	FUTURE:	-Use JS to highlight which inputs are incorrectly filled
*	BUGS:
*
***********************************************************************************************************/

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

if(!$staff) exit_if_error("Please Login", "/index.php");
elseif($staff->roleID < $sv['editTrans']) exit_if_error("You are not authorized to edit this ticket", "/index.php");
elseif(!$_GET["trans_id"]) exit_if_error("Search parameter is missing", "/index.php");
elseif(!Transactions::regexTrans($_GET["trans_id"])) exit_if_error("Ticket #$_GET[trans_id] is invalid", "/index.php");
else {
	$trans_id = $_GET["trans_id"];
	$ticket = new Transactions($trans_id);
	$storage = StorageObject::object_is_in_storage($trans_id) ? new StorageObject($trans_id) : null;
	$account_ids = array_map(create_function('$obj', 'return $obj->a_id;'), Accounts::listAccts($ticket->user, $staff));
}

if($staff->operator == $ticket->user->operator && $staff->roleID < $role["admin"])
	exit_if_error("You do not have permission to edit your own ticket", "/index.php");



if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_data"])) {
	// materials
	foreach($ticket->mats_used as $mat_used) {
		$mu_id = $mat_used->mu_id;

		$material = filter_input(INPUT_POST, "$mu_id-material");
		if(!Materials::regexID($material)) exit_if_error("Material ID #$material is invalid");

		if($mat_used->material->unit == "hour(s)")
			$quantity_used = Mats_Used::regexQuantity(filter_input(INPUT_POST, "$mu_id-hour")) 
			+ Mats_Used::regexQuantity(filter_input(INPUT_POST, "$mu_id-minute")) / 60;
		else $quantity_used = Mats_Used::regexQuantity(filter_input(INPUT_POST, "$mu_id-quantity"));

		$mat_status = Mats_Used::regexStatus(filter_input(INPUT_POST, "$mu_id-select"));
		if(!$mat_status) exit_if_error("Invalid material status for mat_used #$mu_id");
		$mat_status = new Status($mat_status);

		$material_edit_staff = new Users::withID(filter_input(INPUT_POST, "$mu_id-staff"));
		if($error = $mat_used->edit_material_used_information(array("quantity_used" => $quantity_used, 
		"m_id" => $material, "status" => $mat_status, "staff" => $material_edit_staff)))
			exit_if_error("Unable to update material used #$mat_used->mu_id: $error");
	}

	// ticket
	$ticket_status = filter_input(INPUT_POST, "status_id");
	$notes = htmlspecialchars(filter_input(INPUT_POST, "ticket_notes"));
	// input formats should regex, but prevent SQL injection just in case
	$start_time = htmlspecialchars(filter_input(INPUT_POST, "t_start"));
	$end_time = htmlspecialchars(filter_input(INPUT_POST, "t_end"));
	// self regexing
	$operator = Users::withID(filter_input(INPUT_POST, "operator"));
	$staff = Users::withID(filter_input(INPUT_POST, "staff_id"));

	if(!Status::regexID($ticket_status)) exit_if_error("Status ID $ticket_status is an invalid format");
	
	// not problems with data; reassign as appropriate data types
	$ticket_status = new Status($ticket_status);

	exit_if_error($error = $ticket->edit_transaction_information(array(
			"t_start" => $start_time, "t_end" => $end_time, "status" => $ticket_status,
			"notes" => $notes, "operator" => $operator, "staff" => $staff)));

	if($ticket->pickup_time) {
		$pickup_time = htmlspecialchars(filter_input(INPUT_POST, "pickup_time"));
		$receiver = Users::withID(filter_input(INPUT_POST, "receiver"));

		exit_if_error($ticket->edit_transaction_information(array(	"pickup_time" => $pickup_time, 
																	"pickedup_by" => $receiver)));
	}


	// account_charge
	if($ticket->acct_charge) {
		foreach ($ticket->acct_charge as $ac) {
			if(in_array($ac->account->a_id, $account_ids)) {
				$ac_id = $ac->ac_id;
				exit_if_error($ac->edit(filter_input(INPUT_POST, "ac_operator_$ac_id"),
						filter_input(INPUT_POST, "ac_amount_$ac_id"), filter_input(INPUT_POST, "ac_date_$ac_id"),
						filter_input(INPUT_POST, "ac_acct_$ac_id"), filter_input(INPUT_POST, "ac_staff_$ac_id"),
						filter_input(INPUT_POST, "ac_notes_$ac_id")));
			}
		}
	}


	// storage
	if(StorageObject::object_is_in_storage($trans_id)) {
		if(filter_input(INPUT_POST, "remove_from_storage"))
			exit_if_error(StorageObject::remove_object_from_storage($staff, $ticket->trans_id));
		else {
			$storage_object = new StorageObject($trans_id);

			$stored_time = htmlspecialchars(filter_input(INPUT_POST, "storage_start"));
			$storage_staff = Users::withID(filter_input(INPUT_POST, "storage_staff"));

			exit_if_error($storage_object->edit_object_storage_information(array(	"storage_start" => $stored_time, 
																						"staff" => $storage_staff)));
		}
	}

	exit_with_success("Ticket #$trans_id successfully updated");

}



function material_not_already_used($material, $used_materials) {
	foreach ($used_materials as $mat_used)
		if($mat_used->material->m_id == $material->m_id) return false;
	return true;
}


// if an error message passes, add error to session, redirect (default: home)
function exit_if_error($error, $redirect=null) {
	global $trans_id;

	if($error) {
		$_SESSION['error_msg'] = "Edit.php: ".$error;
		if($redirect) header("Location:$redirect");
		else header("Location:./edit.php?trans_id=$trans_id");
		exit();
	}
}


function exit_with_success($message, $redirect=null) {
	global $trans_id;

	$_SESSION["success_msg"] = $message;
	if($redirect) header("Location:$redirect");
	else header("Location:./lookup.php?trans_id=$trans_id");
	exit();
}
?>

<title><?php echo $sv['site_name']; ?> Edit Detail</title>
<div id="page-wrapper">
	<form name="saveForm" method="post" action="" onsubmit="return validateForm()" autocomplete='off'>
		<div class="row">
			<div class="col-lg-12">
				<h1 class="page-header">Edit Details <input class="btn btn-info" type="submit" name="save_data" value="Save"/></h1>
			</div> <!-- /.col-lg-12 -->
		</div> <!-- /.row -->
		<div class="row">
			<div class="col-lg-6">
				<div class="panel panel-warning">
					<div class="panel-heading">
						<i class="fas fa-ticket-alt fa-lg"></i> Ticket # <b><?php echo $ticket->getTrans_id(); ?></b>
					</div>
					<div class="panel-body">
						<?php 
							$readonly = $staff->operator == $ticket->staff->operator ? "readonly" : "";  // prevent staff from listing someone else as changer
						?>
						<table class ="table table-bordered table-striped">
							<tr>
								<td>Device</td>
								<td>
									<?php echo $ticket->device->name; ?>
								</td>
							</tr>
							<?php if($ticket->filename) { ?>
								<tr>
									<td>File Name</td>
									<td>
										<div style="word-wrap: break-word;"><?php echo $ticket->filename; ?></div>
									</td>
								</tr>
							<?php } ?>
							<tr>
								<td>Start Time</td>
								<td>
									<input id='t_start' name='t_start' class='form-control' value='<?php echo date("Y-m-d\TH:i:s", strtotime($ticket->t_start)); ?>' 
									type='datetime-local' onkeyup='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'
									onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'/>
								</td>
							</tr>
							<tr>
								<td>End Time</td>
								<td>
									<input id='t_end' name='t_end' class='form-control' value='<?php echo date("Y-m-d\TH:i:s", strtotime($ticket->t_end)); ?>' 
									type='datetime-local' onkeyup='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'
									onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'/>
								</td>
							</tr>
							<tr>
								<td>Status</td>
								<td>
									<select name="status_id" id="status_id" class='form-control' onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'>
										<?php
										$available_statuses = Status::device_and_transaction_statuses();
										$status_descriptions = Status::getList();
										foreach($available_statuses as $available_status) {
											$selected = $ticket->status->status_id == $available_status ? "selected" : "";
											echo ("<option value='$available_status' $selected>$status_descriptions[$available_status]</option>");
										} ?>
									</select>
								</td>
							</tr>
							<?php if($ticket->pickup_time) { ?>
							<tr>
								<td>Picked Up Time</td>
								<td>
									<input id='pickup_time' name='pickup_time' class='form-control' value='<?php echo date("Y-m-d\TH:i:s", strtotime($ticket->pickup_time)); ?>' 
									type='datetime-local' onkeyup='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'
									onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'/>
								</td>
							</tr>
							<tr>
								<td>Picked Up By</td>
								<td>
									<input type='number' name='receiver' id='receiver' placeholder='1000000000' value='<?php echo $ticket->pickedup_by->operator; ?>'
									class='form-control' onkeyup='restrict_size(this); change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'
									onchange='restrict_size(this); change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'>
								</td>
							</tr>
							<?php } ?>
							<tr>
								<td>Started By</td>
								<td>
									<input type="number" name="operator" id="operator" placeholder="1000000000" value="<?php echo $ticket->user->operator; ?>"
									class='form-control' onkeyup='restrict_size(this); change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'
									onchange='restrict_size(this); change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'>
								</td>
							</tr>
							<tr>
								<td>Staff</td>
								<td>
									<input type="number" name="staff_id" id="staff_id" placeholder="1000000000" value="<?php echo $ticket->staff ? $ticket->staff->operator : ""; ?>"
									onkeyup='restrict_size(this);' onchange='restrict_size(this);' class='form-control' <?php echo $readonly; ?>>
								</td>
							</tr>
							<tr>
								<td>
									<i class="fas fa-edit"></i>Notes
										<div class="btn-group">
											<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
												<i class="fas fa-info"></i>
											</button>
											<ul class="dropdown-menu" role="menu">
												<li style="padding-left: 5px;">Please state why this ticket needed to be edited.</li>
											</ul>
										</div>
								</td>
								<td>
									<textarea name='ticket_notes' id='ticket_notes' class='form-control' 
									onkeyup='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'
									onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'><?php echo $ticket->notes; ?></textarea>
								</td>
							</tr>
						</table>
					</div> <!-- /.panel-body -->
				</div> <!-- /.panel -->
				<div class="panel panel-warning">
					<div class="panel-heading">
						<i class="far fa-life-ring fa-lg"></i> Material
					</div>
					<div id='mats_used_display'>
						<?php foreach ($ticket->mats_used as $mat_used) {
							$readonly = $staff->operator == $mat_used->staff->operator ? "readonly" : "";  // prevent staff from listing someone else as changer
							$mu_id = $mat_used->mu_id; ?>
							<div class="panel-body">
								<table id='mu-<?php echo $mu_id; ?>' class="table table-bordered table-striped mats_used" style="table-layout:fixed">
									<tr>
										<td class="col-md-4">Material</td>
										<td class="col-md-8">
											<select name="<?php echo $mu_id; ?>-material" id="<?php echo $mu_id; ?>-material" class='form-control' 
											onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 1);'>
												<?php
												//List all Materials that are available to that device
												$device_materials = Materials::getDeviceMats($ticket->device->device_group->dg_id);
												foreach($device_materials as $material) {
													$selected = $material->m_id == $mat_used->material->m_id ? "selected" : "";
													echo "<option $selected value='$material->m_id'>$material->m_name</option>";
												} ?>
											</select>
										</td>
									</tr>
									<?php if($mat_used->material->is_measurable) { ?>
										<tr>
											<td>
												Quantity Used
											</td>
											<td>
												<div class="input-group">
													<?php if($mat_used->material->unit == "hour(s)") { 
														$hour = floor($mat_used->quantity_used);
														$minute = ($mat_used->quantity_used - $hour) * 60; ?>
														<span class='input-group-addon'><?php printf("<i class='$sv[currency]'></i> %.2f x ", $mat_used->material->price); ?></span>
														<!-- hours is not set to a minimum because it is assumed staff knows what they are doing -->
														<input class='form-control' type='number' name='<?php echo $mu_id; ?>-hour' id='<?php echo $mu_id; ?>-hour' autocomplete='off' tabindex='2'
														value='<?php echo $hour; ?>' min='0' max='9999' step='1' style='text-align:right;' onkeyup='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 4, 1);'
														onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 4, 1);'/>
														<span class='input-group-addon'>Hours</span>
														<input class='form-control' type='number' name='<?php echo $mu_id; ?>-minute' id='<?php echo $mu_id; ?>-minute' autocomplete='off' tabindex='2'
														value='<?php echo $minute; ?>' min='0' max='9999' style='text-align:right;' onkeyup='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 4, 1);'
														onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 4, 1);'/>
														<span class="input-group-addon">Minutes</span>
													<?php
													}
													else { 
													?>
														<span class='input-group-addon'><?php printf("<i class='$sv[currency]'></i> %.2f x ", $mat_used->material->price); ?></span>
														<input class='form-control' type='number' name='<?php echo $mu_id; ?>-quantity' id='<?php echo $mu_id; ?>-quantity' autocomplete='off'
														value='<?php echo $mat_used->quantity_used; ?>' min='0' max='9999' onkeyup='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 4, 1);'
														style='text-align:right;' onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 4, 1);'/>
														<span class='input-group-addon'><?php echo $mat_used->material->unit; ?></span>
													<?php } ?>
												</div>
											</td>
										</tr>
									<?php } ?>
									<tr>
										<td>Material Status</td>
										<td>
											<select name="<?php echo $mu_id; ?>-select" id="<?php echo $mu_id; ?>-select" class='form-control' 
											onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 1);'>
												<option <?php echo "value='$status[used]'".($mat_used->status->status_id == $status["used"] ? "selected" : ""); ?> >Used</option>
												<option <?php echo "value='$status[unused]'".($mat_used->status->status_id == $status["unused"] ? "selected" : ""); ?> >Unused</option>
												<option <?php echo "value='$status[failed_material]'".($mat_used->status->status_id == $status["failed_material"] ? "selected" : ""); ?> >Failed Material</option>
											</select>
										</td>
									</tr>
									<tr>
										<td>Staff</td>
										<td>
											<input type="number" name="<?php echo $mu_id; ?>-staff" id="<?php echo $mu_id; ?>-staff" placeholder="1000000000" 
											value="<?php if($mat_used->staff) echo $mat_used->staff->operator; ?>" onkeyup='restrict_size(this);' 
											onchange='restrict_size(this);' class='form-control' <?php echo $readonly; ?>>
										</td>
									</tr>
								</table>
							</div> <!-- /.panel-body -->
						<?php } ?>
					</div> <!-- End of mats_used_display -->	
					<div class="panel-body">
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
					</div>
				</div> <!-- /.panel -->
			</div> <!-- /.col-lg-6 -->

	<!-- STORAGE -->

			<div class="col-lg-6">
				<div id='storage_panel' class="panel panel-warning">
					<div class="panel-heading">
						<i class="fas fa-gift fa-lg"></i> Storage
					</div>
					<div class="panel-body">
						<table id='storage_container'  class="table table-bordered table-striped">
							<?php 
							if(StorageObject::object_is_in_storage($ticket->trans_id)) { 
								$storage_object = new StorageObject($ticket->trans_id); 
								$readonly = $staff->operator == $storage_object->staff->operator ? "readonly" : "";  // prevent staff from listing someone else as changer
								?>
								<tr>
									<td>Address</td>
									<td>
										<table width='100%'>
											<tr>
												<td width='40%'>
													<?php echo $storage_object->box_id; ?>
												</td>
												<td width='50%' align='right'>
													Remove From Storage
												</td>
												<td width='10%' style='padding-left:4px;'>
											 		<input type='checkbox' name='remove_from_storage' />
											 	</td>
											</tr>
										</table>
									 </td>
								</tr>
								<tr>
									<td>Placed Into Storage</td>
									<td>
										<input id='storage_start' name='storage_start' class='form-control' value='<?php echo date("Y-m-d\TH:i:s", strtotime($storage_object->storage_start)); ?>' 
										type='datetime-local' onkeyup='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 1);'
										onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 1);'/>
									</td>
								</tr>
								<tr>
									<td>Staff</td>
									<td>
										<input type='number' name="storage_staff" placeholder="1000000000" value="<?php echo $storage_object->staff->operator; ?>"
										onchange='restrict_size(this);' onkeyup='restrict_size(this);' class='form-control' <?php echo $readonly; ?>>
									</td>
								</tr>
							<?php 
							}
							else { ?>
								<tr>
									<td align='center' width='100%'>
										<button type='button' class='btn btn-success' width='100%' onclick='$("#storage_modal").show("modal");'>Put Into Storage</button>
									</td>
								</tr>
							<?php } ?>
						</table>
					</div> <!-- /.panel-body -->
				</div> <!-- /.panel -->

		<!-- ACCOUNT CHARGES -->

				<?php
				//Look for associated charges
				if($ticket->acct_charge) {?>
					<div class="panel panel-warning">
						<div class="panel-heading">
							<i class="fas fa-credit-card fa-lg"></i> Related Charges
						</div>
						<div class="panel-body">
						<?php
						//Show Each Account charge
						foreach($ticket->acct_charge as $account_charge) { 
							$ac_id = $account_charge->ac_id;
							?>
							<table id='ac-<?php echo $ac_id; ?>' class="table table-bordered account_charge">
								<?php
									//Show editing fields if they have access to the Account of the Charge
									if(in_array($account_charge->account->a_id, $account_ids)) {
										$readonly = $staff->operator == $account_charge->staff->operator ? "readonly" : "";  // prevent staff from listing someone else as changer
										if(is_object($account_charge->user)) { ?>
											<tr>
												<td colspan="2" align="center" class="active"><b>Account Charge # <?php echo $ac_id; ?></b></td>
											</tr>
											<tr>
												<td>Paid By</td>
												<td>
													<input type="number" name="ac_operator_<?php echo $ac_id; ?>" id="ac_operator-<?php echo $ac_id; ?>" placeholder="1000000000" 
													value="<?php echo $account_charge->user->operator; ?>" class="form-control" 
													onkeyup='restrict_size(this); change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);' 
													onchange='restrict_size(this); change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'/>
												</td>
											</tr>
										<?php 
										}
										else { ?>
											<tr>
												<td>Paid By</td>
												<td>
													<input type="number" name="ac_operator_<?php echo $ac_id; ?>" id="ac_operator-<?php echo $ac_id; ?>" 
													placeholder="1000000000" attern="[0-9]{10}" class="form-control" 
													onkeyup='restrict_size(this); change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'
													onchange='restrict_size(this); change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'>
												</td>
											</tr>
										<?php } ?>
											<tr>
												<td>Amount</td>
												<td>
													<div class="form-group">
														<div class="input-group">
															<span class="input-group-addon"><?php echo "<i class='$sv[currency]'></i>" ; ?></span>
															<input type="number" id='ac_amount-<?php echo $ac_id; ?>' name="ac_amount_<?php echo $ac_id; ?>" 
															value="<?php echo $account_charge->amount; ?>" step="0.01" class="form-control"
															onkeyup='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'
															onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'/>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td>Date</td>
												<td>
													<input id='ac_date_<?php echo $ac_id; ?>' name='ac_date_<?php echo $ac_id; ?>' class='form-control' 
													value='<?php echo date("Y-m-d\TH:i:s", strtotime($storage_object->storage_start)); ?>' type='datetime-local' 
													onkeyup='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'
													onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'/>
												</td>
											</tr>
											<tr>
												<td>Account</td>
												<td>
													<select name="ac_acct_<?php echo $ac_id; ?>" id="ac_acct-<?php echo $ac_id; ?>" class="form-control" 
													onchange='change_edit_staff(this, "<?php echo "$staff->operator"; ?>", 3, 2);'><?php
														foreach(Accounts::listAccts($ticket->user, $staff) as $acct) {
															$selected = ($acct->a_id == $account_charge->account->a_id) ? "selected" : "";
															echo("<option value='$acct->a_id' title='$acct->getDescription()'' $selected>$acct->name</option>");
														}
														?>
													</select>
												</td>
											</tr>
											<tr>
												<td>Staff</td>
												<td>
													<input type="number" name="ac_staff_<?php echo $ac_id; ?>" id="ac_staff-<?php echo $ac_id; ?>" placeholder="1000000000" 
													value="<?php echo $account_charge->staff->operator; ?>" onchange='restrict_size(this);' class="form-control" <?php echo $readonly ?>/>
												</td>
											</tr>
											<tr>
												<td>Notes</td>
												<td>
													<textarea name="ac_notes_<?php echo $ac_id; ?>" id="ac_notes-<?php echo $ac_id; ?>" class="form-control" tabindex="2"><?php echo $account_charge->ac_notes; ?></textarea>
												</td>
											</tr>
									<?php // locked View of Acct Charge, staff does not have access to this account
									}
									else {
										if(is_object($account_charge->user)) { ?>
											<tr>
												<td colspan="2" align="center" class="active"><b>Account Charge # <?php echo $ac_id; ?></b></td>
											</tr>
											<tr>
												<td>Paid By</td>
												<td>
													<div class="btn-group">
														<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
															<i class="<?php echo $account_charge->user->icon; ?> fa-lg" title="<?php echo $account_charge->user->operator; ?>"></i>
														</button>
														<ul class="dropdown-menu" role="menu">
															<li style="padding-left: 5px;"><?php echo $account_charge->user->operator; ?></li>
														</ul>
													</div>
												</td>
											</tr>
										<?php } ?>
										<tr>
											<td>Amount</td>
											<td><?php echo "<i class='$sv[currency]'></i> ".number_format($account_charge->amount, 2); ?></td>
										</tr>
										<tr>
											<td>Account</td>
											<td>
												<div class="btn-group">
													<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
														<i class='far fa-calendar-alt' title="<?php echo $account_charge->ac_date; ?>"></i>
													</button>
													<ul class="dropdown-menu" role="menu">
														<li style="padding-left: 5px;"><?php echo $account_charge->ac_date; ?></li>
													</ul>
												</div>
												<?php echo $account_charge->account->name; ?>
											</td>
										</tr>
										<tr>
											<td>Staff</td>
											<td>
												<div class="btn-group">
													<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
														<i class="<?php echo $account_charge->staff->icon; ?> fa-lg" title="<?php echo $account_charge->staff->operator; ?>"></i>
													</button>
													<ul class="dropdown-menu" role="menu">
														<li style="padding-left: 5px;"><?php echo $account_charge->staff->operator; ?></li>
													</ul>
												</div>
											</td>
										</tr>
										<?php if($account_charge->ac_notes) { ?>
											<tr>
												<td>Notes</td>
												<td>
													<?php echo $account_charge->ac_notes; ?>
												</td>
											</tr>
										<?php 
										}
									} ?>
								</table>
							<?php } ?>
						</div> <!-- /.panel-body -->
					</div> <!-- /.panel -->
				<?php } ?>
			</div> <!-- /.col-lg-5 -->
		</div> <!-- /.row -->
	</form>
</div> <!-- /#page-wrapper -->


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


<?php // standard call for dependencies
	include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script>

	// set currently logged in user as staff for MU && don't let them change it back
	function change_edit_staff(element, ID, ancestry, ordinal_youngest_child) {
		var ancestor = element;
		for(var x = 0; x < ancestry; x++)
			ancestor = ancestor.parentElement;
		var staff_input = ancestor.children[ancestor.children.length-(ordinal_youngest_child)].children[1].children[0];
		staff_input.value = ID;
		staff_input.readOnly = true;
	}


	// prevent string from being longer than 10 chars for ID
	function restrict_size(element) {
		if(element.value.length > 10) element.value = element.value.substring(0, 10);
	}


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
			data: {	"add_new_material" : true, "request_from_edit_page" : true, 
					"m_id" : m_id, "trans_id" : <?php echo $ticket->trans_id; ?>
			},
			success: function(response) {
				if(response["error"]) {
					alert(response["error"]);
					return;
				}

				// add stuff to page
				var panel = document.getElementById("mats_used_display");
				panel.innerHTML += `	<div class='panel-body'>
											${response["material_HTML"]}
										</div>`;
				alert("Successfully added "+response["material_name"]+" to materials");
				$(`#new_material option[value=${m_id}]`).hide();
				$(`#new_material`).val("");
			}
		});
	}


	// ————————————————— STORAGE ————————————————— 

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
					document.getElementById("storage_container").innerHTML = 	
						`<tr> 
							<td>Address</td>
							<td>
								<table width='100%'>
									<tr>
										<td width='40%'>
											${drawer}${unit.innerHTML}
										</td>
										<td width='50%' align='right'>
											Remove From Storage
										</td>
										<td width='10%' style='padding-left:4px;'>
									 		<input type='checkbox' name='remove_from_storage' />
									 	</td>
									</tr>
								</table>
							 </td>
						</tr>
						<tr>
							<td>Placed Into Storage</td>
							<td>
								<input name='storage_start' value='${current_time()}' type='datetime-local' class='form-control' readonly/> 
							</td>
						</tr>
						<tr>
							<td>Staff</td>
							<td>
								<input name='storage_staff' value='<?php echo $staff->operator; ?>' class='form-control' readonly/>
							</td>
						</tr>`;
					document.getElementById("storage_modal").style.display = "none";
					alert(`Successfully moved object to location ${drawer}${unit.innerHTML}`);
				}
			}
		});
	}


	function current_time() {
		var now = new Date($.now())

		var year = now.getFullYear();
		var month = now.getMonth().toString().length === 1 ? '0' + (now.getMonth() + 1).toString() : now.getMonth() + 1;
		var date = now.getDate().toString().length === 1 ? '0' + (now.getDate()).toString() : now.getDate();
		var hours = now.getHours().toString().length === 1 ? '0' + now.getHours().toString() : now.getHours();
		var minutes = now.getMinutes().toString().length === 1 ? '0' + now.getMinutes().toString() : now.getMinutes();
		var seconds = now.getSeconds().toString().length === 1 ? '0' + now.getSeconds().toString() : now.getSeconds();

		return year + '-' + month + '-' + date + 'T' + hours + ':' + minutes + ':' + seconds;
	}


	// ————————————————— VALIDATE ————————————————— 

	// main function for making sure values are filled
	function validateForm() {
		if(!validate_mats_used()) return false;
		else if(!validate_account_charges()) return false;

		return true;
	}


	function validate_account_charges() {
		var account_charges = document.getElementsByClassName("account_charge");

		for(var x = 0; x < account_charges.length; x++) {
			var ac_id = account_charges[x].id.split("-")[1];
			if(!document.getElementById("ac_operator-"+ac_id).value
			|| !document.getElementById("ac_amount"+ac_id).value
			|| !document.getElementById("ac_acct").value) {
				alert("Account charge is missing a value");
				return null;
			}
			if(stdRegEx(	document.getElementById("ac_operator-"+ac_id).value, 
							/<?php echo $sv['regexUser']; ?>/, "Invalid Operator ID # "+ x)) {
				alert("The operator used for is invalid");
				return false;
			}
		}
		return true;
	}


	function validate_mats_used() {
		var mats_used = document.getElementsByClassName("mats_used");

		for(var x = 0; x < mats_used.length; x++) {
			var mu_id = mats_used[x].id.split("-")[1];

			if(!document.getElementById("mu_mat-"+mu_id).value || 
			!document.getElementById("mu_status-"+mu_id).value ||
			!document.getElementById("mu_staff-"+mu_id).value) {
				alert("Not all values for materials are filled");
				return false;
			}

			// quantity
			if(document.getElementById("mu_quantity-"+mu_id) && 
			!parseFloat(document.getElementById("mu_quantity-"+mu_id).value) &&
			document.getElementById("mu_status-"+mu_id).value != <?php echo $status["unused"]; ?>) {
				alert("A ticket cannot have a non zero value with a status other than unused");
				return false;
			}
			else if(document.getElementById("mu_hour-"+mu_id))
				if(!parseFloat(document.getElementById("mu_hour-"+mu_id).value) 
				&& (!parseFloat(document.getElementById("mu_minute-"+mu_id).value)
				&& document.getElementById("mu_status-"+mu_id).value != <?php echo $status["unused"]; ?>)) {
					alert("A ticket cannot have a non zero value with a status other than unused");
					return false;
				}
		}
		return true;
	}


	function selectDevice(element) {
		if(window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp = new XMLHttpRequest();
		} else {
			// code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange = function() {
			if(this.readyState == 4 && this.status == 200) {
				<?php //Target all MU's
				foreach($ticket->mats_used as $mu) { ?>
				document.getElementById("mu_mat_<?php echo $mu->mu_id; ?>").innerHTML = this.responseText;
				<?php } ?>
			}
		};
		device = "d_id=" + element.value;
		xmlhttp.open("GET","sub/edit_mu.php?" + device,true);
		xmlhttp.send();
	}
</script>