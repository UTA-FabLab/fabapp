<?php
 /*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/connections/db_connect8.php');
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/class/all_classes.php');

$isGranular;

if (!empty($_GET["val"])) {
    $value = $_GET["val"];


    if (strpos($value, 'DG') !== false) {
        sscanf($value, "DG_%d-%d", $dg_id, $d_id);		
        

	if ($result = $mysqli->query(" SELECT * FROM `device_group` WHERE `device_group`.`dg_id` = " . $dg_id ) ) {	//obtain granularity of device group and store it
				error_log("GETWAITQUEUEID.php line 20 dg_id obtained for queue device is: " . $dg_id);
				$row = $result->fetch_assoc();
				$isGranular = $row['granular_wait'];			
				error_log("GETWAITQUEUEID.php line 23 granularity value obtained is: " . $isGranular);
			}
			

        if ($dg_id !="" && $d_id >= 1 && DeviceGroup::regexDgID($dg_id)) {		//check up on validity of dg_id and d_id variable contents before doing anything else
            // Select all of the MAV IDs that are waiting for this device 
			
			if ($isGranular == 'Y' || $isGranular == 'y' ) {							//if device group is found to be granular, poll by device id
				$queryContent = "SELECT `Operator`, `Q_id`
					FROM `wait_queue`
					WHERE `Dev_id` = $d_id AND `Valid` = 'Y'
					ORDER BY `Q_id` ASC
					LIMIT 1";
				$result = $mysqli->query ( $queryContent);

				while($row = mysqli_fetch_array($result)) {
					$op_id = substr($row["Operator"], -4);
					echo "******".$op_id;
				}
			}
			else {									//this should only be reached by non-granular-queue devices
				$queryContent = "SELECT `Operator`, `Q_id`
				FROM `wait_queue`
                WHERE `Devgr_id` = '$dg_id' AND `Valid` = 'Y'
                ORDER BY `Q_id` ASC
                LIMIT 1";
				$result = $mysqli->query($queryContent );                	//if device group is non-granular, poll by device group 
				
				while($row = mysqli_fetch_array($result) ) {
					$op_id = substr($row["Operator"], -4);
					echo "******".$op_id;
				}
			}
        
        } 
		
		
		
         
    }
}

?>