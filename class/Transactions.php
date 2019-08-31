<?php

/***********************************************************************************************************
*	
*	@author Jon Le
*	Edited by: MPZinke on 06.08.19 to improve commenting an logic/functionality of class.
*	 Separated physical location (storage_box) from censeptual information (transaction) in
*	 preparation for StorageBox update.
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.94
*		-House Keeping (DB cleanup, $status variable, class syntax/functionality)
*		-Multiple Materials
*		-Off-line Mode
*		-Sheet Goods
*		-Storage Box
*
*	DESCRIPTION: Transaction (Ticket) instance for device usage.  Associates costs, user,
*	 devices, materials for creating/logging information in DB `transactions`.  Prints hard copy
*	 of tickets.  Designed for by-page instance & not transfering accross pages.  If sent 
*	 pages, post creation changes (eg Materials) do not update
*
***********************************************************************************************************/
 
//Thermal Reciept Dependancies
require_once ($_SERVER['DOCUMENT_ROOT'].'/api/php_printer/autoload.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/connections/tp_connect.php');
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
 
class Transactions {
	public $cost;  // costs associated with ticket; device usage + material usage
	public $est_time;  // estimated time to do transaction task
	public $filename;  // used filename from `notes` with ⦂ following (eg my_file.gcode⦂ )
	public $notes;  // note associated with transaction
	public $pickup_time;  // time item was picked up
	public $t_start;  // start time of transaction task
	public $t_end;  // end time of transaction task
	public $trans_id;  // unique identifier of transaction
	//Objects
	public $acct_charge;  // acount charge object based on trans_id
	public $device;  // device object based on device_id
	public $mats_used;  // material object(s) based on trans_id
	public $pickedup_by;  // person (mavID) that picked up item
	public $purpose;  // purpose object based on p_id
	public $staff;  // fablab staff user object based on staff_id
	public $status;  // status object based on status
	public $user;  // person who started the print/ticket
	
