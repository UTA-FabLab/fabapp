<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$error_msg = "";

if (!$staff || $staff->getRoleID() < $sv['LvlOfStaff']){
    //Not Authorized to see this Page
    $_SESSION['error_msg'] = "You are unable to view this page.";
    header('Location: /index.php');
    exit();
}

if (isset($staff) && $staff->getRoleID() >= $sv['LvlOfStaff']){
    $q_id = filter_input(INPUT_GET , 'q_id', FILTER_VALIDATE_INT, false);
    if (is_int($q_id) && $result = $mysqli->query("
        SELECT `Op_email` , `Op_phone`, `Operator` , `Devgr_id`
        FROM `wait_queue`
        WHERE `Q_id`= $q_id AND `valid`='Y';
    ")) {
        if ($result->num_rows == 1){
            $row = $result->fetch_assoc();
            $old_operator = $row['Operator'];
            $Op_email = $row['Op_email'];
            $Op_phone = $row['Op_phone'];
            $devgr_id = $row['Devgr_id'];
        } else {
            $error_msg = "Unable to find Queue ID.";
        }
    } else {
        $error_msg = "Invalid Queue ID.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['timerBtn'])) {
    Notifications::setLastNotified($q_id);
    if ($_REQUEST['loc'] == 0) {
        header("Location:/index.php");
    } elseif ($_REQUEST['loc'] == 1) {
        header("Location:/pages/wait_ticket.php");
    }
    $_SESSION['success_msg'] = "Timer has been initiated";  
}


//Use the Unique Identifier Q_id to find and update record
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitBtn'])) {
    $em = filter_input(INPUT_POST,'op-email');
    $ph = filter_input(INPUT_POST, 'op-phone');
    $new_operator = filter_input(INPUT_POST,'operator');
    $carrier_name = filter_input(INPUT_POST,'carrier_name');
    $wait_status = Wait_queue::updateContactInfo($q_id, $ph, $em, $carrier_name, $old_operator, $new_operator, $devgr_id);
    if ($wait_status === 0) {
        if ($_REQUEST['loc'] == 0) {
            header("Location:/index.php");
        } elseif ($_REQUEST['loc'] == 1) {
            header("Location:/pages/wait_ticket.php");
        }
        $_SESSION['success_msg'] = "Contact Information Updated";
    } else {
        $_SESSION['error_msg'] = $wait_status;
        header("Location:/pages/waitUserInfo.php?q_id=$q_id&loc=$_REQUEST[loc]");

    }
}
?>
<title><?php echo $sv['site_name'];?> Wait Queue User Info</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Wait Queue User Info</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-8">
            <?php if ($error_msg != "") { ?>
                <div class="panel panel-danger">
                    <div class="panel-heading">
                        <i class="fa fa-address-card" aria-hidden="true"></i> Error With Contact Info
                    </div>
                    <div class="panel-body">
                        <?php echo $error_msg;?>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php } else { ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-address-card" aria-hidden="true"></i> Update User Info : <?php echo "Queue ID $q_id"?>
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped table-hover"><form name="wqform" id="wqform" autocomplete="off" method="POST" action="">
                            <tr>
                                <td><b data-toggle="tooltip" data-placement="top" title="Operator ID">Operator: </b></td>
                                <td><input type="text" name="operator" id="operator" class="form-control" value="<?php echo $old_operator?>" placeholder="1000000000" maxlength="10" size="10"/></td>
                            </tr><tr>
                                <td><b data-toggle="tooltip" data-placement="top" title="email contact information">Email Address: </b></td>
                                <td><input type="text" name="op-email" id="op-email" class="form-control" value="<?php echo $Op_email?>" placeholder="example@mail.com" maxlength="100" size="10"/></td>
                            </tr>
                            <tr>
                                <td><b data-toggle="tooltip" data-placement="top" title="phone contact information">Phone Number: </b></td>
                                <td>
                                    <div class="col-md-6">
                                        <input type="text" name="op-phone" id="op-phone" class="form-control" value="<?php echo $Op_phone?>" placeholder="1234567890" maxlength="10" size="10"/>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="form-control" name="carrier_name" id="carrier_name" tabindex="1">
                                            <option value="" disabled selected>Select Phone Carrier</option>
                                            <?php // Load all of the device groups that are being waited for - signified with a 'DG' in front of the value attribute
                                                if ($result = $mysqli->query("
                                                        SELECT `provider`
                                                        FROM `carrier` 
                                                        WHERE 1;
                                                ")) {
                                                    while ( $rows = mysqli_fetch_array ( $result ) ) {
                                                        // Create value in the form of DG_dgID-dID
                                                        echo "<option value=". $rows ['provider'] .">" . $rows ['provider'] . "</option>";
                                                    }
                                                } else {
                                                    die ("There was an error loading the phone carriers.");
                                                } ?> 
                                        </select>
                                    </div>
                                </td>
                            </tr>
                            <tfoot>
                                <tr>
                                    <td colspan="2"><div class="pull-right"><input type="submit" name="submitBtn" value="Submit" class="btn btn-primary"></div></td>
                                </tr>
                            </tfoot>
                        </form>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php } ?>
        </div>
        <?php if (empty($Op_phone) && empty($Op_email)) { ?>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-clock fa-fw"></i> Start Secondary Timer
                    </div>
                    <!-- /.panel-heading -->
                    <div class="panel-body">
                        <div style="text-align: center">
                            <form method="post" action="" onsubmit="return startSecondaryTimer()" >
                            <button class="btn btn-warning" name="timerBtn">
                                Start Timer
                            </button>
                            </form>
                        </div>
                    </div>
                <!-- /.panel-body -->
                </div>
            </div>
        <?php } ?>
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->

<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php'); 
?>

<script type="text/javascript">
    function startSecondaryTimer(){
        if (confirm("You are about to start this user's secondary timer, please make sure to contact the user by calling out their wait queue number at the FabLab. Click OK to continue or CANCEL to quit.")){
            return true;
        }
        return false;
    }   
</script>