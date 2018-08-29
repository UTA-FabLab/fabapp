<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */

/**
 * Transactions
 * A ticket is generated every time an operator uses a piece of equipment.
 * @author Jon Le
 */
 
//Thermal Reciept Dependancies
require_once ($_SERVER['DOCUMENT_ROOT'].'/api/php_printer/autoload.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/connections/tp_connect.php');
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
 
class Transactions {
    private $duration;
    private $est_time;
    private $t_start;
    private $t_end;
    private $trans_id;
    //Objects
    private $ac;
    private $device;
    private $mats_used;
    private $purpose;
    private $staff;
    private $status;
    private $user;
    
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
    
    private function calc_duration(){
        $diff = strtotime($this->t_end) - strtotime($this->t_start);
        $h = floor($diff / 3600);
        $m = $diff / 60 % 60;
        $s = $diff % 60;
        if ($h > 800){
            $h = 800;
            $m = 0;
            $s = 0;
        }
        $this->setDuration("$h:$m:$s");
        return $diff/3600;
    }
    
    //If the values are different then we will update the the DB
    public function edit($err_catch, $d_id, $t_start, $t_end, $h, $m, $s, $operator, $status_id, $staff_id){
        $diff = 0;
        
        if ($this->device->getD_id() != $d_id){
            $diff = true;
            $this->setDevice($d_id);
        }
        if (strtotime($this->getT_start()) != strtotime($t_start)){
            $diff = true;
            $this->setT_start(date("Y-m-d H:i:s",strtotime($t_start)));
        }
        if (strtotime($this->getT_end()) != strtotime($t_end)){
            $diff = true;
            if($t_end == ""){
                $this->setT_end();
            } else {
                $this->setT_end(date("Y-m-d H:i:s",strtotime($t_end)));
            }
        }
        //Setup Test Duration Test - Either compare empty string or H:M:S time
        if ($h.$m.$s == "" && $t_end != "") {
            $diff = true;
            $this->calc_duration();
        } elseif ($h.$m.$s == "" && $status_id == 10){
            $test = "";
            if (strcmp($this->getDuration(), $test)){
                $diff = true;
                $this->setDuration("");
            }
        } else {
            $test = $h."h ".$m."m $s"."s";
            if (strcmp($this->getDuration(), $test) != 0){
                $diff = true;
                $this->setDuration("$h:$m:$s");
            }
        }
        
        if ($this->getUser()->getOperator() != $operator){
            $diff = true;
            $this->setUser($operator);
        }
        if ($this->getStatus()->getStatus_id() != $status_id){
            $diff = true;
            $this->setStatus_id($status_id);
        }
        if(is_object($this->getStaff())){
            if ($this->getStaff()->getOperator() != $staff_id){
                $diff = true;
                $this->setStaffWId($staff_id);
            }
        } else {
            $diff = true;
            $this->setStaffWId($staff_id);
        }
        
        if ($diff || $err_catch > 0){
            return $this->writeAttr();
        } else {
            return;
        }
    }
    
    //Returns {String if error, False if there is a cost, & True if ticket & Mats have been closed}
    public function end($status_id, $staff){
        global $mysqli;
        global $sv;
        $this->setStaff($staff);
        $this->setStatus_id($status_id);
        
        //If there is a remaining balance, exit
        //Sets Duration & end time
        $total = $this->quote("mats");
        if ($this->status->getStatus_id() != 12 && abs($total - 0.001) > .005){
            debug("Total $total");
            return false;
        }

        if(strcmp($staff->getOperator(), $this->getUser()->getOperator()) == 0 && $staff->getRoleID() < $sv['editTrans']){
            $errorMsg = "Please ask a staff member to close this ticket.";
        }
        
        $msg = $this->writeAttr();
        if (is_string($msg)){
            return $msg;
        }
        
        // Remove to allow for various statuses to be applied to each MU
        foreach ($this->getMats_used() as $mu){
            //Change Status and Assign Staff
            $msg = $mu->end($this->getStatus()->getStatus_id(), $staff);
            if (is_string($msg)){
                //method states failure
                //return "Error Updating Material Used";
                return $msg;
            }
        }
        return true;
    }
    
