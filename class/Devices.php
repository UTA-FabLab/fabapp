<?php

/***********************************************************************************************************
*	
*	@author Jon Le
*	Edited by: MPZinke on 06.11.19 to improve commenting an logic/functionality of class.
*	 Consolidated Device related classes (Devices & DeviceGroup).  Reduced duplicate
*	 code.
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.94
*		-House Keeping (DB cleanup, $status variable, class syntax/functionality)
*		-Multiple Materials
*		-Off-line Mode
*		-Sheet Goods
*		-Storage Box
*
*	DESCRIPTION: Instance of device containing key information.  Methods for device
*	 creation, removal, editing.  Echo dot (or X) for device status.
*
***********************************************************************************************************/


class Devices {
	public $device_id;  // #—main identifier of device
	public $base_price;  // #—minimum cost of device usage
	public $name;  // str—device name
	public $device_group;  // OBJ—parent group of device & siblings
	public $time_limit;  // str—"HH:MM:SS" of time_limit
	public $public_view;  // bool—true if viewable to public
	public $url;  // str—UTA url to device

	//REMOVE WITH UPDATE
	public $device_desc;  // same as name
	public $d_duration;  // same as time_limit

	
	public function __construct($device_id) {
		global $mysqli;
		$this->device_id = $device_id;
		
		if ($result = $mysqli->query("
			SELECT *
			FROM `devices`
			WHERE `d_id` = '$device_id'
			LIMIT 1;
		")){
			$row = $result->fetch_assoc();

			// $this->name = $row['device_name'];  //ADD WITH UPDATE
			$this->name = $row['device_desc'];  //REMOVE WITH UPDATE
			$this->time_limit = $row['time_limit'];  //ADD WITH UPDATE
			// $this->time_limit = $row['d_duration'];  //REMOVE WITH UPDATE

			$this->public_view = $row['public_view'] == 'Y';
			$this->base_price = $row['base_price'];
			$this->device_group = new DeviceGroup($row['dg_id']);
			$this->url = $row['url'];

		}
		else throw new Exception("Invalid Device ID");
	}


	// check DB if any device already has current device_id
	public static function device_id_exists($device_id){
		global $mysqli;
		return mysqli_num_rows($mysqli->query("SELECT * FROM `devices` WHERE `device_id`= '$device_id'")) > 0;
	}
	
	
	// check DB if desired device is not being used
	public static function is_open($device_id){
		global $mysqli, $status;
		
		if($result = $mysqli->query("
			SELECT * 
			FROM `transactions`
			WHERE device_id = '$device_id' AND `status_id` < $status[total_fail]
		")){
			if ($result->num_rows > 0) return true;
			return false;
		}
		return false;
	}


	// create new device in DB device table
	public static function insert_device($dg_id, $public_view, $d_name, $time_limit, $d_price, $d_url){
		global $mysqli;
		
		if ($mysqli->query("
			INSERT INTO `devices` 
			  (`device_id`, `public_view`, `device_name`, `time_limit`, `base_price`, `dg_id`, `url`) 
			VALUES
				('0','$public_view','$d_name', '$time_limit', '$d_price', '$dg_id', '$d_url');"
		)) {
			return $mysqli->insert_id;
		}
		return ("<div class='alert alert-danger'>".$mysqli->error."</div>");
	}


	// device status dot (or X) echo to page
	public static function printDot($staff, $device_id){
		global $mysqli;
		global $sv;
		
		//look up current device status
		$dot = 0;
		$color = "white";
		$symbol = "circle";
		$lookup = "SELECT * FROM `service_call` WHERE `d_id` = '$device_id' AND `solved` = 'N' ORDER BY `sl_id` DESC";
		if($service_status = $mysqli->query($lookup)){
			while ($ticket = $service_status->fetch_assoc()) if($ticket['sl_id'] > $dot) $dot = $ticket['sl_id'];
			if($service_status == NULL || $dot <= 1) $color = "green";
			elseif($dot < 7) $color = "yellow";
			else {
				$color = "red";
				$symbol = "times";
			}
		}
		
		if($staff){
			if($staff->getRoleID() >= $sv['LvlOfStaff'] || $staff->getRoleID() == $sv['serviceTechnican'])
				echo "<a href = '/pages/sr_history.php?device_id=$device_id'><i class='fas fa-$symbol fa-lg' style='color:$color'></i></a>&nbsp;";
			else echo "<i class='fas fa-$symbol fa-lg' style='color:".$color."'></i>&nbsp;";
		} 
		else echo "<i class='fas fa-$symbol fa-lg' style='color:".$color."'></i>&nbsp;";
	}


	// "remove" device from DB by removing it from public view
	public static function remove_device($device_id){
		global $mysqli;
		
		if ($mysqli->query("
			UPDATE `devices`
			SET `public_view` = 'N'
			WHERE `device_id` = $device_id;
		")) {
			return $device_id;
		}
		return ("<div class='alert alert-danger'>".$mysqli->error."</div>");
	}


	// make changed to device in DB
	public static function updateDevice($device_id, $d_desc, $time_limit, $d_price, $dg_id, $d_url, $d_view) {
		global $mysqli;
		
		if ($mysqli->query("
			UPDATE `devices`
			SET `device_name` = '$d_desc' , `time_limit` = '$time_limit' , `base_price` = '$d_price' , `dg_id` = '$dg_id' , `url` = '$d_url' , `public_view` = '$d_view'
			WHERE `device_id` = '$device_id';"
		)) {
			return true;
		}
		return false;
	}



	// ————————————————— REGEX ——————————————————

	public static function regexDeviceID($device_id){
		return preg_match("/^\d+$/", $device_id);
	}
	
	public static function regexTime($time_limit) {
		return preg_match("/^\d{1,3}:\d{2}:\d{2}$/", $time_limit);
	}



// —————————————— REMOVE WITH UPDATE ———————————————
// —————————————————————————————————————————

	public function getD_id() {
		return $this->device_id;
	}
	public function getDevice_id() {
		return $this->device_id;
	}
	public function getPublic_view() {
		if($this->public_view) return "Y";
		return "N";
	}
	public function getD_duration() {
		return $this->time_limit;
	}
	public function getBase_price() {
		if (strlen($this->base_price) < 3 )
			return sprintf("%.2f", $this->base_price);
		else
			return sprintf("%.5f", $this->base_price);
	}
	public function getDg() {
		return $this->device_group;
	}
	public function getUrl() {
		return $this->url;
	}	
	public function getDevice_desc() {
		return $this->name;
	}

}



/***********************************************************************************************************
*
*	DESCRIPTION: Instance mostly called by Device() member.  Holds & gets information 
*	 from DB.
*	FUTURE: adjust FabApp so that $materials attribute is not needed.  It is just 
*	 $required_materials & $non_required materials combined.
*
***********************************************************************************************************/


class DeviceGroup {
	public $dg_id;  // #
	public $name;  // str—English propernoun name of Device Group
	public $parent;  // #—the device group that is the parent of this device_group
	public $is_granular_wait;  // bool—wait queue related
	public $is_juiceboxManaged;  // bool—whether should seek if individual has permission
	public $is_pay_first;  // bool—whether user must pay before use
	public $is_select_mats_first;  // bool—whether user must select materials before use
	public $is_storable;  // bool—whether an object is created that can be stored in storage 
	public $optional_materials;  // array(OBJ)—optional materials for device
	public $required_materials;  // array(OBJ)—materials associated with device are required
	public $thermal_printer_num;
	
	public function __construct($dg_id){
		global $mysqli;
		
		if($result = $mysqli->query("
			SELECT *
			FROM `device_group`
			WHERE `device_group`.`dg_id` = '$dg_id';
		")){
			if ($result->num_rows == 1){
				$row = $result->fetch_assoc();
				$this->dg_id = $row['dg_id'];
				$this->parent = $row['dg_parent'];
				$this->name = $row["dg_name"];
				$this->is_juicebox_managed = $row['juiceboxManaged'] == 'Y';
				$this->is_granular_wait = $row['granular_wait'] == 'Y';
				$this->is_pay_first = $row['payFirst'] == 'Y';
				$this->is_select_mats_first = $row['selectMatsFirst'] == 'Y';
				$this->is_storable = $row['storable'] == 'Y';
				$this->optional_materials = $this->optional_materials();
				$this->required_materials = $this->required_materials();
				$this->thermal_printer_num = $row["thermalPrinterNum"];
			}
		}
	}


	// list of device groups that have devices; OPTIONAL: viewable to public and/or unavailable.
	// Based off of popDB_list(), popDGs() & popDG_WQ()
	// get name and ID of all device groups in DB: previously named popAllDG_list()
	public static function all_device_groups() {
		global $mysqli;
		$all_dgs = array();
		
		if($result = $mysqli->query("
			SELECT `device_group`.`dg_id`, `device_group`.`dg_desc`
			FROM `device_group`
			GROUP BY `device_group`.`dg_desc`, `device_group`.`dg_id`
			ORDER BY `dg_desc`;"
		)){
			while ($row = $result->fetch_assoc()) 
				$all_dgs[$row['dg_id']] = $row['dg_desc'];
			return $all_dgs;  // false if nothing added
		}
		return false;
	}


	// add new device group to DB
	public static function insert_new_device_group($dg_parent, $dg_desc, $dg_pay, $dg_mat, $dg_store, $dg_juicebox, $dg_thermal, $dg_granular){
		global $mysqli;
		
		if ($mysqli->query("
			INSERT INTO `device_group` 
			  (`dg_parent`,`dg_desc`, payFirst, `selectMatsFirst`, `storable`, `juiceboxManaged`, `thermalPrinterNum`, `granular_wait`) 
			VALUES
				('$dg_parent', '$dg_desc', '$dg_pay', '$dg_mat', '$dg_store', '$dg_juicebox', '$dg_thermal', '$dg_granular');"
		)){
			$dg_id = $mysqli->insert_id;
			return $dg_id;
		}
		return ("<div class='alert alert-danger'>".$mysqli->error."</div>");
	}


	public function materials_prices() {
		$all_materials = array_merge($this->optional_materials, $this->required_materials);

		$id_and_price = array();
		foreach($all_materials as $material)
			$id_and_price[$material->m_id] = $material->price;
		return $id_and_price;
	}


	public function optional_materials() {
		global $mysqli;

		$required_materials = array();
		if($results = $mysqli->query("SELECT `m_id`
										FROM `device_materials`
										WHERE `dg_id` = '$this->dg_id'
										AND `required` = 'N';"
		)) {
			while($row = $results->fetch_assoc())
				$required_materials[] = new Materials($row['m_id']);
			return $required_materials;
		}
		return null;
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

	// method to retrieve required materials associated with device_group object
	public function required_materials() {
		global $mysqli;

		$required_materials = array();
		if($results = $mysqli->query("SELECT `m_id`
										FROM `device_materials`
										WHERE `dg_id` = '$this->dg_id'
										AND `required` = 'Y';"
		)) {
			while($row = $results->fetch_assoc())
				$required_materials[] = new Materials($row['m_id']);
			return $required_materials;
		}
		return null;
	}



	// ————————————————— REGEX ——————————————————

	public static function regexDgID($dg_id){
		global $mysqli;

		if(!preg_match("/^\d+$/", $dg_id)){
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
		}
		return "DG Construct: Error with table";
	}

	public static function regexDeviceGroup($device_group) {
		if(!is_array($device_group)) return preg_match('/^[0-9]{1,3}/', $device_group);
		foreach($device_group as $dg_id) if(!preg_match("/^\d+$/", $dg_id)) return false;
		return true;
	}



// —————————————— REMOVE WITH UPDATE ———————————————
// —————————————————————————————————————————



}

?>