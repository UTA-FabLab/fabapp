<?php 

/***********************************************************************************************************
*	
*	@author MPZinke on 06.11.19
*	Edited by: MPZinke on 06.11.19 to improve commenting an logic/functionality of class
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.94
*		-House Keeping (DB cleanup, $status variable, class syntax/functionality)
*		-Multiple Materials
*		-Off-line Mode
*		-Sheet Goods
*		-Storage Box
*
*	DESCRIPTION: Instance mostly called by Device() member.  Holds & gets information 
*	 from DB
*	 
*
***********************************************************************************************************/
/***********************************************************************************************************
*	
*	FUTURE: -Allow for a transaction to be stored across multiple areas
*
***********************************************************************************************************/

// create instance of storage-side information of a transaction
class StorageObject {

	// storage_start & storage_end are mutually exclusive—info for both will never be had
	public $trans_id;  // transaction object of ticket
	// `storage_box` data
	public $box_id;  // location in text eg '1B'
	public $staff;  // staff object of fablab employee who last moved object
	public $storage_start; // if object currently in storage, item_change_time; else null


	public function __construct($trans_id) {
		if(!is_numeric($trans_id)) return;
		$this->trans_id = $trans_id;

		global $mysqli;
		if($results = $mysqli->query("SELECT *
										FROM `storage_box`
										WHERE `trans_id` = '$trans_id';"
		)) {
			//FUTURE: not yet designed to handle more than 1 transaction
			if(!$results->num_rows) throw new Exception("$trans_id has no objects in storage");
			else {
				$row = $results->fetch_assoc();
				$this->box_id = $row['drawer'].$row['unit'];
				$this->staff = Users::withID($row['staff_id']);
				$this->storage_start = $row['item_change_time'];
			}
		}
	}


