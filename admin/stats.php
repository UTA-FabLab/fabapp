<?php

/**********************************************************
*
*	@author MPZinke on 03.12.19
*	CC BY-NC-AS UTA FabLab 2016-2018
*	FabApp V 0.91
*
*	-CSV, PiChart Generator
*	-DESCRIPTION: get date and method of query;
*	 sort query and echo to js function to create file
*	-FUTURE: make download names more 
*	 specific
*
**********************************************************/

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

// staff clearance
if (!$staff || $staff->getRoleID() < $sv['minRoleTrainer']){
	// Not Authorized to see this Page
	header('Location: /index.php');
	$_SESSION['error_msg'] = "Insufficient role level to access, You must be a Trainer.";
}

// fire off modal & timer
if($_SESSION['type'] == 'success'){
	echo "<script type='text/javascript'> window.onload = function(){success()}</script>";
}


// prebuilt queries
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prebuilt_button'])) {
	$function = htmlspecialchars(filter_input(INPUT_POST, "static_queries"));
	$start = htmlspecialchars(filter_input(INPUT_POST, "start_time"));
	$end = htmlspecialchars(filter_input(INPUT_POST, "end_time"));
	$device = htmlspecialchars(filter_input(INPUT_POST, "device"));

	$params = Table::get_prebuild_data($end, $function, $start);

	// prepare if specific device wanted by adding AND condition infront of GROUP BY
	if($function !== "byStation" && $device !== "*") {
		$statement = "AND `d_id` = '".$device."' ";
		$params["statement"] = substr_replace($params["statement"], $statement, strpos($params["statement"], "GROUP BY"), 0);
	}
	
	// create data
	$data['head'] = $params['head'];
	$data['file_name'] = $params['file_name'];
	$data['query'] = $params['statement'];
	$data['results'] = query_results($params['statement']);
	$data['tsv'] = tsv($params['head'], $params['statement']);
	// pie chart
	if($params["file_name"] != "FabLab_TicketsByHourForEachDay" && $params["file_name"] != "FabLab_FailedTickets" 
	&& $params["file_name"] != "FabLab_IDTs")
		$data['pie'] = create_pie_chart($params["head"], $params["statement"]);
	else $data['pie'] = NULL;

}
// custom query
elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_custom_query'])) {
	$table1 = filter_input(INPUT_POST, "table-1");
	$table2 = filter_input(INPUT_POST, "table-2");
	// selections = * or selected columns
	$selections = implode(", ", (filter_input(INPUT_POST, "selection-1") == "*" ? array("`$table1`.*") : get_selections("1")));
	$selections2 = $join = $conditions = "";
	$conditions = implode(" AND ", get_conditions());  // array of conditions
	$group_by = filter_input(INPUT_POST, "group_by_select");
	$order_by = filter_input(INPUT_POST, "order_by_select");
	
	// if second table value chosen, get joinable information
	if($table2 != "—Optional Cross Reference—") {
		$selections2 = implode(", ", (filter_input(INPUT_POST, "selection-2") == "*" ? array("`$table1`.*") : get_selections("2")));
		$join = "JOIN `".$table2."` ON ".explode('|', filter_input(INPUT_POST, "join_select"))[0]." = ".explode('|', filter_input(INPUT_POST, "compare_join_select"))[0];
	}
	$head =  ($selections2 ? explode(', ', $selections.", ".$selections2) : explode(', ', $selections));
	$query = ($selections2 ? "SELECT ".$selections.", ".$selections2 : "SELECT ".$selections)." ";  // if applies: add selections
	$query .= " FROM `$table1` ";
	$query = ($join ? $query.$join." " : $query);  // if applies: add join
	$query = ($conditions ? $query."WHERE ".$conditions." " : $query);
	$query = ($group_by ? $query."GROUP BY ".$group_by." " : $query);
	$query = ($order_by ? $query."ORDER BY ".$order_by : $query);

	$data['file_name'] = "FabLab_DataReport";
	$data['results'] = query_results($query);
	$data['head'] = $data['results'] ? array_keys($data['results'][0]) : NULL;
	$data['query'] = $query;
	$data['tsv'] = tsv($head, $query);
	$data['pie'] = NULL;

}


