<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$error_msg = "";

if (!$staff || $staff->getRoleID() < 10){
    //Not Authorized to see this Page
    $_SESSION['error_msg'] = "You are unable to view this page.";
    header('Location: /index.php');
    exit();
} else {
    $d_id = filter_input(INPUT_GET , 'd_id', FILTER_VALIDATE_INT, false);
    if (is_int($d_id) && $result = $mysqli->query("
        SELECT `d`.`device_desc`, `d`.`d_duration`, `d`.`base_price`, `d`.`public_view`, `d`.`dg_id`, `d`.`url`, `dg`.`dg_desc`
        FROM `devices` `d`, `device_group` `dg`
        WHERE `d`.`d_id`=$d_id AND `d`.`dg_id`=`dg`.`dg_id`;
    ")) {
        if ($result->num_rows == 1){
            $row = $result->fetch_assoc();
            $d_name = $row['device_desc'];
            $d_duration = $row['d_duration'];
            $d_hour = substr($d_duration,0,2);
            $d_minute = substr($d_duration,3,2);
            $dg_id = $row['dg_id'];
            $d_url = $row['url'];
            $d_price = $row['base_price'];
            $dg_desc = $row['dg_desc'];
            $d_view = $row['public_view'];
        } else {
            //Not Authorized to see this Page
            $_SESSION['error_msg'] = "Unable to find device.";
            header('Location: /admin/manage_device.php');
            exit();
        }
    } else {
        //Not Authorized to see this Page
        $_SESSION['error_msg'] = "Invalid device.";
        header('Location: /admin/manage_device.php');
        exit();
    }
}

if (isset($_SESSION['d_msg']) && $_SESSION['d_msg'] == 'success'){
    $d_msg = ("<div class='col-lg-6\10'><div style='text-align: center'>
            <div class='alert alert-success'>
                Successfully edited device's information!
            </div> </div> </div>");
    unset($_SESSION['d_msg']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitBtn'])) {
$d_name1 = filter_input(INPUT_POST,'device_name');
    $d_hour = filter_input(INPUT_POST,'hours');
    $d_minute = filter_input(INPUT_POST,'minutes');
    $d_duration1 = "".$d_hour.":".$d_minute.":00"; 
    if ((filter_input(INPUT_POST,'device_group_id')) == 10101010){
        $dg_id1 = $dg_id;
    } else {
        $dg_id1 = filter_input(INPUT_POST,'device_group_id'); 
    }
    $d_url1 = filter_input(INPUT_POST,'device_url');     
    $d_price1 = filter_input(INPUT_POST,'device_base_price');
    if ((filter_input(INPUT_POST,'device_public_view')) == 10101010){
        $d_view1 = $d_view;
    } else {
        $d_view1 = filter_input(INPUT_POST,'device_public_view'); 
    }
    
    if(isset($_POST['device_group_id']) && preg_match('/^[0-9]+$/i', $_POST['hours']) && preg_match('/^[0-9]+$/i', $_POST['minutes']) && preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['device_name']) && preg_match('/^[0-9\.]+$/i', $_POST['device_base_price'])){
        
        $update_status = Devices::updateDevice($d_id, $d_name1, $d_duration1, $d_price1, $dg_id1, $d_url1, $d_view1);
        if ($update_status == 1) {
            $_SESSION['success_msg'] = "Device has been successfully updated.";
            header("Location:/admin/manage_device.php");
        } else {
            $_SESSION['error_msg'] = "Error Updating Device #".$d_id." information";
            header("Location:/admin/manage_device.php");  
        }
    } else {
        if (!isset($_POST['device_group_id'])) {
            $d_msg = $d_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-10'><div class='alert alert-danger'>
                        You must properly fill the Device creation 'Select Device Group' row.
                    </div> </div> </div> </div>");
        } if (!preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['device_name'])) {
            $d_msg = $d_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-10'><div class='alert alert-danger'>
                        You must properly fill the Device creation 'Device Name' row: '".$_POST['device_name']."'
                    </div> </div> </div> </div>");
        } if (!preg_match('/^[0-9\.]+$/i', $_POST['device_base_price'])) {
            $d_msg = $d_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-10'><div class='alert alert-danger'>
                        You must properly fill the Device creation 'Base Price' row: '".$_POST['device_base_price']."'
                    </div> </div> </div> </div>");
        } if (!preg_match('/^[0-9]+$/i', $_POST['hours'])) {
            $d_msg = $d_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6'><div class='alert alert-danger'>
                        You must properly fill the Device creation 'Device Duration - Hours' row.
                    </div> </div> </div> </div>");
        } if (!isset($_POST['minutes'])) {
            $d_msg = $d_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-10'><div class='alert alert-danger'>
                        You must properly fill the Device creation 'Device Duration - Minutes' row.
                    </div> </div> </div> </div>");
        }
    }
}

?>
<title><?php echo $sv['site_name'];?> Edit Device</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Edit Device&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;<input class="btn btn-default" type="submit" name="submitBtn1" id="submitBtn1" onclick="location.href = '/admin/manage_device.php';" value="Back to Manage Device"></h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    
    <!-- Success/Error Message -->
    <div class="row">
        <?php if($d_msg != "") { ?>
        <div style='text-align: center'>
            <?php echo $d_msg; ?>
        </div>
        <?php } ?>
    </div>
    
    <div class="row">
        <div class="col-md-10">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-edit" aria-hidden="true"></i> Update Device Info: <?php echo $d_name;?>
                </div>
                <form name="edform" id="edform" autocomplete="off" method="POST" action="">
                    <div class="panel-body">
                        <table class="table table-bordered table-striped table-hover">
                            <tr>
                                <td>
                                    <b data-toggle="tooltip" data-placement="top" title="Device Group">Device Group: </b><t>(Current Device Group: </t><b><?php echo $dg_desc;?></b><t>)</t>
                                </td>
                                <td>
                                    <select class="form-control" name="device_group_id" id="device_group_id" tabindex="1">
                                        <option value=10101010 selected>No Change</option>
                                        <?php if($dgs = DeviceGroup::all_device_groups()){
                                            foreach($dgs as $dgs_id => $dgs_desc){
                                                echo("<option value='$dgs_id'>$dgs_desc</option>");
                                            }
                                        } else {
                                            echo("<option value=''>Device list Error - SQL ERROR</option>");
                                        }?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <b data-toggle="tooltip" data-placement="top" title="Device Name">Device Name: </b>
                                    <button type="button" style="background-color:#D3D3D3" class="btn fas fa-info" onclick="d_name_Info()"></button>
                                </td>
                                <td><input type="text" name="device_name" id="device_name" class="form-control" value="<?php echo $d_name?>" placeholder="Device Name" size="10"/></td>
                            </tr>
                            <tr>
                                <td><b data-toggle="tooltip" data-placement="top">Public View</b></td>
                                <td><select class="form-control" name="device_public_view" id="device_public_view" tabindex="1">
                                      <option value=10101010 selected>No Change</option>
                                      <option value="Y">Yes</option>
                                      <option value="N">No</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <b data-toggle="tooltip" data-placement="top">Base Price:</b>
                                    <b class="pull-right" data-placement="bottom">$</b>
                                </td>
                                <td><input type="number" name="device_base_price" id="device_base_price" class="form-control" max="99.99" min="0.00" value="<?php echo $d_price?>" step="0.01" tabindex="1"/></td>
                            </tr>
                            <tr>
                                <td>
                                    <b data-toggle="tooltip" data-placement="top" title="Device Duration">Device Duration: </b>
                                    <button type="button" style="background-color:#D3D3D3" class="btn fas fa-info" onclick="durationInfo()"></button>
                                </td>
                                <td>
                                    <input type="number" name="hours" id="hours" tabindex="6" min="0" max="99" 
                                        step="1" placeholder="hh" value ="<?php echo $d_hour?>"/>Hours
                                    <input type="number" name="minutes" id="minutes" tabindex="6" min="0" max="59" 
                                        step="1" placeholder="mm" max="99" value ="<?php echo $d_minute?>"/>Minutes
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <b data-toggle="tooltip" data-placement="top" title="URL">URL (Optional):</b>
                                </td>
                                <td><input type="text" name="device_url" id="device_url" class="form-control" placeholder="Device URL" value="<?php echo $d_url;?>" tabindex="1"/></td>
                            </tr>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                    <div class="panel-footer clearfix">
                        <div class="pull-right"><input class="btn btn-primary" type="submit" name="submitBtn" id="submitBtn" onclick="return confirm(&quot;You are about to edit this device's information. Click OK to continue or CANCEL to quit.&quot;)" value="Edit Device"></div>
                    </div>
                </form>
            </div>
            <!-- /.panel -->
        </div>
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->

<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php'); 
?>

<script type="text/javascript">

    function d_name_Info(){
        $("#popModal").modal();
        document.getElementById("modal-title").innerHTML = "Device Name";
        document.getElementById("modal-body").innerHTML = "<?php echo ("Please enter the full device name (maximum amount of characters: 100). Example: Roland CNC Mill, Brother Embroider, Janome Serger, etc."); ?>";
    }
    
    function durationInfo(){
        $("#popModal").modal();
        document.getElementById("modal-title").innerHTML = "Device Duration";
        document.getElementById("modal-body").innerHTML = "<?php echo ("Please enter the new maximum duration for the given device. If there is no change to the duration please leave the forms as is."); ?>";
    }
</script>