<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 * 
 */
 
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/connections/db_connect8.php');
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/class/all_classes.php');



if (!empty($_GET["val"])) {
    $value = $_GET["val"];

        sscanf($value, "%d", $dg_id);

        $polyprinters="2";

        if ($dg_id == ""){
            echo "<option selected disabled hidden>Invalid Device Group</option>";
            
        } elseif ($dg_id != $polyprinters && DeviceGroup::regexDgID($dg_id)) {
            // Select all of the MAV IDs that are waiting for this device group
            $result = $mysqli->query ( "
                SELECT DISTINCT D.`device_desc`, D.`d_id`
                FROM `devices` D, `device_group` DG, `transactions` T , `wait_queue` WQ
                WHERE (D.`dg_id`=$dg_id AND D.`dg_id`=DG.`dg_id` AND T.`d_id`=D.`d_id` AND T.`status_id` <= 11 AND DG.`granular_wait`='Y' AND D.`d_id` NOT IN (
                    SELECT `d_id`
                    FROM `service_call`
                    WHERE `solved` = 'N' AND `sl_id` >= 7
                )) OR D.`d_id` IN (
                    SELECT WQ.`Dev_id`
                    FROM `wait_queue` WQ, `devices` D, `device_group` DG
                    WHERE D.`dg_id`=$dg_id AND WQ.`Dev_id`=D.`d_id` AND DG.`granular_wait`='Y' AND WQ.`valid` = 'Y' 
                )
                ORDER BY D.`device_desc`
            " );
    
            while($row = mysqli_fetch_array($result)) {
                echo '<option value="'.$row["d_id"].'">'; echo $row["device_desc"]; echo "</option>";
            }
            if (mysqli_num_rows($result)==0) {
                echo ("<script>document.getElementById(\"deviceList\").disabled = true;</script>");
                echo "<option disabled selected>"; echo "A Device Is Available To Use"; echo "</option>";
            }
            
        
        } elseif ($dg_id == $polyprinters && DeviceGroup::regexDgID($dg_id)) {
            $result = $mysqli->query ( "
                SELECT DISTINCT D.`device_desc`, D.`d_id`
                FROM `devices` D, `device_group` DG, `transactions` T , `wait_queue` WQ
                WHERE (D.`dg_id`=$dg_id AND D.`dg_id`=DG.`dg_id` AND T.`d_id`=D.`d_id` AND T.`status_id` <= 11 AND DG.`granular_wait`='N' AND D.`d_id` NOT IN (
                    SELECT `d_id`
                    FROM `service_call`
                    WHERE `solved` = 'N' AND `sl_id` >= 7
                )) OR D.`d_id` IN (
                    SELECT WQ.`Dev_id`
                    FROM `wait_queue` WQ, `devices` D, `device_group` DG
                    WHERE D.`dg_id`=$dg_id AND WQ.`Dev_id`=D.`d_id` AND DG.`granular_wait`='N' AND WQ.`valid` = 'Y' 
                )
                ORDER BY D.`device_desc`
            " );
            
            if (mysqli_num_rows($result)==0) {
                echo "<option disabled selected>"; echo "A Printer Is Available To Use"; echo "</option>";  
            } else {
                echo "<option value=''>"; echo "No Selection Needed"; echo "</option>";
            }
        } else {
            echo "<option selected disabled hidden>Query Error</option>";
        }
    
} ?>