<?php

/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 */

/**
 * Description of TrainingModule
 *
 * @author Jon Le
 */
class TrainingModule {
	private $tm_id;
	private $title;
	private $tm_desc;
	private $duration;
	private $d_id;
	private $dg_id;
	private $tm_required;
	private $file_name;
	private $file_bin;
	private $class_size;
	private $tm_stamp;

	public function __construct($tm_id){
		global $mysqli;

		if (!preg_match("/^\d+$/", $tm_id)) {
			$this->msg = "Invalid Training Module ID";
		} elseif ($result = $mysqli->query("
			SELECT *
			FROM trainingmodule
			WHERE tm_id = $tm_id
			LIMIT 1;
		")){
			$row = $result->fetch_assoc();

			$this->setTm_desc($row['tm_desc']);
			$this->setClass_size($row['class_size']);
			$this->setD_id($row['d_id']);
			$this->setDg_id($row['dg_id']);
			$this->setDuration($row['duration']);
			$this->setFile_name($row['file_name']);
			$this->setFile_bin($row['file_bin']);
			$this->setTitle($row['title']);
			$this->setTm_id($row['tm_id']);
			$this->setTm_required($row['tm_required']);
			$this->setTm_stamp($row['tm_stamp']);
		}
	}

	public static function get_all_certificates() {
		global $mysqli;

		if($result = $mysqli->query("SELECT * FROM `tm_enroll`
									 LEFT JOIN `trainingmodule`
									 ON `tm_enroll`.`tm_id` = `trainingmodule`.`tm_id`
									 ORDER BY `tme_key` DESC;
			")) {
			while($row = $result->fetch_assoc()) {
				$trainings[] = $row;
			}
			return $trainings;
		}
	}

	
	public function certify_training($operator, $staff){
		global $mysqli;
		global $sv;
		
		if (!Users::regexUser($operator)) return "Invalid Operator ID";

		if ($sv['LvlOfLead'] > $staff->getRoleID()){
			return ("Staff Member Lacks Authority to Issue Certificate");
		}
	
		//verify if trainer has been trained or is admin
		if ($results = $mysqli->query("
			SELECT *
			FROM `tm_enroll`
			WHERE tm_id = $this->tm_id AND operator = ".$staff->getOperator()." AND `current` = 'Y'
		")){
			if( $results->num_rows == 0 && $staff->getRoleID() < $sv['LvlOfLead']) {
				//True when they have the related training
				//Or when they are Admin
				return "Staff Member Lacks Training";
			}
			else {
			}
		}
		else {
			return "Error with submission criteria";
		}
		
		if ($mysqli->query("
			INSERT INTO `tm_enroll` 
				(`tm_id`, `operator`, `completed`, `staff_id`, `current`) 
			VALUES
				('$this->tm_id', '$operator', CURRENT_TIME(), '".$staff->getOperator()."', 'Y');
		")){
			return true;
		} elseif ( strpos($mysqli->error, "Duplicate") === 0) {
			return "$this->title Certificate has already been issued to this person.";
		} else {
			return $mysqli->error;
		}
	}

	//Edit an exisiting Training Module
	public static function editTM($tm_id, $title, $tm_desc, $duration, $d_id, $dg_id, $tm_required, $class_size, $staff) {
		global $mysqli;
		global $sv;

		if (!self::regexTMId($tm_id)) return "Invalid Training Module ID: $tm_id";
		if ( (Devices::regexDID($d_id) || DeviceGroup::regexDgID($dg_id)) && Devices::regexDID($d_id) != DeviceGroup::regexDgID($dg_id)){} else {return "Bad Device or Group: d-$d_id dg-$dg_id";}
		if (!self::regexSize($class_size)) return "Invalid Class Size";
		if (!self::regexTime($duration)) {return "Bad Time - $duration";}
		if (!self::regexTmReq($tm_required)) {return "Select Requirement";}
		if ($staff->getRoleID() < $sv['minRoleTrainer']) {return "Staff Member Is Unable To Edit Training Modules";}

		if($d_id){
			$mysqli->autocommit(FALSE);
			if ($stmt = $mysqli->prepare("
				UPDATE trainingmodule
				SET `title`= ?, `tm_desc` = ?,`duration`= ?,
					`d_id`=?, `dg_id`= NULL, `tm_required`=?, `class_size`=?, `tm_stamp` = CURRENT_TIMESTAMP
				WHERE `tm_id` = ?;
			")){
				$stmt->bind_param("sssisii", $title, $tm_desc, $duration, $d_id, $tm_required, $class_size, $tm_id);
				if ($stmt->execute() === true ){
					$row = $stmt->affected_rows;
					//Success, only one row was updated
					if ($row == 1){
						$mysqli->commit();
						return $row;
					//Error More then one row was affected
					} elseif ($row > 1) {
						$mysqli->rollback();
					}
				} else
					return "TM WriteAttr Error - ".$stmt->error;
			} else {
				return "Error in preparing traningmodule: WriteAttr statement";
			}
		} elseif ($dg_id){
			if ($stmt = $mysqli->prepare("
				UPDATE trainingmodule
				SET `title`= ?, `tm_desc` = ?,`duration`= ?,
					 `d_id`= NULL, `dg_id`=?, `tm_required`=?, `class_size`=?
				WHERE `tm_id` = ?;
			")){
				$stmt->bind_param("sssisii", $title, $tm_desc, $duration, $dg_id, $tm_required, $class_size, $tm_id);
				if ($stmt->execute() === true ){
					$row = $stmt->affected_rows;
					$stmt->close();
					return $row;
				} else
					echo "<br>TM WriteAttr Error - ".$stmt->error;
			} else {
				echo "Error in preparing traningmodule: WriteAttr statement";
			}
		}
	}


	public static function get_certificates_of_training($tm_id) {
		if(!preg_match('/^\d+$/', $tm_id)){
			return false;
		}

		global $mysqli;
		if($result = $mysqli->query("SELECT * FROM `tm_enroll`
									 LEFT JOIN `trainingmodule`
									 ON `tm_enroll`.`tm_id` = `trainingmodule`.`tm_id`
									 WHERE `tm_enroll`.`tm_id` = '$tm_id'
									 ORDER BY `tme_key` DESC;"
		)) {
			while($row = $result->fetch_array(MYSQLI_ASSOC)) {
				$trainings[] = $row;
			}
			return $trainings;
		}
		return false;
	}

	//Returns String if Error
	//Returns Int if properly inserted
	public static function insertTM($title, $tm_desc, $duration, $d_id, $dg_id, $tm_required, $class_size, $staff) {
		global $mysqli;
		global $sv;

		if ( (Devices::regexDID($d_id) || DeviceGroup::regexDgID($dg_id)) && Devices::regexDID($d_id) != DeviceGroup::regexDgID($dg_id)){} else {return "Bad Device or Group: d-$d_id dg-$dg_id";}
		if (!self::regexSize($class_size)) return "Invalid Class Size";
		if (!self::regexTime($duration)) {return "Bad Time - $duration";}
		if (!self::regexTmReq($tm_required)) {return "Select Requirement";}
		if ($staff->getRoleID() < $sv['minRoleTrainer']) {return "Staff Member Is Unable To Add Training Modules";}

		if($d_id){
			if ($mysqli->query("
				INSERT INTO trainingmodule 
					(`title`, `tm_desc`, `duration`, `d_id`, `tm_required`, `class_size`, `tm_stamp`)
				VALUES
					('$title', '$tm_desc', '$duration', '$d_id', '$tm_required', '$class_size', CURRENT_TIMESTAMP);
			")){
				return $mysqli->insert_id;
			} else {
				return $mysqli->error;
			}
		}
		elseif ($dg_id){
			if ($mysqli->query("
				INSERT INTO trainingmodule 
					(`title`, `tm_desc`, `duration`, `dg_id`, `tm_required`, `class_size`, `tm_stamp`)
				VALUES
					('$title', '$tm_desc', '$duration', '$dg_id', '$tm_required', '$class_size', CURRENT_TIMESTAMP);
			")){
				return $mysqli->insert_id;
			} else {
				return $mysqli->error;
			}
		}
	}

	public static function regexSize($class_size){
		if (preg_match("/^\d+$/", $class_size) == 0 && $class_size > 0)
				return false;
		return true;
	}

	public static function regexTime($duration) {
		if ( preg_match("/^\d{1,3}:\d{2}:\d{2}$/", $duration) == 1 )
			return true;
		return false;
	}

	public static function regexTMId($tm_id){
		global $mysqli;
		if (preg_match("/^\d+$/", $tm_id) == 0)
			{return false;}

		//Check to see if device exists
		if ($result = $mysqli->query("
			SELECT *
			FROM `trainingmodule`
			WHERE `tm_id` = '$tm_id';
		")){
			if ($result->num_rows == 1)
				{return true;}
			return false;
		} else {
			return false;
		}
	}
	public static function regexTMReq($tm_required) {
		if ( preg_match("/^[YN]{1}$/", $tm_required) == 1)
			return true;
		return false;
	}

	public function getClass_size() {
		return $this->class_size;
	}

	public function getD_id() {
		return $this->d_id;
	}

	public function getDg_id() {
		return $this->dg_id;
	}

	public function getDuration() {
		return $this->duration;
	}

	public function getFile_name() {
		return $this->file_name;
	}

	public function getFile_bin() {
		return $this->file_bin;
	}

	public function getTitle() {
		return $this->title;
	}

	public function getTm_desc() {
		return $this->tm_desc;
	}

	public function getTm_id() {
		return $this->tm_id;
	}

	public function getTm_required() {
		return $this->tm_required;
	}

	public function getTm_stamp() {
		return $this->tm_stamp;
	}

	public function setClass_size($class_size){
		$this->class_size = $class_size;
	}

	public function setD_id($d_id){
		$this->d_id = $d_id;
	}

	public function setDg_id($dg_id){
		$this->dg_id = $dg_id;
	}

	public function setDuration($duration){
		$this->duration = $duration;
	}

	public function setFile_name($file_name){
		$this->file_name = $file_name;
	}

	public function setFile_bin($file_bin){
		$this->file_bin = $file_bin;
	}

	public function setTitle($title){
		$this->title = $title;
	}

	public function setTm_desc($tm_desc){
		$this->tm_desc = $tm_desc;
	}

	public function setTm_id($tm_id){
		$this->tm_id = $tm_id;
	}

	public function setTm_required($tm_required){
		$this->tm_required = $tm_required;
	}

	public function setTm_stamp ($tm_stamp){
		$this->tm_stamp = $tm_stamp;
	}
}