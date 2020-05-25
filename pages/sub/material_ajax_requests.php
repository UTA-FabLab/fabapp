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
*	 by edit.php, pickup, and end.php to add another material to a transaction.  Page no 
*	 longer allows the changing of a device for a ticket.  After material addition to DB, edit's
*	 HTML will be dressed differently than pickup's and end's.  This is determines by $_POST
*	 parameter "request_from_edit_page".  The edit HTML will contain functions and tabel 
*	 labels matching format of other materials from edit while 
*	FUTURE:	Optionally sepparate edit.php & pickup/end.php opperations.  I did not
*				 sepparate them currently because it would involve much of the same code
*				 duplication, especially for Mats_Used::insert_material_used(....) processes
*	BUGS: 
*
***********************************************************************************************************/


include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

// authenticate permission for user
session_start();
$staff = unserialize($_SESSION['staff']);
if(!$staff || $staff->roleID < $role["staff"]) exit();

//TESTING
// $_SERVER["REQUEST_METHOD"] = "POST";
// $_POST["add_edit_mat_used_instance"] = true;
// $_POST["m_id"] = "13";
// $_POST["trans_id"] = "42649";


if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_new_material"])) {
	$m_id = $_POST["m_id"];
	$trans_id = $_POST["trans_id"];

	check_posted_values($m_id, $trans_id);  // regex passed valued; exit if invalid

	// add to DB
	$mu_id = Mats_Used::insert_material_used($trans_id, $m_id, $status["used"], $staff);
	if(!is_int($mu_id)) {
		echo json_encode(array("error" => "Error associating material with ticket—$mu_id"));
		exit();
	}

	// no errors: create object
	$mat_used = new Mats_Used($mu_id);
	$ticket = new Transactions($trans_id);

	// HTML
	$grouped_and_ungrouped = Mats_Used::group_materials_by_parent($ticket->mats_used);
	$grouplength_and_HTML = HTML_for_newly_added_mat_used($mat_used, $grouped_and_ungrouped);
	$parent_code = $mat_used->material->m_parent ? str_replace(" ", "_", $mat_used->material->m_parent->m_name) : "";

	echo json_encode(array(	"mu_id" => $mu_id,
								"grouplength" => $grouplength_and_HTML["grouplength"],
								"material_HTML" => $grouplength_and_HTML["HTML"], 
								"material_name" => $mat_used->material->m_name,
								"parent_id" => $parent_code));
}
// inventory_processing.php call to update mats_used
elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_inventory"])) {
	$mats_used_changes = json_decode($_POST["mats_used_update"]);
	$successes = $errors = array();
	// $response = array("type" => gettype($mats_used_changes[0]));
	foreach($mats_used_changes as $update) {
		if($update->notes) $update->notes = htmlspecialchars($update->notes);
		$outcome = Mats_Used::insert_material_used(null, $update->m_id, $update->status_id, $staff, $update->quantity_used, $update->notes);
		if(!is_int($outcome)) $errors[$update->m_id] = array(	"m_id" => $update->m_id,
																	"message" => $outcome,
																	"notes" => $update->notes,
																	"status" => $update->status_id, 
																	"quantity" => $update->quantity_used);
		else $successes[$update->m_id] = array(	"m_id" => $update->m_id,
														"message" => $outcome,
														"notes" => $update->notes,
														"status" => $update->status_id, 
														"quantity" => $update->quantity_used);
	}

	$response = array("successes" => $successes, "errors" => $errors);
	echo json_encode($response);
}
// used by edit.php
elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_edit_mat_used_instance"])) {
	$m_id = $_POST["m_id"];
	$trans_id = $_POST["trans_id"];

	check_posted_values($m_id, $trans_id);  // regex passed valued; exit if invalid

	// add to DB
	$mu_id = Mats_Used::insert_material_used($trans_id, $m_id, $status["used"], $staff);
	if(!is_int($mu_id)) {
		echo json_encode(array("error" => "Error associating material with ticket—$mu_id"));
		exit();
	}

	// no errors: create object
	$mat_used = new Mats_Used($mu_id);
	$ticket = new Transactions($trans_id);

	// return instance_HTML
	$HTML = edit_page_new_mat_used_HTML($mat_used);
	echo json_encode(array(	"mu_id" => $mu_id,
								"material_HTML" => $HTML, 
								"material_name" => $mat_used->material->m_name));
}

elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["material_info"]))
{
	if(!Materials::regexID($_POST["material_info"])) exit();
	$mat = new Materials($_POST["material_info"]);

	$attributes = array(
			"m_name" => $mat->m_name, "m_parent" => $mat->m_parent->m_id, "price" => $mat->price, 
			"unit" => $mat->unit, "color_hex" => "#$mat->color_hex", "is_measurable" => $mat->is_measurable, 
			"product_number" => $mat->product_number, "is_current" => $mat->is_current);
	echo json_encode($attributes);
}


// if passed values do not match formatting, exit from system
function check_posted_values($m_id, $trans_id) {
	// check inputs
	if(!Materials::regexID($m_id)) {
		echo json_encode(array("error" => "Material #$m_id is invalid"));
		exit();
	}
	elseif(!Transactions::regexTrans($trans_id)) {
		echo json_encode(array("error" => "Ticket #$trans_id is invalid"));
		exit();
	}
}


// —————————————————— HTML ———————————————————
// ————————————————————————————————————————