	public function __construct($trans_id){
		global $mysqli;
		
		if (!preg_match("/^\d+$/", $trans_id))
			throw new Exception("Invalid Ticket Number : $trans_id");
		
		if ($result = $mysqli->query("
			SELECT *
			FROM transactions
			WHERE trans_id = $trans_id
			LIMIT 1;
		")){
			if ($result->num_rows == 0 ) throw new Exception("Ticket Not Found : $trans_id");

			$row = $result->fetch_assoc();
			$this->acct_charge = Acct_charge::byTrans_id($trans_id);
			$this->device = new Devices($row['d_id']);  //REMOVE WITH UPDATE
			// $this->device = new Devices($row['device_id']);  //ADD WITH UPDATE
			$this->est_time = $row['est_time'];
			if(substr_count($row['notes'], "⦂")) $this->filename = explode("⦂", $row['notes'])[0];
			$this->mats_used = Mats_Used::objects_by_trans_id($row['trans_id']);
			$this->notes = substr_count($row['notes'], "⦂") ? explode("⦂", $row['notes'])[1] : $row['notes'];
			$this->purpose = new Purpose($row['p_id']);
			$this->pickup_time = $row['pickup_time'];
			$this->pickedup_by = Users::withID($row['pickedup_by']);
			$this->staff = Users::withID($row['staff_id']);
			$this->status = new Status($row['status_id']);
			$this->t_end = $row['t_end'];
			$this->t_start = $row['t_start'];
			$this->trans_id = $row['trans_id'];
			$this->user = Users::withID($row['operator']);
		}
	}


	// get the amount of credit (money already put forward) towards this transaction: replaced totalAC()
	public function current_transaction_credit(){
		$total = 0;
		foreach(Acct_charge::byTrans_id($this->trans_id) as $acct_charge)
			// do not include OutStanding Charges
			if($acct_charge->account->a_id != 1) $total += $acct_charge->amount;
		return $total;
	}


	// return the duration of the ticket in float-hours (or if greater minimum duration)
	public function duration_numeric(){
		global $sv;

		if(!$this->t_start) return 0;
		if(!$end = $this->t_end) $end = date("Y-m-d H:i:s", strtotime("now"));	

		$duration = (strtotime($this->t_end) - strtotime($this->t_start)) / 3600;
		return $this->duration < $sv['minTime'] ? $sv['minTime'] : $duration;  // minimum time interval
	}


	// return string form of duration in hh:mm:ss format
	public function duration_string() {
		$duration = strtotime($this->t_end) - strtotime($this->t_start);
		$hour = floor($duration / 3600);
		if($hour > 800) return "800:00:00";  // max visable duration

		$minute = $duration / 60 % 60;
		$second = $duration % 60;

		return "$hour:$minute:$second";
	}


	// if the values are different then update the DB
	public function edit_transaction_information($change_array){
		if(!count($change_array)) return;

		if(array_key_exists("filename", $change_array)) $this->filename = $change_array["filename"];
		if(array_key_exists("notes", $change_array)) $this->notes = $change_array["notes"];
		// $this->setT_end(date("Y-m-d H:i:s",strtotime($t_end)));  //KEEP as example of format for time_end format
		if(array_key_exists("t_start", $change_array)) $this->t_start = $change_array["t_start"];
		if(array_key_exists("t_end", $change_array)) $this->t_end = $change_array["t_end"];
		if(array_key_exists("pickup_time", $change_array)) $this->pickup_time = $change_array["pickup_time"];

		// OBJs
		if(array_key_exists("device", $change_array)) $this->device = $change_array["device"];
		if(array_key_exists("operator", $change_array)) $this->user = $change_array["operator"];
		if(array_key_exists("pickedup_by", $change_array)) $this->pickedup_by = $change_array["pickedup_by"];
		if(array_key_exists("staff", $change_array)) $this->staff = $change_array["staff"];
		if(array_key_exists("status", $change_array)) $this->status = $change_array["status"];

		return $this->update_transaction();
	}


	/*
	takes in future status of ended transaction, OBJ of who ends it, optional array to specifiy
	specific material statuses (if some but not all failed/succeeded/cancelled)
	*/
	public function end_transaction($staff, $status_id){
		global $mysqli, $role, $sv;

		$this->staff = $staff;
		$this->status = new Status($status_id);

		// only staff may close the ticket
		if($staff->roleID < $role["staff"]) return "Please ask a staff member to close this ticket. $staff->roleID";

		// restrict users below editTrans level from ending own tickets
		if($staff->operator == $this->user->operator && $staff->getRoleID() < $sv['editTrans'])
			return "Please ask a fellow staff member to close this ticket.";

		$this->t_end = date("Y-m-d H:i:s");
		return $this->update_transaction();  // return error or null (no error)
	}
	

	public function end_juicebox(){
		global $mysqli, $status;
		
		$total = $this->quote_cost();
		//Status = Moveable
		//Intended to block additional Power On Until Learner Pays Balance
		// Alt logic, payments get placed into a "tab"
		if (abs($total - 0.001) > .005) $this->status = new Status($status["moveable"]);
		else $this->status = new Status($status["complete"]);
		// null for no error, string for error
		return $this->update_transaction();
	}
	

	public function end_octopuppet(){
		global $mysqli, $status;

		if ($mysqli->query("
			UPDATE `transactions`
			SET `t_end` = '$this->t_end', `status_id` = '$status[moveable]'
			WHERE `trans_id` = '$this->trans_id';
		")){
			if ($mysqli->affected_rows == 1) return true;
		}
		return false;  // unsuccessful
	}


	public function endSheetTicket($trans_id, $sheet_good_status){
		global $mysqli, $status;
		
		// 1 for complete, 2 for failed
		$status_id = $sheet_good_status == 1 ? $status["complete"] : $status["total_fail"];
		
		
		if ($mysqli->query("
			UPDATE `transactions`
			SET `t_end` = CURRENT_TIMESTAMP , `status_id` = '$status_id'
			WHERE `trans_id` = '$trans_id';
		"))
			return 1;
		return 0;
	
	}


	public static function insertSheetTrans($trans_id, $inv_id, $quantity){
		global $mysqli;
		
		if ($mysqli->query("
				INSERT INTO `sheet_good_transactions` 
					(`trans_id`, `inv_id`, `quantity`, `remove_date`) 
				VALUES
					('$trans_id', '$inv_id', '$quantity', CURRENT_TIMESTAMP);
		")){
                return true;
            } else {
                return false;
            }
	}


	// create a new transaction in DB and return trans_id to associate with
	public static function insert_new_transaction($operator, $device_id, $est_time, $p_id, $status_id, $staff, $note=null) {
		global $mysqli, $status;

		//Validate input variables
		if(!Devices::regexDeviceID($device_id))return "Bad Device";
		if(Devices::is_open($device_id)) return "Is Open";
		if($est_time && !self::regexTime($est_time)) return "Bad Time - $est_time";
		if(!Purpose::regexID($p_id)) return "Invalid Purpose - $p_id";
		if($status_id && !Status::regexID($status_id)) return "Invalid Status";
		$note = $note ? "'".self::regexNotes($note)."'" : "NULL";
		
		if($error = Wait_queue::transferFromWaitQueue($operator->operator, $device_id)) return $error;
		
		$t_end = $status_id == $status["sheet_sale"] ? "CURRENT_TIMESTAMP" : "NULL";  // sheet goods
		// $note is intentionally left without '' so that if it is null, it will be entered as a null value
		if ($mysqli->query("INSERT INTO transactions 
							(`operator`, `d_id`, `t_start`, `t_end`, `status_id`, `p_id`, `est_time`, `staff_id`, `notes`) 
							-- (`operator`,`device_id`,`t_start`,`status_id`,`p_id`,`est_time`,`staff_id`, `notes`) 
							VALUES
							('$operator->operator', '$device_id', CURRENT_TIMESTAMP, $t_end, '$status_id', '$p_id', '$est_time', '$staff->operator', $note);"
		)){
			return $mysqli->insert_id;
		}
		return $mysqli->error;
	}


	/*
	Check that the materials associated with transaction are not chargable. 
	If materials has cost, user must pay for it.
	*/
	public function no_associated_materials_have_a_price() {
		foreach($this->mats_used as $mat_used)
			if($mat_used->material->price) return false;
		return true;
	}


	// prints the thermal ticket
	public static function printTicket($trans_id){
		global $mysqli, $sv, $tp;

		try {
			$ticket = new self($trans_id);  //Pull Ticket Related Information
		}
		catch(Exception $e) {
			echo $e;
			return $e->getMessage();
		}
		
		try {
			// $tpn = 0;  // no other thermal printers || multiple not implemented: default to first in queue
			$tpn = $ticket->device->device_group->thermal_printer_num;  // get from list of thermal printers based on device location
			$connector = new NetworkPrintConnector( $tp[$tpn][0], $tp[$tpn][1]);
			$printer = new Printer($connector);
		}
		catch (Exception $e) {
			return "Couldn't print to this printer: " . $e -> getMessage() . "\n";
		}

		try {

			// Print Generic Header
			$img = EscposImage::load("$_SERVER[DOCUMENT_ROOT]/images/$sv[icon2]", 0);  //TODO: change to $sv
			$printer->setJustification(Printer::JUSTIFY_CENTER);
			$printer->graphics($img);
			$printer->feed();
			$printer->text("Ticket: $ticket->trans_id");
			$printer->feed();
			$printer->text($ticket->t_start);

			//Body
			$printer->feed(2);
			$printer->text("Device:   ".$ticket->device->name);
			
			// estimated amount
			$est_amount = 0;
			foreach($ticket->mats_used as $mat_used)
				$est_amount += $mat_used->quantity_used;
			$printer->feed();
			$printer->text("Est. Amount:   $est_amount ".$mat_used->material->unit);

			$printer->feed();
			$printer->text("Est. Cost:   ");
			$printer->text("$ ".number_format($ticket->quote_cost(), 2));
			$printer->feed();
			if($ticket->est_time) $printer->text("Est. Duration:   $ticket->est_time");
			
			if ($ticket->filename){
				$printer->feed();
				$printer->text("File:   $ticket->filename");
			}

			$printer->feed(1);
			if ($ticket->device->device_group->is_storable == "Y" && StorageObject::object_is_in_storage($ticket->trans_id)){
				$storage_object = new StorageObject($ticket->trans_id);
				$printer->text("Address: $storage_object->box_id");
			}
			else $printer->text("Address: ________________");

			$printer->feed();
			$printer->text("Potential Problems?  ( Y )  ( N )");
			//Print Color Swap Instructions
			$printer->feed();

			if(count($ticket->mats_used) > 1) {
				$printer->feed();
				$printer->text("Color Swap");
			}
			for($x = count($ticket->mats_used); 0 < $x; $x--) {
				$printer->feed();
				$printer->text(str_pad((count($ticket->mats_used) - $x +1).":", 5, " ").str_pad($ticket->mats_used[$x-1]->material->m_name, 20, "_"));
			}


			$printer->feed(2);
			$printer->text("NOTES: _________________________");
			$printer->feed(2);
			$printer->text("________________________________");
			$printer->feed(2);
			$printer->text("________________________________");
			// footer
			$printer->feed(3);
			$printer->graphics(EscposImage::load($_SERVER['DOCUMENT_ROOT']."/images/sig.png", 0));
			$printer->feed();
			$printer->text($sv['website_url']);  //TODO: change to $sv
			$printer->feed();
			$printer->text($sv['phone_number']);  //TODO: change to $sv
			$printer->feed(2);
			$printer->cut();
		}
		catch (Exception $print_error) {
			return $print_error->getMessage();
		}

		try {
			$printer->close();  //Close printer
		}
		catch( Exception $e) {
			echo "printer was not open";
		}
	}


	// return the cost for this ticket based on materials used excluding amount
	public function quote_cost() {
		global $status;

		$cost = 0;
		// sum materials costs that are not failed
		foreach($this->mats_used as $mu) {
			if($mu->status->status_id != $status['failed_mat'])  // failed materials are not charged to user
				$cost += abs($mu->quantity_used) * $mu->material->price;
		}
		return (0 <= $cost && $cost < .005) ? 0 : $cost;
	}


	// add information about who pickuped a transaction, when and by whom it was facilitated
	public function record_pickup($reciever, $staff) {
		return $this->edit_transaction_information(array(	"pickedup_by" => $reciever, 
															"pickup_time" => date("Y-m-d H:i:s"), 
															"staff" => $staff));
	}


	// the cost of materials - the amount already paid towards the transaction
	public function remaining_balance() {
		// current cost - (already been paid) + .001 (prevent negative rounding errors)
		$balance = $this->quote_cost() - $this->current_transaction_credit() + .001;
		// allow for .005 rounding error
		return (-.005 < $balance && $balance < .005) ? 0 : $balance; 
	}


	// write all variables to the DB for a given Transaction
	public function update_transaction() {
		global $mysqli;

		// prevent error from accessing null's attributes
		$pickedup_by = $this->pickedup_by ? $this->pickedup_by->operator : null;

		// update transaction info
		$statement = $mysqli->prepare("UPDATE `transactions`
											SET `d_id` = ?, `operator` = ?, `t_start` = ?, 
											`t_end` = ?, `status_id` = ?, `staff_id` = ?, 
											`pickup_time` = ?, `pickedup_by` = ?,  `notes` = ?
											WHERE `trans_id` = ?;");

		$statement->bind_param("dssssdsssd", $this->device->device_id, $this->user->operator, $this->t_start, 
									$this->t_end, $this->status->status_id, $this->staff->operator, 
									$this->pickup_time, $pickedup_by, $this->filename_and_notes(), 
									$this->trans_id);



		if(!$statement->execute()) return "Could not update transaction values";

		return null;  // no errors
	}


	// ———————————————— ATTRIBUTES —————————————————

	public function filename_and_notes() {
		if($this->filename) return $this->filename."⦂".$this->notes;
		return $this->notes;
	}

	public function getT_start() {
		global $sv;
		return date($sv['dateFormat'],strtotime($this->t_start));
	}


	public function getT_start_picker() {
		return date("m/d/Y g:i a",strtotime($this->t_start));
	}


	public function getT_end() {
		global $sv;
		if (strcmp($this->t_end, "") == 0)
			return "";
		return date($sv['dateFormat'],strtotime($this->t_end));
	}


	public function getT_end_picker() {
		if (strcmp($this->t_end, "") == 0)
			return "";
		return date("m/d/Y g:i a",strtotime($this->t_end));
	}


	// add filename, if contained in note; otherwise null filename
	public function set_filename_and_notes($trans_notes){
		$this->filename = substr_count($trans_notes, "⦂") ? explode("⦂", $trans_notes)[0] : null;
		$this->notes = substr_count($trans_notes, "⦂") ? explode("⦂", $trans_notes)[1] : $trans_notes;
	}


	/*
	TODO: update external functions:
		duration // returns explode(":", this->duration) "${hour}h ${minute}m ${second}s";
		end_juicebox() // returns if(error)
		flud  // filename
	*/


	// ————————————————— REGEX ——————————————————

	public static function regexNotes($notes) {
		return htmlspecialchars($notes);
	}

	public static function regexTime($duration) {
		if(preg_match("/^\d{1,3}:\d{2}:\d{2}$/", $duration) == 1) return true;
		return false;
	}

	public static function regexTrans($trans_id){
		if(!preg_match("/^\d+$/", $trans_id)) return false;

		global $mysqli;
		//Check to see if transaction exists
		if ($result = $mysqli->query("
			SELECT *
			FROM transactions
			WHERE trans_id = $trans_id
			LIMIT 1;"
		)){
			if ($result->num_rows == 1) return true;
			return false;
		}
		return false;
	}




// —————————————— REMOVE WITH UPDATE ———————————————
// —————————————————————————————————————————

	public function getAc() {
		return $this->acct_charge;
	}
	public function getDevice() {
		return $this->device;
	}
	public function getDuration() {
		if (strcmp($this->duration,"") == 0)
				return "";
		$sArray = explode(":", $this->duration);
		$time = "$sArray[0]h $sArray[1]m $sArray[2]s";
		return $time;
	}
	
	public function getDuration_raw() {
		if (strcmp($this->duration,"") == 0)
				return "";
		return $this->duration;
	}
	public function getEst_time() {
		return $this->est_time;
	}
	public function getMats_used() {
		return $this->mats_used;
	}
	public function getPurpose() {
		return $this->purpose;
	}
	public function getUser() {
		return $this->user;
	}
	public function getStaff() {
		return $this->staff;
	}
	public function getStatus() {
		return $this->status;
	}
	public function getTrans_id() {
		return $this->trans_id;
	}
}

?>