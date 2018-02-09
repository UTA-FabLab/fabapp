<?php
/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 */

/**
 * Description of ObjBox
 *
 * @author Jon Le
 */
include_once ('AuthRecipients.php');

class ObjBox {
    private $o_id;
    private $o_start;
    private $o_end;
    private $address;
    private $user;
    private $transaction;
    private $staff;
    
    public function __construct($o_id){
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $o_id)){
            throw new Exception('Invalid Object ID');
        }
        
        if($result = $mysqli->query("
            SELECT *
            FROM `objox`
            WHERE `o_id` = '$o_id'
            LIMIT 1
        ")){
            $row = $result->fetch_assoc();
            $this->setO_id($row['o_id']);
            $this->setO_start($row['o_start']);
            $this->setO_end($row['o_end']);
            $this->setAddress($row['address']);
            $this->setUser($row['operator']);
            $this->setTrans_id($row['trans_id']);
            $this->setStaff($row['staff_id']);
        } else 
            throw new Exception('Invalid Object Call');
    }
    
    public static function byTrans($trans_id){
        global $mysqli;
        
        if ($result = $mysqli->query("
            SELECT *
            FROM `objbox`
            WHERE `trans_id` = '$trans_id'
            LIMIT 1;
        ")){
            $row = $result->fetch_assoc();
            try{
                $instance = new self($row['o_id']);
                return $instance;
            } catch (Exception $e){
                return false;
            }
        }
    }
    
    public static function findObj($user){
	global $mysqli;
        $instance = array();
        
        if (is_object($user)){
            $operator = $user->getOperator();
        }
        
        //find objects that belong to uta_id
	if ($result = $mysqli->query("
            Select `o_id`
            FROM `objbox` JOIN `transactions`
            ON `transactions`.`trans_id` = `objbox`.`trans_id`
            WHERE `transactions`.`operator` = '$operator' AND `objbox`.`o_end` IS NULL;
	")){
            //if result is zero Look at Auth Recipients Table
            $numRows = $result->num_rows;
            if(($numRows) == 0){
                $result->close();//close old result
                if ($result = $mysqli->query("
                    SELECT `o_id`
                    FROM `objbox` JOIN `authrecipients`
                    ON `objbox`.`trans_id` = `authrecipients`.`trans_id`
                    WHERE `authrecipients`.`operator` = '$operator' AND `objbox`.`o_end` is NULL;
                ")){
                    if(($result->num_rows) == 0){
                        return "No unclaimed objects found. Please look up their last Ticket By ID";
                    }
                } else {
                    return"AuthRecip Error - ar4734";
                }
            }
            while($row = $result->fetch_assoc()){
                array_push($instance, new self($row['o_id']));
            }
            return $instance;
        }
        return "ObjBox Query Error - o4734";
    }
    
    public function getO_id() {
        return $this->o_id;
    }

    public function getO_start() {
        return date('M d, Y g:i a',strtotime($this->o_start));
    }

    public function getO_end() {
        if ($this->o_end == "")
            return "";
        return date('M d, Y g:i a',strtotime($this->o_end));
    }

    public function getAddress() {
        return $this->address;
    }

    public function getUser() {
        return $this->user;
    }

    public function getTransaction() {
        return $this->transaction;
    }

    public function getStaff() {
        return $this->staff;
    }

    public static function insert_Obj($trans_id, $staff){
        global $mysqli;
        global $sv;
        $address = "";
        
        //Deny if user is not staff
        if($staff->getRoleID() < $sv['LvlOfStaff']){
            return "Must be staff in order to move into storage.";
        }
        
        if (!Transactions::regexTrans($trans_id)) return "Invalid Ticket #";
        
        //Check if Object already has a home
	if ($result = $mysqli->query("
            SELECT `address`, `t_end`
            FROM `transactions`
            LEFT JOIN `objbox`
            ON `transactions`.`trans_id` = `objbox`.`trans_id`
            WHERE `transactions`.`trans_id` = '$trans_id'
            LIMIT 1;
	")){
            //exit if so
            $row = $result->fetch_assoc();
            if ( isset($row["address"]) ){
                $address = $row["address"];
                //In lieu of an error just process as normal
                //   return "Ticket - $trans_id already been given an storage address. $address";
            } else 
                $t_end = $row["t_end"];
        } else { 
            return "ObjBox DB Error".$mysqli->error;
        }
        
        //Address already exists, Update insert time, remove `o_end`
        if ($address){
            //Generate New Address
            $address = self::suggestAddress();
            if ($mysqli->query("
                UPDATE `objbox`
                SET `o_start` = CURRENT_TIMESTAMP, `o_end` = NULL,
                    `address` = '$address', `staff_id` = ".$staff->getOperator()."
                WHERE `objbox`.`trans_id` = $trans_id;
            ")){
                return $mysqli->affected_rows;
            } else {
                return"objError - ".$mysqli->error;
            }
        } else {
            //Generate New Address
            $address = self::suggestAddress();
            // exit because ObjBox has Error
            if (strlen($address) > 2){
                return $address;
            }

            //Log moving item into ObjManager
            if ($mysqli->query("
                INSERT INTO objbox 
                    (`trans_id`,`o_start`,`address`,`staff_id`) 
                VALUES
                    ('$trans_id',CURRENT_TIMESTAMP,'$address','".$staff->getOperator()."')
            ")){
                return $mysqli->insert_id;
            } else {
                return"objError - ".$mysqli->error;
            }
        }
        
    }
    
    public static function inStorage(){
        global $mysqli;
        
        if($result = $mysqli->query("
            SELECT COUNT(*) as count
            FROM `objbox`
            WHERE `o_end` IS NULL;
        ")){
            $i = $result->fetch_assoc();
            return $i['count'];
        }
    }
    
    public static function lifetimeObj(){
        global $mysqli;
        
        if($result = $mysqli->query("
            SELECT COUNT(*) as count
            FROM `objbox`
            WHERE 1;
        ")){
            $i = $result->fetch_assoc();
            return $i['count'];
        }
    }
    
    public static function suggestAddress(){
        global $mysqli;
        global $sv;
        $occupied = array();
        $letter = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
        
        if ($result = $mysqli->query("
            SELECT `address`
            FROM `objbox`
            WHERE `o_end` IS NULL 
            ORDER BY `address`
        ")) {
            while ($row = $result->fetch_assoc()){
                array_push($occupied, $row["address"]);
            }
            $result->close();
            
            //If Occupied Addresses == Total # of possible Addresses ObjBox Must be Full
            if(count($occupied) == $sv["box_number"]*$sv["letter"])
                return "You seem to be full on Objects, please contact Admin to empty your ObjectBox Storage.";
            
            //sentinel loop - suggest check suggest
            $address = rand(1,$sv["box_number"]).$letter[rand(0,$sv["letter"]-1)];
            //Assume there will be a match
            $match = TRUE;
            $i=0;
            if (count($occupied) > 0){
                //searches for a match
                while($match == TRUE){
                    
                    //Tests for a match against all known Addresses
                    if (strcmp($address, $occupied[$i]) == 0){
                        //Address found, reset & compare from the beginning
                        $address = rand(1,$sv["box_number"]).$letter[rand(0,$sv["letter"])];
                        $i = 0;
                    }
                    $i++;
                    if($i == count($occupied))
                        $match = FALSE;
                }
            }
            return $address;
		} else {
			return $mysqli->error;
		}
    }
    
    public function pickedUpBy($user, $staff){
        global $mysqli;
        
        //Check if Operator is Allowed to pickup Print
        $msg = AuthRecipients::validatePickUp($this->transaction, $user->getOperator());
        if (is_string($msg)) {
            return $msg;
        }
        
        $quote = $this->transaction->quote();
        $ac_owed = Acct_charge::checkOutstanding($user->getOperator());
        if ($quote > .005 && isset($ac_owed[$this->transaction->getTrans_id()])){
            return "Error OB290 : Ticket has a Balance";
        }
        
        //Update ObjBox Table
        if ($mysqli->query("
            UPDATE `objbox`
            SET `operator` = '".$user->getOperator()."', `o_end` = CURRENT_TIMESTAMP, `staff_id` = '".$staff->getOperator()."'
            WHERE `trans_id` = ".$this->transaction->getTrans_id().";
        ")){
            return true;
        } else {
            return "Update Object Manager Error";
        }
    }

    private function setO_id($o_id) {
        $this->o_id = $o_id;
    }

    private function setO_start($o_start) {
        $this->o_start = $o_start;
    }

    public function setO_end($o_end) {
        $this->o_end = $o_end;
    }

    private function setAddress($address) {
        $this->address = $address;
    }

    public function setUser($operator) {
        if( is_object($operator)){
            $this->user = $operator;
        } elseif (Users::regexUser($operator))
            $this->user = Users::withID($operator);
        else 
            $this->user = null;
    }

    public function setTrans_id($trans_id) {
        $this->transaction = new Transactions($trans_id);
    }

    public function setStaff($staff_id) {
        $this->staff = Users::withID($staff_id);
    }
    
    //Set end timestamp to a Ticket who's status was reverted back to in progress
    public function unend($staff){
        global $mysqli;
        
        if($mysqli->query("
            UPDATE `objbox`
            SET `o_end` = CURRENT_TIMESTAMP, `staff_id` = '". $this->staff->getOperator()."'
            WHERE `o_id` = '".$this->o_id."'
            LIMIT 1;
        ")){
            return true;
        }
    }
    
    public function writeAttr(){
        global $mysqli;
        
        if (strcmp($this->o_end, "") == 0)
            $o_end = "NULL";
        else 
            $o_end = "'$this->o_end'";
        
        if ( is_object($this->user) )
            $user = "'".$this->user->getOperator()."'";
        else 
            $user = "NULL";
        
        if($mysqli->query("
            UPDATE `objbox`
            SET `o_end` = $o_end, `operator` = $user, `staff_id` = '". $this->staff->getOperator()."', `address` = '$this->address'
            WHERE `o_id` = '".$this->o_id."'
            LIMIT 1;
        ")){
            return true;
        }
        return $mysqli->error;
    }
}
