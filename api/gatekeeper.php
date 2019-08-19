<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/class/Users.php');

///////////////////////////////////////////////////////////////
//
//   Gatekeeper
//   Checks to see if ID is authorized to use a FabLab device
//

function gatekeeper ($operator, $d_id) {
    global $mysqli, $status, $sv;
    
    if (is_a($operator, "Users")){
        $user = $operator;
    } else {
        $user = Users::withID($operator);
    }

    // Check to see if ID is a 10-digit number
    if (!is_object($user)) {
        return array ("status_id" => 1, "ERROR" => "Bad ID", "authorized" => "N");
    }
    
    // Check to see if device ID is a valid any-digit number
    if (!Devices::regexDeviceID($d_id)) {
        return array ("status_id" => 1, "ERROR" => "Invalid or missing device id value - $d_id", "authorized" => "N");
    } else {
        $device = new Devices($d_id);
    }
    
    //Prevent new Ticket if previous ticket has not been closed
    if( $result = $mysqli->query("
        SELECT * 
        FROM `transactions`
        LEFT JOIN devices
        ON devices.d_id = transactions.d_id
        WHERE devices.d_id = $d_id AND status_id < $status[total_fail]
        LIMIT 1;
    ")){
        if( $result->num_rows > 0){
            $row = $result->fetch_assoc();
            return array ("status_id" => 1, "ERROR" => "Can not start new Ticket $row[trans_id] on this device until previous ticket has been closed.", "authorized" => "N");
        }
    } else {
        return array ("status_id" => 0, "ERROR" => "gk".$mysqli->error, "authorized" => "N");
    }
    
    //Deny Print if they have prints to pickup
    if ($result = $mysqli->query("
        Select `objbox`.`trans_id`, `objbox`.`o_start`, `device_group`.`dg_parent`
        FROM `objbox`
        JOIN `transactions` ON `transactions`.`trans_id` = `objbox`.`trans_id`
        JOIN `devices` ON `transactions`.`d_id` = `devices`.`d_id`
        JOIN `device_group` ON `device_group`.`dg_id` = `devices`.`dg_id`
        WHERE `transactions`.`operator` = '".$user->getOperator()."' AND `o_end` IS NULL
    ")){
        if($result->num_rows > 0){
            //Current Time
            $now = new DateTime();
            while($row = $result->fetch_array()){
                //Deny if Object in storage is from the same Device Group
                //Deny if Object in storage is older than maxHold
                $o_start = new DateTime($row['o_start']);
                $o_start->add(new DateInterval("P".$sv['maxHold']."D"));
                
                if(($device->getDg()->getDg_parent() == $row['dg_parent']) || ($now > $o_start)){
                    return array ("status_id" => 1, "ERROR" => "Please Pay for Your Previous 3D Print. See Ticket: ".$row['trans_id'],  "authorized" => "N");
                }
            }
        }
    } else {
        return array ("status_id" => 0, "ERROR" => "gk".$mysqli->error, "authorized" => "N");
    }
    ////////////////////////////////////////////////////////
    //
    //  Deny if membership < strtotime("now")
    //
    
    ////////////////////////////////////////////////////////
    //
    //  Deny if User has more than one device powered on
    //
    
    ////////////////////////////////////////////////////////
    //
    //  Deny if Device has a Service Ticket
    //
    if($result = $mysqli->query("
        SELECT *
        FROM `service_call`
        WHERE `d_id` = $d_id AND `solved` = 'N' AND `sl_id` >=7;
    ")){
        if ($result->num_rows > 0){
            if ($user->getRoleID() == $sv['serviceTechnican'] || $user->getRoleID() >= $sv['staffTechnican']){
                //No Problems Keep Working
            } else {
                //role id != 7 or r_id < 10
                return array ("status_id" => 1, "ERROR" => "This device is Out of Service",  "authorized" => "N", "role" => $user->getRoleID());
            }
        }
    }
    
    
    ////////////////////////////////////////////////////////
    //
    //   User has an outstanding charge
    //
    $ac_owed = Acct_charge::checkOutstanding($user->getOperator());
    if (is_array($ac_owed) && sizeof($ac_owed) > 0){
        $msg = "Over due balance for Ticket :";
        foreach(array_keys($ac_owed) as $aco_key){
            $msg = $msg." ".$aco_key." &";
        }
        return array ("status_id" => 1, "ERROR" => rtrim($msg, "&"),  "authorized" => "N");
    }
    
    
    ////////////////////////////////////////////////////////
    //
    //   Check to see if device has training modules
    //
    $training = array();
    if ($results = $mysqli->query("
        SELECT `trainingmodule`.`tm_id`, `trainingmodule`.`title`
        FROM `trainingmodule`
        LEFT JOIN `devices`
        ON `trainingmodule`.`d_id` = `devices`.`d_id`
        where devices.d_id = '$d_id' AND `tm_required` = 'Y'
        UNION
        SELECT `trainingmodule`.`tm_id`, `trainingmodule`.`title`
        FROM `trainingmodule`
        LEFT JOIN `devices`
        ON `trainingmodule`.`dg_id` = `devices`.`dg_id`
        WHERE `devices`.`d_id` = '$d_id' AND `tm_required` = 'Y'
        UNION
        SELECT `trainingmodule`.`tm_id`, `trainingmodule`.`title`
        FROM `trainingmodule`
        LEFT JOIN `device_group`
        ON `trainingmodule`.`dg_id` = `device_group`.`dg_parent`
        LEFT JOIN `devices`
        ON `device_group`.`dg_id` = `devices`.`dg_id`
        WHERE `devices`.`d_id` = '$d_id' AND `tm_required` = 'Y';
    ")){
        while( $row = $results->fetch_assoc() ) {
            $training[$row["tm_id"]] = $row["title"];
        }
        $results->close();
    } else {
        return array ("status_id" => 0, "ERROR" => $mysqli->error, "authorized" => "N");
    }

    if ( count($training) == 0) {
        // If device has no training modules, no need to check further
        // so exit the function.
        //echo "No Training Required";
        return array ("status_id" => $status["active"], "authorized" => "Y");
    }

    // Check if user has completed the necessary training modules
    $count = 0;
    //foreach($training as $t){
    foreach ($training as $tm_id => $title){
        if ($results = $mysqli->query("
            SELECT *
            FROM `tm_enroll`
            WHERE `tm_enroll`.`tm_id` = '$tm_id' AND `operator` = '".$user->getOperator()."'
        ")){
            if( $results->num_rows == 1 ) {
                $count++;
            } else {
                return array ("status_id" => 2, "ERROR" => "ID: ".$user->getOperator()." Needs ".$title, "authorized" => "N");
            }
        } else {
            return array ("status_id" => 0, "ERROR" => $mysqli->error, "authorized" => "N");
        }
    }
	
    if ($count == count($training)){
        //echo "authorized";
        return array ("status_id" => 10, "authorized" => "Y");
    } else {
        return array ("status_id" => 0, "ERROR" => "Gatekeeper : No Resolution", "authorized" => "N");
    }
}
?>