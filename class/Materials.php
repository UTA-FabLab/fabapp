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
	
	public static function getDeviceMats($dg_id = false){
		global $mysqli;
		$device_mats = array();
		
		if(!DeviceGroup::regexDgID($dg_id) && $dg_id !== false) return "Invalid Device Group Value";
		
		if($dg_id === false) {
			if($result = $mysqli->query("
			 SELECT m_id, price, m_name, unit 
			 FROM materials
			 ORDER BY m_name ASC;
		  ")){
			 while( $row = $result->fetch_assoc()) {
				array_push($device_mats, new self($row['m_id']));
				// array_push($device_mats, $row['m_id']);
			 }
			 return $device_mats;
		  }
		}

		elseif($result = $mysqli->query("
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
		}
		return false;
	}


	public static function mat_exists($name) {
		global $mysqli;

		if($result = $mysqli->query("
			SELECT `m_id`
			FROM `materials`
			WHERE `m_name` = '".$name."';
		")) {
			if($result->num_rows == 1) {
				return $result->fetch_assoc()['m_id'];
			}
		}
		return false;
	}


	public static function update_mat($color, $m_id, $measurability, $price, $unit) {
		global $mysqli;

		$statement_parts = [];
		$prepared_statement = "UPDATE `materials` SET ";
		
		if($color !== NULL) $statement_parts[] = "`color_hex` = '".$color."'";
		echo "<script> console.log('Color2: $color');</script>";
		$statement_parts[] = "`measurable` = '".$measurability."'";
		if($price !== NULL) $statement_parts[] = "`price` = '".$price."'";
		if($unit !== "") $statement_parts[] = "`unit` = '".$unit."'";

		$prepared_statement .= implode(", ", $statement_parts)." WHERE `m_id` = '".$m_id."';";
		// echo '<script> console.log("Prep: '.$prepared_statement.'");</script>';  //TESTING
		if($mysqli->query($prepared_statement)) {
			// return false if no failures
			return $mysqli->affected_rows != 1;
		}
		echo '<script> console.log("Prep3: '.$prepared_statement.'");</script>';  //TESTING
		return "Could not update material";
	}


	public static function create_new_mat($color, $measurability, $name, $price, $unit) {
		global $mysqli;

		if ($stmt = $mysqli->prepare(" 
			INSERT INTO `materials`
				(`m_name`, `m_parent`, `price`, `unit`, `color_hex`, `measurable`)
			VALUES
				(?, NULL, ?, ?, ?, ?);
		")) {
			$stmt->bind_param("sdsss", $name, floatval($price), $unit, $color, $measurability);
			if ($stmt->execute() === true){
				$row = $stmt->affected_rows;
				// Success, only one row was updated
				if ($row == 1){
					$mysqli->commit();
					return true;
				// Error More then one row was affected
				} elseif ($row > 1) {
					$mysqli->rollback();
				}
			}
		}
	return false;
	}


	// used to check which device groups have already been assigned to material
    public static function get_device_mat($m_id) {
        global $mysqli;

        $group_ids = array();
        if($result = $mysqli->query("
            SELECT `dg_id`
            FROM `device_materials`
            WHERE `m_id` = '".$m_id."';
        ")) {
        	while($row = $result->fetch_assoc()) {
        		$group_ids[] = $row['dg_id'];
        	}
        	return $group_ids;
        }
        return false;
    }


    public static function assign_device_group($dg_id, $m_id){
    	global $mysqli;

		if ($stmt = $mysqli->prepare(" 
			INSERT INTO `device_materials`
				(`dg_id`, `m_id`)
			VALUES
				(?, ?);
		")) {
			$stmt->bind_param("dd", intval($dg_id), intval($m_id));
			if ($stmt->execute() === true){
				$row = $stmt->affected_rows;
				// Success, only one row was updated
				if ($row == 1){
					$mysqli->commit();
					return true;
				// Error More then one row was affected
				} elseif ($row > 1) {
					$mysqli->rollback();
				}
			}
		}
	return false;
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


	// used for making new item
	public static function regexName($name) {
		if(strlen($name) > 50 || strlen($name) == 0) return false;
		return htmlspecialchars($name);
	}

	public static function regexPrice($price) {
		if(preg_match('/^[0-9]{1,8}+(\.[0-9]{1,4})?$/', $price)) return $price;
		return false;
	}

	public static function regexMeasurability($measure) {
		if($measure === 'Y' || $measure === 'N') return $measure;
		return false;
	}

	public static function regexUnit($unit) {
		if(strlen($unit) > 50) return false;
		return htmlspecialchars($unit);
	}

	public static function regexColor($color) {
		if(preg_match('/^[0-9A-Fa-f]{1,6}/', $color)) return $color;
		return false;
	}

	public static function regexDeviceGroup($dg) {
		foreach($dg as $d) {
			if(!preg_match('/^[0-9]{1,2}/', $d)) return false;
		}
		return true;
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
