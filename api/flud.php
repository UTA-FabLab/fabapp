<?php
/*
 *  CC BY-NC-AS UTA FabLab 2016-2017
 *
 *  flud.php : Fab Lab User Data
 *
 *  Michael Doran, Systems Librarian
 *  University of Texas at Arlington
 *
 *  Jonathan Le & Arun Kalahasti
 *  FabLab @ University of Texas at Arlington
 *  version: 0.9 beta (2017-09-15)
 *
*/

// Requests/replies via JSON data exchange
// =======================================
// 1) PrintTransaction 
// 2) EndTransaction

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Cache-Control, Origin, X-Requested-With, Content-Type, Accept, Key, X-Api-Key, Authorization");
// Tell PHP what time zone before doing any date function foo 
date_default_timezone_set('America/Chicago');
require_once($_SERVER['DOCUMENT_ROOT']."/connections/db_connect8.php");
include_once ($_SERVER['DOCUMENT_ROOT'].'/class/all_classes.php');
include_once 'gatekeeper.php';
$json_out = array();

/*
//Test Data
$input_data["type"] = "print";
$input_data["uta_id"] = "1000000000";
$input_data["device_id"] = "0021";
$input_data["m_id"] = "29";
$input_data["est_filament_used"] = "1";
$input_data["est_build_time"] = "01:01:00";
$input_data["filename"] = "Jon Le's Test";
$input_data["p_id"] = "3";
*/

//Compare Header API Key with site variable's API Key
$headers = apache_request_headers();
if(isset($headers['Authorization'])){
    if ($sv['api_key'] != $headers['Authorization'] ){
        $json_out["authorized"] = "N";
        $json_out["ERROR"] = "Unable to Authenticate";
        ErrorExit(2);
    }
} else {
    $json_out["authorized"] = "N";
    $json_out["ERROR"] = "Header Not Set";
    ErrorExit(2);
}


// Input posted with "Content-Type: application/json" header
$input_data = json_decode(file_get_contents('php://input'), true);
if (! ($input_data)) {
    $json_out["ERROR"] = "Unable to decode JSON message - check syntax";
    ErrorExit(1);
}

// Extract message type from incoming JSON
$type = $input_data["type"];

// Check the request type
if (strtolower($type) == "print") {
    $operator  = $input_data["uta_id"];
    $device_id = $input_data["device_id"];
    PrintTransaction ($operator, $device_id);

} elseif (strtolower($type) == "update_end_time") {
    $device_id = $input_data["device_id"];
    update_end_time( $device_id );

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
//  PrintTransaction
//  Inserts entry into the 'transactions' table

function PrintTransaction ($operator, $device_id) {
    global $json_out;
    global $mysqli;
    global $input_data;
	$json_out["authorized"] = "N";
        
    foreach (gatekeeper($operator, $device_id) as $key => $value){
        $json_out[$key] =  $value;
    }

    if ($json_out["authorized"] == "N"){
        ErrorExit(0);
    }
    $auth_status = $json_out["status_id"];

    if ($device_name_result = mysqli_query($mysqli, "
        SELECT  `device_desc`, `d_id`
        FROM  `devices` 
        WHERE  `device_id` =  '$device_id';
    ")){
        $row = $device_name_result->fetch_array();
        if (!is_null($row)){
            $d_id = $row["d_id"];
        }
        else{
            $json_out["device_name"] = "Not found";
        }
        $device_name_result->close();
    }
    
    if ($input_data["m_id"]){
        $m_id = $input_data["m_id"];
        $material_name_result = mysqli_query($mysqli, "
            SELECT `materials`.`m_name`, `materials`.`price`, `materials`.`unit` 
            FROM `materials` 
            WHERE `materials`.`m_id` = '$m_id'
            LIMIT 1;
        ");

        $row = $material_name_result->fetch_array();
        //Deny if material selected is Generic
        if (!(strpos($row['m_name'], "Generic") === false)){
            $json_out["ERROR"] = "Please Select Material";
            $json_out["authorized"] = "N";
            return;
        }
		
        if (!is_null($row["m_name"])){
            $json_out["m_name"] = $print_json["m_name"] = $row["m_name"];
        } else {
            $json_out["m_name"] = "Not found";
        }
        $material_name_result->close();
    }

    if ($input_data["est_build_time"]){
        $est_build_time = $input_data["est_build_time"];
    }

    if ($input_data["filename"]){
        $filename = "|$input_data[filename]|";
    }

    if ($input_data["p_id"]){
        $p_id = $input_data["p_id"];
    }

	//V.87 to v.90 hybrid call
    if ($insert_result = $mysqli->query("
        INSERT INTO transactions 
            (`operator`,`d_id`,`t_start`,`status_id`,`p_id`,`est_time`, `uta_id`, `device_id`, `purp_id`) 
        VALUES
            ('$operator','$d_id',CURRENT_TIMESTAMP,'$auth_status','$p_id','$est_build_time','$operator','00$d_id','$p_id');
    ")){
        $trans_id = $json_out["trans_id"] = $mysqli->insert_id;
		$print_json["trans_id"] = $trans_id;
        
        if ($stmt = $mysqli->prepare("
            INSERT INTO mats_used
                (`trans_id`,`m_id`,`status_id`, `mu_notes`, `mu_date`, `date`) 
            VALUES
                (?, ?, ?, ?, CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);
        ")){
            $bind_param = $stmt->bind_param("iiis", $trans_id, $m_id, $auth_status, $filename);
            $stmt->execute();
            $stmt->close();
        } else {
			$json_out["ERROR"] = $mysqli->error;
            $json_out["authorized"] = "N";
            return;
        }
    } else {
        $json_out["ERROR"] = $mysqli->error;
		$json_out["authorized"] = "N";
        return;
    }
	
	$msg = Transactions::printTicket($trans_id, $input_data["est_filament_used"]);
	if (is_string($msg)){
		$json_out["ERROR"] = $msg;
	}
}


////////////////////////////////////////////////////////////////
//
//  update_end_time
//  updates the database with a given device ID. Will not close the ticket, only updates the time and status

function update_end_time( $dev_id ){
	global $json_out;
	global $mysqli;
    
	// Check for deviceID value
    if (! (preg_match("/^\d*$/", $dev_id))) {
        $json_out["ERROR"] = "Invalid transaction number";
        ErrorExit(1);
    }
 
    $update_result = mysqli_query($mysqli,"
        UPDATE `transactions`
        SET `t_end` = CURRENT_TIMESTAMP, `status_id` = '11',
            `duration` = SEC_TO_TIME (TIMESTAMPDIFF (SECOND,  t_start, CURRENT_TIMESTAMP))
        WHERE `d_id` = '$dev_id' AND t_end is NULL
        LIMIT 1
    ");
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