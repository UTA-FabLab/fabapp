<?php

//////// ALPHA TRAINING MODULE SEARCH ////////
// Provide a search field by operator to query all TMs a person has completed
// Edit button next to each TM- allow revocation, require reason, timestamp, staff_id. Restrict action to $sv['minRoleTrainer']
// Search by TMs and display all users. Show if current, date, and staff that issued the cert, etc

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

// staff clearance
if (!$staff || $staff->getRoleID() < $sv['LvlOfStaff']){
    //Not Authorized to see this Page
    header('Location: /index.php');
    $_SESSION['error_msg'] = "Insufficient role level to access, You must be a Trainer.";
}

// fire off modal & timer
if($_SESSION['type'] == 'success'){
    echo "<script type='text/javascript'> window.onload = function(){success()}</script>";
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['teBtn'])) {
    $id_number = filter_input(INPUT_POST, 'get_training_field');
    $trainings = IndividualsCertificates::get_individuals_trainings($id_number);
} elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_revoke'])) {
	$expiration = filter_input(INPUT_POST, 'date');
	$reason = filter_input(INPUT_POST, 'reason');
	$tme_key = filter_input(INPUT_POST, 'tme_key');
	$staff_id = $staff->getOperator();
	echo "<script> console.log($staff_id); </script>";
	if(IndividualsCertificates::revoke_training($expiration, $reason, $staff_id, $tme_key)) {
        $_SESSION['type'] = 'tc_success';
        $_SESSION['training_operator'] = $id_number;
        header("Location:tm_search.php");
    } else {
        echo "<script type='text/javascript'> window.onload = function(){goModal('Error',\"Unable to revoke training\", false)}</script>";
	}
} elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['restore_training'])) {
	$tme_key = filter_input(INPUT_POST, 'restore_training');
	$staff_id = $staff->getOperator();
	echo "<script> console.log($staff_id); </script>";  //TESTING
	if(IndividualsCertificates::restore_training($staff_id, $tme_key)) {
        $_SESSION['type'] = 'tc_success';
        $_SESSION['training_operator'] = $id_number;
        header("Location:tm_search.php");
    } else {
        echo "<script type='text/javascript'> window.onload = function(){goModal('Error',\"Unable to restore training\", false)}</script>";
	}

}

// Reset Forum and prevent null submission
if ($_SESSION['type'] && $_SESSION['type'] == 'tc_success' && $_SERVER["REQUEST_METHOD"] != "POST"){
    echo "<script type='text/javascript'> window.onload = function(){goModal('Success','Training Revoked', true)}</script>";
    echo "<script> console.log('I AM FREAKING TRYING TO DO THIS CRAP11111111!');";
    //display current training module && description
    $trainings = IndividualsCertificates::get_individuals_trainings($_SESSION['training_operator']);
    echo "<script> console.log('I AM FREAKING TRYING TO DO THIS CRAP!');";
    
    //clear type to prevent refresh
    $_SESSION['type'] = '';
} 
?>


