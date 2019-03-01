<?php

/**********************************************************
*
*	-CSV Generator
*
**********************************************************/

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

// staff clearance
if (!$staff || $staff->getRoleID() < $sv['minRoleTrainer']){
	//Not Authorized to see this Page
	header('Location: /index.php');
	$_SESSION['error_msg'] = "Insufficient role level to access, You must be a Trainer.";
}

// fire off modal & timer
if($_SESSION['type'] == 'success'){
	echo "<script type='text/javascript'> window.onload = function(){success()}</script>";
}


// prebuilt queries
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prebuilt_button'])) {
	$function = htmlspecialchars(filter_input(INPUT_POST, "prebuilt_query"));
	$start = htmlspecialchars(filter_input(INPUT_POST, "start_time"));
	$end = htmlspecialchars(filter_input(INPUT_POST, "end_time"));

	$params = Table::get_prebuild_data($end, $function, $start);
	
	$csv = export_csv($params["file_name"], $params["head"], $params["statement"]);
	if($params["file_name"] != "TicketsByHourForEachDay" && $params["file_name"] != "FailedTickets") 
		$pie = create_pie_chart($params["file_name"], $params["head"], $params["statement"]);
}



// -————————————  PREBUILD QUERY ————————————— 
// 	-Get fields, sort values, inject JS function call at bottom of page

function export_csv($file_name, $head, $statement) {
	global $mysqli;

	$values = "";
	if($results = $mysqli->query($statement)) {
  		// failed tickets
  		if($file_name == "FailedTickets") {
  			$row = $results->fetch_assoc();
  			$values = "$head[0]\n".$row['COUNT(*)'];
  		}
  		else {
  			$values = implode(",", $head)."\\n";
	  		while($row = $results->fetch_assoc()) {
	  			$values .= implode(",", $row)."\\n";
	  		}
	  	}
	  	return array_combine(array('file_name', 'data'), array($file_name, $values));
	}
	else {
		echo "<script>window.onload = function() {".
					"document.getElementById('badQueryMessage').innerHTML = 'Unable to get data';".
				"}; </script>";
	}
}


function create_pie_chart($file_name, $head, $statement) {
	global $mysqli;

	if($results = $mysqli->query($statement)) {
		echo 	"<script>window.onload = function() {".
					"document.getElementById('badQueryMessage').innerHTML = '';".
				"}; </script>";  // clear possible error message

		$values = array();
		$slices = array();
		while($row = $results->fetch_assoc()) {
			$values[$row[$head[0]]] = $row[$head[1]];
		}
		$sum = array_sum($values);
		foreach($values as $key => $value) {
			$slices[] = "$key,".strval($value / $sum);
		}
		return array_combine(array('data', 'filename'), array(implode(";", $slices), $file_name));
	}
	else {
		echo 	"<script>window.onload = function() {".
					"document.getElementById('badQueryMessage').innerHTML = 'Unable to get data';".
				"}; </script>";
	}	
}

// -————————————— QUERY BUILDER ————————————— 

$tables = get_tables();

