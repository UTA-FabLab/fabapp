<?php

/***********************************************************************************************************
*
*	@author Jon Le
*	Edited by: MPZinke on 06.11.19 to improve commenting an logic/functionality of class.
*	 Made members public. Status variable for dynamic changing of DB.
*	 Status DB changed so that 1-10 hold material statuses, 11-20 hold device statuses, 
*	 21-30 hold transaction statuses.
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.94
*		-House Keeping (DB cleanup, $status variable, class syntax/functionality)
*		-Multiple Materials
*		-Off-line Mode
*		-Sheet Goods
*		-Storage Box
*
*	DESCRIPTION: hold information of status of transaction.
*
***********************************************************************************************************/

$status = array();
if($results = $mysqli->query("SELECT `status_id`, `variable`
								FROM `status`;"
))
	while($row = $results->fetch_assoc())
		$status[$row['variable']] = $row['status_id'];
else $message = $mysqli->error;


class Status {
	public $status_id;
	public $message;
	
	public function __construct($status_id) {
		global $mysqli;
		
		if (!preg_match("/^\d+$/", $status_id))
			throw new Exception("Unable to set status");
		
		if ($result = $mysqli->query("
			SELECT *
			FROM status
			WHERE status_id = $status_id;
		")){
			$row = $result->fetch_assoc();
			$this->setMsg($row['message']);
			$this->setStatus_id($row['status_id']);
		} 
		else throw new Exception("Unable to set status");
	}
	
	public static function getList(){
		global $mysqli;
		$sArray = array();
		
		if ($result = $mysqli->query("
			SELECT *
			FROM status"
		)) {
			while($row = $result->fetch_assoc())
				$sArray[$row['status_id']] = $row['message'];
			return $sArray;
		}
		return false;
	}


	public static function device_and_transaction_statuses() {
		global $mysqli, $status;

		if($results = $mysqli->query("	SELECT `status_id`
										FROM `status`
										WHERE `message` IS NOT NULL
										AND `status_id` != '$status[sheet_sale]'
										AND '$status[active]' <= `status_id`
										AND `status_id` <= '30';"
		)) {
			$ticket_statuses = array();
			while($row = $results->fetch_assoc())
				$ticket_statuses[] = $row["status_id"];
			return $ticket_statuses;
		}
		return null;  // did not work: there should always be a ticket status value
	}


	public static function material_statuses() {
		global $mysqli, $status;

		if($results = $mysqli->query("	SELECT `status_id`
										FROM `status`
										WHERE `message` IS NOT NULL
										AND `status_id` <= '10'
										AND `status_id` != '$status[sheet_sale]';"
		)) {
			$ticket_statuses = array();
			while($row = $results->fetch_assoc())
				$ticket_statuses[] = $row["status_id"];
			return $ticket_statuses;
		}
		return null;  // did not work: there should always be a ticket status value
	}


	public static function regexID($status_id){
		global $mysqli;

		if (!preg_match("/^\d+$/", $status_id))
			return false;

		//check to see if ID exists in table
		if($result = $mysqli->query("
			SELECT *
			FROM status
			WHERE status_id = $status_id
			LIMIT 1;
		")){
			if($result->num_rows == 1) return true;
		}
		return false;
	}


	public function get_status_id() {
		return $this->status_id;
	}

	public function getMsg() {
		return $this->message;
	}

	public function setStatus_id($status_id) {
		$this->status_id = $status_id;
	}

	public function setMsg($msg) {
		$this->message = $msg;
	}
}
