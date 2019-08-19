<?php

/***********************************************************************************************************
*	
*	@author Jon Le
*	created on 08.14.19 
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.94
*		-House Keeping (DB cleanup, $status variable, class syntax)
*		-Multiple Materials
*		-Off-line Mode
*		-Sheet Goods
*		-Storage Box
*
*	DESCRIPTION: AJAX POST call page to execute asyncronous JSON responses.  Used
*	 by edit.php.  Page no longer allows the changing of a device for a ticket.  Instead add
*	 ability to add a material
*	FUTURE: 
*	BUGS: 
*
***********************************************************************************************************/


include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

// authenticate permission for user
session_start();
$staff = unserialize($_SESSION['staff']);
if(!$staff || $staff->roleID < $role["lead"]) exit();


if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_new_material"])) {
	$m_id = $_POST["m_id"];
	$trans_id = $_POST["trans_id"];

	if(!Materials::regexID($m_id))
		echo json_encode(array("error" => "Material #$m_id is invalid"));
	elseif(!Transactions::regexTrans($trans_id))
		echo json_encode(array("error" => "Ticket #$trans_id is invalid"));

	$ticket = new Transactions($trans_id);
	$mu_id = insert_material_used($trans_id, $m_id, $status["used"], $staff);

	if(!is_int($mu_id)) 
		echo json_encode(array("error" => "Error associating material with ticket—$mu_id"));

	// generate material/transaction depending HTML parts
	$device_materials = Materials::getDeviceMats($ticket->device->device_group->dg_id);
	$material_select_options = array();
	foreach($device_materials as $device_mat) {
		$selected = $device_mat->m_id == $m_id ? "selected" : "";
		$material_select_options[] = "<option $selected value='$device_mat->m_id'>$device_mat->m_name</option>";
	}
	$material_select_options = implode("\n", $material_select_options);




	$mat_used_HTML =	"<div class='panel-body'>
									<table id='mu-<?php echo $mu_id; ?>' class='table table-bordered table-striped mats_used' style='table-layout:fixed'>
										<tr>
											<td class='col-md-4'>Material</td>
											<td class='col-md-8'>
												<select name='mu_mat_$mu_id' id='mu_mat-$mu_id' class='form-control' 
													onchange='change_mu_staff(this, \"$staff->operator\", 3);'>
													$material_select_options
												</select>
											</td>
										</tr>
										<tr>".
											if($mat_used->material->is_measurable)
												"<td>
													Quantity Used
												</td>
												<td>
													<div class="input-group">
														<?php if($mat_used->material->unit == "hour(s)") { 
															$hour = floor($mat_used->quantity_used);
															$minute = ($mat_used->quantity_used - $hour) * 60; ?>
															<span class='input-group-addon'><?php printf("<i class='$sv[currency]'></i> %.2f x ", $mat_used->material->price); ?></span>
															<!-- hours is not set to a minimum because it is assumed staff knows what they are doing -->
															<input class='form-control' type='number' name='mu_hour_<?php echo $mu_id; ?>' id='mu_hour-<?php echo $mu_id; ?>' autocomplete='off' tabindex='2'
															value='<?php echo $hour; ?>' min='0' max='9999' step='1' style='text-align:right;' onkeyup='change_mu_staff(this, "<?php echo "$staff->operator"; ?>", 4);'
															onchange='change_mu_staff(this, "<?php echo "$staff->operator"; ?>", 4);'/>
															<span class='input-group-addon'>Hours</span>
															<input class='form-control' type='number' name='mu_minute_<?php echo $mu_id; ?>' id='mu_minute-<?php echo $mu_id; ?>' autocomplete='off' tabindex='2'
															value='<?php echo $minute; ?>' min='0' max='9999' style='text-align:right;' onkeyup='change_mu_staff(this, "<?php echo "$staff->operator"; ?>", 4);'
															onchange='change_mu_staff(this, "<?php echo "$staff->operator"; ?>", 4);'/>
															<span class="input-group-addon">Minutes</span>
														<?php
														}
														else { 
														?>
															<span class='input-group-addon'><?php printf("<i class='$sv[currency]'></i> %.2f x ", $mat_used->material->price); ?></span>
															<input class='form-control' type='number' name='mu_quantity_<?php echo $mu_id; ?>' id='mu_quantity-<?php echo $mu_id; ?>' autocomplete='off'
															value='<?php echo $mat_used->quantity_used; ?>' min='0' max='9999' onkeyup='change_mu_staff(this, "<?php echo "$staff->operator"; ?>", 4);'
															style='text-align:right;' onchange='change_mu_staff(this, "<?php echo "$staff->operator"; ?>", 4);'/>
															<span class='input-group-addon'><?php echo $mat_used->material->unit; ?></span>
														<?php } ?>
													</div>
												</td>
											<?php } ?>
										</tr>
										<tr>
											<td>Material Status</td>
											<td>
												<select name="mu_status_<?php echo $mu_id; ?>" id="mu_status-<?php echo $mu_id; ?>" class='form-control' 
												onchange='change_mu_staff(this, "<?php echo "$staff->operator"; ?>", 3);'>
													<?php
													$available_statuses = Status::material_statuses();
													$status_descriptions = Status::getList();
													foreach($available_statuses as $available_status) {
														$selected = $mat_used->status->status_id == $available_status ? "selected" : "";
														echo ("<option value='$available_status' $selected>$status_descriptions[$available_status]</option>");
													} ?>
												</select>
											</td>
										</tr>
										<tr>
											<td>Staff</td>
											<td>
												<input type="text" name="mu_staff_<?php echo $mu_id; ?>" id="mu_staff-<?php echo $mu_id; ?>" placeholder="1000000000" 
												value="<?php if($mat_used->staff) echo $mat_used->staff->operator; ?>" maxlength="10" class='form-control' tabindex="2">
											</td>
										</tr>
									</table>
								</div> <!-- /.panel-body -->
	"
	echo json_encode(array("mu_id" => $mu_id))
}

?>