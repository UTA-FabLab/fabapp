<?php

/***********************************************************************************************************
*	
*	@author MPZinke
*	created on 08.14.19 
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.93.1
*		-Data Reports update
*
*	DESCRIPTION: AJAX POST call page to execute asyncronous JSON responses.  Used
*	 by stats.php
*	FUTURE:	
*	BUGS: 
*
***********************************************************************************************************/


include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

// authenticate permission for user
session_start();
$staff = unserialize($_SESSION['staff']);
if(!$staff || $staff->roleID < $role["admin"]) exit();

//———TESTING———
// $_SERVER["REQUEST_METHOD"] = "POST";
// $_POST["prebuilt_query"] = true;
// $_POST["query"] = "byHour";
// $_POST["start_time"] = "2020-10-01";
// $_POST["end_time"] = "2020-10-17";
// $_POST["device"] = "*";

try
{
	if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["prebuilt_query"])) {
		$function = $_POST["query"];
		$start = $_POST["start_time"];
		$end = $_POST["end_time"];
		$device = $_POST["device"];

		$pie_chart_label_column = htmlspecialchars($_POST["pie_chart_label_column"]);
		$pie_chart_data_column = htmlspecialchars($_POST["pie_chart_data_column"]);
		$pie_chart_label_column = "Hour";  //TESTING
		$pie_chart_data_column = "Count";  //TESTING

		$params = Database_Query::prebuilt_query($end, $function, $start, $device);
		$query_object = new Database_Query($params["statement"]);
		echo json_encode(array(	"HTML" => $query_object->HTML_table,
									"pie_chart" => $query_object->pie_chart_data($pie_chart_data_column, $pie_chart_label_column),
									"statement" => $query_object->statement,
									"tsv" => $query_object->tsv,
									"warning" => $query_object->warning));
	}


	// ———————————————— CUSTOM QUERY ————————————————

	// get columns to select from
	if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["columns_for_table"])) {
	// elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["columns_for_table"])) {
		$table = new Database_Table(filter_input(INPUT_POST, "table_id"));

		echo json_encode(array(	"columns" => $table->columns,
									"column_names" => $table->column_names,
									"name" => $table->name,
									"table" => $table->table, 
									"types" => $table->column_types));
	}

	// submit custom query and return results
	elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["custom_query"])) {
		$statement = $_POST["statement"];
		// $statement = "SELECT `materials`.*,`device_materials`.* FROM `materials` JOIN `device_materials` ON `materials`.`m_id` = `device_materials`.`m_id`";
		$query_object = new Database_Query($_POST["statement"]);
		if($query_object->error) echo_error($query_object->error);

		echo json_encode(array(	"HTML" => $query_object->HTML_table,
									"statement" => $statement,
									"tsv" => $query_object->tsv,
									"warning" => $query_object->warning));
		// exit();
	}
}
catch(Exception $e)
{
	echo json_encode(array("error" => $e->getMessage()));
}


// -————————————  PREBUILD QUERY ————————————— 
// 	-Get fields, sort values, inject JS function call at bottom of page

function create_pie_chart($head, $statement) {
	global $mysqli;

	if($results = $mysqli->query($statement)) {
		// convert results into array
		$values = array();
		while($row = $results->fetch_assoc())
			$values[$row[$head[0]]] = $row[$head[1]];

		$sum = array_sum($values);

		// get proportionality of each value for each key
		$slices = array();
		foreach($values as $key => $value)
			$slices[] = "$key,".strval($value / $sum);

		return implode(";", $slices);
	}	
}


function echo_error($error_message) {
	echo json_encode(array("error" => $error_message));
	exit();
}


?>