// return tsv string to print into excel sheet
function tsv($head, $statement) {
	global $mysqli;

	if($results = $mysqli->query($statement)) {
		$values = implode("\\t", $head)."\\n";
		while($row = $results->fetch_assoc())
			$values .= implode("\\t", $row)."\\n";
		return $values;
	}
}

// return an array of the results of a query
function query_results($statement) {
	global $mysqli;

	if($results = $mysqli->query($statement)) {
		$values = array();
		while($row = $results->fetch_assoc())
			$values[] = $row;
		return $values;
	}
}

// -————————————  PREBUILD QUERY ————————————— 
// 	-Get fields, sort values, inject JS function call at bottom of page


function create_pie_chart($head, $statement) {
	global $mysqli;

	if($results = $mysqli->query($statement)) {
		// convert results into array
		$values = array();
		while($row = $results->fetch_assoc())
			$values[$row[$head[0]]] = $row[$head[1]];

		$sum = array_sum($values);

		// get proportionality of each value for each key
		$slices = array();
		foreach($values as $key => $value)
			$slices[] = "$key,".strval($value / $sum);

		return implode(";", $slices);
	}	
}


// -————————————  CUSTOM QUERY —————————————— 

$tables = Table::get_tables();

function get_conditions() {
	$count = 1;
	$values = array();

	while($count < 100) {
		$condition = filter_input(INPUT_POST, "conditions-".$count);
		$operator = filter_input(INPUT_POST, "operator-".$count);
		$comparison = "'".filter_input(INPUT_POST, "comparison-".$count++)."'";

		if(!$condition  || $condition == "—COLUMN—") return $values;  // 100 to failsafe
		$condition = explode('|', $condition)[0];
		$values[] = implode(' ', array($condition, $operator, $comparison));
	}
}


function get_selections($table) {
	$count = 1;
	$values = array();

	while($count < 100) {
		$column = filter_input(INPUT_POST, "tag-$table-".$count++);

		if(!$column) return $values;
		$values[] = explode('|', $column)[0];
	}
}

?>

