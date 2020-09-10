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


include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

// staff clearance
if (!isset($user) || !$user("inventory")){
	// Not Authorized to see this Page
	header('Location: /index.php');
	$_SESSION['error_msg'] = "Insufficient role level to access, You must be a Lead.";
}

// fire off modal & timer
if($_SESSION['type'] == 'success'){
	echo "<script type='text/javascript'> window.onload = function(){success()}</script>";
}


// display successes & failures
if (filter_input(INPUT_GET, 'outcome')){
	$outcome = filter_input(INPUT_GET, 'outcome');
	echo "<script> window.onload = function() {".
			"populate_modal('Accept', '', outcome_sorter('$outcome'), 'Update Outcome');".
			"$('#modal_submit').hide();".  // hide 'submit' button
			"document.getElementById('cancel_button').innerHTML = 'Ok';".
		"}; </script>";
}

// -------------------------------------- PAGE FUNCTIONALITY --------------------------------------
$resultStr = "";
$device_mats = Materials::getDeviceMats();


// new materials
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_mat'])) {
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

	if($failure) {
		$_SESSION['error_msg'] = $failure;
		header("Location:inventory_processing.php");  // assumed to be malicious; zero everything
	}

	// commence query
	$prior_id = Materials::mat_exists($name);  // material already exists
	// if((material exists and successfully update) || (material !exists and successfully created))
	if(($prior_id && Materials::update_mat($color, $prior_id, $measurability, $price, $product_number, $unit)) 
		|| (!$prior_id && Materials::create_new_mat($color, $measurability, $name, $price, $product_number, $unit))) {
			$m_id = ($prior_id ? $prior_id : Materials::mat_exists($name));  // id to assign device groups
		$outcome = "S".str_replace(' ', '_', $name).successful_and_failed_device_group_additions($m_id, $device_group);  // S for success, F for failed
	}
	else {  // update or create material failed
		$outcome = "F".str_replace(' ', '_', $name);
	}
	header("Location:inventory_processing.php?outcome=".$outcome);
}

// sheet goods
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if ( isset($_POST['variantBtn']) ){
		if(preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['sheet_name']) && preg_match('/^[0-9\.]+$/i', $_POST['sheet_cost'])){
			$sheet_name = filter_input(INPUT_POST, "sheet_name");
			$sheet_cost = filter_input(INPUT_POST, "sheet_cost");

			$resultStr = Materials::create_new_material($sheet_name, $sv[sheet_goods_parent], $sheet_cost, "NULL", "sq_inch(es)", "", "Y");
		}
		else{
			if (!preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['sheet_name'])) {
				$resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Incorrect input: ". $_POST['sheet_name'] ." on Sheet Good Material Name row.</div></div>"); 
			}
			if (!preg_match('/^([1-9][0-9]*|0)(\.[0-9]{2})?$/', $_POST['sheet_cost'])) {
				$resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Incorrect input: ". $_POST['cost'] ." on Sheet Cost row.</div></div>"); 
			}
		}
	} 
	elseif ( isset($_POST['variantBtn1'])){
		$color = substr(filter_input(INPUT_POST, "sheet_color"), 1);  // ignore '#'
		if(isset($_POST['m_id1']) && preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['sheet_name1']) && !($color && !$color = Materials::regexColor($color))){
			
			//if(!$color) $color = "";
			
			$sheet_parent1 = filter_input(INPUT_POST, "m_id1");
			
			if ($result1 = $mysqli->query("   
				SELECT `materials`.`price`
				FROM `materials`
				WHERE `materials`.`m_id` = '$sheet_parent1';")){
			
				while($row = $result1->fetch_assoc()){
					$sheet_cost1 = $row[price];
				}
			}
			$sheet_name1 = filter_input(INPUT_POST, "sheet_name1");

			$resultStr = Materials::create_new_material($sheet_name1, $sheet_parent1, $sheet_cost1, "NULL", "sq_inch(es)", $color, "Y");
		}
		else{
			if (!preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['sheet_name1'])) {
				$resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Incorrect input: ". $_POST['sheet_name1'] ." on Sheet Good Material Name row.</div></div>"); 
			}
			if ($color && !$color = Materials::regexColor($color)) {
				$resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Bad color for creating Sheet Variant.</div></div>"); 
			}
			if (!preg_match('/^([1-9][0-9]*|0)(\.[0-9]{2})?$/', $_POST['m_id1'])) {
				$resultStr =  $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> You must properly fill the Sheet Parent row.</div></div>"); 
			}
			if (!preg_match('/^[a-f0-9]{6}$/', $_POST['sheet_color_hex']) && $_POST['sheet_color_hex'] != "") {
				$resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Incorrect input: ". $_POST['sheet_color_hex'] ." on Color HEX row.</div></div>"); 
			}
		}
	} 
    elseif ( isset($_POST['inventoryBtn']) ) {
        if(isset($_POST['m_id']) && preg_match('/^[0-9]+$/i', $_POST['variants']) && preg_match('#^\d+(?:\.\d{1,2})?$#', $_POST['sheet_width']) && preg_match('#^\d+(?:\.\d{1,2})?$#', $_POST['sheet_height']) && preg_match('/^[0-9]+$/i', $_POST['sheet_quantity'])){
            $m_id = filter_input(INPUT_POST, "variants");
            $sheet_parent = filter_input(INPUT_POST, "m_id");
            $sheet_width = filter_input(INPUT_POST, "sheet_width");
            $sheet_height = filter_input(INPUT_POST,"sheet_height");
            $sheet_quantity = filter_input(INPUT_POST, "sheet_quantity");
        
            $resultStr = Materials::create_new_sheet_inventory($m_id, $sheet_parent, $sheet_width, $sheet_height, $sheet_quantity);
        }
        else{
            if (!isset($_POST['m_id'])) {
                $resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> You must properly fill the Sheet Material field.</div></div>");
            }
            if (!preg_match('/^[0-9]+$/i', $_POST['variants'])) {
                $resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> You must properly fill the Sheet Material field.</div></div>");
            }
            if (!preg_match('#^\d+(?:\.\d{1,2})?$#', $_POST['sheet_width'])) {
                $resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Incorrect input: ". $_POST['width'] ." on Width field.</div></div>");
            }
            if (!preg_match('#^\d+(?:\.\d{1,2})?$#', $_POST['sheet_height'])) {
                $resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Incorrect input: ". $_POST['height'] ." on Height field.</div></div>"); 
            }
            if (!preg_match('/^[0-9]+$/i', $_POST['sheet_quantity'])) {
                $resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Incorrect input: ". $_POST['sheet_quantity'] ." on Sheet Quantity field.</div></div>"); 
            }
        }
    }
    elseif (isset($_POST['quantityBtn'])){

        if(isset($_POST['sheet_id']) && preg_match('/^[\s\S]{9,999}$/', $_POST['quantity_notes']) && preg_match('/^-?[0-9]+$/i', $_POST['quantity_change']) ){
            $sheet_id = filter_input(INPUT_POST, "sheet_id");
            $quantity_notes = filter_input(INPUT_POST, "quantity_notes");
            $quantity_change = filter_input(INPUT_POST,"quantity_change");
            $resultStr = Materials::update_sheet_quantity($sheet_id, $quantity_change, $quantity_notes); 
        }
        else {
            if (!isset($_POST['sheet_id'])) {
                $resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Incorrect input on Sheet Good selection field.</div></div>");
            }
            if (!preg_match('/^[\s\S]{9,999}$/', $_POST['quantity_notes'])) {
                $resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Incorrect input on Sheet Good notes field: input is too short.</div></div>"); 
            }
            if (!preg_match('/^[0-9]+$/i', $_POST['quantity_change'])) {
                $resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Incorrect input on Change in Quantity field.</div></div>"); 
            }
        }
    }
}


