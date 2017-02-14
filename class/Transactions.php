<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */

/**
 * Transactions
 * A ticket is generated every time an operator uses a piece of equipment.
 * @author Jon Le
 */

include_once ($_SERVER['DOCUMENT_ROOT']."/class/Purpose.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Status.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Users.php");
 
class Transactions {
    public $device;
    private $duration;
    private $est_time;
    private $purpose;
    private $user;
    private $staff;
    private $status;
    private $t_start;
    private $t_end;
    private $trans_id;
    
    public function __construct($trans_id){
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $trans_id))
            throw new Exception('Invalid Ticket Number');
        if ($result = $mysqli->query("
            SELECT *
            FROM transactions
            WHERE trans_id = $trans_id
            LIMIT 1;
        ")){
            $row = $result->fetch_assoc();
            $this->setDevice($row['d_id']);
            $this->setDuration($row['duration']);
            $this->setEst_time($row['est_time']);
            $this->setUser($row['operator']);
            $this->setPurpose($row['p_id']);
            $this->setStaff($row['staff_id']);
            $this->setStatus($row['status_id']);
            $this->setT_end($row['t_end']);
            $this->setT_start($row['t_start']);
            $this->setTrans_id($row['trans_id']);
        }
        
    }
	
    public static function insertTrans($operator, $d_id, $est_time, $p_id, $status_id, $staff_id) {
        global $mysqli;

        //Validate input variables
        if (!Users::regexUser($operator)) return "Bad user ID";
        if (!Devices::regexDID($d_id))return "Bad Device";
        if (Devices::is_open($d_id)) return "Is Open";
        if (!self::regexTime($est_time)) return "Bad Time - $est_time";
        if (!Purpose::regexID($p_id)) return "Invalid Purpose";
        if (!Status::regexID($status_id)) return "Invalid Status";
        if (!Users::regexUser($staff_id)) return "Bad Staff ID";
        
        if ($mysqli->query("
            INSERT INTO transactions 
              (`operator`,`d_id`,`t_start`,`status_id`,`p_id`,`est_time`,`staff_id`) 
            VALUES
                ('$operator','$d_id',CURRENT_TIMESTAMP,'$status_id','$p_id','$est_time','$staff_id');
        ")){
            return $mysqli->insert_id;
        } else {
            return $mysqli->error;
        }
    }
    
    public static function regexTrans($trans_id){
        global $mysqli;
        
        if(!preg_match("/^\d+$/", $trans_id)){
            return false;
        }
        
        //Check to see if transaction exists
        if ($result = $mysqli->query("
            SELECT *
            FROM transactions
            WHERE trans_id = $trans_id
            LIMIT 1;
        ")){
            if ($result->num_rows == 1)
                return true;
            return false;
        } else {
            return false;
        }
    }

    public static function regexTime($duration) {
        if ( preg_match("/^\d{1,3}:\d{2}:\d{2}$/", $duration) == 1 )
            return true;
        return false;
    }
    
    public function end($status_id, $staff_id){
        global $mysqli;
        if(!Status::regexID($status_id)) return false;
        $this->setStatus($status_id);
        
        if(!Staff::regexUser($staff_id)) return false;
        $this->setStaff($staff_id);
        $trans_id = $this->getTrans_id();
        
        // Ticket has materials, log ending time & update status
        // in transactions and mats_used
        if ($this->duration == ""){
            //Transaction lacks ending time
            //So...let's give it one
            $query = "  UPDATE `transactions`
                        SET `t_end` = CURRENT_TIMESTAMP,
                            transactions.status_id = '$status_id',  transactions.staff_id = '$staff_id',
                            duration = SEC_TO_TIME (TIMESTAMPDIFF (SECOND, t_start, CURRENT_TIMESTAMP))
                        WHERE transactions.trans_id = $trans_id";
        } else {
            $query = "  UPDATE `transactions`
                        SET transactions.status_id = '$status_id',  transactions.staff_id = '$staff_id'
                        WHERE transactions.trans_id = $trans_id";
        }
        
        if($result = $mysqli->query($query)){
            if ($result = $mysqli->query("
                    SELECT duration
                    FROM transactions
                    WHERE transactions.trans_id = $trans_id;
            ")){
                $row = $result->fetch_assoc();
                $this->setDuration($row["duration"]);
                return true;
            } else
                return $mysqli->error;
        } else
            return $mysqli->error;
    }
    
    public function writeAttr(){
        global $mysqli;
        
        if (strcmp($this->t_end, "") == 0)
            $t_end = "NULL";
        else 
            $t_end = "'$this->t_end'";
        
        if (strcmp($this->est_time, "") == 0)
            $est_time = "NULL";
        else 
            $est_time = "'$this->est_time'";
        
        if (strcmp($this->duration, "") == 0)
            $duration = "NULL";
        else 
            $duration = "'$this->duration'";
        
        if($mysqli->query("
            UPDATE `transactions`
            SET `d_id` = '".$this->device->getD_id()."', `operator` = '".$this->user->getOperator()."', `est_time` = $est_time,
                `t_start` = '$this->t_start', `t_end` = $t_end, `duration` = $duration,
                `status_id` = '".$this->status->getStatus_id()."', `p_id` = '".$this->purpose->getP_id()."', `staff_id` = ".$this->staff->getOperator()."
            WHERE `trans_id` = '$this->trans_id'
            LIMIT 1;
        ")){
            return true;
        }
        //return $mysqli->error;
        return $mysqli->error." ~ ".$query;
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

    public function getEst_time() {
        return $this->est_time;
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

    public function getT_start() {
        return date('M d, Y g:i a',strtotime($this->t_start));
    }

    public function getT_end() {
        if (strcmp($this->duration,"") == 0)
                return ""; 
        return date('M d, Y g:i a',strtotime($this->t_end));
    }

    public function getTrans_id() {
        return $this->trans_id;
    }

    public function setDevice($d_id) {
        $this->d_id = $d_id;
        $this->device = new Devices($d_id);
    }

    public function setDuration($duration) {
        $this->duration = $duration;
    }

    public function setEst_time($est_time) {
        $this->est_time = $est_time;
    }

    public function setPurpose($p_id) {
        $this->purpose = new Purpose($p_id);
    }

    public function setUser($operator) {
        $this->user = Users::withID($operator);
    }

    public function setStaff($staff_id) {
        $this->staff = Users::withID($staff_id);
    }

    public function setStatus($status_id) {
        $this->status = new Status($status_id);
    }

    private function setT_start($t_start) {
        $this->t_start = $t_start;
    }

    private function setT_end($t_end) {
        $this->t_end = $t_end;
    }

    private function setTrans_id($trans_id) {
        $this->trans_id = $trans_id;
    }
}
?>