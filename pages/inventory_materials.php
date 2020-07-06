<?php

/***********************************************************************************************************
* 
*	@author MPZinke
*	created on 12.08.18
*	EDITED by: MPZinke on 2020.05.22 split into different feature pages. improved commenting.
*		fixed bugs
*	EDITED by: MPZinke on 04.03.19 added product number
*	EDITED by: MPZinke on 10.08.19 to make AJAX request for material updates
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V0.95
*	  -Maintenence
*	DESCRIPTION:	-Allow ability for lvl lead to edit inventory
*				 		-Allow ability for admin to add new material to inventory
*	FUTURE:	-allow material names to be same if only 1 is current.
*	BUGS: 
*
***********************************************************************************************************/


include_once ("$_SERVER[DOCUMENT_ROOT]/pages/header.php");

// staff clearance
if(!$staff || $staff->getRoleID() < $sv['minRoleTrainer'])
{
	// Not Authorized to see this Page
	header('Location: /index.php');
	$_SESSION['error_msg'] = "Insufficient role level to access, You must be a Lead.";
}


$failure_message = "";

// edit material update code
// get desired update material ID & check if valid. get all material values. if value changed, add it to list of 
// changed values. update DB & display success.
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["__EDITINV__submit_button"]))
{
	$selected_material = $_POST["__EDITINV__material_select"];
	if(!Materials::regexID($selected_material))
	{
		echo "Bad m_id: $selected_material";
		exit();
	}
	$material = new Materials($selected_material);  // current state; used for comparison

	// pull values
	$attributes = array(	"m_name", "product_number", "m_parent", "price", "unit",
							"is_measurable", "is_current", "color_hex")	;
	$posted_values = array();
	foreach($attributes as $attribute) $posted_values[$attribute] = $_POST["__EDITINV__${attribute}_input"];

	// check that there will be no m_parent recursion overflow
	if($posted_values["m_parent"] == $material->m_id) exit_from_error("A material cannot be its own parent");

	// get changes
	$changed_values = array();
	foreach($posted_values as $attribute => $value)
		if($material->$attribute != $value) $changed_values[$attribute] = $value;

	// update material for changes
	if($error = $material->edit_material_information($changed_values))
		$_SESSION["error_msg"] = "Failed to update material: $error";
	else $_SESSION["success_msg"] = "Successfully update material";
	header("Location:./inventory_materials.php");
}

// new materials
elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['__NEWINV__submit_button']))
{
	// validate inputs
	if(!$name = filter_input(INPUT_POST, "__NEWINV__name_input"))
		exit_from_error("Name too long for creating new material");
	if($prior_id = Materials::mat_exists($name)) exit_from_error("Material: $name already exists");

	$parent = filter_input(INPUT_POST, "__NEWINV__m_parent_select");
	if($parent && !Materials::regexID($parent)) exit_from_error("Parent material ID $parent is not valid");
	$product_number = filter_input(INPUT_POST, "__NEWINV__product_number_input");
	$price = filter_input(INPUT_POST, "__NEWINV__product_number_input");
	if(!$measurability = Materials::regexMeasurability(filter_input(INPUT_POST, "__NEWINV__measurable_select"))) 
		exit_from_error("Bad measurability data $measurability for creating new material");
	$unit = Materials::regexUnit(filter_input(INPUT_POST, "__NEWINV__unit_input"));
	$color = filter_input(INPUT_POST, "__NEWINV__color_input");
	$device_groups = __NEWINV__get_populate_values("__NEWINV__device_group_input");
	foreach($device_groups as $group)
		if(!DeviceGroup::regexDgID($group)) exit_from_error("Device group $group is not valid");

	// create material
	if(!$insert_id = Materials::create_new_material(substr($color, 1), $measurability, $name, $parent, $price, 
	$product_number, $unit))
		exit_from_error("Failed to create material $name");
	else
	{
		foreach($device_groups as $group)
		{
			if(!Materials::assign_device_group($group, $insert_id))
				exit_from_error("Unable to assign $insert_id to device group $group");
		}
	}
	exit_with_success("Successfully created Material $name");
}


else if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['__SHEETPAR__submit_button']) ){
	if(!preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['__SHEETPAR__name_input']))
		exit_from_error("Incorrect input: $_POST[sheet_name] on Sheet Good Material Name row.");
	elseif(!preg_match('/^([1-9][0-9]*|0)(\.[0-9]{2})?$/', $_POST['__SHEETPAR_cost_input']))
		exit_from_error("Incorrect input: $_POST[cost] on Sheet Cost row.");

	$sheet_name = filter_input(INPUT_POST, "__SHEETPAR__name_input");
	$sheet_cost = filter_input(INPUT_POST, "__SHEETPAR_cost_input");

	// add to DB
	if(!Materials::create_new_material(none, "Y", $sheet_name, $sv["sheet_goods_parent"], $sheet_cost, none, 
	"in<sup>2</sup>"))
		exit_from_error("Unable to create new sheetgood parent");

	exit_with_success("Successfully created Sheetgood group $sheet_name");
}


elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['__SHEETVAR__submit_button'])){
	$color = substr(filter_input(INPUT_POST, "__SHEETVAR__color_input"), 1);  // ignore '#'
	if(!preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['__SHEETVAR__name_input']))
		exit_from_error("Incorrect input: $_POST[__SHEETVAR__name_input] on Sheet Good Material Name row.");
	elseif(!preg_match('/^([1-9][0-9]*|0)(\.[0-9]{2})?$/', $_POST['__SHEETVAR__m_parent_select']))
		exit_from_error("You must properly fill the Sheet Parent row.");


	$sheet_parent1 = filter_input(INPUT_POST, "__SHEETVAR__m_parent_select");
	if($result1 = $mysqli->query("   
		SELECT `materials`.`price`
		FROM `materials`
		WHERE `materials`.`m_id` = '$sheet_parent1';"))
	{
		while($row = $result1->fetch_assoc())
			$sheet_cost1 = $row["price"];
	}
	$sheet_name1 = filter_input(INPUT_POST, "__SHEETVAR__name_input");

	// add to DB
	if(!Materials::create_new_material($color, "Y", $sheet_name1, $sheet_parent1, $sheet_cost1, none, 
	"in<sup>2</sup>"))
		exit_from_error("Unable to create new sheetgood variant");

	exit_with_success("Successfully created Sheetgood variant with color $color");
}


elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['__SHEETSIZE__submit_button']))
{
	if(!preg_match('/^[0-9]+$/i', $_POST['__SHEETSIZE__parent_select']))
		exit_from_error("You must properly fill the Sheet Material field.");
	elseif(!preg_match('/^[0-9]+$/i', $_POST['__SHEETSIZE__variant_select']))
		exit_from_error("You must properly fill the Sheet Material field.");
	elseif(!preg_match('#^\d+(?:\.\d{1,2})?$#', $_POST['__SHEETSIZE__width_input']))
		exit_from_error("Incorrect input: $_POST[__SHEETSIZE__width_input] on Width field.");
	elseif(!preg_match('#^\d+(?:\.\d{1,2})?$#', $_POST['__SHEETSIZE__height_input']))
		exit_from_error("Incorrect input: $_POST[__SHEETSIZE__height_input] on Height field.");
	elseif(!preg_match('/^[0-9]+$/i', $_POST['__SHEETSIZE__quantity_input']))
		exit_from_error("Incorrect input: $_POST[__SHEETSIZE__quantity_input] on Sheet Quantity field.");


	$m_id = filter_input(INPUT_POST, "__SHEETSIZE__variant_select");
	$sheet_parent = filter_input(INPUT_POST, "__SHEETSIZE__parent_select");
	$sheet_width = filter_input(INPUT_POST, "__SHEETSIZE__width_input");
	$sheet_height = filter_input(INPUT_POST,"__SHEETSIZE__height_input");
	$sheet_quantity = filter_input(INPUT_POST, "__SHEETSIZE__quantity_input");

	// add to DB
	$error_message = Materials::create_new_sheet_inventory($m_id, $sheet_parent, $sheet_width, $sheet_height, 
			$sheet_quantity);
	if($error_message) exit_from_error($error_message);
	exit_with_success("Successfully created Sheetgood variant size $sheet_width x $sheet_height");
}


// adds error message to session variable, redirects & stops current processes
// takes an error string to add to session variable
// adds error message. redirects page. ends future processes.
function exit_from_error($error_message)
{
	if(!$error_message) return;

	$_SESSION["error_msg"] = $error_message;
	header("Location:./inventory_materials.php");
	exit();
}


// adds success message to session variable, redirects & stops current processes
// takes an success string to add to session variable
// adds success message. redirects page. ends future processes.
function exit_with_success($success_message)
{
	$_SESSION["success_msg"] = $success_message;
	header("Location:./inventory_materials.php");
	exit();
}


// pulls iterative, consecutive posted values from page for base tag
// takes base tag to concatenate with iterative suffix
// iterates selecting posted values while values are not null, adding value to list.
// return list of pulled values
function __NEWINV__get_populate_values($name)
{
	$device_groups = array();
	for($x = 0; $_POST["$name-$x"]; $x++) $device_groups[] = $_POST["$name-$x"];
	return $device_groups;
}

?>

