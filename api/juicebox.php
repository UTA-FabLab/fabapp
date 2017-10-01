<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Cache-Control, Origin, X-Requested-With, Content-Type, Accept, Key, X-Api-Key");
/*
 *  CC BY-NC-AS UTA FabLab 2016-2017
 * 
 *  Suleiman Barakat & Jon Le
 *	FabLab @ University of Texas at Arlington
 *  version: 0.9 beta (2017-01-16)
 */
require_once( __DIR__."/../connections/db_connect8.php");
include 'gatekeeper.php';

// Tell PHP what time zone before doing any date function foo 
date_default_timezone_set('America/Chicago');

/*
//Test Data
$input_data["type"] = "rfid_double";
$input_data["number"] = "769918733";
$input_data["device"] = "21";
$input_data["number_employee"]  = "769918733";
*/

// Input posted with "Content-Type: application/json" header
$input_data = json_decode(file_get_contents('php://input'), true);
if (! ($input_data)) {
	$json_out["ERROR"] = "Unable to decode JSON message - check syntax";
	ErrorExit(1);
}


// Extract message type from incoming JSON
$type = $input_data["type"];
$json_out = array();

if (strtolower($type) == "utaid_double"){				// added this part to support user + learner transaction with utaid
	$operator = $input_data["number"];
	$device_id = $input_data["device"];
	$staff_id = $input_data["number_employee"];
	OnTransaction_double($operator, $staff_id, $device_id);

} elseif(strtolower($type) == "rfid_double"){				// added this part to support user + learner transac 
	$operator = RFIDtoUTAID($input_data["number"]);
	$device_id = $input_data["device"];
	$staff_id = RFIDtoUTAID($input_data["number_employee"]);
	OnTransaction_double($operator, $staff_id, $device_id);

} elseif(strtolower($type) == "check_status_utaid"){
	check_user_status( $input_data["number"] );
	
} elseif(strtolower($type) == "check_status_rfid"){
	check_user_status(RFIDtoUTAID($input_data["number"]) );

}elseif( strtolower($type) == "end_transaction" ){
	end_transaction( $input_data["trans_id"] );
} else {
	$json_out["ERROR"] = "Unknown type: $type";
	ErrorExit(1);
}

echo json_encode($json_out);

	
////////////////////////////////////////////////////////////////
//                      OnTransaction_double
//   What do I do?
//
function OnTransaction_double ($operator, $staff_id ,$device_id) {
    global $json_out;
	
	//pass user to check if authorized through Gatekeeper
    foreach (gatekeeper($operator, $device_id) as $key => $value){
        $json_out[$key] =  $value;
    }
    if ($json_out["authorized"] == "N"){
		$json_out["ID"] = $operator;
        ErrorExit(0);
    }
    $status_id = $json_out["status_id"];
	
	foreach (gatekeeper($staff_id, $device_id) as $key => $value){
        $json_out[$key] =  $value;
    }
    if ($json_out["authorized"] == "N"){
		$json_out["ID"] = $staff_id;
        ErrorExit(0);
    }
	$status_id_2 = $json_out["status_id"];

	$role_id_1 = (int)getRole_from_id( $operator );
	$role_id_2 = (int)getRole_from_id( $staff_id );
	
	if( ($role_id_1 == 3 || $role_id_1 == 4 ) && $role_id_2 > 7 ) {
		CreateTransaction_double($operator, $staff_id, $device_id, $status_id);
		
	} else if( ($role_id_2 == 3 || $role_id_2 == 4) && $role_id_1 > 7 ){
		CreateTransaction_double($staff_id, $operator, $device_id, $status_id);

	} else if( $role_id_1 >= 7 && $role_id_2 >= 7 && $operator != $staff_id) {
		CreateTransaction_double($operator, $staff_id, $device_id, $status_id);
		
	}else if($role_id_1 > 8 && $role_id_2 > 8 && $operator == $staff_id){
		CreateTransaction_double($operator, $staff_id, $device_id, $status_id);
		
	} else if($operator == $staff_id && ($role_id_1 >= 9 || $role_id_1 == 7)){
		CreateTransaction_double($staff_id, $operator, $device_id, $status_id);
	
	} else if( $operator == $staff_id ) { 
		$json_out["ERROR"] = "both id's are the same";
		$json_out["success"] = "N";
		$json_out["authorized"] = "N";
		echo json_encode($json_out);
		exit(0);

	} else {
		$json_out["ERROR"] = "$role_id_1 $role_id_2 one of the id's does not have access";
		$json_out["authorized"] = "N";
		$json_out["success"] = "N";
		echo json_encode($json_out);
		exit(0);
	}
}

