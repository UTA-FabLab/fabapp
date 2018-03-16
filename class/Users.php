<?php
/*
 *   CC BY-NC-AS UTA FabLab 2015-2017
 */

/**
 * Users
 * Pull all attributes relevant to a User
 * @author Jon Le
 */
 include_once ($_SERVER['DOCUMENT_ROOT']."/class/Role.php");
 
 class Users {
    private $u_id;
    private $accounts;
    private $adj_date;
    private $exp_date;
    private $icon;
    private $operator;
    private $rfid_no;
    private $roleID;
    private $notes;

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

    public function createWithID($operator){
        global $mysqli;

        if ($result = $mysqli->query("
            SELECT users.operator, users.r_id, exp_date, icon, rfid_no, adj_date, notes
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
            $this->setIcon($row['icon']);
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
            SELECT users.operator, users.r_id, exp_date, icon, rfid_no, adj_date, notes
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
    
    public function history(){
        global $mysqli;
        $tickets = array();
        
        if ($result = $mysqli->query("
            SELECT `trans_id`
            FROM `transactions`
            WHERE `transactions`.`operator` = '".$this->operator."'
            ORDER BY `trans_id` DESC;
        ")){
            while($row = $result->fetch_assoc()){
                array_push($tickets, new Transactions($row['trans_id']));
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
        
        //Maybe Move this Logic into it's own function
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
                    INSERT INTO `users` (`u_id`, `operator`, `r_id`, `exp_date`, `icon`, `adj_date`, `notes`) 
                    VALUES (NULL, ?, '2', NULL, NULL, CURRENT_TIME(), ?);
                ")){
                    $stmt->bind_param("ss", $this->operator, $staff_id);
                    if ($stmt->execute() === true ){
                        $row = $stmt->affected_rows;
                        $stmt->close();
                        $this->rfid_no = $rfid_no;
                        if ($row == 1){
                            return true;
                        } else {
                            return "Users: insertRFID Count Error ".$row;
                        }
                    } else
                        return "Users: insertRFID Execute Error";
                } else {
                    return "Error in preparing Users: insertRFID statement";
                }
            }
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
        if (preg_match("/".$sv['regexUser']."/",$operator) == 0) {
            //"Invalid Operator ID:".$operator;
            return false;
        } else {
            return true;
        }
    }
    
    public static function RFIDtoID ($rfid_no) {
        global $mysqli;
        
        if (preg_match("/^\d+$/",$rfid_no) == 0) {
            return false;
        }

        if ($result = $mysqli->query("
            SELECT operator FROM rfid WHERE rfid_no = $rfid_no
        ")){
            $row = $result->fetch_array(MYSQLI_NUM);;
            $operator = $row[0];
            if ($uta_id) {
                return($operator);
            } else {
                return "No UTA ID match for RFID $rfid_no";
            }
        } else {
            return "Error Users RF";
        }
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
        
        //See if operator can use any default accounts
        //Based on $sv attributes for acct1, acct3, & acct4
        /*
        if ($sv['acct1'] <= $this->roleID){
            array_push($accounts, new Accounts(1));
        }
        if ($sv['acct3'] <= $this->roleID){
            array_push($accounts, new Accounts(3));
        }
        if ($sv['acct4'] <= $this->roleID){
            array_push($accounts, new Accounts(4));
        }
        */
        
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
            WHERE t_start 
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
 }?>