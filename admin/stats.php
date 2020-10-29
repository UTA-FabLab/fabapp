<?php

/***********************************************************************************************************
*	
*	@author MPZinke 
*	created on 03.12.19
*	edited by MPZinke on 09.30.19 to allow for AJAX requests to prevent page from reloading
*	 on query submission.  Limit size of query to 500.  Add more prebuilt queries
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.93.1
*		-Data Reports update
*
*	DESCRIPTION: get date and method of query; sort query and echo to js function to
*	 create file.
*	FUTURE:	-Add file type (csv, tsv, excel) download dropdown selection
*	BUGS:		-Fix panels
*				-Make Pie chart functions work for new responses
*
***********************************************************************************************************/


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


// -————————————  CUSTOM QUERY —————————————— 

$tables = Database_Table::get_tables();

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
								<select id='static_queries' class='form-control' onchange='showQueryContent(this), prebuilt_populated()'>
									<option disabled selected hidden>Select Query</option>
									<option value='byHour'>Tickets by Hour</option>
									<option value='byDay'>Tickets by Day</option>
									<option value='byHourDay'>Tickets by Hour for Each Day</option>
									<option value='byStation'>Tickets by Station</option>
									<option value='failedTickets'>Failed Tickets</option>
									<option value='byAccount'>Tickets by Account</option>
									<option value='IDTs'>IDT Tickets</option>
									<option value='by_device_all'>Tickets by Device (All)</option>
									<option value='by_device_floor'>Tickets by Device (Floor)</option>
									<option value='by_device_all'>Tickets by Device (Shop)</option>
									<option value='by_bursar'>Tickets charged to Bursar account</option>
								</select>
							</td>
							<td class='col-md-2'>
								<button id='prebuilt_button' class='btn btn-default' type='button' onclick='submit_prebuilt_query();' style='width:80%;' disabled>Get Query</button> 
							</td>
						</tr> 
					</table>
				</div>

				<div id='prebuilt_fields'>
					<div id='dates' hidden>
						<div class='input-group' id='start_time_div' style="padding:8px;padding-top:2px;padding-bottom:2px;"> 
							<span class='input-group-addon'>Start&nbsp;</span>
							<input type='date' id='start_time' class='form-control' onchange="prebuilt_populated()" />
						</div>
						<div class='input-group' id='end_time_div' style="padding:8px;padding-top:2px;padding-bottom:2px;"> 
							<span class='input-group-addon'>End&nbsp;&nbsp;</span>
							<input type='date' id='end_time' class='form-control' value='<?php
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
			<div id='custom_query_collapse' class='collapse query_collapse'>
				<form method='POST'>
					<h2>Custom Query</h2>
					<table class='table'>
						<tr>
							<td class='col-md-2	'>
								Tables
							</td>
							<td class='col-md-10'>
								<div class='col-md-6' style='padding-left:0px;' >
									<select id='table-1' name='table-1' class='form-control' onchange='get_table_columns(this, "1");'>
										<option selected>—Select Table—</option>
									<?php
									if($tables) {
										foreach($tables as $table) {
											echo "<option value='$table->table'>$table->name</option>";
										}
									}
									else {
										echo "<option disabled selected hidden>Could not get query</option>";
									}
									?>
									</select>
								</div>
								<div class='col-md-6' style='padding-left:0px;padding-right:0px;'>
									<select id='table-2' name='table-2' class='form-control' onchange='get_table_columns(this, "2")' disabled>
										<option selected style="color:#888888;">—Optional Cross Reference—</option>
									<?php
									if($tables) {
										foreach($tables as $table) {
											echo "<option value='$table->table'>$table->name</option>";
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
												<option value='' selected>—NOT REQUIRED—</option>
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
											<select id='operator-1' name='operator-1' class='form-control condition_operator' style='width:100%;'>
												<option selected></option>
												<option value='<'><</option>
												<option value='='>=</option>
												<option value='>'>></option>
												<option value='!='>!=</option>
											</select>
										</td>
										<td width='30%'>
											<input id='comparison-1' name='comparison-1' class='form-control condition_compare' style='width:100%;'/>
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
								<button type='button' onclick='build_query_statement();' class='btn pull-right btn-success'>Submit Query</button>
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>

	<!---------------------------------- Query Data Area ---------------------------------->

		<div id='result_pannel' class="panel panel-default" hidden>
			<div class="panel-heading">
				<button class='btn btn-default' style='right: 10px;' type='button' data-toggle='collapse' data-target='.results_collapse' 
				  onclick='results_button_text(this);' aria-expanded='false' aria-controls='collapse'>Hide Query</button>
			</div>
			<div class='collapse in results_collapse'>
				<div id='query_display' style='padding:16px;'>
					<!-- QUERY GOES HERE -->
				</div>
				<div>
					<table class='col-md-12'>
						<tr>
							<td class='col-md-3' style='padding:16px;'>
								<input id='tsv_data_input' value='' hidden>
								<button class='btn btn-default' onclick='exportTableToExcel(document.getElementById("tsv_data_input").value);'>Download Excel</button>
							</td>
							<!-- TODO: create so that only appears is Pie chart data sent through AJAX -->
							<td id='pie_chart_option' class='col-md-3' style='padding:16px;'>
								<div id='pie_chart_button_div' hidden>
									<button class='btn btn-default'>Download Pie Chart</button>
								</div>
							</td>
						</tr>
					</table>
				</div>
				<div style='padding:16px;' id='result_table_div'>
				</div>
			</div>
		</div>
	</div>
</div>


<?php include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php'); ?>

<script>
	var global_response;  // hold response data for TSV/CSV/Pie Chart usage
	var type_of_column_for_column_name = {};  // populated by AJAX call

	function submit_query_and_add_values_to_table(data_submission) {
		//AJAX
		$.ajax({
			url: "./sub/stats_ajax_requests.php",
			type: "POST",
			dataType: "json",
			data: data_submission,
			success: function(response) {
				console.log(response);
				if(response["error"]) {
					alert(response["error"]);
					return;
				}
				else if(response["warning"]) 
					alert(response["warning"]);

				$("#custom_query_collapse").removeClass('in');  // hide selections
				document.getElementById("query_display").innerHTML = response["statement"];
				document.getElementById("result_pannel").hidden = false;
				
				// set data to table and format
				document.getElementById("result_table_div").innerHTML = response["HTML"];
				$("#result_table").DataTable({
					"iDisplayLength" : 25
				});

				if(response["pie_chart"]) return;  //TODO: unhide pie chart button
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				alert("Error in query submission");
				console.log(errorThrown);
			}
		});
	}


// ——————————————— DATA EXPORTING —————————————————
// —————————————————————————————————————————

// ———————————————— EXCEL/TSV/CSV —————————————————

	function exportTableToExcel(tsv, filename = 'excel_data'){
		var downloadLink;
		var dataType = 'application/vnd.ms-excel';
		
		filename += '.xls';  // specify file name
		downloadLink = document.createElement("a");  // create download link element
		document.body.appendChild(downloadLink);
		
		if(navigator.msSaveOrOpenBlob){
				var blob = new Blob(['\ufeff', tsv], {type: dataType});
				navigator.msSaveOrOpenBlob( blob, filename);
		}
		else{
				downloadLink.href = 'data:' + dataType + ', ' + tsv;  // create a link to the file
				downloadLink.download = filename;  // setting the file name
				downloadLink.click();  // triggering the function
		}
	}


// ————————————— Pie Chart —————————————

	// take in dictionary 
	function create_pie_chart(data, filename="pie_chart_data.png") {
		console.log(data);
		var canvas = document.getElementById('piechart');
		canvas.getContext('2d').clearRect(0, 0, 1000, 1500);
		// pre-made color list
		var colors = [	'#0019F5', '#46A9F6', '#72FAFC', '#62D7A8', '#009000', '#55BA36', '#D8FB52', '#FFFE54', '#E7C042', '#FD8633', '#EB5B2A',
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


// ——————————————— PREBUILT QUERIES ———————————————— 
// ————————————————————————————————————————–

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
		var start = document.getElementById("start_time").value;
		var end = document.getElementById("end_time").value;

		// disable button if fields not populated
		if(!start.match(/(\d{4})-(\d{2})-(\d{2})/) || !end.match(/(\d{4})-(\d{2})-(\d{2})/))
			document.getElementById("prebuilt_button").disabled = true;
		else document.getElementById("prebuilt_button").disabled = false;
	}


	function submit_prebuilt_query() {
		var query = document.getElementById("static_queries").value;
		var start = document.getElementById("start_time").value;
		var end = document.getElementById("end_time").value;
		var device = document.getElementById("device").value;

		var data =
		{
			"prebuilt_query" : true, "query" : query, "start_time" : start, "end_time" : end, "device" : device,
			"pie_chart_label_column" : "", "pie_chart_data_column" : ""
		};
		submit_query_and_add_values_to_table(data);
	}


	function results_button_text(element) {
		if(element.innerHTML == "Hide Query") element.innerHTML = "Show Query";
		else element.innerHTML = "Hide Query";
	}


// ————————————————— Query Builder —————————————————
// —————————————————————————————————————————


	// call to modal to get first available box with type; create drawer layout & insert into modal
	function get_table_columns(element, table) {
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
				url: "./sub/stats_ajax_requests.php",
				type: "POST",
				dataType: "json",
				data: {"columns_for_table" : true, "table_id" : table_choice},
				success: function(response) {
					add_columns_to_selections(response, table);
					add_columns_to_conditions(table);
					add_columns_to_joins(table);
					add_columns_to_group_or_order_by(document.getElementById("group_by_select"));
					add_columns_to_group_or_order_by(document.getElementById("order_by_select"));
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					console.log("ERROR");
					console.log(errorThrown);
				}
			});
		}
	}


	function build_query_statement() {
		// select
		if(document.getElementById("selection-1").value.includes(".*")) var selected_columns_objects = [document.getElementById("selection-1")];
		else var selected_columns_objects = [].slice.call(document.getElementsByClassName("selected_column_table-1"));
		if(document.getElementById("selection-2").value.includes(".*")) selected_columns_objects.push(document.getElementById("selection-2"));
		else selected_columns_objects.concat([].slice.call(document.getElementsByClassName("selected_column_table-2")));  // add join columns

		var selected_columns = [];
		for(var x = 0; x < selected_columns_objects.length; x++)
			if(selected_columns_objects[x].value)
				selected_columns.push(selected_columns_objects[x].value); 
		selected_columns.join(", ")

		// from
		var table_1 = document.getElementById("table-1").value;

		// join (optional)
		var join = "";
		var table_2 = document.getElementById("table-2").value;
		if(table_2 && table_2 != "—Optional Cross Reference—") {
			var table_1_join = document.getElementById("join_select").value;
			var table_2_join = document.getElementById("compare_join_select").value;
			join = `JOIN \`${table_2}\` ON ${table_1_join} = ${table_2_join}`;
		}

		// where
		var where = "";
		var conditions = document.getElementsByClassName("conditions");
		if(document.getElementById("conditions-1").value != "—COLUMN—") {
			var condition_statements = [];
			for(var x = 0; x < conditions.length; x++) {
				if($(conditions[x]).find(".condition_select")[0].value) {
					var select = $(conditions[x]).find(".condition_select")[0].value;
					var operator = $(conditions[x]).find(".condition_operator")[0].value;
					var compare = $(conditions[x]).find(".condition_compare")[0].value;
					condition_statements.push(`${select} ${operator} ${compare}`);
				}
			}
			where = ` WHERE ${condition_statements.join(" AND ")}`;
		}

		// group
		var group = "";
		if(document.getElementById("group_by_select").value)
			group = ` GROUP BY ${document.getElementById("group_by_select").value}`;

		// order
		var order = "";
		if(document.getElementById("group_by_select").value)
			order = ` GROUP BY ${document.getElementById("group_by_select").value}`;

		var statement = `SELECT ${selected_columns} FROM \`${table_1}\` ${join} ${where} ${group} ${order}`;

		submit_query_and_add_values_to_table({"custom_query" : true, "statement" : statement});
	}


