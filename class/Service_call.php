<?php
/*
 *   Jon Le 2016-2018
 *   FabApp V 0.91
 */


class Service_call {
    private $sc_id;
    private $solved;
    private $sc_notes;
    private $sc_time;
    //Objects
    private $device;
    private $staff;
    private $sl;
    private $sr;
    
    public function __construct($sc_id) {
        global $mysqli;
        $this->sc_id = $sc_id;
        
        if ($result = $mysqli->query("
             SELECT *
             FROM `service_call`
             WHERE `sc_id` = '$sc_id';
        ")){
            $row = $result->fetch_assoc();
            $this->setDevice($row['d_id']);
            $this->setSc_id($sc_id);
            $this->setSc_notes($row['sc_notes']);
            $this->setSc_time($row['sc_time']);
            $this->setSl($row['sl_id']);
            $this->setSolved($row['solved']);
            $this->setSr($sc_id);
            $this->setStaff($row['staff_id']);
            $result->close();
        } else
            throw new Exception("Invalid Service Call ID");
    }
    
    public static function byDevice($device){
        global $mysqli;
        
        if ($result = $mysqli->query("
            SELECT `service_call`.`sc_id`, `staff_id`, `service_call`.`d_id`, `sl_id`, `sc_time`, `sc_notes`, `solved`, `device_desc`
            FROM `service_call`
            LEFT JOIN `devices`
            ON `service_call`.`d_id` = `devices`.`d_id`
            WHERE `service_call`.`d_id` = '".$device->getD_id()."' ORDER BY `sc_id` ASC
        ")){
            return $result;
        } else {
            return "";
        }
    }
    
    public static function call($staff, $device, $sl_id, $sc_notes){
        global $mysqli;
        
        $staff_id = $staff->getOperator();
        if (is_object($device)){
            $d_id = $device->getD_id();
        } elseif (is_string($device)) {
            return $device;
        } else {
            $d_id = $device;
        }
        $sc_notes = htmlspecialchars_decode($sc_notes);
        
        if ($sl_id == 1){
            //By default issues are marked complete, as they do not need require additional attention.
            $query = "INSERT INTO `service_call` (`staff_id`, `d_id`, `sl_id`, `solved`, `sc_notes`, `sc_time`)
                VALUES (?, ?, ?, 'Y', ?, CURRENT_TIMESTAMP);";
        } else {
            $query = "INSERT INTO `service_call` (`staff_id`, `d_id`, `sl_id`, `solved`, `sc_notes`, `sc_time`)
                VALUES (?, ?, ?, 'N', ?, CURRENT_TIMESTAMP);";
        }
        
        if ( $stmt = $mysqli->prepare($query)){
            if (!$stmt->bind_param("siis", $staff_id, $d_id, $sl_id, $sc_notes))
                    return "Bind Error 76";
            if ($stmt->execute()){
                return true;
            } else {
                return "SC Execute Error 80";
            }
        } else {
            return "SC Prep Error 83";
        }
    }
    
    private function changeStatus($status){
        global $mysqli;
        
        if ( $stmt = $mysqli->prepare("
            UPDATE `service_call`
            SET `solved` = 'Y'
            WHERE `sc_id` = ?
        ")){
            if (!$stmt->bind_param("i", $this->sc_id))
                return "Bind Error 94";
            if ($stmt->execute()){
                return true;
            } else {
                return "SC Execute Error 100";
            }
        } else {
            return "SC Prep Error 103";
        }
    }

    public function getSc_id() {
        return $this->sc_id;
    }

    public function getDevice() {
        return $this->device;
    }

    public function getStaff() {
        return $this->staff;
    }

    public function getSl() {
        return $this->sl;
    }

    public function getSolved() {
        return $this->solved;
    }

    public function getSc_notes() {
        return $this->sc_notes;
    }
    
    public function getSc_time() {
        global $sv;
        return date($sv['dateFormat'],strtotime($this->sc_time));
    }
    
    public function getSR(){
        return $this->sr;
    }
    
    public function insert_reply($staff, $status, $sl_id, $sr_notes){
        global $mysqli;
        
        if ($status == "complete"){
            $query = "  UPDATE `service_call` 
                        SET `solved` = 'Y'
                        WHERE `service_call`.`sc_id` = ".$this->getSc_id().";";
        } else {
            $query = "  UPDATE `service_call` 
                        SET `sl_id` = '$sl_id', `solved` = 'N'
                        WHERE `service_call`.`sc_id` = ".$this->getSc_id().";";
        }
        
        $msg = Service_reply::insert_reply($staff, $this->getSc_id(), $sr_notes);
        if (is_string($msg)){
            //display error message
            return $msg;
        }
        if ($result = $mysqli->query($query)){
            return true;
        } else {
            return "SC Error 167";
        }
    }
    
    public static function openSC(){
        global $mysqli;
        
        if ($result = $mysqli->query("
            SELECT `device_desc`, `sl_id`, `sc_id`, `staff_id`, `sc_time`, `sc_notes`, `solved`
            FROM `service_call`
            LEFT JOIN `devices`
            ON `service_call`.`d_id` = `devices`.`d_id`
            WHERE `solved` = 'N'
            ORDER BY `sc_id` ASC
        ")){
            return $result;
        } else {
            return "";
        }
    }

    public function setDevice($d_id) {
        $this->device = new Devices($d_id);
    }
    function setSc_id($sc_id) {
        $this->sc_id = $sc_id;
    }

    function setStaff($staff) {
        $this->staff = Staff::withID($staff);
    }

    function setSl($sl_id) {
        $this->sl = new Service_lvl($sl_id);
    }

    function setSolved($solved) {
        $this->solved = $solved;
    }

    function setSc_notes($sc_notes) {
        $this->sc_notes = htmlspecialchars_decode($sc_notes);
    }
    
    function setSr($sc_id){
        $this->sr = Service_reply::bySc_id($sc_id);
    }

    function setSc_time($sc_time) {
        $this->sc_time = $sc_time;
    }

}
