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
include_once 'Materials.php';

class Mats_Used {
    private $mu_id;
    private $trans_id;
    private $m_id;
    private $unit_used;
    private $mu_date;
    private $status_id;
    private $staff_id;
    private $filename;
    private $mu_notes;
    private $material;
    
    public function __construct($mu_id) {
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $mu_id))
            throw new Exception('Invalid Ticket Number');
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
            $this->setStatus_id($row['status_id']);
            $this->setStaff_id($row['staff_id']);
            $this->setMu_notesDB($row['mu_notes']);
        }
    }
    
    public static function insert_Mats_used($trans_id, $m_id, $unit_used, $status_id, $staff_id, $mu_notes){
        global $mysqli;
        
        //Validate input variables
        if (!Transactions::regexTrans($trans_id)) return false;
        if (!Materials::regexID($m_id)) return false;
        if (!self::regexUnit_Used($unit_used)) return false;
        //invert amount to show consumption
        $unit_used = -$unit_used;
        if (!Users::regexUser($staff_id)) return false;
        
        if ($stmt = $mysqli->prepare("
            INSERT INTO mats_used
                (`trans_id`,`m_id`,`unit_used`, `status_id`, `staff_id`, `mu_notes`, `mu_date`) 
            VALUES
                (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP);
        ")){
            $bind_param = $stmt->bind_param("iidiss", $trans_id, $m_id, $unit_used, $status_id, $staff_id, $mu_notes);
            $stmt->execute();
            $insID = $stmt->insert_id;
            $stmt->close();
            return $insID;
        } else {
            echo "Error in stating Materials Used.";
            return false;
        }
    }
    
    public static function insert_Mats($trans_id, $m_id, $status_id, $staff_id){
        global $mysqli;
        
        //Validate input variables
        if (!Transactions::regexTrans($trans_id)) return false;
        if (!Materials::regexID($m_id)) return false;
        if (!Status::regexID($status_id)) return false;
        if (!Users::regexUser($staff_id)) return false;
        
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
    
    //When recieving materials into inventory
    public static function insert_Inventory($m_id, $inv_rec, $status_id, $staff_id){
        global $mysqli;
        
        
    }
    
    //Use this method for writing updates to the DB for the object
    public function writeAttr(){
        global $mysqli;
    
        //Combine $filename & $mu_notes
        if ($this->filename == "")
            $notes = $this->mu_notes;
        else
            $notes = "|".$this->filename."|".$this->mu_notes;
        
        //Update Mat_used
        if ($stmt = $mysqli->prepare("
            UPDATE mats_used
            SET `trans_id`= ?, `m_id`= ?, `unit_used`= ?, `mu_date` = CURRENT_TIMESTAMP,
                `status_id`= ?, `staff_id`=?, `mu_notes` = ?
            WHERE mu_id = ?;
        ")){
            $bind_param = $stmt->bind_param("sidissi", $this->trans_id, $this->m_id, $this->unit_used, $this->status_id, $this->staff_id, $notes, $this->mu_id);
            if ($stmt->execute() === true ){
                $row = $stmt->affected_rows;
                $stmt->close();
                return $row;
            } else
                return "mats_used Update Error";
        } else {
            return "Error in preparing mats_used statement";
        }
    }
    
    public static function regexUnit_Used($unit_used){
        if (preg_match("/^\d{0,4}\.{0,1}\d{0,2}$/", $unit_used) && $unit_used > 0)
            return true;
        echo "Invalid Amount Used - $unit_used";
        return false;
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

    public function &getMaterial() {
        return $this->material;
    }

    public function getUnit_used() {
        return $this->unit_used;
    }

    public function getMu_date() {
        return $this->mu_date;
    }

    public function getStatus_id() {
        return $this->status_id;
    }

    public function getStaff_id() {
        return $this->staff_id;
    }

    public function getMu_notes() {
        return $this->mu_notes;
    }

    public function getFilename() {
        return $this->filename;
    }
    
    private function setMu_id($mu_id) {
        $this->mu_id = $mu_id;
    }

    public function setTrans_id($trans_id) {
        $this->trans_id = $trans_id;
    }

    public function setM_id($m_id) {
        $this->m_id = $m_id;
    }

    public function setMaterial($m_id) {
        $this->material = new Materials($m_id);
    }

    public function setUnit_used($unit_used) {
        $this->unit_used = -$unit_used;
    }

    public function setMu_date($mu_date) {
        $this->mu_date = $mu_date;
    }

    public function setStatus_id($status_id) {
        $this->status_id = $status_id;
    }

    public function setStaff_id($staff_id) {
        $this->staff_id = $staff_id;
    }

    public function setMu_notes($mu_notes) {
        $this->mu_notes = $mu_notes;
    }

    private function setMu_notesDB($mu_notes) {
        
        //Break filename apart from Notes
        $sArray = explode("|", $mu_notes);
        
        if (count($sArray) == 3){
            if($sArray[0] == ""){
                $this->filename = $sArray[1];
            } else {
                $this->filename = "";
            }
            $this->mu_notes = $sArray[2];
        } else 
            $this->mu_notes = $mu_notes;
    }
}
