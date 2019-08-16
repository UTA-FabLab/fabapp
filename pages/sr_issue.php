<?php
/*
 * CC BY-NC-AS UTA FabLab 2016-2018
 * FabApp V 0.91
 */
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/pages/header.php');
$device = $sl_id = $notes = "";

if (!isset($staff) || ($staff->getRoleID() < $sv['LvlOfStaff'] && $staff->getRoleID() != $sv['serviceTechnican'])) {
	// Not Authorized to see this Page
	$_SESSION['error_msg'] = "You must be logged in to report an issue.";
	header ( 'Location: /index.php' );
} elseif (filter_has_var(INPUT_GET, 'd_id') && Devices::regexDID(filter_has_var(INPUT_GET, 'd_id'))) {
	$device = new Devices(filter_input(INPUT_GET, 'd_id'));
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['srBtn'])){
	//device, sl_id, notes
	$devices = new Devices(filter_input(INPUT_POST, 'devices'));
	$sl_id = filter_input(INPUT_POST, 'sl_id');
	$notes = filter_input(INPUT_POST, 'notes');
	
	$msg = Service_call::call($staff, $devices, $sl_id, $notes);
	if (is_string($msg)){
		//display error message
		$errorMsg = $msg;
	} else {
		header("Location:sr_history.php?d_id=".$devices->d_id);
	}
}
?>
<title><?php echo $sv['site_name'];?> Report Issue</title>
<body>
	<div id="page-wrapper">
		<div class="row">
			<div class="col-lg-12">
				<h1 class="page-header">Report Issue</h1>
			</div>
			<!-- /.col-lg-12 -->
		</div>
		<!-- /.row -->
		<div class="row">
			<div class="col-lg-10">
				<?php if (isset($errorMsg)) { ?>
					<div class="alert alert-danger" role = "alert" id="errordiv">
						<?php echo $errorMsg; ?>
					</div>
				<?php } else { ?>
					<div class="alert alert-danger" role = "alert" id="errordiv" style="display:none;">
						<p id="errormessage"></p>
					</div>
				<?php } ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<i class="fas fa-fire fa-fw"></i> Report New Issue
					</div>
					<div class="panel-body">
						<form name="scform" method= "POST"  action="" onsubmit="return validateForm();">
							<table class="table table-striped">
								<tr>
									<td class="col-sm-2">Device Group</td>
									<td class="col-sm-10">
										<select class="form-control" name="devGrp" id="devGrp" onChange="change_group()" >
											<option value="" hidden> Select Group</option>
											<?php
											$device_groups = DeviceGroup::all_device_groups();
											if(!$device_groups) echo "<option selected>ERROR</option>";
											else {
												foreach ($device_groups as $id => $name) {
													$selected = (is_object($device) && $device->device_group->dg_id == $id) ? "selected" : "";
													echo("<option value='$id' $selected>$name</option>");
												}
											}
											?>
										</select>
									</td>
								</tr>
								<tr>
									<td>Device</td>
									<td>
										<div class="input-group">
											<span class="input-group-addon" id="dot_span">
												<?php if (is_object($device)){
													Devices::printDot($staff, $device->d_id);
												} else { ?>
													<i class='fas fa-circle fa-lg' style='color:gainsboro'></i>
												<?php } ?>
											</span>
											<select class="form-control" name="devices" id="devices" tabindex="2" onchange="change_dot()">
												<?php if (is_object($device)){
													echo ("<option value='".$device->d_id."'>".$device->name."</option>");
												} else { ?>
													<option value ="" hidden> Select Group First</option>
												<?php } ?>
											</select>
										</div>
									</td>
								</tr>
								<tr>
									<td>Service Level</td>
									<td>
										<div class="input-group">
											<span class="input-group-addon">
												<?php if ($sl_id != "") {
													Service_lvl::getDot($sl_id);
												} else { ?>
													<i class='fas fa-circle fa-lg' style='color:gainsboro' id="sl_dot"></i>
												<?php } ?>
											</span>
											<select class="form-control" name="sl_id" id="sl_id" onchange="change_sldot()">
												<option value = "" hidden> <i class="fas fa-fire"/>Select</option>
												<?php //List available Service Levels
												$slArray = Service_lvl::getList();
												foreach ($slArray as $sl){
													if ($sl_id == $sl->getSl_id()){
														echo("<option value='".$sl->getSl_id()."' selected>".$sl->getMsg()."</option>");
													} else {
														echo("<option value='".$sl->getSl_id()."'>".$sl->getMsg()."</option>");
													}
												}
												?>
											</select>
										</div>
									</td>
								</tr>
								<tr>
									<td>Notes:</td>
									<td>
										<textarea class="form-control" id="notes" rows="5" name="notes"
											style="resize: none"><?php echo $notes; ?></textarea>
									</td>
								</tr>
							</table>
					</div>
					<div class="panel-footer clearfix">
						<div class="pull-right">
							<input class="btn " type="reset" value="Reset">
							<input class="btn btn-primary" type="submit" name="srBtn" value="Submit">
						</div>
					</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /.col-lg-8 -->
	</div>
	<!-- /.row -->
	<!-- /#page-wrapper -->
</body>
<script type="text/javascript">
	function change_sldot(){
		var sl_id = document.getElementById("sl_id").value;
		var sl_dot = document.getElementById("sl_dot");
		if(sl_id == 1) {
			sl_dot.style.color = "green";
			sl_dot.classList.remove("fa-times");
			sl_dot.classList.add("fa-circle");
		} else if(sl_id < 7) {
			sl_dot.style.color = "yellow";
			sl_dot.classList.remove("fa-times");
			sl_dot.classList.add("fa-circle");
		} else {
			//7+ is Non-useable
			sl_dot.style.color = "red";
			sl_dot.classList.add("fa-times");
		}
	}
	
	function validateForm(){
		if (!stdRegEx("devices", /^\d+/, "Select Device")){
			return false;
		}
		if (!stdRegEx("notes", /^.{10,}/, "Please State the Issue")){
			return false;
		}
		if (!stdRegEx("sl_id", /^\d+/, "Select the Severity of the Issue")){
			return false;
		}
	}
	
	function change_group(){
		if (window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp = new XMLHttpRequest();
		} else {
			// code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				document.getElementById("devices").innerHTML = this.responseText;
			}
		};
		xmlhttp.open("GET","/pages/sub/getDevices.php?dg_id="+ document.getElementById("devGrp").value, true);
		xmlhttp.send();
	}
</script>
<?php
// Standard call for dependencies
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/pages/footer.php');
?>