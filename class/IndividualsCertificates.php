<?php 

/* Handles the processes used to assign, catalogue and revoke certificates of individuals in the FabLab. */

class IndividualsCertificates {
	private $certificates = array();


	public function __construct($id_number) {
		global $mysqli;

		if(!preg_match("/^\d+$/", $id_number)) {
			$this->$msg = "Training record not found";
		}
		elseif($result = $mysql->query(
			"SELECT * FROM 
			 `tm_enroll` 
			 WHERE `operator` = '".$id_number."';"
		)) {
			$this->setCertificates($results);
		}
	}


	public static function get_individuals_trainings($id_number) {
	    global $mysqli;
        $result = $mysqli->query("  SELECT * FROM `tm_enroll`
                                    LEFT JOIN `trainingmodule`
                                    ON `tm_enroll`.`tm_id` = `trainingmodule`.`tm_id`
                                    WHERE `operator` = '".$id_number."';");
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }


	private function setCertificates($results) {
		while($row = $results->fetch_array(MYSQLI_ASSOC)) {
			array_push($this->$certificates, new TrainingCertificate($row));
		}
	}


	public static function revoke_training($expiration, $reason, $staff_id, $tme_key) {
		global $mysqli;
		// regex $reason
		$prior_revoke_reason = $mysqli->query("SELECT `altered_notes` FROM `tm_enroll`
											   WHERE `tme_key` = ".$tme_key.";")->fetch_object()->altered_notes;
		if($prior_revoke_reason !== NULL) $reason = "PRIOR REASON: ".$prior_revoke_reason."\nCURRENT: ".$reason;

		if($mysqli->query("UPDATE `tm_enroll`
						   SET `expiration_date` = '".$expiration."', `altered_by` = '".$staff_id."', 
						   	   `altered_notes` = '".$reason."', `current` = 'N', `altered_date` = now()
						   WHERE `tme_key` = ".$tme_key.";")) 
		{
			return true;
		}
		return false;
	}


	public static function restore_training($staff_id, $tme_key) {
		global $mysqli;
		if($mysqli->query("UPDATE `tm_enroll`
						   SET `current` = 'Y', `altered_by` = '".$staff_id."'
						   WHERE `tme_key` = ".$tme_key.";")) 
		{
			return true;
		}
		return false;
	}

}

/* created by: MPZinke on 11.26.18 */ ?>