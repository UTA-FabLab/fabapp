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
*	 by storage_unit_creator.php.  This page pertains to Storage Box: movement of objects and 
*	 displaying of drawers
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

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["display_unit"])) {
	$drawer_indicator = htmlspecialchars($_POST["drawer"]);
	$unit_indicator = htmlspecialchars($_POST["unit"]);

	$standard_behavior = array(	"style" => "background-color:#999999;border:solid;border-width:2px;", 
									"onclick" => "alert(\"No touching!\");");
	$empty_behavior = array("style" => "background-color:#000000;color:#000000;border:solid black;border-width:2px;");
	$selected_callback = function($unit) use ($unit_indicator) {return $unit->unit_indicator == $unit_indicator;};
	$selected_behavior = array("style" => "background-color:#00FF00;border:solid black;border-width:2px;");

	$drawer = new StorageDrawer($drawer_indicator, $standard_behavior, $empty_behavior, $selected_callback, $selected_behavior);

	$trans_id = $drawer->selected_units[0]->trans_id ? $drawer->selected_units[0]->trans_id : null;

	echo json_encode(array("drawer_HTML" => $drawer->HTML_display(), "trans_id" => $trans_id, "type" => $drawer->selected_units[0]->type));
}


if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["drawer_indicator_is_valid"])) {
	$drawer_indicator = htmlspecialchars($_POST["drawer"]);

	// valid if new drawer indicator is not in array
	echo json_encode(array("is_valid" => !in_array($drawer_indicator, StorageDrawer::get_unique_drawers())));
}


if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["unit_indicator_is_valid"])) {
	$drawer = htmlspecialchars($_POST["drawer"]);
	$unit = htmlspecialchars($_POST["unit"]);

	echo json_encode(array("is_valid" => !in_array($unit, StorageDrawer::drawer_units_labels($drawer))));
}

?>