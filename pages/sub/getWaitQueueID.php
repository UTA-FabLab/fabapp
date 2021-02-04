<?php
 /*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/connections/db_connect8.php');
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/class/all_classes.php');



if (!empty($_GET["val"])) {
    $value = $_GET["val"];

    if (strpos($value, 'DG') !== false) {
        sscanf($value, "DG_%d-%d", $dg_id, $d_id);
        
        $polyprinters="2";

        if ($dg_id !="" && $dg_id != $polyprinters && DeviceGroup::regexDgID($dg_id)) {
            // Select all of the MAV IDs that are waiting for this device group
            $result = $mysqli->query ( "               
                SELECT `Operator`, `Q_id`
                FROM `wait_queue`
                WHERE `Dev_id` = $d_id AND `Valid` = 'Y'
                ORDER BY `Q_id` ASC
                LIMIT 1
            " );
            while($row = mysqli_fetch_array($result)) {
                $op_id = substr($row["Operator"], -4);
                echo "******".$op_id;
            }
        
        } 
        if ($dg_id !="" && $dg_id == $polyprinters && DeviceGroup::regexDgID($dg_id)) {
            // Select all of the MAV IDs that are waiting for this device group
            $result = $mysqli->query ( "                
                SELECT `Operator`, `Q_id`
                FROM `wait_queue`
                WHERE `Devgr_id` = $dg_id AND `Valid` = 'Y'
                ORDER BY `Q_id` ASC
                LIMIT 1
            " );
    
            while($row = mysqli_fetch_array($result)) {
                $op_id = substr($row["Operator"], -4);
                echo "******".$op_id;
            }
        }
    }
}

?>