////////////////////////////////////////////////////////////////
//                     CreateTransaction_double
//  Inserts entry into the 'transactions' table
//  

function CreateTransaction_double ($staff_id, $operator, $device_id, $status_id) {
	global $json_out;
	global $mysqli;
	
    $insert_result = mysqli_query($mysqli, "
      INSERT INTO transactions 
        (`operator`,`d_id`,`t_start`,`status_id`) 
      VALUES
        ('$operator','$device_id',CURRENT_TIMESTAMP,'$status_id');
    ");
    $mysqli_error = mysqli_error($mysqli);
    if ($mysqli_error) {
			$json_out["ERROR"] = $mysqli_error;
			ErrorExit(2);
    } else {
        $trans_id = mysqli_insert_id($mysqli);
        $json_out["trans_id"]   = $trans_id;
        $json_out["status_id"] = $status_id;
        $json_out["authorized"] = "Y";
    }
    
    $json_out["trans_id"]   = $trans_id;
   
    
    
}


////////////////////////////////////////////////////////////////
// check status of utaid
// this will return the role number of a given utaid
//
function getRole_from_id( $operator ){
	global $mysqli;

	$result = mysqli_query($mysqli, "
		SELECT r_id FROM users WHERE operator = '$operator'
		");
		
	$row = mysqli_fetch_row($result);
	return $row[0];
}

function check_user_status( $operator ){
	global $json_out;
	global $mysqli;

	//Check if transaction has already ended
	$result = mysqli_query($mysqli, "
		SELECT r_id FROM users WHERE operator = '$operator'
    	");
    	
    	
	$row = mysqli_fetch_row($result);
	
	$json_out["role"] = $row[0];
	
	return $row[0];
}

////////////////////////////////////////////////////////////////
//
//  RFIDtoUTAID
//  Matches RFID to a UTA ID
//  Users::RFIDtoOperator("1000000000");
function RFIDtoUTAID ($rfid_no) {
   global $json_out;
   global $mysqli;


    $result = mysqli_query($mysqli, "
		SELECT operator FROM rfid WHERE rfid_no = $rfid_no
    ");
	
    $mysql_error = mysqli_error($mysqli);
    if ($mysql_error) {
        $json_out["ERROR"] = $mysql_error;
        ErrorExit(2);
    }

    $row = $result->fetch_array(MYSQLI_NUM);;
    $operator = $row[0];

    if ($operator) {
        return($operator);
    } else {
        $json_out["ERROR"] = "No ID match for RFID $rfid_no";
        ErrorExit(1);
    }
} 



function end_transaction( $num ){
	global $json_out;
	global $mysqli;
	
	$result = mysqli_query($mysqli, "
		UPDATE transactions
			SET t_end = CURRENT_TIMESTAMP, duration = TIMESTAMPDIFF(second, t_start, t_end), status_id = 14 
			WHERE trans_id = $num
    	");
	
	$json_out["CONTENT"] = "transaction ". $num . "has been closed";
	$json_out["success"] = "Y";
	echo json_encode($json_out);
}



function ErrorExit ($exit_status) {
	global $mysqli;
    global $json_out;
    //header("Content-Type: application/json");
	$mysqli->close();
    echo json_encode($json_out);
    exit($exit_status);
}


?>