function get_tables() {
	global $mysqli;  // do not know why is this required

	$tmp = array();
	if($results = $mysqli->query("SELECT `table_name`, `label`
								  FROM `table_descriptions`;")) {
		while($row = $results->fetch_array(MYSQLI_ASSOC)) {
			$tmp[] = $row;
		}
		return $tmp;
	}
} ?>

<title><?php echo $sv['site_name'];?> Data Reports</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-md-12">
			<h1 class="page-header">Data Reports</h1>
		</div>
	</div>

	<!-- Table select -->
	<div class='col-md-12'>
		<form method="POST">
			<h2>Pre-Built Queries</h2>
			<div>
				<div id='badQueryMesssage'>
					<h3 id='badQueryMessage'></h3>
				</div>
				<!-- pie chart -->
				<div>
					<canvas id='piechart' width="1000px" height="1500px" hidden>Your browser does not support graphics</canvas>
					<a id='download' download="FabApp_PieChart.png" href="" onclick="download_piechart(this);"></a>
				</div>
				<!-- Prebuilt inputs -->
				<table class='table'> <tr>
					<td class='col-md-8' align="pull-left">
						<select id='static_queries' class='form-control' onchange='showQueryContent(this), prebuilt_populated()'>
							<option disabled selected hidden>Select Query</option>
							<option value='byHour'>Tickets by Hour</option>
							<option value='byDay'>Tickets by Day</option>
							<option value='byHourDay'>Tickets by Hour for Each Day</option>
							<option value='byStation'>Tickets by Station</option>
							<option value='byAccount'> Tickets by Account</option>
							<option value='failedTickets'>Failed Tickets</option>
						</select>
					</td>
					<td class='col-md-4'>
						<button name='prebuilt_button' id='prebuilt_button' class='btn btn-default' style='width:40%;' disabled>Get Query</button> 
					</td>
				</tr> </table>
			</div>

			<div id='prebuilt_fields'>
				<input name='prebuilt_query' id='prebuilt_query' hidden/>
				<div id='dates' hidden>
					<div class='input-group' id='start_time' style="padding:8px;padding-top:2px;padding-bottom:2px;"> 
						<!-- TESTING -->
						<span class='input-group-addon'>Start&nbsp;</span><input type='date' name='start_time' id='prebuild_start' class='form-control' value='<?php 
						echo date('Y-m-d', strtotime("-3 year"));
						?>' onchange="prebuilt_populated()" />
						<!-- <span class='input-group-addon'>Start&nbsp;</span><input type='date' name='start_time' id='prebuild_start' class='form-control' onchange="prebuilt_populated()" /> -->
					</div>
					<div class='input-group' id='end_time' style="padding:8px;padding-top:2px;padding-bottom:2px;"> 
						<span class='input-group-addon'>End&nbsp;&nbsp;</span><input type='date' name='end_time' id='prebuild_end' class='form-control' value='<?php
						echo date('Y-m-d');
						?>' onchange="prebuilt_populated()"/>
						
						<!-- <span class='input-group-addon'>End&nbsp;&nbsp;</span><input type='date' name='end_time' id='prebuild_end' class='form-control' onchange="prebuilt_populated()"/> -->
					</div>
				</div>
			</div>
		</form>
	<!---------------------------- custom query builder ---------------------------->
		<form method='POST'>
			<h2>Custom Query</h2>
			<div class='col-md-6'>
				<select id='table_select' class='form-control status_select' onchange='getTableCols(this, "table1")'>
				<?php
				if($tables && count($tables) > 0) {
					echo "<option selected value=''>—Select Table—</option>";
					foreach($tables as $tab) {
						echo "<option value='$tab[table_name]'>".$tab['label']."</option>";
					}
				}
				else {
					echo "<option disabled selected hidden>Could not get query</option>";
				}
				?>
				</select>
				<div id='table1'>
				</div>
			</div>
			<div class='col-md-6'>
				<select id='cross_reference' class='form-control status_select' onchange='getTableCols(this, "table2")' disabled>
					<option selected style="color:#888888;">—Optional Cross Reference—</option>
				<?php
				if($tables && count($tables) > 0) {
					foreach($tables as $tab) {
						echo "<option value='$tab[table_name]'>".$tab['label']."</option>";
					}
				}
				else {
					echo "<option disabled selected hidden>Could not get query</option>";
				}
				?>
				</select>
				<div id='table2'>
				</div>
			</div>
		</form>
	</div>
</div>


<?php include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php'); ?>

<script>
	// used once query type is selected to display time (& payment type) inputs
	function showQueryContent(element) {
		var option = element.value;
		document.getElementById("prebuilt_query").value = option;  // set input value filtered for on PHP call
		$("#dates").show();
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



	function create_csv(filename, array) {
		var csvContent = 'data:text/csv;charset=utf-8,' + array;
		var encodedUri = encodeURI(csvContent);
		window.open(encodedUri, 'test.csv');
	}


// ————————————— Pie Chart —————————————

	function create_wedge(canvas, color, start, stop, text, pos_x, pos_y) {
		if (canvas.getContext) {
			var ctx = canvas.getContext('2d'); 
			ctx.beginPath();
			ctx.fillStyle = color;
			ctx.arc(500, 500, 500, start, stop, false);
			ctx.lineTo(500, 500);
			ctx.rect(pos_x, pos_y-40, 40, 40)
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
		var canvas = document.getElementById('piechart');
		canvas.getContext('2d').clearRect(0, 0, 1000, 1500);

		var colors = ['#0019F5', '#46A9F6', '#72FAFC', '#62D7A8', '#009000', '#55BA36', '#D8FB52', '#FFFE54', '#E7C042', '#FD8633', '#EB5B2A',
					    '#CB331F', '#800000', '#652121', '#5C4033', '#333333', '#999999', '#FF69B4', '#CB3464', '#CB3496', '#CA3AE7', '#8129F5',
					    '#551A8B', '#000080'];
		var start = 0;
		data = data.split(';');
		var row_per_col = Math.ceil(data.length/3);
		for(var x = 0; x < data.length; x++) {
			// chart
			var row = data[x].split(',');
			var stop = start + 2 * Math.PI * row[1];
			var color = colors[parseInt(x*colors.length/data.length)];
			var text_pos = [parseInt(x/row_per_col)*300+50, (x%row_per_col)*50+1100];
			create_wedge(canvas, color, start, stop, row[0], text_pos[0], text_pos[1]);
			start = stop;
		}
		document.getElementById('download').download = filename;
		document.getElementById('download').click();
	}



// ———————————— Query Builder ————————————


	// AJAX for getting the names of columns from selected table
	function getTableCols(element, table_div){
		var table = element.value;
		var cross_reference = document.getElementById("cross_reference");
		if(cross_reference.disabled && document.getElementById("table_select").value) $("#cross_reference").prop("disabled", false);
		
		if (window.XMLHttpRequest) xmlhttp = new XMLHttpRequest();  // code for IE7+, Firefox, Chrome, Opera, Safari
		else { xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); }  // code for IE6, IE5
		if(table === "") {
			document.getElementById(table_div).innerHTML = "";
			$("#cross_reference").prop("disabled", true);
			return;
		}
		xmlhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) document.getElementById(table_div).innerHTML = this.responseText;
		};
		xmlhttp.open("GET", "sub/getTableCols.php?table_id=" + table, true);
		xmlhttp.send();
	}


	function isChecked(x) {
		console.log("HERE");  //TESTING
		if( $("#"+x).is(":visible") ) 
			$("#"+x).hide();
		else {
			$("#"+x).show();
		}
	}

</script>

<?php 
// ugly call to JS to print/download everything
if(isset($csv)) {
	echo "<script> create_csv('$csv[file_name]', '$csv[data]'); </script>";
	if(isset($pie)) {
		echo "<script> create_pie_chart('$pie[data]', '$pie[filename]'); </script>";
	}
}
?>