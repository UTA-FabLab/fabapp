<?php

/*
 * FabApp V 0.91
 * 2016-2018 Jon Le
 */

class Service_lvl {
    private $sl_id;
    private $msg;
    
    public function __construct($sl_id) {
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $sl_id))
            throw new Exception("Unable to set status");
        
        if ($result = $mysqli->query("
            SELECT *
            FROM `service_lvl`
            WHERE `sl_id` = '$sl_id';
        ")){
            $row = $result->fetch_assoc();
            $this->setMsg($row['msg']);
            $this->setSl_id($sl_id);
        } else {
            throw new Exception("Unable to set status");
        }
    }
    
    public static function getList(){
        global $mysqli;
        $slArray = array();
        
        if ($result = $mysqli->query("
            SELECT `sl_id`
            FROM `service_lvl`
            WHERE 1;
        ")){
            while($row = $result->fetch_assoc()){
                array_push($slArray, new self($row['sl_id']));
            }
            return $slArray;
        } else {
            return false;
        }
    }
    
    public static function getDot($sl_id){
        $icon = "circle";
        
        if($sl_id == 1) {
            $color = "green";
        } elseif($sl_id < 7) {
            $color = "yellow";
        } else {
            //7+ is Non-useable
            $color = "red";
            $icon = "times";
        }

        echo "<i class='fas fa-$icon fa-lg' style='color:".$color."' id='sl_dot'></i>&nbsp;";
    }
    
    public static function regexID($sl_id){
        global $mysqli;

        if (!preg_match("/^\d+$/", $sl_id))
            return false;

        //check to see if ID exists in table
        if($result = $mysqli->query("
            SELECT *
            FROM `service_lvl`
            WHERE `sl_id` = '$sl_id';
        ")){
            if ($result->num_rows == 1)
                return true;
        } else 
            return false;
    }
    
    public static function sltoMsg($sl_id){
        global $mysqli;
        
        if( $result = $mysqli->query("
            SELECT `msg`
            FROM `service_lvl`
            WHERE `sl_id` = '$sl_id'
        ")){
            $row = $result->fetch_assoc();
            return $row['msg'];
        }
    }


    public function getSl_id() {
        return $this->sl_id;
    }

    public function getMsg() {
        return $this->msg;
    }

    public function setSl_id($sl_id) {
        $this->sl_id = $sl_id;
    }

    public function setMsg($msg) {
        $this->msg = $msg;
    }
}