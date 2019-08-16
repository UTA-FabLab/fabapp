<?php

/***********************************************************************************************************
*	
*	@author Jon Le
*	Edited by: MPZinke 05.17.19 to have Graphical representation of location
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V 0.94
*
*	FUTURE: add $sv for "held in storage too long"
*	 Make so that people can't see staff
*
***********************************************************************************************************/


include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
if (!$staff || $staff->getRoleID() < $sv['LvlOfStaff']){
	//Not Authorized to see this Page
	$_SESSION['error_msg'] = "You are unable to view this page.";
	header('Location: /index.php');
	exit();
}
?>
<title><?php echo $sv['site_name'];?> ObjBox</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-md-12">
			<h1 class="page-header">Objects in Storage</h1>
		</div>
		<!-- /.col-md-12 -->
	</div>
	<!-- /.row -->
	<div class="row">
		<div class="col-md-8">
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fas fa-gift fa-fw"></i>
				</div>
				<div class="panel-body">
					<table class="table table-striped table-striped" id="objTable">
						<thead>
							<th>Ticket</th>
							<th>Location</th>
							<th>Date</th>
							<th>Operator</th>
						</thead>
						<?php 

						$Drawers = StorageDrawer::get_all_drawers(array("style" => "background-color:#00FF00;border:solid;border-width:2px;"), 
																	null, null, array("style" => "background-color:#0000FF;border:solid;border-width:2px;"));
						// create drawer, tooltip for each box
						foreach($Drawers as $drawer) {
							foreach($drawer->units as $unit) {
								$drawer->determine_selected_units(function($drawer_unit) use ($unit) {return $unit->trans_id && $drawer_unit->trans_id == $unit->trans_id;});
								if($unit->trans_id && date('Y-m-d', strtotime("+2 week", strtotime($unit->item_change_time))) < date('Y-m-d'))
									echo "<tr style='background-color:#FF0000;'>";
								elseif($unit->trans_id && date('Y-m-d', strtotime("+1 week", strtotime($unit->item_change_time))) < date('Y-m-d'))
									echo "<tr style='background-color:#FFFF00;'>";
								elseif($unit->trans_id) echo "<tr style='background-color:#00FF00;'>";
								else echo "<tr>";
								echo	"<td>
											<a href='/pages/lookup.php?trans_id=$unit->trans_id'>$unit->trans_id</a>
										</td>
										<td>
											<button onclick='display_drawer_with_box(\"$drawer->drawer_indicator\", \"$unit->unit_indicator\");'
											type='button' class='btn btn-default'>".
												$unit->box_id.
											"</button>
										</td>
										<td>
											$unit->item_change_time
										</td>
										<td>
											$unit->staff
										</td>
									</tr>";
							}
						}
						?>
					</table>
				</div>
			</div>
		</div>
		<!-- /.col-md-8 -->
		<div class="col-md-4">
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="far fa-file-excel fa-fw"></i> Generate File
				</div>
				<div class="panel-body">
					<button class="btn btn-primary" disabled="true">Download CSV</button>
				</div>
				<!-- /.panel-body -->
			</div>
			<!-- /.panel -->
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fas fa-gift"></i> ObjectBox Stats
				</div>
				<div class="panel-body">
					<table class="table table-bordered table-hover">
						<tr>
							<td>Capacity</td>
							<td>
								<?php
								if($results = $mysqli->query("SELECT COUNT(`drawer`)
																	FROM `storage_box`;"
								)) {
									echo $results->fetch_assoc()["COUNT(`drawer`)"];
								}
								?>	
							</td>
						</tr>
						<tr>
							<td>In Storage</td>
							<td>
								<?php echo StorageObject::number_of_objects_in_storage(); ?>
							</td>
						</tr>
					</table>
				</div> <!-- /.panel-body -->
			</div> <!-- /.panel --> <!-- /.col-md-4 -->
	</div> <!-- /.row -->
</div> <!-- /#page-wrapper -->



<!-- modal for selecting storage location -->
<div id='storage_modal' class='modal'>
	<div class='modal-dialog'>
		<div class='modal-content'>
			<div class='modal-header'>
				<button type='button' class='close' onclick='$("#storage_modal").hide();'>&times;</button>
				<h4 class='modal-title' id='drawer_title' align='center'>ERROR</h4>
			</div>
			<div class='modal-body'>
				<div id='drawer_fill' align='center'>
					ERROR with AJAX request
				</div>
				<div class='modal-footer'>
					  <button type='button' class='btn btn-default' onclick='$("#storage_modal").hide();'>Cancel</button>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>

<script>
	$('#objTable').DataTable({
		"iDisplayLength": 25,
		"order": []
	});


	function display_drawer_with_box(drawer, unit) {
		$.ajax({
			url: "./sub/storage_ajax_requests_admin.php",
			type: "POST",
			dataType: "json",
			data: {"display_unit" : true, "drawer" : drawer, "unit" : unit},
			success: function(response) {
				if(response["error"]) {
					alert(response["error"]);
					return;
				}

				document.getElementById("drawer_fill").innerHTML = response["drawer_HTML"];
				var title = `Display of ${response["type"]} Box ${drawer}${unit}`;
				if(response["trans_id"]) title += ` containing Ticket #${response["trans_id"]}`;
				document.getElementById("drawer_title").innerHTML = title;

			}
		});
		$("#storage_modal").show("modal");
	}

</script>