<title><?php echo $sv['site_name'];?> Edit Inventory</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-md-12">
			<h1 class="page-header">Edit Inventory</h1>
		</div>
	</div>

	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading" style="background-color: #B5E6E6;">
				<i class="fas fa-warehouse"></i> Edit Inventory
			</div>
			<div class="panel-body">
				<div class="table">
					<ul class="nav nav-tabs">
						<li class="active">
							<a data-toggle="tab" aria-expanded="false" href="#1">Edit Material</a>
						</li>
						<li>
							<a data-toggle="tab" aria-expanded="false" href="#2">New Material</a>
						</li>
						<li class="dropdown">
							<a class="dropdown-toggle" data-toggle="dropdown">Sheet Goods
							<span class="caret"></span></a>
							<ul class="dropdown-menu">
								<li><a data-toggle="tab" aria-expanded="false" href="#3">Add Sheet Parent</a></li>
								<li><a data-toggle="tab" aria-expanded="false" href="#4">Add Sheet Child</a></li>
								<li><a data-toggle="tab" aria-expanded="false" href="#5">Add Sheet Size</a></li>
							</ul>
						</li>
					</ul>

<!--———————————————————— EDIT MATERIAL ————————————————————-->
					<div class="tab-content">
						<div id="1" class="tab-pane fade active in">
							<form method='POST'>
								<div class="panel panel-default">
									<div class='panel-body'>
										<div class='input-group'>
											<span class='input-group-addon'>Material</span>
											<select id='__EDITINV__material_select' name='__EDITINV__material_select'
											class='form-control' onchange='__EDITINV__data_for_material(this);'>
												<?php
													$__EDITINV__all_materials = Materials::get_all_materials();
													if(!count($__EDITINV__all_materials)) echo "<option>NONE</option>";
													else
													{
														foreach($__EDITINV__all_materials as $mat)
															echo "<option value='$mat->m_id'>$mat->m_name</option>";
													}
												?>
											</select>
										</div>
										<table id='__EDITINV__data_table' class='table' hidden>
											<?php
												$__EDITINV__text_attributes = array(
														"Name" => "m_name", "Product Number" => "product_number",
														"Parent" => "m_parent", "Price" => "price", "Unit" => "unit");

												foreach($__EDITINV__text_attributes as $script => $var)
												{
													?>
													<tr>
														<td class='col-md-3'><?php echo $script; ?></td>
														<td class='col-md-9'>
															<input id='<?php echo "__EDITINV__${var}_input"; ?>' 
															name='<?php echo "__EDITINV__${var}_input"; ?>'
															class='form-control'>
														</td>
													</tr>
													<?php
												}

											?>
											<tr>
												<td>Color</td>
												<td>
													<input id='__EDITINV__color_hex_input' name='__EDITINV__color_hex_input'
													class='form-control' type='color' style='padding:0px;'>
												</td>
											</tr>
											<?php 
												foreach(array("Measurable" => "is_measurable", "Current" => "is_current") as $script => $var)
												{
													?>
													<tr>
														<td class='col-md-3'><?php echo $script; ?></td>
														<td class='col-md-9'>
															<input id='<?php echo "__EDITINV__${var}_input"; ?>' 
															name='<?php echo "__EDITINV__${var}_input"; ?>' type='checkbox'
															class='form-control'>
														</td>
													</tr>
													<?php
												}
											?>
											<!-- TODO: allow editing device groups -->
											</table>
										</div>  <!-- <div class="panel-body"> -->
									<div class="panel-footer">
										<div align='right' style='padding-top:16px;'>
											<button type='submit' class='btn btn-success' name='__EDITINV__submit_button'>Update</button>
										</div>
									</div>  <!-- <div class="panel-footer"> -->
								</form>
							</div>  <!-- <div class="panel panel-default"> -->
						</div>  <!-- <div id="5" class="tab-pane fade"> -->

