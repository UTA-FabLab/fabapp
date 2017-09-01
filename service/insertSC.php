<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

if ($staff && $staff->getRoleID() < 7){
    //Not Authorized to see this Page
    header('Location: /index.php');
}

$fieldReport= "Your service call has been submitted.";

$staffID = $staff->getOperator();
$devID = $_POST['deviceList'];
$slID = $_POST['optradio'];
$solvedSt = "N";

$scnotes = $_POST['notes'];
$sctime = date("Y-m-d h:i:s");

//unset($_POST);
//unset($_POST['deviceList']);
//unset($_POST['optradio']);
//unset($_POST['notes']);

$toInsert = "INSERT INTO `service_call` (`staff_id`, `d_id`, `sl_id`, `solved`, `sc_notes`, `sc_time`)
            VALUES ('$staffID', '$devID', '$slID', '$solvedSt', '$scnotes', CURRENT_TIME());";
if(!$result = $mysqli->query($toInsert)){
    $fieldReport = "Error in submitting";
    //$fieldReport = $mysqli->error;
} 


header("refresh:5; url=/service/newTicket.php");
//header("Location: /service/newTicket.php");
?>
<title><?php echo $sv['site_name'];?> Report Service Call</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?php echo $fieldReport; ?></h1>
            
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-12">
        <p>This page will be redirected in 10 seconds </p>
        
        
        </div>
        <!-- /.col-lg-4 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>