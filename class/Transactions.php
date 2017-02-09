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
include_once ("Users.php");
 
class Transactions {
    private $d_id;
    private $duration;
    private $est_time;
    private $p_id;
    private $operator;
    private $staff_id;
    private $status_id;
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
            $this->setD_id($row['d_id']);
            $this->setDuration($row['duration']);
            $this->setEst_time($row['est_time']);
            $this->setOperator($row['operator']);
            $this->setP_id($row['p_id']);
            $this->setStaff_id($row['staff_id']);
            $this->setStatus_id($row['status_id']);
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
        $this->setStatus_id($status_id);
        if(!Staff::regexUser($staff_id)) return false;
        $this->setStaff_id($staff_id);
        $trans_id = $this->getTrans_id();
        
        //check if transaction has a related material
        if ($result = $mysqli->query("
            SELECT *
            FROM mats_used
            WHERE trans_id = $trans_id;
        ")){
            $num_rows = $result->num_rows;

            //if rows then...
            if($num_rows > 0){
                // Ticket has materials, log ending time & update status
                // in transactions and mats_used
                if ($this->duration == ""){
                    //Transaction lacks ending time
                    //So...let's give it one
                    $query = "  UPDATE `transactions`, `mats_used`
                                SET `t_end` = CURRENT_TIMESTAMP,
                                    transactions.status_id = '$status_id',  transactions.staff_id = '$staff_id',
                                    mats_used.status_id = '$status_id', mats_used.staff_id = '$staff_id',
                                    duration = SEC_TO_TIME (TIMESTAMPDIFF (SECOND, t_start, CURRENT_TIMESTAMP))
                                WHERE transactions.trans_id = $trans_id AND transactions.trans_id = mats_used.trans_id; ";
                } else {
                    $query = "  UPDATE `transactions`, `mats_used`
                                SET transactions.status_id = '$status_id',  transactions.staff_id = '$staff_id',
                                    mats_used.status_id = '$status_id', mats_used.staff_id = '$staff_id',
                                WHERE transactions.trans_id = $trans_id AND transactions.trans_id = mats_used.trans_id; ";
                }
            } else {
                //Ticket has no materials, so only log ending time
                $query = "  UPDATE `transactions`
                            SET `t_end` = CURRENT_TIMESTAMP, 
                                transactions.status_id = $status_id, staff_id = $staff_id,
                                duration = SEC_TO_TIME (TIMESTAMPDIFF (SECOND, t_start, CURRENT_TIMESTAMP))
                            WHERE transactions.trans_id = $trans_id;";
            }
        } else {
            return $mysqli->error;
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
                return false;
        } else
            return false;
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
            SET `d_id` = '$this->d_id', `operator` = '$this->operator', `est_time` = $est_time,
                `t_start` = '$this->t_start', `t_end` = $t_end, `duration` = $duration,
                `status_id` = '$this->status_id', `p_id` = $this->p_id, `staff_id` = $this->staff_id
            WHERE `trans_id` = '$this->trans_id'
            LIMIT 1;
        ")){
            return true;
        }
        //return $mysqli->error;
        return $mysqli->error." ~ ".$query;
    }
    
    public function getD_id() {
        return $this->d_id;
    }

    public function getDuration() {
        return $this->duration;
    }

    public function getEst_time() {
        return $this->est_time;
    }

    public function getP_id() {
        return $this->p_id;
    }

    public function getOperator() {
        return $this->operator;
    }

    public function getStaff_id() {
        return $this->staff_id;
    }

    public function getStatus_id() {
        return $this->status_id;
    }

    public function getT_start() {
        return $this->t_start;
    }

    public function getT_end() {
        return $this->t_end;
    }

    public function getTrans_id() {
        return $this->trans_id;
    }

    public function setD_id($d_id) {
        $this->d_id = $d_id;
    }

    public function setDuration($duration) {
        $this->duration = $duration;
    }

    public function setEst_time($est_time) {
        $this->est_time = $est_time;
    }

    public function setP_id($p_id) {
        $this->p_id = $p_id;
    }

    public function setOperator($operator) {
        $this->operator = $operator;
    }

    public function setStaff_id($staff_id) {
        $this->staff_id = $staff_id;
    }

    public function setStatus_id($status_id) {
        $this->status_id = $status_id;
    }

    public function setT_start($t_start) {
        $this->t_start = $t_start;
    }

    public function setT_end($t_end) {
        $this->t_end = $t_end;
    }

    public function setTrans_id($trans_id) {
        $this->trans_id = $trans_id;
    }
}
?>