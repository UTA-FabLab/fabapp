<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$d_id = $dg_id = $operator = "";

if (!$staff || $staff->getRoleID() < $sv['LvlOfStaff']){
	//Not Authorized to see this Page
	header('Location: /index.php');
	$_SESSION['error_msg'] = "Insufficient role level to access, sorry.";
	exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitBtn']) && ($_POST['submitBtn'] == 'Submit') && !empty($_POST['tm_id']) && $_SESSION['type'] != 'tc_success') {
	$tm_id = filter_input(INPUT_POST,'tm_id');
	$operator = filter_input(INPUT_POST, 'operator');
	$msg = false;
	
	$msg = submitTM($tm_id, $operator, $staff);
	
	if ($msg === true){
		$_SESSION['type'] = 'tc_success';
		$_SESSION['tm_id'] = $tm_id;
		header("Location:training_certificate.php");
	} else {
		echo "<script type='text/javascript'> window.onload = function(){goModal('Error',\"$msg\", false)}</script>";
	}
} elseif ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['teBtn'])) {
	$id = filter_input(INPUT_POST, 'teField');
	$user = Users::withID($id);
}

//
// Reset Forum and prevent null submission
//
if ($_SESSION['type'] && $_SESSION['type'] == 'tc_success' && $_SERVER["REQUEST_METHOD"] != "POST"){
	echo "<script type='text/javascript'> window.onload = function(){goModal('Success','Certificate Issued!', true)}</script>";
	
	//display current training module && description
	$tm = new TrainingModule($_SESSION['tm_id']);
	
	//clear type to prevent refresh
	$_SESSION['type'] = '';
}

