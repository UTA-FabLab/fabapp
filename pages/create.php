<?php

/***********************************************************************************************************
*	
*	@author Jon Le
*	Edited by: MPZinke on 07.25.19 to allow for multiple material assigning to ticket. Improve 
*	 commenting an logic/functionality of page; update in accord with class & material 
*	 changes
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.94
*		-Multiple Materials
*		-Off-line Mode
*		-Sheet Goods
*		-Storage Box
*		-House Keeping (DB cleanup, $status variable, class syntax)
*
*	FUNCTION: Create transaction for device, operator & possible information.
*	DESCRIPTION/PROCESS: -Check if staff logged in: ! -> go to home
*	 -GET device ID & create device OBJ.
*	 -Echo to page HTML including device->->required_materials tags.  If device is prepaid, 
*	  add quantitiy inputs & adjust to whether material is time.
*	 -Echo device->->optional_materials as options. JS handles tag creation including 
*	  whether tag has a quantity input & what type.  Time quantities are not required to match
*	  so that the ticket can split time into multiple categories, however the minTime is set for
*	  each of them individually.
*	 -JS checks for Operator ID & Purpose.
*	-Backend calls a function corresponding to the ticket type which pulls required & optional 
*	  values through get_tags(.) function & checks that the number of pulled required 
*	  materials equals listed required.  This pulls from the array of tags passed to 
*	  it.
*	 -The two arrays are then combined and have keys renamed to a consolidated form.
*	 -A transaction is created in DB and materials are assign (if any).
*	 -User is redirected to next step (page) in process.
*
*	FUTURE: Add ability to preselect where the object will go if storable
*	QUESTIONS: Do we still care about est_time?
*
***********************************************************************************************************/


require_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/api/gatekeeper.php');

// restrict page to staff
if(!$staff) {
	$_SESSION['error_msg'] = "Please log in";
	header("Location:/index.php");
}
elseif($staff && $staff->getRoleID() < $role['staff']) {
	$_SESSION['error_msg'] = "Please ask a staff member for assistance";
	header("Location:/index.php");
}

// Check Device ID
if(!isset($_GET["d_id"]) || !Devices::regexDeviceID(filter_input(INPUT_GET, 'd_id'))) {  //REMOVE WITH UPDATE
// if(!isset($_GET["device_id"]) || !Devices::regexDeviceID(filter_input(INPUT_GET,'device_id'))) {  //ADD WITH UPDATE
	exit_if_error("Device ID is missing or invalid", "/index.php");
}

$device_id = filter_input(INPUT_GET, "d_id");  //REMOVE WITH UPDATE
// $device_id = filter_input(INPUT_GET,'device_id');  //ADD WITH UPDATE
$device = new Devices($device_id);

//TODO: check that device is not in use

// convert the time limit of a device to decimal form
if($device->time_limit) {
	$timeArry = explode(':', $device->time_limit);
	$lime_limit = $timeArry[0] + $timeArry[1] / 60;
}



if (array_key_exists("operator", $_GET) && Users::regexUser($_GET["operator"]))
	$operator = Users::withID($_GET['operator']);

// create ticket creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ticketBtn'])) {
	//set status id to "Powered On"
	$status_id = 0;
	
	//Call Gatekeeper to regex UTAID and validate if User is authorized
	foreach (gatekeeper($_POST["operator"], $device->device_id) as $key => $value) $gk_msg[$key] =  $value;
	
	if ($gk_msg["authorized"] == "N")
		exit_if_error("Status Code:".$gk_msg["status_id"]." - ".$gk_msg["ERROR"]);
	else {
		$status_id = $gk_msg['status_id'];
		$operator = Users::withID(filter_input(INPUT_POST, 'operator'));
	}

	
	// purpose
	$p_id = filter_input(INPUT_POST, 'p_id');
	if(!Purpose::regexID($p_id)) exit_if_error("Invalid Purpose Code : $p_id");
	
	// start ticket
	if($device->device_group->is_pay_first) pay_first_ticket($operator, $device, $p_id, $staff);
	elseif($device->device_group->is_select_mats_first) select_materials_first_ticket($operator, $device, $p_id, $staff);
	else ticket_without_materials($operator, $device, $p_id, $staff);

}