	// add an item to a location.  If already in storage, remove from previous location
	public function add_object_to_location_from_possible_previous($location, $staff, $trans_id) {
		global $mysqli, $sv;

		// clear previous storage box if exists
		if(self::object_is_in_storage($trans_id)) self::remove_object_from_storage($staff, $trans_id);

		// move to new location
		$indicators = StorageDrawer::drawer_and_unit_dict_from_combined_box_label($location);
		// adjust the binding_parameters for the box-unit relationship (alph-num or num-alpha)
		$execute_type = $sv['strg_drwr_indicator'] == "numer" ? "dsds" : "dssd";

		$statement = $mysqli->prepare("UPDATE `storage_box`
											SET `trans_id` = ?, `item_change_time` = CURRENT_TIMESTAMP, `staff_id` = ?
											WHERE `drawer` = ? AND `unit` = ?;");
		$statement->bind_param($execute_type, $trans_id, $staff->operator, $indicators['drawer'], $indicators['unit']);

		if(!$statement->execute()) return "Could not update storage box values";
		return null;  // no errors
	}


	public static function all_in_storage_for_operator($operator) {
		global $mysqli;

		$tickets = array();
		if($results = $mysqli->query("	SELECT `storage_box`.`trans_id`
										FROM `storage_box`
										JOIN `transactions`
										ON `transactions`.`trans_id` = `storage_box`.`trans_id`
										LEFT JOIN `authrecipients`
										ON `authrecipients`.`trans_id` = `storage_box`.`trans_id`
										WHERE `transactions`.`operator` = '$operator->operator'
										OR `authrecipients`.`operator` = '$operator->operator'
										ORDER BY `storage_box`.`trans_id` ASC;"
		)) {
			while($row = $results->fetch_assoc())
				$tickets[] = new Transactions($row["trans_id"]);
			return $tickets;
		}
	}


	public function edit_object_storage_information($change_array) {
		if(!count($change_array)) return;
		
		if(array_key_exists("staff", $change_array)) $this->staff = $change_array["staff"];
		if(array_key_exists("box_id", $change_array)) $this->box_id = $change_array["box_id"];
		if(array_key_exists("storage_start", $change_array)) $this->storage_start = $change_array["storage_start"];

		return $this->update_storage_box();
	}


	public static function find_objects_in_storage_for_user($user) {
		if(is_object($user)) $operator = $user->operator;

		$users_objects = array();
		global $mysqli;
		if($results = $mysqli->query("SELECT `transactions`.`trans_id` 
										FROM `transactions`
										JOIN `authrecipients` 
										ON `transactions`.`trans_id` = `authrecipients`.`trans_id`
										WHERE `transactions`.`pickup_time` IS NULL
										AND (`transactions`.`operator` = '$operator' OR `authrecipients`.`operator` = '$operator');"
		)) {
			while($row = $results->fetch_assoc()) $users_objects[] = new self($row['trans_id']);
			return $users_objects;
		}
		return "Unable to check results";
	}


	public static function number_of_objects_in_storage() {
		global $mysqli;

		if($results = $mysqli->query("SELECT COUNT(`drawer`) as count
										FROM `storage_box`
										WHERE `trans_id` IS NOT NULL;"
		)) return $results->fetch_assoc()["count"];
		return "Could not get count";
	}


	// boolean result of true if object is in storage, false if not; able to have obj in multiple storage boxes
	public static function object_is_in_storage($trans_id) {
		global $mysqli;

		if($results = $mysqli->query("SELECT `trans_id`
										FROM `storage_box`
										WHERE `trans_id` = '$trans_id';"
		)) return $results->num_rows != 0;
	}


	public static function remove_object_from_storage($staff, $trans_id) {
		global $mysqli;

		$statement = $mysqli->prepare("UPDATE `storage_box`
											SET `trans_id` = NULL, `item_change_time` = CURRENT_TIMESTAMP, `staff_id` = ?
											WHERE `trans_id` = ?;");
		$statement->bind_param("sd", $staff->operator, $trans_id);
		if(!$statement->execute()) return "Could not update storage box values";
	}


	public function update_storage_box() {
		global $mysqli;

		// update storage box info
		$statement = $mysqli->prepare("UPDATE `storage_box`
											SET `item_change_time` = ?, `staff_id` = ?
											WHERE `trans_id` = ?;");
		$statement->bind_param("ssd", $this->storage_start, $this->staff->operator, $this->trans_id);
		if(!$statement->execute()) return "Could not update storage box values";

		return null;  // no errors
	}


	// should be initiated before every StorageObject method call to identify person operating on object
	public function set_staff($staff) {
		if(!is_object($staff)) return false;
		$this->staff = $staff;
		return true;
	}
}


/*
Unit (drawer subsection) object to hold information about its location, size, start;
methods to operate on drawer units
*/
class StorageUnit {

	public $box_id;  // string for box unit  (eg 1A or C11)
	public $drawer_indicator;  // drawer or shelf letter or number
	public $unit_indicator;  // sub category inside the drawer
	public $start;  // subdivision start of drawer or shelf [rows, columns]
	// subdivision end of drawer or shelf [rows, columns]; 
	// indicates additional divisions, because a 1x1 unit's end == start (5-5 minus 5-5 = 0-0)
	public $span;  
	public $contained_cells;
	public $trans_id;  // trans_id of stored item
	public $item_change_time;  // date of when item was placed/stored in unit
	public $staff;  // staff member to insert/remove item
	public $type;  // what the section label will be (eg small, large, glass, etc)

	public function __construct($box_id) {
		global $mysqli;
		global $sv;

		// ---- get and sort data to populate fields ----
		$this->box_id = $box_id;
		if($sv['strg_drwr_indicator'] == "numer") {
			$this->drawer_indicator = preg_replace('/[^0-9]/', '', $box_id);
			$this->unit_indicator = preg_replace( '/[^a-zA-Z]/', '', $box_id);
		}
		else {
			$this->drawer_indicator = preg_replace( '/[^a-zA-Z]/', '', $box_id);
			$this->unit_indicator = preg_replace('/[^0-9]/', '', $box_id);
		}

		if($results = $mysqli->query("SELECT *
										FROM `storage_box`
										WHERE `drawer` = '$this->drawer_indicator'
										AND `unit` = '$this->unit_indicator';
		")) {
			if($results->num_rows == 1) {
				$unit = $results->fetch_assoc();
				$this->start = array_map('intval', explode("-", $unit['start']));
				$this->span = array_map('intval', explode("-", $unit['span']));
				$this->trans_id = $unit['trans_id'];
				$this->item_change_time = $unit['item_change_time'];
				$this->staff = $unit['staff_id'];
				$this->type = $unit['type'];
				$this->set_contained_cells();
			}
		}
		else {
			// delete self cause it done messed up
		}

	}


	// return a HTML cell based on StorageUnit's attributes & values passed; replace '_' with cell label
	public function cell($td_values) {
		$colspan = $this->span[1] + 1;
		$rowspan = $this->span[0] + 1;
		$width = 50 * $colspan;

		$td_values["id"] = $td_values["id"] ? $td_values["id"] : $this->unit_indicator;
		$td_values['label'] = $td_values["label"] ? str_replace("_", $this->unit_indicator, $td_values["label"]) : $this->unit_indicator;

		return "<td id='$td_values[id]' class='$td_values[class]' colspan='$colspan' rowspan='$rowspan' style='width:${width}px;$td_values[style]'
				onclick='$td_values[onclick]' onmouseover='$td_values[onmouseover]' onmouseout='$td_values[onmouseout]' align='center'>$td_values[label]</td>";
	}


	// add a new StorageUnit to DB
	public static function create_new_partition($drawer, $unit, $start, $span, $type, $size = false) {
		if(!$size) $size = StorageDrawer::get_drawer_size($drawer);

		$drawer = StorageDrawer::regex_drawer_indicator($drawer);
		$unit = self::regex_unit_indicator($unit);
		$size = StorageDrawer::regex_drawer_size($size);
		$start = self::regex_unit_start($start);
		$span = self::regex_unit_span($span);
		$type = self::regex_unit_type($type);

		global $mysqli;
		$statement = $mysqli->prepare("INSERT INTO `storage_box` (`drawer`, `unit`, `drawer_size`, `start`, `span`, `type`) VALUES 
						(?, ?, ?, ?, ?, ?);");
		$statement->bind_param("ssssss", $drawer, $unit, $size, $start, $span, $type);
		if($statement->execute()) return true;
		return false;
	}


	public static function currently_holds_an_item($drawer, $unit) {
		$drawer = StorageDrawer::regex_drawer_indicator($drawer);
		$unit = self::regex_unit_indicator($unit);

		global $mysqli;
		if($results = $mysqli->query("SELECT *
										FROM `storage_box`
										WHERE `trans_id` IS NOT NULL
										AND `drawer` = '$drawer'
										AND `unit` = '$unit';"
		)) {
			if($results->num_rows == 0) return false;
		}
		return true;  // could not query or size not 0, so assume drawer holds item
	}


	public static function delete_unit($drawer, $unit) {
		$drawer = StorageDrawer::regex_drawer_indicator($drawer);
		$unit = self::regex_unit_indicator($unit);

		if(self::currently_holds_an_item($drawer, $unit)) return "Cannot delete unit because unit is not empty";

		global $storage_box_DB_user;
		$statement = $storage_box_DB_user->prepare("	DELETE FROM `storage_box`
																WHERE `drawer` = ?
																AND `unit` = ?;");
		$statement->bind_param("ss", $drawer, $unit);
		if($statement->execute()) return null;
		return "Failed to delete unit";
	}


	// find the unit currently holding the sought object; return object of unit
	public function get_unit_for_trans_id($trans_id) {
		global $mysqli;

		return $unit;
	}


	public function position_is_a_part_of_unit($position) {
		return ($this->start[0] <= $position[0] && $position[0] <= $this->start[0] + $this->span[0] &&
		$this->start[1] <= $position[1] && $position[1] <= $this->start[1] + $this->span[1]);
	}


	public function starting_position_is_current_cell($position) {
		return $position === $this->start;
	}


	// get the different cells for a unit
	public function set_contained_cells() {
		for($x = $this->start[0]; $x <= $this->start[0] + $this->span[0]; $x++) {
			for($y = $this->start[1]; $y <= $this->start[1] + $this->span[1]; $y++) {
				$this->contained_cells[] = array($x, $y);
			}
		}
	}


	// query the different types of drawers currently in DB
	public static function types() {
		global $mysqli;

		$types = array();
		if($results = $mysqli->query("SELECT `type`
										FROM `storage_box`
										GROUP BY `type`;"
		)) {
			while($row = $results->fetch_assoc())
				$types[] = $row['type'];
			return $types;
		}
		return null;
	}


	// ————————————————— REGEX ——————————————————

	public static function regex_unit_indicator($id) {
		return htmlspecialchars($id);
	}

	public static function regex_unit_span($span) {
		return htmlspecialchars($span);
	}

	public static function regex_unit_start($start) {
		return htmlspecialchars($start);
	}

	public static function regex_unit_type($type) {
		return htmlspecialchars($type);
	}

}

/***********************************************************************************************************
*	
*	Object of data for a drawer
*	Takes in the drawer indicator and builds units based on query from it.  Behaviors dictate
*	appearance of unit cells when drawn to a table.  Selected unit is the unit that the table 
*	highlights
*	FUTURE: default behaviors
*
***********************************************************************************************************/


class StorageDrawer {
	public $drawer_indicator;
	public $units = array();  // OBJs—StorageUnit: all units from DB (default & selected_units)
	public $drawer_size;  // number of division for drawer or shelf [rows, columns]
	public $unit_behavior;  // array of key/values for display/function of drawer display: for units in DB
	public $empty_behavior;  // array of key/values for display/function of drawer display: for unallocated space
	// item specific
	public $select_unit_callback;  // function—instructions of which units for drawer are desired
	public $selected_units = array();  // OBJs—StorageUnit: unit that is desired
	public $selected_unit_behavior;  // array of key/values for display/function of drawer display: for units that have matching trans_id

	public function __construct($drawer_id, $unit_behavior, $empty_behavior, $select_unit_callback=null, $selected_unit_behavior=null) {
		$this->drawer_indicator = self::regex_drawer_indicator($drawer_id);
		$this->units = self::get_units_for_drawer($this->drawer_indicator);
		$this->drawer_size = array_map('intval', explode("-", self::get_drawer_size($this->drawer_indicator)));
		$this->unit_behavior = $unit_behavior;
		$this->empty_behavior = $empty_behavior;

		$this->select_unit_callback = $select_unit_callback;
		$this->determine_selected_units();
		$this->selected_unit_behavior = $selected_unit_behavior;
	}
	

	/*
	called by HTML_display(): creates the necessary cell data for the table that
	displays the drawer
	*/
	public function add_cell_to_table($position) {
		foreach($this->units as $unit) {
			if($unit->starting_position_is_current_cell($position)) {
				if(count($this->selected_units) && in_array($unit, $this->selected_units))
					return $unit->cell($this->selected_unit_behavior);
				return $unit->cell($this->unit_behavior);
			}
		}
		// if cell is a part of unit, but not start, do nothing
		foreach ($this->units as $unit)
			if(in_array($position, $unit->contained_cells)) return;
		// cell is not part of unit
		return $this->unitless_cell(implode('-', $position), implode('-', $position));
	}


	// copy the drawer layout in the DB to a new drawer with the provided designator
	public static function copy_drawer($new_drawer_name, $old_drawer_name) {
		global $mysqli;

		$new_drawer_name = StorageDrawer::regex_drawer_indicator($new_drawer_name);
		$old_drawer_name = StorageDrawer::regex_drawer_indicator($old_drawer_name);

		if(!$new_drawer_name || !$old_drawer_name) return false;

		$statement = $mysqli->prepare("INSERT INTO `storage_box` (`drawer`, `unit`, `drawer_size`, `start`, `span`, `type`)
    						SELECT ?, `unit`, `drawer_size`, `start`, `span`, `type` 
    						FROM `storage_box` 
    						WHERE `drawer` = ?;");

		$statement->bind_param("ss", $new_drawer_name, $old_drawer_name);
		if($statement->execute())
			return true;
		return false;
	}


	public static function delete_drawer($drawer) {
		$drawer = self::regex_drawer_indicator($drawer);

		if(self::drawer_currently_holds_any_item($drawer)) return "Can not delete drawer because drawer is not empty";

		global $storage_box_DB_user;
		$statement = $storage_box_DB_user->prepare("DELETE FROM `storage_box`
											WHERE `drawer` = ?;");
		$statement->bind_param("s", $drawer);
		if($statement->execute()) return null;
		return "Failed to delete drawer";
	}


	/*
	go through units in drawer and select unique drawers based on call back.  Unique 
	drawers are added to a list where their attributes will be different based on 
	$selected_unit_behavior
	*/
	public function determine_selected_units($select_unit_callback=null) {
		$select_unit_callback = $select_unit_callback ? $select_unit_callback : $this->select_unit_callback;

		if(!$select_unit_callback) return;  // nothing to call
		$this->selected_units = array();  // clear previously selected units
		foreach($this->units as $unit) {
			if(call_user_func($select_unit_callback, $unit)) $this->selected_units[] = $unit;
		}
	}


	// html button that displays table and highlights unit for unit
	public function display_tooltip($box_id) {
		return "<button type='button' class='btn btn-default' data-toggle='tooltip' data-html='true' title='' data-original-title=\"".$this->HTML_display()."\">
					$box_id
				</button>";
	}


	// dictionary of {"drawer" : drawer, "unit" : unit} from $box_id (eg 4B or F17)
	public static function drawer_and_unit_dict_from_combined_box_label($box_id) {
		global $sv;

		$dict = array('drawer' => $sv['strg_drwr_indicator'] == "numer" ? preg_replace('/[^0-9]/', '', $box_id) :  preg_replace( '/[^a-zA-Z]/', '', $box_id));
		$dict['unit'] = $sv['strg_drwr_indicator'] == "numer" ? preg_replace( '/[^a-zA-Z]/', '', $box_id) : preg_replace('/[^0-9]/', '', $box_id);

		return $dict;
	}


	public static function drawer_currently_holds_any_item($drawer) {
		$drawer = self::regex_drawer_indicator($drawer);

		global $mysqli;
		if($results = $mysqli->query("SELECT *
										FROM `storage_box`
										WHERE `trans_id` IS NOT NULL
										AND `drawer` = '$drawer';"
		)) {
			if($results->num_rows == 0) return false;
		}
		return true;  // could not query or size not 0, so assume drawer holds item
	}


	// return boolean of if the input drawer exists
	public static function drawer_exists($drawer) {
		$drawer = self::regex_drawer_indicator($drawer);

		global $mysqli;
		if($results = $mysqli->query("SELECT *
										FROM `storage_box`
										WHERE `drawer` = '$drawer';"
		)) {
			return $results->num_rows > 0;
		}
	}


	public static function drawer_units_labels($drawer) {
		$drawer = self::regex_drawer_indicator($drawer);

		global $mysqli;
		if($results = $mysqli->query("SELECT `unit`
										FROM `storage_box`
										WHERE `drawer` = '$drawer';"
		)) {
			$unit_indicators_for_drawer = array();
			while($row = $results->fetch_assoc())
				$unit_indicators_for_drawer[] = $row['unit'];
			return $unit_indicators_for_drawer;
		}
	}


	public static function get_all_drawers($unit_behavior, $empty_behavior, $selected_unit_callback=null, $selected_unit_behavior=null) {
		$drawer_labels = StorageDrawer::get_unique_drawers();
		$drawers = array();
		foreach($drawer_labels as $label)
			$drawers[] = new StorageDrawer($label, $unit_behavior, $empty_behavior, $selected_unit_callback, $selected_unit_behavior);
		return $drawers;
	}


	public static function get_a_drawer_and_unit_for_type($type) {
		global $mysqli;

		if($results = $mysqli->query("SELECT `drawer`, `unit`
										FROM `storage_box`
										WHERE `type` = '$type'
										AND `trans_id` IS NULL
										LIMIT 1;"
		)) {
			if($results->num_rows == 1) {
				$row = $results->fetch_assoc();
				$drawer = $row['drawer'];
				$unit_indicator = $row['unit'];

				// might be good to pass these instead of hardcoding them
				$basic_unit = array("style" => "background-color:#999999;border:solid;border-width:2px;", 
									"onclick" => "alert(\"Incorrect box\");");
				$empty = array("style" => "background-color:#000000;color:#000000;border:solid black;border-width:2px;");
				$selected_behavior = array("style" => "background-color:#00FF00;border:solid black;border-width:2px;", 
									"onclick" => "storage_selected(this, \"$drawer-$unit_indicator\")");

				$select_unit_callback = function($drawer_unit) use ($unit_indicator) {return $drawer_unit->unit_indicator == $unit_indicator;};
				return new StorageDrawer($drawer, $basic_unit, $empty, $select_unit_callback, $selected_behavior);
			}
			return "There are no free slots for an object of that type";
		}
		return "Failed to search storage";
	}


	public static function get_numeric_drawer_size($drawer) {
		global $mysqli;

		if($results = $mysqli->query("SELECT `drawer_size` 
										FROM `storage_box` 
										WHERE `drawer` = '$drawer' 
										GROUP BY `drawer`;
		")) {
			return array_map('intval', explode("-", $results->fetch_assoc()['drawer_size']));
		}
		return NULL;
	}


	public static function get_drawer_containing_item($trans_id) {

	}


	// return array of objects for all units with the desired row id
	public static function get_units_for_drawer($drawer) {
		$drawer = self::regex_drawer_indicator($drawer);

		global $mysqli;

		$row_units = array();
		if($results = $mysqli->query("SELECT `drawer`, `unit`
										FROM `storage_box`
										WHERE `drawer` = '$drawer';"
		)) {
			while($storage_unit = $results->fetch_assoc())
				$row_units[] = new StorageUnit($storage_unit['drawer'].$storage_unit['unit']);
		}
		return $row_units;
	}


	public static function get_drawer_size($drawer) {
		global $mysqli;

		if($results = $mysqli->query("SELECT `drawer_size` 
										FROM `storage_box` 
										WHERE `drawer` = '$drawer' 
										GROUP BY `drawer`;
		")) {
			return $results->fetch_assoc()['drawer_size'];
		}
		return NULL;
	}



	public static function get_unique_drawers($type = false) {
		global $mysqli;

		$drawer_designations = array();
		if($type) $results = $mysqli->query("	SELECT DISTINCT `drawer`
												FROM `storage_box`
												WHERE `type` = '$type';");
		else $results = $mysqli->query("	SELECT DISTINCT `drawer`
											FROM `storage_box`
											ORDER BY CAST(`drawer` AS unsigned);");

		if($results) {
			while($drawer = $results->fetch_assoc()) {
				$drawer_designations[] = $drawer['drawer'];
			}
			return $drawer_designations;
		}
		return array("Could not get drawer information");
	}


	// create HTML table to display drawer in accordance to set behaviors
	public function HTML_display() {
		$max_width = 50 * $this->drawer_size[1];
		$table = "<table style='max-width:${max_width}px;'> ";
		for($x = 1; $x <= $this->drawer_size[0]; $x++) {
			$row = " <tr style='height:50px;'> ";
			for($y = 1; $y <= $this->drawer_size[1]; $y++)
				$row .= $this->add_cell_to_table(array($x, $y));
			$table .= $row."</tr>";
		}
		$table .= "
			<tr> 
				<td colspan='".$this->drawer_size[1]."' align=CENTER style='font-size:1.5em'>
					<span style='text-decoration:overline'><strong>Drawer Front</strong></span>
				</td> 
			<tr> 
		</table>";
		return $table;
	}


	public static function no_unit_is_using_cell($drawer, $position) {
		foreach($drawer as $unit) {
			if($unit->position_is_a_part_of_unit($position)) return false;
		}
		return true;
	}

	/*
	called by add_cell_to_table(): creates a cell for the matrix of a drawer that is not stated in 
	DB
	eg in a 5x5 matrix if only 20 of the cells are known to the DB, this function will handle the 
	other 5
	*/
	public function unitless_cell($id, $label) {
		$attrs = $this->empty_behavior;
		return "<td id='$id' class='$attrs[class]' style='width:50px;$attrs[style]' onclick='$attrs[onclick]' 
		onmouseover='$attrs[onmouseover]' onmouseout='$attrs[onmouseout]' align='center'>$label</td>";
	}


	// ————————————————— REGEX ——————————————————

	public static function regex_drawer_indicator($name) {
		return htmlspecialchars($name);
	}

	public static function regex_drawer_size($size) {
		return htmlspecialchars($size);
	}
}


?>