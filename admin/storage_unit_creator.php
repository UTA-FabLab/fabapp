<?php

/**********************************************************
*
*	storage_unit_creator.php
*
*	@author MPZinke on 04.28.19
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.92
*
*	-Admin page for editing the drawers on hand
*	-DESCRIPTION: show current drawers, create
*	 new drawers, edit/delete existing drawers
*	-FUTURE: When clicking on an invalid cell,
*	 prevent background from returning to original 
*	 color
*
**********************************************************/

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

// staff clearance
if (!$staff || $staff->getRoleID() < $sv['minRoleTrainer']){
	// Not Authorized to see this Page
	header('Location: /index.php');
	$_SESSION['error_msg'] = "Insufficient role level to access, You must be a Trainer.";
}


// access DB as user with storage_box delete permissions using following credentials 
$db_storage_box_host = "localhost";
$db_storage_box_user = "";  // populate
$db_storage_box_pass = "";  // populate
$dbdatabase = "fabapp";


// set up page to load drawer
if(filter_input(INPUT_GET, "drawer")) {
	$drawer_number = filter_input(INPUT_GET, "drawer");
	$unit_behavior = array("class" => "unit", "onclick" => "delete_unit(this)", "onmouseover" => "track(this)", "onmouseout" => "untrack(this)");
	$empty_behavior = array("class" => "free", "onclick" => "bound_partition(this, \"edit_partition_input\", \"free\")", 
								"onmouseover" => "track(this)", "onmouseout" => "untrack(this)");
	$Drawer = new StorageDrawer($drawer_number, $unit_behavior, $empty_behavior);  // array of unit objects
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["get_drawer"])) {
	$selected_drawer_number = filter_input(INPUT_POST, "drawer_number");
	header("Location:storage_unit_creator.php?drawer=$selected_drawer_number");
}
elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_drawer"])) {
	$selected_drawer_number = htmlspecialchars(filter_input(INPUT_POST, "drawer_number"));

	// connect to mysql database using user with storage_box delete permissions
	$storage_box_DB_user = new mysqli($db_storage_box_host, $db_storage_box_user, $db_storage_box_pass, $dbdatabase) or die(mysql_error());

	$errors = StorageDrawer::delete_drawer($selected_drawer_number);
	if(!$errors) $_SESSION['success_msg'] = "Successfully deleted drawer";
	else $_SESSION['error_msg'] = $errors;
	header("Location:storage_unit_creator.php");
}
elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_new_drawer"])) {
	$drawer = filter_input(INPUT_POST, "new_drawer_name_input");
	$unit = filter_input(INPUT_POST, "new_drawer_indicator_input");
	$size = filter_input(INPUT_POST, "new_drawer_row_input")."-".filter_input(INPUT_POST, "new_drawer_column_input");
	$start = explode(":", filter_input(INPUT_POST, "new_drawer_partition"))[0];
	$span = explode(":", filter_input(INPUT_POST, "new_drawer_partition"))[1];
	$type = filter_input(INPUT_POST, "new_drawer_type_input");
	if(!StorageUnit::create_new_partition($drawer, $unit, $start, $span, $type, $size)) 
		$_SESSION['error_msg'] = "Failed to create new unit";
	header("Location:storage_unit_creator.php?drawer=$drawer");
}
elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["copy_unit_button"])) {
	$new_drawer_number = filter_input(INPUT_POST, "copy_drawer_input");

	if(StorageDrawer::copy_drawer($new_drawer_number, $drawer_number)) {
		$_SESSION['success_msg'] = "Successfully copied drawer";
		header("Location:storage_unit_creator.php?drawer=$new_drawer_number");
	}
	else {
		$_SESSION['error_msg'] = "Failed to copy drawer";
		header("Location:storage_unit_creator.php?drawer=$drawer_number");
	}
}
elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["partition_unit"])) {
	$unit = filter_input(INPUT_POST, "partition_label");
	$start = explode(":", filter_input(INPUT_POST, "edit_partition_input"))[0];
	$span = explode(":", filter_input(INPUT_POST, "edit_partition_input"))[1];
	$type = filter_input(INPUT_POST, "partition_unit_type");

	if(!StorageUnit::create_new_partition($drawer_number, $unit, $start, $span, $type)) $_SESSION['error_msg'] = "Failed to partition unit";
	header("Location:storage_unit_creator.php?drawer=$drawer_number");
}
elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_unit_button"])) {
	$unit = htmlspecialchars(filter_input(INPUT_POST, "deletion_input"));

	// connect to mysql database using user with storage_box delete permissions
	$storage_box_DB_user = new mysqli($db_storage_box_host, $db_storage_box_user, $db_storage_box_pass, $dbdatabase) or die(mysql_error());

	$errors = StorageUnit::delete_unit($drawer_number, $unit);
	if(!$errors) $_SESSION['success_msg'] = "Successfully deleted unit";
	else $_SESSION['error_msg'] = $errors;
	// check — if deleting partition deleted drawer
	if(StorageDrawer::drawer_exists($drawer_number)) header("Location:storage_unit_creator.php?drawer=$drawer_number");
	else header("Location:storage_unit_creator.php");
}

