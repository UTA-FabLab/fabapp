<?php
/*
 *   CC BY-NC-AS UTA FabLab 2015-2016 
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
        $instance->createWithID($operator);
        return $instance;
    }

    public static function withRF($rfid_no){
        $instance = new self();
        $instance->createWithRF($rfid_no);
        return $instance;
    }

    public function createWithID($operator){
        global $mysqli;

        if (!$this->regexUser($operator)){
            return;
        }
        if ($result = $mysqli->query("
            SELECT users.operator, users.r_id, exp_date, icon, rfid_no
            FROM `users`
            LEFT JOIN rfid
            ON users.operator = rfid.operator
            WHERE users.operator = '$operator'
            Limit 1;
        ")){
            $row = $result->fetch_assoc();
            if (strcmp($row['operator'], "") != 0)
                $this->operator = $row['operator'];
            else 
                $this->operator = $operator;
            
            $this->exp_date = $row['exp_date'];
            $this->roleID = $row['r_id'];
            $this->rfid_no = $row['rfid_no'];
            $this->icon = $row['icon'];
            $this->setAccounts($operator);
        } else {
            echo $mysqli->error;
            return false;
        }
    }
	
    public function createWithRF($rfid_no){
        global $mysqli;

        if (preg_match("/^\d+$/",$rfid_no) == 0){
            echo "Bad RFID Number";
            return;
        }
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
            $this->roleID = $row['r_id'];
            $this->rfid_no = $row['rfid_no'];
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
        $accounts = array();
        
        if($result = $mysqli->query("
            SELECT `a_id`
            FROM `auth_accts`
            WHERE `auth_accts`.`operator` = '$operator' AND `valid` = 'Y';
        ")){
            while($row = $result->fetch_assoc()){
                array_push($accounts, new Accounts($row['a_id']));
            }
            $this->accounts = $accounts;
        } else {
            echo $mysqli->error;
            return false;
        }
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
	
    public function setRoleID($staff_id, $role){
        if (!$this->regexUser($staff_id))
            return; //bad Staff ID
        return "Function not Prepared yet";
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

    public function setRfid_no(){
        return "Function not Prepared Yet";
    }
	
    public function getIcon(){
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