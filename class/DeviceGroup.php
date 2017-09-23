<?php

/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 */

/**
 * Description of DeviceGroup
 *
 * @author Jon Le
 */
class DeviceGroup {
    private $dg_id;
    private $dg_name;
    private $dg_parent;
    private $dg_desc;
    private $payFirst;
    private $selectMatsFirst;
    private $storable;
    
    public function __construct($dg_id){
        global $mysqli;
        
        if($result = $mysqli->query("
            SELECT *
            FROM `device_group`
            WHERE `device_group`.`dg_id` = '$dg_id';
        ")){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                $this->setDg_id($row['dg_id']);
                $this->setDg_name($row['dg_name']);
                $this->setDg_parent($row['dg_parent']);
                $this->setDg_desc($row['dg_desc']);
                $this->setPayFirst($row['payFirst']);
                $this->setSelectMatsFirst($row['selectMatsFirst']);
                $this->setStorable($row['storable']);
                $result->close();
            }
        }
        
    }

    
    public static function regexDgID($dg_id){
        global $mysqli;

        if (preg_match("/^\d+$/", $dg_id) == 0){
            //echo "Invalid Device Group.";
            return false;
        }

        //Check to see if device exists
        if ($result = $mysqli->query("
            SELECT *
            FROM `device_group`
            WHERE `dg_id` = '$dg_id';
        ")){
            if ($result->num_rows == 1)
                return true;
            return "DG construct: Result not unique";
        } else {
            return "DG Construct: Error with table";
        }
    }
    
    public function getDg_id() {
        return $this->dg_id;
    }

    public function getDg_name() {
        return $this->dg_name;
    }

    public function getDg_parent() {
        return $this->dg_parent;
    }

    public function getDg_desc() {
        return $this->dg_desc;
    }

    public function getPayFirst() {
        return $this->payFirst;
    }

    public function getSelectMatsFirst() {
        return $this->selectMatsFirst;
    }

    public function getStorable() {
        return $this->storable;
    }

    public function setDg_id($dg_id) {
        $this->dg_id = $dg_id;
    }

    public function setDg_name($dg_name) {
        $this->dg_name = $dg_name;
    }

    public function setDg_parent($dg_parent) {
        $this->dg_parent = $dg_parent;
    }

    public function setDg_desc($dg_desc) {
        $this->dg_desc = $dg_desc;
    }

    public function setPayFirst($payFirst) {
        //Only Y or N, default to N otherwise
        if(preg_match("/[YN]{1}/", $payFirst)){
            $this->payFirst = $payFirst;
        } else {
            $this->payFirst = "N";
        }
    }

    public function setSelectMatsFirst($selectMatsFirst) {
        //Only Y or N, default to N otherwise
        if(preg_match("/[YN]{1}/", $selectMatsFirst)){
            $this->selectMatsFirst = $selectMatsFirst;
        } else {
            $this->selectMatsFirst = "N";
        }
    }

    public function setStorable($storable) {
        //Only Y or N, default to N otherwise
        if(preg_match("/[YN]{1}/", $storable)){
            $this->storable = $storable;
        } else {
            $this->storable = "N";
        }
    }


}