<title><?php echo $sv['site_name'];?> Data Reports</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-md-12">
			<h1 class="page-header">Data Reports</h1>
		</div>
	</div>

	<!-- Table select -->
	<div class='col-md-12'>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fas fa-database"></i> Pre-Built Queries
			</div>
		</div>
		<div class='collapse in query_collapse'>
			<form method="POST">
				<div>
					<div id='badQueryMesssage'>
						<h3 id='badQueryMessage'></h3>
					</div>
					<!-- pie chart -->
					<div>
						<canvas id='piechart' width="1000px" height="1500px" hidden>Your browser does not support graphics</canvas>
						<a id='download' download="FabApp_PieChart.png" href="" onclick="download_piechart(this);" hidden></a>
					</div>
					<!-- Prebuilt inputs -->
					<table class='table'> 
						<tr>
							<td class='col-md-6' align="pull-left">
								<select id='static_queries' name='static_queries' class='form-control' onchange='showQueryContent(this), prebuilt_populated()'>
									<option disabled selected hidden>Select Query</option>
									<option value='byHour'>Tickets by Hour</option>
									<option value='byDay'>Tickets by Day</option>
									<option value='byHourDay'>Tickets by Hour for Each Day</option>
									<option value='byStation'>Tickets by Station</option>
									<option value='failedTickets'>Failed Tickets</option>
									<option value='byAccount'>Tickets by Account</option>
									<option value='IDTs'>IDT Tickets</option>
								</select>
							</td>
							<td class='col-md-2'>
								<button name='prebuilt_button' id='prebuilt_button' class='btn btn-default' style='width:80%;' disabled>Get Query</button> 
							</td>
						</tr> 
					</table>
				</div>

				<div id='prebuilt_fields'>
					<div id='dates' hidden>
						<div class='input-group' id='start_time' style="padding:8px;padding-top:2px;padding-bottom:2px;"> 
							<span class='input-group-addon'>Start&nbsp;</span><input type='date' name='start_time' id='prebuild_start' class='form-control' onchange="prebuilt_populated()" />
						</div>
						<div class='input-group' id='end_time' style="padding:8px;padding-top:2px;padding-bottom:2px;"> 
							<span class='input-group-addon'>End&nbsp;&nbsp;</span><input type='date' name='end_time' id='prebuild_end' class='form-control' value='<?php
							echo date('Y-m-d');
							?>' onchange="prebuilt_populated()"/>
						</div>
					</div>
					<div id='devices' class='input-group hidden' style='padding:8px;width:100%;'>
						<select id='device' name='device' class='form-control'>
							<option value='*'>All</option>
							<?php 
							if($results = $mysqli->query("SELECT `d_id`, `device_desc` 
															FROM  `devices`;"
							)) {
								while($row = $results->fetch_assoc()) {
									echo "<option value='$row[d_id]'>$row[device_desc]</option>";
								}
							}
							?>
						</select>
					</div>
				</div>
			</form>
		</div>
	<!---------------------------------- custom query builder ---------------------------------->
		<div class="panel panel-default">
			<div class="panel-heading">
				<button class='btn btn-default' style='right: 10px;' type='button' data-toggle='collapse' data-target='.query_collapse' 
				  onclick='button_text(this)' aria-expanded='false' aria-controls='collapse'>Create Custom Query</button>
			</div>
			<div class='collapse query_collapse'>
				<form method='POST'>
					<h2>Custom Query</h2>
					<table class='table'>
						<tr>
							<td class='col-md-2	'>
								Tables
							</td>
							<td class='col-md-10'>
								<div class='col-md-6' style='padding-left:0px;' >
									<select id='table-1' name='table-1' class='form-control' onchange='getTableCols(this, "1");'>
										<option selected>—Select Table—</option>
									<?php
									if($tables) {
										foreach($tables as $label => $name) {
											echo "<option value='$name'>$label</option>";
										}
									}
									else {
										echo "<option disabled selected hidden>Could not get query</option>";
									}
									?>
									</select>
								</div>
								<div class='col-md-6' style='padding-left:0px;padding-right:0px;'>
									<select id='table-2' name='table-2' class='form-control' onchange='getTableCols(this, "2")' disabled>
										<option selected style="color:#888888;">—Optional Cross Reference—</option>
									<?php
									if($tables) {
										foreach($tables as $label => $name) {
											echo "<option value='$name'>$label</option>";
										}
									}
									else {
										echo "<option disabled selected hidden>Could not get query</option>";
									}
									?>
									</select>
								</div>
							</td>
						</tr>
						<tr id='columns' hidden>
							<td>
								Select
							</td>
							<td>
								<table class='col-md-6'>
									<tr>
										<td style='padding-right:16px;'>
											<select id='selection-1' name='selection-1' class='form-control' onchange='add_tags(this, "1")'>
												<option value='*' selected>ALL</option>
											</select>
										</td>
									</tr>
									<tr>
										<td id='tags-1' style='padding:8px;'>
											<input id='tag-1' name='tag-1' value='FREAKING' /> <!-- TESTING -->
										</td>
									</tr>
								</table>
								<table class='col-md-6'>
									<tr>
										<td>
											<select id='selection-2' name='selection-2' class='form-control' onchange='add_tags(this, "2")' disabled>
												<option value='*' selected>ALL</option>
											</select>
										</td>
									</tr>
									<tr>
										<td id='tags-2' style='padding:8px;'>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr id='condition_row' hidden>
							<td>
								Condition
							</td>
							<td>
								<table id='condition_table' class='col-sm-12' style="width:100%;">
									<tr id='condition-1' class='conditions' style="padding-left:0px;width:100%;">
										<td width='30%' style="padding-left:0px;">
											<select id='conditions-1' name='conditions-1' class='form-control condition_select' style='width:100%;' onchange='condition_input(this);'>
												<option selected>—COLUMN—</option>
											</select>
										</td>
										<td width='10%'>
											<select id='operator-1' name='operator-1' class='form-control' style='width:100%;'>
												<option selected></option>
												<option value='<'><</option>
												<option value='='>=</option>
												<option value='>'>></option>
											</select>
										</td>
										<td width='30%'>
											<input id='comparison-1' name='comparison-1' class='form-control' style='width:100%;'/>
										</td>
									</tr>
								</table>
								<button class="btn btn-info pull-right" type='button' onclick="additional_condition()">Additional Condition</button>
							</td>
						</tr>
						<tr id='join_row' hidden>
							<td>
								Join
							</td>
							<td>
								<table width='100%'>
									<tr>
										<td width='40%'>
											<select id='join_select' name='join_select' class='form-control' onchange='filter_join(this);'>
											</select>
										</td>
										<td width='20%' align='center'>
											based on
										</td>
										<td width='40%'>
											<select id='compare_join_select' name='compare_join_select' class='form-control'>
											</select>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr id='group_by' hidden>
							<td>
								Group By
							</td>
							<td>
								<select id='group_by_select' name='group_by_select' class='form-control'>
								</select>
							</td>
						</tr>
						<tr id='order_by' hidden>
							<td>
								Order By
							</td>
							<td>
								<select id='order_by_select' name='order_by_select' class='form-control'>
								</select>
							</td>
						</tr>
						<tr id='submit_custom_query' hidden>
							<td>
							</td>
							<td width='100%'>
								<button name='submit_custom_query' class='btn pull-right btn-success'>Submit Query</button>
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>

	<!---------------------------------- Query Data Area ---------------------------------->
		<?php if(isset($data)) { ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<button class='btn btn-default' style='right: 10px;' type='button' data-toggle='collapse' data-target='.results_collapse' 
				  onclick='results_button_text(this);' aria-expanded='false' aria-controls='collapse'>Hide Query</button>
			</div>
			<div class='collapse in results_collapse'>
				<div style='padding:16px;'>
					<?php echo $data['query']; ?>
				</div>
				<?php
				 if($data['results'] != NULL) { ?>
					<div>
						<table class='col-md-12'>
							<tr>
								<td class='col-md-3' style='padding:16px;'>
									<button class='btn btn-default' onclick='exportTableToExcel("<?php echo $data['tsv'] ?>", "<?php echo $data['file_name']?>");'>Download Excel</button>
								</td>
								<td class='col-md-3' style='padding:16px;'>
									<?php if($data['pie']) { ?>
									<button class='btn btn-default' onclick='create_pie_chart("<?php echo $data['pie'] ?>", "<?php echo $data['file_name']?>");'>Download Pie Chart</button>
									<?php } ?>
								</td>
							</tr>
						</table>
					</div>
					<div style='padding:16px;'>
						<table id='query_table' class='table col-md-12'>
							<thead>
								<?php
								foreach($data['head'] as $key) echo "<td>$key</td>";
								?>
							</thead>
							<tbody>
								<?php
								foreach($data['results'] as $row) {
									echo "<tr>";
									foreach($row as $key => $value) {
										echo "<td>";
										if($key == "trans_id") 
											echo "<a href='https://$_SERVER[HTTP_HOST]/pages/lookup.php?trans_id=$value' target='_blank'>$value</a>";  //CHANGE HTTPS
										else echo $value;
										echo "</td>";
									}
									echo "</tr>";
								} ?>
							</tbody>
						</table>
					</div>
				<?php 
				}
				else echo "<h3 style='padding:16px;'>No Data For Selection</h3>";
				?>
			</div>
		</div>
		<?php } ?>
	</div>
