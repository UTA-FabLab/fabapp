<?php
/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
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
    private $m_id;
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
            $this->setM_id($row['m_id']);
            $this->setMaterial($row['m_id']);
            $this->setUnit_used($row['unit_used']);
            $this->setMu_date($row['mu_date']);
            $this->setStatus($row['status_id']);
            $this->setStaff($row['staff_id']);
            $this->setMu_notesDB($row['mu_notes']);
        }}
    
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
    
    public function end($status_id, $staff){
        global $mysqli;
        
        /* //Deny if there is a cost associated with the materials
        if($this->getMaterial()->getPrice() > 0.0){
            return "This material has a cost associated with it.";
        }
        */
        
        if ($mysqli->query("
            UPDATE `mats_used`
            SET `status_id` = '$status_id', `staff_id` = '".$staff->getOperator()."'
            WHERE `mu_id` = '".$this->getMu_id()."'
        ")){
            if ($mysqli->affected_rows == 1){
                $this->setStatus($status_id);
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
        global $sv;
        
        //Deny if user is not staff
        if($staff->getRoleID() < $sv['LvlOfStaff']){
            return "Must be staff in order to update";
        }
        
        //Validate input variables
        if (!Transactions::regexTrans($trans_id)) return false;
        if (!Materials::regexID($m_id)) return false;
        if (!Status::regexID($status_id)) return false;
        
        //If all is good in the hood lets make an entry
        if($result = $mysqli->query("
            INSERT INTO mats_used
                (`trans_id`,`m_id`,`status_id`, `staff_id`, `mu_date`) 
            VALUES
                ('$trans_id', '$m_id', '$status_id', '".$staff->getOperator()."', CURRENT_TIMESTAMP);
        ")){
            return $mysqli->insert_id;
        } else {
            //echo "Error in stating materials used";
            echo $mysqli->error;
            return false;
        }
    }
    
    //When recieving materials into inventory
    public static function insert_Inventory($m_id, $inv_rec, $status_id, $staff_id){
        global $mysqli;
        
    }
    
    public static function regexUnit_Used($unit_used){
        if (preg_match("/^\d{0,5}\.{0,1}\d{0,2}$/", $unit_used) && $unit_used >= 0)
            return true;
        //echo "Invalid Amount Used - $unit_used";
        return false;
    }
    
    //Use this method for writing updates to the DB for the object
    public function updateUsed($staff){
        global $mysqli;
        global $sv;
        
        //Deny if user is not staff
        if($staff->getRoleID() < $sv['LvlOfStaff']){
            return "Must be staff in order to update";
        }
    
        //Combine $header & $mu_notes
        if ($this->header == "" || $this->status->getStatus_id() == 12 || $this->status->getStatus_id() == 20) {
            $notes = $this->mu_notes;
        } else {
            $notes = "|".$this->header."|".$this->mu_notes;
        }
        $status_id = $this->status->getStatus_id();
        
        //invert value
        $uu = -$this->unit_used;
        
        //Update Mat_used
        if ($stmt = $mysqli->prepare("
            UPDATE `mats_used`
            SET `unit_used`= ?, `mu_date` = CURRENT_TIMESTAMP,
                `status_id`= ?, `staff_id` = ?, `mu_notes` = ?
            WHERE `mu_id` = ?;
        ")){
            $stmt->bind_param("dissi", $uu, $status_id, $staff, $notes, $this->mu_id);
            if ($stmt->execute() === true ){
                $row = $stmt->affected_rows;
                $stmt->close();
                return $row;
            } else
                return "mats_used: updateUsed Error";
        } else {
            return "Error in preparing mats_used: updateUsed statement";
        }
    }
    
    //Use this method for writing attributes to the DB for the object
    public function writeAttr(){
        global $mysqli;
    
        //Combine $header & $mu_notes
        if ($this->header == "" || $this->status->getStatus_id() == 12 || $this->status->getStatus_id() == 20) {
            $notes = $this->mu_notes;
        } else {
            $notes = "|".$this->header."|".$this->mu_notes;
        }
        $status_id = $this->status->getStatus_id();
        $operator = $this->staff->getOperator();
        
        if (strcmp($this->mu_date, "") == 0)
            $mu_date = "NULL";
        else 
            $mu_date = "$this->mu_date";
        
        //Update Mat_used
        if ($stmt = $mysqli->prepare("
            UPDATE mats_used
            SET `unit_used`= ?, `mu_date` = ?,
                `status_id`= ?, `staff_id`=?, `mu_notes` = ?
            WHERE mu_id = ?;
        ")){
            $stmt->bind_param("dsissi", $this->unit_used, $mu_date, $status_id, $operator, $notes, $this->mu_id);
            if ($stmt->execute() === true ){
                $row = $stmt->affected_rows;
                $stmt->close();
                return $row;
            } else {
                return "mats_used WriteAttr Error - ".$stmt->error;
            }
        } else {
            return "Error in preparing mats_used: WriteAttr statement";
        }
    }
    
    public function getMu_id() {
        return $this->mu_id;
    }

    public function getTrans_id() {
        return $this->trans_id;
    }

    public function getM_id() {
        return $this->m_id;
    }

    public function getMaterial() {
        return $this->material;
    }

    public function getUnit_used() {
        return $this->unit_used;
    }

    public function getMu_date() {
        global $sv; //$sv['dateFormat']
        
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

    public function setM_id($m_id) {
        $this->m_id = $m_id;
    }

    public function setMaterial($m_id) {
        $this->material = new Materials($m_id);
    }

    public function setMu_date($mu_date) {
        $this->mu_date = $mu_date;
    }

    public function setMu_notes($mu_notes) {
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

    public function setStatus($status_id) {
        try {
            $this->status = new Status($status_id);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function setStaff($staff_id) {
        $this->staff = Users::withID($staff_id);
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
}
