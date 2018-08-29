<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$d_id = $dg_id = $operator = "";

if (!$staff || $staff->getRoleID() < 7){
    //Not Authorized to see this Page
    header('Location: /index.php');
    exit();
}
?>
<title><?php echo $sv['site_name'];?> Create Wait Ticket</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Create Wait Ticket</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-8">
            <?php if ($staff && ($staff->getRoleID() >= $sv['LvlOfStaff'])) {?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-ticket-alt" aria-hidden="true"></i> Create New Wait Ticket
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped table-hover"><form name="wqform" id="wqform" autocomplete="off" method="POST" action="">
                            <tr>
                                <td><a href="#" data-toggle="tooltip" data-placement="top" title="Which device does this wait ticket belong to?">Select Device Group</a></td>
                                <td>
                                    <select name="devGrp" id="devGrp" onChange="change_group()" >
                                        <option disabled hidden selected value="">Device Group</option>
                                        <?php if($result = $mysqli->query("
                                            SELECT DISTINCT `device_group`.`dg_id`, `device_group`.`dg_desc`
                                            FROM `device_group`
                                            ORDER BY `dg_desc`
                                        ")){
                                                while($row = $result->fetch_assoc()){
                                                echo("<option value='$row[dg_id]'>$row[dg_desc]</option>");
                                            }
                                        } else {
                                            echo ("Device list Error - SQL ERROR");
                                        }?>
                                    </select>
                                    &emsp;&emsp;&emsp;&emsp;&emsp;&emsp;
                                    <select name="deviceList" id="deviceList">
                                        <option value =""> Select Group First</option>
                                    </select>   
                                </td>
                            </tr>
                            <tr>
                                <td><a href="#" data-toggle="tooltip" data-placement="top" title="The email of the person that you will issue a wait ticket for">(Optional) Email</a></td>
                                <td><input type="text" name="op-email" id="op-email" class="form-control" placeholder="email address" maxlength="100" size="10"/></td>
                            </tr>
                            <tr>
                                <td><a href="#" data-toggle="tooltip" data-placement="top" title="The phone number of person that you will issue a wait ticket for">(Optional) Phone</a></td>
                                <td><input type="text" name="op-phone" id="op-phone" class="form-control" placeholder="phone number" maxlength="10" size="10"/></td>
                            </tr>
                            <tr>
                                <td><a href="#" data-toggle="tooltip" data-placement="top" title="The person that you will issue a wait ticket for">Operator</a></td>
                                <td><input type="text" name="operator" id="operator" class="form-control" placeholder="1000000000" maxlength="10" size="10"/></td>
                            </tr>
                            <tr>
                                <td>
                                    <label><i class="fa fa-info-circle"></i> Disclaimer</label>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="disclaimer" value="">I have read and understand the <a href="http://fablab.uta.edu/policy" target="_blank">Fablab Wait Policies</a>.
                                            </label>
                                        </div>
                                        <?php
                                        if(isset($_POST['disclaimer'])) {
                                            if(isset($_POST['deviceList'])){
                                                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitBtn'])) {
                                                    $operator = filter_input(INPUT_POST, 'operator');
                                                    /*if(isset($device)) {
                                                        $wait_id = Wait_queue::insertWaitQueue($operator, $device->getD_id(), $est_time, $p_id, $status_id, $staff);
                                                    } else if(isset($device, NULL, 2)) {
                                                        $wait_id = 
                                                    }*/
                                                    $d_id = filter_input(INPUT_POST,'deviceList');
                                                    $dg_id = filter_input(INPUT_POST,'devGrp');
                                                    $em = filter_input(INPUT_POST,'op-email');
                                                    $ph = filter_input(INPUT_POST, 'op-phone');
                                                    $wait_id = Wait_queue::insertWaitQueue($operator, $d_id, $dg_id, $ph, $em);
                                                    
                                                    if (is_int($wait_id)){
                                                        if($result = $mysqli->query("
                                                            SELECT `wait_queue`.`q_id`, `wait_queue`.`estTime`, `wait_queue`.`Dev_id`, `wait_queue`.`Devgr_id`
                                                            FROM `wait_queue`
                                                            WHERE `wait_queue`.`Devgr_id`=$dg_id AND `wait_queue`.`Operator`=$operator 
                                                        ")){
                                                                while($row = $result->fetch_assoc()){
                                                                $q_id=$row["q_id"];
                                                                $estTime=$row["estTime"];
                                                            }
                                                        }
                                                        if($result = $mysqli->query("
                                                            SELECT `devices`.`device_desc`
                                                            FROM `devices`
                                                            WHERE `devices`.`d_id`=$d_id
                                                        ")){
                                                                while($row = $result->fetch_assoc()){
                                                                $device_desc=$row["device_desc"];
                                                            }
                                                        }
                                                        if ($dg_id == "2"){
                                                            $device_desc = "PolyPrinter";
                                                            
                                                        }

                                                        
                                                        //Wait_queue::printTicket($q_id, $estTime, $device_desc);
                                                    }
                                                }
                                            }
                                            else {
                                            echo ("<div style='text-align: center'>
                                                    <div class='alert alert-danger'>
                                                        You Must Select A Device
                                                    </div> </div>");
                                        }
                                        } else {
                                            echo ("<div style='text-align: center'>
                                                    <div class='alert alert-danger'>
                                                        You must accept the disclaimer before creating a ticket.
                                                    </div> </div>");
                                        }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                            <tfoot>
                                <tr>
                                    <td colspan="2"><div class="pull-right"><input type="submit" name="submitBtn" value="Submit"></div></td>
                                </tr>
                            </tfoot>
                        </form>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php } elseif($staff) { ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-sign-in-alt fa-lg"></i>  Issue wait ticket
                    </div>
                    <div class="panel-body">
                        <?php
                            echo ("To issue a wait ticket, you must be logged in as ".ROLE::getTitle($sv['minRoleTrainer'])." or higher.");
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
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->

<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript">
        var device = "";
     
    function newTicket(){
        var device_id = document.getElementById("devGrp").value;
        var o_id = document.getElementById("deviceList").value;
        
        if("D_" === device_id.substring(0,2)){
            device_id = device_id.substring(2);
        } else{
            if("-" === device_id.substring(4,5)){
            device_id = device_id.substring(5);
            } else{
            device_id = device_id.substring(6);
            }
        }
        
        device = "d_id=" + device_id + "&operator=" + o_id;

        if (device  != ""){
            var dest = "/pages/create.php?";
            dest = dest.concat(device);
            console.log(dest);
            window.location.href = dest;
        } else {
            message = "Please select a device.";
            var answer = alert(message);
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
                document.getElementById("deviceList").innerHTML = this.responseText;
            }
        };
        
        xmlhttp.open("GET","/admin/sub/getDevices.php?val="+ document.getElementById("devGrp").value, true);
        xmlhttp.send();
    }

</script>