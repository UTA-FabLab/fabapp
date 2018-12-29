<?php
/*
 *   Jon Le 2016-2018
 *   FabApp V 0.91
 */

/**
 * Description of mats_used
 *
 * @author Jon Le
 */
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Materials.php");

class Mats_Used {
    private $mu_id;
    private $trans_id;
    private $unit_used;
    private $mu_date;
    private $status;
    private $staff;
    private $header;
    private $mu_notes;
    private $material;
    
    public function __construct($mu_id){
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $mu_id))
            {throw new Exception('Invalid Ticket Number');}
        if ($result = $mysqli->query("
            SELECT *
            FROM `mats_used`
            WHERE `mu_id` = $mu_id
            LIMIT 1;
        ")){
            $row = $result->fetch_assoc();
            $this->setMu_id($row['mu_id']);
            $this->setTrans_id($row['trans_id']);
            $this->setMaterial($row['m_id']);
            $this->setUnit_used($row['unit_used']);
            $this->setMu_date($row['mu_date']);
            $this->setStatus_id($row['status_id']);
            $this->setStaff($row['staff_id']);
            $this->setMu_notesDB($row['mu_notes']);
        }
    }
    
    public static function byTrans($trans_id){
        global $mysqli;
        $muArray = array();
        
        if ($result = $mysqli->query("
            SELECT *
            FROM mats_used
            WHERE trans_id = $trans_id;
        ")){
            while($row = $result->fetch_assoc()){
                array_push( $muArray, new self($row['mu_id']) );
            }
        }
        return $muArray;
    }
    
    //If the values are different then we will update the the DB
    //Changes are committed through the Transaction::Edit()
    public function edit($m_id, $uu, $status_id, $staff_id, $mu_notes){
        $diff = 0;
        
        if ($this->getMaterial()->getM_id() != $m_id){
            $diff = true;
            $this->setMaterial($m_id);
        }
        if ($this->getUnit_used() != $uu){
            $diff = true;
            $this->setUnit_used($uu);
        }
        if ($this->getStatus()->getStatus_id() != $status_id){
            $diff = true;
            $this->setStatus_id($status_id);
        }
        if(is_object($this->getStaff())){
            if ($this->getStaff()->getOperator() != $staff_id){
                $diff = true;
                $this->setStaff($staff_id);
            }
        } else {
            $diff = true;
            $this->setStaff($staff_id);
        }
        if ($this->getMu_notes() != $mu_notes){
            $diff = true;
            $this->setMu_notes($mu_notes);
        }
        
        if ($diff ==true){
            $this->setMu_date("CURRENT_TIMESTAMP");
        }
        //Send Delta to let transactions know to update the ticket
        return $diff;
    }
    
    public function end($status_id, $staff){
        global $mysqli;
        
        //Deny if there is a cost associated with the materials
        if($status_id != 12 && $this->getMaterial()->getPrice() > 0.005){
            return "This material has a cost associated with it.";
        }
        
        if ($mysqli->query("
            UPDATE `mats_used`
            SET `status_id` = '$status_id', `staff_id` = '".$staff->getOperator()."', `mu_date` = CURRENT_TIMESTAMP
            WHERE `mu_id` = '".$this->getMu_id()."'
        ")){
            if ($mysqli->affected_rows == 1){
                $this->setStatus_id($status_id);
                return TRUE;
            } else {
                return "MU error line 85";
            }
        } else {
            return $mysqli->error;
        }
    }
    
    //This method indicates how much of a material was used
    public static function insert_Mats_used($trans_id, $m_id, $unit_used, $status_id, $staff, $mu_notes){
        global $mysqli;
        global $sv;
        
        //Deny if user is not staff
        if($staff->getRoleID() < $sv['LvlOfStaff']){
            return "Must be staff in order to update";
        }
        
        //Validate input variables
        if (!Transactions::regexTrans($trans_id)) return false;
        if (!Materials::regexID($m_id)) return false;
        if (!self::regexUnit_Used($unit_used)) return false;
        //invert amount to show consumption
        $unit_used = -$unit_used;
        
        //scrub $mu_notes
        $mu_notes = htmlspecialchars($mu_notes);
        
        if ($stmt = $mysqli->prepare("
            INSERT INTO mats_used
                (`trans_id`,`m_id`,`unit_used`, `status_id`, `staff_id`, `mu_notes`, `mu_date`) 
            VALUES
                (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP);
        ")){
            $bind_param = $stmt->bind_param("iidiss", $trans_id, $m_id, $unit_used, $status_id, $staff->getOperator(), $mu_notes);
            $stmt->execute();
            $insID = $stmt->insert_id;
            $stmt->close();
            return $insID;
        } else {
            //return "Error in stating Materials Used.";
            return $mysqli->error;
        }
    }


	public static function insert_Mats($trans_id, $m_id, $status_id, $staff){
        global $mysqli;
        
        //Validate input variables
        if (!Transactions::regexTrans($trans_id)) return false;
        if (!Materials::regexID($m_id)) return false;
        if (!Status::regexID($status_id)) return false;
		
        $staff_id = $staff->getOperator();
        
        //If all is good in the hood lets make an entry
        if($result = $mysqli->query("
            INSERT INTO mats_used
                (`trans_id`,`m_id`,`status_id`, `staff_id`, `mu_date`) 
            VALUES
                ($trans_id, $m_id, $status_id, $staff_id, CURRENT_TIMESTAMP);
        ")){
            return $mysqli->insert_id;
        } else {
            //echo "Error in stating materials used";
            echo $mysqli->error;
            return false;
        }
    }


    public static function units_in_system($m_id) {
        global $mysqli;

        if (preg_match("/^\d+$/", $m_id)) {
            if($result = $mysqli->query("
                SELECT SUM(unit_used) as `sum`
                FROM `mats_used`
                WHERE `m_id` = '$m_id';
            "))
                return $result->fetch_object()->sum;
        }
        return false;
    }

    //TODO: adjust so that the increase quantity is stated instead of amount adjusted to
    public static function update_mat_quantity($m_id, $quantity, $reason, $staff, $status) {
        global $mysqli;
        if($staff->getRoleID() < $sv['LvlOfLead']) return false;

        $m_id = Mats_Used::regexMatID($m_id);
        $quantity = Mats_Used::regexQuantity($quantity);
        $status = Mats_Used::regexStatus($status);
        $reason = Mats_Used::regexReason($reason);

        if($m_id && $quantity && $status) {
            if($mysqli->query("
                INSERT INTO `mats_used`
                    (`m_id`, `unit_used`, `mu_date`, `status_id`, `operator`, `mu_notes`) 
                VALUES
                    ('$m_id', '$quantity', CURRENT_TIME(), '$status', '".$staff->getOperator()."', '".$reason."');
            "))
                return true;
        }
        return false;
    }


    
    public static function regexUnit_Used($unit_used){
        if (preg_match("/^\d{0,5}\.{0,1}\d{0,2}$/", $unit_used) && $unit_used >= 0)
            return true;
        //echo "Invalid Amount Used - $unit_used";
        return false;
    }


    public static function regexMatID($m_id) {
        if(preg_match("/^\d+$/", $m_id)) return intval($m_id);
        return false;
    }


    public static function regexReason($reason) {
        return htmlspecialchars($reason);
    }


    public static function regexStatus($status) {
        if(preg_match("/^\d+$/", $status)) return intval($status);
        return false;     
    }


    public static function regexQuantity($quantity) {
        if(preg_match('/^[0-9]{1,7}+(\.[0-9]{1,2})?$/', $quantity) || is_numeric($quantity)) 
            return floatval($quantity);
        return false;
    }    


    
    public function getMu_id() {
        return $this->mu_id;
    }

    public function getTrans_id() {
        return $this->trans_id;
    }

    public function getMaterial() {
        return $this->material;
    }

    public function getUnit_used() {
        return $this->unit_used;
    }

    public function getMu_date() {
        //Pull format setting from DB's site varibles
        global $sv;
        
        return date($sv['dateFormat'],strtotime($this->mu_date));
    }

    public function getStatus() {
        return $this->status;
    }

    public function getStaff() {
        return $this->staff;
    }

    public function getMu_notes() {
        return $this->mu_notes;
    }

    public function getHeader() {
        return $this->header;
    }
    
    public function setHeader($header){
        $this->header = str_replace("|", "l", $header);
    }
    private function setMu_id($mu_id) {
        $this->mu_id = $mu_id;
    }

    public function setMaterial($m_id) {
        $this->material = new Materials($m_id);
    }

    public function setMu_date($mu_date) {
        $this->mu_date = $mu_date;
    }

    public function setMu_notes($mu_notes) {
        $mu_notes = htmlspecialchars($mu_notes);
        $this->mu_notes = str_replace("|", "l", $mu_notes);
    }

    private function setMu_notesDB($mu_notes) {
        //Break header apart from Notes
        $sArray = explode("|", $mu_notes);
        
        if (count($sArray) == 3){
            if($sArray[0] == ""){
                $this->header = $sArray[1];
            } else {
                $this->header = "";
            }
            $this->mu_notes = $sArray[2];
        } else 
            $this->mu_notes = $mu_notes;
    }

    public function setStatus_id($status_id) {
        try {
            $this->status = new Status($status_id);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function setStaff($staff_id) {
        if (is_object($staff_id)){
            $this->staff = $staff_id;
        } else {
            $this->staff = Users::withID($staff_id);
        }
    }

    public function setUnit_used($unit_used) {
        $unit_used = abs($unit_used);
        if (!self::regexUnit_Used($unit_used)) 
            return "Invalid Amount Used - $unit_used";
        $this->unit_used = $unit_used;
        return true;
    }

    public function setTrans_id($trans_id) {
        $this->trans_id = $trans_id;
    }
	
    //Use this method for writing attributes to the DB for the object
    public function writeAttr(){
        global $mysqli;
    
        $m_id = $this->getMaterial()->getM_id();
        $mu_date = "$this->mu_date";
        //Combine $header & $mu_notes
        //Drop header if all is well
        if ($this->header == "" || $this->status->getStatus_id() >= 20) {
            $notes = $this->mu_notes;
        } else {
            $notes = "|".$this->header."|".$this->mu_notes;
        }
        $status_id = $this->status->getStatus_id();
        
        if ( is_object($this->staff) ) {
            $staff_id = $this->staff->getOperator();
        } elseif ( is_int($this->staff) ) {
            $staff_id = $this->staff;
        } else {
            $staff_id = NULL;
        }
        
        if (strcmp($this->unit_used, "") == 0){
            $unit_used = NULL;
        } else {
            if ($this->trans_id){
                $unit_used = "-$this->unit_used";
            } else {
                $unit_used = "$this->unit_used";
            }
        }
        
        //Either Update or Not
        if ($this->mu_date == "CURRENT_TIMESTAMP"){
            $stmt = "UPDATE `mats_used`
            SET `m_id`= ?, `unit_used`= ?, `mu_date` = CURRENT_TIMESTAMP,
                `status_id`= ?, `staff_id`=?, `mu_notes` = ?
            WHERE `mu_id` = ?;";
        } else {
            $stmt = "UPDATE `mats_used`
            SET `m_id`= ?, `unit_used`= ?, `mu_date` = ?,
                `status_id`= ?, `staff_id`= ?, `mu_notes` = ?
            WHERE `mu_id` = ?;";
        }
        //Update Mat_used
        if ($stmt = $mysqli->prepare($stmt)){
            if ($this->mu_date == "CURRENT_TIMESTAMP"){
                $stmt->bind_param("idissi", $m_id, $unit_used, $status_id, $staff_id, $notes, $this->mu_id);
            } else {
                $stmt->bind_param("idsissi", $m_id, $unit_used, $mu_date, $status_id, $staff_id, $notes, $this->mu_id);
            }
            if ($stmt->execute() === true ){
                $row = $stmt->affected_rows;
                $stmt->close();
                return $row;
            } else {
                return "mats_used WriteAttr Error - ".$stmt->error." m_id:".$m_id;
            }
        } else {
            return "Error in preparing mats_used: WriteAttr statement";
        }
    }
}
