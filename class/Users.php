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
    private $operator;
    private $exp_date;
    private $roleID;
    private $rfid_no;
    private $icon;
    private $accounts;

    public function __construct() {}

    public static function withID($operator){
        $instance = new self();
        if (!self::regexUser($operator)){
            return NULL;
        }
        $instance->createWithID($operator);
        return $instance;
    }

    public static function withRF($rfid_no){
        $instance = new self();
        if (!$this->regexUser($rfid_no)){
            return "No ID Found";
        }
        $instance->createWithRF($rfid_no);
        return $instance;
    }

    public function createWithID($operator){
        global $mysqli;

        if ($result = $mysqli->query("
            SELECT users.operator, users.r_id, exp_date, icon, rfid_no
            FROM `users`
            LEFT JOIN rfid
            ON users.operator = rfid.operator
            WHERE users.operator = '$operator'
            Limit 1;
        ")){
            $row = $result->fetch_assoc();
            if (strcmp($row['operator'], "") != 0){
                $this->operator = $row['operator'];
                $this->setRoleID($row['r_id']);
            } else {
                $this->operator = $operator;
                $this->setRoleID(2);
            }
            
            $this->exp_date = $row['exp_date'];
            $this->setRfid_no($row['rfid_no']);
            $this->icon = $row['icon'];
            $this->setAccounts($operator);
        } else {
            echo $mysqli->error;
            return false;
        }
    }
	
    public function createWithRF($rfid_no){
        global $mysqli;
        
        if ($result = $mysqli->query("
            SELECT users.operator, users.r_id, exp_date, icon, rfid_no
            FROM `rfid`
            LEFT JOIN users
            ON rfid.operator = users.operator
            WHERE rfid.rfid_no = $rfid_no
            Limit 1;
        ")){
            $row = $result->fetch_assoc();
            $this->operator = $row['operator'];
            $this->exp_date = $row['exp_date'];
            $this->setRoleID($row['r_id']);
            $this->setRfid_no($row['rfid_no']);
            $this->icon = $row['icon'];
        } else {
            return false;
        }
    }
	
    public static function regexUser($operator) {
        //10 digit format check
        if (preg_match("/^\d{10}$/",$operator) == 0) {
            //echo "Invalid Operator ID:".$operator;
            return false;
        } else {
            return true;
        }
    }
    
    public function setAccounts($operator){
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
            echo $mysqli->error;
            return false;
        }
        
        //See if operator can use any default accounts
        //Based on $sv attributes for acct3 & acct4
        if ($sv['acct3'] <= $this->roleID){
            array_push($accounts, new Accounts(3));
        }
        if ($sv['acct4'] <= $this->roleID){
            array_push($accounts, new Accounts(4));
        }
        
        $this->accounts = $accounts;
    }
    
    public function getAccounts(){
        return $this->accounts;
    }
	
    public function getOperator(){
        return $this->operator;
    }
	
    public function getRoleID() {
        if ($this->roleID){
            return $this->roleID;
        } else {
            return false;
        }
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
    
    public function modifyRoleID($r_id, $staff, $notes){
        global $mysqli;

        if ($this->operator == $staff->getOperator()){
                return "Staff can not modify their own Role ID";
        }

        //concat staff ID onto notes for record keeping
        $notes = "|".$staff->getOperator()."| ".$notes;
		
        if ($staff->getRoleID() >= $r_id){
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
            echo "Insufficient Role";
        }
    }
	
    public function getExp_date(){
        return $this->exp_date;
    }

    public function setExp_date(){
        return "Function not Prepared Yet";
    }
	
    public function getRfid_no(){
        return $this->rfid_no;
    }

    public function setRfid_no($rfid_no){
        $this->rfid_no = $rfid_no;
        return "Function not Prepared Yet";
    }
	
    public function getIcon(){
        if ($this->icon == ""){
            return "user";
        }
        return $this->icon;
    }

    public function setIcon(){
        //check against known list
        return "Function not Prepared Yet";
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
 }?>