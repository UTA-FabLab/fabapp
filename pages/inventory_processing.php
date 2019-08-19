<?php 
/**************************************************
*
*	@author MPZinke on 12.08.18
*
*	-Allow ability for lvl lead to edit inventory
*	-Allow ability for admin to add new material
*	 to inventory
*	-UPDATE: added product number
*	 on 04.03.19
*
*	Edited by Sammy Hamwi Summer 2019
*	 with sheetgood changes
*
**************************************************/

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

// staff clearance
if (!$staff || $staff->getRoleID() < $sv['LvlOfLead']){
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
$materials = Materials::get_all_materials();

// new inventory processing
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_inventory'])) {
	$outcomes = array();
	$values = get_populated_values("row-dict");
	foreach($values as $val) {
		// val: 0) name, 1) mat_id, 2) quantity, 3) status
		if(!Materials::mat_exists($val[1])) continue;  // ignore unknown materials
		// function regexs all inputs; S for success, F for failed
		// quant * -1 to counter default quantity used as negative value change in ::insert_material_used
		if(is_int(Mats_Used::insert_material_used(null, $val[1], $val[3], $staff, -1 * floatval($val[2]))))
			$outcomes[] = "S".str_replace(' ', '_', $val[0]);
		else $outcomes[] = "F".str_replace(' ', '_', $val[0]);
	}
	header("Location: inventory_processing.php?outcome=".implode('|', $outcomes));
}
// new materials
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_mat'])) {
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
		if(isset($_POST['m_id']) && isset($_POST['variants']) && preg_match('#^\d+(?:\.\d{1,2})?$#', $_POST['sheet_width']) && preg_match('#^\d+(?:\.\d{1,2})?$#', $_POST['sheet_height']) && preg_match('/^[0-9]+$/i', $_POST['sheet_quantity'])){
			$m_id = filter_input(INPUT_POST, "variants");
			$sheet_parent = filter_input(INPUT_POST, "m_id");
			$sheet_width = filter_input(INPUT_POST, "sheet_width");
			$sheet_height = filter_input(INPUT_POST,"sheet_height");
			$sheet_quantity = filter_input(INPUT_POST, "sheet_quantity");
		
			$resultStr = Materials::create_new_sheet_inventory($m_id, $sheet_parent, $sheet_width, $sheet_height, $sheet_quantity);
		}
		else{
			if (!isset($_POST['m_id'])) {
				$resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> You must properly fill the Sheet Material row.</div></div>");
			}
			if (!isset($_POST['variants'])) {
				$resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> You must properly fill the Sheet Material row.</div></div>");
			}
			if (!preg_match('#^\d+(?:\.\d{1,2})?$#', $_POST['sheet_width'])) {
				$resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Incorrect input: ". $_POST['width'] ." on Width row.</div></div>");
			}
			if (!preg_match('#^\d+(?:\.\d{1,2})?$#', $_POST['sheet_height'])) {
				$resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Incorrect input: ". $_POST['height'] ." on Height row.</div></div>"); 
			}
			if (!preg_match('/^[0-9]+$/i', $_POST['sheet_quantity'])) {
				$resultStr = $resultStr.("<div class='col-md-12'><div class='alert alert-danger'> Incorrect input: ". $_POST['sheet_quantity'] ." on Sheet Quantity row.</div></div>"); 
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
			<div class="panel-heading">
				<i class="fas fa-shipping-fast"></i> Update For Newly Delivered Inventory
				<div class="pull-right">
					<div class="btn-group">
						<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
							<span class="fas fa-info"></span>
						</button>
						<ul class="dropdown-menu pull-right" role="menu">
							<li>
								<a>If the product number does not match the<br/>product received please ask a supervisor</a>
							</li>
						</ul>
					</div>
				</div>
			</div>
			<!-- /.panel-heading -->
			<div class='collapse in inventory_collapse' id='collapse'>
				<div class="panel-body">
					<table class="table-striped table-bordered table-responsive col-md-12" id="update_mat_table">
						<thead>
							<th class='col-md-2' style="text-align:center;">Material</th>
							<th class='col-md-2' style="text-align:center;">Change In Quantity</th>
							<th class='col-md-1' style="text-align:center;">Status</th>
							<th class='col-md-2' style="text-align:center;">Product Number</th>
						</thead>
						<tr class='update_rows'>
							<td class="td_select">
								<div>
									<select class="form-control dm_select" onchange='update_unit(this)'>
										<option selected='selected' value="NONE">Select Material</option>
										<?php foreach($materials as $mat){
											// options have three values: m_id, unit, product number
											echo ("<option value='$mat->m_id|$mat->unit|$mat->m_prod_number
													' id='$mat->unit' >$mat->m_name</option>");
										}?>
									</select>
								</div>
							</td>
							<td>
								<div class="input-group">
									<input type="number" min="0" class="form-control loc quantity" placeholder="1,000" max='9999999.99'/>
									<span class="input-group-addon unit"></span>
								</div>
							</td>
							<td id='status'>
								<div>
									<select class="form-control status_select">
										<option selected='selected' value="NONE">Select Status</option>
										<?php  
											$mat_statuses = Status::material_statuses();
											$status_list = Status::getList();
											foreach($mat_statuses as $mat_stat)
												echo "<option value='$mat_stat'>$status_list[$mat_stat]</option>";
										}?>
									</select>
								</div>
							</td>
							<!-- TODO -->
							<td id='product'>
								<div class='product_number' style='text-align:center;'>
									
								</div>
							</td>
						</tr>
					</table>
					<button class="btn btn-info pull-right" onclick="additional_mat()">Additional Material Updates</button>
				</div>
				<div class="panel-footer">
					<div class="clearfix">
						<button class="btn pull-right btn-success" name="to_confirmation" onclick="update_inventory()">Submit</button>
					</div>
				</div>
			</div>
		</div>

	<!------------------------------- Add new item to inventory ------------------------------->
	<?php if($staff->getRoleID() >= $sv['minRoleTrainer']) { ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<button class='btn btn-default' style='right: 10px;' type='button' data-toggle='collapse' data-target='.inventory_collapse' 
				  onclick='button_text(this)' aria-expanded='false' aria-controls='collapse'>Create New Inventory Item</button>
			</div>
			<div class='collapse inventory_collapse'>
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
	<?php } ?>
		
	<div class="panel panel-default">
		<div class="panel-heading">
			<button class='btn btn-default' style='right: 10px;' type='button' data-toggle='collapse' data-target='.sheet_inventory_collapse' 
			  onclick='button_text2(this)' aria-expanded='false' aria-controls='collapse'>Manage Sheet Good Inventory</button>
		</div>
		<div class='collapse sheet_inventory_collapse'>
			<div class='panel-body'>
				<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fas fa-plus fa-fw" aria-hidden="true"></i> Add New Sheet Good Inventory
					</div>
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
											<div class="col-md-2">
												<i>Width </i>
											</div>
											<div class="col-md-10">
												<div class="input-group">
													<input type="number" class="form-control"name="sheet_width" id="sheet_width" max="500" min="1" value="0" step="0.1" placeholder="Enter Width" />
													<span class="input-group-addon unit">inch(es)</span>
												</div>
											</div>
										</div>
										<div class="col-md-12">
											<div class="col-md-2">
												<i>Height </i>
											</div>
											<div class="col-md-10">
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
											<div class="pull-left">
												<button type="submit" name="inventoryBtn" class="btn btn-success" onclick="return Submitter()">Add Sheet Inventory</button>
											</div>
										</td>
									</tr>
								</tfoot>
							</form>
						</table>
					</div>
					<!-- /.panel-body -->
				</div>
				<!-- /.panel --> 
			</div>

				<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fas fa-plus fa-fw" aria-hidden="true"></i> Add New Sheet Good Variant
					</div>
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
											<input disabled type="color" name="sheet_color" id="sheet_color" value="" style="width: 300px;">
										</div>
									</td>
								</tr>
								<tfoot>
									<tr>
										<td colspan="2">
											<div class="pull-left">
												<button type="submit" name="variantBtn1" class="btn btn-success" onclick="return Submitter()">Add Sheet Variant</button>
											</div>
										</td>
									</tr>
								</tfoot>
							</form>
						</table>
					</div>
					<!-- /.panel-body -->
					<div class="panel-heading">
						<div class="panel panel-default">
							<div style="text-align:center;">
							<input class='btn btn-info' type='button' data-toggle='collapse' data-target='.variant_field' onclick='button_text1(this)' aria-expanded='false' style='width:100%;' aria-controls='collapse' id="Add Sheet Good Material" value='Add Sheet Good Material'/>
							</div>
						</div>
					</div>
					<div class="panel-body">
						<div class='col-md-12 variant_field collapse'>
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
												<div class="pull-left">
													<button type="submit" name="variantBtn" class="btn btn-success" onclick="return Submitter()">Add Sheet Material</button>
												</div>
											</td>
										</tr>
									</tfoot>
								</form>
							</table>
						</div>
					</div>
				</div>
				<!-- /.panel --> 
			</div>
			</div>
		</div>
	</div>

	</div>
<!-- Update inventory -->
<!-- Display changes of inventory based on item selected; maybe new page? -->
		<div class="row">
			<div class="row">
				&nbsp;&nbsp;&nbsp;&nbsp;
			</div>
			<!-- /.row -->

			<div class="row">
				&nbsp;&nbsp;&nbsp;&nbsp;
			</div>
			<!-- /.row -->

			<div class="row">
				&nbsp;&nbsp;&nbsp;&nbsp;
			</div>
			<!-- /.row -->

			<div class="row">
				&nbsp;&nbsp;&nbsp;&nbsp;
			</div>
			<!-- /.row -->
		</div>
</div>

<!-- MODAL -->
<!-- TODO: fix so that line isn't in middle -->
<div id="update_modal" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method='POST'>
				<div class="modal-header" id='modal-header'>
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">Revoke Training</h4>
				</div>
				<div class='modal-body'>
					<table id='confirmation_table' class="table table-striped table-bordered table-responsive col-md-12">
					</table>
				</div>
				<div class="modal-footer" id='modal-footer'>
					  <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					  <button type="submit" id="modal_submit" class="btn btn-primary">All Data Is Correct</button>
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

	// insert [unit] and related product # into cells
	function update_unit(element) {
		var select_mat_data = element.options[element.selectedIndex].value.split('|');
		var great_grandparent = element.parentElement.parentElement.parentElement;
		great_grandparent.getElementsByClassName("unit")[0].innerHTML = select_mat_data[1] || "[ - ]";
		great_grandparent.getElementsByClassName("product_number")[0].innerHTML = select_mat_data[2] || "[unassigned]";
	}


	// add material row to inventory update table
	function additional_mat() {
		var loaded_mats = document.querySelectorAll(".update_rows").length;
		var table = document.getElementById("update_mat_table");
		var row = table.insertRow(-1);  // insert at bottom
		var cells = [row.insertCell(0), row.insertCell(1), row.insertCell(2), row.insertCell(3)];
		row.className = "update_rows";
		// duplicate material dropdown, replace select id with next increment
		cells[0].innerHTML = document.getElementsByClassName("td_select")[0].innerHTML;
		cells[1].innerHTML = 
			"<div class='input-group'>"+
				"<input type='number' min='0' class='form-control loc quantity' placeholder='1,000'/>"+
				"<span class='input-group-addon unit' ></span>"+
			"</div>";
		cells[2].innerHTML = document.getElementById("status").innerHTML;
		cells[3].innerHTML = document.getElementById("product").innerHTML;
	}


	// write number of fails and successes to modal once query sent
	// get string with instances delineated by '|' with first char success/fail; rest: name of attempt
	// return as format read by modal /populate modal(....)
	function outcome_sorter(str) {
		var outputs = str.split('|');
		var results = [];
		for(var x = 0; x < outputs.length; x++) {
			if(outputs[x].charAt(0) == 'S') var outcome = "Success";
			else { var outcome = "Failed"; }
			results.push([outputs[x].substring(1).replace(/_/g, " "), outcome]);
		}
		console.log(results);
		return results;
	}


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
	
	// populate modal with inventory change
	function update_inventory(){
		// get information of updated materials
		var input_rows = document.querySelectorAll(".update_rows");
		var materials = [];  // list to hold dict values for data from each row
		for(var x = 0; x < input_rows.length; x++) {
			var row = get_and_check_update_values(input_rows[x]);
			if(row == null) return;  // bad data: stop process from proceding to modal populations
			else if(row == "") continue;  // ignore blank row
			materials.push(row);
		}

		if(materials.length < 1) {
			alert("Enter at least 1 item");
			return;
		}

		// header for modal table
		var table_data = [["<b>Material</b>", "<b>Quantity</b>", "<b>Status</b>", "<b>Product Number</b>"]];
		// add rest of data
		for(var x = 0; x < materials.length; x++) {
			if(materials[x]["status_id"] == <?php echo $status["removed"]; ?>) materials[x]["quantity"] = 0 - materials[x]["quantity"];
			var data =  materials[x]["name"] + '|' + materials[x]["mat_id"] + '|' + materials[x]["quantity"] + '|' + 
						materials[x]["status_id"];
			var information = materials[x]["name"]+"<input name='row-dict-"+x+"' value='"+data+"' hidden/>";
			table_data.push([information, materials[x]["quantity"], materials[x]["status_text"], materials[x]["product"]]);
		}
		populate_modal("All Data Is Correct", "update_inventory", table_data, "Update Inventory");
	}


	// get info for each row, add it to dictionary, send dict to populate confirmation module
	function get_and_check_update_values(div) {
		var quantity = div.getElementsByClassName("quantity")[0].value;
		
		var mat = div.getElementsByClassName("dm_select")[0];
		var mat_id = mat.options[mat.selectedIndex].value.split('|')[0];
		var name = mat.options[mat.selectedIndex].text;
		var product = div.getElementsByClassName("product_number")[0].innerHTML;

		var status = div.getElementsByClassName("status_select")[0];
		var status_id = status.options[status.selectedIndex].value;
		var status_text = status.options[status.selectedIndex].text;			

		// check if all information is filled out or empty
		// product is not passed to backend, so no need to check
		if(quantity === "" && mat_id === "NONE" && reason === "" && status_id === "NONE") return "";
		else if(mat_id === "NONE") alert("Select a material or clear everything in row");
		else if(quantity === "") alert("Enter a quantity or clear everything in row");
		else if(parseFloat(quantity) > 99999.99) alert("The database does not accept a quantity larger than 99999.99");
		else if(status_id === "NONE") alert("Select a status or clear everything in row");
		else return {"quantity" : quantity, "mat_id" : mat_id, "name" : name,
			  "status_id" : status_id, "status_text" : status_text, "product" : product};
		// bad data
		return null;
	}


	function populate_modal(button_label, button_name, data, head) {
		var table = document.getElementById("confirmation_table");
		// clear modal
		document.getElementById("modal-header").innerHTML =
			"<button type='button' class='close' data-dismiss='modal'>&times;</button>"+
			"<h4 class='modal-title'>"+head+"</h4>";
		for(var x = table.getElementsByTagName("tr").length-1; x >= 0; x--) table.deleteRow(x);
		document.getElementById("modal-footer").innerHTML = 
			"<button type='button' id='cancel_button' class='btn btn-default' data-dismiss='modal'>Cancel</button>"+
			"<button type='submit' id='modal_submit' class='btn btn-primary' name='"+button_name+"'>"+button_label+"</button>";

		// update modal; first [0] row is header
		for(var x = 0; x < data.length; x++) {
			var row = table.insertRow(x);
			for(var y = 0; y < data[x].length; y++) {
				var cell = row.insertCell(y);
				cell.innerHTML = data[x][y];
			}
			cell.id = "cell-id-"+x;
		}
		$('#update_modal').modal('show');
	}


<?php 

// ------------------------------------ CREATE NEW MATERIAL ---------------------------------------

// further restrict new item creation to trainers
if($staff->getRoleID() >= $sv['minRoleTrainer']) { ?>

	// change the text of the button that collapses the section between current inventory and create new material
	function button_text(element) {
		if(element.innerHTML == "Create New Inventory Item") element.innerHTML = "Back to Update Current Inventory";
		else { element.innerHTML = "Create New Inventory Item"; }
	}

	function button_text1(element) {
		element.value = (element.value == "Add Sheet Good Material") ? "Hide Tool" : "Add Sheet Good Material";
	}
	
	function button_text2(element) {
		if(element.innerHTML == "Manage Sheet Good Inventory") element.innerHTML = "Hide";
		else { element.innerHTML = "Manage Sheet Good Inventory"; }
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
<?php } ?>

</script>