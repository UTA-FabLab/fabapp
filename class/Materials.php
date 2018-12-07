<?php
/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 */

/**
 * Description of Materials
 *
 * @author Jon Le
 */
class Materials {
    private $m_id;
    private $m_name;
    private $price;
    private $unit;
    private $m_parent;
    private $color_hex;
    private $measurable;
    
    public function __construct($m_id) {
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $m_id))
            throw new Exception('Material ID Number '.$m_id." ".gettype($m_id));
        if ($result = $mysqli->query("
            SELECT *
            FROM `materials`
            WHERE `m_id` = $m_id
            LIMIT 1;
        ")){
            $row = $result->fetch_assoc();
            $this->setM_id($m_id);
            $this->setM_name($row['m_name']);
            $this->setPrice($row['price']);
            $this->setUnit($row['unit']);
            $this->setM_parent($row['m_parent']);
            $this->setColor_hex($row['color_hex']);
            $this->setMeasurable($row['measurable']);
        }
    }
    
    public static function getDeviceMats($dg_id){
        global $mysqli;
        $device_mats = array();
        
        if(!DeviceGroup::regexDgID($dg_id)) return "Invalid Device Group Value";
        
        if ($result = $mysqli->query("
            SELECT device_materials.m_id, price, m_name, unit
            FROM materials
            LEFT JOIN device_materials
            ON materials.m_id = device_materials.m_id
            WHERE dg_id = '$dg_id'
            ORDER BY m_name ASC;
        ")){
            while( $row = $result->fetch_assoc() ) {
                array_push($device_mats, new self($row['m_id']));
            }
            return $device_mats;
        } else {
            return false;
        }
    }
    
    public static function regexID($m_id){
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $m_id))
            return false;
        if($result = $mysqli->query("
            SELECT *
            FROM `materials`
            WHERE `m_id` = '$m_id'
            LIMIT 1;
        ")){
            if($result->num_rows == 1)
                return true;
        }
        return $mysqli->error;
    }
   

    public static function units_in_system($m_id) {
        global $mysqli;

        if (preg_match("/^\d+$/", $m_id)) {
            if($result = $mysqli->query("
                SELECT SUM(unit_used) as `sum`
                FROM `mats_used`
                WHERE `m_id` = '$m_id';
            "))
                return $result->fetch_object()->sum;
        }
        return false;
    }


    public static function update_material_quantity($m_id, $update_quantity, $staff) {
        global $mysqli;
        //TODO: change to proper level
        if($staff->getRoleID() < 9) return false;

        if(preg_match("/^\d+$/", $m_id) && is_numeric($update_quantity)) {
            $current_amount = Materials::units_in_system($m_id);
            $difference = $update_quantity - $current_amount;  // get the amount to find

            if($mysqli->query("
                INSERT INTO `mats_used`
                    (`m_id`, `unit_used`, `mu_date`, `operator`, `mu_notes`) 
                VALUES
                    ('$m_id', '$difference', CURRENT_TIME(), '".$staff->getOperator()."', 'updated to match current inventory');
            "))
                return true;
        }
        return false;
    }

 
    public function getM_id() {
        return $this->m_id;
    }
    
    public function getMeasurable(){
        return $this->measurable;
    }

    public function getM_name() {
        return $this->m_name;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getUnit() {
        return $this->unit;
    }

    public function getM_parent() {
        return $this->m_parent;
    }
    
    public function getColor_hex(){
        return $this->color_hex;
    }

    public function setM_id($m_id) {
        $this->m_id = $m_id;
    }
    
    public function setMeasurable($m){
        //Only Y or N, default to N otherwise
        if(preg_match("/[YN]{1}/", $m)){
            $this->measurable = $m;
        } else {
            $this->measurable = "N";
        }
    }

    public function setM_name($m_name) {
        $this->m_name = $m_name;
    }

    public function setPrice($price) {
        $this->price = $price;
    }

    public function setUnit($unit) {
        $this->unit = $unit;
    }

    public function setM_parent($m_parent) {
        $this->m_parent = $m_parent;
    }
    
    public function setColor_hex($color_hex){
        if ($color_hex){
            $this->color_hex = $color_hex;
        } else {
            $this->color_hex = NULL;
        }
    }
}