</div>


<?php include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php'); ?>

<script>
	$("#query_table").DataTable();

	// used once query type is selected to display time & checkbox & hide/show devices inputs
	function showQueryContent(element) {
		var option = element.value;
		$("#dates").show();
		document.getElementById('devices').classList.remove("hidden");
		if(option == "byStation")
			document.getElementById('devices').classList.add("hidden");
	}


	// check if input is populated to enable the submit button
	function prebuilt_populated() {
		var start = document.getElementById("prebuild_start").value;
		var end = document.getElementById("prebuild_end").value;

		// disable button if fields not populated
		if(!start.match(/(\d{4})-(\d{2})-(\d{2})/) || !end.match(/(\d{4})-(\d{2})-(\d{2})/)) {
			document.getElementById("prebuilt_button").disabled = true;
		}
		else {
			document.getElementById("prebuilt_button").disabled = false;
		}
	}


	function results_button_text(element) {
		if(element.innerHTML == "Hide Query") element.innerHTML = "Show Query";
		else element.innerHTML = "Hide Query";
	}


	function exportTableToExcel(tsv, filename = ''){
		var downloadLink;
		var dataType = 'application/vnd.ms-excel';
		
		// Specify file name
		filename = filename?filename+'.xls':'excel_data.xls';
		
		// Create download link element
		downloadLink = document.createElement("a");
		
		document.body.appendChild(downloadLink);
		
		if(navigator.msSaveOrOpenBlob){
				var blob = new Blob(['\ufeff', tsv], {
						type: dataType
				});
				navigator.msSaveOrOpenBlob( blob, filename);
		}
		else{
				// Create a link to the file
				downloadLink.href = 'data:' + dataType + ', ' + tsv;
		
				// Setting the file name
				downloadLink.download = filename;
				
				//triggering the function
				downloadLink.click();
		}
	}