function HTML_for_newly_added_mat_used($mat_used, $grouped_and_ungrouped) {
	foreach($grouped_and_ungrouped["grouped"] as $parent_name => $grouped_mats_used) {
		if(in_array($mat_used, $grouped_mats_used)) {
			// material added has no group to add to
			if(count($grouped_mats_used) == 2)
				return array(	"HTML" => Mats_Used::grouped_materials_HTML($grouped_mats_used, $parent_name),
								"grouplength" => 2);

			// group already existed, add parent table to add to
			return array(	"HTML" => $mat_used->instance_HTML(),
							"grouplength" => count($grouped_mats_used));
		}

	}

	// prior single material of group, create new group: placed in table to allow for highlighting
	$HTML = "<table class='table table-bordered'>
					<tr>
						<td>".
							$mat_used->instance_HTML().
						"</td>
					</tr>
				</table>";
	return array(	"HTML" => $HTML, 
					"grouplength" => 1);
}


function mats_used_share_parents_and_not_same_instance($mu1, $mu2) {
	if($mu1->mu_id != $mu2->mu_id && $mu1->material->m_parent == $mu2->material->m_parent)
		return true;
	return false;
}


function edit_page_new_mat_used_HTML($mat_used) {
	// ——— POPULATION STUFF ———
	$readonly = $staff->operator == $mat_used->staff->operator ? "readonly" : "";  // prevent staff from listing someone else as changer
	$mu_id = $mat_used->mu_id;
	
	// options for material select
	// list all Materials that are available to that device
	$material_options = "";
	$device_materials = Materials::getDeviceMats($ticket->device->device_group->dg_id);
	foreach($device_materials as $material) {
		$selected = $material->m_id == $mat_used->material->m_id ? "selected" : "";
		$material_options .= "\n<option $selected value='$material->m_id'>$material->m_name</option>";
	}

	// quantity 
	if($mat_used->material->is_measurable) {
		$input = 	"<tr>
						<td>
							Quantity Used
						</td>
						<td>
							<div class='input-group'>";

		if($mat_used->material->unit == "hour(s)" || $mat_used->material->unit == "hours") { 
			$hour = floor($mat_used->quantity_used);
			$minute = ($mat_used->quantity_used - $hour) * 60;
			$input .= "<span class='input-group-addon'>".sprintf("<i class='$sv[currency]'></i> %.2f x ", $mat_used->material->price)."</span>
			<!-- hours is not set to a minimum because it is assumed staff knows what they are doing -->
			<input class='form-control' type='number' name='$mu_id-hour' id='$mu_id-hour' autocomplete='off' tabindex='2'
			value='$hour' min='0' max='9999' step='1' style='text-align:right;' onkeyup='change_edit_staff(this, \"$staff->operator\", 4, 1);'
			onchange='change_edit_staff(this, \"$staff->operator\", 4, 1);'/>
			<span class='input-group-addon'>Hours</span>
			<input class='form-control' type='number' name='$mu_id-minute' id='$mu_id-minute' autocomplete='off' tabindex='2'
			value='$minute' min='0' max='9999' style='text-align:right;' onkeyup='change_edit_staff(this, \"$staff->operator\", 4, 1);'
			onchange='change_edit_staff(this, \"$staff->operator\", 4, 1);'/>
			<span class='input-group-addon'>Minutes</span>";
		}
		else { 
			$input .= "<span class='input-group-addon'>".sprintf("<i class='$sv[currency]'></i> %.2f x ", $mat_used->material->price)."</span>
			<input class='form-control' type='number' name='$mu_id-quantity' id='$mu_id-quantity' autocomplete='off'
			value='$mat_used->quantity_used' min='0' max='9999' onkeyup='change_edit_staff(this, \"$staff->operator\", 4, 1);'
			style='text-align:right;' onchange='change_edit_staff(this, \"$staff->operator\", 4, 1);'/>
			<span class='input-group-addon'>".$mat_used->material->unit."</span>";
		}
		$input .= "</div>
				</td>
			</tr>";
	}

	$HTML = 
		"<div class='panel-body'>
			<table id='mu-$mu_id' class='table table-bordered table-striped mats_used' style='table-layout:fixed'>
				<tr>
					<td class='col-md-4'>Material</td>
					<td class='col-md-8'>
						<select name='<?php echo $mu_id; ?>-material' id='<?php echo $mu_id; ?>-material' class='form-control' 
						onchange='change_edit_staff(this, '$staff->operator', 3, 1);'>
							$material_options
						</select>
					</td>
				</tr>
				$input
				<tr>
					<td>Material Status</td>
					<td>
						<select name='$mu_id-select' id='$mu_id-select' class='form-control' 
						onchange='change_edit_staff(this, \"$staff->operator\", 3, 1);'>
							<option value='$status[used]'".($mat_used->status->status_id == $status["used"] ? "selected" : "").">Used</option>
							<option value='$status[unused]'".($mat_used->status->status_id == $status["unused"] ? "selected" : "").">Unused</option>
							<option value='$status[failed_mat]'".($mat_used->status->status_id == $status["failed_mat"] ? "selected" : "").">Failed Material</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>Staff</td>
					<td>
						<input type='number' name='$mu_id-staff' id='$mu_id-staff' placeholder='1000000000' 
						value='".($mat_used->staff ? $mat_used->staff->operator : "")."' onkeyup='restrict_size(this);' 
						onchange='restrict_size(this);' class='form-control' $readonly>
					</td>
				</tr>
			</table>
		</div> <!-- /.panel-body -->";
	return $HTML;
}

?>