<?php

/***********************************************************************************************************
*
*	@author Jon Le
*	Edited by: MPZinke on 07.13.19 to add ability to measure filament used for each color.
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 1.0
*
*	DESCRIPTION: Split .gcode into multiple parts for color swap.  Halt printing and move
*	 nozzle out of the way.  Then comment .gcode in same manner as Prusa printer Kisslicer
*	 for amounts of each filament in [cm3].
*
***********************************************************************************************************/

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$auto_off = true;
//Retrive all Materials available for the PolyPrinter
$device_mats = Materials::getDeviceMats(2);
?>
<title><?php echo $sv['site_name'];?> Tools</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-md-12">
			<h1 class="page-header">Tools</h1>
			And other helpful stuff.
		</div>
		<!-- /.col-md-12 -->
	</div>
	<!-- /.row -->
	<div class="row">
		<div class="col-md-8">
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="far fa-file-alt fa-fw"></i> Color Swap Instructions
					<div class="pull-right">
						<button  class="btn btn-xs" data-toggle="collapse" data-target="#swapPanel"><i class="fas fa-bars"></i></button>
					</div>
				</div>
				<!-- /.panel-heading -->
				<div class="panel-body collapse in" id="swapPanel">
					<table class="table table-responsive">
						<tr>
							<td class="col-md-7">
								<img src="../images/colorswap.JPG" alt="" class="img-responsive"/>
							</td>
							<td class="col-md-5">
								<b>*Not intended for sequential printing*</b>
								<p>After the stl has been sliced and the gcode has been saved, view the “Models and Paths” and 
									determine which layer you want to pause the printer on. The layer is displayed in KISSlicer as </p>
								<p>z value = z height in mm</p>
								<p>Check the values of z as you scroll through the different layers. If you want to change the colors, 
									for example, between layers z=10.000 and z=10.250, you will want to pause at the end of layer z=10.000.</p>
								<p>Enter that value in the field as "10.000"</p>
								<input disabled value="10.000"/>
								<p>To do multiple color swaps add a new row for each pause that you want to have. The values must be in 
									sequential order. It requires both a valid Z height and a selected color.</p>
								<p>Upload the gcode file to the right.</p>
							</td>
						</tr>
						<tr>
							<td>
								<table class="table table-hover">
									<tr>
										<td>gcode Example</td>
										<td>Explanation</td>
									</tr>
									<tr>
										<td><i>G28 X</i></td>
										<td><i>Zero X-axis</i></td>
									</tr>
									<tr>
										<td><i>G1 Z20.000</i></td>
										<td><i>Move Z height to 20.000mm</i></td>
									</tr>
									<tr>
										<td><i>M117</i></td>
										<td><i>Print coordinates to console</i></td>
									</tr>
									<tr>
										<td><i>M0</i></td>
										<td><i>Pause Printer</i></td>
									</tr>
									<tr>
										<td><i>G1 Z10.2497</i></td>
										<td><i>Move Z height to correct height</i></td>
									</tr>
									<tr>
										<td><i>G28 X Y</i></td>
										<td><i>Zero X & Y axis</i></td>
									</tr>
								</table>
							</td>
							<td></td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<!-- /.col-md-8 -->
		<div class="col-md-4">
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fas fa-palette fa-fw"></i> Color Swap 
				</div>
				<!-- /.panel-heading -->
				<div class="panel-body">
					<table class="table-striped table-bordered table-responsive" id="cs_table">
						<thead>
							<th style="text-align:center">Location</th>
							<th style="text-align:center">Color</th>
						</thead>
						<tr>
							<td>
								<div class ="input-group">
									<span class="input-group-addon">Z = </span>
									<input type="number" min="0" step=".25" class="form-control swap" placeholder="ex. 10.000"/>
								</div>
							</td>
							<td id="td_select">
								<select class="form-control dm_select">
									<option hidden disabled selected value="">Select Color</option>
									<?php foreach($device_mats as $dm){
										echo ("<option value='".$dm->m_id."'>".$dm->m_name."</option>");
									}?>
								</select>
							</td>
						</tr>
					</table>
					<button class="btn btn-success pull-right" onclick="addZ()">Add Additional Color Swaps</button>
				</div>
				<div class="panel-footer">
					<div class="clearfix">
						<div class="pull-left">
							<input type="file" id="files" name="files" accept=".gcode"/>
							<output id="feedback"></output>
						</div>
						<button class="btn pull-right" onclick="insert_color_swaps()" disabled id="swapBtn">Submit</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script>
	if (window.File && window.FileReader && window.FileList && window.Blob) {
		
	} else {
		alert('Color Swap is not supported in this browser.');
	}
	
	function addZ(){
		var table = document.getElementById("cs_table");
		var row = table.insertRow(-1);
		var cell1 = row.insertCell(0);
		var cell2 = row.insertCell(1);
		cell1.innerHTML = "<div class ='input-group'><span class='input-group-addon'>Z = </span>"+
			"<input class='form-control swap'/></div>";
		//Duplicate Color DropDown
		cell2.innerHTML = document.getElementById("td_select").innerHTML;
	}
	
	function destroyClickedElement(event) {
		document.body.removeChild(event.target);
	}
	
	function handleFileSelect(evt) {
		var file = evt.target.files[0]; // File
		var regex = new RegExp('[\.]{1}(gcode)$');
		sBtn = document.getElementById('swapBtn');
		
		if (file == null){
			sBtn.classList.remove("btn-primary");
			sBtn.disabled = true;
			return;
		}
		
		var reader = new FileReader();

		reader.onload = function(e) {
			if (file && regex.test(file.name)) {
				sBtn.classList.add("btn-primary");
				sBtn.disabled = false;
				console.log("size "+ (file.size/1024000).toFixed(2) + "mb");
			} else {
				document.getElementById('feedback').innerHTML = "<div class='alert alert-danger' role='alert'> Invalid File Type</div>";
				sBtn.classList.remove("btn-primary");
				sBtn.disabled = true;
				console.log("File Undefined");
			}
		};
		reader.readAsText(file);
	}
	document.getElementById('files').addEventListener('change', handleFileSelect, false);//Sort Just z values
	
	function sortFloat(a,b) {
		return a - b;
	}


