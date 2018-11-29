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


	public static function revoke_training($expiration, $reason, $staff, $tme_key) {
		global $sv;
		global $mysqli;

		if($sv['minRoleTrainer'] < $staff->getRoleID()) return false;
		$tme_key = preg_match( '/^\d+$/', $tme_key);
 		$reason = htmlspecialchars($reason);

		// update reason if reason; check if primary key exists
		$prior_revoke_reason = $mysqli->query("SELECT `altered_notes` FROM `tm_enroll`
											   WHERE `tme_key` = ".$tme_key.";");
		if(!$prior_revoke_reason) return false;
		$prior_revoke_reason = $prior_revoke_reason->fetch_object()->altered_notes;
		if($prior_revoke_reason !== NULL || $prior_revoke_reason != "") $reason = $prior_revoke_reason."\n".$reason;


		if ($stmt = $mysqli->prepare(" UPDATE `tm_enroll`
					                   SET `altered_by` = ?, `altered_date` = now(), `altered_notes`= ?, `current` = 'N', `expiration_date`= ?
					                   WHERE `tme_key` = ?;"))
		{
	        $stmt->bind_param("sssi", $staff->getOperator(), $reason, $expiration, $tme_key);
	        if ($stmt->execute() === true ){
	            $row = $stmt->affected_rows;
	            //Success, only one row was updated
	            if ($row == 1){
	                $mysqli->commit();
	                return true;
	            //Error More then one row was affected
	            } elseif ($row > 1) {
	                $mysqli->rollback();
	            }
	        }
	    }
		return false;
	}


	public static function restore_training($staff_id, $tme_key) {
		global $mysqli;

		$tme_key = preg_match( '/^\d+$/', $tme_key);
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