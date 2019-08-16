<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

if (!$staff || $staff->getRoleID() < 10){
    //Not Authorized to see this Page
    $_SESSION['error_msg'] = "You are unable to view this page.";
    header('Location: /index.php');
    exit();
}

if (isset($_SESSION['d_msg']) && $_SESSION['d_msg'] == 'success'){
    $d_msg = ("<div class='col-lg-6'><div style='text-align: center'>
            <div class='alert alert-success'>
                Successfully added device to database!
            </div> </div> </div>");
    unset($_SESSION['d_msg']);
}

if (isset($_SESSION['dg_msg']) && $_SESSION['dg_msg'] == 'success'){
    $dg_msg = ("<div class='col-lg-6 pull-right'><div style='text-align: center'>
            <div class='alert alert-success'>
                Successfully added device group to database!
            </div> </div> </div>");
    unset($_SESSION['dg_msg']);
}

if (isset($_SESSION['remove_d_msg']) && $_SESSION['remove_d_msg'] == 'success'){
    $remove_d_msg = ("<div class='col-lg-6'><div style='text-align: center'>
            <div class='alert alert-success'>
                Successfully removed device from database!
            </div> </div> </div>");
    unset($_SESSION['remove_d_msg']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitBtn'])) {
    $device_group_id1 = filter_input(INPUT_POST, 'device_group_id');
    $device_public_view1 = filter_input(INPUT_POST, 'device_public_view');
    $device_name1 = filter_input(INPUT_POST, 'device_name');
    $device_hour1 = filter_input(INPUT_POST,'hours');
    $device_minute1 = filter_input(INPUT_POST,'minutes');
    $device_base_price1 = filter_input(INPUT_POST, 'device_base_price');
    $device_url1 = filter_input(INPUT_POST, 'device_url');
    $device_duration1 = "".$device_hour1.":".$device_minute1.":00";
    
    
    if(isset($_POST['device_group_id']) && preg_match('/^[0-9]+$/i', $_POST['hours']) && isset($_POST['minutes']) && isset($_POST['device_public_view']) && preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['device_name']) && preg_match('/^[0-9\.]+$/i', $_POST['device_base_price'])){
        
        $d_id1 = Devices::insert_device($device_group_id1, $device_public_view1, $device_name1, $device_duration1, $device_base_price1, $device_url1);

        if (is_int($d_id1)){
            $_SESSION['d_msg'] = "success";
            header("Location:manage_device.php");

        } else {
            $d_msg = $d_id1;
        }

    } else {
        if (!isset($_POST['device_group_id'])) {
            $d_msg = $d_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6'><div class='alert alert-danger'>
                        You must properly fill the Device creation 'Select Device Group' row.
                    </div> </div> </div> </div>");
        } if (!isset($_POST['device_public_view'])) {
            $d_msg = $d_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6'><div class='alert alert-danger'>
                        You must properly fill the Device creation 'Public View' row.
                    </div> </div> </div> </div>");
        } if (!preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['device_name'])) {
            $d_msg = $d_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6'><div class='alert alert-danger'>
                        You must properly fill the Device creation 'Device Name' row: '".$_POST['device_name']."'
                    </div> </div> </div> </div>");
        } if (!preg_match('/^[0-9\.]+$/i', $_POST['device_base_price'])) {
            $d_msg = $d_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6'><div class='alert alert-danger'>
                        You must properly fill the Device creation 'Base Price' row: '".$_POST['device_base_price']."'
                    </div> </div> </div> </div>");
        } if (!preg_match('/^[0-9]+$/i', $_POST['hours'])) {
            $d_msg = $d_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6'><div class='alert alert-danger'>
                        You must properly fill the Device creation 'Device Duration - Hours' row.
                    </div> </div> </div> </div>");
        } if (!isset($_POST['minutes'])) {
            $d_msg = $d_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6'><div class='alert alert-danger'>
                        You must properly fill the Device creation 'Device Duration - Minutes' row.
                    </div> </div> </div> </div>");
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitBtn1'])) {
    $device_group_name1 = filter_input(INPUT_POST, 'device_group_name');
    $device_group_abv1 = filter_input(INPUT_POST, 'device_group_abv');
    $device_group_parent1 = filter_input(INPUT_POST, 'device_group_parent');
    $dg_pay1 = filter_input(INPUT_POST,'dg_pay');
    $dg_mats1 = filter_input(INPUT_POST,'dg_mats');
    $dg_store1 = filter_input(INPUT_POST, 'dg_store');
    $dg_juicebox1 = filter_input(INPUT_POST, 'dg_juicebox');
    $dg_thermal1 = filter_input(INPUT_POST, 'dg_thermal');
    $dg_granular1 = filter_input(INPUT_POST, 'dg_granular');   
    
    if(isset($_POST['device_group_parent']) && isset($_POST['dg_pay']) && isset($_POST['dg_mats']) && isset($_POST['dg_store']) && isset($_POST['dg_juicebox']) && isset($_POST['dg_thermal']) && isset($_POST['dg_granular']) && preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['device_group_name']) && preg_match('/^[a-z0-9\-\#\_]{1,15}$/i', $_POST['device_group_abv'])){
        
        $dg_id1 = DeviceGroup::insert_dg($device_group_abv1, $device_group_parent1, $device_group_name1, $dg_pay1, $dg_mats1, $dg_store1, $dg_juicebox1, $dg_thermal1, $dg_granular1);

        if (is_int($dg_id1)){
            $_SESSION['dg_msg'] = "success";
            header("Location:manage_device.php");

        } else {
            $dg_msg = $dg_id1;
        }

    } else {
        if (!preg_match('/^[a-z0-9\-\_\# ]{1,100}$/i', $_POST['device_group_name'])) {
            $dg_msg = $dg_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6 pull-right'><div class='alert alert-danger'>
                        You must properly fill the Device Group creation 'Device Group Name' row: '".$_POST['device_group_name']."'
                    </div> </div> </div> </div>");
        } if (!preg_match('/^[a-z0-9\-\#\_]{1,15}$/i', $_POST['device_group_abv'])) {
            $dg_msg = $dg_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6 pull-right'><div class='alert alert-danger'>
                        You must properly fill the Device Group creation 'Device Group Abreviation' row: '".$_POST['device_group_abv']."'
                    </div> </div> </div> </div>");
        } if (!isset($_POST['device_group_parent'])) {
            $dg_msg = ("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6 pull-right'><div class='alert alert-danger'>
                        You must properly fill the Device Group creation 'Device Group Parent' row.
                    </div> </div> </div> </div>");
        } if (!isset($_POST['dg_pay'])) {
            $dg_msg = $dg_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6 pull-right'><div class='alert alert-danger'>
                        You must properly fill the Device Group creation 'Pay First' row.
                    </div> </div> </div> </div>");
        } if (!isset($_POST['dg_mats'])) {
            $dg_msg = $dg_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6 pull-right'><div class='alert alert-danger'>
                        You must properly fill the Device Group creation 'Select Materials First' row.
                    </div> </div> </div> </div>");
        } if (!isset($_POST['dg_store'])) {
            $dg_msg = $dg_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6 pull-right'><div class='alert alert-danger'>
                        You must properly fill the Device Group creation 'Storable' row.
                    </div> </div> </div> </div>");
        } if (!isset($_POST['dg_juicebox'])) {
            $dg_msg = $dg_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6 pull-right'><div class='alert alert-danger'>
                        You must properly fill the Device Group creation 'JuiceBox Managed' row.
                    </div> </div> </div> </div>");
        } if (!isset($_POST['dg_thermal'])) {
            $dg_msg = $dg_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6 pull-right'><div class='alert alert-danger'>
                        You must properly fill the Device Group creation 'Thermal Printer Number' row.
                    </div> </div> </div> </div>");
        } if (!isset($_POST['dg_granular'])) {
            $dg_msg = $dg_msg.("<div class='row'><div style='text-align: center'>
                    <div class='col-lg-6 pull-right'><div class='alert alert-danger'>
                        You must properly fill the Device Group creation 'Granular Wait' row.
                    </div> </div> </div> </div>");
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitBtn2'])) {
    $device_id1 = filter_input(INPUT_POST,'devices');
    
    if(preg_match('/^[0-9]+$/i', $_POST['devices'])){
        
        $temp_d_id1 = Devices::remove_device($device_id1);
        
        if (preg_match('/^[0-9]+$/i', $temp_d_id1)){
            $_SESSION['remove_d_msg'] = "success";
            header("Location:manage_device.php");

        } else {
            $remove_d_msg = $temp_d_id1;
        }

    } else {
        $remove_d_msg = ("<div class='row'><div style='text-align: center'>
                <div class='col-lg-6'><div class='alert alert-danger'>
                    You must properly select a device to delete from the database. Make sure to select the device group first, then select the device.
                </div> </div> </div> </div>");
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitBtn3'])) {
    header("Location:/admin/sub/edit_device.php?d_id=".filter_input(INPUT_POST,'devices1')."");
}

?>
<title><?php echo $sv['site_name'];?> Manage Devices</title>
<div id="page-wrapper">
    
    <!-- Page Title -->
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Manage Devices</h1>
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
        <?php if($dg_msg != "") { ?>
            <div style='text-align: center'>
                <?php echo $dg_msg; ?>
            </div>
        <?php } ?>
        <?php if($remove_d_msg != "") { ?>
            <div style='text-align: center'>
                <?php echo $remove_d_msg; ?>
            </div>
        <?php } ?>
    </div>
    <!-- /.row -->
    
    <!-- Panels -->
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-box" aria-hidden="true"></i> Add Device
                </div>
                <form name="mdform" id="mdform" autocomplete="off" method="POST" action="">
                    <div class="panel-body">
                        <table class="table table-bordered table-striped table-hover">
                            <tr>
                                <td><b href="#" data-toggle="tooltip" data-placement="top">Select Device Group</b></td>
                                <td>
                                    <select class="form-control" name="device_group_id" id="device_group_id" tabindex="1">
                                        <option value="" disabled selected>Select Your Option</option>
                                        <?php if($dgs = DeviceGroup::all_device_groups()){
                                            foreach($dgs as $dg_id => $dg_desc){
                                                echo("<option value='$dg_id'>$dg_desc</option>");
                                            }
                                        } else {
                                            echo("<option value=''>Device list Error - SQL ERROR</option>");
                                        }?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><b data-toggle="tooltip" data-placement="top">Public View</b></td>
                                <td><select class="form-control" name="device_public_view" id="device_public_view" tabindex="1">
                                      <option value="" disabled selected>Select Your Option</option>
                                      <option value="Y">Yes</option>
                                      <option value="N">No</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <b data-toggle="tooltip" data-placement="top">Device Name</b>
                                    <button type="button" style="background-color:white" class="btn fas fa-info" onclick="d_name_Info()"></button>
                                </td>
                                <td><input type="text" name="device_name" id="device_name" class="form-control" placeholder="Device Name" value="<?php echo $device_name1;?>" tabindex="1"/></td>
                            </tr>
                            <tr>
                                <td>
                                    <b data-toggle="tooltip" data-placement="top">Base Price</b>
                                    <b class="pull-right" data-placement="bottom" title="$">$</b>
                                </td>
                                <td><input type="number" name="device_base_price" id="device_base_price" class="form-control" max="99.99" min="0.00" value="0.00" step="0.01" tabindex="1"/></td>
                            </tr>
                            <tr>
                                <td>
                                    <b data-toggle="tooltip" data-placement="top">Device Duration</b>
                                    <button type="button" style="background-color:white" class="btn fas fa-info" onclick="durationInfo()"></button>
                                </td>
                                <td>
                                    <input type="number" name="hours" id="hours" tabindex="6" min="0" max="100" 
                                        step="1" placeholder="hh" max="99" pattern="[0-9]" value ="<?php if (isset($device_hour1)){ echo $device_hour1; } else{ echo (0);}?>"/>Hours
                                    <select name="minutes" id="minutes" tabindex="7">
                                        <option value="00">00</option>
                                        <option value="05">05</option>
                                        <option value="10">10</option>
                                        <option value="15">15</option>
                                        <option value="20">20</option>
                                        <option value="25">25</option>
                                        <option value="30">30</option>
                                        <option value="35">35</option>
                                        <option value="40">40</option>
                                        <option value="45">45</option>
                                        <option value="50">50</option>
                                        <option value="55">55</option>
                                    </select>minutes
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <b data-toggle="tooltip" data-placement="top" title="URL">URL (Optional)</b>
                                </td>
                                <td><input type="text" name="device_url" id="device_url" class="form-control" placeholder="Device URL" value="<?php echo $device_url1;?>" tabindex="1"/></td>
                            </tr>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                    <div class="panel-footer clearfix">
                        <div class="pull-right"><input class="btn btn-primary" type="submit" name="submitBtn" id="submitBtn" onclick="return confirm(&quot;You are about to add this device to the FabApp's Database. Click OK to continue or CANCEL to quit.&quot;)" value="Add Device"></div>
                        <button id="remove_d" name="remove_d" class='btn btn-danger' style='right: 10px;' type='button' data-toggle='collapse' data-target='.remove_collapse' 
                          onclick="button_text(this)" aria-expanded='false' aria-controls='collapse'>Remove Device</button>
                        <form name="mdform3" id="mdform3" autocomplete="off" method="POST" action="">
                        <div class='collapse remove_collapse'>
                            <div class="panel-body">
                                <table class="table table-bordered table-striped table-hover">
                                    <tr style="background-color:white">
                                        <td><b data-toggle="tooltip" data-placement="top">Select Device Group</b></td>
                                        <td>
                                            <select class="form-control" name="dg_id" id="dg_id" onchange="change_dg()" tabindex="1">
                                                <option disabled hidden selected value="">Device Group</option>
                                                <?php if($dgs = DeviceGroup::all_device_groups()){
                                                    foreach($dgs as $dg_id => $dg_desc){
                                                        echo("<option value='$dg_id'>$dg_desc</option>");
                                                    }
                                                } else {
                                                    echo("<option value=''>Device list Error - SQL ERROR</option>");
                                                }?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr style="background-color:white">
                                        <td><b data-toggle="tooltip" data-placement="top">Select Device</b></td>
                                        <td>
                                            <select class="form-control" name="devices" id="devices" tabindex="1">
                                                <option value =""> Select Group First</option>
                                            </select>   
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="panel-footer clearfix">
                                <!--<div class="pull-right"><input class="btn btn-danger" type="submit" name="submitBtn2" id="submitBtn2" value="Submit"></div>-->
                                <div style="text-align: center">
                                    <button class="btn btn-danger" name="submitBtn2" id="submitBtn2" onclick="return confirm(&quot;You are about to delete this device from the FabApp's Database. Click OK to continue or CANCEL to quit.&quot;)" value="Submit">
                                        Remove Device
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- /.panel-body -->
                        </form>
                    </div>
                </form>
            </div>
            <!-- /.panel -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-edit" aria-hidden="true"></i> Edit Device
                </div>
                <form name="mdform1" id="mdform1" autocomplete="off" method="POST" action="">
                    <div class="panel-body">
                        <table class="table table-bordered table-striped table-hover">
                            <tr>
                                <td><b data-toggle="tooltip" data-placement="top">Select Device Group</b></td>
                                <td>
                                    <select class="form-control" name="dg1_id" id="dg1_id" onchange="change_dg1()" tabindex="1">
                                        <option disabled hidden selected value="">Device Group</option>
                                        <?php if($dgs = DeviceGroup::all_device_groups()){
                                            foreach($dgs as $dg_id => $dg_desc){
                                                echo("<option value='$dg_id'>$dg_desc</option>");
                                            }
                                        } else {
                                            echo("<option value=''>Device list Error - SQL ERROR</option>");
                                        }?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><b data-toggle="tooltip" data-placement="top">Select Device</b></td>
                                <td>
                                    <select class="form-control" name="devices1" id="devices1" tabindex="1">
                                        <option value =""> Select Group First</option>
                                    </select>   
                                </td>
                            </tr>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                    <div class="panel-footer clearfix">
                        <div class="pull-right"><input class="btn btn-primary" type="submit" name="submitBtn3" id="submitBtn3" onclick="return confirm(&quot;You are about to be redirected to the edit device page for this device. Click OK to continue or CANCEL to quit.&quot;)" value="Edit Device"></div>
                    </div>
                </form>
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-md-6 -->

        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-box" aria-hidden="true"></i> Add Device Group
                </div>
                <form name="mdform2" id="mdform2" autocomplete="off" method="POST" action="">
                <div class="panel-body">
                    <table class="table table-bordered table-striped table-hover">
                        <tr>
                            <td>
                                <b data-toggle="tooltip" data-placement="top">Device Group Name</b>
                                <button type="button" style="background-color:white" class="btn fas fa-info" onclick="dg_name_Info()"></button>
                            </td>
                            <td><input type="text" name="device_group_name" id="device_group_name" class="form-control" placeholder="Device Group Name" value="<?php echo $device_group_name1;?>" tabindex="1"/></td>
                        </tr>
                        <tr>
                            <td>
                                <b data-toggle="tooltip" data-placement="top">Device Group Name Abreviation</b>
                                <button type="button" style="background-color:#D3D3D3" class="btn fas fa-info" onclick="dg_abv_Info()"></button>
                            </td>
                            <td><input type="text" name="device_group_abv" id="device_group_abv" class="form-control" placeholder="Device Group Abreviation" value="<?php echo $device_group_abv1;?>" tabindex="1"/></td>
                        </tr>
                        <tr>
                            <td><b href="#" data-toggle="tooltip" data-placement="top">Device Group Parent</b></td>
                            <td>
                                <select  class="form-control" name="device_group_parent" id="device_group_parent" tabindex="1">
                                    <option value="" disabled selected>Select Your Option</option>
                                    <option value="NULL">No Parent</option>                                 
                                    <?php if($dgs = DeviceGroup::all_device_groups()){
                                        foreach($dgs as $dg_id => $dg_desc){
                                            echo("<option value='$dg_id'>$dg_desc</option>");
                                        }
                                    } else {
                                        echo("<option value=''>Device list Error - SQL ERROR</option>");
                                    }?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><b data-toggle="tooltip" data-placement="top">Pay First</b></td>
                            <td><select class="form-control" name="dg_pay" id="dg_pay" tabindex="1">
                                  <option value="" disabled selected>Select Your Option</option>
                                  <option value="Y">Yes</option>
                                  <option value="N">No</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><b data-toggle="tooltip" data-placement="top">Select Materials First</b></td>
                            <td><select class="form-control" name="dg_mats" id="dg_mats" tabindex="1">
                                  <option value="" disabled selected>Select Your Option</option>
                                  <option value="Y">Yes</option>
                                  <option value="N">No</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><b data-toggle="tooltip" data-placement="top">Storable</b></td>
                            <td><select class="form-control" name="dg_store" id="dg_store" tabindex="1">
                                  <option value="" disabled selected>Select Your Option</option>
                                  <option value="Y">Yes</option>
                                  <option value="N">No</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><b data-toggle="tooltip" data-placement="top">JuiceBox Managed</b></td>
                            <td><select class="form-control" name="dg_juicebox" id="dg_juicebox" tabindex="1">
                                  <option value="" disabled selected>Select Your Option</option>
                                  <option value="Y">Yes</option>
                                  <option value="N">No</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><b data-toggle="tooltip" data-placement="top">Thermal Printer Number</b></td>
                            <td><select class="form-control" name="dg_thermal" id="dg_thermal" tabindex="1">
                                  <option value=0>0</option>
                                  <option value=1>1</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><b data-toggle="tooltip" data-placement="top">Granular Wait</b></td>
                            <td><select class="form-control" name="dg_granular" id="dg_granular" tabindex="1">
                                  <option value="" disabled selected>Select Your Option</option>
                                  <option value="Y">Yes</option>
                                  <option value="N">No</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                <!-- /.panel-body -->
                <div class="panel-footer clearfix">
                    <div class="pull-right"><input class="btn btn-primary" type="submit" name="submitBtn1" id="submitBtn1" onclick="return confirm(&quot;You are about to add this device group to the FabApp's Database. Click OK to continue or CANCEL to quit.&quot;)" value="Add Device Group"></div>
                </div>
                </form>
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-md-6 -->

    </div>
    <!-- /.row -->

</div>
<!-- /#page-wrapper -->


<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript">
    
    function change_dg(){
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
        
        xmlhttp.open("GET","/admin/sub/md_getDevices.php?val="+ document.getElementById("dg_id").value+"&loc=1", true);
        xmlhttp.send();
        inUseCheck();
    }
    
    function change_dg1(){
        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            // code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("devices1").innerHTML = this.responseText;
            }
        };
        
        xmlhttp.open("GET","/admin/sub/md_getDevices.php?val="+ document.getElementById("dg1_id").value+"&loc=0", true);
        xmlhttp.send();
        inUseCheck();
    }
    
    function button_text(element) {
        if(element.innerHTML == "Remove Device") element.innerHTML = "Back";
        else { element.innerHTML = "Remove Device"; }
    }
    
    function durationInfo(){
        $("#popModal").modal();
        document.getElementById("modal-title").innerHTML = "Device Duration";
        document.getElementById("modal-body").innerHTML = "<?php echo ("Please enter the maximum duration for the given new device. If there is no threshold to the device duration, leave the duration 0 hours 0 minutes."); ?>";
    }
    
    function d_name_Info(){
        $("#popModal").modal();
        document.getElementById("modal-title").innerHTML = "Device Name";
        document.getElementById("modal-body").innerHTML = "<?php echo ("Please enter the full device name (maximum amount of characters: 100). Example: Roland CNC Mill, Brother Embroider, Janome Serger, etc."); ?>";
    }
    
    function dg_name_Info(){
        $("#popModal").modal();
        document.getElementById("modal-title").innerHTML = "Device Group Name";
        document.getElementById("modal-body").innerHTML = "<?php echo ("Please enter the full device group name (maximum amount of characters: 100). Example: Linear Saws, PolyPrinter, Shop Room, etc."); ?>";
    }
    
    function dg_abv_Info(){
        $("#popModal").modal();
        document.getElementById("modal-title").innerHTML = "Device Group Abreviation";
        document.getElementById("modal-body").innerHTML = "<?php echo ("Please enter an abreviation for the given device group name with no spaces (maximum amount of characters: 15). For example: The abreviation of device group 'Linear Saw' is 'lin_saw'."); ?>";
    }

</script>