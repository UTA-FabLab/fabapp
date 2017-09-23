<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */

/**
 * Description of Devices
 *
 * @author Jon Le
 */


class Devices {
    private $d_id;
    private $device_id;
    private $public_view;
    private $device_desc;
    private $d_duration;
    private $base_price;
    private $dg;
    private $url;
    private $device_key;
    private $salt_key;
    private $exp_key;
    
    public function __construct($d_id) {
        global $mysqli;
        $this->d_id = $d_id;
        
        if ($result = $mysqli->query("
            SELECT *
            FROM `devices`
            WHERE `d_id` = '$d_id'
            LIMIT 1;
        ")){
            $row = $result->fetch_assoc();

            $this->setDevice_id($row['device_id']);
            $this->setDevice_desc($row['device_desc']);
            $this->setPublic_view($row['public_view']);
            $this->setD_duration($row['d_duration']);
            $this->setBase_price($row['base_price']);
            $this->setDg($row['dg_id']);
            $this->setUrl($row['url']);
            $this->setDevice_key($row['device_key']);
            $this->setSalt_key($row['salt_key']);
            $this->setExp_key($row['exp_key']);
            $result->close();
        } else
            throw new Exception("Invalid Device ID");
    }
    
    public static function is_open($d_id){
        global $mysqli;
        
        if($result = $mysqli->query("
            SELECT * 
            FROM `transactions`
            WHERE d_id = '$d_id' AND `status_id` < 12
        ")){
            if ($result->num_rows > 0)
                return true;
            return false;
        }
        return false;
    }

    public static function regexDID($d_id){
        global $mysqli;
        
        if (preg_match("/^\d+$/", $d_id) == 0){
            echo "Invalid D ID.";
            return false;
        }
        
        //Check to see if device exists
        if ($result = $mysqli->query("
            SELECT *
            FROM `devices`
            WHERE `d_id` = '$d_id'
            LIMIT 1;
        ")){
            if ($result->num_rows == 1)
                return true;
            return false;
        } else {
            return false;
        }
    }
    
    public static function regexDeviceID($device_id){
        if (preg_match("/^\d{4}$/", $d_id) == 0){
            echo "Invalid Device ID. ";
            return false;
            
        }//Check to see if device exists
        if ($result = $mysqli->query("
            SELECT *
            FROM `devices`
            WHERE `device_id` = '$device_id'
            LIMIT 1;
        ")){
            if ($result->num_rows == 1)
                return true;
            return false;
        } else {
            return false;
        }
    }
    
    public function getD_id() {
        return $this->d_id;
    }

    public function getDevice_id() {
        return $this->device_id;
    }

    public function getPublic_view() {
        return $this->public_view;
    }

    public function getD_duration() {
        return $this->d_duration;
    }

    public function getBase_price() {
        if (strlen($this->base_price) < 3 )
            return sprintf("%.2f", $this->base_price);
        else
            return sprintf("%.5f", $this->base_price);
    }

    public function getDg() {
        return $this->dg;
    }

    public function getUrl() {
        return $this->url;
    }

    public function getDevice_key() {
        return $this->device_key;
    }
    
    public function getDevice_desc() {
        return $this->device_desc;
    }
    
    public function getSalt_key() {
        return $this->salt_key;
    }
    
    public function getExp_key() {
        return $this->exp_key;
    }

    public function setD_id($d_id) {
        if (preg_match("/^\d+$/",$device_id) == 0)
            return false;
        $this->d_id = $d_id;
    }

    public function setD_duration($d_duration) {
        if(Transactions::regexTime($d_duration)){
            $this->d_duration = $d_duration;
        } else {
            echo ("Invalid time $d_duration. ");
            return false;
        }
    }
    
    public function setDevice_desc($device_desc) {
        $this->device_desc = $device_desc;
    }

    public function setDevice_id($device_id) {
        if (preg_match("/^\d{4}$/",$device_id)){
            $this->device_id = $device_id;
        } else {
            echo("Invalid Device ID - $device_id. ");
            return false;
        }
    }

    public function setPublic_view($public_view) {
        if (preg_match("/^[YN]{1}$/",$public_view))
            $this->public_view = $public_view;
        else 
            echo "Invalid Public View";
    }

    public function setBase_price($base_price) {
        $this->base_price = $base_price;
    }

    public function setDg($dg) {
        $this->dg = new DeviceGroup($dg);
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function setDevice_key($device_key) {
        $this->device_key = $device_key;
    }

    private function setSalt_key($salt_key) {
        $this->salt_key = $salt_key;
    }

    private function setExp_key($exp_key) {
        $this->exp_key = $exp_key;
    }
    
    public function genSalt(){
        global $sv;
        
        //generate 64 characters
        $this->setSalt_key($salt_key);
        
        //set expiration CURRENT_TIME + $sv['salt_duration'];
        $this->setExp_key($exp_key);
        
        //Update DB
    }
    
    public static function printDot($staff, $d_id){
    	global $mysqli;
        
    	//look up current device status
    	$dot = 0;
    	$color = "white";
    	$symbol = "circle";
    	$lookup = "SELECT * FROM `service_call` WHERE `d_id` = '$d_id' AND solved = 'N' ORDER BY sc_time DESC";
    	if($status = $mysqli->query($lookup)){
            while ($ticket = $status->fetch_assoc()){
                if($ticket['sl_id'] > $dot)
                    $dot = $ticket['sl_id'];
            }
            if($status == NULL || $dot <= 1) {
                $color = "green";
            } elseif($dot < 7) {
                $color = "yellow";
            } else {
                $color = "red";
                $symbol = "times";
            }
    	}
        
    	if($staff){
            if($staff->getRoleID() > 7)
                echo "<a href = '/service/sortableHistory.php?d_id=".$d_id."'><i class='fa fa-$symbol fa-fw' style='color:$color'></i></a>&nbsp;";
            else
                echo "<i class='fa fa-".$symbol." fa-fw' style='color:".$color."'></i>&nbsp;";
    	} else {
            echo "<i class='fa fa-".$symbol." fa-fw' style='color:".$color."'></i>&nbsp;";
    	}
    }
}
