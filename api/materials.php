<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Cache-Control, Origin, X-Requested-With, Content-Type, Accept, Key, X-Api-Key, Authorization");

/*
 *  materials.php : Materials list interface
 *	
 *	Arun Kalahasti & Jon Le
 *  version: 0.9 alpha (2018-03-14)
 *
*/

// Requests/replies via JSON data exchange
// =======================================
// 1) Get Materials By Device ID

require_once( __DIR__."/../connections/db_connect8.php");
include_once ($_SERVER['DOCUMENT_ROOT'].'/class/all_classes.php');
$json_out = array();

//Compare Header API Key with site variable's API Key
$headers = apache_request_headers();
if ($sv['api_key'] == "") {
    $json_out["api_key"] = "Not Set";
} elseif (isset($headers['authorization'])) {
    if ($sv['api_key'] != $headers['authorization'] ){
        $json_out["ERROR"] = "Unable to authenticate Device";
        http_response_code(401);
        ErrorExit(1);
    }
} elseif (isset($headers['Authorization'])) {
    if ($sv['api_key'] != $headers['Authorization'] ){
        $json_out["ERROR"] = "Unable to Authenticate Device";
        http_response_code(401);
        ErrorExit(1);
    }
} else {
    $json_out["ERROR"] = "Header Are Not Set";
    http_response_code(401);
    ErrorExit(1);
}

// Input posted with "Content-Type: application/json" header
$input_data = json_decode(file_get_contents('php://input'), true);
if (! ($input_data)) {
    $json_out["ERROR"] = "Unable to decode JSON message - check syntax";
    ErrorExit(1);
}

// Extract message type from incoming JSON
$type      = $input_data["type"];
	
// Check the request type
if (strtolower($type) == "device_id") {
	$device_id = $input_data["device_id"];
	GetByDeviceID($device_id);
} else {
	$json_out["ERROR"] = "Unknown type: $type";
	ErrorExit(1);
}

	
	// Output JSON and exit
header("Content-Type: application/json");
echo json_encode($json_out);
exit(0);


////////////////////////////////////////////////////////////////
//                           Functions
////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////
//
//  Template 
//  

function Template ($arguments) {
	global $json_out;
	global $mysqli;
	
}

////////////////////////////////////////////////////////////////
//
//  GetByDeviceID 
//  

function GetByDeviceID ($device_id) {
	global $json_out;
	global $mysqli;
	
	CheckDeviceID($device_id);
	
	$result = mysqli_query($mysqli, "
		SELECT `materials`.`m_id`, `materials`.`m_name`, `materials`.`price`, `materials`.`unit`
		FROM `materials` 
		LEFT JOIN `device_materials`
		ON `device_materials`.`m_id` = `materials`.`m_id` 
		WHERE `dg_id` = (SELECT `devices`.`dg_id` FROM `devices` WHERE `devices`.`d_id` = $device_id)
		ORDER BY `m_name`;
    ");
	if ($mysqli->error) {
        $json_out["ERROR"] = $mysqli->error;
        ErrorExit(2);
    }
	
	while($row = $result->fetch_array(MYSQL_ASSOC)) {
            $json_out[] = $row;
    }
	
}

////////////////////////////////////////////////////////////////
//
//  CheckDeviceID 
//  

function CheckDeviceID ($device_id) {
	global $json_out;
	
	// Check for valid device_id value
    if (preg_match("/^\d{1,4}$/",$device_id) == 0) {
        $json_out["success"] = "N";
        $json_out["ERROR"] = "Invalid or missing device_id: $device_id";
        ErrorExit(1);
    }
	
}

////////////////////////////////////////////////////////////////
//
//  ErrorExit
//  Sends error message and quits 

function ErrorExit ($exit_status) {
	global $mysqli;
    global $json_out;
    header("Content-Type: application/json");
	$mysqli->close();
    echo json_encode($json_out);
    exit($exit_status);
}

?>