?>


<title><?php echo $sv['site_name'];?> Storage Drawer Creator</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-md-12">
			<h1 class="page-header">Storage Drawer Creator</h1>
		</div>
	</div>
	<div class='col-md-12'>
		<div class="panel panel-default">
			<div class="panel-heading">
				<table width='100%'>
					<tr width="100%">
						<td>
							<i class="fas fa-inbox"></i> Drawer Selection
						</td>
						<td align=RIGHT>
							<button type="button" data-toggle='collapse'aria-expanded='false' 
							aria-controls='collapse' class='btn btn-default drawer_collapse_button' 
							onclick='data_collapse(this, "drawer_operations");'
							><?php echo (isset($Drawer) ? "Show Drawer Select" : "Hide Drawer Select") ?></button>
						</td>
					</tr>
				</table>
			</div>
			<!-- hide if a drawer is selected -->
			<div id='drawer_operations' class='collapse drawer_collapse <?php if(!isset($Drawer)) echo "in";?>'>
				<form method="POST">
					<table class='table'>
						<tr>
							<td class='col-md-2'>
								Drawer <?php echo ($sv['strg_drwr_indicator'] == "numer" ? "Number" : "Letter") ?> 
							</td>
							<td class='col-md-6'>
								<select name='drawer_number' class='form-control'>
								<?php
									foreach(StorageDrawer::get_unique_drawers() as $drawer_instance) {
										echo "<option value='$drawer_instance'>$drawer_instance</option>";
									} 
								?>
								</select>
							</td>
							<td class='col-md-2' align='center'>
								<button name='get_drawer' class='btn btn-default'>Select Drawer</button>
							</td>
							<td class='col-md-2' align='center'>
								<button name='delete_drawer' class='btn btn-danger'>Delete Drawer</button>
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>
		<br/>

	<!--------------------------------- NEW DRAWER --------------------------------->
		<div class="panel panel-default">
			<div class="panel-heading">
				<table width='100%'>
					<tr>
						<td>
							<i class="fas fa-plus"></i> New Drawer
						</td>
						<td align=RIGHT>
							<button type="button" data-toggle='collapse' aria-expanded='false' 
							aria-controls='collapse' class='btn btn-default drawer_collapse_button' onclick='data_collapse(this, "new_drawer_div");'
							>Show Drawer Creator</button>
						</td>
					</tr>
				</table>
			</div>
			<div id='new_drawer_div' class='collapse drawer_collapse'>
				<div>
					<form method='post'>
						<table width='100%'>
							<tr>
								<!-- name of new drawer -->
								<td>
									<div class="input-group" style='padding:8px;'>
										<span class="input-group-addon">Drawer</span>
										<input id='new_drawer_name_input' name='new_drawer_name_input' class='form-control' 
										onkeyup='change_drawer_display_name(this);' autocomplete='false' 
										<?php echo ($sv['strg_drwr_indicator'] == "numer" ? "type='number' max='999' min='0'" : "maxlength='3'");  ?>/>
									</div>
								</td>
								<!-- height of new drawer -->
								<td>
									<div class="input-group" style='padding:8px;'>
										<span class="input-group-addon">Height</span>
										<input id='new_drawer_row_input' name='new_drawer_row_input' class='form-control' onchange='new_drawer();'
										onkeyup='new_drawer();' type='number' min='1' max='50'/>  <!-- Don't get carried away there cowboy -->
									</div>
								</td>
								<!-- width of new drawer -->
								<td>
									<div class="input-group" style='padding:8px;'>
										<span class="input-group-addon">Width</span>
										<input id='new_drawer_column_input' name='new_drawer_column_input' class='form-control' 
										onkeyup='new_drawer();' type='number' min='1' max='50' onchange='new_drawer();'/>
									</div>
								</td>
								<!-- button to commence new drawer creation: enabled when name, height, width, and partition are filled -->
								<td>
									<button id='create_new_drawer' name='create_new_drawer' class='btn btn-primary' disabled>Create New Drawer</button>
								</td>
							</tr>
							<tr>
								<td>
									<div class="input-group" style='padding:8px;'>
										<span class="input-group-addon"><?php echo "Unit ".($sv['strg_drwr_indicator'] == "numer" ? "Letter" : "Number") ?></span>
										<input id='new_drawer_indicator_input' name='new_drawer_indicator_input' class='form-control'
										onkeyup='enable_new_drawer_button();' maxlength='3'/>
									</div>
								</td>
								<td>
									<div class="input-group" style='padding:8px;'>
										<span class="input-group-addon">Initial Unit Type</span>
										<input id='new_drawer_type_input' name='new_drawer_type_input' class='form-control'  list='new_unit_suggestions'
										onkeyup='enable_new_drawer_button();' onchange='enable_new_drawer_button();'>
										<datalist id='new_unit_suggestions'>
											<?php 
											if($results = $mysqli->query(" SELECT `type`
																			FROM `storage_box`
																			GROUP BY `type`;"
											)) {
												while($row = $results->fetch_assoc())
													echo "<option value='$row[type]'>";
											}
											?>
										</datalist>
									</div>
								</td>
							</tr>
						</table>
						<!-- store value of partition -->
						<input id='new_drawer_partition' name='new_drawer_partition' hidden/>
					</form>
				</div>
				<div align='center' style='padding:8px;'>
					<h3 id='drawer_name' class='col-md-12' align='center' style='padding-left:16px;'></h3>
					<!-- table to draw new drawer with JS -->
					<table id='new_drawer'>
						<tbody>
						</tbody>
					</table>
					<div width='100%' align='center' style='font-size:1.5em;'>
						<span style='text-decoration:overline'><strong>Drawer Front</strong></span>
					</div>
				</div>
			</div>
		</div>


	<!------------------------- EDIT CURRENT DRAWER -------------------------->
	<?php 
	if(isset($Drawer)) {
		if(!$Drawer) {  // if no data for drawer
			$_SESSION['error_msg'] = "Drawer '$drawer_number' Not Found";
			header("Location:./storage_unit_creator.php");
		}
		?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<table width='100%'>
					<tr width="100%">
						<td>
							<i class="fas fa-th"></i> Divisions
						</td>
						<td align=RIGHT>
							<button type="button" data-toggle='collapse' aria-expanded='false' 
							aria-controls='collapse' class='btn btn-default drawer_collapse_button' 
							onclick='data_collapse(this, "drawer_editor");'>Hide Drawer Editor</button>
						</td>
					</tr>
				</table>
			</div>
			<div id='drawer_editor' class='collapse in drawer_collapse'>
				<h3 class='col-md-12' align='center' style='padding-left:16px;'>Drawer <?php echo $drawer_number; ?></h3>
				<div align='center'>
					<?php 
						
						echo $Drawer->HTML_display(); 
					?>
					<form method="post">
						<table width='100%'>
							<tr style='padding:8px;'>
								<td style='padding:8px;width:35%;' align='center'>
									<button onclick='set_copying(this);' id='copy_toggle' 
									class='btn btn-default' type='button'>Copy Drawer</button>
								</td>
								<td style='padding:8px;width:30%;' align='center'> 
									<button id='partition_toggle' class='btn btn-default' type='button' onclick='set_partitioning(this)'>Partition Into Unit</button>
								</td>
								<td style='padding:8px;width:35%' align='center'> 
									<button id='delete_toggle' class='btn btn-default btn-warning' type='button' onclick='set_deleting(this);'>Delete Unit</button>
								</td>
							</tr>
						</table>
						<table width='100%'>
							<!-- copy drawer -->
							<tr id='copy_unit_div' hidden>
								<td class='col-md-9'>
									<div class="input-group" style='padding:8px;padding-left:12px;'>
										<span class="input-group-addon">New Drawer <?php echo ($sv['strg_drwr_indicator']=="numer" ? "Number" : "Character")?></span>
										<input id='copy_drawer_input' name='copy_drawer_input' class='form-control' name='copy_drawer_input' autocomplete='off'
										<?php echo ($sv['strg_drwr_indicator'] == "numer" ? "type='number' max='999' min='0'" : "maxlength='3'");?>
										onkeyup='enable_copy_button();' />
									</div>
								</td>
								<td class='col-md-4'>
									<button id='copy_unit_button' name='copy_unit_button' class='btn btn-info' disabled>Copy</button>
								</td>
							</tr>
							<!-- partition drawer -->
							<tr id='partition_unit_div' style='width:100%;' hidden>
								<td class='col-md-5' style='padding:8px;padding-left:16px;width:60%;' align='right'>
									<div class="input-group">
										<span class="input-group-addon">Unit <?php echo ($sv['strg_drwr_indicator']=="numer" ? "Character" : "Number")?></span>
										<input id='partition_label' name='partition_label' type='text' maxlength='3' class='form-control' autocomplete='off'
										onkeyup='enable_partition_button();' 
										<?php echo ($sv['strg_drwr_indicator'] == "numer" ? "type='number' max='999' min='0'" : "maxlength='3'");?>
										placeholder='<?php echo ($sv[strg_drwr_indicator]=="numer" ? "ABC" : "123")?>'/>
									</div>
								</td>
								<td class='col-md-4'>
									<div class="input-group" style='padding:8px;'>
										<span class="input-group-addon">Type</span>
										<input id='partition_unit_type' name='partition_unit_type' class='form-control' list='edit_unit_suggestions'
										onchange='enable_partition_button();' onkeyup='enable_partition_button();'>
										<datalist id='edit_unit_suggestions'>
											<?php 
												if($results = $mysqli->query(" SELECT `type`
																				FROM `storage_box`
																				GROUP BY `type`;"
												)) {
													while($row = $results->fetch_assoc())
														echo "<option value='$row[type]'>";
												}
											?>
										</datalist>
									</div>
								</td>
								<td class='col-md-3'>
									<button id='partition_unit' name='partition_unit' class='btn btn-info' disabled>Partition</button>
									<input id='edit_partition_input' name='edit_partition_input' value='' hidden/>
								</td>
							</tr>
							<!-- delete drawer -->
							<tr id='delete_unit_div' hidden>
								<td align='center' colspan='3' style='padding:8px;'>
									<button id='delete_unit_button' name='delete_unit_button' class='btn btn-danger'>Delete</button>
									<input id='deletion_input' name='deletion_input' value='' hidden/>
								</td>
							</tr>
						</table>
					</form>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			//IMPORTANT: this could be annoying if content is added after this section in the HTML
			window.scrollTo(0, document.body.clientHeight);
		</script>
	<?php } ?>
	</div>