<!--——————————————————— NEW MATERIAL ————————————————————-->
						<div id="2" class="tab-pane fade">
							<div class="panel panel-default">
								<div class='panel-body'>
									<table class='table table-bordered table-striped table-hover' id='new_item_table'>
										<tr>
											<td class='col-md-4'>Item Name</td>   
											<td class='col-md-8'>
												<input id='__NEWINV__name_input' class='form-control' type='text' placeholder='New Material' 
												maxlength='50' onchange='__NEWINV__validate_inputs();' onkeyup='__NEWINV__validate_inputs();'> 
											</td>
										</tr>
										<tr>
											<td>Parent Material</td>
											<td>
												<select id='__NEWINV__m_parent_select' class='form-control'>
													<option value=''>NONE</option>
													<?php
														$__NEWINV__all_materials = Materials::get_all_materials();
														foreach($__NEWINV__all_materials as $mat)
															echo "<option value='$mat->m_id'>$mat->m_name</option>";
													?>
												</select>
											</td>
										</tr>
										<tr>
											<td class='col-md-4'>Product Number</td>
											<td class='col-md-8'>
												<input id='__NEWINV__product_number_input' class='form-control' type='text' 
												placeholder='3D ABS-1KG1.75-BLK' maxlength='30' /> 
											</td>
										</tr>
										<tr>
											<td>Measurable</td>
											<td>
												<select id='__NEWINV__measurable_select' class='form-control'>
													<option value='Y'>Y</option>
													<option value='N'>N</option>
												</select>
											</td>
										</tr>
										<tr>
											<td>Price</td>
											<td>
												<div class="input-group">
													<span class="input-group-addon unit">$</span>
													<input id='__NEWINV__price_input' type="number" min="0" step='0.01' class="form-control"
													placeholder="0.10" onchange='__NEWINV__validate_inputs();' onkeyup='__NEWINV__validate_inputs();'>
												</div>
											</td>
										</tr>
										<tr>
											<td>Unit</td>
											<td>
												<input id='__NEWINV__unit_input' list='units' class='form-control'/>
												<datalist id='units'>
													<?php 
														if($autofills = $mysqli->query(
																"SELECT DISTINCT `unit` 
																FROM `materials` 
																WHERE `unit` != ''")
														)
														{
															while($row = $autofills->fetch_assoc())
																echo "<option value='$row[unit]'>$row[unit]</option>";
														} 
													?>
												</datalist>
											</td>
										</tr>
										<tr>
											<td>Color Hex</td>
											<td>
												<table style="margin:none;height:100%;width:100%;padding:0px;">
													<tr style="width:100%;">
														<td style="width:50%;" id='__NEWINV__RGB_td' hidden>
															<div class="input-group">
																<span class="input-group-addon unit">RGB</span>
																<input id="rgb_input" type="text" class='form-control' value="80,0,0"
																  onchange="__NEWINV__validate_rgb_color(this); __NEWINV__set_full_color(this)"
																  onkeyup="__NEWINV__validate_rgb_color(this); __NEWINV__set_full_color(this)">
															</div>
														</td>
														<td style="width:30%;" id="__NEWINV__color_td" hidden>
															<input type="color" id="__NEWINV__color_input" class='form-control' style='padding:0px;'
															  onchange="__NEWINV__click_color(0, 5)" value="#500000" style="width:85%;">
														</td>
														<td style="width:20%;">
															<button id='__NEWINV__color_button' class='btn' type='button' style='width:100%;' 
															onclick="__NEWINV__switch_color_active()">
																Include Color
															</button>
														</td>
													</tr>
												</table>
											</td>
										</tr>
										<tr id='__NEWINV__device_group_row'>
											<td>Device Group</td>
											<td>
												<table id='__NEWINV__device_group_table' class='table'>
												</table>
												<button class="btn btn-info pull-right" onclick="__NEWINV__additional_device_group()">
													Additional Device Groups
												</button>
											</td>
										</tr>
									</table>
								</div>  <!-- <div class="panel-body"> -->
								<div class="panel-footer">
									<div class="clearfix">
										<button id='__NEWINV__modal_button' class="btn pull-right btn-success" 
										onclick="__NEWINV__compile_and_populate_modal();" disabled>
											Create New Item
										</button>
									</div>
								</div>  <!-- <div class="panel-footer"> -->
							</div>  <!-- <div class="panel panel-default"> -->
						</div>  <!-- <div id="5" class="tab-pane fade"> -->


<!--———————————————— CREATE SHEETGOOD PARENT ————————————————-->
						<div id="3" class="tab-pane fade">
							<div class="panel panel-default">
								<div class="panel-body">
									<table class="table table-bordered table-striped table-hover">
										<form method="POST" action="" autocomplete='off'>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top" title="email contact information">
														Sheet Good Material Name
													</b>
												</td>
												<td>
													<input type="text" class="form-control"name="__SHEETPAR__name_input" id="__SHEETPAR__name_input" 
													maxlength="50" size="50" placeholder="Enter Name" />
												</td>
											</tr>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top">Sheet Cost (per in<sup>2</sup>) </b>
												</td>
												<td>
													<div class="input-group">
														<span class="input-group-addon unit">$</span>
														<input type="number" name="__SHEETPAR_cost_input" id="__SHEETPAR_cost_input" min="0" step='0.01' 
														class="form-control" max="99.99" min="0.00" value="0.00" step="0.01" tabindex="1"/>
													</div>
												</td>
											</tr>
											<tfoot>
												<tr>
													<td colspan="2">
														<div class="pull-right">
															<button type="submit" name="__SHEETPAR__submit_button" class="btn btn-success" 
															onclick="return __SHEETGOOD__submit_confirmation()">Create Sheet Parent</button>
														</div>
													</td>
												</tr>
											</tfoot>
										</form>
									</table>
								</div>  <!-- <div class="panel-body"> -->
							</div>  <!-- <div class="panel panel-default"> -->
						</div>  <!-- <div id="3" class="tab-pane fade"> -->

<!--———————————————— CREATE SHEETGOOD CHILD —————————————————-->
						<div id="4" class="tab-pane fade">
							  <div class="panel panel-default">
								<div class="panel-body">
									<table class="table table-bordered table-striped table-hover">
										<form method="POST" action="" autocomplete='off'>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top" title="Select Parent">Sheet Parent</b>
												</td>
												<td>
													<select class="form-control" name="__SHEETVAR__m_parent_select" id="__SHEETVAR__m_parent_select">
														<?php
															$result = $mysqli->query(
																	"SELECT DISTINCT `materials`.`m_id`, `materials`.`m_name`
																	FROM `materials`
																	WHERE `materials`.`m_parent` = '$sv[sheet_goods_parent]';");
															if(!$result)
																echo "<option> disabled selected hidden>QUERY ERROR</option>";
															else
															{
																echo "<option disabled selected value=''>Select Sheet</option>";
																while($row = $result->fetch_assoc())
																	echo "<option value=\"$row[m_id]\">$row[m_name]</option>";
															}
														?>
													</select>
												</td>
											</tr>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top" title="Enter the name of the sheet material">
														Sheet Good Name
													</b>
												</td>
												<td>
													<input type="text" class="form-control"name="__SHEETVAR__name_input" id="__SHEETVAR__name_input" 
													maxlength="50" size="50" placeholder="Enter Name" />
												</td>
											</tr>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top" title="Choose or input the color of the material">
														Color Hex
													</b>
												</td>
												<td>
													<div style="text-align:center;">
														<input type="color" name="__SHEETVAR__color_input" id="__SHEETVAR__color_input" 
														value="#000000" style="width: 500px;height: 30px;">
													</div>
												</td>
											</tr>
											<tfoot>
												<tr>
													<td colspan="2">
														<div class="pull-right">
															<button type="submit" name="__SHEETVAR__submit_button" class="btn btn-success" 
															onclick="return __SHEETGOOD__submit_confirmation()">Create Sheet Child</button>
														</div>
													</td>
												</tr>
											</tfoot>
										</form>
									</table>
								</div>  <!-- <div class="panel-body"> -->
							</div>  <!-- <div class="panel panel-default"> -->
						</div>  <!-- <div id="4" class="tab-pane fade"> -->

<!--————————————————— CREATE SHEETGOOD SIZE —————————————————-->
						<div id="5" class="tab-pane fade">
							<div class="panel panel-default">
								<div class="panel-body">
									<table class="table table-bordered table-striped table-hover">
										<form method="POST" action="" autocomplete='off'>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top" title="Select Variant">Sheet Material</b>
												</td>
												<td>
													<div class="col-md-6">
														<select class="form-control" id="__SHEETSIZE__parent_select" tabindex="1"
														name='__SHEETSIZE__parent_select' onchange="__SHEETGOOD__get_variants()">
															<?php
																$result = $mysqli->query("	  
																	SELECT DISTINCT `materials`.`m_id`, `materials`.`m_name`
																	FROM `materials`
																	WHERE `materials`.`m_parent` = '$sv[sheet_goods_parent]';");
																if(!$result)
																	echo "<option> disabled selected hidden>QUERY ERROR</option>";
																else
																{
																	echo "<option disabled hidden selected value=''>Sheet Parent</option>";
																	while($row = $result->fetch_assoc())
																		echo "<option value='$row[m_id]''>$row[m_name]</option>";
																}
															?>
														</select>
														</div>

														<div class="col-md-6">
														<select class="form-control" name="__SHEETSIZE__variant_select"
														id="__SHEETSIZE__variant_select" tabindex="1">
															<option value =""> Select Parent First</option>
														</select>   
													</div>
												</td>
											</tr>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top">Sheet Size (Inches) </b>
												</td>
												<td>
													<div class="col-md-12">
														<div class="col-md-1">
															<i>Width </i>
														</div>
														<div class="col-md-11">
															<div class="input-group">
																<input type="number" class="form-control" name="__SHEETSIZE__width_input"
																max="500" min="1" value="0" step="0.1" placeholder="Enter Width" />
																<span class="input-group-addon unit">inch(es)</span>
															</div>
														</div>
													</div>
													<div class="col-md-12">
														<div class="col-md-1">
															<i>Height </i>
														</div>
														<div class="col-md-11">
															<div class="input-group">
																<input type="number" class="form-control" name="__SHEETSIZE__height_input"
																max="500" min="1" value="0" step="0.1" placeholder="Enter Height" />
																<span class="input-group-addon unit">inch(es)</span>
															</div>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top">Quantity </b>
												</td>
												<td>
													<input type="number" class="form-control" name="__SHEETSIZE__quantity_input" max="250" 
													min="1" value="1" step="1" placeholder="Enter Quantity" />
												</td>
											</tr>

											<tfoot>
												<tr>
													<td colspan="2">
														<div class="pull-right">
															<button type="submit" name="__SHEETSIZE__submit_button" class="btn btn-success" 
															onclick="return __SHEETGOOD__submit_confirmation()">Create Sheet Inventory</button>
														</div>
													</td>
												</tr>
											</tfoot>
										</form>
									</table>
								</div>  <!-- <div class="panel-body"> -->
							</div>  <!-- <div class="panel panel-default"> -->
						</div>  <!-- <div id="5" class="tab-pane fade"> -->
					</div>  <!-- <div class="tab-content"> -->
				</div>  <!-- <div class="table"> -->
			</div>  <!-- <div class="panel-body"> -->
		</div>  <!-- <div class="panel panel-default"> -->
	</div>  <!-- <div class="col-md-12"> -->
</div>  <!-- <div id="page-wrapper"> -->


<!-- holds data from new material section and allows for final confirmation. data is added by JS.  -->
<div id="__NEWINV__modal" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method='POST'>
				<div class="modal-header" id='modal-header'>
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">Update Inventory</h4>
				</div>
				<div id='__NEWINV__modal_body' class='modal-body'>
					<!-- populated with material attributes -->
				</div>
				<div id='__NEWINV__modal_footer' class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button name='__NEWINV__submit_button' type='submit' class='btn btn-success'>
						Create
					</button>
				</div>
			</form>
		</div>  <!-- <div class="modal-content"> -->
	</div>  <!-- <div class="modal-dialog"> -->
</div>  <!-- <div id="__NEWINV__modal" class="modal fade"> -->


<?php
	//Standard call for dependencies
	include_once("$_SERVER[DOCUMENT_ROOT]/pages/footer.php");
?>

<script>

	// ——————————————————— EDIT INVENTORY ———————————————–————
	// ———————————————————————————————————————————————

	// AJAX call to populate material data to edit.
	// takes reference to calling element (__EDITINV__material_select) for selected material.
	// calls page, posting the m_id of desired material. populate fields with echoed JSON data. show table.
	function __EDITINV__data_for_material(element)
	{
		$.ajax({
			url: "./sub/material_ajax_requests.php",
			type: "POST",
			dataType: "json",
			data: {"material_info" : element.value},
			success: function(response)
			{
				var attributes = ["m_name", "product_number", "m_parent", "price", "unit", "color_hex"];
				console.log(response);
				for(x in attributes)
					document.getElementById(`__EDITINV__${attributes[x]}_input`).value = response[attributes[x]];
				document.getElementById("__EDITINV__is_measurable_input").checked = response["is_measurable"];
				document.getElementById("__EDITINV__is_current_input").checked = response["is_current"];

				document.getElementById("__EDITINV__data_table").hidden = false;
			},
			error: function(XMLHttpRequest, textStatus, errorThrown)
			{
				alert("Error in query submission");
				console.log(errorThrown);
			}
		});
	}


	// ——————————————————— NEW INVENTORY ———————————————–————
	// ———————————————————————————————————————————————

	// ———————————————————— PAGE MANIP —————————————————————
	
	// add more device group rows.
	// on function call, add HTML tr to table with select with values for device groups.
	function __NEWINV__additional_device_group()
	{
		var row = document.getElementById("__NEWINV__device_group_table").insertRow(-1);
		var innerdata =	`<td>
								<div class='input-group'>
									<select tabindex="2" class='form-control __NEWINV__device_group_select'>
										<option selected disabled hidden value=''>SELECT</option>
										<?php
											if($devices = $mysqli->query(
												"SELECT `dg_id`, `dg_name`, `dg_desc`
												FROM `device_group`
												ORDER BY `dg_desc`"
											))
											{
												while($row = $devices->fetch_assoc())
													echo("<option value='$row[dg_id]'>$row[dg_desc]</option>\n");
											}
											else echo ("SQL ERROR");
										?>
									</select>
									<span class='input-group-addon' style='padding:0px;'>
										<button type='button' onclick='__NEWINV__delete_device_group(this);'
										style='border:#555 solid 0px;'>
											&times
										</button>
									</span>
								</div>
							</td>`;
		// put HTML of first device group div into new one with updated id
		row.innerHTML = innerdata;
	}


	// remove the selected device group from page on x button click
	// takes reference to x button that was clicked.
	// finds the ancestor row for the referenced element. removes row and all sub elements.
	function __NEWINV__delete_device_group(element)
	{
		var row = element.closest("tr");
		if(row) row.remove();
		else alert("JS: Problem finding row for button");
	}


	// check if name already exists (case insensitive).
	// takes a string of the name to check.
	// checks against the listed materials in update inventory select div
	// returns bool of if any match.
	function __NEWINV__name_already_used(material_name)
	{
		var created_materials = document.getElementById("__EDITINV__material_select");
		var name = material_name.toLowerCase();
		for(var x = 0; x < created_materials.options.length; x++)
			if(created_materials.options[x].text.toLowerCase() == name) return true;
		return false;
	}


	// check that inputs are properly filled and en/disables modal button based on validity.
	// pulls values & checks that they are populated. checks whether material name is a duplicate. if it is, disables 
	// button and highlights input red(ish). if all data valid, enables button.
	function __NEWINV__validate_inputs()
	{
		var name_input = document.getElementById("__NEWINV__name_input");
		var price_input = document.getElementById("__NEWINV__price_input");

		// check that inputs are populated
		if(!name_input.value || !String(price_input.value).length)
			document.getElementById("__NEWINV__modal_button").disabled = true;
		else document.getElementById("__NEWINV__modal_button").disabled = false;

		// check that name is not repeat
		if(!__NEWINV__name_already_used(name_input.value)) name_input.style["background-color"] = "#EEE";
		else 
		{
			document.getElementById("__NEWINV__modal_button").disabled = true;
			name_input.style["background-color"] = "#CC5555";
		}
	}


	// ————————————————————— MODAL ————————————————–——————

	// populate modal with info for new item
	// calls functions to pull & organize data into HTML tables. puts tables into modal and displays modal.
	function __NEWINV__compile_and_populate_modal()
	{
		var new_material_value_table = __NEWINV__HTML_value_table();
		var new_material_device_groups = __NEWINV__HTML_device_group_table();
		if(!new_material_device_groups) var data_table = new_material_value_table;
		else var data_table = new_material_value_table + '\n' + new_material_device_groups;

		document.getElementById("__NEWINV__modal_body").innerHTML = data_table;

		$('#__NEWINV__modal').modal('show');
	}


	// creates an HTML table for the selected device groups for the new material.
	// pulls all elements with classname for device group. adds all that have a value to list. all values in list are dressed 
	// in HTML and added to table string.
	function __NEWINV__HTML_device_group_table()
	{
		// collect device groups
		var device_group_elements = document.getElementsByClassName("__NEWINV__device_group_select");
		var populated_device_groups = [];
		for(var x = 0; x < device_group_elements.length; x++) 
			if(device_group_elements[x].value) populated_device_groups.push(device_group_elements[x]);
		if(!populated_device_groups.length) return null;  // none are populated; ignore rest of process

		// create table
		var table_HTML =	`<table class='table'>
								<tr>
									<th><b>Device Groups</b></th>
								</tr>`;
		for(var x = 0; x < populated_device_groups.length; x++)
		{
			var current_device_group = populated_device_groups[x];
			var device_group_name = current_device_group.options[current_device_group.selectedIndex].text;
			table_HTML +=	`<tr>
									<td>
										${device_group_name}
										<input name='__NEWINV__device_group_input-${x}' hidden
										value='${current_device_group.value}'>
									</td>
								</tr>`;
		}

		return table_HTML + `</table>`;
	}


	// creates an HTML table for the attributes for the new material.
	// pulls elements. adds all to list. all values in list are dressed in HTML and added to table string.
	function __NEWINV__HTML_value_table()
	{
		var titles = ["Item Name", "Parent Material", "Product Number", "Measurable", "Price", "Unit", "Color Hex"];
		var ids =	["name_input", "m_parent_select", "product_number_input", "measurable_select", "price_input", "unit_input"];
		if(__NEWINV__INCLUDE_COLOR) ids.push("color_input");  // otherwise color is not selected

		// get values from page
		var values = [];
		for(var x = 0; x < ids.length; x++) values.push(document.getElementById(`__NEWINV__${ids[x]}`).value);

		// validate first 3
		// require fields populated
		var required_fields = [0, 3, 4];
		for(var x = 0; x < required_fields.length; x++)
			if(values[required_fields[x]] == "") return alert(`${titles[required_fields[x]]} requires a value`);

		// create table
		var table_HTML = `<table class='table'>\n`;
		for(var x = 0; x < values.length; x++)
		{
			table_HTML +=	`<tr>
									<td>${titles[x]}</td>
									<td>
										${values[x]}
										<input name='__NEWINV__${ids[x]}' value='${values[x]}' hidden>
									</td>\n
								</tr>\n`;
		}
		
		return table_HTML + `</table>\n`;
	}


	// ———————————————————— SHEETGOODS ————————————————————
	// ———————————————————————————————————————————————

	// get user confirmation of sheetgood submission
	// call confirm().
	// if user confirmeed return true (to submit form). else return false
	function __SHEETGOOD__submit_confirmation()
	{
		if(confirm("You are about to submit this query. Click OK to continue or CANCEL to quit.")) return true;
		return false;
	}


	// ajax request to si_getVariants.php to get variants of sheet good parent group.
	// makes a GET request to page passing selected parent. populates variants select.
	function __SHEETGOOD__get_variants()
	{
		// code for IE7+, Firefox, Chrome, Opera, Safari
		if(window.XMLHttpRequest) xmlhttp = new XMLHttpRequest();
		else xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");  // code for IE6, IE5

		xmlhttp.onreadystatechange = function()
		{
			if(this.readyState == 4 && this.status == 200)
				document.getElementById("__SHEETSIZE__variant_select").innerHTML = this.responseText;
			else if(this.status >= 400) alert("Unable to get sheetgood variants");
		};
		
		var sheet_id = document.getElementById("__SHEETSIZE__parent_select").value;
		xmlhttp.open("GET", `/pages/sub/si_getVariants.php?val=${sheet_id}`, true);
		xmlhttp.send();
	} 


	// ——————————————————— COLOR PICKING ———————————————–————
	// ———————————————————————————————————————————————

	var __NEWINV__INCLUDE_COLOR = false;  // bool to determine if to include color

	function __NEWINV__validate_rgb_color(element)
	{
		var value = element.value;  // sugar
		// no "rgb(", ")", values > 255
		var rgb = value.split(',');
		if(rgb.length != 3) element.value = "80,0,0";

		for(var x = 0; x < rgb.length; x++)
		{
			if(rgb[x] != "") rgb[x] = parseInt(rgb[x]).toString();

			if(isNaN(parseInt(rgb[x])) && rgb[x] != "") rgb[x] = "0";  // not an integer or blank
			else if(parseInt(rgb[x]) > 255) rgb[x] = rgb[x].substring(0, rgb[x].length -1);  // larger than 255

			// remove leading 0's
			if(parseInt(rgb[x]) > 0 && rgb[x].substring(0, 1) == "0") rgb[x] = rgb[x].substring(1);
		}
		element.value = rgb.join(",");
	}


	// button action to hide/show activate/deactivate color input ability.
	// flips visability of ability to see color choosing option.
	function __NEWINV__switch_color_active()
	{
		__NEWINV__INCLUDE_COLOR = !__NEWINV__INCLUDE_COLOR;
		if(__NEWINV__INCLUDE_COLOR)
		{
			document.getElementById("__NEWINV__color_button").innerHTML = "Exclude Color";
			$("#__NEWINV__RGB_td").show();
			$("#__NEWINV__color_td").show();
		}
		else
		{
			document.getElementById("__NEWINV__color_button").innerHTML = "Include Color";
			$("#__NEWINV__RGB_td").hide();
			$("#__NEWINV__color_td").hide();
		}
	}


	// get value in RGB input, change to HEX, set HTML5 input color to HEX equiv.
	// converts rgb input to hex valued color. sets color picker input to hex values
	function __NEWINV__set_full_color()
	{
		var rgb = document.getElementById("rgb_input");
		var color = rgb.value.split(',');
		var hex = "#";
		for(var x = 0; x < color.length; x++)
		{
			if(color[x] == "") color[x] = 0;
			var temp = parseInt(color[x]).toString(16);
			hex += temp.length == 1 ? "0" + temp : temp;
		}
		document.getElementById("__NEWINV__color_input").value = hex;
	}


	// change RGB input to equivalent of HEX value from HTML5 color picker.
	// takes initial hex value html version. on bad html version, alerts user of incapability to user picker.
	function __NEWINV__click_color(hex, html5)
	{
		var color;
		if(html5 && html5 == 5) 
		{
			color = document.getElementById("__NEWINV__color_input").value;
		}
		else
		{
			alert("Color wheel is only supported by HTML5\nPlease use RGB box");
			return;
		}

		r = parseInt(color.substr(1,2), 16);
		g = parseInt(color.substr(3,2), 16);
		b = parseInt(color.substr(5), 16);
		document.getElementById("rgb_input").value = "rgb(" + r + ',' + g + ',' + b + ')';
	}



	function any(list, comparitor, value)
	{
		for(var x = 0; x < list.length; x++)
		{
			if(comparitor(list[x], value)) return true;
		}
		return false;
	}


</script>