<!-- create page -->
<title><?php echo $sv['site_name'];?> Individual Certificates</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">Individual Certificates</h1>
        </div>
        <!-- /.col-md-12 -->
    </div>

	<!-- search box -->
    <div class="panel panel-default">
        <div class="panel-heading">
            <i class="fas fa-book fa-lg"></i> Look Up Completed Trainings
        </div>
        <div class="panel-body">
            <form name="teForm" method="POST" action="" autocomplete="off" onsubmit="return stdRegEx('get_training_field', /^\d{10}$/, 'Please enter ID #2')">
                <div class="input-group custom-search-form">
                    <input type="text" name="get_training_field" id="get_training_field" class="form-control" placeholder="Enter ID #" maxlength="10" size="10"
                           value="<?php if (isset($id)) echo $id; ?>">
                    <span class="input-group-btn">
                    <button class="btn btn-default" type="submit" name="teBtn">
                        <i class="fas fa-search"></i>
                    </button>
                    </span>
                </div>
            </form>
            <?php if(isset($trainings)){ ?>
                <table id="teTable" class="table table-striped">
                    <thead>
                        <tr>
							<th class='col-md-2' align='center'>Date Completed</th>
							<th class='col-md-2' align='center'>Staff Approval</th>
							<th class='col-md-3'>Training Module</th>
                  			<?php if($staff && $staff->getRoleID() >= $sv['minRoleTrainer']) {
                  				echo "<th class='col-md-5'>Revoke</th>" ;
                  			} else {
                  				echo "<th class='col-md-5'>Validity</th>";
                  			} ?>
                        </tr>
                    </thead>
                    <?php
                    // change to for loop
                    for ($x = 0; $x < count($trainings); $x++){
                        $row = $trainings[$x];
                        echo "<tr";
                        	if($row['current'] == 'N') echo " style='background-color:#ffcccc;'";  // highlight if revoked; '>' in next line is very important
							echo ">";
							$issuer = Users::withID($row['staff_id']);
							?>
							<td style="padding-left: 15px;">  <!-- date completed -->
								<div class="btn-group">
								   <button type="button" class="btn btn-default btn-s dropdown-toggle" data-toggle="dropdown">
										<?php echo "<i class='far fa-clock fa-lg' title='".date($sv['dateFormat'], strtotime($row['completed']))."'></i>"; ?>
	                                </button>
	                                <ul class="dropdown-menu pull-right" role="menu">
										<li style="padding-left: 5px;"> <?php echo date($sv['dateFormat'], strtotime($row['completed'])); ?> </li>
	                                </ul>
	                            </div>
                            </td>
	                        <td style="padding-left: 15px;">  <!-- approved by -->
	                            <div class="btn-group">
	                                <button type="button" class="btn btn-default btn-s dropdown-toggle" data-toggle="dropdown">
										<?php echo "<i class='".$issuer->getIcon()." fa-lg' title='".$issuer->getOperator()."'></i>"; ?>
	                                </button>
	                                <ul class="dropdown-menu pull-right" role="menu">
										<li style="padding-left: 5px;"><?php echo $issuer->getOperator();?></li>
	                                </ul>
	                            </div>
                            </td>
                            <td>  <!-- training module description -->
                                <?php echo $row['title']; ?>
                                <div class="btn-group">
									<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
										<span class="fas fa-info-circle" title="Desc"></span>
									</button>
									<ul class="dropdown-menu pull-right" role="menu">
										<li style="padding-left: 5px;"><?php echo $row['tm_desc'];?></li>
									</ul>
                                </div>
                            </td>
                            <td>
		                        <table> <tr> <td class='col-sm-2'>
	                            <?php if($row['current'] == 'N') { ?>
	                            	<form method="post">
		                            	<button type='submit' value=<?php echo "'".$row['tme_key']."'"; ?> class='btn btn-success' name='restore_training' >Restore
		                            	</button>
		                            </form>
	                            	<?php if($staff && $staff->getRoleID() >= $sv['minRoleTrainer']) { ?>
			                            	<td class='col-sm-2'>	
			                            		<div class="btn-group">
													<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
														Time Frame
													</button>
													<ul class="dropdown-menu pull-right" role="menu">
														<li style="padding: 5px;"><?php echo "ALTERED DATE: ".$row['altered_date'].
																	"\nEXPIRATION DATE: ".$row['expiration_date'];?></li>
													</ul>
			                                	</div>
			                                </td>
			                                <td class='col-sm-2'>
			                                	<div class="btn-group">
													<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
														Revoker
													</button>
													<ul class="dropdown-menu pull-right" role="menu">
														<li style="padding: 5px;"><?php echo $row['altered_by'];?></li>
				                                    </ul>
			                                	</div>
			                                </td>
			                                <td class='col-sm-1'>
			                                	<div class="btn-group">
				                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
				                                    	Reason
				                                    </button>
													<ul class="dropdown-menu pull-right" role="menu">
														<li style="padding: 5px;"><?php echo $row['altered_notes'];?></li>
													</ul>
			                                	</div>
			                                </td>
	                                <?php }
	                            // not revoked and is staff
	                            } elseif($staff && $staff->getRoleID() >= $sv['minRoleTrainer']) { ?>
	                            	<button type='button' value='Revoke' class='btn btn-danger' <?php echo "onclick='revoke_training(".$row['tme_key'].")'" ?> >Revoke
	                            	</button>
	                            	<!-- echo "<button type='button' value='Revoke' class='btn btn-danger' onclick='revoke_training(".$row['tme_key'].")' >Revoke</button>"; -->
								<?php } ?>
								 <!-- echo "<tr onclick='edit_sv(".$sva->getId().")'>"; -->
								</td> </tr> </table>
							</td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } ?>
        </div> <!-- /.panel-body -->
    </div> <!-- /.panel -->
</div>

<!-- modal to change info -->
<div id="revokeModal" class="modal">
</div>

<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>

<script type="text/javascript">
    $('#teTable').DataTable();


    function revoke_training(training_ID){
        if (Number.isInteger(training_ID)){
            if (window.XMLHttpRequest) {
                // code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp = new XMLHttpRequest();
            } else {
                // code for IE6, IE5
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("revokeModal").innerHTML = this.responseText;
                }
            };
            xmlhttp.open("GET", "sub/revoke_training.php?training_ID=" + training_ID, true);
            xmlhttp.send();
        }
        $('#revokeModal').modal('show');
    }


 </script>