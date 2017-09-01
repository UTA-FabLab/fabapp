<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
 if ($staff) if($staff->getRoleID() < 7){
    //Not Authorized to see this Page
    header('Location: /index.php');
}
?>
<title><?php echo $sv['site_name'];?> Service Replies</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Service Replies</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-calendar-check-o fa-fw"></i> Replies
                </div>
                <div class="panel-body" style="max-height: 250px; overflow-y: scroll;">
                	<table id='replies' class="table table-striped table-bordered" border='1'>
                    <?php
                    $exists = $mysqli->query("SELECT * FROM service_call WHERE sc_id = " . $_GET['service_call_id']);
                    if($exists->num_rows > 0){
	                    if ($result = $mysqli->query("
	                    SELECT reply.sr_id, reply.staff_id, reply.sr_time, reply.sr_notes, service_call.d_id FROM reply LEFT JOIN service_call
						ON (reply.sc_id=service_call.sc_id)
						WHERE service_call.sc_id = " . $_GET['service_call_id'] . "
						ORDER BY reply.sr_id ASC")){
							if ($result->num_rows > 0){
		                    		echo "<thead><tr>";
			                    		//loop thru the field names to print the correct headers
			                    		echo "<th style='text-align:center' width=\"" . 100/(mysqli_num_fields($result)+3) . "%\">On</th>";
			                    		echo "<th style='text-align:center' width=\"" . 100/(mysqli_num_fields($result)+3) . "%\">By</th>";
			                    		echo "<th style='text-align:center' width=\"" . 4*(100/(mysqli_num_fields($result)+3)) . "%\">Service Reply Notes</th>";
		                    		echo "</thead></tr>";
		                    			
		                    		//display the data
		                    		echo "<tbody>";
		                    		while ($cols = mysqli_fetch_array($result,MYSQLI_ASSOC)){
		                    			echo "<tr>";
		                    			for($i = 0; $i < mysqli_num_fields($result); $i++){
		                    				switch($i){
		                    					case 0:		//On
		                    						echo "<td align='center' style='padding: 15px'>" . date('M d g:i a', strtotime($cols['sr_time'])) . "</td>" ;
		                    					break;
		                    					case 1:		//By
		                    						if($staffIcon = $mysqli->query("
												    SELECT icon
												    FROM users
												    WHERE operator = " . $cols['staff_id'])){
													    if($staffIcon->num_rows > 0){
													    	$staffIcon = mysqli_fetch_array($staffIcon, MYSQLI_ASSOC);
													    	echo "<td align='center' style='padding: 15px'><i class='fa fa-" . $staffIcon['icon'] . " fa-lg fa-fw'></i></td>";
													    }
													    else
													    	echo "<td align='center' style='padding: 2px;'>Invalid User ID</td>";
		                    						}
		                    						else
		                    							echo "<td align='center' style='padding: 2px;'>Invalid User ID</td>";
		                    					break;
		                    					case 2:		//Notes
		                    						echo "<td align='left' style='padding: 10px;'>" . $cols['sr_notes'] . "</td>";
		                   						break;
		                    				}
		                    			}
		                    			echo "</tr>";
		                    		}
		                    	}else{
		                    		echo "<tr><td align = 'center'>No history to display!</td></tr>";
		                    	}
		                    }
						}
						else 
							echo "<tr><td align = 'center'>Ticket number: " . $_GET['service_call_id']. " does not exist!</td></tr>";?>
						</tbody></table>
                   </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
        </div>
    </div>
    <!-- /.row -->
    <?php //What is the assumption?  Who are we qualifying for?
	if($staff->getRoleID() != 8 && $staff->getRoleID() != 9 && $exists->num_rows > 0){ ?>
    <div class="row">
        <div class="col-md-12">
        <div class="alert alert-danger" role = "alert" id="errordiv" style="display:none;"><p id="errormessage"></p></div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-ticket fa-fw"></i> Update Ticket
                </div>
                <div class="panel-body">
				<form name="reply" method= "POST" action="/service/insertReply.php" onsubmit="return validateReply();">
				<table class="table table-striped table-bordered">
					<tr>
						<td>Service Call Number:</td>
						<td><?php echo "<input type='text' name='service_call_number' value=" . $_GET['service_call_id'] . " readonly>"?></td>
					</tr>
					<tr>
						<td>Device Name:</td>
						<td> <?php
							echo "<select class='form-control' name='dev' id = 'dev'>";
							$default_value = "SELECT `device_desc`, `d_id`, `dg_id` FROM `devices` AS `d_id` WHERE `d_id` = (SELECT `d_id` FROM `service_cal`l AS `d_id` WHERE `sc_id` = ". $_GET['service_call_id'] . ")";
							if ($default = $mysqli->query($default_value)){
								$default = mysqli_fetch_array($default);
								$list_elements = "SELECT `d_id`, `device_desc` FROM `devices` WHERE `dg_id` = " . $default['dg_id'] . " ORDER BY `device_desc` ASC";
								echo "<option selected value=" . $default['d_id'] . ">" . $default['device_desc'] . "</option>";
								if($list = $mysqli->query($list_elements)){
									while ($rows = mysqli_fetch_array($list,MYSQLI_ASSOC)) {
										if($rows['d_id'] == $default['d_id'])
											continue;
										else
											echo "<option value=" . $rows['d_id'] . ">" . $rows['device_desc'] . "</option>";
									}
								}
								else
									echo "<option value=0>Error Loading Device Group</option>";
								echo "</select>";
								
							}
							else
								echo "There was an error loading device description";						
						?> </td>
					</tr>
					<tr>
						<td>Service Level</td>
						<td align = 'center'><?php
						if($options = $mysqli->query("SELECT sl_id,msg FROM service_lvl")){
							if($status = $mysqli->query("SELECT sl_id FROM service_call WHERE sc_id = " . $_GET['service_call_id'])){
								$status = mysqli_fetch_array($status);
								while($row = $options->fetch_array(MYSQLI_ASSOC)){
									echo "<label class='radio-inline'><input type='radio' name='service_level' value='" . $row['sl_id'] . "'" . (($status['sl_id'] == $row['sl_id']) ? "checked='checked'" : "") . ">" . $row['msg'] . "</label>";
								}
								echo "<label class='radio-inline'><input type='radio' name='service_level' value='100'>Completed</label>";
							}
							else
								echo "Error loading Service Call";
						}
						else
							echo "Error loading Service Levels";
						?></td>
					</tr>
					<tr>
						<td>Notes:</td>
						<td><div class="form-group">
						<textarea class="form-control" rows="5" name="notes"  id="notes" style="resize: none"></textarea>
						</div></td>
					</tr>
					<tr>
						<td>Staff ID</td>
						<td><?php echo $staff->getOperator();?></td>
					</tr>
					<tr>
						<td>Current Date</td>
						<td><?php echo $date = date("m/d/Y h:i a", time());?></td>
					</tr>
					<tr>
						<td><input class="btn btn-primary pull-right" type="reset" value="Reset"></td>
						<td><input class="btn btn-primary" type="submit" value="Submit"></td>
					</tr>
				</table>
				</form>
                </div>
            </div>
        </div>
        <!-- /.col-lg-8 -->
    </div>
    <?php }?>
</div>
<!-- /#page-wrapper -->

<script type="text/javascript">
	function validateLevel(radios){
		for(i = 0; i< radios.length ; ++i)
			if(radios[i].checked)
				return true;
		return false;
	}
	function validateReply(){
		var dev = document.getElementById("dev").value;
		var radiocheck = false;
		if(validateLevel(document.forms["reply"]["service_level"]))
			radiocheck = true;
		var notes = document.getElementById("notes").value;
		if(dev == "" ||  notes == "" || radiocheck == false){
			document.getElementById('errordiv').style.display = 'block';
			document.getElementById("errormessage").innerHTML = "All fields are required";			
			return false;
		}
	}
	window.onload = function() {
	   	$('#replies').DataTable();
    };
</script>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>