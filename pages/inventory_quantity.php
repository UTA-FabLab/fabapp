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
if (!$staff || $staff->getRoleID() < $sv['LvlOfLead']){
	// Not Authorized to see this Page
	header('Location: /index.php');
	$_SESSION['error_msg'] = "Insufficient role level to access, You must be a Lead.";
}


// -------------------------------------- PAGE FUNCTIONALITY --------------------------------------
$device_mats = Materials::getDeviceMats();

// sheet goods
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['__SHEETGOOD__quantity_button']))
{
	if(!isset($_POST['__SHEETGOOD__varient_sizes_select']))
		exit_from_error("Incorrect input on Sheet Good selection field");
	if(!preg_match('/^[\s\S]{9,999}$/', $_POST['__SHEETGOOD__quantity_notes']))
		exit_from_error("Incorrect input on Sheet Good notes field: input is too short");
	if(!preg_match('/^[0-9]+$/i', $_POST['__SHEETGOOD__quantity_change']))
		exit_from_error("Incorrect input on Change in Quantity field");


	$sheet_id = filter_input(INPUT_POST, "__SHEETGOOD__varient_sizes_select");
	$quantity_notes = filter_input(INPUT_POST, "__SHEETGOOD__quantity_notes");
	$quantity_change = filter_input(INPUT_POST,"__SHEETGOOD__quantity_change");
	exit_from_error(Materials::update_sheet_quantity($sheet_id, $quantity_change, $quantity_notes));
	exit_with_success("Successfully updated material quantity");
}



function exit_from_error($error_message)
{
	if(!$error_message) return;

	$_SESSION["error_msg"] = $error_message;
	header("Location:./inventory_quantity.php");
	exit();
}