// ———————————————— COLOR SWAP —————————————————


	/*
	search through file for layers to add color swaps from page;
	sum filament lengths for each color;
	calculate volume based on percentage of total filament
		ie volume_of_part = part_length / total_length * total_volume
	write to file & download
	*/
	function insert_color_swaps(){
		//Get File & prep Reader
		var in_file = document.getElementById('files').files[0];
		var reader = new FileReader();
		var file_name = in_file.name.insert(in_file.name.length-6, "_ColorSwap");
		
		//Array of Z & m_id Locations
		var filament_amts = [0];
		var total_filament_volume;

		var swap_layers = [];
		var swap_inputs = document.getElementsByClassName("swap");
		// convert swap layers to float.3 & add to array
		for(var x = 0; x < swap_inputs.length; x++){
			var z_layer = parseFloat(swap_inputs[x].value).toFixed(3);
			if(!isNaN(z_layer)) swap_layers.push(z_layer);
		}

		reader.onload = function(file) {
			var gcode = file.target.result.split("\n");

			for(var x = 0; x < gcode.length; x++) {
				var line = gcode[x];
				var command = line.substr(0, line.indexOf(' ')) || line;

				// get total filament amount & add amount (remove the E infront of steps) to current filament amount
				if(command == 'G1' && line.includes('E'))
					filament_amts[filament_amts.length-1] += parseFloat(extrusion_value(line));
				// faster check for ignoring non-comment lines
				else if(command == ';') {
					var swap_layer = swap_layer_for_line(line, swap_layers);  // returns z height + buffer || null
					if(swap_layer) {
						// add swap commands
						var next_z_layer_value = find_next_z_layer_value(x, gcode);
						var swap_commands = "G28 X\nG1 Z"+swap_layer+"\nM117\nM0\nG1 "+next_z_layer_value+"\nG28 X Y";
						gcode.splice(x++, 0, swap_commands);  // ignore next line (priorly inserted command)

						// start counting next filament layer of gcode
						filament_amts.push(0);
					}
					else if(line.includes("Ext 1 =")) total_filament_volume = parseFloat(line.substr(line.indexOf("(")+1).match(/\d+(\.\d+)?/g));
				}
			}

			// calculate percentages of volume based on given total and calculated parts
			var filament_volumes = [];
			var total_filament_steps = filament_amts.reduce((a,b) => a + b, 0);
			for(var x = 0; x  < filament_amts.length; x++) filament_volumes.push(filament_amts[x] / total_filament_steps * total_filament_volume);

			//TEST
			// console.log("VOLUMES: ", filament_volumes.join(','));
			// console.log("SUM VOLUME: ", filament_volumes.reduce((a,b) => a + b, 0));
			// console.log("TOTAL VOLUME: ", total_filament_volume);

			// insert filament volumes used; finish up file
			gcode[gcode.length-1] = ("; filament used [cm3] = "+filament_volumes.join(', ')+"\n");
			gcode = gcode.join("\n");

			//Output File to Computer
			var download_link = document.createElement("a");
			download_link.download = file_name;
			var blob = new Blob([gcode], {type:"text/plain;charset-utf-8"});
			if (window.webkitURL != null) {
				// Chrome allows the link to be clicked
				// without actually adding it to the DOM.
				download_link.href = window.webkitURL.createObjectURL(blob);
			}
			else {
				// Firefox requires the link to be added to the DOM before it can be clicked
				download_link.href = window.URL.createObjectURL(blob);
				download_link.onclick = destroyClickedElement;
				download_link.style.display = "none";
				document.body.appendChild(download_link);
			}
				
			if(confirm((filament_amts.length-1)+" valid color swaps, is that correct?"))
				download_link.click();
			else alert("Unable to find all z values.");
		};
		reader.readAsText(in_file);
	}


	// return the number next to Extrude command (eg E123.45 -> 123.45)
	function extrusion_value(line) {
		var E_index = line.indexOf('E')+1;
		var first_space_index_after_E_value = line.substr(E_index).indexOf(' ');
		return line.substr(E_index, first_space_index_after_E_value) || line.substr(E_index);
	}


	// from current index, search proceeding lines for next layer (to move nozzle to following color swap)
	function find_next_z_layer_value(current_index, gcode) {
		for(var x = current_index+1; x < gcode.length; x++) {
			if(gcode[x].includes("BEGIN_LAYER_OBJECT z="))
				return gcode[x].substr(gcode[x].indexOf('=')+1).match(/\d+(\.\d+)?/g)[0];
		}
	}


	// get the specific z height for the current line: if none, return null
	function swap_layer_for_line(line, layers) {
		for(var x = 0; x <  layers.length; x++)
			if(line.includes("END_LAYER_OBJECT z="+layers[x])) return 10+parseFloat(layers[x]);  // add buffer room for extrusion
		return null;
	}

  
	String.prototype.insert = function (index, string) {
		if(index) return this.substring(0, index) + string + this.substring(index);
		return string + this;
	};
</script>