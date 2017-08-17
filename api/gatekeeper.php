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
	

    // Check to see if ID is a 10-digit number
    if (!Users::regexUser($operator)) {
        return array ("status_id" => 1, "ERROR" => "Bad ID", "authorized" => "N");
    }
    
    // Check to see if device ID is a valid any-digit number
    if (preg_match("/^\d+$/",$d_id) == 0) {
        return array ("status_id" => 1, "ERROR" => "Invalid or missing device id value - $d_id", "authorized" => "N");
    }
    
    //Prevent new Ticket if previous ticket has not been closed
    if( $result = $mysqli->query("
        SELECT * 
        FROM `transactions`
        LEFT JOIN devices
        ON devices.d_id = transactions.d_id
        WHERE device_id = $d_id AND status_id < 12
        LIMIT 1;
    ")){
        if( $result->num_rows > 0){
            return array ("status_id" => 1, "ERROR" => "Can not start new print on this printer until previous ticket has been closed.", "authorized" => "N");
        }
    } else {
        return array ("status_id" => 0, "ERROR" => $mysqli->error, "authorized" => "N");
    }
    
    //Deny Print if they have prints to pickup
    if ($result = $mysqli->query("
            Select transactions.trans_id, address, mats_used.unit_used, mats_used.m_id, d_id
            FROM objbox JOIN transactions
            ON transactions.trans_id=objbox.trans_id
            LEFT JOIN mats_used
            ON transactions.trans_id = mats_used.trans_id
            WHERE transactions.operator = '$operator' AND o_end IS NULL
            ORDER BY t_start DESC
    ")){
        //if result is NULL Look at Auth Recipients Table
        $numRows = $result->num_rows;
        if($numRows > 0){
            $row = $result->fetch_array();
            return array ("status_id" => 1, "ERROR" => "Please Pay for Your Previous 3D Print. See Ticket: ".$row['trans_id'],  "authorized" => "N");
        }
    } else {
            return array ("status_id" => 0, "ERROR" => $mysqli->error, "authorized" => "N");
    }
    
    //if membership < date()
	
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
        return array ("status_id" => 2, "ERROR" => "Device: $d_id Requires Training", "authorized" => "N");
    } else {
        //echo "authorized";
        return array ("status_id" => 10, "authorized" => "Y");
    }
}
?>