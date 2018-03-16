<?php
/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 */

/**
 * Description of Purpose
 *
 * @author Jon Le
 */
class Purpose {
    private $p_id;
    private $p_title;
    
    public function __construct($p_id) {
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $p_id)){
            $this->p_title = "Invalid Purpose Code";
        } else {
            if ($result = $mysqli->query("
                SELECT *
                FROM purpose
                WHERE p_id = $p_id;
            ")){
                $row = $result->fetch_assoc();
                $this->setP_id($row['p_id']);
                $this->setP_title($row['p_title']);
            }
        }
    }
    
    public static function getList(){
        global $mysqli;
        $sArray = array();
        
        if ($result = $mysqli->query("
            SELECT *
            FROM purpose
            WHERE 1;
        ")){
            while($row = $result->fetch_assoc()){
                $sArray[$row['p_id']] = $row['p_title'];
            }
            return $sArray;
        } else {
            return false;
        }
    }
    
    public static function regexID($p_id){
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $p_id))
            return false;
        
        //check to see if ID exists in table
        if($result = $mysqli->query("
            SELECT *
            FROM purpose
            WHERE p_id = $p_id
            LIMIT 1;
        ")){
            if ($result->num_rows == 1)
                return true;
            else
                return false;
        } else 
            return false;
    }
    
    public function getP_id() {
        return $this->p_id;
    }

    public function getP_title() {
        return $this->p_title;
    }

    public function setP_id($p_id) {
        $this->p_id = $p_id;
    }

    public function setP_title($p_title) {
        $this->p_title = $p_title;
    }
}