// —————————————— PREBUILT POPULATION ——————————————–
// —————————————————————————————————————————
// populate column options for selected table

// —————————————————— SELECT ——————————————————

	// create columns to query from ie 'SELECT A, B, C...'
	function add_columns_to_selections(data, table) {
		var columns_select = document.getElementById("selection-"+table);
		columns_select.innerHTML = `<option value='\`${data["table"]}\`.*'>ALL</option>`;  // clear attributes
		var table_select = document.getElementById("table-"+table);

		for(var column of data["columns"].values()) {
			var text = `${data["name"]}: ${data["column_names"][column]}`;
			var value = `\`${data["table"]}\`.\`${column}\``;
			create_option(columns_select, text, value);  // add attribute
			// add column to global dict of column types 
			type_of_column_for_column_name[value] = data["types"][column];
		}
	}


	// column has been selected; create tag to add to tage space
	function add_tags(column_select, table) {
		// clear tag space; display all columns if all selected
		if(column_select.value == '*') reset_column_select_and_tag_spaces(table);
		// add tag to tag space
		else {
			var selected_column = column_select.options[column_select.selectedIndex];
			var value = column_select.value;  // `table`.`column`
			var select = create_selection(column_select, selected_column, table, value);  // SELECT: allow for selection of functions applied to column
			var functionality = function() {
				unhide_tag_option_if_hidden(select, selected_column, table);
				if(document.getElementById('tags-'+table).children.length == 0) column_select.value = '*';
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

			$(column_select).val('');  // change select to blank value
		}
	}


	// SELECT: allow for selection of functions applied to column
	function create_selection(column_select, selected_column, table, value) {
		var functions = column_function(type_of_column_for_column_name[column_select.value]);
		if(functions.length == 0) {
			var select = document.createElement("input");
			select.classList.add("selected_column_table-"+table);
			select.readOnly = true;
			select.value = value;
			select.type = "hidden";
		}
		else {
			var select = document.createElement("select");
			select.classList.add("selected_column_table-"+table);
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


	// used when selecting all or changing tables
	function reset_column_select_and_tag_spaces(table) {
		var column_select = document.getElementById("selection-"+table);
		for(var x = 1; x < column_select.length; x++) column_select.options[x].hidden = false;
		document.getElementById("tags-"+table).innerHTML = '';
	}


// ——————————————— CONDITIONS —————————————————–—

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
		row.innerHTML = initial_condition.innerHTML.replace(/condition-1/g, "");
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
				if(x >= table_columns.length + other_columns.length)
					document.getElementById("condition_table").deleteRow(data_length + other_columns.length-1);
				else create_option(conditions[x], table_columns[y].text, table_columns[y].value);
			}
		}
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
			comparison.type = {"datetime" : "datetime-local", "decimal" : "number", "time" : "time", "int": "number", "varchar" : "text"}[type_of_column_for_column_name[element.value]];
			if(element.value == "enum") comparison.maxlength = "1";
		}
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



	// ————————————————— JOIN ———————————————————


	function add_columns_to_joins(table) {
		if(table == "1") return;  // table-1 change means indefinite joining
		var join = [document.getElementById("join_select"), document.getElementById("compare_join_select")];
		var selection = [document.getElementById("selection-1"), document.getElementById("selection-2")];
		for(var x = 0; x < join.length; x++) {
			join[x].innerHTML = "";  // clear options
			for(var y = 1; y < selection[x].options.length; y++) create_option(join[x], selection[x].options[y].text, selection[x].options[y].value)
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

			create_option(by_select, option.text, option.value);  // add basis option

			// add possible functions available for a column
			var functions = column_function(type_of_column_for_column_name[option.value]);
			for(var y = 0; y < functions.length; y++) {
				create_option(by_select, functions[y].replace(/\(opt\)/g, " of "+option.text), functions[y].replace(/opt/g, option.value));
			}
		}
	}


	// when join_select, allow for selection of only same types for compare_join_select 
	function filter_join(element) {
		var type = type_of_column_for_column_name[element.value];
		var comparison = document.getElementById("compare_join_select").options;
		for(var x = 0; x < comparison.length; x++) {
			var type_comparison = type_of_column_for_column_name[comparison[x].value];
			if(type_comparison != type) comparison[x].style.display = "none";
		}
	}


	// ————————————————— OTHER ——————————————————

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


	// create an option and add to passed select
	function create_option(select, text, value) {
		var option = document.createElement("option");
		option.text = text;
		option.value = value;
		select.appendChild(option);
	}


	// function to allow more precise evalutation for future options
	function unhide_tag_option_if_hidden(select, selected_column, table) {
		selected_column.hidden = false;
	}


// ————————————————— UTILITY ———————————————————
// ————————————————————————————————————————


	// if any of the items is relevant to the /usage function (eg contains /value), return true
	function any(list, usage, value) {
		for(var x = 0; x < list.length; x++)
			if(usage(list[x], value)) return true;
		return false;
	}

</script>