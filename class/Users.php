<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */

/**
 * Users
 * Pull all attributes relevant to a User
 * @author Jon Le
 */
include_once ($_SERVER['DOCUMENT_ROOT']."/class/site_variables.php");

$role = array();
if($results = $mysqli->query("SELECT `r_id`, `variable`
								FROM `role`;"
))
	while($row = $results->fetch_assoc())
		$role[$row['variable']] = $row['r_id'];
else $message = $mysqli->error;


class Users {
	public $accounts;
	public $adj_date;
	public $exp_date;
	public $icon;
	public $long_close;
	public $notes;
	public $operator;
	public $rfid_no;
	public $roleID;

	public function __construct() {}

	public static function withID($operator){
		$instance = new self();
		if (!self::regexUser($operator)){
			return "Invalid ID Number - $operator";
		}
		$instance->createWithID($operator);
		return $instance;
	}

	public static function withRF($rfid_no){
		$instance = new self();
		if (!self::regexRFID($rfid_no)){
			return "Invalid RFID Number - $rfid_no";
		}
		if (!self::rfidExist($rfid_no)){
			return "No Match for RFID Number - $rfid_no";
		}
		
		$instance->createWithRF($rfid_no);
		return $instance;
	}
	
	public static function payer($operator){
		$instance = new self();
		if (preg_match("/^\d{9}$/",$operator) == 0){
			return false;
		}
		$instance->setOperator($operator);
		$instance->setRoleID(1);
		$instance->setIcon("far fa-id-card");
		return $instance;
	}

