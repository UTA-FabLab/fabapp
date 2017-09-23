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
    private $trans_id;
    private $staff;
    
    public function __construct($o_id){
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $o_id)){
            throw new Exception('Invalid Object ID');
        }
        
        if($result = $mysqli->query("
            SELECT *
            FROM ObjBox
            WHERE o_id = $o_id
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
            WHERE trans_id = $trans_id
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

    public static function insert_Obj($trans_id, $staff_id){
        global $mysqli;
        
        if (!Transactions::regexTrans($trans_id)) return "Invalid Ticket #";
        if (!Users::regexUser($staff_id)) return "Invalid Staff ID";
        
        //Check if Object already has a home
	if ($result = $mysqli->query("
            SELECT address, t_end
            FROM transactions
            LEFT JOIN objbox
            ON transactions.trans_id = objbox.trans_id
            WHERE transactions.trans_id = $trans_id
            LIMIT 1;
	")){
            //exit if so
            $row = $result->fetch_assoc();
            if ( isset($row["address"]) ){
                $address = $row["address"];
                return "Ticket - $trans_id already been given an storage address, $address";
            } else 
                $t_end = $row["t_end"];
        } else { 
            return "ObjBox DB Error".$mysqli->error;
        }
        
        //Generate Address
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
                ('$trans_id',CURRENT_TIMESTAMP,'$address','$staff_id')
        ")){
            return $mysqli->insert_id;
        } else {
            return"objError - ".$mysqli->error;
        }
    }
    
    public static function suggestAddress(){
        global $mysqli;
        global $sv;
        $occupied = array();
        $letter = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
        
        if ($result = $mysqli->query("
            SELECT address 
            FROM objbox 
            WHERE o_end IS NULL 
            ORDER BY address
        ")) {
            while ($row = $result->fetch_assoc()){
                array_push($occupied, $row["address"]);
            }
            $result->close();
            
            //If Occupied Addresses == Total # of possible Addresses ObjBox Must be Full
            if(count($occupied) == $sv["box_number"]*$sv["letter"])
                return "Your Seem to be full on Objects\n Please empty your Object Storage.";
            
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
    
    public static function findObj($operator){
	global $mysqli;
        $instance = array();
        
        if (!Users::regexUser($operator)) return "Invalid ID #";
        
        //find objects that belong to uta_id
	if ($result = $mysqli->query("
            Select o_id
            FROM objbox JOIN transactions
            ON transactions.trans_id = objbox.trans_id
            WHERE transactions.operator = '$operator' AND objbox.o_end IS NULL;
	")){
            //if result is zero Look at Auth Recipients Table
            $numRows = $result->num_rows;
            if(($numRows) == 0){
                $result->close();//close old result
                if ($result = $mysqli->query("
                    SELECT o_id
                    FROM objbox JOIN authrecipients
                    ON objbox.trans_id = authrecipients.trans_id
                    WHERE authrecipients.operator = '$operator' AND objbox.o_end is NULL;
                ")){
                    if(($result->num_rows) == 0){
                        return "No unclaimed objects found. \\nPlease look up their last Ticket By ID";
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
    
    public function pickedUpBy($operator, $staff_id){
        global $mysqli;
        
        if (!Users::regexUser($operator)) { return "Invalid Operator ID"; }
        if (!Users::regexUser($staff_id)) { return "Invalid Staff ID"; }
        
        //Check if Operator is Allowed to pickup Print
        $msg = AuthRecipients::validatePickUp($this->trans_id, $operator);
        if (is_string($msg)) {
            return $msg;
        }
        
        //update ObjBox Table
        if ($mysqli->query("
            UPDATE `objbox`
            SET `operator` = '$operator', `o_end` = CURRENT_TIMESTAMP, `staff_id` = $staff_id
            WHERE `trans_id` = $this->trans_id;
        ")){
            return true;
        } else {
            return "Update Object Manager Error";
        }
    }
    
    public static function inStorage(){
        global $mysqli;
        
        if($result = $mysqli->query("
            SELECT COUNT(*) as count
            FROM objbox
            WHERE o_end IS NULL;
        ")){
            $i = $result->fetch_assoc();
            return $i['count'];
        }
    }
    
    public static function lifetimeObj(){
        global $mysqli;
        
        if($result = $mysqli->query("
            SELECT COUNT(*) as count
            FROM objbox
            WHERE 1;
        ")){
            $i = $result->fetch_assoc();
            return $i['count'];
        }
    }
    
    public function writeAttr(){
        global $mysqli;
        
        if (strcmp($this->o_end, "") == 0)
            $o_end = "NULL";
        else 
            $o_end = "'$this->o_end'";
        
        if (strcmp($this->user->getOperator(), "") == 0)
            $user = "NULL";
        else 
            $user = "'".$this->user->getOperator()."'";
        
        if($mysqli->query("
            UPDATE `objbox`
            SET `o_id` = $o_end, `operator` = $user, `staff_id` = '". $this->staff->getOperator()."'
            WHERE `o_id` = $this->o_id
            LIMIT 1;
        ")){
            return true;
        }
        return $mysqli->error;
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

    public function getTrans_id() {
        return $this->trans_id;
    }

    public function getStaff() {
        return $this->staff;
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
        if (Users::regexUser($operator))
            $this->user = Users::withID($operator);
        else 
            $this->user = null;
    }

    public function setTrans_id($trans_id) {
        $this->trans_id = $trans_id;
    }

    public function setStaff($staff_id) {
        $this->staff = Users::withID($staff_id);
    }
}