function exit_with_success($success_message)
{
	$_SESSION["success_msg"] = $success_message;
	header("Location:./inventory_quantity.php");
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
		if($failure_message)
		{
			?>
			<div class='col-md-12'>
				<div class='alert alert-danger'>
					<?php echo $failure_message; ?>
				</div>
			</div>
			<?php
		} 
	?>
	
	<!-- Update inventory -->
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading" style="background-color: #B5E6E6;">
				<i class="fas fa-warehouse"></i> Update Inventory Quantity
			</div>
			<!-- /.panel-heading -->
			<div class="panel-body">
				<div class="table">
					<ul class="nav nav-tabs">
						<li class="active">
							<a data-toggle="tab" aria-expanded="false" href="#1">Update Inventory</a>
						</li>
						<li >
							<a data-toggle="tab" aria-expanded="false" href="#2">Update Sheet Goods</a>
						</li>
					</ul>

<!--—————————————— UPDATE INVENTORY ——————————————-->
					<div class="tab-content">
						<div id="1" class="tab-pane fade in active">
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
													<select class="form-control dm_select" onchange='__INVENTORY__update_unit(this)'>
														<option selected='selected' value="NONE" disabled hidden>Select Material</option>
														<?php
															foreach($device_mats as $dm)
																// options have three values: m_id, unit, product number
																echo ("<option value='$dm->m_id|$dm->unit|$dm->m_prod_number'
																		id='$dm->unit' >$dm->m_name</option>");
														?>
													</select>
												</div>
											</td>
											<td id='product'>
												<div class='product_number' style='text-align:center;'>
												</div>
											</td>
											<td id='status'>
												<div>
													<select class="form-control status_select" 
													onchange='__INVENTORY__add_negative_sign_quantity_addon_if_reduction(this);'>
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
													<input type="number" min="0" class="form-control quantity" 
													placeholder="1,000" max='9999999.99'/>
													<span class="input-group-addon unit"></span>
												</div>
											</td>
											<td id='reason'>
												<input type='text' class='form-control reason' placeholder='Reason'/>
											</td>
											<td style='text-align:center;margin:auto;'>
												<button class='btn' style='width:100%' onclick='__INVENTORY__delete_row(this);'>
													&times;
												</button>
											</td>
										</tr>
									</table>
									<button class="btn btn-info pull-right" onclick="__INVENTORY__additional_material()">
										Additional Material Updates
									</button>
								</div>
								<div class="panel-footer">
									<div class="clearfix">
										<button class="btn pull-right btn-success" name="to_confirmation" 
										onclick="__INVENTORY__compile_and_populate_modal();">Submit</button>
									</div>
								</div>
							</div>
						</div>

<!--——————————————— EDIT SHEET GOOD AMOUNT ———————————————-->
						<div id="2" class="tab-pane fade">
							<div class="panel panel-default">
								<div class="panel-body">
									<table class="table table-bordered table-striped table-hover">
										<form method="POST" action="" autocomplete='off'>
											<tr>
												<td class='col-md-3'>
													<b data-toggle="tooltip" data-placement="top" title="Select type of material">
														Sheet Parent Group
													</b>
												</td>
												<td class="col-md-8">
														<select class="form-control" name="__SHEETGOOD__sheet_parent_select" 
														id="__SHEETGOOD__sheet_parent_select" 
														onchange="__SHEETGOOD__get_variants();" tabindex="1">
															<option value="" disabled selected hidden>Sheet Child</option>
															<?php
																$result = $mysqli->query(
																	"SELECT `m_id`, `m_name`
																	FROM `materials`
																	WHERE `m_parent` = $sv[sheet_goods_parent]
																	GROUP BY `m_id`;");

																if(!$result)
																	echo "<option> disabled selected hidden>QUERY ERROR</option>";
																else
																{
																	echo "<option disabled hidden selected value=''>Sheet Parent</option>";
																	while($row = $result->fetch_assoc())
																		echo "<option value='$row[m_id]'>$row[m_name]</option>";
																}
															?>
														</select>
													</div>
												</td>
											</tr>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top" title="Select which child of material group to use">
														Sheet Variant
													</b>
												</td>
												<td class="col-md-8">
													<select class="form-control" name="__SHEETGOOD__sheet_variant_select" 
													id="__SHEETGOOD__sheet_variant_select" 
													onchange="__SHEETGOOD__get_sizes();">
														<option selected hidden disabled>Select Parent First</option>
													</select> 
												</td>
											</tr>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top" title="Select which size of the type of material">
														Sheet Size
													</b>
												</td>
												<td class="col-md-8">
													<select id='__SHEETGOOD__varient_sizes_select' name='__SHEETGOOD__varient_sizes_select' 
													class='form-control'>
														<option selected hidden disabled>Select Variant First</option>
													</select>
												</td>
											</tr>
											<tr>
												<td>
													<b data-toggle="tooltip" data-placement="top">Change in Quantity</b>
												</td>
												<td>
													<input type="number" class="form-control"name="__SHEETGOOD__quantity_change" 
													id="__SHEETGOOD__quantity_change" 
													max="250" min="-250" value="1" step="1" placeholder="Enter Quantity" />
												</td>
											</tr>
											<tr>
												<td>
													<b>Notes</b>
												</td>
												<td>
													<textarea rows="4" cols="50" type="text" name="__SHEETGOOD__quantity_notes" 
													id="__SHEETGOOD__quantity_notes" 
													class="form-control" placeholder="Enter notes regarding quantity change"></textarea>
												</td>
											</tr>

											<tfoot>
												<tr>
													<td colspan="2">
														<div class="pull-right">
															<button type="submit" name="__SHEETGOOD__quantity_button" class="btn btn-success" 
															onclick="return __SHEETGOOD__submit_confirmation()">
																Update Quantity
															</button>
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


<div id="__INVENTORY__modal" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header" id='modal-header'>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Update Inventory</h4>
			</div>
			<div id='__INVENTORY__modal_body' class='modal-body'>
				<!-- populated with inventory attributes -->
			</div>
			<div id='__INVENTORY__modal_footer' class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button id='__INVENTORY__submit_button' type='button' class='btn btn-success'>
					Update Inventory
				</button>
			</div>
		</div>  <!-- <div class="modal-content"> -->
	</div>  <!-- <div class="modal-dialog"> -->
</div>  <!-- <div id="__INVENTORY__modal" class="modal fade"> -->



<?php
	include_once("$_SERVER[DOCUMENT_ROOT]/pages/footer.php");
?>




<script>

	// ———————————————————— INVENTORY ————————————————–—————
	// ———————————————————————————————————————————————

	// ———————————————————— PAGE MANIP —————————————————————

	// when a status selected, show symbolic negative sign (backend automatically flips value).
	// takes the select that selected the status.
	// find parent row. navigate to amount input. if reducing status, add neg. sign, else remove one.
	function __INVENTORY__add_negative_sign_quantity_addon_if_reduction(element)
	{
		var ancestor = element.parentElement.parentElement.parentElement;
		var input_group = ancestor.children[3].children[0];  // navigate to quantity input

		// reduction
		if((element.value == <?php echo $status["removed"]; ?> ||
		element.value == <?php echo $status["failed_mat"] ?> ||
		element.value == <?php echo $status["used"]; ?>) && 
		!input_group.getElementsByClassName("negative").length)
		{
			var negative_span = document.createElement("span");
			negative_span.classList.add("negative", "input-group-addon");
			negative_span.innerHTML = "–";
			input_group.insertBefore(negative_span, input_group.firstChild);
		}

		// addition and has negative sign
		else if(element.value != <?php echo $status["removed"]; ?>
		&& element.value != <?php echo $status["failed_mat"] ?>
		&& element.value != <?php echo $status["used"]; ?>
		&& input_group.getElementsByClassName("negative").length)
			input_group.children[0].remove();
	}


	// add material row to inventory update table.
	// take optional values to put in materials and statuses.
	// find table to add to. get materials and statuses if not provided. create new row from template. add new 
	// row to table.
	function __INVENTORY__additional_material(material_options=null, material_statuses=null)
	{
		if(!material_options)
		{
			var material_options =	 document.getElementsByClassName("update_rows")[0]
											.getElementsByClassName("dm_select")[0].innerHTML;
		}
		if(!material_statuses)
		{
			var material_statuses = document.getElementsByClassName("update_rows")[0]
											.getElementsByClassName("status_select")[0].innerHTML;
		}
		var update_data = 	`<td class="td_select">
									<div><select class="form-control dm_select" onchange="__INVENTORY__update_unit(this)">${material_options}</select></div>
								</td>
								<td id="product"><div class="product_number" style="text-align:center;"></div></td>
								<td id="status">
									<div>
										<select class="form-control status_select" onchange="__INVENTORY__add_negative_sign_quantity_addon_if_reduction(this);">
											${material_statuses}
										</select>
									</div>
								</td>
								<td><div class="input-group">
										<input type="number" min="0" class="form-control quantity" placeholder="1,000" max="9999999.99">
										<span class="input-group-addon unit"></span>
								</div></td>
								<td id="reason"><input type="text" class="form-control reason" placeholder="Reason"></td>
								<td style="text-align:center;margin:auto;">
									<button class="btn" style="width:100%" onclick="__INVENTORY__delete_row(this);">×</button>
								</td>`;

		var new_row = document.getElementById("update_mat_table").insertRow(-1);
		new_row.classList.add("update_rows");
		new_row.innerHTML = update_data;
	}


	// the x button is pressed for a row to be deleted (because it is unused or some other reason).
	// takes the button element that is being pressed.
	// checks that there is more than 1 row. if so, deletes selected row, else notifies user.
	function __INVENTORY__delete_row(element) {
		// prevent deletion of all rows
		if(document.getElementsByClassName("update_rows").length == 1)
		{
			alert("You must have at least 1 material update");
			return;
		}
		var row = element.closest("tr");
		if(row) row.remove();
		else alert("JS: Problem finding row for button");
	}


	// finds row that has successfully been updated and removes it.
	// takes dictionary of successes to remove.
	// goes through dictionary. builds a dictionary with the m_id. finds the row based on m_id, quantity, etc.
	// deletes row if found. if not found, alerts user.
	function __INVENTORY__remove_inventory_update_instance_from_page(successes) {
		var failures = [];
		for(var key in successes)
		{
			var material_update = Object.assign({}, successes[key], {"m_id" : key});
			var row = __INVENTORY__row_for_material_update(material_update);

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


	// find the matching update row based on m_id, quantity_used, status & notes.
	// takes the update info.
	// queries all rows & reduces by m_id. if more than 1 update for material, reduces by quantity, status, & 
	// notes.
	// if match found, return match as list. otherwise return empty list or multivalue list.
	function __INVENTORY__row_for_material_update(update) {
		var input_rows = document.getElementsByClassName("update_rows");
		// get matching rows based on material ID
		var material_match_rows = [];
		for(var x = 0; x < input_rows.length; x++)
		{
			if(parseInt(input_rows[x].getElementsByClassName("dm_select")[0].value) == parseInt(update["m_id"])) 
				material_match_rows.push(input_rows[x]);
		}

		if(!material_match_rows.length) return [];
		else if(material_match_rows.length == 1) return material_match_rows;

		// further narrowing
		var material_matched_by_all_attributes = [];  // store all results to see if multiple found
		for(var x = 0; x < material_match_rows.length; x++)
		{
			if(parseInt(material_match_rows[x].getElementsByClassName("quantity")[0].value) == parseInt(update["quantity"])
			&&	parseInt(material_match_rows[x].getElementsByClassName("status_select")[0].value) == parseInt(update["status"])
			&& material_match_rows[x].getElementsByClassName("reason")[0].value == update["notes"])
				material_matched_by_all_attributes.push(material_match_rows);
		}

		return material_matched_by_all_attributes;
	}


	// insert [unit] and related product # into cells.
	// takes the material select element.
	// finds row for element. splits select option value into m_id, unit & product number. if unit or product number
	// does not exist, defaults value. otherwise insert value into place.
	function __INVENTORY__update_unit(element) {
		var select_mat_data = element.options[element.selectedIndex].value.split('|');
		var great_grandparent = element.parentElement.parentElement.parentElement;
		great_grandparent.getElementsByClassName("unit")[0].innerHTML = select_mat_data[1] || "[ - ]";
		great_grandparent.getElementsByClassName("product_number")[0].innerHTML = select_mat_data[2] || "[unassigned]";
	}



	// ————————————————————— MODAL ————————————————–——————

	// populate modal with inventory change
	// compile list of materials. if not valid list, return. create a display table for data for modal. populate modal
	// with display table, data.
	function __INVENTORY__compile_and_populate_modal()
	{
		// get information of updated materials
		var materials = __INVENTORY__get_and_check_update_values();  // list to hold dict values for data from each row
		if(!materials) return;  // bad data: stop process from proceding to modal populations
		else if(materials.length < 1) return alert("Enter at least 1 item");

		// header for modal table
		var display_table = __INVENTORY__HTML_table_for_data(materials);
		__INVENTORY__populate_modal(display_table, materials);
	}


	// get info for each row, add it to dictionary, send dict to populate confirmation module
	// query all update_rows. for instance in list, create dictionary & validate data. if data valid, add dictionary to
	// list. otherwise, return nothing.
	// return list of dictionaries of each update instance.
	function __INVENTORY__get_and_check_update_values()
	{
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

			// check if all information filled or empty (product is not passed to backend, so no need to check)
			if(quantity === "" && mat_id === "NONE" && reason === "" && status_id === "NONE") continue;  //TODO: delete row
			
			if(!__INVENTORY__update_instance_is_valid(mat_id, quantity, reason, status_id)) return;
			var instance =	{
								"quantity_used" : quantity, "m_id" : mat_id, "name" : name,
								"status_id" : status_id, "status_text" : status_text,
								"product" : product, "notes" : reason
							};
			materials.push(instance);
		}
		return materials;
	}


	// create html table for update data
	// takes material data list of dictionaries
	// create header. for each instance in data list, create a row in table using data.
	// return table element
	function __INVENTORY__HTML_table_for_data(data) {
		var table = document.createElement("table");
		table.classList.add("table", "table-striped", "table-bordered", "table-responsive", "col-md-12");
		table.innerHTML = `<thead>
								<th><b>Material</b></th> <th><b>Product Number</b></th>
								<th><b>Status</b></th> <th><b>Quantity</b></th>
								<th><b>Reason</b>
							</thead>`;

		// add values for each material
		for(var x = 0; x < data.length; x++)
		{
			var row = table.insertRow(-1);
			row.innerHTML =  `<td>${data[x]["name"]}</td> <td>${data[x]["product"]}</td>
								<td>${data[x]["status_text"]}</td> <td>${data[x]["quantity_used"]}</td>
								<td>${data[x]["notes"]}</td>`;
		}
		return table;
	}


	// add material data and display table to modal.
	// takes html table, materials dictionary
	// insert table into body, json into AJAX function call. display modal. wait for user to click submit
	function __INVENTORY__populate_modal(display_table, materials)
	{
		document.getElementById("__INVENTORY__modal_body").innerHTML = display_table.outerHTML;
		document.getElementById("__INVENTORY__submit_button").onclick = function()
		{
			__INVENTORY__submit_update(materials);
		};
		$('#__INVENTORY__modal').modal('show');
	}


	// check that inputs have been properly filled
	// for each parameter, check it is in a correct range. if not, alert message & return nothing.
	// return true otherwise
	function __INVENTORY__update_instance_is_valid(mat_id, quantity, reason, status_id)
	{
		if(mat_id === "NONE")
			return alert("Select a material or clear everything in row");
		else if(quantity === "")
			return alert("Enter a quantity or clear everything in row");
		else if(parseFloat(quantity) > 99999.99)
			return alert("The database does not accept a quantity larger than 99999.99");
		else if(status_id === "NONE")
			return alert("Select a status or clear everything in row");
		else if(reason.length < 5)
			return alert("Enter a reason or clear everything in row");

		return true;
	}


	// ————————————————————— SUBMIT ————————————————–——————

	// AJAX call to material_ajax_requests.php to update values.
	// takes dictionary of updates & values.
	// converts dictionary to JSON. posts JSON to page. checks for successes from response. gets list of 
	// materials from first material select & status options from first status select to use with new row. removes
	// rows of successful updates. hides modal. if error, write details to log.
	function __INVENTORY__submit_update(materials)
	{
		$.ajax({
			url: "./sub/material_ajax_requests.php",
			type: "POST",
			dataType: "json",
			data: {"update_inventory" : true, "mats_used_update" : JSON.stringify(materials)},
			success: function(response)
			{
				// get statuses and options before removal
				var material_options =		document.getElementsByClassName("update_rows")[0]
												.getElementsByClassName("dm_select")[0].innerHTML;
				var material_statuses =	document.getElementsByClassName("update_rows")[0]
												.getElementsByClassName("status_select")[0].innerHTML;

				// remove successful updates
				__INVENTORY__remove_inventory_update_instance_from_page(response["successes"]);
				alert("Successfully updated all materials");
				__INVENTORY__additional_material(material_options, material_statuses);  // add clean row
				$('#__INVENTORY__modal').modal('hide');
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				alert("Error in query submission");
				console.log(errorThrown);
			}
		});
	}


	// ——————————————————— SHEET GOODS ————————————————–————
	// ———————————————————————————————————————————————


	// get user confirmation of sheetgood submission
	// call confirm().
	// if user confirmeed return true (to submit form). else return false
	function __SHEETGOOD__submit_confirmation()
	{
		if(confirm("You are about to submit this query. Click OK to continue or CANCEL to quit.")) return true;
		return false;
	} 


	// ajax request to si_getSheets.php to get sizes of sheet good variant.
	// makes a GET request to page passing selected variant. populates sizes select.
	function __SHEETGOOD__get_sizes()
	{
		// code for IE7+, Firefox, Chrome, Opera, Safari
		if(window.XMLHttpRequest) xmlhttp = new XMLHttpRequest();
		else xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");  // code for IE6, IE5

		xmlhttp.onreadystatechange = function()
		{
			if(this.readyState == 4 && this.status == 200)
				document.getElementById("__SHEETGOOD__varient_sizes_select").innerHTML = this.responseText;
			else if(this.status >= 400) alert("Unable to change sheetgood quantity");
		};
		
		var sheet_id = document.getElementById("__SHEETGOOD__sheet_variant_select").value;
		xmlhttp.open("GET", `/pages/sub/si_getSheets.php?val=${sheet_id}`, true);
		xmlhttp.send();
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
				document.getElementById("__SHEETGOOD__sheet_variant_select").innerHTML = this.responseText;
			else if(this.status >= 400) alert("Unable to get sheetgood variants");
		};
		
		var sheet_id = document.getElementById("__SHEETGOOD__sheet_parent_select").value;
		xmlhttp.open("GET", `/pages/sub/si_getVariants.php?val=${sheet_id}`, true);
		xmlhttp.send();
	} 


</script>