	public function createWithID($operator){
		global $mysqli;

		if ($result = $mysqli->query("
			SELECT users.operator, users.r_id, exp_date, icon, rfid_no, adj_date, notes, long_close
			FROM `users`
			LEFT JOIN rfid
			ON users.operator = rfid.operator
			WHERE users.operator = '$operator'
			Limit 1;
		")){
			$row = $result->fetch_assoc();
			if (strcmp($row['operator'], "") != 0){
				$this->setOperator($row['operator']);
				$this->setRoleID($row['r_id']);
			} else {
				$this->setOperator($operator);
				$this->setRoleID(2);
			}
			
			$this->setAccounts($operator);
			$this->setAdj_date($row['adj_date']);
			$this->setExp_date($row['exp_date']);
			$this->icon = $row['icon'] ? $row['icon'] : "fas fa-user";
			$this->setLong_close ($row['long_close']);
			$this->setNotes ($row['notes']);
			$this->setRfid_no($row['rfid_no']);
		} else {
			echo $mysqli->error;
			return false;
		}
	}
	
	public function createWithRF($rfid_no){
		global $mysqli;
		
		if ($result = $mysqli->query("
			SELECT users.operator, users.r_id, exp_date, icon, rfid_no, adj_date, notes, long_close
			FROM `rfid`
			LEFT JOIN users
			ON rfid.operator = users.operator
			WHERE rfid.rfid_no = $rfid_no;
		")){
			if ($result->num_rows == 1) {
				$row = $result->fetch_assoc();
				$this->setAccounts($row['operator']);
				$this->setAdj_date($row['adj_date']);
				$this->setIcon ($row['icon']);
				$this->setExp_date ($row['exp_date']);
				$this->setLong_close ($row['long_close']);
				$this->setNotes ($row['notes']);
				$this->setOperator($row['operator']);
				$this->setRoleID($row['r_id']);
				$this->setRfid_no($row['rfid_no']);
			} else {
				throw new Exception("SQL Error");
			}
		} else {
			return false;
		}
	}

	public function getAccounts(){
		return $this->accounts;
	}

	public function getAdj_date(){
		return $this->adj_date;
	}
	
	public function getExp_date(){
		return $this->exp_date;
	}
	
	public function getIcon(){
		if ($this->icon == ""){
			return "fas fa-user";
		}
		return $this->icon;
	}
	
	public function getLong_close(){
		return $this->long_close;
	}
	
	public function getNotes(){
		return $this->Notes;
	}
	
	public function getOperator(){
		return $this->operator;
	}
	
	public function getRfid_no(){
		return $this->rfid_no;
	}
	
	public function getRoleID() {
		if ($this->roleID){
			return $this->roleID;
		} else {
			return false;
		}
	}
	
    public static function getTabResult(){
        global $mysqli;
        if ($result = $mysqli->query("
            SELECT DISTINCT `role`.`r_id`, `role`.`title`
            FROM `role`, `users`
            WHERE `role`.`r_id` = `users`.`r_id`
        ")){
            return  $result;
        } else {
            return false;
        }
    }
	
	public function history(){
		global $mysqli;
		$tickets = array();
		
		if ($result = $mysqli->query(
			//ADD WITH UPDATE
			// "SELECT `transactions`.`trans_id`, `devices`.`device_name`, `transactions`.`t_start`, `status`.`msg`, `acct_charge`.`amount`
			//  FROM `transactions`
			//  LEFT JOIN `devices`
			//  ON `transactions`.`device_id` = `devices`.`device_id`
			//REMOVE FIRST LINE WITH UPDATE
			"SELECT `transactions`.`trans_id`, `devices`.`device_desc`, `transactions`.`t_start`, `status`.`message`, `acct_charge`.`amount`
			 FROM `transactions`
			 LEFT JOIN `devices`
			 ON `transactions`.`d_id` = `devices`.`d_id`
			 LEFT JOIN `status`
			 ON `transactions`.`status_id` = `status`.`status_id`
			 LEFT JOIN `acct_charge`
			 ON `transactions`.`trans_id` = `acct_charge`.`trans_id`
			 WHERE `transactions`.`operator` = '$this->operator'
			 ORDER BY `trans_id` DESC;
		")){
			while($row = $result->fetch_assoc()){
				array_push($tickets, array($row['trans_id'], $row['device_name'], $row['t_start'], $row['message'], $row['amount']));
			}
		}
		return $tickets;
	}
	
	public function insertRFID($staff, $rfid_no){
		global $mysqli;
		global $sv;
		
		if ($staff->getRoleID() < $sv['editRfid']){
			return "Insufficient role to add new RFID";
		}
		if (!self::regexRFID($rfid_no)){
			return "U:151 Invalid RFID number format.";
		}
		
		//Check if RFID is already in table
		if ($result = $mysqli->query("
			SELECT *
			FROM `rfid`
			WHERE `rfid_no` = $rfid_no;
		")){
			if ($result->num_rows > 0){
				$row = $result->fetch_assoc();
				return "This RFID #$rfid_no has already been assinged to ".$row['operator'];
			}
		}
		
		if ($stmt = $mysqli->prepare("
			INSERT INTO `rfid` (`rf_id`, `rfid_no`, `operator`) VALUES (NULL, ?, ?);
		")){
			$stmt->bind_param("ss", $rfid_no, $this->operator);
			if ($stmt->execute() === true ){
				$row = $stmt->affected_rows;
				$stmt->close();
				$this->rfid_no = $rfid_no;
				if ($row == 1){
				} else {
					return "Users: insertRFID Count Error ".$row;
				}
			} else
				//return "Users: insertRFID Execute Error";
				return $stmt->error;
		} else {
			return "Error in preparing Users: insertRFID statement";
		}
		//By Default assign Role 2
		$this->insertUser($staff, 2);
	}
	
	public function insertUser($staff, $role_id){
		global $mysqli;
		global $sv;
		
		if ($staff->getRoleID() < $sv['minRoleTrainer']){
			return "Insufficient role to Modify Role";
		}
		$operator = $this->getOperator();
		$staff_id = $staff->getOperator();
		//Check if User is already in table
		if ($result = $mysqli->query("
			SELECT *
			FROM `users`
			WHERE `operator` = $operator;
		")){
			if ($result->num_rows == 0){
				//Define User in table and assign default Role
				if ($stmt = $mysqli->prepare("
					INSERT INTO `users` (`operator`, `r_id`, `adj_date`, `notes`, `long_close`) 
					VALUES (?, ?, CURRENT_TIME(), ?, 'N');
				")){
					$stmt->bind_param("sss", $this->operator, $role_id, $staff_id);
					if ($stmt->execute() === true ){
						$row = $stmt->affected_rows;
						$stmt->close();
						if ($row == 1){
							$this->setRoleID($role_id);
							return true;
						} else {
							return "Users: insertUser Count Error ".$row;
						}
					} else
						return "Users: insertUser Execute Error";
				} else {
					return "Error in preparing Users: insertUser statement";
				}
			} else{
				//User is in table, lets modify & update adjustment date
				if ($stmt = $mysqli->prepare("
					UPDATE `users` SET `r_id` = ?, `adj_date` = CURRENT_TIME(), `notes` = ? WHERE `users`.`operator` = ?;
				")){
					$stmt->bind_param("sss", $role_id, $staff_id, $this->operator);
					if ($stmt->execute() === true ){
						$row = $stmt->affected_rows;
						$stmt->close();
						if ($row == 1){
							$this->setRoleID($role_id);
							return true;
						} else {
							return "Users: insertUser Update Error ".$row;
						}
					} else
						return "Users: insertUser: Update Execute Error";
				} else {
					return "Error in preparing Users Update: insertUser statement";
				}
			}
		} else {
			return "Error in searching Users.";
		}
	}
	public function modifyRoleID($staff, $notes){
		global $mysqli;
		global $sv;

		if ($this->operator == $staff->getOperator()){
			return "Staff can not modify their own Role ID";
		}

		//concat staff ID onto notes for record keeping
		$notes = "|".$staff->getOperator()."| ".$notes;
		
		if ($staff->getRoleID() >= $sv['editRole']){
			//Staff must have high enough role
			if ($stmt = $mysqli->prepare("
				UPDATE `users`
				SET `r_id` = ?, `adj_date` = CURRENT_TIMESTAMP, `notes`= ?
				WHERE `operator` = ?;
			")){
				$stmt->bind_param("iss", $r_id, $notes, $this->operator);
				if ($stmt->execute() === true ){
					$row = $stmt->affected_rows;
					$stmt->close();
					$this->roleID = $r_id;
					return $row;
				} else
					return "Users: updateRoleID Error";
			} else {
				return "Error in preparing Users: modifyRoleID statement";
			}
		} else {
			return "Insufficient Role to modify Role ID";
		}
	}

	//  The RFID must exist in the table
	public static function regexRFID($rfid_no) {
		global $mysqli;
		//4 to 12 digit format check
		if (preg_match("/^\d{4,12}$/",$rfid_no) == 0) {
			return false;
		}
		return true;
	}
	
	public static function rfidExist($rfid_no){
		global $mysqli;
		
		if ($result = $mysqli->query("
			SELECT *
			FROM `rfid`
			WHERE `rfid`.`rfid_no` = '$rfid_no';
		")){
			if($result->num_rows == 1){
				return true;
			}
			return false;
		}
	}
	
	public static function regexUser($operator) {
		global $sv;
		//10 digit format check
		return preg_match("/$sv[regexUser]/",$operator);
	}
	
	public static function RFIDtoID ($rfid_no) {
		global $mysqli;
		
		if (!preg_match("/^\d+$/",$rfid_no) == 0) return false;

		if ($result = $mysqli->query("
			SELECT operator FROM rfid WHERE rfid_no = $rfid_no
		")){
			$row = $result->fetch_array(MYSQLI_NUM);;
			$operator = $row[0];
			if ($uta_id) return($operator);
			return "No UTA ID match for RFID $rfid_no";
		}
		return "Error Users RF";
	}
	
	private function setAccounts($operator){
		global $mysqli;
		global $sv;
		$accounts = array();
		
		//Authorized Accounts that the user is authorized to use
		if($result = $mysqli->query("
			SELECT `a_id`
			FROM `auth_accts`
			WHERE `auth_accts`.`operator` = '$operator' AND `valid` = 'Y';
		")){
			while($row = $result->fetch_assoc()){
				array_push($accounts, new Accounts($row['a_id']));
			}
		} else {
			//echo $mysqli->error;
			return false;
		}
		
		$this->accounts = $accounts;
	}

	public function setAdj_date($adj_date){
		$this->adj_date = $adj_date;
	}

	public function setExp_date($exp_date){
		$this->exp_date = $exp_date;
	}

	public function setIcon($icon){
		$this->icon = $icon;
	}
	
	public function setLong_close($lc){
		$this->long_close = $lc;
	}
	
	public function setNotes($notes){
		$this->notes = $notes;
	}
	
	public function setOperator($operator){
		$this->operator = $operator;
	}

	public function setRfid_no($rfid_no){
		$this->rfid_no = $rfid_no;
	}
	
	private function setRoleID($r_id){
		$current_date = new DateTime();
		if (($current_date->format('Y-m-d H:i:s') < $this->getExp_date()) || is_null($this->getExp_date())){
			$this->roleID = $r_id;
		} else {
			//User's Role LvL has expired, default to 1
			$this->roleID = 1;
			return "User's role has expired.";
		}
	}
	
	public function ticketsAssist(){
		global $mysqli;
		
		if ($result = $mysqli->query("
			SELECT Count(trans_id) as assist
			FROM `transactions`
			WHERE `staff_id` = '".$this->operator."'
		")){
			$row = $result->fetch_assoc();
			return $row['assist'];
		}
	}
	
	public function ticketsAssistRank(){
		global $mysqli;
		global $sv;
		$i = 0;
		
		if ($result = $mysqli->query("
			SELECT Count(trans_id) as Visits, `staff_id`
			FROM `transactions`
			WHERE `staff_id` IS NOT NULL AND `t_start`
			BETWEEN  DATE_ADD(CURRENT_DATE, INTERVAL -$sv[rank_period] MONTH)
			AND CURRENT_DATE
			Group BY `staff_id` ORDER BY Visits DESC
		")){
			while($row = $result->fetch_assoc()){
				$i++;
				if ($row['staff_id'] == $this->operator){
					return $i;
				}
			}
			return "-";
		}
	}
	
	public function ticketsTotal(){
		global $mysqli;
		
		if ($result = $mysqli->query("
			SELECT Count(trans_id) as visits
			FROM `transactions`
			WHERE `operator` = '".$this->operator."'
		")){
			if ($result->num_rows == 1){
				$row = $result->fetch_assoc();
				return $row['visits'];
			} else{
				return 0;
			}
		}
	}
	
	public function ticketsTotalRank(){
		global $mysqli;
		global $sv;
		$i = 0;
		
		if ($result = $mysqli->query("
			SELECT Count(trans_id) as Visits, `operator`
			FROM `transactions`
			WHERE t_start 
			BETWEEN  DATE_ADD(CURRENT_DATE, INTERVAL -$sv[rank_period] MONTH)
			AND CURRENT_DATE
			Group BY `operator` ORDER BY Visits DESC
		")){
			while($row = $result->fetch_assoc()){
				$i++;
				if ($row['operator'] == $this->operator){
					return $i;
				}
			}
			return "-";
		}
	}
	
	public function updateRFID($staff, $rfid_no){
		global $mysqli;
		global $sv;
		
		if ($staff->getRoleID() < $sv['editRfid']){
			return "Insufficient role to update RFID";
		}
		if ($this->rfid_no == $rfid_no){
			return "RFID remains unchanged";
		}
		
		//Check if RFID is already in table
		if ($result = $mysqli->query("
			SELECT *
			FROM `rfid`
			WHERE `rfid_no` = $rfid_no;
		")){
			if ($result->num_rows > 0){
				$row = $result->fetch_assoc();
				return "This RFID #$rfid_no has already been assinged to ".$row['operator'];
			}
		}
		
		if ($stmt = $mysqli->prepare("
			UPDATE `rfid`
			SET `rfid_no` = ?
			WHERE `operator` = ?;
		")){
			$stmt->bind_param("ss", $rfid_no, $this->operator);
			if ($stmt->execute() === true ){
				$row = $stmt->affected_rows;
				$stmt->close();
				$this->rfid_no = $rfid_no;
				if ($row == 1){
					return true;
				} else {
					return "Users: updateRFID Count Error";
				}
			} else {
				//return "Users: updateRFID Execute Error";
				return $stmt->error;
			}
		} else {
			return "Error in preparing Users: updateRFID statement";
		}
	}
 }



class Role{
	
	public static function getTitle($r_id){
		global $mysqli;

		if (preg_match("/^\d+$/",$r_id) == 0) {
			echo "Invalid RoleID - $r_id";
			return false;
		}

		if ($result = $mysqli->query("
			SELECT `title`
			FROM `role`
			WHERE `r_id` = '$r_id'
			Limit 1;
		")){
			$row = $result->fetch_assoc();
			return $row["title"];
		} else {
			echo mysqli_error($mysqli);
		}
	}


	public static function listRoles(){
		global $mysqli;

		if ($result = $mysqli->query("
			SELECT `r_id`, `title`
			FROM `role`
			WHERE 1;
		")){
			return $result;
		} else {
			echo mysqli_error($mysqli);
		}
	}
}



class Staff extends Users {
	public $timeLimit;
	
	public function __construct() {
		global $sv;
		parent::__construct();
		
		$this->setTimeLimit($sv["limit"]);
	}
	
	public static function withID($operator){
		$instance = new self();
		$instance->createWithID($operator);
		return $instance;
	}
	
	public function getTimeLimit(){
		return $this->timeLimit;
	}
	
	public function setTimeLimit($limit){
		$this->timeLimit = $limit;
	}
}
 ?>