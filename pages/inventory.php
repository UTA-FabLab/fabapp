<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.9
 *
 *	 edited by: MPZinke on 12.08.18
 *   -Allow lead to change inventory amount
 *   -Restrict displayed inv. to current only
 */

$LvlOfInventory = 9;  //TODO: find out actual level; change for all

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

// change inventory
if($_SERVER["REQUEST_METHOD"] == "POST" && $staff->getRoleID() >= $sv['LvlOfLead'] && isset($_POST['save_material'])) {
	$m_id = filter_input(INPUT_POST, 'm_id');
	$new_amount = filter_input(INPUT_POST, 'quantity');
	$mat = new Materials($m_id);
	$original_amount = Mats_Used::units_in_system($m_id);
	$difference = $new_amount - $original_amount;
	$reason = "Updated to match current inventory";

	if(Mats_Used::update_mat_quantity($m_id, $difference, $reason, $staff, 16)) {
		$_SESSION['success_msg'] = $mat->getM_name()." updated from ".$original_amount." ".$mat->getUnit()." to ".
								   $new_amount." ".$mat->getUnit();
	} else {
		$_SESSION['error_msg'] = "Unable to update ".$mat->getM_name();
	}
	header("Location:inventory.php");
} 


?>
<title><?php echo $sv['site_name'];?> Inventory</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-md-12">
			<h1 class="page-header">Inventory</h1>
		</div>
		<!-- /.col-md-12 -->
	</div>
	<!-- /.row -->
	<div class="row">
		<div class="col-md-12">
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fas fa-warehouse fa-fw"></i> Inventory
					<?php if($staff && $staff->getRoleID() >= $sv['LvlOfLead']) { ?>
						<div class="pull-right">
							<div class="btn-group">
								<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
									<span class="fas fa-info"></span>
								</button>
								<ul class="dropdown-menu pull-right" role="menu">
									<li>
										<a>Double click on the unit to edit<br/>This should only be done for small inventory discrepancies</a>
									</li>
								</ul>
							</div>
						</div>
					<?php } ?>
				</div>
				<div class="panel-body">
					<table class="table table-condensed" id="invTable">
						<thead>
							<tr>
								<th class='col-md-5'>Material</th>
								<th><i class="fas fa-paint-brush fa-fw col-md-1"></i></th>
								<th class='col-md-2'>Qty on Hand</th>
								<th class='col-md-4'>Product Number</th>
							</tr>
						</thead>
						<tbody>
						<?php //Display Inventory Based on device group
						if($result = $mysqli->query("
							SELECT `materials`.`m_id` as `m_id`, `m_name`, SUM(quantity) AS `sum`, `materials`.`product_number`, `color_hex`, `unit`
							FROM `materials`
							LEFT JOIN `mats_used`
							ON mats_used.m_id = `materials`.`m_id`
							WHERE `materials`.`measurable` = 'Y'
							AND `materials`.`current` = 'Y'
							GROUP BY `m_name`, `color_hex`, `unit`
							ORDER BY `m_name` ASC;
						")){
							while ($row = $result->fetch_assoc()){ ?>
								<tr>
									<td><?php echo $row['m_name']; ?></td>
									<td align='center'><div class="color-box" style="width:100%;background-color: #<?php echo $row['color_hex'];?>;"/></td>
									<?php if($staff && $staff->getRoleID() >= $sv['LvlOfLead']) {
										echo "<td ondblclick='edit_materials(".$row['m_id'].")'>".number_format($row['sum'])." ".$row['unit']."</td>";
									} else {
										echo "<td>".number_format($row['sum'])." ".$row['unit']."</td>";
									} ?>
									<td><?php echo $row['product_number']; ?>
								</tr>
							<?php }
						} else { ?>
							<tr><td colspan="3">None</td></tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
				<!-- /.panel-body -->
			</div>
			<!-- /.panel -->
		</div>
		<!-- /.col-md-8 -->
	</div>
	<!-- /.row -->
</div>
<!-- /#page-wrapper -->

<div id='material_modal' class='modal'> 
</div>

<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>

<script>
	$('#invTable').DataTable({
		"iDisplayLength": 25,
		"order": []
	});

	function edit_materials(m_id){
		if (Number.isInteger(m_id)){
			if (window.XMLHttpRequest) {
				// code for IE7+, Firefox, Chrome, Opera, Safari
				xmlhttp = new XMLHttpRequest();
			} else {
				// code for IE6, IE5
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			}
			xmlhttp.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					document.getElementById("material_modal").innerHTML = this.responseText;
				}
			};
			xmlhttp.open("GET", "sub/edit_materials.php?m_id=" + m_id, true);
			xmlhttp.send();
		}

		$('#material_modal').modal('show');
	}


</script>