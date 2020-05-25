<?php

/***********************************************************************************************************
* 
*   @author MPZinke
*   created on 12.08.18
*   EDITED by: MPZinke on 04.03.19 added product number
*   EDITED by: MPZinke on 10.08.19 to make AJAX request for material updates
*   CC BY-NC-AS UTA FabLab 2016-2019
*   FabApp V 0.94
*	  -Multiple Materials
*	  -Off-line Mode
*	  -Sheet Goods
*	  -Storage Box
*	  -House Keeping (DB cleanup, $status variable, class syntax)
*
*   DESCRIPTION:	-Allow ability for lvl lead to edit inventory
*				  -Allow ability for admin to add new material to inventory
*   FUTURE: 
*   BUGS: 
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
$device_mats = Materials::get_all_materials();

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
	$material = new Materials($selected_material);

	// pull values
	$attributes = array(	"m_name", "product_number", "m_parent", "price", "unit",
							"is_measurable", "is_current", "color_hex")	;
	$posted_values = array();
	foreach($attributes as $attribute) $posted_values[$attribute] = $_POST["__EDITINV__${attribute}_input"];

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
elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_mat']))
{
	// check inputs
	if(!$name = Materials::regexName(filter_input(INPUT_POST, "item_name"))) $failure = "Name too long for creating new material";

	$product_number = Materials::regexProductNum(filter_input(INPUT_POST, "product_number"));

	$price = filter_input(INPUT_POST, "item_price");
	if($price && !Materials::regexPrice($price)) $failure = "Bad price for creating new material";
	elseif(!$price) $price = NULL;
	
	if(!$measurability = Materials::regexMeasurability(filter_input(INPUT_POST, "item_measurability"))) 
		$failure = "Bad measurability data for creating new material";
	
	$unit = filter_input(INPUT_POST, "item_unit");
	if($unit && !$unit = Materials::regexUnit($unit)) $failure = "Unit too long for creating new material";
	elseif(!$unit) $unit = "";
	
	$color = substr(filter_input(INPUT_POST, "item_color"), 1);  // ignore '#'
	if($color && !$color = Materials::regexColor($color)) $failure = " Bad color for creating new material";
	elseif(!$color) $color = NULL;
	
	$device_group = get_populated_values("item_device_group");
	if($device_group && !Materials::regexDeviceGroup($device_group)) $failure = "One or more device group ID is/are incorrect";

	if($failure)
	{
		$_SESSION['error_msg'] = $failure;
		header("Location:inventory_processing.php");  // assumed to be malicious; zero everything
	}

	// commence query
	$prior_id = Materials::mat_exists($name);  // material already exists
	// if((material exists and successfully update) || (material !exists and successfully created))
	if(($prior_id && Materials::update_mat($color, $prior_id, $measurability, $price, $product_number, $unit)) 
		|| (!$prior_id && Materials::create_new_mat($color, $measurability, $name, $price, $product_number, $unit)))
	{
			$m_id = ($prior_id ? $prior_id : Materials::mat_exists($name));  // id to assign device groups
		$outcome = "S".str_replace(' ', '_', $name).successful_and_failed_device_group_additions($m_id, $device_group);  // S for success, F for failed
	}
	else {  // update or create material failed
		$outcome = "F".str_replace(' ', '_', $name);
	}
	header("Location:inventory_processing.php?outcome=".$outcome);
}


else if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['variantBtn']) ){
	if(!preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['sheet_name']))
		$error_message = "Incorrect input: $_POST[sheet_name] on Sheet Good Material Name row."; 
	elseif(!preg_match('/^([1-9][0-9]*|0)(\.[0-9]{2})?$/', $_POST['sheet_cost']))
		$error_message = "Incorrect input: $_POST[cost] on Sheet Cost row."; 
	else
	{
		$sheet_name = filter_input(INPUT_POST, "sheet_name");
		$sheet_cost = filter_input(INPUT_POST, "sheet_cost");

		$error_message = Materials::create_new_material($sheet_name, $sv["sheet_goods_parent"], $sheet_cost, "NULL", "sq_inch(es)", "", "Y");
	}
}


elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['variantBtn1'])){
	$color = substr(filter_input(INPUT_POST, "sheet_color"), 1);  // ignore '#'
	if(!preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['sheet_name1']))
		$error_message = "Incorrect input: ". $_POST['sheet_name1'] ." on Sheet Good Material Name row."; 
	elseif($color && !$color = Materials::regexColor($color))
		$error_message = "Bad color for creating Sheet Variant."; 
	elseif(!preg_match('/^([1-9][0-9]*|0)(\.[0-9]{2})?$/', $_POST['m_id1']))
		$error_message = "You must properly fill the Sheet Parent row."; 
	elseif(!preg_match('/^[a-f0-9]{6}$/', $_POST['sheet_color_hex']) && $_POST['sheet_color_hex'] != "")
		$error_message = "Incorrect input: ". $_POST['sheet_color_hex'] ." on Color HEX row."; 
	else
	{
		$sheet_parent1 = filter_input(INPUT_POST, "m_id1");
		
		if($result1 = $mysqli->query("   
			SELECT `materials`.`price`
			FROM `materials`
			WHERE `materials`.`m_id` = '$sheet_parent1';"))
		{
			while($row = $result1->fetch_assoc())
				$sheet_cost1 = $row["price"];
		}
		$sheet_name1 = filter_input(INPUT_POST, "sheet_name1");

		$error_message = Materials::create_new_material($sheet_name1, $sheet_parent1, $sheet_cost1, "NULL", "sq_inch(es)", $color, "Y");
	}
}

elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['inventoryBtn']))
{
	if(!isset($_POST['m_id']))
		$error_message = "You must properly fill the Sheet Material field.";
	elseif(!preg_match('/^[0-9]+$/i', $_POST['variants']))
		$error_message = "You must properly fill the Sheet Material field.";
	elseif(!preg_match('#^\d+(?:\.\d{1,2})?$#', $_POST['sheet_width']))
		$error_message = "Incorrect input: ". $_POST['width'] ." on Width field.";
	elseif(!preg_match('#^\d+(?:\.\d{1,2})?$#', $_POST['sheet_height']))
		$error_message = "Incorrect input: ". $_POST['height'] ." on Height field."; 
	elseif(!preg_match('/^[0-9]+$/i', $_POST['sheet_quantity']))
		$error_message = "Incorrect input: ". $_POST['sheet_quantity'] ." on Sheet Quantity field."; 
	else
	{
		$m_id = filter_input(INPUT_POST, "variants");
		$sheet_parent = filter_input(INPUT_POST, "m_id");
		$sheet_width = filter_input(INPUT_POST, "sheet_width");
		$sheet_height = filter_input(INPUT_POST,"sheet_height");
		$sheet_quantity = filter_input(INPUT_POST, "sheet_quantity");
	
		$error_message = Materials::create_new_sheet_inventory($m_id, $sheet_parent, $sheet_width, $sheet_height, $sheet_quantity);
	}
}



?>

<title><?php echo $sv['site_name'];?> Edit Inventory</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-md-12">
			<h1 class="page-header">Edit Inventory</h1>
		</div>
	</div>
	
	<?php
		if($error_message)
		{
			?>
			<div class='col-md-12'>
				<div class='alert alert-danger'>
					<?php echo $error_message; ?>
				</div>
			</div>
			<?php
		} 
	?>

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

<!--—————————————— EDIT MATERIAL ——————————————-->
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
													if(!count($device_mats)) echo "<option>NONE</option>";
													else
													{
														foreach($device_mats as $mat)
															echo "<option value='$mat->m_id'>$mat->m_name</option>";
													}
												?>
											</select>
										</div>
										<table id='__EDITINV__data_table' class='table' hidden>
											<?php
												$__EDITINV__text_attributes = array(
																				"Name" => "m_name", "Product Number" => "product_number",
																				"Parent" => "m_parent", "Price" => "price", 
																				"Unit" => "unit");

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

<!--—————————————— NEW MATERIAL ——————————————-->
						<div id="2" class="tab-pane fade">
							<div class="panel panel-default">
								<div class='panel-body'>
									<table class='table table-bordered table-striped table-hover' id='new_item_table'>
										<tr>
											<td class='col-md-4'>Item Name</td>   
											<td class='col-md-8'>
												<input id='__NEWINV__name_input' class='form-control' type='text' placeholder='New Material' maxlength='50' /> 
											</td>
										</tr>
										<tr>
											<td class='col-md-4'>Product Number</td>
											<td class='col-md-8'>
												<input id='__NEWINV__product_number_input' class='form-control' type='text' placeholder='3D ABS-1KG1.75-BLK' maxlength='30' /> 
											</td>
										</tr>
										<tr>
											<td>Measurable</td>
											<td>
												<select id='__NEWINV__measurable_select' name='measureable_select' class='form-control'>
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
													<input id='__NEWINV__price_input' type="number" min="0" step='0.01' class="form-control" placeholder="0.10"/>
												</div>
											</td>
										</tr>
										<tr>
											<td>Unit</td>
											<td>
												<input id='__NEWINV_unit_input' list='units' class='form-control'/>
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
																<input id="rgb_input" type="text" class='form-control' placeholder="rgb(80,0,0)"
																  onchange="__NEWINV__set_full_color()" onkeydown="__NEWINV__submit_on_enter(event)">
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
										<button class="btn pull-right btn-success" name="to_confirmation" 
										onclick="__NEWINV__compile_and_populate_modal();">
											Create New Item
										</button>
									</div>
								</div>  <!-- <div class="panel-footer"> -->
							</div>  <!-- <div class="panel panel-default"> -->
						</div>  <!-- <div id="5" class="tab-pane fade"> -->


<!--—————————————— CREATE SHEETGOOD PARENT ——————————————-->
						<div id="3" class="tab-pane fade">
							<div class="panel panel-default">
								<div class="panel-body">
									<table class="table table-bordered table-striped table-hover">
										<form method="POST" action="" autocomplete='off'>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top" title="email contact information">Sheet Good Material Name </b>
												</td>
												<td>
													<input type="text" class="form-control"name="sheet_name" id="sheet_name" maxlength="50" size="50" placeholder="Enter Name" />
												</td>
											</tr>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top">Sheet Cost (per in<sup>2</sup>) </b>
												</td>
												<td>
													<div class="input-group">
														<span class="input-group-addon unit">$</span>
														<input type="number" name="sheet_cost" id="sheet_cost" min="0" step='0.01' class="form-control" max="99.99" min="0.00" value="0.00" step="0.01" tabindex="1"/>
													</div>
												</td>
											</tr>
											<tfoot>
												<tr>
													<td colspan="2">
														<div class="pull-right">
															<button type="submit" name="variantBtn" class="btn btn-success" onclick="return Submitter()">Create Sheet Parent</button>
														</div>
													</td>
												</tr>
											</tfoot>
										</form>
									</table>
								</div>  <!-- <div class="panel-body"> -->
							</div>  <!-- <div class="panel panel-default"> -->
						</div>  <!-- <div id="3" class="tab-pane fade"> -->

<!--—————————————— CREATE SHEETGOOD CHILD ——————————————-->
						<div id="4" class="tab-pane fade">
							  <div class="panel panel-default">
								<div class="panel-body">
									<table class="table table-bordered table-striped table-hover">
										<form method="POST" action="" autocomplete='off'>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top" title="Select Parent">Sheet Parent </b>
												</td>
												<td>
													<select class="form-control" name="m_id1" id="m_id1">
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
													<b data-toggle="tooltip" data-placement="top" title="Enter the name of the sheet material">Sheet Good Name </b>
												</td>
												<td>
													<input type="text" class="form-control"name="sheet_name1" id="sheet_name1" maxlength="50" size="50" placeholder="Enter Name" />
												</td>
											</tr>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top" title="Choose or input the color of the material">Color Hex </b>
													<br>Include Color <input type="checkbox" id="colorBox" />
												</td>
												<td>
													<div style="text-align:center;">
														<input disabled type="color" name="sheet_color" id="sheet_color" value="#000000" style="width: 500px;height: 30px;">
													</div>
												</td>
											</tr>
											<tfoot>
												<tr>
													<td colspan="2">
														<div class="pull-right">
															<button type="submit" name="variantBtn1" class="btn btn-success" onclick="return Submitter()">Create Sheet Child</button>
														</div>
													</td>
												</tr>
											</tfoot>
										</form>
									</table>
								</div>  <!-- <div class="panel-body"> -->
							</div>  <!-- <div class="panel panel-default"> -->
						</div>  <!-- <div id="4" class="tab-pane fade"> -->

<!--—————————————— CREATE SHEETGOOD SIZE ——————————————-->
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
													<select class="form-control" name="m_id" id="m_id" onchange="change_m_id()" tabindex="1">
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
													<select class="form-control" name="variants" id="variants" tabindex="1">
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
																<input type="number" class="form-control"name="sheet_width" id="sheet_width" max="500" min="1" value="0" step="0.1" placeholder="Enter Width" />
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
																<input type="number" class="form-control"name="sheet_height" id="sheet_height" max="500" min="1" value="0" step="0.1" placeholder="Enter Height" />
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
													<input type="number" class="form-control"name="sheet_quantity" id="sheet_quantity" max="250" min="1" value="1" step="1" placeholder="Enter Quantity" />
												</td>
											</tr>

											<tfoot>
												<tr>
													<td colspan="2">
														<div class="pull-right">
															<button type="submit" name="inventoryBtn" class="btn btn-success" onclick="return Submitter()">Create Sheet Inventory</button>
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



<div id="__NEWINV__modal" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method='POST'>
				<div class="modal-header" id='modal-header'>
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">Update Inventory</h4>
				</div>
				<div id='__NEWINV__modal_body' class='modal-body'>
					<table id='confirmation_table' class="table table-striped table-bordered table-responsive col-md-12">
					</table>
				</div>
				<div id='__NEWINV__modal_footer' class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button id='__NEWINV__submit_button' type='button' class='btn btn-success'>
						Update Inventory
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
	
	// add more device group rows 
	function __NEWINV__additional_device_group()
	{
		var row = document.getElementById("__NEWINV__device_group_table").insertRow(-1);
		var innerdata =	`<td>
								<div class='input-group'>
									<select tabindex="2" class='form-control __NEWINV__device_group'>
										<option selected disabled hidden>SELECT</option>
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


	function __NEWINV__delete_device_group(element)
	{
		var row = element.closest("tr");
		if(row) row.remove();
		else alert("JS: Problem finding row for button");
	}


	// check if name already exists and warn of overriding
	// checks against the listed materials in update inventory select div
	function __NEWINV__name_already_used(material_name)
	{
		var created_materials = document.getElementsById("__EDITINV__material_select");
		var name = material_name.toLowerCase();
		for(var x = 0; x < created_materials.options.length; x++)
			if(created_materials.options[x].text.toLowerCase() == name) return true;
		return false;
	}


	// populate modal with info for new item
	function __NEWINV__compile_and_populate_modal()
	{
		var new_material_value_table = __NEWINV__HTML_value_table();

		// device groups: front end allows for double submission; back end doesn't
		var dg_instances = document.querySelectorAll(".device_group");
		var populated_dg_count = 0;
		for(var x = 0; x < dg_instances.length; x++)
		{
			var group = dg_instances[x].options[dg_instances[x].selectedIndex];
			if(!group.value) continue;  // ignore blank rows

			var information = "<input name='item_device_group-"+populated_dg_count++
						+"' value='"+group.value+"' hidden/>" + group.text;  // not a typo
			material_attributes.push(["Device Group", information]);
		}

		populate_modal("All Data Is Correct", "new_mat", material_attributes, "New Material");
		// display warning if name is already chosen (case insensitive, remove white space)
		__NEWINV__name_already_used(document.getElementById("__NEWINV__name_input").value.toLowerCase().replace(/^\s+|\s+$/g, ''));
	}


	function __NEWINV__HTML_device_group_table()
	{

	}


	function __NEWINV__HTML_value_table()
	{
		var titles = ["Item Name", "Product Number", "Measurable", "Price", "Unit", "Color Hex"];
		var ids =	["name_input", "product_number_input", "measurable_select", "price_input", "unit_input"];
		if(__NEWINV__INCLUDE_COLOR) ids.push("color_input");  // otherwise color is not selected

		// get values from page
		var values = [];
		for(var x = 0; x < ids.length; x++) values.push(document.getElementById(`__NEWINV__${ids[x]}`).value);

		// validate first 3
		// require fields populated
		var required_fields = [0, 2, 3];
		for(var x = 0; x < required_fields.length; x++)
			if(values[required_fields[x]] == "") return alert(`${titles[required_fields[x]]} requires a value`);

		var table_HTML = `<table class='table'>\n`;
		for(var x = 0; x < values.length; x++)
		{
			table_HTML +=	`<tr>
									<td>${titles[x]}</td>
									<td>\n${value[x]}\n<input name='' value='${value} hidden></td>\n
								</tr>\n`;
		}
		
		return table_HTML + `</table>\n`;
	}


	// ------------------------------------- COLOR PICKING -------------------------------------

	var __NEWINV__INCLUDE_COLOR = false;  // bool to determine if to include color

	var previous_value = "rgb(80,0,0)";  // store string of rgb; default on if bad string entered
	var color_field = document.getElementById("rgb_input");
	color_field.value = previous_value;
	color_field.addEventListener('keyup', function(evt){
		// no "rgb(", ")", values > 255
		var rgb = this.value.substring(4, this.value.length-1).split(',');
		// you're bad at this: reset string punishment
		if(this.value.length < 8)
		{
			this.value = "rgb(80,0,0)";
		}
		else if(this.value.match(/,/g).length != 2 || this.value.substring(0,4) != "rgb(" || this.value.charAt(this.value.length-1) != ')' ||
		  parseInt(rgb[0]) > 255 || parseInt(rgb[1]) > 255 || parseInt(rgb[2]) > 255)
		{
			this.value = previous_value;
		}
		else previous_value = this.value;  // update value to default on
	}, false);


	// button action to hide/show activate/deactivate color input ability
	function __NEWINV__switch_color_active()
	{
		$("#__NEWINV__RGB_td").hide();
		$("#__NEWINV__color_td").hide();
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


	// when enter or tab pressed, change color to HEX equivalent of RGB value in input
	function __NEWINV__submit_on_enter(e)
	{
		keyboardKey = e.which || e.keyCode;
		if(keyboardKey == 13) __NEWINV__set_full_color();
	}


	// get value in RGB input, change to HEX, set HTML5 input color to HEX equiv.
	function __NEWINV__set_full_color()
	{
		var color_field = document.getElementById("rgb_input");
		var color = color_field.value.substring(4, color_field.value.length-1).split(',');
		var hex = "#";
		for(var x = 0; x < color.length; x++)
		{
			var temp = parseInt(color[x]).toString(16);
			hex += temp.length == 1 ? "0" + temp : temp;
		}
		document.getElementById("__NEWINV__color_input").value = hex;
	}


	// change RGB input to equivalent of HEX value from HTML5 color picker
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


</script>