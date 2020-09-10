<?php

/***********************************************************************************************************
*	
*	@author Jon Le
*	Edited by: MPZinke on 06.11.19 to improve commenting an logic/functionality of class.
*	 Consolidated Device related classes (Materials & Mats_Used).  Reduced duplicate
*	 code.
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.94
*		-House Keeping (DB cleanup, $status variable, class syntax/functionality)
*		-Multiple Materials
*		-Off-line Mode
*		-Sheet Goods
*		-Storage Box
*
*	DESCRIPTION: Material instance of row from `material` column in DB.  Extends 
*	 functionality of getting information on material & group existence.  Adds functionality of 
*	 material creation & update
*
***********************************************************************************************************/


class Materials {
	public $m_id;  // #—primary key of material
	public $m_name;  // str—english name of material
	public $m_parent;  // OBJ(Materials)—parent of material
	public $price;  // #—price of material
	public $unit;  // str—unit the material uses
	public $color_hex;  // str—color of material in a hex form
	public $is_measurable;  // bool—if DB.measurable == Y
	public $product_number;  // str—assigned product number of material
	public $is_current;
	
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
			$this->m_id = $m_id;
			$this->m_name = $row['m_name'];
			$this->m_parent = $row['m_parent'] ? new self($row['m_parent']) : null;
			$this->price = $row['price'];
			$this->unit = $row['unit'];
			$this->color_hex = $row['color_hex'] ? $row['color_hex'] : null;
			$this->is_measurable = $row['measurable'] == "Y";
			$this->is_current = $row['current'] == "Y";
			$this->product_number = $row['product_number'];
		}
	}
	

	// create new instance in DB of material-device association
	public static function assign_device_group($dg_id, $m_id){
		global $mysqli;

		if ($statement = $mysqli->prepare(" 
			INSERT INTO `device_materials`
				(`dg_id`, `m_id`)
			VALUES
				(?, ?);
		")) {
			$statement->bind_param("dd", intval($dg_id), intval($m_id));
			if ($statement->execute() === true){
				$row = $statement->affected_rows;
				// Success, only one row was updated
				if ($row == 1) return $mysqli->commit();
				// error—more then one row was affected
				elseif ($row > 1) $mysqli->rollback();
			}
		}
		return false;
	}


	// create a new material in DB taking parameters
	public static function create_new_material($color, $measurability, $name, $parent, $price, $product_number, $unit) {
		global $mysqli;

		if ($statement = $mysqli->prepare(
			"INSERT INTO `materials`
				(`m_name`, `m_parent`, `price`, `product_number`, `unit`, `color_hex`, `measurable`)
			VALUES
				(?, ?, ?, ?, ?, ?, ?);"
		))
		{
			$statement->bind_param("sidssss", $name, $parent, floatval($price), $product_number, $unit, $color, 
			$measurability);
			if(!$statement) return false;
			if(!$statement->execute()) return false;
			return $mysqli->insert_id;
		}
		return false;
	}

	
	public static function create_new_sheet_inventory($m_id, $m_parent, $sheet_width, $sheet_height, $sheet_quantity) {
		//MPZinke: I am currently cleaning this up to prevent passing of HTML strings and prevents SQL injections.  In 
		// the future I should update it so that it strictly does what its name does (ie creates a new material), and move
		// updating feature to its own function.

		global $mysqli;

		if(self::hasInventory($m_id , $sheet_width, $sheet_height))
		{
			$statement = $mysqli->prepare(	"UPDATE `sheet_good_inventory` SET `quantity` = ? 
												WHERE `m_id`= ? AND `width`= ? AND `height` = ?;");
			if(!$statement) return "Error creating update statement";
			$statement->bind_param("dddd", $sheet_quantity, $m_id, $sheet_width, $sheet_height);
			if(!$statement) return "Bad param binding: $mysqli->error";
			if(!$statement->execute()) return "Bad execution: $mysqli->error";
			return false;  // no errors
		}
		else
		{
			$statement = $mysqli->prepare(	"INSERT INTO `sheet_good_inventory` 
												(`m_id`, `m_parent`, `width`, `height`, `quantity`) VALUES
												(?, ?, ?, ?, ?);");
			if(!$statement) return "Error creating update statement";
			$statement->bind_param("ddddd", $m_id, $m_parent, $sheet_width, $sheet_height, $sheet_quantity);
			if(!$statement) return "Bad param binding: $mysqli->error";
			if(!$statement->execute()) return "Bad execution: $mysqli->error";
			return false;  // no errors
		}
	}



	// if the values are different then update the OBJ then DB
	public function edit_material_information($change_array){
		if(!count($change_array)) return;

		if(array_key_exists("color_hex", $change_array)) $this->color_hex = $change_array["color_hex"];
		if(array_key_exists("is_measurable", $change_array)) $this->is_measurable = $change_array["is_measurable"];
		if(array_key_exists("is_current", $change_array)) $this->is_current = $change_array["is_current"];
		if(array_key_exists("m_name", $change_array)) $this->m_name = $change_array["m_name"];
		if(array_key_exists("product_number", $change_array)) $this->product_number = $change_array["product_number"];
		if(array_key_exists("price", $change_array)) $this->price = $change_array["price"];
		if(array_key_exists("unit", $change_array)) $this->unit = $change_array["unit"];

		return $this->update_material();
	}


	// return specific device material object if requested or return all device materials
	// used to associate materials to (specific) devices
	public static function getDeviceMats($dg_id=null){
		global $mysqli;
		$device_mats = array();

		if($dg_id && !DeviceGroup::regexDeviceGroup($dg_id)) return "Invalid Device Group Value";
		
		// desired dg given else select *
		$where = $dg_id ? "WHERE dg_id = '$dg_id'" : "";

		if($result = $mysqli->query("	SELECT device_materials.m_id
										FROM device_materials
										LEFT JOIN materials
										ON materials.m_id = device_materials.m_id
										$where
										ORDER BY m_name ASC;"
		)){
			while($row = $result->fetch_assoc())
				array_push($device_mats, new self($row['m_id']));
			return $device_mats;
		}
		return false;
	}


	public static function get_all_materials() {
		global $mysqli;

		$materials = array();

		if($results = $mysqli->query("	SELECT `m_id`
										FROM `materials`
										ORDER BY `m_name` ASC;"
		)) {
			while($row = $results->fetch_assoc())
				array_push($materials, new self($row['m_id']));
			return $materials;
		}
		return false;
	}


	// used to check which device groups have already been assigned to material
	public static function get_device_material_group($m_id) {
		global $mysqli;

		if($result = $mysqli->query("
			SELECT `dg_id`
			FROM `device_materials`
			WHERE `m_id` = '$m_id';
		")) {
			$group_ids = array();
			while($row = $result->fetch_assoc())
				$group_ids[] = $row['dg_id'];
			return $group_ids;
		}
		return null;
	}


	public static function getTabResult($sv_sheetgoods){
		global $mysqli;
		if ($result = $mysqli->query("
			SELECT DISTINCT `materials`.`m_id`, `materials`.`m_name`
			FROM `materials`, `sheet_good_inventory`
			WHERE `materials`.`m_id` = `sheet_good_inventory`.`m_parent` 
			AND `materials`.`m_parent` = '$sv_sheetgoods' 
			AND `sheet_good_inventory`.`quantity` > 0;
		")){
			return  $result;
		}
		return false;
	}

	
	public static function hasInventory($m_id , $width, $height){
		global $mysqli;
		return mysqli_num_rows($mysqli->query(" 
								SELECT * 
								FROM `sheet_good_inventory` 
								WHERE `m_id`='$m_id' AND `width`='$width' AND `height` ='$height'"))>0;
	}


	// return boolean of whether material exist in database based on name or id#
	public static function mat_exists($id_or_name) {
		global $mysqli;

		// get by ID #
		if(preg_match("/^\d+$/", $id_or_name)) {
			if($result = $mysqli->query("
				SELECT `m_id`
				FROM `materials`
				WHERE `m_id` = $id_or_name;
			")) {
				return true;
			}
		}
		// not numeric; get by name
		elseif($result = $mysqli->query("
			SELECT `m_id`
			FROM `materials`
			WHERE `m_name` = '$id_or_name';"
		)) {
			if($result->num_rows == 1)
				return $result->fetch_assoc()['m_id'];
		}
		return false;
	}


	public static function sheet_quantity($inv_id) {
		global $mysqli;

		if (preg_match("/^\d+$/", $inv_id)) {
			if($result = $mysqli->query("
				SELECT `quantity`
				FROM `sheet_good_inventory`
				WHERE `inv_id` = '$inv_id';
			"))
				return $result->fetch_object()->quantity;
		}
		return false;
	}

	
	public static function sold_sheet_quantity($inv_id, $quantity) {
		global $mysqli;

		$inv_id1 = Mats_Used::regexID($inv_id);

		if($inv_id1) {
			if($mysqli->query("
				UPDATE `sheet_good_inventory`
				SET `quantity` = `quantity` - '$quantity'
				WHERE `inv_ID` = '$inv_id';
			"))
				return true;
		}
		return false;
	}
	

	// Writes all variables to the DB for a given Transaction
	public function update_material(){
		global $mysqli;
		$is_measurable = $this->is_measurable ? "Y" : "N";
		$is_current = $this->is_current ? "Y" : "N";

		// update transaction info
		$statement = $mysqli->prepare(	"UPDATE `materials`
											SET `color_hex` = ?, `measurable` = ?, `m_name` = ?, 
											`product_number` = ?, `price` = ?, `unit` = ?, `current` = ?
											WHERE `m_id` = ?;");
		$statement->bind_param("ssssdssd", substr($this->color_hex, 1), $is_measurable, $this->m_name, 
									$this->product_number, $this->price, $this->unit, $is_current,
									$this->m_id);
		if(!$statement) return "Bad parameter passed in updating material";
		if(!$statement->execute()) return $mysqli->error;

		return NULL;  // no errors
	}


	public static function update_sheet_quantity($inv_id, $quantity, $notes) {
		global $mysqli;

		// check that quantity will not be set to a negative value; if negative; return error message
		$result = $mysqli->query(	"SELECT `quantity`
									FROM `sheet_good_inventory`
									WHERE `inv_id` = '$inv_id';");  // cannot affectively SQL inject at this point
		if(!$result) return "Materials::update_sheet_quantity: ".$mysqli->error;
		if($result->fetch_assoc()["quantity"] + $quantity < 0)
			return "Incorrect quantity change input, your result must not change quantity to a value below 0";

		// update quantity
		$statement = $mysqli->prepare(	"UPDATE `sheet_good_inventory`
											SET `quantity` = `quantity` + ?, `notes` = ? 
											WHERE `inv_ID` = ?;");
		if(!$statement) return "Materials::update_sheet_quantity: ".$mysqli->error;

		$statement->bind_param("dsd", $quantity, $notes, $inv_id);
		if(!$statement) return "Materials::update_sheet_quantity: ".$mysqli->error;
		if(!$statement->execute()) return "Materials::update_sheet_quantity: ".$mysqli->error;
		return NULL;
	}



	// ————————————————— REGEX ——————————————————
	
	public static function regexID($m_id){
		return preg_match("/^\d+$/", $m_id);
	}

	public static function regexColor($color) {
		if(preg_match('/^#[0-9A-Fa-f]{0,6}/', $color)) return $color;
		return false;
	}

	public static function regexMeasurability($measure) {
		if($measure === 'Y' || $measure === 'N') return $measure;
		return false;
	}

	// used for making new item
	public static function regexName($name) {
		if(strlen($name) > 50 || strlen($name) == 0) return false;
		return htmlspecialchars($name);
	}

	public static function regexProductNum($product_number) {
		if(strlen($product_number) > 30) return false;
		return htmlspecialchars($product_number);
	}

	public static function regexPrice($price) {
		if(preg_match('/^[0-9]{1,8}+(\.[0-9]{1,4})?$/', $price)) return $price;
		return false;
	}

	public static function regexUnit($unit) {
		if(strlen($unit) > 50) return false;
		return htmlspecialchars($unit);
	}
}




/***********************************************************************************************************
*
*	DESCRIPTION: Instance from DB of material used.  Allows for material usage update &
*	 creation.  Holds REGEX
*
***********************************************************************************************************/

class Mats_Used {
	public $mu_id;  // instance id of material used
	public $quantity_used;  // quanitity of a material used for instance
	public $trans_id;  // transaction id
	// objects
	public $material;  // material object used
	public $staff;  // staff member object of last change to materials used DB
	public $status;  // status of material usage from DB
	
	public function __construct($mu_id){
		global $mysqli;
		
		if (!preg_match("/^\d+$/", $mu_id)) throw new Exception('Invalid Mats_Used ID');

		if ($result = $mysqli->query("
			SELECT *
			FROM `mats_used`
			WHERE `mu_id` = $mu_id
			LIMIT 1;"
		)){
			$row = $result->fetch_assoc();
			$this->mu_id = $row['mu_id'];
			$this->trans_id = $row['trans_id'];
			$this->material = new Materials($row['m_id']);
			$this->quantity_used = abs($row['quantity']);
			$this->status = new Status($row['status_id']);
			$this->staff = Users::with_id($row['staff_id']);
		}
	}

	
	// get all mats_used based on trans_id and create objects
	public static function objects_by_trans_id($trans_id){
		global $mysqli;
		$muArray = array();
		
		if ($result = $mysqli->query("
			SELECT *
			FROM mats_used
			WHERE trans_id = $trans_id;
		")){
			while($row = $result->fetch_assoc()) array_push($muArray, new self($row['mu_id']));
		}
		return $muArray;
	}


	/*
	called by Transactions when finishing a ticket to update the OBJ then the DB based on
	new OBJ attributes
	*/
	public function edit_material_used_information($change_array){
		if(!count($change_array)) return;
		
		if(array_key_exists("m_id", $change_array)) $this->material = new Materials($change_array["m_id"]);
		if(array_key_exists("quantity_used", $change_array)) $this->quantity_used = $change_array["quantity_used"];
		if(array_key_exists("status", $change_array)) $this->status = $change_array["status"];
		if(array_key_exists("staff", $change_array)) $this->staff = $change_array["staff"];

		return $this->update_mats_used();
	}


	// updates DB 
	public function end_material_used($staff, $status_id, $quantity_used){
		global $mysqli;

		$this->staff = $staff;
		$this->status = new Status($status_id);
		$this->quantity_used = $quantity_used;

		return $this->update_mats_used();
		
	}


	public static function grouped_materials_HTML($mats_used, $parent_name, $color="DDDDDD") {
		$group_HTML = "";

		$parent_class_name = str_replace(" ", "_", $parent_name);  // class_name
		$group_HTML .= "\n<table id='$parent_class_name-table' class='table table-bordered' style='border-left:#".$color." 2px solid;'>
								<tr class='tablerow' style='background-color:#".$color.";'>
									<td>
										$parent_name
									</td>
								</tr>
								<tr id='$parent_class_name-children_display_row' style='padding-left:2px;'>
									<td width='100%'>";
		// add each material to HTML
		foreach($mats_used as $mu) $group_HTML .= $mu->instance_HTML($readonly);

		// get total amount used of materials in group; display sub-total
		$total_quantity = 0;
		foreach($mats_used as $mu) $total_quantity += $mu->quantity_used;
		$group_HTML .= 	"	</td>
					</tr>
					<tr>
						<td style='background-color:#CCCCCC;'>Sub-Total for $parent_name
							<div class='input-group'>
								<input type='number' id='$parent_class_name-subtotal' class='form-control' autocomplete='off' value='$total_quantity' style='text-align:right;'
								onkeyup='adjust_children_input(this); adjust_balances();' onchange='adjust_children_input(this); adjust_balances();' >
								<span class='input-group-addon'>".$mats_used[0]->material->unit."</span>
							</div>	
						</td>
					</tr>
				</table>";

		return $group_HTML;
	}


	public static function group_materials_by_parent($mats_used) {
		// group materials by parent
		$grouped = $ungrouped = array();
		foreach($mats_used as $mu) {
			$parent_name = $mu->material->m_parent->m_name;
			// combine with previously added 
			if(array_key_exists($parent_name, $grouped))  // group already exists
				$grouped[$parent_name][] = $mu;
			elseif(array_key_exists($parent_name, $ungrouped)) {  // groupable; create group
				$group = array($mu, $ungrouped[$parent_name]);
				$grouped[$parent_name] = $group;
				unset($ungrouped[$parent_name]);
			}
			else $ungrouped[$parent_name] = $mu;  // no parent
		}
		return array("grouped" => $grouped, "ungrouped" => $ungrouped);
	}


	public static function group_mats_used_HTML($mats_used, $readonly=false) {
		$grouped_and_ungrouped = self::group_materials_by_parent($mats_used);
		$grouped = $grouped_and_ungrouped["grouped"];
		$ungrouped = $grouped_and_ungrouped["ungrouped"];

		$colors = array("DD00DD", "0F8DFF", "339933", "FFFF00", "888800", "FF0000");
		$HTML = "<!-- BEGIN Mats_Used::group_mats_used_HTML(./) -->";
		$grouped_keys = array_keys($grouped);
		$x;  // used for index of $colors array
		for($x = 0; $x < count($grouped_keys); $x++)
			$HTML .= self::grouped_materials_HTML($grouped[$grouped_keys[$x]], $grouped_keys[$x], $colors[$x]);

		// add ungrouped materials: placed in table to allow for highlighting
		foreach($ungrouped as $mu) {
			$HTML .= 	"<table class='table table-bordered' style='border-left:#".$colors[$x%6]." 2px solid; '>
							<tr>
								<td>".
									$mu->instance_HTML().
								"</td>
							</tr>
						</table>";
			$x++;
		}
		return $HTML .= "\n<!-- END Mats_Used::group_mats_used_HTML(./) -->";
	}

	
	// create a new instance of material usage in DB.  Optional quanity used
	public static function insert_material_used($trans_id, $m_id, $status_id, $staff, $quantity_used=0, $notes=null) {
		global $mysqli;
		
		//Deny if user is not staff
		if(!$staff->is_staff()) return "Must be staff in order to update";
		
		//Validate input variables
		// optional trans_id
		if($trans_id && !Transactions::regexTrans($trans_id)) return "Bad transaction ID given to create material usage entry";
		if(!Materials::regexID($m_id)) return "Bad material ID #$m_id given to create material usage entry";
		if(!is_numeric($quantity_used)) return "Bad quantity used $quantity_used given to create material usage entry";
		$quantity_used = -$quantity_used;  // invert amount to show consumption

		if($statement = $mysqli->prepare("
			INSERT INTO mats_used
				(`trans_id`,`m_id`,`quantity`, `status_id`, `staff_id`, `mu_notes`) 
			VALUES
				(?, ?, ?, ?, ?, ?);
		")){
			$bind_param = $statement->bind_param("iidiss", $trans_id, $m_id, $quantity_used, $status_id, $staff->id, $notes);
			if($statement->execute()) return $statement->insert_id;
		}
		return $mysqli->error;
	}


	// create an HTML table for the input, select display of mat_used's instance
	public function instance_HTML($readonly=false) {
		global $STATUS, $SITE_VARS;

		$parent_class_name = $this->material->m_parent ? str_replace(" ", "_", $this->material->m_parent->m_name) : "";

		// color_hex ascription
		if($this->material->color_hex)
			$color_hex = "<div class='color-box' style='background-color:#".$this->material->color_hex.";' align='left'></div>";
		// input acription
		if($this->material->is_measurable) {
			if($this->material->unit == "hour(s)" || $this->material->unit == "hours")
				$input = 	"<tr>
								<td>
									<div class='input-group'>
										<span class='input-group-addon'><i class='$SITE_VARS[currency]'></i> ".sprintf("%0.2f", $this->material->price)." x </span>
										<input type='number' id='$this->mu_id-input' class='form-control mat_used_input time $parent_class_name-input' 
										onkeyup='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' 
										onchange='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' 
										autocomplete='off' style='text-align:right;' min='$min_hours' step='1' value='$hour'>
										<span class='input-group-addon'>Hours</span>

										<input type='number' id='$this->mu_id-minute' class='form-control time' 
										onkeyup='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' 
										onchange='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' 
										autocomplete='off' style='text-align:right;' min='0' max='59' value='$minute'>
										<span class='input-group-addon'>Minutes</span>
									</div>
								</td>
							</tr>";
			else $input = 	"<tr>
								<td>
									<div class='input-group'>
										<span class='input-group-addon'><i class='$SITE_VARS[currency]'></i> ".sprintf("%0.2f", $this->material->price)." x </span>
										<input type='number' id='$this->mu_id-input' class='form-control mat_used_input $parent_class_name-input' 
										onkeyup='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' 
										onchange='adjust_parent_input(this); adjust_status_for_input(this); adjust_balances();' 
										autocomplete='off' value='".sprintf("%0.2f", $this->quantity_used)."' style='text-align:right;' min='0'/>
										<span class='input-group-addon'>".$this->material->unit."</span>
									</div>
								</td>
							</tr>";
		}
		$measurability = $this->material->is_measurable ? "measurable" : "immeasurable";

		$HTML = 	"\n<!-- BEGIN Mats_Used::instance_HTML(/) -->
					<table id='$this->mu_id-table' class='table table-bordered $parent_class_name-child'>
						<tr class='info'>
							<td>
								".$this->material->m_name." $color_hex
							</td>
						</tr>
						$input
						<tr>
							<td>
								<select id='$this->mu_id-select' class='form-control mat_used_select $measurability' 
								onchange='adjust_ticket_status(this); adjust_input_for_status(this);'>
									<option selected hidden>SELECT</option>
									<option value='$STATUS[used]'>Used</option>
									<option $default_selection value='$STATUS[unused]'>Not Used</option>
									<option value='$STATUS[failed_mat]'>Failed Material</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<button class='btn btn-default' onclick='split(".$this->material->m_id.");'>Split ".$this->material->m_name."</button>
							</td>
						</tr>
					</table>
					<!-- END Mats_Used::instance_HTML(/) -->";
		return $HTML;
	}


	// get the number of units of a material in DB
	public static function units_in_system($m_id) {
		global $mysqli;

		if (preg_match("/^\d+$/", $m_id)) {
			if($result = $mysqli->query("
				SELECT SUM(quantity) as `sum`
				FROM `mats_used`
				WHERE `m_id` = '$m_id';
			"))
				return $result->fetch_object()->sum;
		}
		return false;
	}


	// writes all variables to the DB for this instance
	public function update_mats_used() {
		global $mysqli;

		$quantity_used = -$this->quantity_used;

		// update transaction info
		$statement = $mysqli->prepare("UPDATE `mats_used`
											SET `m_id` = ?, `quantity` = ?, 
											`status_id` = ?, `staff_id` = ?
											WHERE `mu_id` = ?;");
		if(!$statement) return "Could not update materials used (DB prepare statement failed)";

		// quantity used is negative b/c using a mat always takes away from inventory
		$statement->bind_param("dddsd", 
									$this->material->m_id, $quantity_used,
									$this->status->status_id, $this->staff->id, 
									$this->mu_id);
		if(!$statement->execute()) return "Could not update transaction values";

		return null;  // no errors
	}


	// ———————————————— ATTRIBUTES —————————————————

	public function getMu_date() {
		global $SITE_VARS;
		return date($SITE_VARS['dateFormat'],strtotime($this->date));
	}


	// ————————————————— REGEX ——————————————————

	public static function regexID($m_id){
		return preg_match("/^\d+$/", $m_id);
	}

	// no quanitity is a valid quantity
	public static function regexUnit_Used($quantity_used){
		if(!$quantity_used || preg_match("/^-?\d{0,5}\.{0,1}\d{0,2}$/", $quantity_used) && $quantity_used >= 0)
			return true;
		return false;
	}

	public static function regexReason($reason) {
		return htmlspecialchars($reason);
	}

	public static function regexStatus($status_id) {
		if(preg_match("/^\d+$/", $status_id)) return intval($status_id);
		return false;	 
	}

	public static function regexQuantity($quantity) {
		if(preg_match('/^[0-9]{1,7}+(\.[0-9]{1,2})?$/', $quantity) || is_numeric($quantity)) 
			return floatval($quantity);
		return false;
	}	
}

?>