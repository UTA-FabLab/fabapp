<?php
/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 */

/**
 * Description of Status
 * Refers to the different states that a transaction or material used can have
 * refer to the status table with in the DB
 * @author Jon Le
 */
class Status {
    private $status_id;
    private $msg;
    
    public function __construct($status_id) {
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $status_id))
            throw new Exception("Unable to set status");
        
        if ($result = $mysqli->query("
            SELECT *
            FROM status
            WHERE status_id = $status_id;
        ")){
            $row = $result->fetch_assoc();
            $this->setMsg($row['msg']);
            $this->setStatus_id($row['status_id']);
        } else {
            throw new Exception("Unable to set status");
        }
    }
    
    public static function getList(){
        global $mysqli;
        $sArray = array();
        
        if ($result = $myqsli->query("
            SELECT *
            FROM status
            WHERE 1;
        ")){
            while($row = $result->fetch_assoc()){
                $sArray[$row['status_id']] = $row['msg'];
            }
            $mysqli->close();
            return $sArray;
        } else {
            return false;
        }
    }
    
    public static function regexID($status_id){
        global $mysqli;

        if (!preg_match("/^\d+$/", $status_id))
            return false;

        //check to see if ID exists in table
        if($result = $mysqli->query("
            SELECT *
            FROM status
            WHERE status_id = $status_id
            LIMIT 1;
        ")){
            if ($result->num_rows == 1)
                return true;
        } else 
            return false;
    }


    public function getStatus_id() {
        return $this->status_id;
    }

    public function getMsg() {
        return $this->msg;
    }

    public function setStatus_id($status_id) {
        $this->status_id = $status_id;
    }

    public function setMsg($msg) {
        $this->msg = $msg;
    }
}