// ————————————— Pie Chart —————————————

	function create_wedge(canvas, color, start, stop, text, pos_x, pos_y) {
		if (canvas.getContext) {
			var ctx = canvas.getContext('2d'); 
			ctx.beginPath();
			ctx.fillStyle = color;
			ctx.arc(500, 500, 500, start, stop, false);
			ctx.lineTo(500, 500);  // center of circle
			ctx.rect(pos_x, pos_y-40, 40, 40);
			ctx.fill();
			// text
			ctx.fillStyle = '#000000';
			ctx.font = 'normal 30px Helvetica';
			ctx.fillText(text, pos_x+50, pos_y);
		}
	}


	 function download_piechart(element) {
		var image = document.getElementById('piechart').toDataURL('image/png');
		element.href = image;
	}

	function create_pie_chart(data, filename) {
		console.log(data);
		var canvas = document.getElementById('piechart');
		canvas.getContext('2d').clearRect(0, 0, 1000, 1500);
		// pre-made color list
		var colors = ['#0019F5', '#46A9F6', '#72FAFC', '#62D7A8', '#009000', '#55BA36', '#D8FB52', '#FFFE54', '#E7C042', '#FD8633', '#EB5B2A',
						'#CB331F', '#800000', '#652121', '#5C4033', '#333333', '#999999', '#FF69B4', '#CB3464', '#CB3496', '#CA3AE7', '#8129F5',
						'#551A8B', '#000080'];
		
		var start = 0;  // wedge beginning
		data = data.split(';');
		var row_per_col = Math.ceil(data.length/3);  // number of rows for text positioning
		for(var x = 0; x < data.length; x++) {
			// chart
			var color = colors[parseInt(x*colors.length/data.length)];  // space out colors so they are different Hues
			var row = data[x].split(',');
			var stop = start + 2 * Math.PI * row[1];  // wedge ending
			var text = row[0].substring(0,12).padEnd(12) + (parseFloat(row[1]) * 100).toFixed(1).toString()+'%';
			var text_pos = [parseInt(x/row_per_col)*350+25, (x%row_per_col)*50+1100];
			create_wedge(canvas, color, start, stop, text, text_pos[0], text_pos[1]);
			start = stop;
		}
		document.getElementById('download').download = filename;
		document.getElementById('download').click();
	}