</div>

<style rel="stylesheet">
	.free {
		background-color: #BBBBBB;
		border: solid;
		border-width: 2px;
		height: 50px;
		width: 50px;
	}
	.new {
		background: #00FF00;
		border: solid;
		border-width: 2px;
		height: 50px;
		width: 50px;
	}
	.unit {
		background: #00FF00;
		border: solid;
		border-width: 2px;
		height: 50px;
		width: 50px;
	}
</style>
<?php 
	include_once($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>

<script>
	// ———————————————— GLOBAL ———————————————— 
	var deleting = false;  // denote if deleting process is ongoing (trigger start/stop)
	var partitioning = false;  // denote if partition process is ongoing (trigger start/stop)
	var partition_start;  // pixel position of starting element
	var partition_end;  // pixel position of ending element


	// act as bool for drawer editing specific JS
	var drawer_editor = document.getElementById("drawer_editor");

	// ————————————— PAGE APPEARANCE ————————————— 

	// hide different sections based on adding bootstrap class /in; reset values of editing inputs
	function data_collapse(element, collapse_title) {
		reset_drawer_appearance();
		if(drawer_editor) reset_edit_values();  

		// show/hide parts
		var collapses = document.getElementsByClassName("drawer_collapse");
		for(var x = 0; x < collapses.length; x++) {
			if(collapses[x].id == collapse_title) {
				if(collapses[x].classList.contains("in")) collapses[x].classList.remove("in");  // hide div
				else collapses[x].classList.add("in");  // show div
			}
			else collapses[x].classList.remove("in");  // hide div
		}
		// change button label
		var buttons = document.getElementsByClassName("drawer_collapse_button");
		for(var x = 0; x < buttons.length; x++) {
			if(buttons[x] == element) 
				buttons[x].innerHTML = buttons[x].innerHTML.indexOf("Show") > -1 ? buttons[x].innerHTML.replace("Show", "Hide") : buttons[x].innerHTML.replace("Hide", "Show");
			else buttons[x].innerHTML = buttons[x].innerHTML.replace("Hide", "Show");
		}
	}


	// disable button if any of the current value is any of the preexisting values
	function prevent_duplicate_creation(button, input, label, preexisting) {
		if(any(preexisting, function(part, input) {return part.toLowerCase() == input.toLowerCase()}, input))
			button.innerHTML = "Already exists";
		else button.innerHTML = label;
	}


	/*
	reset values of different collapse sub divs when section collapses, different subdiv entered,
	or subdiv left (ie cancel clicked)
	*/
	function reset_drawer_appearance() {
		// reset new drawer
		document.getElementById("new_drawer_row_input").value = null;
		document.getElementById("new_drawer_column_input").value = null;
		document.getElementById("new_drawer").tBodies[0].innerHTML = "";

		if(drawer_editor) {
			// reset partition
			partitioning = false;
			document.getElementById("partition_unit_div").hidden = true;
			document.getElementById("partition_unit").disabled = true;
			document.getElementById("partition_toggle").innerHTML = "Partition Into Unit";

			// reset deleting
			deleting = false;
			document.getElementById("delete_unit_button").disabled = true;
			document.getElementById("delete_unit_div").hidden = true;
			var delete_toggle = document.getElementById("delete_toggle");
			delete_toggle.innerHTML = "Delete Unit";
			delete_toggle.classList.add("btn-warning");

			// reset copying
			$("#copy_unit_div").attr("hidden", true);
			document.getElementById("copy_unit_button").disabled = true;
			document.getElementById("copy_toggle").innerHTML = "Copy Unit";

			// reset colors
			var units = document.getElementsByClassName("unit");
			var free = document.getElementsByClassName("free");
			change_style([[units, [["background-color", "#00FF00"]]], [free, [["background-color", "#BBBBBB"]]]]);
		 }
	}


	// reset values on change to prevent partitioning from being falsely overridden (ie partitioning new drawer vs partition an existing one)
	function reset_edit_values() {
		document.getElementById("copy_drawer_input").value = null;  // copy
		document.getElementById("partition_label").value = null;  // partition label
		document.getElementById("edit_partition_input").value = null;  // parition start/span
		document.getElementById("deletion_input").value = null;  // delete

		// partition
		partition_start = partition_end = null;
		var partitions = document.querySelectorAll(".free, .new, .unit");
		for(var x = 0; x < partitions.length; x++) 
			partitions[x].style.backgroundColor = partitions[x].classList.contains("free") ? "#BBBBBB" : "#00FF00";
	}


	// --used to change color of cells when mouse hovers or leaves--
	// change color as mouse moves over cell
	function track(element) {
		// choose selects to include
		if(partitioning && !partition_end) {
			element.style.backgroundColor = element.classList.contains("free") ? "#0000FF" : "#FF0000";
			if(partition_start) highlight_partition(element, ".free, .unit", "free");
		}
		else if(deleting && element.classList.contains("unit")) element.style.backgroundColor = "#FFFF00";
	}


	// once mouse moves off of cell change color back
	function untrack(element) { 
		if(partitioning && !partition_start) {
			element.style.backgroundColor = element.classList.contains("free") ? "#00FF00" : "#333333";
		}
		else if(deleting && element.classList.contains("unit")) {
			element.style.backgroundColor = "#00FF00";
			// turn selected delete cell red
			if(element.id == document.getElementById("deletion_input").value) element.style.backgroundColor = "#FF0000";
		}
	}


	// ———————————————— NEW DRAWER ———————————————— 
	// ———————————————————————————————————————

	/* 
	create visual of the new drawer as data is entered
	constain minimum to be 1 
	*/
	function new_drawer() {
		var table = document.getElementById("new_drawer").tBodies[0];
		var row_count = document.getElementById("new_drawer_row_input").value || 1;
		var column_count = document.getElementById("new_drawer_column_input").value || 1;
		table.innerHTML = "";

		for(var x = 1; x <= row_count; x++) {
			table.insertRow(-1);
			for(var y = 1; y <= column_count; y++) {
				var cell = table.children[x-1].insertCell(-1);
				cell.classList.add("new")
				cell.id = `${x}-${y}`;
				cell.innerHTML = `${x}-${y}`;
				cell.style.textAlign = 'center';
				cell.onmouseover = function() {
					if(!partition_end) {
						this.style.backgroundColor = "#0000FF";
						if(partition_start) highlight_partition(this, ".new", "new");
					}
				};
				cell.onmouseout = function() {
					if(!partition_start) this.style.backgroundColor = "#00FF00";  // return to normal color
				};
				cell.onclick = function() {
					bound_partition(this, "new_drawer_partition", "new");
				};
			}
		}
	}


	// UI: change the title of the new drawer displayed when drawer "name" entered
	function change_drawer_display_name(element) {
		enable_new_drawer_button();
		document.getElementById("drawer_name").innerHTML = "Drawer "+element.value;
	}


	// enable the button to create a new drawer when rows, columns, name, and first box are specified
	function enable_new_drawer_button() {
		var drawer_input = document.getElementById("new_drawer_name_input").value;
		if(!drawer_input) return;

		$.ajax({
			url: "./sub/storage_ajax_requests_admin.php",
			type: "POST",
			dataType: "json",
			data: {"drawer_indicator_is_valid" : true, "drawer" : drawer_input},
			success: function(response) {
				var button = document.getElementById("create_new_drawer");
				if(!response["is_valid"]) button.innerHTML = "Already exists";
				else button.innerHTML = "Create New Drawer";

				if(drawer_input && document.getElementById("new_drawer_row_input").value && document.getElementById("new_drawer_type_input").value
				&& document.getElementById("new_drawer_column_input").value && document.getElementById("new_drawer_partition").value
				&& document.getElementById("new_drawer_indicator_input").value && response["is_valid"])
					button.disabled = false;
				else button.disabled = true;
			}
		});
	}


	// ——————————— DRAWER EDITING CELL ACTIONS ————————————
	// ———————————————————————————————————————


	// ———————————————— COPYING ———————————————— 
	// create a new drawer based on the currently selected drawer with specified label

	// enable the button once a new drawer label has been input
	function enable_copy_button() {
		var drawer_input = document.getElementById("copy_drawer_input").value;
		if(!drawer_input) return;

		$.ajax({
			url: "./sub/storage_ajax_requests_admin.php",
			type: "POST",
			dataType: "json",
			data: {"drawer_indicator_is_valid" : true, "drawer" : drawer_input},
			success: function(response) {
				var button = document.getElementById("copy_unit_button");
				if(!response["is_valid"]) button.innerHTML = "Already exists";
				else button.innerHTML = "Copy";

				// en/disable button
				if(drawer_input && response["is_valid"]) button.disabled = false;
				else button.disabled = true;
			}
		});
	}


	// setup and show the copy div; clear other data
	function set_copying(element) {
		var activated = element.innerHTML == "Cancel";
		reset_drawer_appearance();
		reset_edit_values();

		element.innerHTML = activated ? "Copy Unit" : "Cancel";
		$("#copy_unit_div").attr("hidden", activated);
	}


	// ———————————————— DELETING ———————————————— 
	// delete a unit of the selected drawer

	// called on unit selection to add value to /deletion_input for php to get from
	function delete_unit(element) {
		// reset delete colors
		var units = document.getElementsByClassName("unit");
		var free = document.getElementsByClassName("free");
		change_style([[units, [["background-color", "#00FF00"]]], [free, [["background-color", "#BBBBBB"]]]]);

		if(deleting) {
			document.getElementById("deletion_input").value = element.id;
			element.style.backgroundColor = "#FF0000";  // turn selected delete red
			enable_delete_button();
		}
	}


	// enable button once a cell has been selected to delete
	function enable_delete_button() {
		if(document.getElementById("deletion_input").value) document.getElementById("delete_unit_button").disabled = false;
	}


	/*
	start deleting functionality by changing color 
	& changing switch to allow for highlighting/selecting
	*/
	function set_deleting(element) {
		var activated = element.innerHTML == "Cancel";  // activated by last click
		reset_drawer_appearance();
		reset_edit_values();
		deleting = !activated;

		if(deleting) {
			element.innerHTML = "Cancel";

			var units = document.getElementsByClassName("unit");
			var free = document.getElementsByClassName("free");
			var delete_cell = document.getElementById("delete_unit_div");

			element.classList.remove("btn-warning");
			change_style([[units, [["background-color", "#00FF00"]]], [free, [["background-color", "#333333"]]]]);
			delete_cell.hidden = false;
		}
	}


	// ——————————————— PARTITIONING ———————————————
	// create a partition by selecting (drag area) of free boxes

	// when a cell is clicked, start||stop partitioning of unit
	function bound_partition(element, partition_input, selectable_class) {
		var element_position = get_position(element);
		if(element.classList.contains("free") && !partitioning) return;  // if not partitioning && not a part of a new drawer do nothing

		// if partition already set, reset partition, start partitioning
		if(partition_end) {
			partition_start = partition_end = null;
			change_style([[document.getElementsByClassName("free"), [["background-color", "#00FF00"]]],
							[document.getElementsByClassName("unit"), [["background-color", "#333333"]]],
							[document.getElementsByClassName("new"), [["background-color", "#00FF00"]]]]);
			element.style.backgroundColor = element.classList.contains(selectable_class) ? "#0000FF" : "#FF0000";
		}

		// if no partition set, start partitioning || if point behind start, set this point to new start
		if(!partition_start || (partition_start && (get_position(partition_start, 'X') > element_position['X'] || 
		get_position(partition_start, 'Y') > element_position['Y'])))
			partition_start = element;
		// finish ongoing partition, unless occupied cells are included
		else if(partition_only_contains_valid_chunks(element)) {
			partition_end = element;
			document.getElementById(partition_input).value = grid_start_and_span(partition_start, partition_end);
			if(partition_input == "new_drawer_partition") enable_new_drawer_button();
			else if(partition_input == "edit_partition_input") enable_partition_button();
		}
	}


	// enable button if partition is labeled and partition is assigned
	function enable_partition_button() {
		var unit_input = document.getElementById("partition_label").value;
		if(!unit_input) return;
		var drawer = window.location.search.substr(1).split("=")[1];  // get from URL

		$.ajax({
			url: "./sub/storage_ajax_requests_admin.php",
			type: "POST",
			dataType: "json",
			data: {"unit_indicator_is_valid" : true, "drawer" : drawer, "unit" : unit_input},
			success: function(response) {
				var button = document.getElementById("partition_unit");
				if(!response["is_valid"]) button.innerHTML = "Already exists";
				else button.innerHTML = "Copy";

				// en/disable button
				if(unit_input && document.getElementById("partition_unit_type").value 
				&& document.getElementById("edit_partition_input").value && response["is_valid"])
					button.disabled = false;
				else button.disabled = true;
			}
		});
	}


	// color units within draw path; if valid, highlight blue, if any not valid, highlight red
	function highlight_partition(element, classes_to_select_from, selectable_class) {
		var selects = document.querySelectorAll(classes_to_select_from);
		var selected = [];
		for(var x = 0; x  < selects.length; x++) {
			if(select_is_in_drag_area(partition_start, selects[x], element))
				selected.push(selects[x]);
			// reset non-used elements
			else selects[x].style.backgroundColor = selects[x].classList.contains(selectable_class) ? "#00FF00" : "#333333";
		}
		// --turn red if any boxes are not partitionable
		if(any(selected, function(part, value) {return part.classList.contains(value);}, "unit"))
			change_style([[selected, [["background-color", "#FF0000"]]]]);
		else 
			change_style([[selected, [["background-color", "#2222FF"]]]]);
	}


	// check if any of the cells in the drag area are occupied
	function partition_only_contains_valid_chunks(element) {
		var selects = document.querySelectorAll(".free, .new, .unit");
		var selected = [];
		for(var x = 0; x  < selects.length; x++) {
			if(select_is_in_drag_area(partition_start, selects[x], element))
				selected.push(selects[x]);
		}
		return !any(selected, function(part, value) {return part.classList.contains(value);}, "unit");
	}


	// return if the top & left edges are between the beginning cell and the mouse location
	function select_is_in_drag_area(lower_element, temp_element, upper_element) {
		var lower_bound = get_position(lower_element);
		var temp_bound = get_position(temp_element);
		var upper_bound = get_position(upper_element);
		return lower_bound['X'] <= temp_bound['X'] && temp_bound['X'] <= upper_bound['X'] 
				&& lower_bound['Y'] <= temp_bound['Y'] && temp_bound['Y'] <= upper_bound['Y']
	}


	// called when opening to partitioning: reset delete, prepare grid to partitioning
	function set_partitioning(element) {
		var activated = element.innerHTML == "Cancel";  // activated by previous click
		reset_drawer_appearance();
		reset_edit_values();
		partitioning = !activated;
		
		if(partitioning) {
			var units = document.getElementsByClassName("unit");
			var free = document.getElementsByClassName("free");

			change_style([[free, [["background-color", "#00FF00"]]], [units, [["background-color", "#333333"]]]]);
			element.innerHTML = "Cancel";
			document.getElementById("partition_unit_div").hidden = false;
		}
	}


	// —————————————————— UTILITY —————————————————
	// ———————————————————————————————————————

	// if any of the items is relevant to the /usage function (eg contains /value), return true
	function any(list, usage, value) {
		for(var x = 0; x < list.length; x++)
			if(usage(list[x], value)) return true;
		return false;
	}

	// take in list of things to change
	// each sublist contains list of elements to change, followed by list of attribute, value pairs
	function change_style(list) {
		for(var x = 0; x < list.length; x++) {  // [free, [["background-color", "#BBBBBB"]] sublist
			for(var selection = 0; selection < list[x][0].length; selection++) {  // list of elements to changes
				for(var change = 0; change < list[x][1].length; change++)  // [["background-color", "#BBBBBB"]] list of attribute, value pairs
					list[x][0][selection].style[list[x][1][change][0]] = list[x][1][change][1];
			}
		}
	}


	/*
	return either x,y coordinates of passed cell
	optional selection of x-axis or y-axis; default both
	*/
	function get_position(element, axis=false) {
		var rect = element.getBoundingClientRect();
		if(!axis) return {'X' : rect.left, 'Y' : rect.top};
		if(axis == "X") return rect.left;
		if(axis == "Y") return rect.top;
	}


	/*
	calculate the span and return x1-y1:xspan-yspan
	does not account for negative span, but is accounted for in start assignment
	if no end element selected (should not be the case), end = start
	*/
	function grid_start_and_span(start, end) {
		start_coordinates = start.id.split("-");
		if(!end) end = start;
		end_coordinates = end.id.split("-");
		var x = parseInt(end_coordinates[0]) - parseInt(start_coordinates[0]);
		var y = parseInt(end_coordinates[1]) - parseInt(start_coordinates[1]);
		return start.id + ":" + [x,y].join("-");
	}

</script>