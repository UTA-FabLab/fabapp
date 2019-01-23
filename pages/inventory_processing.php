<?php 

/* created by: MPZinke on 12.8.18 */

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



$device_mats = Materials::getDeviceMats();

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_inventory'])) {

	$outcomes = array();
	$values = get_populated_values("row-dict");
	foreach($values as $val) {
		if(!Materials::mat_exists($val[1])) continue;  // ignore unknown materials
		// function regexs all inputs; S for success, F for failed
		if(Mats_Used::update_mat_quantity($val[1], floatval($val[2]), $val[3], $staff, $val[4])) $outcomes[] = "S".str_replace(' ', '_', $val[0]);
		else $outcomes[] = "F".str_replace(' ', '_', $val[0]);
	}
	header("Location: inventory_processing.php?outcome=".implode('|', $outcomes));
}
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_mat'])) {
	// check inputs
	if(!$name = Materials::regexName(filter_input(INPUT_POST, "item_name"))) $failure = "Name too long for creating new material";

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
	if($prior_id && Materials::update_mat($color, $prior_id, $measurability, $price, $unit)) {
		$outcome = "S".str_replace(' ', '_', $name);  // S for success, F for failed
		foreach($device_group as $dg) {
			$prior_dgs = Materials::get_device_mat($prior_id);  // called each time to prevent double submission in 1 attempt
			if(in_array($dg, $prior_dgs)) continue;  // don't create duplicate
			if(Materials::assign_device_group($dg, $m_id)) {
				$outcome .= "|SDevice:_".$dg;
			}
			else {
				$outcome .= "|FDevice:_".$dg;	
			}
		}
	}
	// material doesn't exist
	//TODO: doesn't send back good ID number
	elseif(!$prior_id && Materials::create_new_mat($color, $measurability, $name, $price, $unit)) {
		$new_mat_id = Materials::mat_exists($name);
		$outcome = "S".str_replace(' ', '_', $name);
		foreach($device_group as $dg) {
			$prior_dgs = Materials::get_device_mat($prior_id);
			if(in_array($dg, $prior_dgs)) continue;
			if(Materials::assign_device_group($dg, $new_mat_id)) {
				$outcome .= "|SDevice:_".$dg;
			}
			else {
				$outcome .= "|FDevice:_".$dg;	
			}
		}
	}
	else {  // update or create material failed
		$outcome .= "F".str_replace(' ', '_', $name);
	}
	header("Location:inventory_processing.php?outcome=".$outcome);
}


