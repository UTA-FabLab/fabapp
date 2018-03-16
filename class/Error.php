<?php

/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 */

/**
 * Description of Error_log
 * Capture Errors for things that are difficult to detect 
 * 
 * @author Jon Le
 */
require_once ($_SERVER['DOCUMENT_ROOT']."/connections/db_connect8.php");

class Error {
    private $e_id;
    private $e_time;
    private $page;
    private $msg;
    private $staff;
    
    public function __construct($e_id) {
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $e_id))
            throw new Exception('Invalid Error Number');
        if ($result = $mysqli->query("
            SELECT *
            FROM `error` 
            WHERE `e_id` = $e_id
            ORDER BY `e_time` DESC;
        ")){
            $row = $result->fetch_assoc();
            $this->setE_id($row['e_id']);
            $this->setE_time($row['e_time']);
            $this->setPage($row['page']);
            $this->setMsg($row['msg']);
            $this->setStaffWId($row['staff_id']);
        }
    }
    
    public static function insertError($page, $msg, $staff_id) {
        global $mysqli;
        
        if (strcmp($staff_id, "") == 0) {
            $staff_id = "NULL";
        } else {
            $staff_id = "'$staff_id'";
        }
        
        if ($stmt = $mysqli->prepare("
            INSERT INTO `error` (`e_time`, `page`, `msg`, `staff_id`) 
            VALUES (CURRENT_TIMESTAMP, ?, ?, ?);
        ")){
            $stmt->bind_param("sss", $page, $msg, $staff_id);
            $stmt->execute();
            $insID = $stmt->insert_id;
            $stmt->close();
            return $insID;
        } else {
            //return "Error in stating Materials Used.";
            return $mysqli->error;
        }
    }
    
    public static function getErrors(){
        global $mysqli;
        $error_array = array();
        
        if ($result = $mysqli->query("
            SELECT `e_id`
            FROM `error` 
            WHERE 1 
            ORDER BY `e_time` DESC;
        ")){
            while( $row = $result->fetch_assoc() ) {
                array_push($error_array, new self($row['e_id']));
            }
            return $error_array;
        } else {
            return false;
        }
    }

    public function getE_id() {
        return $this->e_id;
    }

    public function getE_time() {
        global $sv;
        return date($sv['dateFormat'],strtotime($this->e_time));
    }

    public function getPage() {
        return $this->page;
    }

    public function getMsg() {
        return $this->msg;
    }

    public function getStaff() {
        return $this->staff;
    }

    public function setE_id($e_id) {
        $this->e_id = $e_id;
    }

    public function setE_time($e_time) {
        $this->e_time = $e_time;
    }

    public function setPage($page) {
        $this->page = $page;
    }

    public function setMsg($msg) {
        $this->msg = $msg;
    }

    public function setStaffWId($staff_id) {
        $this->staff = Users::withID($staff_id);
    }

    public function setStaff($staff) {
        $this->staff = $staff;
    }
}