function submitTM($tm_id, $operator, $staff){
	global $mysqli;
	
	if (!TrainingModule::regexTMId($tm_id)){
		return "Invalid Training Module ID";
	}
	
	//Attempt to mark as certified
	$tm = new TrainingModule($tm_id);
	$msg = $tm->certify_training($operator, $staff);
	if($msg === true){
		return true;
	} else {
		return $msg;
	}
}
?>
<title><?php echo $sv['site_name'];?> Certify Training</title>
<div id="page-wrapper">
	<div class="row">
		<div class="col-lg-12">
			<h1 class="page-header">Certify Training</h1>
		</div>
		<!-- /.col-lg-12 -->
	</div>
	<!-- /.row -->
	<div class="row">
		<div class="col-md-8">
			<?php if ($staff && $staff->getRoleID() >= $sv['LvlOfLead']) {?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="far fa-check-circle fa-lg"></i> Certify Completion of Training
					</div>
					<div class="panel-body">
						<table class="table table-bordered table-striped table-hover"><form name="tcForm" id="tcForm" autocomplete="off" method="POST" action="">
							<tr>
								<td class="col-md-3">
									<a href="#" data-toggle="tooltip" data-placement="top" title="The person that conducted this training">Trainer</a>
				</td>
								<td class="col-md-9"><?php if ( $staff )
									echo "<i class='".$staff->getIcon()." fa-lg' title='".$staff->getOperator()."'></i>";?>
								</td>
							</tr>
							<tr>
								<td><a href="#" data-toggle="tooltip" data-placement="top" title="Which device does this training belong to?">Select Device or Group</a></td>
								<td>
									<select name="d_id" id="d_id" onchange="selectDevice(this)" tabindex="1">
										<option disabled hidden selected value="">Device</option>
										<?php if($result = $mysqli->query("
											SELECT DISTINCT `devices`.`d_id`, `devices`.`device_desc`
											FROM `devices`
											INNER JOIN `trainingmodule`
											ON `devices`.`d_id` = `trainingmodule`.`d_id`
											ORDER BY `device_desc`
										")){
											while($row = $result->fetch_assoc()){
												echo("<option value='$row[d_id]'>$row[device_desc]</option>");
											}
										} else {
											echo ("Device list Error - SQL ERROR");
										}?>
									</select> or <select name="dg_id" id="dg_id" onchange="selectDevice(this)" tabindex="2">
										<option disabled hidden selected value="">Device Group</option>
										<?php if($result = $mysqli->query("
											SELECT DISTINCT `device_group`.`dg_id`, `device_group`.`dg_desc`
											FROM `device_group`
											INNER JOIN `trainingmodule`
											ON `device_group`.`dg_id` = `trainingmodule`.`dg_id`
											ORDER BY `dg_desc`
										")){
											while($row = $result->fetch_assoc()){
												echo("<option value='$row[dg_id]'>$row[dg_desc]</option>");
											}
										} else {
											echo ("Device list Error - SQL ERROR");
										}?>
									</select>
								</td>
							</tr>
							<tr id="tr_tm">
								<td><a href="#" data-toogle="tooltop" data-placement="top" title="Please select the relevant training that was conducted">Training</a></td>
								<td>
							<?php if (isset($tm)){ ?>
										<select name="tm_id" id="tm_id" onchange="getDesc(this)">
											<option value="<?php echo $tm->getTm_id()?>" ><?php echo $tm->getTitle()?></option>
										</select>
									</td>
								</tr>
								<tr>
									<td><a href="#" data-toogle="tooltop" data-placement="top" title="A brief description of what this training covers">Description</a></td>
									<td id="tm_desc"><?php echo $tm->getTm_desc()?></td>
								</tr>
							<?php } else { ?>
										<select name="tm_id" id="tm_id" onchange="getDesc(this)">
											<option value="" hidden>Select</option>
											<option value="" disabled="">Please Select a Device First</option>
										</select>
									</td>
								</tr>
								<tr>
									<td><a href="#" data-toogle="tooltop" data-placement="top" title="All devices covered by this Device Group">Devices</a></td>
									<td id="td_deviceList"></td>
								</tr>
								<tr>
									<td><a href="#" data-toogle="tooltop" data-placement="top" title="A brief description of what this training covers">Description</a></td>
									<td id="tm_desc"></td>
								</tr>
							<?php } ?>
							<tr>
								<td><a href="#" data-toggle="tooltip" data-placement="top" title="The person that you will issue a certificate to">Learner</a></td>
								<td><input type="text" name="operator" id="operator" class="form-control" placeholder="1000000000" maxlength="10" size="10"/></td>
							</tr>
							<tfoot>
								<tr>
									<td colspan="2"><div class="pull-right"><input type="submit" name="submitBtn" value="Submit"></div></td>
								</tr>
							</tfoot>
						</form></table>
					</div>
					<!-- /.panel-body -->
				</div>
				<!-- /.panel -->
			<?php } elseif($staff) { ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fas fa-sign-in-alt fa-lg"></i>  Certify Completion of Training
					</div>
					<div class="panel-body">
						<?php
							echo ("To issue a certificate, you must be logged in as ".ROLE::getTitle($sv['minRoleTrainer'])." or higher.");
						?>
					</div>
					<!-- /.panel-body -->
				</div>
				<!-- /.panel -->
			<?php } else { ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fas fa-sign-in-alt fa-lg"></i> Please Log In
					</div>
					<div class="panel-body">
					</div>
					<!-- /.panel-body -->
				</div>
				<!-- /.panel -->
			<?php } ?>
		</div>
		<!-- /.col-md-8 -->
		<div class="col-md-4">
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fas fa-table fa-lg"></i> Stats
				</div>
				<div class="panel-body">
					<table class="table table-condensed">
						<tbody>
							<?php if($result = $mysqli->query("
								SELECT count(*) as count
								FROM `trainingmodule`
						")){
							$row = $result->fetch_assoc()?>
							<tr>
								<td><i class="far fa-file fa-lg"></i> Training Modules</td>
								<td><?php echo $row['count'];?></td>
							</tr>
						<?php } else { ?>
							<tr>
								<td>Training Modules</td><td>-</td>
							</tr>
						<?php } ?>
						<?php if($result = $mysqli->query("
							SELECT count(*) as count
							FROM `tm_enroll`
							WHERE `current` = 'Y'
						")){
							$row = $result->fetch_assoc()?>
							<tr>
								<td><i class="far fa-check-circle fa-lg"></i> Certificates Issued</td>
								<td><?php echo $row['count'];?></td>
							</tr>
						<?php } else { ?>
							<tr>
								<td>Training Enrollments</td><td>-</td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
				<!-- /.panel-body -->
			</div>
			<!-- /.panel -->
		</div>
		<!-- /.col-md-4 -->
	</div>
	<!-- /.row -->
</div>
<!-- /#page-wrapper -->

<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript">
	
	$('#teTable').DataTable({
		searching: false, 
		paging: false});
	
	//AJAX call to build a list of training modules for the specified device or device group
	function selectDevice(element){
		if (element.id == 'd_id'){
			document.getElementById("dg_id").selectedIndex = 0;
		} else if (element.id == 'dg_id') {
			document.getElementById("d_id").selectedIndex = 0;
		}
		document.getElementById("tm_id").selectedIndex = 0;
		document.getElementById("td_deviceList").innerHTML = "";

		if (window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp = new XMLHttpRequest();
		} else {
			// code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				document.getElementById("tm_id").innerHTML = this.responseText;
				document.getElementById("tm_desc").innerHTML = "";
			}
		};
		device = element.id + "=" + element.value;
		xmlhttp.open("GET","sub/certTM.php?" + device,true);
		xmlhttp.send();
		
		//List Devices
		if (element.id == 'dg_id') {
			if (window.XMLHttpRequest) {
				// code for IE7+, Firefox, Chrome, Opera, Safari
				xmlhttp2 = new XMLHttpRequest();
			} else {
				// code for IE6, IE5
				xmlhttp2 = new ActiveXObject("Microsoft.XMLHTTP");
			}
			xmlhttp2.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					document.getElementById("td_deviceList").innerHTML = this.responseText;
				}
			};
			device = element.id + "=" + element.value;
			xmlhttp2.open("GET","sub/certDevices.php?" + device,true);
			xmlhttp2.send();
		}
	}
	
	
	//AJAX call to build a list of training modules for the specified device or device group
	function getDesc(element){
		if (window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp = new XMLHttpRequest();
		} else {
			// code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				document.getElementById("tm_desc").innerHTML = this.responseText;
			}
		};
		xmlhttp.open("GET","sub/descTM.php?tm_id=" + element.value,true);
		xmlhttp.send();
	}
</script>