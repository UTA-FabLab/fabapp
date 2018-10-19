<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Cache-Control, Origin, X-Requested-With, Content-Type, Accept, Key, X-Api-Key, Authorization");
/*
 *  CC BY-NC-AS UTA FabLab 2016-2018
 * 
 *  Suleiman Barakat & Jon Le
 *  FabLab @ University of Texas at Arlington
 *  version: 0.91
 */
require_once ($_SERVER['DOCUMENT_ROOT']."/connections/db_connect8.php");
include_once ($_SERVER['DOCUMENT_ROOT'].'/class/all_classes.php');
include_once 'gatekeeper.php';



// RFID to Role ID
//$test_input = array("type" => "check_status_rfid", "number" => "1415435517", "device" => "0021");
//Test Turn On Using Operator IDs
//$test_input = array("type" => "utaid_double", "device" => "0007", "number" => "1000000010", "number_employee" => "1000000010"))
//Test End
//$test_input = array("type" => "end_transaction", "device" => "0007"))

//Compare Header API Key with site variable's API Key
$headers = apache_request_headers();
if ($sv['api_key'] == "") {
    $json_out["ERROR"] = "API Not Set, Unable to authenticate Device";
    //ErrorExit(1);
} elseif(isset($headers['authorization'])){
    if ($sv['api_key'] != $headers['authorization'] ){
        $json_out["ERROR"] = "Unable to authenticate Device";
        ErrorExit(1);
    }
} elseif(isset($headers['Authorization'])){
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

// Extract message type from incoming JSON
if ( isset($input_data["type"]) ){
    $type = $input_data["type"];
    $json_out = array();
} else {
    $json_out["ERROR"] = "Type of call not set";
    ErrorExit(1);
}

if (strtolower($type) == "utaid_double"){
    // added this part to support user + learner transaction with utaid
    $user = Users::withID($input_data["number"]);
    $staff = Users::withID($input_data["number_employee"]);
    if(array_key_exists("dev_id", $input_data)){
        $device_id = $input_data["dev_id"];
    } elseif(array_key_exists("device_id", $input_data)) {
        $device_id = $input_data["device_id"];
    } else {
        $device_id = $input_data["device"];
    }
    OnTransaction_double($user, $staff, $device_id);

} elseif(strtolower($type) == "rfid_double"){
    // added this part to support user + learner transac 
    $user = RFIDtoUTAID($input_data["number"]);
    $staff = RFIDtoUTAID($input_data["number_employee"]);
    if(array_key_exists("dev_id", $input_data)){
        $device_id = $input_data["dev_id"];
    } elseif(array_key_exists("device_id", $input_data)) {
        $device_id = $input_data["device_id"];
    } else {
        $device_id = $input_data["device"];
    }
    
    OnTransaction_double($user, $staff, $device_id);

} elseif(strtolower($type) == "check_status_utaid") {
    check_user_status( Users::withID($input_data["number"]) );
	
} elseif(strtolower($type) == "check_status_rfid") {
    check_user_status( RFIDtoUTAID($input_data["number"]) );

} elseif( strtolower($type) == "end_transaction" ) {
    if(array_key_exists("dev_id", $input_data)){
        end_transaction( $input_data["dev_id"] );
    } elseif(array_key_exists("device_id", $input_data)) {
        end_transaction( $input_data["device_id"] );
    } else {
        end_transaction( $input_data["device"] );
    }
    
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
    //pass User to check if authorized through Gatekeeper
    foreach (gatekeeper($user, $device_id) as $key => $value){
        $json_out[$key] =  $value;
    }
    if ($json_out["authorized"] == "N"){
        $json_out["ID"] = $user->getOperator();
        ErrorExit(1);
    }
    $status_id = $json_out["status_id"];
    
    //pass Staff to check if authorized through Gatekeeper
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
    
    //Deny if they are not the next person in line to use this device
    $msg = Wait_queue::transferFromWaitQueue($user->getOperator(), $device_id);
    if (is_string($msg)){
        $json_out["ERROR"] = $msg;
        return;
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
}

////////////////////////////////////////////////////////////////
//
//  Status Check to test conenction and send to JuiceBox
//  Currently JuiceBox does not do anything with this information.
//
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
    global $sv;
    
    if ($stmt = $mysqli->prepare("
        UPDATE `site_variables` 
        SET `value` = ?
        WHERE `site_variables`.`name` = 'lastRfid';
    ")){
        $stmt->bind_param("s", $rfid_no);
        $stmt->execute();
        $insID = $stmt->insert_id;
        $stmt->close();
    } else {
        Error::insertError($_SERVER['PHP_SELF'],  $mysqli->error, "");
    }
    
    $user = Users::withRF($rfid_no);
    if (is_object($user)) {
        return($user);
    } else {
        $json_out["ERROR"] = $user;
        ErrorExit(1);
    }
}


function end_transaction( $d_id ){
    global $mysqli;
    global $json_out;

	// Check for deviceID value
    if (! (preg_match("/^\d*$/", $d_id))) {
        $json_out["ERROR"] = "Invalid device number";
        Error::insertError($_SERVER['PHP_SELF'],  $json_out["ERROR"], "");
        ErrorExit(1);
    }
    
    //Remove Leading Zeros
    $d_id = ltrim($d_id, '0');
    
	if ($result = $mysqli->query("
		SELECT *
		FROM `transactions`
		WHERE `d_id` = '$d_id' AND `t_end` is NULL
	")){
            $row = $result->fetch_assoc();
            $ticket = new Transactions($row['trans_id']);
	}
	
    $msg = $ticket->end_juicebox();

    if ($msg === true){
        $json_out["CONTENT"] = "Ticket ".$ticket->getTrans_id()." has been closed";
        $json_out["success"] = "Y";
    } else {
        $json_out["success"] = "N";
        Error::insertError($_SERVER['PHP_SELF'],  $msg, "");
        ErrorExit(1);
    }
}


function ErrorExit ($exit_status) {
    global $mysqli;
    global $json_out;
    global $input_data;
    
    if(isset($input_data["device"])){
        $device = new Devices($input_data['device']);
        Error::insertError("JuiceBox: ".$device->getDevice_desc(), $json_out['ERROR'], "");
    } else {
        Error::insertError("JuiceBox", $json_out['ERROR'], "");
    }
    echo json_encode($json_out);
    $mysqli->close();
    exit();
}
?>