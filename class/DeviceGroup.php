<?php

/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 */

/**
 * Description of DeviceGroup
 *
 * @author Jon Le
 */
class DeviceGroup {
    //put your code here
    
    public static function regexDgID($dg_id){
		global $mysqli;
		
		if (preg_match("/^\d+$/", $dg_id) == 0){
			//echo "Invalid Device Group.";
			return false;
		}

		//Check to see if device exists
		if ($result = $mysqli->query("
			SELECT *
			FROM `device_group`
			WHERE `dg_id` = '$dg_id'
			LIMIT 1;
		")){
			if ($result->num_rows == 1)
				return true;
			return false;
		} else {
			return false;
		}
    }
}
