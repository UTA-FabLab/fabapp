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
        
//        $polyprinters="2";		//remnant from bad hardcoding
//For some reason Boss Laser on Test (DG_4-35) causes a glitch where it runs both IF statements, any other number does not trigger this - need to look into it
	if ($result = $mysqli->query(" SELECT * FROM `device_group` WHERE `device_group`.`dg_id` = " . $dg_id ) ) {	//obtain granularity of device group and store it
			
				$row = $result->fetch_assoc();
				$isGranular = $row['granular_wait'];			
			}
			

        if ($dg_id !="" && $d_id >= 1 && DeviceGroup::regexDgID($dg_id)) {		//check up on validity of dg_id and d_id variable contents
            // Select all of the MAV IDs that are waiting for this device 
			if ($isGranular == 'Y') {							//this should house the granular queue logic
				$result = $mysqli->query ( "               
					SELECT `Operator`, `Q_id`
					FROM `wait_queue`
					WHERE `Dev_id` = $d_id AND `Valid` = 'Y'
					ORDER BY `Q_id` ASC
					LIMIT 1
				" );
				while($row = mysqli_fetch_array($result)) {
					error_log("This is the granular queue logic outputting row : " . print_r($row, true) );
					$op_id = substr($row["Operator"], -4);
					echo "******".$op_id;
				}
			}
			else {									//this should only be reached by non-granular-queue devices
				$result = $mysqli->query ( "                
                SELECT `Operator`, `Q_id`
                FROM `wait_queue`
                WHERE `Devgr_id` = $dg_id AND `Valid` = 'Y'
                ORDER BY `Q_id` ASC
                LIMIT 1
				" );
    
				while($row = mysqli_fetch_array($result)) {
					error_log("This is the non-granular queue logic outputting row: " . print_r($row, true) );
					$op_id = substr($row["Operator"], -4);
					echo "******".$op_id;
				}
			}
        
        } 
		//DG4_35 =  boss laser, still causing a bug
/*		if ($dg_id !="" && $dg_id >= 1 && DeviceGroup::regexDgID($dg_id)) {			//this should be the non-granular queue logic  
            // Select all of the MAV IDs that are waiting for this device group
			error_log("non-granular logic, d_id and dg_id are:  " . $d_id . "  and " . $dg_id);
			
            
        }
*/
		
		
         
    }
}

?>