    public function end_juicebox(){
        global $mysqli;
        
        $total = $this->quote("mats");
        if (abs($total - 0.001) > .005){
            //Status = Moveable
            //Intended to block additional Power On Until Learner Pays Balance
            // Alt logic, payments get placed into a "tab"
            $status_id = 11;
        } else {
            $status_id = 14;
        }
        if ($mysqli->query("
            UPDATE `transactions`
            SET `t_end` = '".$this->t_end."', `duration` = '".$this->duration."', `status_id` = '$status_id'
            WHERE `trans_id` = '".$this->trans_id."';
        ")){
            if ($mysqli->affected_rows == 1){
                return true;
            } else {
                return "Check end_juicebox.php";
            }
        } else {
            return "Update Error T130";
        }
    }
    
    public function end_octopuppet(){
        global $mysqli;
        $status_id = 11;
		
        $this->quote("mats");
        if ($mysqli->query("
            UPDATE `transactions`
            SET `t_end` = '".$this->t_end."', `duration` = '".$this->duration."', `status_id` = '$status_id'
            WHERE `trans_id` = '".$this->trans_id."	';
        ")){
            if ($mysqli->affected_rows == 1){
                return true;
            } else {
                return false;
            }
        } else {
            return false;
            //return "End OctoPuppet Error T150";
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
        
        Wait_queue::transferFromWaitQueue($operator->getOperator(), $d_id);
        
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
    
    public function getDuration_raw() {
        if (strcmp($this->duration,"") == 0)
                return "";
        return $this->duration;
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
    }

    public function getT_start_picker() {
        return date("m/d/Y g:i a",strtotime($this->t_start));
    }

    public function getT_end() {
        global $sv;
        if (strcmp($this->t_end, "") == 0)
            return "";
        return date($sv['dateFormat'],strtotime($this->t_end));
    }

    public function getT_end_picker() {
        if (strcmp($this->t_end, "") == 0)
            return "";
        return date("m/d/Y g:i a",strtotime($this->t_end));
    }

    public function getTrans_id() {
        return $this->trans_id;
    }
    
    public function move($staff){
        global $mysqli;
        global $sv;
        $this->setStaff($staff);
	$letter = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");

        if( $staff->getRoleID() < $sv['LvlOfStaff']){
            return "You are unable to alter this ticket ".$this->getTrans_id();
        }
        
        //Update Transaction with End time, Duration, and Status
        if ($this->writeAttr() === true){
            //Log moving item into ObjManager
            return ObjBox::insert_Obj($this->trans_id, $staff);
        } else {
            return "T244: moveError ";
        }
    }
	
    public static function printTicket($trans_id){
        global $mysqli;
        global $tp;
        $est_cost = 0;

        try {
            //Pull Ticket Related Information
            $ticket = new self($trans_id);
        } catch(Exception $e) {
            echo $e;
            return $e;
        }
        
        try {
            $tpn = $ticket->getDevice()->getDg()->getThermalPrinterNum();
            $connector = new NetworkPrintConnector( $tp[$tpn][0], $tp[$tpn][1]);
            $printer = new Printer($connector);
        } catch (Exception $e) {
            return "Couldn't print to this printer: " . $e -> getMessage() . "\n";
        }

        try {
            // Print Generic Header
            $img = EscposImage::load($_SERVER['DOCUMENT_ROOT']."/images/fablab2.png", 0);
            $printer -> setJustification(Printer::JUSTIFY_CENTER);
            $printer -> graphics($img);
            $printer -> feed();
            $printer -> text($ticket->getT_start());
            $printer -> feed();
            $printer -> text("Ticket: " . $ticket->getTrans_id());
            $printer -> feed();
            //Body
            $printer -> feed();
            $printer -> text("Device:   ".$ticket->getDevice()->getDevice_desc());
            //Print Each Material
            foreach ($ticket->getMats_used() as $mu) {
                $printer -> feed();
                $printer -> text("Material:   ".$mu->getMaterial()->getM_name());

                $filename = $mu->getHeader();
                $printer -> feed();
                if ($mu->getUnit_used() > 0){
                    $printer -> text("Est. Amount:   ".$mu->getUnit_used()." ".$mu->getMaterial()->getUnit());
                    //Calculate Cost
                    $est_cost += $mu->getMaterial()->getPrice() * $mu->getUnit_used();
                } elseif (isset($est_amount)) {
                    $printer -> text("Est. Amount:   ".$est_amount." ".$mu->getMaterial()->getUnit());
                    //Calculate Cost
                    $est_cost += $mu->getMaterial()->getPrice() * $est_amount;
                }
            }
            $printer -> feed();
            $printer -> text("Est. Cost:   ");
            $printer -> text("$ ".number_format($est_cost,2));
            $printer -> feed();
            $printer -> text("Est. Duration:   ".$ticket->getEst_time());
            if ($filename){
                $printer -> feed();
                $printer -> text("File:   ".$filename);
            }
            $printer -> feed(3);
            $printer -> text("Address: ______________________");
            $printer -> feed();
            $printer -> text("Potential Problems?  ( Y )  ( N )");
            $printer -> feed();
            $printer -> text("NOTES: _________________________");
            $printer -> feed(2);
            $printer -> text("________________________________");
            $printer -> feed(2);
            $printer -> text("________________________________");
            $printer -> feed(3);
            $printer -> graphics(EscposImage::load($_SERVER['DOCUMENT_ROOT']."/images/sig.png", 0));
            //EscposImage::load($_SERVER['DOCUMENT_ROOT']."/images/fablab2.png", 0);
			
            $printer -> feed();
            $printer -> text("http://fablab.uta.edu/");
            $printer -> feed();
            $printer -> text("(817) 272-1785");
            $printer -> feed(2);
            $printer -> cut();
        } catch (Exception $print_error) {
            return $print_error->getMessage();
        }

        try {
            /* Close printer */
            $printer -> close();
        } catch( Exception $e) {
            echo "printer was not open";
        }
    }
    
    //return the Estimated cost for this ticket, set duration and t_end
    public function quote($str){
        global $sv;
        $cost = 0;
        
        //Decide of we need to include the cost of materials in this calculation
        if ($str == "mats"){
            //Add up costs of materials
            foreach ($this->getMats_used() as $mu){
                if ($mu->getStatus()->getStatus_id() != 12){
                    $cost += $mu->getUnit_used() * $mu->getMaterial()->getPrice();
                }
            }
        }
        //Find the difference between right now and the start time
        //Format duration into standard form
        if ($this->getDuration()){
            $sArray = explode(":", $this->duration);
            $diff = $sArray[0] + $sArray[1]/60 + $sArray[2]/3600;
        } else {
            //Set End Time
            $this->setT_end(date("Y-m-d H:i:s", strtotime("now")));
            
            $diff = $this->calc_duration();
        }
        
        //Minimum Time Interval
        if ($diff < $sv['minTime']){
            $diff = $sv['minTime'];
        }
        
        //echo sprintf("<br>Mats Cost = $%.2f", $cost);
        $cost += $diff * $this->getDevice()->getBase_price();
        
        //Take Current Cost - (what has already been paid for)
        //Add .0001 to prevent negative rounding errors
        return ($cost - $this->totalAC() + .001);
    }

    public static function regexTime($duration) {
        if ( preg_match("/^\d{1,3}:\d{2}:\d{2}$/", $duration) == 1 )
            return true;
        return false;
        $sArray = explode(":", $duration);
        $h = $sArray[0] + $sArray[1]/60;
        if ($h === 0){
            // Total Time is 0
            return false;
        }
        return true;
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
        foreach (Acct_charge::byTrans_id($this->trans_id) as $ac){
            if ($ac->getAccount()->getA_id() == 1 ){
                //Do not include OutStanding Charges
            } else {
                $total += $ac->getAmount();
            }
        }
        return $total;
    }
    
    //Writes all variables to the DB for a given Transaction
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
        
        if (strcmp($this->purpose->getP_id(), "") == 0)
            $purpose = "NULL";
        else 
            $purpose = "'".$this->purpose->getP_id()."'";
        
        if (is_object($this->staff)){
            $staff_id = "'".$this->staff->getOperator()."'";
        } else {
            $staff_id = "NULL";
        }
        
        if($mysqli->query("
            UPDATE `transactions`
            SET `d_id` = '".$this->device->getD_id()."', `operator` = '".$this->user->getOperator()."', `est_time` = $est_time,
                `t_start` = '$this->t_start', `t_end` = $t_end, `duration` = $duration,
                `status_id` = '".$this->status->getStatus_id()."', `p_id` = $purpose, `staff_id` = $staff_id
            WHERE `trans_id` = '$this->trans_id'
            LIMIT 1;
        ")){
            foreach($this->getMats_used() as $mu){
                $str = $mu->writeAttr();
            }
            if (is_string($str)){
                return $str;
            } else {
                return true;
            }
        } else {
            return $mysqli->error;
        }
    }
}
?>