// get values for possible instances, return values in array (eg device groups or update rows)
function get_populated_values($tag_name) {
	$count = 0;
	$values = array();
	while(true) {
		$data = filter_input(INPUT_POST, $tag_name."-".$count++);
		if(!$data || $count > 100) return $values;  // 100 to failsafe
		
		if($instances = substr_count($data, '|')) $values[] = explode('|', $data, $instances+1);
		else $values[] = $data;
	}
}


// add already non-existing device groups to material
function successful_and_failed_device_group_additions($m_id, $device_group) {
	foreach($device_group as $dg) {
		$prior_dgs = Materials::get_device_material_group($m_id);  // called each time to prevent double submission in 1 attempt
		if(in_array($dg, $prior_dgs)) continue;  // don't create duplicate
		if(Materials::assign_device_group($dg, $m_id)) {
			$outcome .= "|SDevice:_".$dg;
		}
		else {
			$outcome .= "|FDevice:_".$dg;   
		}
	}
	return $outcome;
}


?>

<title><?php echo $sv['site_name'];?> Edit Inventory</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-md-12">
			<h1 class="page-header">Edit Inventory</h1>
		</div>
	</div>
	
	<?php if ($resultStr != ""){ ?>
			<?php echo $resultStr; ?>
	<?php } ?>
	
	<!-- Update inventory -->
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading" style="background-color: #B5E6E6;">
				<i class="fas fa-warehouse"></i> Edit Inventory
			</div>
			<!-- /.panel-heading -->
			<div class="panel-body">
				<div class="table">
					<ul class="nav nav-tabs">
						<li class="active">
							<a data-toggle="tab" aria-expanded="false" href="#1"> Update Inventory</a>
						</li>
						<li>
							<a data-toggle="tab" aria-expanded="false" href="#2"> Create Inventory</a>
						</li>
						<li class="dropdown">
						  <a class="dropdown-toggle" data-toggle="dropdown" href="#1">Sheet Goods
						  <span class="caret"></span></a>
					  <ul class="dropdown-menu">
						<li><a data-toggle="tab" aria-expanded="false" href="#3">Add Sheet Parent</a></li>
						<li><a data-toggle="tab" aria-expanded="false" href="#4">Add Sheet Child</a></li>
						<li><a data-toggle="tab" aria-expanded="false" href="#5">Add Sheet Inventory</a></li>
						<li><a data-toggle="tab" aria-expanded="false" href="#6">Quantity Management</a></li>
					  </ul>
					</li>
					</ul>

