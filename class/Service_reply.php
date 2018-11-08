<?php
/*
 *   Jon Le 2016-2018
 *   FabApp V 0.91
 */

/**
 * Description of Service Reply
 * Provides updates on to service calls
 * @author Jon Le
 */


class Service_reply {
    private $sr_id;
    private $sc_id;
    private $sr_notes;
    private $sr_time;
    //objects
    private $staff;
    
    public function __construct($sr_id) {
        global $mysqli;
        
        if ($result = $mysqli->query("
            SELECT * 
            FROM `service_reply` 
            WHERE `sr_id` = $sr_id
            LIMIT 1;
        ")){
            if( $result->num_rows == 1){
                $row = $result->fetch_assoc();
                $this->setSr_id($row['sr_id']);
                $this->setSc_id($row['sc_id']);
                $this->setStaff($row['staff_id']);
                $this->setSr_notes($row['sr_notes']);
                $this->setSr_time($row['sr_time']);
            }
        }
    }
    
    public static function bySc_id($sc_id){
        global $mysqli;
        $sr_array = array();
        
        if($result = $mysqli->query("
            SELECT `sr_id`
            FROM `service_reply`
            WHERE `sc_id` = '$sc_id'
        ")){
            while($row = $result->fetch_assoc()){
                array_push( $sr_array, new self($row['sr_id']) );
            }
        }
        
        return $sr_array;
    }
    
    function getSr_id() {
        return $this->sr_id;
    }

    function getSc_id() {
        return $this->sc_id;
    }

    function getStaff() {
        return $this->staff;
    }

    function getSr_notes() {
        return $this->sr_notes;
    }

    function getSr_time() {
        global $sv;
        
        return date($sv['dateFormat'],strtotime($this->sr_time));
    }
    
    public static function insert_reply($staff, $sc_id, $sr_notes){
        global $mysqli;
        
        $staff_id = $staff->getOperator();
        $sr_notes = htmlspecialchars($sr_notes);
        
        if ( $stmt = $mysqli->prepare("
            INSERT INTO `service_reply` (`sc_id`, `staff_id`, `sr_notes`, `sr_time`)
            VALUES (?, ?, ?, CURRENT_TIMESTAMP);
        ")){
            if (!$stmt->bind_param("iss", $sc_id, $staff_id, $sr_notes))
                    return "Bind Error 88";
            if ($stmt->execute()){
                return true;
            } else {
                return "SR Execute Error 95";
            }
        } else {
            return "SR Prep Error 98";
        }
        
        return true;
    }

    function setSr_id($sr_id) {
        $this->sr_id = $sr_id;
    }

    function setSc_id($sc_id) {
        $this->sc_id = $sc_id;
    }

    function setStaff($staff) {
        if (is_object($staff)){
            $this->staff = $staff;
        } else {
            $this->staff = Users::withID($staff);
        }
    }

    function setSr_notes($sr_notes) {
        $this->sr_notes = $sr_notes;
    }

    function setSr_time($sr_time) {
        $this->sr_time = $sr_time;
    }
}
