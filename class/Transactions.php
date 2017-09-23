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
    private $ac;
    private $mats_used;
    
    public function __construct($trans_id){
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $trans_id))
            throw new Exception("Invalid Ticket Number : $trans_id");
        
        if ($result = $mysqli->query("
            SELECT *
            FROM transactions
            WHERE trans_id = $trans_id
            LIMIT 1;
        ")){
            if ($result->num_rows == 0 ){
                throw new Exception("Ticket Not Found : $trans_id");
            }
            $row = $result->fetch_assoc();
            $this->setAc($trans_id);
            $this->setDevice($row['d_id']);
            $this->setDuration($row['duration']);
            $this->setEst_time($row['est_time']);
            $this->setUser($row['operator']);
            $this->setPurpose($row['p_id']);
            $this->setStaffWId($row['staff_id']);
            $this->setStatus_id($row['status_id']);
            $this->setT_end($row['t_end']);
            $this->setT_start($row['t_start']);
            $this->setTrans_id($row['trans_id']);
            $this->setMats_used($row['trans_id']);
        }
        
    }
	
    public static function insertTrans($operator, $d_id, $est_time, $p_id, $status_id, $staff) {
        global $mysqli;

        //Validate input variables
        if (!Devices::regexDID($d_id))return "Bad Device";
        if (Devices::is_open($d_id)) return "Is Open";
        if (!self::regexTime($est_time)) return "Bad Time - $est_time";
        if (!Purpose::regexID($p_id)) return "Invalid Purpose";
        if (!Status::regexID($status_id)) return "Invalid Status";
        
        if ($mysqli->query("
            INSERT INTO transactions 
              (`operator`,`d_id`,`t_start`,`status_id`,`p_id`,`est_time`,`staff_id`) 
            VALUES
                ('".$operator->getOperator()."','$d_id',CURRENT_TIMESTAMP,'$status_id','$p_id','$est_time','".$staff->getOperator()."');
        ")){
            return $mysqli->insert_id;
        } else {
            return $mysqli->error;
        }
    }
    
    //Returns {String if error, False if there is a cost, & True if ticket & Mats have been closed}
    public function end($status_id, $staff){
        global $mysqli;
        global $sv;
        $hasCost = false;
        $this->setStaff($staff);
        $this->setStatus_id($status_id);
        
        
        if ($this->getStatus()->getStatus_id() == 12) {
            //Check if notes are written to allow close as Failed
            foreach($this->mats_used as $mu){
                if(strlen($mu->getMu_notes()) < 10){
                    return "Please state a reason for marking this print as failed";
                }
            }
            
            if( $staff->getRoleID() < $sv['LvlOfStaff']){
                return "You are unable to close this ticket ".$this->getTrans_id();
            }
            
        //} elseif ($this->getStatus()->getStatus_id() == 14){
            // (otherwise) Allow patrons to self close we must check if payment is required
        } else {
            //Try to make this more general purpose
            //This will not work for a null Vinyl or screen print job
            //Check in end.php if DG.selectMatsFirst == N && count($dm) > 0
            /*
            if(count($this->mats_used) == 0){
                //Check to see if mats for that machine has an associated cost
                $device_mats = Materials::getDeviceMats($this->getDevice()->getDg()->getDg_id());
                foreach($device_mats as $dm){
                    if($dm["price"] > 0){
                        //We found what we are looking for, let's move on
                        //If status_id == 14, then exit as payment might be required
                        $hasCost = true;
                        break;
                    }
                }
            }
            */
            
            //If there is a remaining balance, exit
            //Sets Duration & end time
            $total = $this->quote();
            if ($total > 0){
                return false;
                //return "$".$total;
            }
            
            //If device group is storable, use move instead
            if($this->getDevice()->getDg()->getStorable() == "Y"){
                return false;
            }

            if( $staff->getRoleID() < $sv['LvlOfStaff'] && $staff->getOperator() != $this->getUser()->getOperator()){
                //Complete Status - no costs
                //$this->setStatus_id(14);
                return "You are unable to close this ticket ".$this->getTrans_id();
            }
        }
        
        // Log ending time & update status
        // of Ticket
        if (strcmp($this->duration,"") == 0){
            //Transaction lacks ending time
            //So...let's give it one
            $query = "  UPDATE `transactions`
                        SET `t_end` = CURRENT_TIMESTAMP,
                            transactions.status_id = '".$this->getStatus()->getStatus_id()."',  transactions.staff_id = '".$staff->getOperator()."',
                            duration = SEC_TO_TIME (TIMESTAMPDIFF (SECOND, t_start, CURRENT_TIMESTAMP))
                        WHERE transactions.trans_id = ".$this->getTrans_id();
        } else {
            $query = "  UPDATE `transactions`
                        SET transactions.status_id = '".$this->getStatus()->getStatus_id()."',  transactions.staff_id = '".$staff->getOperator()."'
                        WHERE transactions.trans_id = ".$this->getTrans_id();
        }
        
        if($result = $mysqli->query($query)){
            if ($result = $mysqli->query("
                    SELECT duration
                    FROM transactions
                    WHERE transactions.trans_id = ".$this->getTrans_id()
            )){
                $row = $result->fetch_assoc();
                $this->setDuration($row["duration"]);
                $result->close();
            } else {
                return $mysqli->error;
            }
        } else {
            return $mysqli->error;
        }
        
        foreach ($this->getMats_used() as $mu){
            $msg = $mu->end($this->getStatus()->getStatus_id(), $staff);
            if (is_string($msg)){
                //method states failure
                //return "Error Updating Material Used";
                return $msg;
            }
        }
        return true;
    }
    
    //This method is intended to restore a backup copy
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
            foreach($this->getMats_used() as $mu){
                $mu->writeAttr();
            }
            return true;
        } else {
            return $mysqli->error;
        }
    }

    public function getAc() {
        return $this->ac;
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

    public function getMats_used() {
        return $this->mats_used;
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
        global $sv;
        return date($sv['dateFormat'],strtotime($this->t_start));
        //return $this->t_start;
    }

    public function getT_end() {
        global $sv;
        if (strcmp($this->t_end, "") == 0)
                return "";
        return date($sv['dateFormat'],strtotime($this->t_end));
    }

    public function getTrans_id() {
        return $this->trans_id;
    }
    
    //return the Estimated cost for this ticket, set duration and t_end
    public function quote(){
        global $sv;
        $cost = 0;
        
        //Add up costs of materials
        foreach ($this->getMats_used() as $mu){
            $cost += $mu->getUnit_used() * $mu->getMaterial()->getPrice();
        }
        //Find the difference between right now and the start time
        //Format duration into standard form
        if ($this->getDuration()){
            $sArray = explode(":", $this->duration);
            $diff = $sArray[0] + $sArray[1]/60 + $sArray[2]/3600;
        } else {
            //Set End Time
            $this->setT_end(date("Y-m-d H:i:s", strtotime("now")));
            
            $diff = strtotime($this->t_end) - strtotime($this->t_start);
            $h = floor($diff / 3600);
            $m = $diff / 60 % 60;
            $s = $diff % 60;
            $this->setDuration("$h:$m:$s");
            $diff = $diff/3600;
        }
        
        //Minimum Time Interval
        if ($diff < $sv['timeInterval']){
            $diff = $sv['timeInterval'];
        }
        
        //echo sprintf("<br>Mats Cost = $%.2f", $cost);
        $cost += $diff * $this->getDevice()->getBase_price();
        
        //Take Current Cost - (what has already been paid for)
        return ($cost - $this->totalAC());
    }

    public static function regexTime($duration) {
        if ( preg_match("/^\d{1,3}:\d{2}:\d{2}$/", $duration) == 1 )
            return true;
        return false;
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

    public function setAc($trans_id) {
        $this->ac = Acct_charge::byTrans_id($trans_id);
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

    private function setMats_used($trans_id) {
        $this->mats_used = Mats_Used::byTrans($trans_id);
    }

    public function setPurpose($p_id) {
        $this->purpose = new Purpose($p_id);
    }

    public function setUser($operator) {
        $this->user = Users::withID($operator);
    }

    public function setStaffWId($staff_id) {
        $this->staff = Users::withID($staff_id);
    }

    public function setStaff($staff) {
        $this->staff = $staff;
    }

    public function setStatus_id($status_id) {
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
    
    public function totalAC(){
        $total = 0;
        foreach ($this->ac as $ac){
            $total += $ac->getAmount();
        }
        return $total;
    }
}
?>