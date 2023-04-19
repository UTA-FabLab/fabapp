<?php
class OfflineTrans {
    public static function haveOfflineTrans(){
        global $mysqli, $status;
        if ($result = $mysqli->query("
        SELECT *
        FROM `transactions`
        WHERE `status_id` = '$status[offline]';
        "))
        if ($result->num_rows > 0){
            return true;
        }
        return false;
    }
    
    public static function byOffTransId($off_trans_id){
        global $mysqli;
        if ($result = $mysqli->query("
            SELECT *
            FROM `offline_transactions`
            WHERE `off_trans_id` = '$off_trans_id'
            LIMIT 1;
        "))
            $row = $result->fetch_assoc();
            $trans_id = $row['trans_id'];
            return $trans_id;
    }

    public static function byTransId($trans_id){
		global $mysqli; 
        if ($result = $mysqli->query("
            SELECT *
            FROM `offline_transactions`
            WHERE `trans_id` = '$trans_id'
            LIMIT 1;
        ")){
			$row = $result->fetch_assoc();
						
			if(is_null($row) ){
				$off_trans_id = null;
				return $off_trans_id;
			} else {
				
				if($row['off_trans_id'] != "") {
					$off_trans_id = $row['off_trans_id'];
					return $off_trans_id;
				}
				else {
					error_log("Line 46 OfflineTrans.php the value of row<off_trans_id> from SQL query looks to be an empty string, returning null value. This probably shouldnt be happening.");
					return null;
				}
			}
		
        } 
			else {
				error_log("Line 55 in OfflineTrans.php, SQL query attempt itself unsuccessful, this shouldn't be reachable");
				return "No SQL call completion, check OfflineTrans class, this shouldn't be reachable";
			}
	}	
				
		
			
            
		
}