// ticket requires payment beforehand; pass details and move to pay.php
function pay_first_ticket($operator, $device, $p_id, $staff) {
	global $status;

	$required = get_tags(array("required", "required-amount", "required-hour", "required-minute"));  // get material amounts & IDs
	if(count($required) != count($device->device_group->required_materials))  // check for inconsistency in required vs gathered
		exit_if_error("Problem retrieving material quantities for DB storing");

	// add arrays together and switch keys to a ubiquitious name
	$optional = get_tags(array("optional", "optional-amount", "optional-hour", "optional-minute"));
	$materials = combine_arrays_and_consolidate_keys($optional, $required);  // replace 'required' in key with material

	// contains valid quanities & IDs
	foreach($materials as $material) 
		if(($material["amount"] && !Mats_Used::regexQuantity($material["amount"])) || !Materials::regexID($material["m_id"]))
			exit_if_errro("Invalid material quantity–$material[amount]");

	// create new transaction
	$trans_id = Transactions::insert_new_transaction($operator, $device->device_id, null, $p_id, $status['active'], $staff);
	if(!is_int($trans_id))
		exit_if_error("Can not create a new ticket–$trans_id");

	// create new mats_used instance for material
	foreach($materials as $material)
		if(!is_int($mu_id = Mats_Used::insert_material_used($trans_id, $material["m_id"], $status["used"], $staff, $material["amount"])))
			exit_if_error("Problem associating materials to ticket–$mu_id");

	header("Location:pay.php?trans_id=$trans_id");
}


// new transaction: no materials; no initial payment
function ticket_without_materials($operator, $device, $p_id, $staff) {
	global $status;

	if(!is_int($trans_id = Transactions::insert_new_transaction($operator, $device->device_id, null, $p_id, $status['active'], $staff)))
		exit_if_error("Can not create a new ticket–$trans_id");
	header("Location:lookup.php?trans_id=$trans_id");  // trans_id is not error message; proceed to next part
}


// start a ticket with materials chosen first
function select_materials_first_ticket($operator, $device, $p_id, $staff) {
	global $status;

	$required = get_tags(array("required"));
	if(count($required) != count($device->device_group->required_materials))
		exit_if_error("Problem retrieving material quantities for DB storing");

	$optional = get_tags(array("optional"));
	$materials = combine_arrays_and_consolidate_keys($required, $optional);

	// contains valid m_id's
	foreach($materials as $material)
		if(!Materials::regexID($material['m_id']))
			exit_if_error("Problem reading material id–$material[m_id]");

	// create new transaction
	if(!is_int($trans_id = Transactions::insert_new_transaction($operator, $device->device_id, null, $p_id, $status['active'], $staff)))
		exit_if_error("Can not create a new ticket–$trans_id");

	// create new mats_used instance for material
	foreach($materials as $material)
		if(!is_int($mu_id = Mats_Used::insert_material_used($trans_id, $material['m_id'], 0, $staff)))
			exit_if_error("Problem associating materials to ticket–$mu_id");

	header("Location:lookup.php?trans_id=$trans_id");
}


// ——————————————— VALUES FROM FORM ———————————————

// pulls upto 100 tags (custom material display divs in selected materials)
function get_tags($tags) {
	$count = 1;
	$values = array();

	do {
		$var = array();  // temp array to hold gotten tags & be added to $values
		foreach($tags as $attribute)
			$var[$attribute] = floatval(filter_input(INPUT_POST, $attribute.'-'.$count));

		if($var[$tags[0]]) $values[] = $var;  // add value to list if not null
	} while($var[$tags[0]] && ++$count < 100);  // stop if !value, terminate value, or gone too long

	return $values;
}


// combine $optional & $required materials; rewrite to array with new einheitliche keys
function combine_arrays_and_consolidate_keys($optional, $required) {
	$materials = array();

	foreach(array_merge($optional, $required) as $mat_assoc) {
		$temp = array();
		// check keys for correct key prefix
		$prefix = array_key_exists("required", $mat_assoc) ? "required" : "optional";
		$temp["m_id"] = $mat_assoc["$prefix"];
		if(($mat_assoc["$prefix-hour"] || $mat_assoc["$prefix-minute"]) && !$mat_assoc["$prefix-amount"])
			$temp["amount"] = intval($mat_assoc["$prefix-hour"]) + floatval($mat_assoc["$prefix-minute"]) / 60;
		else $temp["amount"] = floatval($mat_assoc["$prefix-amount"]);
		$materials[] = $temp;
	}
	return $materials;
}