// ———————————— Query Builder ————————————


	// AJAX for getting the names of columns from selected table
	function getTableCols(element, table){
		var table_choice = element.value;
		// always reset tags of 2 b/c user changed mind about what they wanted: prevent accidental submission of more selections
		for(var x = parseInt(table); x < 3; x++) reset_column_select_and_tag_spaces(x);
		var attributes2 = document.getElementById("selection-2");
		attributes2.disabled = true;
		attributes2.innerHTML = "<option value='*' selected>ALL</option>"

		// — — tables not yet acceptably selected — —
		// reset table-2: not ready to be used yet
		if(table == "1") {
			document.getElementById("table-2").value = "—Optional Cross Reference—";
			document.getElementById("selection-2").disabled = true;
			$("#join_row").hide();
		}
		else {
			$("#join_row").show();
		}

		// disable/reset cross reference & hide available columns if nothing for standard select chosen
		if(table_choice == "—Select Table—") {
			$("#columns, #condition_row, #group_by, #order_by, #submit_custom_query").hide();
			$("#table-2").prop("disabled", true);
			document.getElementById("selection-2").selectedIndex = 0;
		}
		else if(table_choice == "—Optional Cross Reference—") {
			var conditions = document.getElementsByClassName("condition_select");
			for(x = 0; x < conditions.length; x++) delete_table2_columns_from_conditions(conditions[x], table);
			document.getElementById("selection-2").disabled = true;
			$("#join_row").hide();
		}
		else {
			// show available columns, enable joinability
			$("#columns, #condition_row, #group_by, #order_by, #submit_custom_query").show();
			$("#table-2").prop("disabled", false);
			document.getElementById("selection-"+table).disabled = false;
			$.ajax({
				url: './sub/getTableCols.php?table_id='+table_choice,
				type: 'GET', 
				dataType: "json",     // change dataType as well.
				success: function(data) {
					console.log("LN: 631");
					add_columns_to_selections(data, table);
					add_columns_to_conditions(table);
					add_columns_to_joins(table);
					add_columns_to_group_or_order_by(document.getElementById("group_by_select"));
					add_columns_to_group_or_order_by(document.getElementById("order_by_select"));
				}
			});
		}
	}


	// add conditions to selects to query from ie 'WHERE A = 7...'
	function add_columns_to_conditions(table) {
		var conditions = document.getElementsByClassName("condition_select");
		for(var x = 0; x < conditions.length; x++) {
			var other_columns = (table == "2" ? document.getElementById("selection-1").options : []);  // declare here for length of 0
			delete_table2_columns_from_conditions(conditions[x], table);

			// new condition to each condition row
			var table_columns = document.getElementById("selection-"+table).options;
			for(var y = 1; y < table_columns.length; y++) {
				// delete unneeded rows
				if(x >= table_columns.length + other_columns.length) document.getElementById("condition_table").deleteRow(data_length + other_columns.length-1);
				else create_option(conditions[x], table_columns[y].text, table_columns[y].value);
			}
		}
	}



	// add available group by option to select based on available in condition-1
	function add_columns_to_group_or_order_by(by_select) {
		by_select.innerHTML = "<option value=''>—NONE—</option>";  // clear possible preexisting values
		var conditions = document.getElementById("conditions-1");
		for(var x = 1; x < conditions.options.length; x++) {
			var option = conditions.options[x];
			// create a group for each grouping options
			var optgroup = document.createElement("optgroup");
			optgroup.label = option.text;
			by_select.appendChild(optgroup);

			create_option(by_select, option.text, option.value.split('|')[0]);  // add basis option

			// add possible functions available for a column
			var functions = column_function(option.value.split('|')[1]);
			for(var y = 0; y < functions.length; y++) {
				create_option(by_select, functions[y].replace(/\(opt\)/g, " of "+option.text), functions[y].replace(/opt/g, option.value.split('|')[0]));
			}
		}
	}


	function add_columns_to_joins(table) {
		if(table == "1") return;  // table-1 change means indefinite joining
		var join = [document.getElementById("join_select"), document.getElementById("compare_join_select")];
		var selection = [document.getElementById("selection-1"), document.getElementById("selection-2")];
		for(var x = 0; x < join.length; x++) {
			join[x].innerHTML = "";  // clear options
			for(var y = 1; y < selection[x].options.length; y++) create_option(join[x], selection[x].options[y].text, selection[x].options[y].value)
		}
	}



	function add_columns_to_selections(data, table) {
		// create columns to query from ie 'SELECT A, B, C...'
		var columns_select = document.getElementById("selection-"+table);
		columns_select.innerHTML = "<option selected value='*'>ALL</option>";  // clear attributes
		var table_select = document.getElementById("table-"+table);
		var table_name = table_select.options[table_select.selectedIndex].text;
		for(var column in data) {
			var text = table_name+": "+column;
			var value = "`"+table_select.value+"`.`"+column+"`|"+data[column];
			create_option(columns_select, text, value);  // add attribute
		}
	}


	// column has been selected; create tag to add to tage space
	function add_tags(column_select, table) {
		// clear tag space; display all columns if all selected
		if(column_select.value == '*') reset_column_select_and_tag_spaces(table);
		// add tag to tag space
		else {
			var selected_column = column_select.options[column_select.selectedIndex];
			var value = column_select.value.split("|")[0];  // `table`.`column`
			var select = create_selection(column_select, selected_column, value);  // SELECT: allow for selection of functions applied to column
			var functionality = function() {
				unhide_tag_option_if_hidden(select, selected_column, table);
				if(document.getElementById('tags-'+table).children.length == 0) column_select.value = '*';
				refactor_selection_names(table);  // rename others to be sequential
			};

			var tag = create_tag(functionality, select, document.getElementById("tags-"+table));
			if(tag.children[0].type == "hidden") {
				var label = document.createElement("label");
				label.innerHTML = selected_column.text;
				label.style = "font-weight: normal !important;";
				tag.insertBefore(label, tag.children[1]);
			}

			// hide corresponding column option to prevent double submission
			hide_tag_option_from_tag_select_if_tag_count_equals_possibilities(select, selected_column, table);
			refactor_selection_names(table);
			$(column_select).val('');  // change select to blank value
		}
	}


	// add material row to inventory update table
	function additional_condition() {
		var conditions = document.getElementsByClassName("condition_select");
		var new_condition_length = conditions.length + 1;
		if(document.getElementById("conditions-1").options.length - 1 < new_condition_length) {
			alert("There are only "+(new_condition_length-1)+" conditions");
			return;
		}
		else if(conditions[conditions.length-1].value == "—COLUMN—") {
			alert("Please fill in the previous condition first");
			return;
		}

		var initial_condition = document.getElementById("condition-1");
		var table = document.getElementById("condition_table");
		var row = table.insertRow(-1);  // insert at bottom
		row.id = "condition-"+new_condition_length;
		row.className = "conditions";
		row.innerHTML = initial_condition.innerHTML.replace(/-1/g, "-"+new_condition_length);
	}


	// change the text of the button that collapses the section between current inventory and create new material
	function button_text(element) {
		if(element.innerHTML == "Create Custom Query") element.innerHTML = "Back to Pre-Built Queries";
		else element.innerHTML = "Create Custom Query";
	}


	function column_function(type) {
		return {"int" : ["AVG(opt)", "COUNT(opt)", "MAX(opt)", "MIN(opt)", "SUM(opt)"],
						"decimal" :  ["AVG(opt)", "COUNT(opt)", "MAX(opt)", "MIN(opt)", "SUM(opt)"],
						"datetime" : ["DAYNAME(opt)", "HOUR(opt)", "WEEKDAY(opt)"]}[type] || [];
	}


	// limit input types for conditions based on second part of value after "|" (column type)
	function condition_input(element) {
		// delete proceding conditions if value == COLUMN
		if(element.id != "conditions-1" && element.value == "—COLUMN—")
		//for looop it
			element.parentElement.parentElement.parentElement.removeChild(document.getElementById("condition-"+element.id.split("-")[1]));
		else {
			var comparison = document.getElementById("comparison-"+element.id.split("-")[1]);
			//TODO: find good way of implementing time interval
			comparison.type = {"datetime" : "datetime-local", "decimal" : "number", "time" : "time", "int": "number", "varchar" : "text"}[element.value.split("|")[1]];
			if(element.value == "enum") comparison.maxlength = "1";
		}
	}


	// create an option and add to passed select
	function create_option(select, text, value) {
		var option = document.createElement("option");
		option.text = text;
		option.value = value;
		select.appendChild(option);
	}


	// SELECT: allow for selection of functions applied to column
	function create_selection(column_select, selected_column, value) {
		var functions = column_function(column_select.value.split('|')[1]);
		if(functions.length == 0) {
			var select = document.createElement("input");
			select.readOnly = true;
			select.value = value;
			select.type = "hidden";
		}
		else {
			var select = document.createElement("select");
			create_option(select, selected_column.text, value);
			for(var x = 0; x < functions.length; x++)
				create_option(select, functions[x].replace(/\(opt\)/g, " of "+selected_column.text), functions[x].replace(/opt/g, value));
		}
		return select;
	}


	/* personal implimentation for creating a tag for data sorting
		ARGS: -function of things to do after tag is closed,
				-the text that will display within the tag
				-element to which tage is added */
	function create_tag(functionality, inner_element, parent) {
		var tag = document.createElement("div");
		var close = document.createElement("button");

		// appearance
		close.style = inner_element.style = "background-color:inherit;border-style:none;height:100%;display:inline-block;";
		tag.style = 'background-color:#E8E8E8;border-style:solid;border-color:#fff;border-radius:5px;border-width:3px;display:inline-block;text-align:justify;';

		// close button event
		close.innerHTML = "&#215";
		close.onclick = function() {
			parent.removeChild(tag);  // remove self
			functionality();  // user specified proceeding function
		};

		// add parts to tag; add tag to parent
		tag.appendChild(inner_element);
		tag.appendChild(close);
		parent.appendChild(tag);	
		return tag;
	}



	// delete table-2 columns from conditions by removing all and adding the ones from other table
	function delete_table2_columns_from_conditions(select_to_add_to, table) {
		select_to_add_to.innerHTML = "<option selected>—COLUMN—</option>";  // clear conditions
		// add first table's columns to conditions; ignore if table is '1' (see var other_columns)
		var other_columns = (table == "2" ? document.getElementById("selection-1").options : []);  // declare here for length of 0
		var other_table = document.getElementById("table-1");
		var other_table_name = other_table.options[other_table.selectedIndex].text;
		for(var y = 1; y < other_columns.length; y++) create_option(select_to_add_to, other_columns[y].text, other_columns[y].value);
	}


	// when join_select, allow for selection of only same types for compare_join_select 
	function filter_join(element) {
		var type = element.value.split("|")[1];
		var comparison = document.getElementById("compare_join_select").options;
		for(var x = 0; x < comparison.length; x++) {
			var type_comparision = comparison[x].value.split("|")[1];
			if(type_comparison != type) comparison[x].style.display = "none";
		}
	}


	function hide_tag_option_from_tag_select_if_tag_count_equals_possibilities(select, selected_column, table) {
		var basic_value = select.value;
		var max_selection_count = select.children.length || 1;
		var selection_count = 0;  // hold number of times a selection has been selected
		var selections = document.getElementById("tags-"+table).children;
		for(var x = 0; x < selections.length; x++) {
			// if div input option == basic value || (is div select && contains basic value)
			if(selections[x].children[0].value == basic_value || (selections[x].children[0].children.length > 0 
			&& selections[x].children[0].options[0].value == basic_value)) 
				selection_count++;
			if(max_selection_count == selection_count) selected_column.hidden = true;
		}
	}


	// when selection is deleted, rename all elements to be sequentially named
	function refactor_selection_names(table) {
		var tag_space = document.getElementById("tags-"+table);
		if(tag_space.children.length == 0) $("#tags-"+table).val("*");
		for(var x = 0; x < tag_space.children.length; x++) tag_space.children[x].children[0].name = 'tag-'+table+'-'+(x+1);
	}


	// used when selecting all or changing tables
	function reset_column_select_and_tag_spaces(table) {
		var column_select = document.getElementById("selection-"+table);
		for(var x = 1; x < column_select.length; x++) column_select.options[x].hidden = false;
		document.getElementById("tags-"+table).innerHTML = '';
	}


	// function to allow more precise evalutation for future options
	function unhide_tag_option_if_hidden(select, selected_column, table) {
		selected_column.hidden = false;
	}

</script>