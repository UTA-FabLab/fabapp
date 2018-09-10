<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$d_id1 = $dg_id1 = $operator1 = "";
$number_of_queue_tables = 0;
$device_array = array();

if (!$staff || $staff->getRoleID() < $sv['LvlOfStaff']){
    //Not Authorized to see this Page
    $_SESSION['error_msg'] = "You are unable to view this page.";
    header('Location: /index.php');
    exit();
}
?>
<title><?php echo $sv['site_name'];?> Wait Queue System</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Wait Queue System</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    
    <!-- Wait Queue -->
    <?php if (Wait_queue::hasWait()) {?>
        <div class="row">
            <div class="col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-ticket-alt fa-fw"></i>  Wait Queue
                    </div>
                    <!-- /.panel-heading -->
                    <div class="panel-body">
                        <div class="table-responsive">
                            <ul class="nav nav-tabs">
                                <!-- Load all device groups as a tab that have at least one device in that group -->
                                <?php if ($result = $mysqli->query("
                                    SELECT dg_id, dg_desc
                                    FROM device_group 
                                    WHERE device_group.dg_id IN ( 
                                            SELECT D.dg_id
                                            FROM devices D, wait_queue WQ
                                            WHERE D.dg_id=WQ.devgr_id AND WQ.valid='Y'
                                            GROUP BY dg_id
                                            HAVING COUNT(*) >= 1
                                    )
                                    ORDER BY dg_id;
                                ")) {
                                 if ($result = Wait_queue::getTabResult()) {
                                        $count = 0;
                                        while ($row = $result->fetch_assoc()) { ?>
                                            <li class="<?php if ($count == 0) echo "active";?>">
                                                <a <?php echo("href=\"#".$row["dg_id"]."\""); ?>  data-toggle="tab" aria-expanded="false"> <?php echo($row["dg_desc"]); ?> </a>
                                            </li>
                                        <?php 
                                        if ($count == 0){
                                            //create a way to display the first wait_queue table tab by saving which dg_id it is to variable 'first_dgid'
                                            $first_dgid = $row["dg_id"];  
                                        }   
                                        $count++;                                                                  
                                        }
                                    }
                                } ?>
                            </ul>
                            <div class="tab-content">
                                <?php
                                if ($Tabresult = Wait_queue::getTabResult()) {
                                    while($tab = $Tabresult->fetch_assoc()){
                                        $number_of_queue_tables++;
                                        // Give all of the dynamic tables a name so they can be called when their tab is clicked ?>
                                        <div class="tab-pane fade <?php if ($first_dgid == $tab["dg_id"]) echo "in active";?>" <?php echo("id=\"".$tab["dg_id"]."\"") ?> >
                                            <table class="table table-striped table-bordered table-hover" <?php echo("id=\"waitTable_$number_of_queue_tables\"") ?>>
                                                <thead>
                                                    <tr class="tablerow">
                                                        <th><i class="fa fa-th-list"></i> Queue Number</th>
                                                        <?php if ($staff && ($staff->getRoleID() >= $sv['LvlOfStaff'])) { ?> <th><i class="far fa-user"></i> MavID</th><?php } ?>
                                                        <?php if ($tab["dg_id"]==2) { ?> <th><i class="far fa-flag"></i> Device Group</th><?php } ?>
                                                        <?php if ($tab["dg_id"]!=2) { ?> <th><i class="far fa-flag"></i> Device</th><?php } ?>
                                                        <th><i class="far fa-clock"></i> Time Left</th>
                                                        <?php if ($staff && ($staff->getRoleID() >= $sv['LvlOfStaff'])) { ?> 
                                                            <th><i class="far fa-flag"></i> Alerts</th>
                                                            <th><i class="fa fa-times"></i> Remove</th>
                                                        <?php } ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php // Display all of the students in the wait queue for a device group
                                                    if ($result = $mysqli->query("
                                                            SELECT *
                                                            FROM wait_queue WQ JOIN device_group DG ON WQ.devgr_id = DG.dg_id
                                                            LEFT JOIN devices D ON WQ.Dev_id = D.d_id
                                                            WHERE valid = 'Y' and WQ.devgr_id=$tab[dg_id]
                                                            ORDER BY Q_id;
                                                    ")) {
                                                        $counter = 1;
                                                        Wait_queue::calculateDeviceWaitTimes();      

                                                        while ($row = $result->fetch_assoc()) { ?>
                                                            <tr class="tablerow">

                                                                <!-- Wait Queue Number -->
                                                                <td align="center"><?php echo($row['Q_id']) ?></td>

                                                                <!-- Operator ID --> 
                                                                <?php if ($staff && ($staff->getRoleID() >= $sv['LvlOfStaff'])) { ?>
                                                                    <td>
                                                                        <?php $user = Users::withID($row['Operator']);?>
                                                                        <a class="<?php echo $user->getIcon()?> fa-lg" title="<?php echo($row['Operator']) ?>"  href="/pages/updateContact.php?operator=<?php echo $row["Operator"]?>&loc=1"></a>
                                                                        <?php if (!empty($row['Op_phone'])) { ?> <i class="fas fa-mobile"   title="<?php echo ($row['Op_phone']) ?>"></i> <?php } ?>
                                                                        <?php if (!empty($row['Op_email'])) { ?> <i class="fas fa-envelope" title="<?php echo ($row['Op_email']) ?>"></i> <?php } ?>
                                                                    </td>
                                                                <?php } ?>

                                                                <!-- Device Group Name -->
                                                                <?php if ($tab["dg_id"]==2) { ?> <td align="center"><?php echo($row['dg_desc']) ?></td><?php } ?>
                                                                <?php if ($tab["dg_id"]!=2) { ?> <td align="center"><?php echo($row['device_desc']) ?></td><?php } ?>


                                                                <!-- Start Time, Estimated Time, Last Contact Time -->
                                                                <td>
                                                                    <!-- Start Time -->
                                                                <?php if ($staff && ($staff->getRoleID() >= $sv['LvlOfStaff'])) { ?>
                                                                    <i class="far fa-calendar-alt" align="center" title="Started @ <?php echo( date($sv['dateFormat'],strtotime($row['Start_date'])) ) ?>"></i>
                                                                <?php } ?>

                                                                <!-- Estimated Time -->
                                                                <?php if (isset($row['estTime'])) {
                                                                    echo("<span align=\"center\" id=\"q$row[Q_id]\">"."  $row[estTime]  </span>" );
                                                                    $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $row["estTime"]);
                                                                    sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
                                                                    $time_seconds = $hours * 3600 + $minutes * 60 + $seconds + ($sv["grace_period"]);
                                                                    $temp_time = $hours * 3600 + $minutes * 60 + $seconds;
                                                                    if ($temp_time == "00:00:00"){
                                                                            $time_seconds = $hours * 3600 + $minutes * 60 + $seconds - (time() - strtotime($row["Start_date"]) ) + $sv["grace_period"];
                                                                    }
                                                                    array_push($device_array, array("q".$row["Q_id"], $time_seconds));
                                                                } ?>

                                                                    <!-- Last Contact Time -->
                                                                <?php if ($staff && ($staff->getRoleID() >= $sv['LvlOfStaff'])) { ?>
                                                                    <?php if (isset($row['last_contact'])) { ?> 
                                                                        <i class="far fa-bell" align="center" title="Last Alerted @ <?php echo(date($sv['dateFormat'], strtotime($row['last_contact']))) ?>"></i> <?php
                                                                    } ?>
                                                                <?php } ?>
                                                                </td>

                                                                <!-- Send an Alert -->
                                                                <?php if ($staff && ($staff->getRoleID() >= $sv['LvlOfStaff'])) { ?>
                                                                <td> 
                                                                    <?php if (!empty($row['Op_phone']) || !empty($row['Op_email'])) { ?> 
                                                                        <div style="text-align: center">
                                                                            <button class="btn btn-xs btn-primary" data-target="#removeModal" data-toggle="modal" 
                                                                                    onclick="sendManualMessage(<?php echo $row["Q_id"]?>, 'The FabLab is waiting for you to start your print!')">
                                                                                    Send Alert
                                                                            </button>
                                                                        </div>
                                                                    <?php } ?>
                                                                </td>
                                                                <?php } ?>

                                                                <!-- Remove From Wait Queue -->
                                                                <?php if ($staff && ($staff->getRoleID() >= $sv['LvlOfStaff'])) { ?>
                                                                     <td> 
                                                                         <div style="text-align: center">
                                                                             <button class="btn btn-danger btn-xs btn-primary" data-target="#removeModal" data-toggle="modal" 
                                                                                    onclick="removeFromWaitlist(<?php echo $row["Q_id"].", ".$row["Operator"].", undefined"?>)">
                                                                                    Remove
                                                                             </button>
                                                                         </div>
                                                                     </td>
                                                                 <?php } ?>
                                                            </tr>
                                                        <?php }
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php }
                                } ?>
                                </div>
                        </div>
                        <!-- /.table-responsive -->
                    </div>
                </div>
            </div>
            <!-- /.col-md-8 -->
            <?php if ($staff && ($staff->getRoleID() >= $sv['LvlOfStaff'])) { ?>
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fa fa-print fa-fw"></i>Process Ticket
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-lg-11">
                                    <tr>
                                        <td>Device</td>
                                        <td>
                                            <select class="form-control" name="devGrp" id="devGrp" onChange="change_group()" >
                                                <option value="" selected hidden> Select Device</option>
                                                <?php // Load all of the device groups that are being waited for - signified with a 'DG' in front of the value attribute
                                                    if ($result = $mysqli->query("
                                                            SELECT DISTINCT D.`device_desc`, D.`dg_id`, D.`d_id`
                                                            FROM `devices` D 
                                                            JOIN `wait_queue` WQ on D.`dg_id` = WQ.`Devgr_id`
                                                            LEFT JOIN (SELECT trans_id, t_start, t_end, d_id, operator, status_id FROM transactions WHERE status_id < 12  ORDER BY trans_id DESC) as t
                                                            ON D.`d_id` = t.`d_id`
                                                            WHERE WQ.`valid`='Y' AND (WQ.`Devgr_id` = 2 OR D.`d_id` = WQ.`Dev_id`) AND t.`trans_id` IS NULL
                                                    ")) {
                                                        while ( $rows = mysqli_fetch_array ( $result ) ) {
                                                            // Create value in the form of DG_dgID-dID
                                                            echo "<option value=". "DG_" . $rows ['dg_id'] . "-" . $rows ['d_id'].">" . $rows ['device_desc'] . "</option>";
                                                        }
                                                    } else {
                                                        die ("There was an error loading the device groups.");
                                                    } ?> 
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Operator</td>
                                        <td>
                                            <select class="form-control" name="deviceList" id="deviceList">
                                                <option value ="" selected hidden> Select Device First</option>
                                            </select>
                                        </td>
                                    </tr>
                                </div>
                                <!-- /.col-md-11 -->
                            </div>

                            <button class="btn btn-primary" type="button" id="addBtn" onclick="newTicket()">Create Ticket</button>
                        </div>
                        <!-- /.panel-body -->
                    </div>
                </div>
            <?php } ?>
        </div>
        <!-- /.row -->
    <?php } ?>
    
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
                                    <select name="dg_id" id="dg_id" onChange="change_dg()" >
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
                                    <select name="devices" id="devices">
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
                                <td><input type="text" name="operator1" id="operator1" class="form-control" placeholder="1000000000" maxlength="10" size="10"/></td>
                            </tr>
                                    <div class="form-group">
                                        <?php
                                            if(isset($_POST['devices'])){
                                                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitBtn'])) {
                                                    $operator1 = filter_input(INPUT_POST, 'operator1');
                                                    /*if(isset($device)) {
                                                        $wait_id = Wait_queue::insertWaitQueue($operator, $device->getD_id(), $est_time, $p_id, $status_id, $staff);
                                                    } else if(isset($device, NULL, 2)) {
                                                        $wait_id = 
                                                    }*/
                                                    $d_id1 = filter_input(INPUT_POST,'devices');
                                                    $dg_id1 = filter_input(INPUT_POST,'dg_id');
                                                    $em1 = filter_input(INPUT_POST,'op-email');
                                                    $ph1 = filter_input(INPUT_POST, 'op-phone');
                                                    $wait_id1 = Wait_queue::insertWaitQueue($operator1, $d_id1, $dg_id1, $ph1, $em1);
                                                    
                                                    if (is_int($wait_id1)){
                                                        if($result = $mysqli->query("
                                                            SELECT `wait_queue`.`q_id`, `wait_queue`.`estTime`, `wait_queue`.`Dev_id`, `wait_queue`.`Devgr_id`
                                                            FROM `wait_queue`
                                                            WHERE `wait_queue`.`Devgr_id`=$dg_id AND `wait_queue`.`Operator`=$operator 
                                                        ")){
                                                                while($row = $result->fetch_assoc()){
                                                                $q_id1=$row["q_id"];
                                                                $estTime1=$row["estTime"];
                                                            }
                                                        }
                                                        if($result = $mysqli->query("
                                                            SELECT `devices`.`device_desc`
                                                            FROM `devices`
                                                            WHERE `devices`.`d_id`=$d_id1
                                                        ")){
                                                                while($row = $result->fetch_assoc()){
                                                                $device_desc1=$row["device_desc"];
                                                            }
                                                        }
                                                        if ($dg_id1 == "2"){
                                                            $device_desc1 = "PolyPrinter";
                                                            
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
                                            } ?>
                                    </div>
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

    <?php if (Wait_queue::hasWait()) {?>
        <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fa fa-print fa-fw"></i>Remove All Wait-Queue Users
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div style="text-align: center">
                                <button class="btn btn-danger" data-target="#removeModal" data-toggle="modal" 
                                onclick="removeAllUsers()">
                                    Remove All Users
                                </button>
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
<?php foreach ($device_array as $da) { ?>
    var time = <?php echo $da[1];?>;
    var display = document.getElementById('<?php echo $da[0];?>');
    startTimer(time, display);
    
<?php } ?>
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
        var dest = "";
        if (device  != ""){
            if (device_id.substring(0,1) == "2"){
                dest = "http://polyprinter-"+device_id.substring(1)+".uta.edu";
                window.open(dest,"_self")
            }
            else {
                var dest = "/pages/create.php?";
                dest = dest.concat(device);
                console.log(dest);
                window.location.href = dest;
            } 
        } 

        else {
            message = "Please select a device.";
            var answer = alert(message);
        }
    } 
    
    
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
        
        xmlhttp.open("GET","/pages/sub/getDevices.php?val="+ document.getElementById("dg_id").value, true);
        xmlhttp.send();
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
        
        xmlhttp.open("GET","/pages/sub/getWaitQueueID.php?val="+ document.getElementById("devGrp").value, true);
        xmlhttp.send();
    }
    
     function sendManualMessage(q_id, message){
        
        if (confirm("You are about to send a notification to a wait queue user. Click OK to continue or CANCEL to quit.")){
            
        window.location.href = "/pages/sub/endWaitList.php?q_id=" + q_id + "&message=" + message + "&loc=1";
        }
     }
    
     function removeFromWaitlist(q_id){
        
        if (confirm("You are about to delete a someone from the wait queue. Click OK to continue or CANCEL to quit.")){  
        
        window.location.href = "/pages/sub/endWaitList.php?q_id=" + q_id + "&loc=1";
        }
     }
    
     function removeAllUsers(){
        
        if (confirm("You are about to delete ALL wait queue users. Click OK to continue or CANCEL to quit.")){
            
        window.location.href = "/pages/sub/endWaitList.php";
        }
     }
    
    var str;
    for(var i=1; i<= <?php echo $number_of_queue_tables;?>; i++){
        str = "#waitTable_"+i
        $(str).DataTable({
                    "iDisplayLength": 10,
                    "order": []
                    });
    }

</script>