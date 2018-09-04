<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$error_msg = "";

if (isset($staff) && $staff->getRoleID() >= $sv['LvlOfStaff']){
    $q_id = filter_input(INPUT_GET , 'q_id', FILTER_VALIDATE_INT, false);
    if (is_int($q_id) && $result = $mysqli->query("
        SELECT `Op_email` , `Op_phone`, `operator`
        FROM `wait_queue`
        WHERE `Q_id`= $q_id AND `valid`='Y';
    ")) {
        if ($result->num_rows == 1){
            $row = $result->fetch_assoc();
            $operator = $row['operator'];
            $Op_email = $row['Op_email'];
            $Op_phone = $row['Op_phone'];
        } else {
            $error_msg = "Unable to find Queue ID.";
        }
    } else {
        $error_msg = "Invalid Queue ID.";
    }
}

//Use the Unique Identifier Q_id to find and update record
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitBtn'])) {
    $em = filter_input(INPUT_POST,'op-email');
    $ph = filter_input(INPUT_POST, 'op-phone');
    $status = Wait_queue::updateContactInfo($q_id, $ph, $em);
    if ($status === 0) {
        $_SESSION['success_msg'] = "Contact Information Updated";
        header("Location:/index.php");
    } else {
        $_SESSION['error_msg'] = $status;
        header("Location:/pages/updateContact.php?q_id=$q_id");
        
    }
}
?>
<title><?php echo $sv['site_name'];?> Update Contact Info</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Update Contact Info</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-8">
            <?php if ($error_msg != "") { ?>
                <div class="panel panel-danger">
                    <div class="panel-heading">
                        <i class="far fa-bell" aria-hidden="true"></i> Error With Contact Info
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
                        <i class="far fa-bell" aria-hidden="true"></i> Update Contact Info : <?php echo "Q_ID $q_id - $operator"?>
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped table-hover"><form name="wqform" id="wqform" autocomplete="off" method="POST" action="">
                            <tr>
                                <td><b href="#" data-toggle="tooltip" data-placement="top" title="email contact information">Email Address: </b></td>
                                <td><input type="text" name="op-email" id="op-email" class="form-control" value="<?php echo $Op_email?>" placeholder="example@mail.com" maxlength="100" size="10"/></td>
                            </tr>
                            <tr>
                                <td><b href="#" data-toggle="tooltip" data-placement="top" title="phone contact information">Phone Number: </b></td>
                                <td><input type="text" name="op-phone" id="op-phone" class="form-control" value="<?php echo $Op_phone?>" placeholder="1234567890" maxlength="10" size="10"/></td>
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
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->

<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>