function exit_if_error($error, $redirect=null) {
	global $device_id;
	if(!$error) return;

	$_SESSION['error_msg'] = $error;
	if($redirect) header("Location:$redirect");
	else header("Location:/pages/create.php?d_id=$device_id");  //REMOVE WITH UPDATE
	// header("Location:/pages/create.php?device_id=$device_id");  //ADD WITH UPDATE
	exit();
}

?>
<title><?php echo $sv['site_name'];?> Create Ticket</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-md-12">
			<h1 class="page-header">Create Ticket for the <?php echo $device->name; ?></h1>
		</div>
		<div class="row">
			<div class="col-md-12">
			<?php
			if(isset($error))
				echo "<h1 class='page-header'>Error</h1>$error";
			else {
			?>
				<div class="panel panel-default">
					<div class="panel-body">
						<form id="cform" name="cform" method="post" action="" onsubmit="return validateForm()" autocomplete='off'>
							<table class="table table-striped table-bordered table-hover">
								<tr class="tablerow">
									<td align="center">ID Number</td>
									<td>
										<input type="number" name="operator" id="operator" class='form-control' placeholder="1000000000" 
										onchange='restrict_size(this);' onkeyup='restrict_size(this);' maxlength="10" size="10" autofocus tabindex="1"/>
									</td>
								</tr>
								<tr class="tablerow">
									<?php // build Purpose Option List
									if($pArray = Purpose::getList()){ ?>
										<td align="Center">
											Purpose of Visit
										</td>
										<td>
											<select id='purpose' name="p_id" class='form-control' tabindex="8">
												<option disabled hidden selected value="">Select</option>
												<?php 
												foreach($pArray as $key => $value)
													echo("<option value='$key'>$value</option>");
											echo "</select>";
									} ?>
								</tr>				  
								<?php
								// if device usage is limited to a max time amount
								if($lime_limit) { ?>
								<tr class="tablerow">
									<td align="center">Max Time</td>
										<td>
											<div class="input-group">
												<input type="text" size="1" name="hours" class='form-control time' disabled value="<?php echo $lime_limit?>"/>
												<span class="input-group-addon">Hours</span>
											</div>
										</td>
									</tr>
								<?php }	

				// ——————————–— MATS CHOSEN FIRST ————————————
								// list all required mats associated with device 
								if($device->device_group->is_pay_first || $device->device_group->is_select_mats_first) {
									if($device->device_group->required_materials) { ?>
										<tr>
											<td align='center'>Required Materials</td>
											<td>
												<?php 
												for($x = 1; $x <= count($device->device_group->required_materials); $x++) {
													$material = $device->device_group->required_materials[$x-1];
													$unit = $material->unit ? $material->unit : "[-]";

													// add quantitiy measurements if payfirst && measurable
													if($material->is_measurable && $device->device_group->is_pay_first) {
														if($material->unit == "hour(s)") {
															$min_hours = intval($sv['minTime']);
															$max_hours = intval($time_limit) ? intval($time_limit) : "";

															$quantity = 	"<div class='input-group'>
																				<span class='input-group-addon'><i class='$sv[currency]'></i> ".sprintf("%0.2f", $material->price)." x </span>
																				<input type='number' id='$material->m_id-input' class='form-control mat_used_input time' value='$min_hours'
																				name='required-hour-$x' onchange='quote();' onkeyup='quote();' autocomplete='off'  step='1'
																				style='text-align:right; max-width:75px; min-width:75px;' min='$min_hours' max='$max_hours'/>
																				<span class='input-group-addon'>Hours</span>

																				<input type='number' id='$material->m_id-minute' name='required-minute-$x' class='form-control time' value='0'
																				onchange='quote();' onkeyup='quote();' autocomplete='off' style='text-align:right; max-width: 100px;' min='0' max='59'/>
																				<span class='input-group-addon'>Minutes</span>
																			</div>";
														}
														elseif($device->device_group->is_pay_first)
															$quantity = 	"<div class='input-group'>
																				<span class='input-group-addon'><i class='$sv[currency]'></i> ".sprintf("%0.2f", $material->price)." x </span>
																				<input type='number' id='$material->m_id-input' name='required-amount-$x' class='form-control mat_used_input' 
																				onchange='quote();' onkeyup='quote();' autocomplete='off' style='text-align:right; max-width:100px;' min='0' value='0'/>
																				<span class='input-group-addon'>$unit</span>
																			</div>";
													}

													// standard tag with quantity (?is_select_mats_first) input
													echo 	"<div style='background-color: rgb(232, 232, 232); border-style: solid; border-radius: 8px; 
															border-width: 10px; display: inline-block; margin: 5px; padding: 8px; text-align: justify; 
															border-color: #$material->color_hex;'>
																<input class='material' readonly hidden value='$material->m_id' name='required-$x'>
																<label>$material->m_name</label>
																$quantity
															</div>";
												}
												?>
											</td>
										</tr>
									<?php 
									} // end required materials
				// ———————————–— OPTIONAL MATS —————————————
									// optional materials for prepaid or select_mats_first
									if($device->device_group->optional_materials) { ?>
									<tr class="tablerow">
										<td align="Center">Optional Materials</td>
										<td>
											<select id="material_select" class='form-control' onchange='add_tag(this);' tabindex="2">
												<option disabled hidden selected value="">—Select—</option>
												<?php
												foreach($device->device_group->optional_materials as $material)
													echo"<option value='$material->m_id'>$material->m_name</option>";
												?>
											</select>
											<div id='selected_materials' style='width:100%;'>
											</div>
										</td>
									</tr>
									<?php
									}  // end optional materials
								}  // end prepaid or select_mats_first
				// ——————————— MATS SELECTED AT END ———————————
								// mats are selected at the end
								elseif(count($device->device_group->optional_materials)) { ?>
									<tr class="tablerow">
										<td align="Center">Material</td>
										<td><b>Indicate the material used when closing this ticket.</b>
											<select name="m_id" id="m_id" tabindex="2" disabled="true" hidden>
												<option selected value="none">None</option>
											</select>
										</td>
									</tr>
								</tr>
								<?php 
								}  // end select at end 
								// is_pay_first price quote
								if($device->device_group->is_pay_first) { ?>
									<tr class="tableheader warning">
										<td id='pay_first_cell' align="center" colspan=2>Payment Required First
											<div id="quote" style="float:right"><i class='<?php echo $sv['currency']; ?> fa-fw'></i> 0.00</div>
										</td>
									</tr>
								<?php } ?>
								<tr class="tablerow">
									<td align="center"><input type="button" class='btn btn-default' onclick="resetForm()" value="Reset form"></td>
									<td align="right"><input type="submit" name="ticketBtn" value="Submit" class='btn btn-default' tabindex="9"></td>
								</tr>
							</table>
						</form>
					</div>
				</div>
			<?php } ?>
			</div>
		</div>
	</div>
