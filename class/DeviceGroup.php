<?php

/*
 * License - FabApp V 0.91
 * 2016-2018 CC BY-NC-AS UTA FabLab
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
    private $granular_wait;
    private $juiceboxManaged;
    private $payFirst;
    private $selectMatsFirst;
    private $storable;
    private $thermalPrinterNum;
    
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
                $this->setJuiceboxManaged($row['juiceboxManaged']);
                $this->setGranular_wait($row['granular_wait']);
                $this->setPayFirst($row['payFirst']);
                $this->setSelectMatsFirst($row['selectMatsFirst']);
                $this->setStorable($row['storable']);
                $this->setThermalPrinterNum($row['thermalPrinterNum']);
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
    
    //List all DGs that have devices within their group.
    public static function popDGs(){
        global $mysqli;
        if($result = $mysqli->query("
            SELECT DISTINCT `device_group`.`dg_id`, `device_group`.`dg_desc`
            FROM `devices`
            LEFT JOIN `device_group`
            ON `device_group`.`dg_id` = `devices`.`dg_id`
            ORDER BY `dg_desc`
        ")){
            return $result;
        } else {
            return false;
        }
    }
    
    //List all DGs that have devices within their group & have WQ tickets or are at capacity.
    public static function popDG_WQ(){
        global $mysqli;
        $all_dgs = array();
        
        //list all DGs = $all_dgs
        if($result = $mysqli->query("
            SELECT `device_group`.`dg_id`, `device_group`.`dg_desc`
            FROM `devices`
            JOIN `device_group`
            ON `device_group`.`dg_id` = `devices`.`dg_id`
            WHERE `devices`.`public_view`='Y' AND `devices`.`d_id` NOT IN (
                    SELECT `d_id`
                    FROM `service_call`
                    WHERE `solved` = 'N' AND `sl_id` >= 7
                )
            GROUP BY `device_group`.`dg_desc`, `device_group`.`dg_id`
            ORDER BY `dg_desc`
        ")){
            while ($row = $result->fetch_assoc()){
                $all_dgs[$row['dg_id']] = $row['dg_desc'];
            }
        } else {
            return false;
        }
            
        return $all_dgs;
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

    public function getGranular_wait(){
        return $this->granular_wait;
    }

    public function getJuiceboxManaged(){
        return $this->JuiceboxManaged;
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

    public function getThermalPrinterNum() {
        return $this->thermalPrinterNum;
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
    
    public function setGranular_wait($granular_wait) {
        $this->granular_wait = $granular_wait;
    }

    public function setJuiceboxManaged($juiceboxManaged){
        //Only Y or N, default to N otherwise
        if(preg_match("/[YN]{1}/", $juiceboxManaged)){
            $this->juiceboxManaged = $juiceboxManaged;
        } else {
            $this->juiceboxManaged = "N";
        }
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

    public function setThermalPrinterNum($tpn) {
        $this->thermalPrinterNum = $tpn;
    }
}
