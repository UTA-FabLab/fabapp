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
    global $mysqli;
    global $sv;
    $user = Users::withID($operator);
	

    // Check to see if ID is a 10-digit number
    if (!$user) {
        return array ("status_id" => 1, "ERROR" => "Bad ID", "authorized" => "N");
    }
    
    // Check to see if device ID is a valid any-digit number
    if (!Devices::regexDID($d_id)) {
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
        WHERE devices.d_id = $d_id AND status_id < 12
        LIMIT 1;
    ")){
        if( $result->num_rows > 0){
            return array ("status_id" => 1, "ERROR" => "Can not start new ticket on this device until previous ticket has been closed.", "authorized" => "N");
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
        WHERE `transactions`.`operator` = '$operator' AND `o_end` IS NULL
    ")){
        if($result->num_rows > 0){
            //Current Time
            $now = new DateTime();
            while($row = $result->fetch_array()){
                //if 3D Printer, you must pay
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
    
    //if membership < date()
    
    //User has an outstanding charge
    $msg = Acct_charge::checkOutstanding($user->getOperator());
    if (is_string($msg)){
        return array ("status_id" => 1, "ERROR" => $msg,  "authorized" => "N");
    }
    
    // Check to see if device has training modules
    $training = array();
    if ($results = $mysqli->query("
        SELECT *
        FROM trainingmodule
        LEFT JOIN devices
        ON trainingmodule.d_id = devices.d_id
        where devices.d_id = '$d_id' AND tm_required = 'Y'
        UNION
        SELECT *
        FROM trainingmodule
        LEFT JOIN devices
        ON trainingmodule.dg_id = devices.dg_id
        WHERE devices.d_id = '$d_id' AND tm_required = 'Y';
    ")){
        while( $row = $results->fetch_assoc() ) {
            $training[] = $row["tm_id"];
        }
        $results->close();
    } else {
        return array ("status_id" => 0, "ERROR" => $mysqli->error, "authorized" => "N");
    }

    if ( count($training) == 0) {
        // If device has no training modules, no need to check further
        // so exit the function.
        //echo "No Training Required";
        return array ("status_id" => 10, "authorized" => "Y");
    }

    // Check if user has completed the necessary training modules
    $count = 0;
    foreach($training as $t){
        if ($results = $mysqli->query("
            SELECT *
            FROM `tm_enroll`
            WHERE tm_id = $t AND operator = $operator
        ")){
            if( $results->num_rows == 1 ) {
                $count++;
            }
        } else {
            return array ("status_id" => 0, "ERROR" => $mysqli->error, "authorized" => "N");
        }
    }
	
    if ($count != count($training)){
        //echo "Device: ".$device_id." requires training"."<br />";
        //echo "Enroll - ". $count ." Class - ". count($training) ."<br />";
        return array ("status_id" => 2, "ERROR" => $device->getDevice_desc()." Requires Training", "authorized" => "N");
    } else {
        //echo "authorized";
        return array ("status_id" => 10, "authorized" => "Y");
    }
}
?>