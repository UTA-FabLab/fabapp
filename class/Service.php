<?php
/*
 *   Jon Le 2016-2018
 *   FabApp V 0.91
 */


class Service_call {
	private $sc_id;
	private $solved;
	private $sc_notes;
	private $sc_time;
	//Objects
	private $device;
	private $staff;
	private $sl;
	private $sr;
	
	public function __construct($sc_id) {
		global $mysqli;
		$this->sc_id = $sc_id;
		
		if ($result = $mysqli->query("
			 SELECT *
			 FROM `service_call`
			 WHERE `sc_id` = '$sc_id';
		")){
			$row = $result->fetch_assoc();
			$this->setDevice($row['device_id']);
			$this->setSc_id($sc_id);
			$this->setSc_notes($row['sc_notes']);
			$this->setSc_time($row['sc_time']);
			$this->setSl($row['sl_id']);
			$this->setSolved($row['solved']);
			$this->setSr($sc_id);
			$this->setStaff($row['staff_id']);
			$result->close();
		} else
			throw new Exception("Invalid Service Call ID");
	}
	
	public static function byDevice($device){
		global $mysqli;
		
		if ($result = $mysqli->query("
			SELECT `service_call`.`sc_id`, `staff_id`, `service_call`.`device_id`, `sl_id`, `sc_time`, `sc_notes`, `solved`, `device_name`
			FROM `service_call`
			LEFT JOIN `devices`
			ON `service_call`.`device_id` = `devices`.`device_id`
			WHERE `service_call`.`device_id` = '$device->id' ORDER BY `sc_id` ASC
		")){
			return $result;
		}
		else {
			return "";
		}
	}
	
	public static function call($staff, $device, $sl_id, $sc_notes){
		global $mysqli;
		
		$staff_id = $staff->getOperator();
		if (is_object($device)){
			$device_id = $device->id;
		} elseif (is_string($device)) {
			return $device;
		} else {
			$device_id = $device;
		}
		$sc_notes = htmlspecialchars($sc_notes);
		
		if ($sl_id == 1){
			//By default issues are marked complete, as they do not need require additional attention.
			$query = "INSERT INTO `service_call` (`staff_id`, `device_id`, `sl_id`, `solved`, `sc_notes`, `sc_time`)
				VALUES (?, ?, ?, 'Y', ?, CURRENT_TIMESTAMP);";
		} else {
			$query = "INSERT INTO `service_call` (`staff_id`, `device_id`, `sl_id`, `solved`, `sc_notes`, `sc_time`)
				VALUES (?, ?, ?, 'N', ?, CURRENT_TIMESTAMP);";
		}
		
		if ( $stmt = $mysqli->prepare($query)){
			if (!$stmt->bind_param("siis", $staff_id, $device_id, $sl_id, $sc_notes))
					return "Bind Error 76";
			if ($stmt->execute()){
				return true;
			} else {
				return "SC Execute Error 80";
			}
		} else {
			return "SC Prep Error 83";
		}
	}

	public function getSc_id() {
		return $this->sc_id;
	}

	public function getDevice() {
		return $this->device;
	}

	public function getStaff() {
		return $this->staff;
	}

	public function getSl() {
		return $this->sl;
	}

	public function getSolved() {
		return $this->solved;
	}

	public function getSc_notes() {
		return $this->sc_notes;
	}
	
	public function getSc_time() {
		global $sv;
		return date($sv['dateFormat'],strtotime($this->sc_time));
	}
	
	public function getSR(){
		return $this->sr;
	}
	
	public function insert_reply($staff, $status, $sl_id, $sr_notes){
		global $mysqli;
		
		if (!Service_lvl::regexID($sl_id)){
			return "Invalid Service Level Value";
		}
		
		$msg = Service_reply::insert_reply($staff, $this->getSc_id(), $sr_notes);
		if (is_string($msg)){
			//display error message
			return $msg;
		}
		if ($sl_id != $this->getSl()->getSl_id() || $status == "complete"){
			//Either you can mark a ticket as complete or you can leave it incomplete and change the severity of the issue
			if ($status == "complete"){
				$query = "  UPDATE `service_call` 
							SET `solved` = 'Y'
							WHERE `service_call`.`sc_id` = ".$this->getSc_id().";";
			} else {
				$query = "  UPDATE `service_call` 
							SET `sl_id` = '$sl_id', `solved` = 'N'
							WHERE `service_call`.`sc_id` = ".$this->getSc_id().";";
			}
			
			//Run query to update status
			if ($result = $mysqli->query($query)){
				return true;
			} else {
				return "SC Error 167";
			}
		}
	}
	
	//Returns results of open Service Calls
	public static function openSC(){
		global $mysqli;
		
		if ($result = $mysqli->query("
			SELECT `device_name`, `sl_id`, `sc_id`, `staff_id`, `sc_time`, `sc_notes`, `solved`
			FROM `service_call`
			LEFT JOIN `devices`
			ON `service_call`.`device_id` = `devices`.`device_id`
			WHERE `solved` = 'N'
			ORDER BY `sc_id` ASC
		")){
			return $result;
		} else {
			return "";
		}
	}

	public function setDevice($device_id) {
		$this->device = new Devices($device_id);
	}
	function setSc_id($sc_id) {
		$this->sc_id = $sc_id;
	}

	function setStaff($staff) {
		$this->staff = Staff::withID($staff);
	}

	function setSl($sl_id) {
		$this->sl = new Service_lvl($sl_id);
	}

	function setSolved($solved) {
		$this->solved = $solved;
	}

	function setSc_notes($sc_notes) {
		$this->sc_notes = ($sc_notes);
	}
	
	function setSr($sc_id){
		$this->sr = Service_reply::bySc_id($sc_id);
	}

	function setSc_time($sc_time) {
		$this->sc_time = $sc_time;
	}
	
	function updateNotes($notes){
		global $mysqli;
		
		//Prevent script injections
		$notes = htmlspecialchars($notes);
		
		if ( $stmt = $mysqli->prepare("
			UPDATE `service_call` 
			SET `sc_notes` = ?
			WHERE `service_call`.`sc_id` = ?
		")){
			if (!$stmt->bind_param("si", $notes, $this->sc_id))
					return "Bind Error 76";
			if ($stmt->execute()){
				return true;
			} else {
				return "SC Execute Error 80";
			}
		} else {
			return "SC Prep Error 83";
		}
	}

}



class Service_lvl {
	private $sl_id;
	private $msg;
	
	public function __construct($sl_id) {
		global $mysqli;
		
		if (!preg_match("/^\d+$/", $sl_id))
			throw new Exception("Unable to set status");
		
		if ($result = $mysqli->query("
			SELECT *
			FROM `service_lvl`
			WHERE `sl_id` = '$sl_id';
		")){
			$row = $result->fetch_assoc();
			$this->setMsg($row['msg']);
			$this->setSl_id($sl_id);
		} else {
			throw new Exception("Unable to set status");
		}
	}
	
	public static function getList(){
		global $mysqli;
		$slArray = array();
		
		if ($result = $mysqli->query("
			SELECT `sl_id`
			FROM `service_lvl`
			WHERE 1;
		")){
			while($row = $result->fetch_assoc()){
				array_push($slArray, new self($row['sl_id']));
			}
			return $slArray;
		} else {
			return false;
		}
	}
	
	public static function getDot($sl_id){
		$icon = "circle";
		
		if($sl_id == 1) {
			$color = "green";
		} elseif($sl_id < 7) {
			$color = "yellow";
		} else {
			//7+ is Non-useable
			$color = "red";
			$icon = "times";
		}

		echo "<i class='fas fa-$icon fa-lg' style='color:".$color."' id='sl_dot'></i>&nbsp;";
	}
	
	public static function regexID($sl_id){
		global $mysqli;

		if (!preg_match("/^\d+$/", $sl_id))
			return false;

		//check to see if ID exists in table
		if($result = $mysqli->query("
			SELECT *
			FROM `service_lvl`
			WHERE `sl_id` = '$sl_id';
		")){
			if ($result->num_rows == 1)
				return true;
		} else 
			return false;
	}
	
	public static function sltoMsg($sl_id){
		global $mysqli;
		
		if( $result = $mysqli->query("
			SELECT `msg`
			FROM `service_lvl`
			WHERE `sl_id` = '$sl_id'
		")){
			$row = $result->fetch_assoc();
			return $row['msg'];
		}
	}


	public function getSl_id() {
		return $this->sl_id;
	}

	public function getMsg() {
		return $this->msg;
	}

	public function setSl_id($sl_id) {
		$this->sl_id = $sl_id;
	}

	public function setMsg($msg) {
		$this->msg = $msg;
	}
}



class Service_reply {
	private $sr_id;
	private $sc_id;
	private $sr_notes;
	private $sr_time;
	//objects
	private $staff;
	
	public function __construct($sr_id) {
		global $mysqli;
		
		if ($result = $mysqli->query("
			SELECT * 
			FROM `service_reply` 
			WHERE `sr_id` = $sr_id
			LIMIT 1;
		")){
			if( $result->num_rows == 1){
				$row = $result->fetch_assoc();
				$this->setSr_id($row['sr_id']);
				$this->setSc_id($row['sc_id']);
				$this->setStaff($row['staff_id']);
				$this->setSr_notes($row['sr_notes']);
				$this->setSr_time($row['sr_time']);
			}
		}
	}
	
	public static function bySc_id($sc_id){
		global $mysqli;
		$sr_array = array();
		
		if($result = $mysqli->query("
			SELECT `sr_id`
			FROM `service_reply`
			WHERE `sc_id` = '$sc_id'
		")){
			while($row = $result->fetch_assoc()){
				array_push( $sr_array, new self($row['sr_id']) );
			}
		}
		
		return $sr_array;
	}
	
	function getSr_id() {
		return $this->sr_id;
	}

	function getSc_id() {
		return $this->sc_id;
	}

	function getStaff() {
		return $this->staff;
	}

	function getSr_notes() {
		return $this->sr_notes;
	}

	function getSr_time() {
		global $sv;
		
		return date($sv['dateFormat'],strtotime($this->sr_time));
	}
	
	public static function insert_reply($staff, $sc_id, $sr_notes){
		global $mysqli;
		
		$staff_id = $staff->getOperator();
		$sr_notes = htmlspecialchars($sr_notes);
		
		if ( $stmt = $mysqli->prepare("
			INSERT INTO `service_reply` (`sc_id`, `staff_id`, `sr_notes`, `sr_time`)
			VALUES (?, ?, ?, CURRENT_TIMESTAMP);
		")){
			if (!$stmt->bind_param("iss", $sc_id, $staff_id, $sr_notes))
					return "Bind Error 88";
			if ($stmt->execute()){
				return true;
			} else {
				return "SR Execute Error 95";
			}
		} else {
			return "SR Prep Error 98";
		}
		
		return true;
	}

	function setSr_id($sr_id) {
		$this->sr_id = $sr_id;
	}

	function setSc_id($sc_id) {
		$this->sc_id = $sc_id;
	}

	function setStaff($staff) {
		if (is_object($staff)){
			$this->staff = $staff;
		} else {
			$this->staff = Users::withID($staff);
		}
	}

	function setSr_notes($sr_notes) {
		$this->sr_notes = $sr_notes;
	}

	function setSr_time($sr_time) {
		$this->sr_time = $sr_time;
	}
}



?>