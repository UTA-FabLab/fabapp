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
if(!$staff || $staff->roleID < $role["lead"]) exit();


if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_new_material"])) {
	$m_id = $_POST["m_id"];
	$trans_id = $_POST["trans_id"];

	check_posted_values($m_id, $trans_id);  // regex passed valued; exit if invalid

	// add to DB
	$mu_id = Mats_Used::insert_material_used($trans_id, $m_id, $status["used"], $staff);
	if(!is_int($mu_id))  
		echo json_encode(array("error" => "Error associating material with ticket—$mu_id"));

	// no errors: create object
	$mat_used = new Mats_Used($mu_id);

	$mat_used_HTML = HTML_table_for_mat_used($mat_used);
	$parent_code = $mat_used->material->m_parent ? str_replace(" ", "_", $mat_used->material->m_parent->m_name) : "";

	echo json_encode(array(	"mu_id" => $mu_id, 
								"material_HTML" => $mat_used_HTML, 
								"material_name" => $mat_used->material->m_name,
								"parent_id" => $parent_code));
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

// create HTML for the mat_used
function HTML_table_for_mat_used($mat_used) {
	$header = HTML_header_for_request($mat_used->material);
	$quantity_input = HTML_quantity_input($mat_used);
	$status_select = HTML_status_select($mat_used);
	$staff_input = HTML_staff_row($mat_used);

	// name excluding spaces for id's and classes
	$parent_code = $mat_used->material->m_parent ? str_replace(" ", "_", $mat_used->material->m_parent->m_name) : "";

	return	"<table class='table $parent_code' width='100%' style='margin-bottom:0px !important;'>
				$header
				$quantity_input  <!-- this line is for measurable materials only -->
				<tr>
					$status_select
				</tr>
				$staff_input  <!-- this line is edit.php only -->
			</table>";
}


// create html header for material display (selectable mats for edit.php or mat name for end/pickup)
function HTML_header_for_request($selected_material) {
	global $_POST, $staff;

	// pickup/end
	if(!$_POST["request_from_edit_page"]) 
		return	"<tr class='tablerow info' width='100%'>
					<td colspan='2'>
						$selected_material->m_name".
						($selected_material->color_hex ? " <div class='color-box' style='background-color:#$selected_material->color_hex;' align='left'/>" : null).
					"</td>
				</tr>";

	// -- FOR EDIT.PHP --
	// other optional materials
	$device_materials = Materials::getDeviceMats($ticket->device->device_group->dg_id);
	$material_select_options = "";
	foreach($device_materials as $device_mat) {
		$selected = $device_mat->m_id == $selected_material->m_id ? "selected" : "";
		$material_select_options .= "<option $selected value='$device_mat->m_id'>$device_mat->m_name</option>\n";
	}

	return	"<tr>
				<td class='col-md-4'>Material</td>
				<td class='col-md-8'>
					<select name='$mu_id-material' id='$mu_id-material' class='form-control' 
					onchange='change_edit_staff(this, \"$staff->operator\", 3, 1);'>
						$material_select_options
					</select>
				</td>
			</tr>";
}


// create input html for quanities (either time or standard): used by create_material_html_block(.)
function HTML_quantity_input($mat_used) {
	global $_POST, $staff, $sv;

	// -- JS FUNCTIONS FOR INPUT --
	if($_POST["request_from_edit_page"])  // edit.php functions (track person who changes input)
		$input_functions =		"onkeyup='change_edit_staff(this, \"$staff->operator\", 4, 1);'
								onchange='change_edit_staff(this, \"$staff->operator\", 4, 1);'";
	else  // end/pickup.php functions (multiple material price calculation/status adjustment)
		$input_functions =		"onkeyup='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' 
								onchange='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' ";

	// html for table with label (edit.php) or input that spans table
	if($_POST["request_from_edit_page"])
		$row_format =	"<tr>
							<td>
								Quantity Used
							</td>
							<td>";
	else
		$row_format =	"<tr colspan='2'>
							<td>";

	// -- CREATE INPUT --
	// time based (2 inputs for hour/minute)
	if($mat_used->material->unit == "hour(s)") {
		$min_hours = intval($sv['minTime']);

		$hour = floor($mat_used->quantity_used);
		$minute = ($mat_used->quantity_used - $hour) * 60;
		return	"$row_format
						<div class='input-group'>
							<span class='input-group-addon'><i class='$sv[currency]'></i> ".sprintf("%0.2f", $mat_used->material->price)." x </span>
							<input type='number' id='$mat_used->mu_id-input' class='form-control mat_used_input $parent_code-child time' 
							$input_functions
							autocomplete='off' style='text-align:right;' min='$min_hours' step='1' value='$hour'>
							<span class='input-group-addon'>Hours</span>

							<input type='number' id='$mat_used->mu_id-minute' class='form-control time' 
							$input_functions
							autocomplete='off' style='text-align:right;' min='0' max='59' value='$minute'>
							<span class='input-group-addon'>Minutes</span>
						</div>
					</td>
				</tr>";
	}
	return 	"$row_format
					<div class='input-group'>
						<span class='input-group-addon'><i class='$sv[currency]'></i> ".sprintf("%0.2f", $mat_used->material->price)." x </span>
						<input type='number' id='$mat_used->mu_id-input' class='form-control mat_used_input $parent_code-child' 
						$input_functions
						autocomplete='off' value='".sprintf("%0.2f", $mat_used->quantity_used)."' style='text-align:right;' min='0'/>
						<span class='input-group-addon'>".$mat_used->material->unit."</span>
					</div>
				</td>
			</tr>";
}


// HTML staff row that contains staff input when called by edit page, otherwise null
function HTML_staff_row($mat_used) {
	global $_POST;

	if($_POST["request_from_edit_page"])
		return	"<tr>
					<td>Staff</td>
					<td>
						<input type='text' name='$mu_id-staff' id='$mu_id-staff' placeholder='1000000000' 
						value='".($mat_used->staff ? $mat_used->staff->operator : "")."' maxlength='10' class='form-control' tabindex='2'>
					</td>
				</tr>";
	return null;
}


/* create html code for mat_used status: determine measurability, functions (end/pickup vs edit)
select status for current status of mat_used.  Add info to <select> */
function HTML_status_select($mat_used) {
	global $_POST, $staff, $status;

	// -- JS FUNCTIONS FOR INPUT --
	if($_POST["request_from_edit_page"])  // edit.php functions (track person who changes select)
		$status_function = "onchange='change_edit_staff(this, \"$staff->operator\", 3, 1);'";
	else  // end/pickup.php functions (multiple material price calculation/status adjustment)
		$status_function = "onchange='adjust_ticket_status(this); adjust_input_for_status(this);'";

	// determine class to choose from in pickup/end
	$measurability = $mat_used->material->is_measurable ? "measurable" : "immeasurable";

	// statuses
	$material_statuses = "";
	$status_descriptions = Status::getList();
	foreach(array($status["used"], $status["unused"], $status["failed_mat"]) as $available_status) {
		$selected = $mat_used->status->status_id == $available_status ? "selected" : "";
		$material_statuses .= "<option value='$available_status' $selected>$status_descriptions[$available_status]</option>";
	}

	// add edit.php table row label if request from edit.php
	$row_format =	$_POST["request_from_edit_page"] ? "<td>Material Status</td>" : "";
	return	"$row_format
			<td>
				<select name='$mat_used->mu_id-select' id='$mat_used->mu_id-select' class='form-control' $status_function>
					$material_statuses 
				</select>
			<td>";
}

?>