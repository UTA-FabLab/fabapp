<?php

/***********************************************************************************************************
*	
*	@author MPZinke
*	created on 08.07.19 
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.94
*		-House Keeping (DB cleanup, $status variable, class syntax)
*		-Multiple Materials
*		-Off-line Mode
*		-Sheet Goods
*		-Storage Box
*
*	DESCRIPTION: AJAX POST call page to execute asyncronous JSON responses.  Used
*	 by end.php & lookup.php.  This page pertains to Storage Box: display drawer, confirm
*	 that new drawer name or unit is not currently held by another drawer/unit
*	FUTURE: 
*	BUGS: 
*
***********************************************************************************************************/

// echo json_encode(array("error" => "TEST"));

include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

// authenticate permission for user
session_start();
$staff = unserialize($_SESSION['staff']);
if(!$staff || $staff->roleID < $role["staff"]) exit();



// get storage drawer with highlighted unit for unit type
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["drawer_for_type"])) {
	$unit_type = htmlspecialchars($_POST["unit_type"]);
	$drawer = StorageDrawer::get_a_drawer_and_unit_for_type($unit_type);

	if(is_string($drawer)) {
		echo json_encode(array("error" => $drawer));
		exit();
	}

	echo json_encode(array(	"drawer_HTML" => $drawer->HTML_display(), 
								"drawer_label" => $drawer->drawer_indicator,
								"unit_label" => $drawer->selected_units[0]->unit_indicator));
}


// get all available/unoccupied units for drawer
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["choose_unit_in_drawer"])) {
	$drawer_indicator = $_POST["drawer_label"];

	// standard display for drawer
	$standard_behavior = array(	"style" => "background-color:#999999;border:solid;border-width:2px;", 
									"onclick" => "alert(\"Box is occupied\");", "label" => "Occupied");
	$empty_behavior = array("style" => "background-color:#000000;color:#000000;border:solid black;border-width:2px;");
	$selected_callback = function($unit) {return !$unit->trans_id;};
	$selected_behavior = array("style" => "background-color:#00FF00;border:solid black;border-width:2px;", 
									"onclick" => "add_to_location(this)");

	$drawer = new StorageDrawer($drawer_indicator, $standard_behavior, $empty_behavior, $selected_callback, $selected_behavior);

	echo json_encode(array("drawer_HTML" => $drawer->HTML_display()));
}


// move in storage
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["move_to_new_location"])) {
	$drawer = htmlspecialchars($_POST["drawer"]);
	$unit = htmlspecialchars($_POST["unit"]);
	$trans_id = htmlspecialchars($_POST["trans_id"]);

	$error = StorageObject::add_object_to_location_from_possible_previous($drawer.$unit, $staff, $trans_id);
	echo json_encode(array("error" => $error));
}


// move to storage
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_to_storage"])) {

}

?>