<!--—————————————— UPDATE INVENTORY ——————————————-->

					<div class="tab-content">
					  <div id="1" class="tab-pane fade in active">
						<div class="row">
							&nbsp;&nbsp;&nbsp;&nbsp;
						</div>
						<div class="panel panel-default">
							<div class="panel-body">
								<table class="table-striped table-bordered table-responsive col-md-12" id="update_mat_table">
									<thead>
										<th class='col-md-2' style="text-align:center;">Material</th>
										<th class='col-md-2' style="text-align:center;">Product Number</th>
										<th class='col-md-2' style="text-align:center;">Status</th>
										<th class='col-md-2' style="text-align:center;">Change In Quantity</th>
										<th class='col-md-3' style="text-align:center;">Reason</th>
										<th class='col-md-1'></th> <!-- for cancel row -->
									</thead>
									<tr class='update_rows'>
										<td class="td_select">
											<div>
												<select class="form-control dm_select" onchange='update_unit(this)'>
													<option selected='selected' value="NONE" disabled hidden>Select Material</option>
													<?php foreach($device_mats as $dm){
														// options have three values: m_id, unit, product number
														echo ("<option value='$dm->m_id|$dm->unit|$dm->m_prod_number
																' id='$dm->unit' >$dm->m_name</option>");
													}?>
												</select>
											</div>
										</td>
										<td id='product'>
											<div class='product_number' style='text-align:center;'>
											</div>
										</td>
										<td id='status'>
											<div>
												<select class="form-control status_select" onchange='add_negative_sign_quantity_addon_if_reduction(this);'>
													<option selected='selected' value="NONE" disabled hidden>Select Status</option>
													<?php
													$status_list = Status::getList();
													foreach(Status::material_statuses() as $id)
														echo "<option value='$id'>$status_list[$id]</option>";
													?>
												</select>
											</div>
										</td>
										<td>
											<div class="input-group">
												<input type="number" min="0" class="form-control quantity" placeholder="1,000" max='9999999.99'/>
												<span class="input-group-addon unit"></span>
											</div>
										</td>
										<td id='reason'>
											<input type='text' class='form-control reason' placeholder='Reason'/>
										</td>
										<td style='text-align:center;margin:auto;'>
											<button class='btn' style='width:100%' onclick='delete_row(this);'>&times;</button>
										</td>
									</tr>
								</table>
								<button class="btn btn-info pull-right" onclick="additional_mat()">Additional Material Updates</button>
							</div>
							<div class="panel-footer">
								<div class="clearfix">
									<button class="btn pull-right btn-success" name="to_confirmation" onclick="compile_inventory_update_and_populate_modal()">Submit</button>
								</div>
							</div>
						</div>
					  </div>
					
<!--—————————————— CREATE INVENTORY ——————————————-->

					  <div id="2" class="tab-pane fade">
						<div class="row">
							&nbsp;&nbsp;&nbsp;&nbsp;
						</div>
						<div class="panel panel-default">
							<div class='panel-body'>
								<table class='table table-bordered table-striped table-hover' id='new_item_table'>
									<tr>
										<td class='col-md-4'>
											Item Name
										</td>   
										<td class='col-md-8'>
											<input id='new_item_name' class='form-control' type='text' placeholder='New Material' maxlength='50' /> 
										</td>
									</tr>
									<tr>
										<td class='col-md-4'>
											Product Number
										</td>
										<td class='col-md-8'>
											<input id='new_product_number' class='form-control' type='text' placeholder='3D ABS-1KG1.75-BLK' maxlength='30' /> 
										</td>
									</tr>
									<tr>
										<td>
											Measurable
										</td>
										<td>
											<select id='new_item_measurability' name='measureable_select' class='form-control'>
												<option value='Y'>Y</option>
												<option value='N'>N</option>
											</select>
										</td>
									</tr>
									<tr>
										<td>
											Price
										</td>
										<td>
											<div class="input-group">
												<span class="input-group-addon unit">$</span>
												<input id='new_item_price' type="number" min="0" step='0.01' class="form-control" placeholder="0.10"/>
											</div>
										</td>
									</tr>
									<tr>
										<td>
											Unit
										</td>
										<td>
											<input id='new_item_unit' list='units' class='form-control'/>
											<datalist id='units'>
												<?php if($autofills = $mysqli->query("
														SELECT DISTINCT `unit` 
														FROM `materials` 
														WHERE `unit` != ''
													")) {
													while($row = $autofills->fetch_assoc()) {
														echo "<option value='$row[unit]'>$row[unit]</option>";
													}
												} ?>
											</datalist>
										</td>
									</tr>
									<tr>
										<td>
											Color Hex
										</td>
										<td>
											<table style="margin:none;height:100%;width:100%;padding:0px;"> <tr style="width:100%;">
												<td style="width:50%;" id='rgb_td' hidden>
													<div class="input-group">
														<span class="input-group-addon unit">RGB</span>
														<input id="rgb_input" type="text" class='form-control' placeholder="rgb(80,0,0)"
														  onchange="color_setFullColor()" onkeydown="color_submitOnEnter(event)">
													</div>
												</td>
												<td style="width:30%;" id="color_picker_td" hidden>
													<input type="color" id="new_item_color" class='form-control' style='padding:0px;'
													  onchange="color_clickColor(0, 5)" value="#500000" style="width:85%;">
												</td>
												<td style="width:20%;">
													<button id='include_color_button' class='btn' type='button' style='width:100%;' onclick="color_switch()">Include Color</button>
												</td>
											</tr></table>
										</td>
									</tr>
									<tr id='dg_row'>
										<td>
											Device Group
										</td>
										<td>
											<select id="new_item_dg-0" tabindex="2" class='form-control device_group'>
												<option value="">NONE</option>
												<?php if($devices = $mysqli->query("
														SELECT `dg_id`, `dg_name`, `dg_desc`
														FROM `device_group`
														ORDER BY `dg_desc`
													")) {
													while($row = $devices->fetch_assoc()){
														echo("<option value='$row[dg_id]'>$row[dg_desc]</option>");
													}
												} else {
													echo ("Device list Error - SQL ERROR");
												} ?>
											</select>
										</td>
									</tr>
								</table>
								<button class="btn btn-info pull-right" onclick="additional_device_groups()">Additional Device Groups</button>
							</div>
							<div class="panel-footer">
								<div class="clearfix">
									<button class="btn pull-right btn-success" name="to_confirmation" onclick="create_new_item()">Create New Item</button>
								</div>
							</div>
						</div>
					  </div>

<!--——————————————— SHEET GOODS ———————————————-->
						
					  <div id="3" class="tab-pane fade">
						<div class="row">
							&nbsp;&nbsp;&nbsp;&nbsp;
						</div>
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
							</div>
						</div>
					  </div>
						
					  <div id="4" class="tab-pane fade">
						<div class="row">
							&nbsp;&nbsp;&nbsp;&nbsp;
						</div>
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
													<option disabled selected value="">Select Sheet</option>
													<?php
														$result = $mysqli->query("	  
															SELECT DISTINCT `materials`.`m_id`, `materials`.`m_name`
															FROM `materials`
															WHERE `materials`.`m_parent` = '$sv[sheet_goods_parent]';");
														while($row = $result->fetch_assoc()){
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
							</div>

						  </div>
					  </div>
						
					  <div id="5" class="tab-pane fade">
						<div class="row">
							&nbsp;&nbsp;&nbsp;&nbsp;
						</div>
						<div class="panel panel-default">
							<div class="panel-body">
								<table class="table table-bordered table-striped table-hover">
									<form method="POST" action="" autocomplete='off'>
										<tr>
											<td>
												<b data-toggle="tooltip" data-placement="top" title="Select Variant">Sheet Material </b>
											</td>
											<td>
												<div class="col-md-6">
												<select class="form-control" name="m_id" id="m_id" onchange="change_m_id()" tabindex="1">
													<option disabled hidden selected value="">Sheet Parent</option>
													<?php
														$result = $mysqli->query("	  
															SELECT DISTINCT `materials`.`m_id`, `materials`.`m_name`
															FROM `materials`
															WHERE `materials`.`m_parent` = '$sv[sheet_goods_parent]';");
														while($row = $result->fetch_assoc()){
															echo "<option value=\"$row[m_id]\">$row[m_name]</option>";
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
							</div>
						</div>
					  </div>
					  
                      <div id="6" class="tab-pane fade">
                        <div class="row">
                            &nbsp;&nbsp;&nbsp;&nbsp;
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <table class="table table-bordered table-striped table-hover">
                                    <form method="POST" action="" autocomplete='off'>
                                        <tr>
                                            <td>
                                                <b data-toggle="tooltip" data-placement="top" title="Select Variant">Sheet Good </b>
                                            </td>
                                            <td>
                                                <div class="col-md-6">
                                                    <select class="form-control" name="sheet_id" id="sheet_id" onchange="change_sheets()" tabindex="1">
                                                        <option value="" disabled selected>Sheet Child</option>
                                                        <?php
                                                            $result = $mysqli->query("            
                                                                SELECT `materials`.`m_id`, `materials`.`m_name`
                                                                FROM `materials` , `sheet_good_inventory`
                                                                WHERE `sheet_good_inventory`.`m_ID`=`materials`.`m_id`
                                                                GROUP BY `materials`.`m_id`;");
                                                            while($row = $result->fetch_assoc()){
                                                                echo "<option value=\"$row[m_id]\">$row[m_name]</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                </div>


                                                <div class="col-md-6">
                                                    <select class="form-control" name="sheet_variants" id="sheet_variants" onchange="change_quantity()" tabindex="1">
                                                        <option value =""> Select Child First</option>
                                                    </select> 
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <b data-toggle="tooltip" data-placement="top">Change in Quantity </b>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control"name="quantity_change" id="quantity_change" max="250" min="-250" value="1" step="1" placeholder="Enter Quantity" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <b>Notes</b>
                                            </td>
                                            <td>
                                                <textarea rows="4" cols="50" type="text" name="quantity_notes" id="quantity_notes" class="form-control" placeholder="Enter notes regarding quantity change"></textarea>
                                            </td>
                                        </tr>

                                        <tfoot>
                                            <tr>
                                                <td colspan="2">
                                                    <div class="pull-right">
                                                        <button type="submit" name="quantityBtn" class="btn btn-success" onclick="return Submitter()">Update Quantity</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </form>
                                </table>
                            </div>

                        </div>
                      </div>
                    
					</div>
				</div>
				<!-- /.table-responsive -->
			</div>
		</div>
	</div>
	<!-- /.col-md-8 -->
</div>

<!-- MODAL -->
<!-- TODO: fix so that line isn't in middle -->
<div id="update_modal" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method='POST'>
				<div class="modal-header" id='modal-header'>
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">Update Inventory</h4>
				</div>
				<div id='modal-body' class='modal-body'>
					<table id='confirmation_table' class="table table-striped table-bordered table-responsive col-md-12">
					</table>
				</div>
				<div id='modal-footer' class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button id='submit_inventory_button' type='button' class='btn btn-success'>
						Update Inventory
					</button>
				</div>
			</form>
		</div>
	</div>
</div>


<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>


<script>
	var MATERIALS_TO_BE_UPDATED = null;  // store values to be gathered for submission


	// —————————————— UPDATE INVENTORY ———————————————
	// ———————————————————————————————————————

	// ———————————————— PAGE USAGE ————————————————

	// the x button is pressed for a row to be deleted (because it is unused or some other reason)
	function delete_row(element) {
		// prevent deletion of all rows
		if(document.getElementsByClassName("update_rows").length == 1) {
			alert("You must have at least 1 material update");
			return;
		}
		var row = element.closest("tr");
		if(row) row.remove();
		else alert("JS: Problem finding row for button");
	}


	// add material row to inventory update table
	function additional_mat() {
		var loaded_mats = document.querySelectorAll(".update_rows").length;
		var table = document.getElementById("update_mat_table");
		var row = table.insertRow(-1);  // insert at bottom
		row.className = "update_rows";
		row.innerHTML = table.rows[1].innerHTML;
		row.getElementsByClassName("unit")[0].innerHTML = "";
	}


	// when a status is selected, have a symbolic negative sign (backend automatically flips 
	// value) to show a reduction in amount
	function add_negative_sign_quantity_addon_if_reduction(element) {
		var ancestor = element.parentElement.parentElement.parentElement;
		var input_group = ancestor.children[3].children[0];
		if((element.value == <?php echo $STATUS["removed"]; ?> ||
		element.value == <?php echo $STATUS["failed_mat"] ?> ||
		element.value == <?php echo $STATUS["used"]; ?>) && 
		!input_group.getElementsByClassName("negative").length) {
			var negative_span = document.createElement("span");
			negative_span.classList.add("negative", "input-group-addon");
			negative_span.innerHTML = "–";
			input_group.insertBefore(negative_span, input_group.firstChild);
		}
		else if(element.value != <?php echo $STATUS["removed"]; ?> &&
		element.value != <?php echo $STATUS["failed_mat"] ?> &&
		element.value != <?php echo $STATUS["used"]; ?> && 
		input_group.getElementsByClassName("negative").length) {
			input_group.children[0].remove();
		}

	}


	// insert [unit] and related product # into cells
	function update_unit(element) {
		var select_mat_data = element.options[element.selectedIndex].value.split('|');
		var great_grandparent = element.parentElement.parentElement.parentElement;
		great_grandparent.getElementsByClassName("unit")[0].innerHTML = select_mat_data[1] || "[ - ]";
		great_grandparent.getElementsByClassName("product_number")[0].innerHTML = select_mat_data[2] || "[unassigned]";
	}


	// —————————————————– UPDATE —————————————————

	// if number of items does not equal one, return error message; otherwise return none (no error)
	function alert_for_no_rows_found(results, success) {
		if(results.length) return false;

		alert(	`ERROR: Could not find a row for material ${success["m_id"]} with a `+
				`status of ${success["status_id"]}, quantity of ${success["quantity_used"]} `+
				`and notes: '${success["notes"]}'`);
		return true;
	}

	// populate modal with inventory change
	function compile_inventory_update_and_populate_modal(){
		// get information of updated materials
		var materials = get_and_check_update_values();  // list to hold dict values for data from each row
		if(!materials) return;  // bad data: stop process from proceding to modal populations
		else if(materials.length < 1) {
			alert("Enter at least 1 item");
			return;
		}

		// header for modal table
		var table = modal_data_table(materials);
		populate_modal(table, materials, "All Data Is Correct");
	}


	// find the matching update row based on m_id, quantity_used, status & notes
	function row_for_material_update(update) {
		var input_rows = document.getElementsByClassName("update_rows");
		// get matching rows based on material ID
		var material_match_rows = [];
		for(var x = 0; x < input_rows.length; x++)
			if(parseInt(input_rows[x].getElementsByClassName("dm_select")[0].value) == parseInt(update["m_id"])) 
				material_match_rows.push(input_rows[x]);

		if(!material_match_rows.length) return [];
		else if(material_match_rows.length == 1)
			return material_match_rows;
		// further narrowing
		else {
			var material_matched_by_all_attributes = [];  // store all results to see if multiple found
			for(var x = 0; x < material_match_rows.length; x++)
				if(parseInt(material_match_rows[x].getElementsByClassName("quantity")[0].value) == parseInt(update["quantity"]) &&
				parseInt(material_match_rows[x].getElementsByClassName("status_select")[0].value) == parseInt(update["status"]) &&
				material_match_rows[x].getElementsByClassName("reason")[0].value == update["notes"])
					material_matched_by_all_attributes.push(material_match_rows);

			return material_matched_by_all_attributes;
		}
	}


	// remove the update instances from the update table based on successes return by the 
	// AJAX call
	function remove_inventory_update_instance_from_page(successes) {
		var failures = [];
		for(var key in successes) {
			var material_update = Object.assign({}, successes[key], {"m_id" : key});
			var row = row_for_material_update(material_update);

			if(row.length == 1) row[0].remove();
			else failures.push(material_update);
		}

		for(var x = 0; x < failures.length; x++)
			var failures_string = `\n material ${failures["m_id"]} `+
					`with a status of ${failures["status"]}, quantity of ${failures["quantity"]} and `+
					`notes: '${failures["notes"]}'`;
		if(failures.length) 
			alert(	"Unable to refresh page of updated materials.  Please note that these "+
					"materials were updated despite remaining in the list:" + failures_string);
	}


	// get info for each row, add it to dictionary, send dict to populate confirmation module
	function get_and_check_update_values() {
		var input_rows = document.getElementsByClassName("update_rows");
		var materials = [];  // list to hold dict values for data from each row
		for(var x = 0; x < input_rows.length; x++) {
			var quantity = input_rows[x].getElementsByClassName("quantity")[0].value;
			var reason = input_rows[x].getElementsByClassName("reason")[0].value.replace(/\|/g, ";");
			
			var mat = input_rows[x].getElementsByClassName("dm_select")[0];
			var mat_id = mat.options[mat.selectedIndex].value.split('|')[0];
			var name = mat.options[mat.selectedIndex].text;
			var product = input_rows[x].getElementsByClassName("product_number")[0].innerHTML;

			var status = input_rows[x].getElementsByClassName("status_select")[0];
			var status_id = status.options[status.selectedIndex].value;
			var status_text = status.options[status.selectedIndex].text;			

			// check if all information is filled out or empty
			// product is not passed to backend, so no need to check
			if(quantity === "" && mat_id === "NONE" && reason === "" && status_id === "NONE") continue;  //TODO: delete row
			else if(mat_id === "NONE") return alert_and_return("Select a material or clear everything in row");
			else if(quantity === "") return alert_and_return("Enter a quantity or clear everything in row");
			else if(parseFloat(quantity) > 99999.99) return alert_and_return("The database does not accept a quantity larger than 99999.99");
			else if(status_id === "NONE") return alert_and_return("Select a status or clear everything in row");
			else if(reason.length < 5) return alert_and_return("Enter a reason or clear everything in row");
			materials.push({"quantity_used" : quantity, "m_id" : mat_id, "name" : name,
				  "status_id" : status_id, "status_text" : status_text, "product" : product, "notes" : reason});
		}
		return materials;
	}


	function modal_data_table(data) {
		var table = document.createElement("table");
		table.classList.add("table", "table-striped", "table-bordered", "table-responsive", "col-md-12");
		table.innerHTML = `<thead>
								<th><b>Material</b></th>
								<th><b>Product Number</b></th>
								<th><b>Status</b></th>
								<th><b>Quantity</b></th>
								<th><b>Reason</b>
							</thead>`;
		for(var x = 0; x < data.length; x++) {
			var row = table.insertRow(-1);
			row.innerHTML =  `<td>${data[x]["name"]}</td>
								<td>${data[x]["product"]}</td>
								<td>${data[x]["status_text"]}</td>
								<td>${data[x]["quantity_used"]}</td>
								<td>${data[x]["notes"]}</td>`;
		}
		return table;
	}


	function populate_modal(body, data, head) {
		document.getElementById("modal-body").innerHTML = body.outerHTML;
		document.getElementById("submit_inventory_button").onclick = function() {
			submit_inventory_update(data);
		};
		$('#update_modal').modal('show');
	}


	function submit_inventory_update(materials) {
		$('#update_modal').modal('hide');
		console.log(materials);
		console.log(JSON.stringify(materials));
		$.ajax({
			url: "./sub/material_ajax_requests.php",
			type: "POST",
			dataType: "json",
			data: {"update_inventory" : true, "mats_used_update" : JSON.stringify(materials)},
			success: function(response) {
				var material_options = document.getElementsByClassName("update_rows")[0].getElementsByClassName("dm_select")[0].innerHTML;
				var material_statuses = document.getElementsByClassName("update_rows")[0].getElementsByClassName("status_select")[0].innerHTML;
				var update_row = 	`<td class="td_select">
										<div><select class="form-control dm_select" onchange="update_unit(this)">${material_options}</select></div>
									</td>
									<td id="product"><div class="product_number" style="text-align:center;"></div></td>
									<td id="status">
										<div><select class="form-control status_select" onchange="add_negative_sign_quantity_addon_if_reduction(this);">${material_statuses}</select></div>
									</td>
									<td><div class="input-group">
											<input type="number" min="0" class="form-control quantity" placeholder="1,000" max="9999999.99">
											<span class="input-group-addon unit"></span>
									</div></td>
									<td id="reason"><input type="text" class="form-control reason" placeholder="Reason"></td>
									<td style="text-align:center;margin:auto;">
										<button class="btn" style="width:100%" onclick="delete_row(this);">×</button>
									</td>`;

				remove_inventory_update_instance_from_page(response["successes"]);
				alert("Successfully updated all materials");

				var new_row = document.getElementById("update_mat_table").insertRow(-1);
				new_row.class = "update_rows";
				new_row.innerHTML = update_row;
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				alert("Error in query submission");
				console.log(errorThrown);
			}
		});
	}




	// ——————————————— SHEET GOODS ————————————————–
	// ———————————————————————————————————————

// ----------------------------------------- DATA & MODAL -----------------------------------------

	function Submitter(){

		if (confirm("You are about to submit this query. Click OK to continue or CANCEL to quit.")){
			return true;
		}
		return false;
	} 
	
    function change_m_id(){
        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            // code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("variants").innerHTML = this.responseText;
            }
        };
        
        xmlhttp.open("GET","/pages/sub/si_getVariants.php?val="+ document.getElementById("m_id").value, true);
        xmlhttp.send();
        inUseCheck();
    }
    
    function change_sheets(){
        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            // code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("sheet_variants").innerHTML = this.responseText;
            }
        };
        
        xmlhttp.open("GET","/pages/sub/si_getSheets.php?val="+ document.getElementById("sheet_id").value, true);
        xmlhttp.send();
        inUseCheck();
    } 

	function change_quantity(){
		if (window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp = new XMLHttpRequest();
		} else {
			// code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				document.getElementById("current_quantity").innerHTML = this.responseText;
			}
		};
		
		xmlhttp.open("GET","/pages/sub/si_Changequantity.php?val="+ document.getElementById("sheet_id").value, true);
		xmlhttp.send();
		inUseCheck();
	}



<?php 

// -—————————————— CREATE NEW MATERIAL ——————————————
// -————————————————————————————————————————

// further restrict new item creation to trainers
 ?>

	// change the text of the button that collapses the section between current inventory and create new material
	function button_text(element) {
		if(element.innerHTML == "Create New Inventory Item") element.innerHTML = "Back to Update Current Inventory";
		else element.innerHTML = "Create New Inventory Item";
	}

	function button_text1(element) {
		element.value = (element.value == "Add Sheet Good Material") ? "Hide Tool" : "Add Sheet Good Material";
	}
	
	function button_text2(element) {
		if(element.innerHTML == "Manage Sheet Good Inventory") element.innerHTML = "Hide";
		else element.innerHTML = "Manage Sheet Good Inventory";
	}
	
	document.getElementById('colorBox').onchange = function() {
		document.getElementById('sheet_color').disabled = !this.checked;
	};
	
	// add more device group rows 
	function additional_device_groups() {
		var row = document.getElementById("new_item_table").insertRow(-1);
		var innerdata = document.getElementById("dg_row").innerHTML;
		// put HTML of first device group div into new one with updated id
		row.innerHTML = innerdata.replace("new_item_dg-0", "new_item_dg-"+document.querySelectorAll(".device_group").length);
	}


	// check if name already exists and warn of overriding
	// checks against the listed materials in update inventory select div
	function name_already_used(name) {
		var created_materials = document.getElementsByClassName("dm_select")[0];
		for(var x = 0; x < created_materials.options.length; x++) {
			if(created_materials.options[x].text.toLowerCase() == name) {
				var modal_button = document.getElementById("modal_submit");
				var modal_header = document.getElementsByClassName("modal-header")[0];
				modal_header.innerHTML = modal_header.innerHTML + 
					"<h4 style='color:rgb(200,0,0,1);'>WARNING: THIS NAME IS ALREADY TAKEN.<br/>"+
					"PROCEEDING WILL REWRITE THE FOLLOWING INFORMATION</h4>";
				modal_button.classList.add("btn-danger");
				modal_button.innerHTML = "Override";
				return;
			}
		}
	}


	// populate modal with info for new item
	function create_new_item() {
		var titles = ["Item Name", "Product Number", "Measurable", "Price", "Unit", "Color Hex"];
		var ids = ["new_item_name", "new_product_number", "new_item_measurability", "new_item_price", "new_item_unit"];
		if(include_color) ids.push("new_item_color");  // otherwise color is not selected

		// require fields populated (first 3)
		for(var x = 0; x < 1; x++) if(document.getElementById(ids[x]).value == "") {
			alert(titles[x] + " requires a value");
			return;
		}
		var material_attributes = [["<b>Attribute</b>", "<b>Value</b>"]];
		// remove whitespace and add each attribute value to input for PHP to get data from
		for(var x = 0; x < ids.length; x++) {
			var value = document.getElementById(ids[x]).value.replace(/^\s+|\s+$/g, '');  
			if(!value) continue;  // ignore blank values
			// input to store data for PHP to get from
			var information = "<input name='"+ids[x].substr(4)+"' value='"+value+"' hidden/>" + value;
			material_attributes.push([titles[x], information]);
		}

		// device groups: front end allows for double submission; back end doesn't
		var dg_instances = document.querySelectorAll(".device_group");
		var populated_dg_count = 0;
		for(var x = 0; x < dg_instances.length; x++) {
			var group = dg_instances[x].options[dg_instances[x].selectedIndex];
			if(!group.value) continue;  // ignore blank rows

			var information = "<input name='item_device_group-"+populated_dg_count++
						+"' value='"+group.value+"' hidden/>" + group.text;  // not a typo
			material_attributes.push(["Device Group", information]);
		}

		populate_modal("All Data Is Correct", "new_mat", material_attributes, "New Material");
		// display warning if name is already chosen (case insensitive, remove white space)
		name_already_used(document.getElementById("new_item_name").value.toLowerCase().replace(/^\s+|\s+$/g, ''));
	}


	// ------------------------------------- COLOR PICKING -------------------------------------

	var include_color = false;  // bool to determine if to include color

	var previous_value = "rgb(80,0,0)";  // store string of rgb; default on if bad string entered
	var color_field = document.getElementById("rgb_input");
	color_field.value = previous_value;
	color_field.addEventListener('keyup', function(evt){
		// no "rgb(", ")", values > 255
		var rgb = this.value.substring(4, this.value.length-1).split(',');
		// you're bad at this: reset string punishment
		if(this.value.length < 8) {
			this.value = "rgb(80,0,0)";
		}
		else if(this.value.match(/,/g).length != 2 || this.value.substring(0,4) != "rgb(" || this.value.charAt(this.value.length-1) != ')' ||
		  parseInt(rgb[0]) > 255 || parseInt(rgb[1]) > 255 || parseInt(rgb[2]) > 255) {
			this.value = previous_value;
		}
		else previous_value = this.value;  // update value to default on
	}, false);


	// button action to hide/show activate/deactivate color input ability
	function color_switch() {
		$("#rgb_td").hide();
		$("#color_picker_td").hide();
		include_color = !include_color;
		if(include_color) {
			document.getElementById("include_color_button").innerHTML = "Exclude Color";
			$("#rgb_td").show();
			$("#color_picker_td").show();
		}
		else {
			document.getElementById("include_color_button").innerHTML = "Include Color";
			$("#rgb_td").hide();
			$("#color_picker_td").hide();
		}
	}


	// when enter or tab pressed, change color to HEX equivalent of RGB value in input
	function color_submitOnEnter(e) {
		keyboardKey = e.which || e.keyCode;
		if (keyboardKey == 13) {
			color_setFullColor();
		}
	}


	// get value in RGB input, change to HEX, set HTML5 input color to HEX equiv.
	function color_setFullColor() {
		var color_field = document.getElementById("rgb_input");
		var color = color_field.value.substring(4, color_field.value.length-1).split(',');
		var hex = "#";
		for(var x = 0; x < color.length; x++) {
			var temp = parseInt(color[x]).toString(16);
			hex += temp.length == 1 ? "0" + temp : temp;
		}
		document.getElementById("new_item_color").value = hex;
	}


	// change RGB input to equivalent of HEX value from HTML5 color picker
	function color_clickColor(hex, html5) {
		var color;
		if (html5 && html5 == 5)  {
			color = document.getElementById("new_item_color").value;
		}
		else {
			alert("Color wheel is only supported by HTML5\nPlease use RGB box");
			return;
		}

		r = parseInt(color.substr(1,2), 16);
		g = parseInt(color.substr(3,2), 16);
		b = parseInt(color.substr(5), 16);
		document.getElementById("rgb_input").value = "rgb(" + r + ',' + g + ',' + b + ')';
	}


	function alert_and_return(message) {
		alert(message);
		return null;
	}

</script>