</div>

<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>

<script type="text/javascript">

	// —— FROM DB ——
	var currency = "<?php echo "<i class='$sv[currency] fa-fw'></i>"; ?>";
	// the minimum amout of time to be charge for a device
	var minimum_charge_time = parseFloat(<?php echo $sv["minTime"]; ?>);
	// devices time limit
	var time_limit = parseFloat(<?php echo $time_limit; ?>);

	// dictionary of m_id, color_hex, m_name, unit for all optional materials associated with a device
	var device_mat_attrs = {
		<?php  // echo materials as a dictionary
		$materials = $device->device_group->optional_materials;
		echo implode(",\n", 
				array_map(
					function($m) {return 	"$m->m_id : {'color' : '$m->color_hex', 'name' : '$m->m_name', ".
											"'price' : ".sprintf("%0.2f", $m->price).", 'unit' : '$m->unit', ".
											"'measurable' : ".($m->is_measurable ? "true" : "false")."}";}, 
					$materials)); ?>
	};


	// for more information about class see ~/pages/end.php
	class Input {
		constructor(input) {
			this.mu_id = input.id.substr(0, input.id.indexOf('-'));
			this.element; 
			this.is_time_based;
			this.initialize_element_and_type(input);
			this.parent = this.parent_from_classes(input.classList);
			this.price = parseFloat(input.parentElement.children[0].innerHTML.match(/\d+(\.\d+)?/g));
			this.status = document.getElementById(this.mu_id+"-select");
		}


		// if input is time based assign appropriate elements for it
		initialize_element_and_type(input) {
			if(input.classList.contains("time")) 
				this.element = {	"hour" : document.getElementById(this.mu_id+"-input"), 
									"min" : document.getElementById(this.mu_id+"-minute")}, 
				this.is_time_based = true;
			else {
				this.element = input
				this.is_time_based = false;
			}
		}


		// return numeric quantity for input(s) associated
		quantity() {
			if(!this.is_time_based) return parseFloat(this.element.value);
			return parseFloat(this.element['hour'].value) + parseFloat(this.element['min'].value) / 60 || 0;

		}


		refactor_name(index) {
			if(!this.is_time_based) this.element.name = `optional-amount-${index}`;
			else {
				this.element["hour"].name = `optional-hour-${index}`;
				this.element["min"].name = `optional-minute-${index}`;
			}
		}


		// set quantity based on quantity passed and parse into hour/minute if necessary
		set_val(quantity) {
			if(!this.is_time_based) this.element.value = parseFloat(quantity);
			else {
				var hour = parseInt(quantity);
				var minute = (quantity - hour) * 60;
				this.element["hour"].value = hour;
				this.element["min"].value = round(minute, 2);
			}
		}


		// retrieve the parent name of a class (<parent_name>-child); return null if no parent name
		parent_from_classes(classList) {
			for(var x = 0; x < classList.length; x++)
				if(classList[x].includes("-child") && classList[x].length > 6) 
					return document.getElementById(classList[x].substr(0, classList[x].indexOf('-')));
			return null;
		}
	}


	// ------------------------------------------------------ FORM ------------------------------------------------------

	function resetForm() {
		document.getElementById("cform").reset();
		if(document.getElementById("selected_materials")) {
			var options = document.getElementById("material_select");
			for(var x = 0; x < options.options.length; x++) options.options[x].hidden = false;
			document.getElementById("selected_materials").innerHTML = "";
		}
		document.getElementById("quote").innerHTML = currency+" 0.00";
	}

	function validateForm() {
		if (!stdRegEx("operator", /^\d{10}$/, "Invalid ID #")) return false;
		// check purpose populated
		else if(!document.getElementById("purpose").value) {
			alert("Please select a purpose");
			return false;
		}
	}


	// calculate & display cost of selected materials
	function quote() {
		var materials = document.getElementsByClassName("mat_used_input");
		var total = 0;
		for(var x = 0; x < materials.length; x++) {
			var input = new Input(materials[x]);
			if(input.is_time_based && input.quantity() > time_limit) {
				alert("The current time amount is above the max allowed time for this device");
				input.set_val(time_limit);
			}
			else if(input.is_time_based && input.quantity() < minimum_charge_time) {
				alert("The current time amount is less than the minimum amount to be charged for");
				input.set_val(minimum_charge_time);
			}
			total += input.quantity() * input.price;
		}
		document.getElementById("quote").innerHTML = currency + total.toFixed(2);
	}



	// –————————————— OPTIONAL MATERIALS ——————————————

	// call tag creation, style fields of tag and add tag to selected_materials div
	function add_tag(select) {
		var selected = select.options[select.selectedIndex];

		var input = document.createElement("input");
		input.classList.add("material");
		input.readOnly = true;
		input.type = "hidden";
		input.value = select.value;

		var label = document.createElement("label");
		label.innerHTML = device_mat_attrs[select.value]['name'];

		var functionality = function() {
			selected.hidden = false;
			quote();
			refactor_selection_names();  // rename others to be sequential
		};
		var parent = document.getElementById("selected_materials");

		var tag = create_tag(functionality, input, label, parent);
		tag.style["border-color"] = `#${device_mat_attrs[select.value]['color']}`;  // add groovy color border of selected material
		parent.appendChild(tag);

		refactor_selection_names();  // rename others to be sequential
		select.value = select.options[0];
		selected.hidden = true;  // prevent double selection
	}


	// create tag (input, label and display) for selected materials
	function create_tag(functionality, input, label, parent) {
		var tag = document.createElement("div");
		var close = document.createElement("button");

		// appearance
		close.style = input.style = "background-color:#E8E8E8;border-style:none;height:100%;display:inline-block;float:right;";
		tag.style = 'background-color:#E8E8E8;border-style:solid;border-radius:8px;border-width:10px;display:inline-block;margin:5px;padding:8px;text-align:justify;';

		// close button event
		close.innerHTML = "<strong>&#215</strong>";
		close.onclick = function() {
			parent.removeChild(tag);  // remove self
			functionality();  // user specified proceeding function
		};

		// add parts to tag; add tag to parent
		tag.appendChild(input);
		tag.appendChild(label);
		tag.appendChild(close);

		parent.appendChild(tag);

		// add amounts if payFirst
		if(document.getElementById("pay_first_cell") && device_mat_attrs[input.value]['measurable']) {
			tag.appendChild(add_quantity_input(input));
			quote();
		}

		return tag;
	}


	// when prepaid (choose quantity), add inputs for quantity
	function add_quantity_input(mat_input) {
		var input_group = document.createElement("div");
		input_group.classList.add("input-group");

		var price_span = document.createElement("span");
		price_span.classList.add("input-group-addon");
		price_span.innerHTML = "<i class='<?php echo $sv['currency']; ?> fa-fw'></i> " + device_mat_attrs[mat_input.value]['price'];

		var input_attrs = {"onchange" : quote, "onkeyup" : quote, "min" : "0", "value" : 0, "type" : "number", "autocomplete" : "off"};
		var input = document.createElement("input");
		input.classList.add("form-control", "mat_used_input");
		input.id = `${mat_input.value}-input`;
		input.style.maxWidth = "100px";
		input.style.textAlign = "right";
		for(key in input_attrs)  // add ubiquitous attributes
			input[key] = input_attrs[key];

		var unit_span = document.createElement("span");
		unit_span.classList.add("input-group-addon");
		unit_span.innerHTML = device_mat_attrs[mat_input.value]['unit'];
		// add to div
		input_group.appendChild(price_span);
		input_group.appendChild(input);
		input_group.appendChild(unit_span);

		// adjust for time based data
		if(device_mat_attrs[mat_input.value]['unit'] == "hour(s)") {
			input.classList.add("time");
			input.max = time_limit;
			input.min = minimum_charge_time;
			input.value = minimum_charge_time;
			input.style.maxWidth = "75px";
			input.step = 1;  // only take full hour increments

			var minute_input = document.createElement("input");
			minute_input.id = `${mat_input.value}-minute`;
			minute_input.classList.add("form-control", "time");
			minute_input.max = 59;
			minute_input.style.textAlign = "right";
			for(key in input_attrs)  // add ubiquitous attributes
				minute_input[key] = input_attrs[key];

			unit_span.innerHTML = "Hour";
			var minute_span = document.createElement("span");
			minute_span.classList.add("input-group-addon");
			minute_span.innerHTML = "Minutes";


			input_group.appendChild(minute_input);
			input_group.appendChild(minute_span);
		}

		return input_group;
	}


	// used for name attribute that PHP pulls
	// when selection is deleted, rename all elements to be sequentially named
	function refactor_selection_names() {
		var tag_space = document.getElementById("selected_materials");
		for(var x = 0; x < tag_space.children.length; x++) {
			tag_space.children[x].children[0].name = 'optional-'+(x+1);
			
			// if tag has a quantity input, name quantity input(s)
			if(document.getElementById("pay_first_cell") && 
			device_mat_attrs[tag_space.children[x].children[0].value]["measurable"]) {
				var group_addon = tag_space.children[x].children[3];
				var input = new Input(group_addon.children[1]);
				input.refactor_name(x+1);
			}
		}
	}



	// ————————————————— UTILITY ——————————————————

	// because JS does not have a good rounding function, copied one from StackOverflow
	function round(float, decimal) {
		if(!float) return 0;
		return Number(Math.round(float+`e${decimal}`)+`e-${decimal}`).toFixed(decimal);
	}


		// prevent string from being longer than 10 chars for ID
	function restrict_size(element) {
		if(element.value.length > 10) element.value = element.value.substring(0, 10);
	}

</script>
