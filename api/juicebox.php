<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Cache-Control, Origin, X-Requested-With, Content-Type, Accept, Key, X-Api-Key, Authorization");
/*
 *  CC BY-NC-AS UTA FabLab 2016-2017
 * 
 *  Suleiman Barakat & Jon Le
 *	FabLab @ University of Texas at Arlington
 *  version: 0.9 beta (2017-10-18)
 */
require_once ($_SERVER['DOCUMENT_ROOT']."/connections/db_connect8.php");
include_once ($_SERVER['DOCUMENT_ROOT'].'/class/all_classes.php');
include_once 'gatekeeper.php';

// Tell PHP what time zone before doing any date function foo
date_default_timezone_set('America/Chicago');

/*
// RFID to Role ID
$input_data["type"] = "check_status_rfid";
$input_data["number"] = "1415435516";
*/

/*
//Test Data
$input_data["type"] = "utaid_double";
$input_data["device"] = "2";
$input_data["number"] = "1000000003";
$input_data["number_employee"]  = "1000000007";
*/

/*
//Compare Header API Key with site variable's API Key
$headers = apache_request_headers();
if(isset($headers['Authorization'])){
    if ($sv['api_key'] != $headers['Authorization'] ){
        $json_out["ERROR"] = "Unable to Authenticate Device";
        ErrorExit(1);
    }
} else {
    $json_out["ERROR"] = "Header Are Not Set";
    ErrorExit(1);
}

// Input posted with "Content-Type: application/json" header
$input_data = json_decode(file_get_contents('php://input'), true);
if (! ($input_data)) {
    $json_out["ERROR"] = "Unable to decode JSON message - check syntax";
    ErrorExit(1);
}
*/


// Extract message type from incoming JSON
$type = $input_data["type"];
$json_out = array();

if (strtolower($type) == "utaid_double"){				// added this part to support user + learner transaction with utaid
	$user = Users::withID($input_data["number"]);
	$staff = Users::withID($input_data["number_employee"]);
	$device_id = $input_data["device"];
	OnTransaction_double($user, $staff, $device_id);

} elseif(strtolower($type) == "rfid_double"){				// added this part to support user + learner transac 
	$user = RFIDtoUTAID($input_data["number"]);
	$device_id = $input_data["device"];
	$staff = RFIDtoUTAID($input_data["number_employee"]);
	OnTransaction_double($user, $staff, $device_id);

} elseif(strtolower($type) == "check_status_utaid"){
	check_user_status( Users::withID($input_data["number"]) );
	
} elseif(strtolower($type) == "check_status_rfid"){
	check_user_status( RFIDtoUTAID($input_data["number"]) );

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
function OnTransaction_double ($user, $staff, $device_id) {
    global $json_out;
    global $sv;
	
    $json_out['device'] = "$device_id";
    //pass user to check if authorized through Gatekeeper
    foreach (gatekeeper($user, $device_id) as $key => $value){
        $json_out[$key] =  $value;
    }
    if ($json_out["authorized"] == "N"){
        $json_out["ID"] = $user->getOperator();
        ErrorExit(1);
    }
    $status_id = $json_out["status_id"];
    foreach (gatekeeper($staff, $device_id) as $key => $value){
        $json_out[$key] =  $value;
    }
    if ($json_out["authorized"] == "N"){
        $json_out["ID"] = $staff->getOperator();
        ErrorExit(1);
    }
    $status_id_2 = $json_out["status_id"];

    if($user != $staff && ($user->getRoleID() >= $sv['LvlOfStaff'] || $staff->getRoleID() >= $sv['LvlOfStaff'])){
        CreateTransaction_double($user, $staff, $device_id, $status_id);

    } else if($user == $staff && ($user->getRoleID() >= $sv['minRoleTrainer'] || $user->getRoleID() == 7)){
        CreateTransaction_double($staff, $user, $device_id, $status_id);

    } else if( $user == $staff ) { 
        $json_out["ERROR"] = "Both id's are the same and lack appropriate Role ID";
        $json_out["success"] = "N";
        $json_out["authorized"] = "N";
        ErrorExit(1);

    } else {
        $json_out["ERROR"] = "ID-1:".$user->getOperator()." Role:".$user->getRoleID()." & ID-2:".$user->getOperator()." Role:".$staff->getRoleID().", one of the id's does not have access";
        $json_out["success"] = "N";
        $json_out["authorized"] = "N";
        ErrorExit(1);
    }
}

////////////////////////////////////////////////////////////////
//                     CreateTransaction_double
//  Inserts entry into the 'transactions' table
//  

function CreateTransaction_double ($user, $staff, $device_id, $status_id) {
    global $json_out;
    global $mysqli;
    
    //Lower Role ID must be user
    if ($user->getRoleID() > $staff->getRoleID()){
        $temp = $user;
        $user = $staff;
        $staff = $temp;
    }
	
    $insert_result = mysqli_query($mysqli, "
      INSERT INTO transactions 
        (`operator`,`d_id`,`t_start`,`status_id`, `staff_id`) 
      VALUES
        ('".$user->getOperator()."', '$device_id', CURRENT_TIMESTAMP, '$status_id', '".$staff->getOperator()."');
    ");
    $mysqli_error = mysqli_error($mysqli);
    if ($mysqli_error) {
        $json_out["ERROR"] = $mysqli_error;
        ErrorExit(2);
    
    } else {
        $trans_id = mysqli_insert_id($mysqli);
        $json_out["trans_id"] = $trans_id;
        $json_out["status_id"] = $status_id;
        $json_out["authorized"] = "Y";
    }
    
    $json_out["trans_id"] = $trans_id;
   
    
    
}


function check_user_status( $operator ){
    global $json_out;

    $json_out["role"] = $operator->getRoleID();
    return $operator->getRoleID();
}

////////////////////////////////////////////////////////////////
//
//  RFIDtoUTAID
//  Matches RFID to a UTA ID
//  Users::RFIDtoOperator("1000000000");
function RFIDtoUTAID ($rfid_no) {
   global $json_out;
   global $mysqli;

    $user = Users::withRF($rfid_no);

    if (is_object($user)) {
        return($user);
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

    Error::insertError($_SERVER['PHP_SELF'], $json_out['ERROR'], "");
    echo json_encode($json_out);
    $mysqli->close();
    exit($exit_status);
}
?>