// get values for possible instances, return values in array
function get_populated_values($tag_name) {
	$count = 0;
	$values = array();
	while(true) {
		$data = filter_input(INPUT_POST, $tag_name."-".$count);
		if(!$data || $count > 100) return $values;  // 100 to failsafe
		
		if($instances = substr_count($data, '|')) $values[] = explode('|', $data, $instances+1);
		else $values[] = $data;
		$count++;
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

	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fas fa-shipping-fast"></i> Update For Newly Delivered Inventory
			</div>
			<!-- /.panel-heading -->
			<div class='collapse in inventory_collapse' id='collapse'>
				<div class="panel-body">
					<table class="table-striped table-bordered table-responsive col-md-12" id="update_mat_table">
						<thead>
							<th class='col-md-2' style="text-align:center">Material</th>
							<th class='col-md-2' style="text-align:center">Change In Quantity</th>
							<th class='col-md-2' style="text-align:center">Status</th>
							<th class='col-md-4' style="text-align:center">Reason</th>
						</thead>
						<tr class='update_rows'>
							<td class="td_select">
								<select class="form-control dm_select" onchange='update_unit(this)'>
									<option selected='selected' value="NONE">Select Material</option>
									<?php foreach($device_mats as $dm){
										// options have two values: m_id, unit
										echo ("<option value='".$dm->getM_id()."|".$dm->getUnit()."' id='".$dm->getUnit()."' >".$dm->getM_name()."</option>");
									}?>
								</select>
							</td>
							<td>
								<div class="input-group">
									<input type="number" min="0" class="form-control loc quantity" placeholder="1,000" max='9999999.99'/>
									<span class="input-group-addon unit"></span>
								</div>
							</td>
							<td id='status'>
								<select class="form-control status_select">
									<option selected='selected' value="NONE">Select Status</option>
									<?php  if($autofills = $mysqli->query("
										SELECT * 
										FROM `status`
									")) {
										while($row = $autofills->fetch_assoc()) {
											echo "<option value='$row[status_id]'>$row[msg]</option>";
										}
									}?>
								</select>
							</td>
							<td id='reason'>
								<input type='text' class='form-control reason' placeholder='Reason'/>
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

	<!-- Add new item to inventory -->
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
	</div>
<!-- Update inventory -->
<!-- Display changes of inventory based on item selected; maybe new page? -->
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
	function button_text(element) {
		if(element.innerHTML == "Create New Inventory Item") element.innerHTML = "Back to Update Current Inventory";
		else { element.innerHTML = "Create New Inventory Item"; }
	}


	// insert [unit] into span
	function update_unit(element) {
		var select = element.options[element.selectedIndex].value.split('|');
		if(select[1] === "") select[1] = "[ - ]";
		else if(select[1] === undefined) select[1] = "";
		element.parentElement.parentElement.getElementsByClassName("unit")[0].innerHTML = select[1];
	}



	function additional_mat(){
		var loaded_mats = document.querySelectorAll(".update_rows").length;
		var table = document.getElementById("update_mat_table");
		var row = table.insertRow(-1);
		var cell1 = row.insertCell(0);
		var cell2 = row.insertCell(1);
		var cell3 = row.insertCell(2);
		var cell4 = row.insertCell(3);
		row.className = "update_rows";
		// duplicate material dropdown, replace select id with next increment
		cell1.innerHTML = document.getElementsByClassName("td_select")[0].innerHTML;
		cell2.innerHTML = 
			"<div class='input-group'>"+
				"<input type='number' min='0' class='form-control loc quantity' placeholder='1,000'/>"+
				"<span class='input-group-addon unit' ></span>"+
			"</div>";
		cell3.innerHTML = document.getElementById("status").innerHTML;
		cell4.innerHTML =
			"<td id='reason'>"+ 
				"<input type='text' class='form-control reason' placeholder='Reason'/>"+ 
			"</td>";
	}


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



// --------------------- DATA & MODAL ---------------------

	// populate modal with inventory
	function update_inventory(){
		// get information of updated materials
		var input_info = document.querySelectorAll(".update_rows");
		var materials = [];
		for(var x = 0; x < input_info.length; x++) {
			var quantity = input_info[x].getElementsByClassName("quantity")[0].value;
			var reason = input_info[x].getElementsByClassName("reason")[0].value.replace(/\|/g, ";");
			
			var mat = input_info[x].getElementsByClassName("dm_select")[0];
			var mat_id = mat.options[mat.selectedIndex].value.split('|')[0];
			var name = mat.options[mat.selectedIndex].text;

			var status = input_info[x].getElementsByClassName("status_select")[0];
			var status_id = status.options[status.selectedIndex].value;
			var status_text = status.options[status.selectedIndex].text;			

			// check if all information is filled out or empty
			if(quantity === "" && mat_id === "NONE" && reason === "" && status_id === "NONE") continue;
			else if(mat_id === "NONE") alert("Select a material or clear everything in row");
			else if(quantity === "") alert("Enter a quantity or clear everything in row");
			else if(parseFloat(quantity) > 99999.99) alert("The database does not accept a quantity larger than 99999.99");
			else if(status_id === "NONE") alert("Select a status or clear everything in row");
			else if(reason.length < 5) alert("Enter a reason or clear everything in row");
			else {
				materials.push({"quantity" : quantity, "mat_id" : mat_id, "reason" : reason, "name" : name,
				  "status_id" : status_id, "status_text" : status_text});
				continue;
			}
			return;
		}

		if(materials.length < 1) {
			alert("Enter at least 1 item");
			return;
		}

		var table_data = [["<b>Material</b>", "<b>Quantity</b>", "<b>Status</b>", "<b>Reason</b>"]];
		for(var x = 0; x < materials.length; x++) {
			if(materials[x]["status_id"] == "9") materials[x]["quantity"] = 0 - materials[x]["quantity"];
			var data =  materials[x]["name"] + '|' + materials[x]["mat_id"] + '|' + materials[x]["quantity"] + '|' + 
						materials[x]["reason"] + '|' + materials[x]["status_id"];
			var information = materials[x]["name"]+"<input name='row-dict-"+x+"' value='"+data+"' hidden/>";
			table_data.push([information, materials[x]["quantity"], materials[x]["status_text"], materials[x]["reason"]]);
		}
		populate_modal("All Data Is Correct", "update_inventory", table_data, "Update Inventory");
	}


	function populate_modal(button_label, button_name, data, head) {
		var table = document.getElementById("confirmation_table");
		
		// clear modal
		document.getElementById("modal-header").innerHTML =
			"<button type='button' class='close' data-dismiss='modal'>&times;</button>"+
			"<h4 class='modal-title'>"+head+"</h4>";
		for(var x = table.getElementsByTagName("tr").length-1; x >= 0; x--) {
			table.deleteRow(x);
		}
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

// further restrict new item creation to trainers
if($staff->getRoleID() >= $sv['minRoleTrainer']) { 

	// add names and ids to check if name already exists
	if($result = $mysqli->query("
		SELECT `m_name`
		FROM `materials`;"
	)) {
		echo "	var preexisting_mats = assign_mat_names();
			function assign_mat_names() {
				var temp = [];";
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			echo "temp.push('$row[m_name]'.toLowerCase());";
		}
		echo "return temp;}";
	} ?>


	function additional_device_groups() {
		var related_dg = document.querySelectorAll(".device_group").length;
		var table = document.getElementById("new_item_table");
		var row = table.insertRow(-1);
		var innerdata = document.getElementById("dg_row").innerHTML;
		row.innerHTML = innerdata.substr(0,91) + related_dg + innerdata.substr(92);
	}


	// ugly; should redress
	function name_already_used(name) {
		for(var x = 0; x < preexisting_mats.length; x++) {
			if(preexisting_mats[x] == name) {
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
		var titles = ["Item Name", "Measurable", "Price", "Unit", "Color Hex"];
		var ids = ["new_item_name", "new_item_measurability", "new_item_price", "new_item_unit"];
		if(include_color) ids.push("new_item_color");  // otherwise color is not selected

		// require fields populated
		if(document.getElementById(ids[0]).value == "" || document.getElementById(ids[1]).value == "") {
			alert(titles[x] + " requires a value");
			return;
		}

		var table_data = [["<b>Label</b>", "<b>Value</b>"]];
		for(var x = 0; x < ids.length; x++) {
			var value = document.getElementById(ids[x]).value.replace(/^\s+|\s+$/g, '');
			if(!value) continue;

			var information = "<input name='"+ids[x].substr(4)+"' value='"+value+"' hidden/>" + value;
			table_data.push([titles[x], information]);
		}

		// device groups: front end allows for double submission; back end doesn't
		var dg_instances = document.querySelectorAll(".device_group");
		var populated_dg_count = 0;
		for(var x = 0; x < dg_instances.length; x++) {
			var value = dg_instances[x].options[dg_instances[x].selectedIndex].value;
			if(!value) continue;
			
			var text = dg_instances[x].options[dg_instances[x].selectedIndex].text;
			var information = "<input name='item_device_group-"+populated_dg_count+"' value='"+value+"' hidden/>" + text;
			table_data.push(["Device Group", information]);
			populated_dg_count++;
		}

		populate_modal("All Data Is Correct", "new_mat", table_data, "create_new_item");

		// display warning if name is already chosen (case insensitive, remove white space)
		var name = document.getElementById("new_item_name").value;
		name_already_used(name.toLowerCase().replace(/^\s+|\s+$/g, ''));
	}



	// ------------------- COLOR PICKING -------------------

	var include_color = false;  // bool to determine if to include color

	var previous_value = "rgb(80,0,0)";
	var color_field = document.getElementById("rgb_input");
	color_field.value = previous_value;
	color_field.addEventListener('keyup', function(evt){
		// no "rgb(", ")", values > 255
		var rgb = this.value.substring(4, this.value.length-1).split(',');
		if(this.value.length < 8) {
			this.value = "rgb(80,0,0)";
		}
		else if(this.value.match(/,/g).length != 2 || this.value.substring(0,4) != "rgb(" || this.value.charAt(this.value.length-1) != ')' ||
		  parseInt(rgb[0]) > 255 || parseInt(rgb[1]) > 255 || parseInt(rgb[2]) > 255) {
			this.value = previous_value;
		}
		else {
			previous_value = this.value;
		}
	}, false);



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


	function color_submitOnEnter(e) {
		keyboardKey = e.which || e.keyCode;
		if (keyboardKey == 13) {
			color_setFullColor();
		}
	}


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