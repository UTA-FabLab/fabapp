<?php 

/**************************************************
*
*	@author MPZinke on 01.31.19
*
*	-Allow viewing of all inventory: currently used
*	 and past
*	-Allow ability for admin to change inventory
*	 from inuse to retired & vice versus
*
**************************************************/

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

// staff clearance
if (!$staff || $staff->getRoleID() < $sv['minRoleTrainer']){
	// Not Authorized to see this Page
	header('Location: /index.php');
	$_SESSION['error_msg'] = "Insufficient role level to access, You must be a Lead.";
}

// fire off modal & timer
if($_SESSION['type'] == 'success'){
	echo "<script type='text/javascript'> window.onload = function(){success()}</script>";
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_inventory'])) {

}
?>

<title><?php echo $sv['site_name'];?> Current Inventory</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-md-12">
			<h1 class="page-header">Current Inventory</h1>
		</div>
	</div>

	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fas fa-shipping-fast"></i> Update For Newly Delivered Inventory
			</div>
			<!-- /.panel-heading -->
			<div>
				<table class="table table-condensed col-md-12" id="mats">
					<thead>
						<th class='col-md-8' style="text-align:center">Material</th>
						<th class='col-md-4' style="text-align:center">Current</th>
					</thead>
					<!-- display all materials -->
					<tbody>
						<?php if($result = $mysqli->query("SELECT `m_id`, `m_name`, `current`
														   FROM `materials`
														   ORDER BY `m_name`
						")) {
							while($row = $result->fetch_assoc()) {
								echo "<tr> <td> $row[m_name] </td>".
								"<td> <select class='form-control col-md-4' onchange='changeCurrent($row[m_id], this)' style='width:100%;'>".
									"<option value='Y'>Y</option>";
								// display whether currently used material
								if($row['current'] === 'N') echo "<option selected='selected' value='N'>N</option>";
								else echo "<option value='N'>N</option>";
								echo "</select> </td> </td> </tr>";
							}
						}
						else {
							echo "Unable to get table";
						} ?>
				</table>
			</div>
		</div>
	</div>
<!-- Display changes of inventory based on item selected; maybe new page? -->
</div>
<div id="modal" class="modal">
</div>


<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>


<script> 
	$('#mats').DataTable();

	// AJAX call to change_current.php to change status; fills modal w/ success msg
	function changeCurrent(id, element) {
		var val = element.value;
		console.log(id, val);
		// AJAX call
		if (window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp = new XMLHttpRequest();
		} else {
			// code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				document.getElementById("modal").innerHTML = this.responseText;
			}
		};
		xmlhttp.open("GET", "sub/change_current.php?id=" +id+ "|" + val, true);
		xmlhttp.send();
		$("#modal").show();
	}

	// close modal buttons were not working
	function dismiss_modal() {
		$("#modal").hide();
	}

</script>