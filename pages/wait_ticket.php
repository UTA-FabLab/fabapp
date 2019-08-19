<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$d_id1 = $dg_id1 = $ph1 = $carrier_name1 = $em1 = $operator1 = $wt_msg = "";
$number_of_queue_tables = 0;
$device_array = array();

if (!$staff || $staff->getRoleID() < $sv['LvlOfStaff']){
    //Not Authorized to see this Page
    $_SESSION['error_msg'] = "You are unable to view this page.";
    header('Location: /index.php');
    exit();
}
if (isset($_SESSION['wt_msg']) && $_SESSION['wt_msg'] == 'success'){
    $wt_msg = ("<div style='text-align: center'>
            <div class='alert alert-success'>
                Successfully added to wait queue and updated contact info!
            </div> </div>");
    unset($_SESSION['wt_msg']);
}
if (!array_key_exists("clear_queue",$sv)){
    $mysqli->query("INSERT INTO `site_variables` (`id`, `name`, `value`, `notes`) VALUES (NULL, 'clear_queue', '8', 'Minimum Lvl Required to clear the Wait Queue')");
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['removeBtn']) && $staff->getRoleID() >= $sv['clear_queue']) {
    Wait_queue::removeAllUsers();
    $_SESSION['success_msg'] = "Wait Queue has been cleared";
    header("Location:/index.php");
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitBtn'])) {
    $operator1 = filter_input(INPUT_POST, 'operator1');
    $em1 = filter_input(INPUT_POST,'op-email');
    $ph1 = filter_input(INPUT_POST, 'op-phone');
    
    if(isset($_POST['devices']) && isset($_POST['dg_id'])){
        $d_id1 = filter_input(INPUT_POST,'devices');
        $dg_id1 = filter_input(INPUT_POST,'dg_id');
        $wait_status = Wait_queue::hasDGWait($operator1 , $dg_id1);
        
        if ($wait_status)
        {
            $wt_msg = ("<div style='text-align: center'>
                    <div class='alert alert-danger'>
                        Operator is already in this Wait Queue.
                    </div> </div>");
        } else {
            if($ph1 && !isset($_POST['carrier_name'])){
                $wt_msg = ("<div style='text-align: center'>
                <div class='alert alert-danger'>
                    You Must Select A Carrier If Entering a Phone Number
                </div> </div>");
            } elseif (!$ph1 && isset($_POST['carrier_name'])){
                $wt_msg = ("<div style='text-align: center'>
                <div class='alert alert-danger'>
                    You Must Enter a Phone Number If Selecting a Carrier
                </div> </div>");
                
            } else {
                $carrier_name1 = filter_input(INPUT_POST,'carrier_name');
                $wait_id1 = Wait_queue::insertWaitQueue($operator1, $d_id1, $dg_id1, $ph1, $carrier_name1, $em1);

                if (is_int($wait_id1)){
                    $_SESSION['wt_msg'] = "success";
                    header("Location:wait_ticket.php");

                } else {
                    $wt_msg = $wait_id1;
                }
            }
        }
    } else {
        $wt_msg = ("<div style='text-align: center'>
                <div class='alert alert-danger'>
                    You Must Select A Device
                </div> </div>");
    }
}
?>
<title><?php echo $sv['site_name'];?> Wait Queue System</title>
<div id="page-wrapper">
    
    <!-- Page Title -->
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Wait Queue System</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    
    <!-- Ticket Creation -->
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-ticket-alt" aria-hidden="true"></i> Create Wait Queue Ticket
                </div>
                <form name="wqform" id="wqform" autocomplete="off" method="POST" action="">
                <div class="panel-body">
                    <?php if($wt_msg != "") { ?>
                        <div style='text-align: center'>
                            <?php echo $wt_msg; ?>
                        </div>
                    <?php } ?>
                    <table class="table table-bordered table-striped table-hover">
                        <tr>
                            <td><a href="#" data-toggle="tooltip" data-placement="top" title="Which device does this wait ticket belong to?">Select Device Group</a></td>
                            <td>
                                <select name="dg_id" id="dg_id" onchange="change_dg()" tabindex="1">
                                    <option disabled hidden selected value="">Device Group</option>
                                    <?php if($dgs = DeviceGroup::popDG_WQ()){
                                        foreach($dgs as $dg_id => $dg_desc){
                                            echo("<option value='$dg_id'>$dg_desc</option>");
                                        }
                                    } else {
                                        echo("<option value=''>Device list Error - SQL ERROR</option>");
                                    }?>
                                </select>
                                &emsp;&emsp;&emsp;&emsp;&emsp;&emsp;
                                <select name="devices" id="devices" tabindex="1">
                                    <option value =""> Select Group First</option>
                                </select>   
                            </td>
                        </tr>
                        <tr>
                            <td><a href="#" data-toggle="tooltip" data-placement="top" title="The person's MavID that you will issue a wait ticket for">Operator</a></td>
                            <td><input type="text" name="operator1" id="operator1" class="form-control" placeholder="1000000000" maxlength="10" size="10" value="<?php echo $operator1;?>" tabindex="1" oninput="inUseCheck()"/></td>
                        </tr>
                        <tr>
                            <td><a href="#" data-toggle="tooltip" data-placement="top" title="The email of the person that you will issue a wait ticket for">(Optional) Email</a></td>
                            <td><input type="text" name="op-email" id="op-email" class="form-control" placeholder="email address" maxlength="100" size="10" value="<?php echo $em1;?>" tabindex="1"/></td>
                        </tr>
                        <tr>
                            <td>
                                <a href="#" data-toggle="tooltip" data-placement="top" title="The phone number of person that you will issue a wait ticket for">(Optional) Phone</a>
                                <button type="button" class="btn fas fa-broadcast-tower" onclick="listCarriers()"></button>
                            </td>
                            <td>
                                <div class="col-md-6">
                                    <input type="text" name="op-phone" id="op-phone" class="form-control" placeholder="phone number" maxlength="10" size="10" value="<?php echo $ph1;?>" tabindex="1"/>
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
                        <tr id="tr_verify" hidden>
                            <td colspan="2">
                                <label for="warnCB">Opererator has an active Ticket, please inform user of policy.</label>
                                <input type="checkbox" id="warnCB" onchange="warnedCB()"/>
                            </td>
                        </tr>
                    </table>
                </div>
                <!-- /.panel-body -->
                <div class="panel-footer clearfix">
                    <div class="pull-right"><input class="btn" type="submit" name="submitBtn" id="submitBtn" value="Submit" disabled></div>
                </div>
                </form>
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-md-8 -->
        <div class="col-md-4">
            <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="far fa-clock fa-fw"></i>Secondary Timer Length of Wait
                    </div>
                    <!-- /.panel-heading -->
                    <div class="panel-body">
                        <?php $m = floor($sv['wait_period']/60);
                        $s = $sv['wait_period'] % 60;
                            echo "$m minutes"; 
                            if ($s != 0) echo " & $s seconds";?>
                    </div>
            </div>
            <?php if (Wait_queue::hasWait()) {?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-print fa-fw"></i>Process Ticket
                    </div>
                    <!-- /.panel-heading -->
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-11">
                                <tr>
                                    <td><b>Device:</b></td>
                                    <td>
                                        <select class="form-control" name="devGrp" id="devGrp" onChange="change_group()" >
                                            <option value="" selected hidden> Select Device</option>
                                            <?php // Load all of the device groups that are being waited for - signified with a 'DG' in front of the value attribute
                                                if ($result = $mysqli->query("
                                                        SELECT DISTINCT D.`device_desc`, D.`dg_id`, D.`d_id`
                                                        FROM `devices` D 
                                                        JOIN `wait_queue` WQ on D.`dg_id` = WQ.`Devgr_id`
                                                        LEFT JOIN (SELECT trans_id, t_start, t_end, d_id, operator, status_id FROM transactions WHERE status_id < $status[total_fail]  ORDER BY trans_id DESC) as t
                                                        ON D.`d_id` = t.`d_id`
                                                        WHERE WQ.`valid`='Y' AND (WQ.`Devgr_id` = 2 OR D.`d_id` = WQ.`Dev_id`) AND t.`trans_id` IS NULL AND D.`d_id` NOT IN (
                                                            SELECT `d_id`
                                                            FROM `service_call`
                                                            WHERE `solved` = 'N' AND `sl_id` >= 7
                                                        )
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
                                <tr> <br>
                                    <td><p><b>Operator: </b><span type="password" id="processOperator"></span></p></td>
                                    <td><input type="text" name="operator_ticket" id="operator_ticket" class="form-control" placeholder="1000000000" maxlength="10" size="10"/></td>
                                </tr>
                            </div>
                            <!-- /.col-md-11 -->
                        </div>
                        <br>
                        <button align="pull-right" class="btn btn-primary" type="button" id="addBtn" onclick="newTicket()">Create Ticket</button>
                    </div>
                    <!-- /.panel-body -->
                </div>
            <?php } ?>
        </div>
        <!-- /.col-md-4 -->
    </div>
    <!-- /.row -->
    
    <!-- Wait Queue -->
    <div class="row">
    <?php if (Wait_queue::hasWait()) { ?>
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-list-ol"></i>  Wait Queue
                </div>
                <!-- /.panel-heading -->
                <div class="panel-body">
                    <div class="table-responsive">
                        <ul class="nav nav-tabs">
                            <!-- Load all device groups as a tab that have at least one device in that group -->
                            <?php if ($result = Wait_queue::getTabResult()) {
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
                            } ?>
                        </ul>
                        <div class="tab-content">
                            <?php
                            if ($Tabresult = Wait_queue::getTabResult()) {
                                while($tab = $Tabresult->fetch_assoc()){
                                    $number_of_queue_tables++;
                                    
                                    // Calculate the wait queue timer by granular wait of a device group
                                    if ($tab["granular_wait"] == 'Y'){
                                        Wait_queue::calculateDeviceWaitTimes(); 
                                    } else {
                                        Wait_queue::calculateWaitTimes();
                                    } 

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
                                                    while ($row = $result->fetch_assoc()) { ?>
                                                        <tr class="tablerow">

                                                            <!-- Wait Queue Number -->
                                                            <td align="center"><?php echo($row['Q_id']) ?></td>

                                                            <!-- Operator ID --> 
                                                            <td>
                                                                <?php $user = Users::withID($row['Operator']);?>
                                                                <a class="<?php echo $user->getIcon()?> fa-lg" title="<?php echo($row['Operator']) ?>"  href="/pages/waitUserInfo.php?q_id=<?php echo $row["Q_id"]?>&loc=1"></a>
                                                                <?php if (!empty($row['Op_phone'])) { ?> <i class="fas fa-mobile"   title="<?php echo ($row['Op_phone']) ?>"></i> <?php } ?>
                                                                <?php if (!empty($row['Op_email'])) { ?> <i class="fas fa-envelope" title="<?php echo ($row['Op_email']) ?>"></i> <?php } ?>
                                                            </td>

                                                            <!-- Device Group Name -->
                                                            <?php if ($tab["dg_id"]==2) { ?> <td align="center"><?php echo($row['dg_desc']) ?></td><?php } ?>
                                                            <?php if ($tab["dg_id"]!=2) { ?> <td align="center"><?php echo($row['device_desc']) ?></td><?php } ?>


                                                            <!-- Start Time, Estimated Time, Last Contact Time -->
                                                            <td>
                                                                <!-- Start Time -->
                                                                <i class="far fa-calendar-alt" align="center" title="Started @ <?php echo( date($sv['dateFormat'],strtotime($row['Start_date'])) ) ?>"></i>

                                                            <!-- Estimated Time -->
                                                            <?php if (isset($row['estTime'])) {
                                                                $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $row["estTime"]);
                                                                sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
                                                                $time_seconds = $hours * 3600 + $minutes * 60 + $seconds + $sv["wait_period"];
                                                                $temp_time = $hours * 3600 + $minutes * 60 + $seconds;
                                                                if (isset($row['last_contact'])){
                                                                    $time_seconds = $sv["wait_period"] - (time() - strtotime($row['last_contact']) );
                                                                    if ($time_seconds <= 0 ){
                                                                        echo("<span style=\"color:red\" align=\"center\" id=\"q$row[Q_id]\">"."  $row[estTime]  </span>" );
                                                                    } else {
                                                                        echo("<span style=\"color:orange\" align=\"center\" id=\"q$row[Q_id]\">"."  $row[estTime]  </span>" );
                                                                    }
                                                                    array_push($device_array, array("q".$row["Q_id"], $time_seconds));
                                                                } elseif ($temp_time == "00:00:00") {
                                                                    echo("<span align=\"center\" id=\"q$row[Q_id]\">"."  $row[estTime]  </span>" );
                                                                    //do nothing keeping time at 00:00:00
                                                                } else {
                                                                    echo("<span align=\"center\" id=\"q$row[Q_id]\">"."  $row[estTime]  </span>" );
                                                                    array_push($device_array, array("q".$row["Q_id"], $time_seconds));
                                                                }
                                                            } ?>

                                                            <!-- Last Contact Time -->
                                                            <?php if (isset($row['last_contact'])) { ?> 
                                                                <i class="far fa-bell" align="center" title="Last Alerted @ <?php echo(date($sv['dateFormat'], strtotime($row['last_contact']))) ?>"></i> <?php
                                                            } ?>
                                                            </td>

                                                            <!-- Send an Alert -->
                                                            
                                                            <td> 
                                                                <?php if (!empty($row['Op_phone']) || !empty($row['Op_email'])) { ?> 
                                                                    <div style="text-align: center">
                                                                        <?php //prepare message
                                                                        if ( $row['device_desc'] == ""){
                                                                            //datetime is added within the AJAX file endWaitList
                                                                            $msg = "A $row[dg_desc] is now available. Please make your way to the FabLab. You have until ";
                                                                        } else {
                                                                            //datetime is added within the AJAX file endWaitList
                                                                            $msg = "$row[device_desc] is now available. Please make your way to the FabLab. You have until ";
                                                                        }
                                                                        ?>
                                                                        <button class="<?php if (isset($row['last_contact'])){echo "btn btn-xs btn-warning";} else{echo "btn btn-xs btn-primary";}?>" data-target="#removeModal" data-toggle="modal" 
                                                                                onclick="sendManualMessage(<?php echo $row["Q_id"]?>, '<?php echo $msg;?>', 1)">
                                                                            <!-- make note that adding explanation points may cause errors with notifications -->
                                                                                Send Alert
                                                                        </button>
                                                                    </div>
                                                                <?php } ?>
                                                            </td>

                                                            <!-- Remove From Wait Queue -->
                                                            <td> 
                                                                <div style="text-align: center">
                                                                    <button class="<?php if (isset($row['last_contact']) && $time_seconds <= 0){echo "btn btn-xs btn-danger";} else{echo "btn btn-xs";}?>" data-target="#removeModal" data-toggle="modal" 
                                                                            onclick="removeFromWaitlist(<?php echo $row["Q_id"].", ".$row["Operator"].", undefined"?>)">
                                                                            Remove
                                                                    </button>
                                                                </div>
                                                            </td>
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
        <?php if ($staff->getRoleID() >= $sv['clear_queue']){ ?>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-trash fa-fw"></i>Remove All Wait-Queue Users
                    </div>
                    <!-- /.panel-heading -->
                    <div class="panel-body">
                        <div style="text-align: center">
                            <form method="post" action="" onsubmit="return removeAllUsers()" >
                            <button class="btn btn-danger" name="removeBtn">
                                Remove All Users
                            </button>
                            </form>
                        </div>
                    </div>
                <!-- /.panel-body -->
                </div>
            </div>
            <!-- /.col-md-4 -->
        <?php } ?>
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
        
        xmlhttp.open("GET","/pages/sub/wq_getDevices.php?val="+ document.getElementById("dg_id").value, true);
        xmlhttp.send();
        inUseCheck();
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
                document.getElementById("processOperator").innerHTML = this.responseText;
            }
        };
        
        xmlhttp.open("GET","/pages/sub/getWaitQueueID.php?val="+ document.getElementById("devGrp").value, true);
        xmlhttp.send();
    }
    
    function listCarriers(){
        $("#popModal").modal();
        document.getElementById("modal-title").innerHTML = "Supported Carriers";
        document.getElementById("modal-body").innerHTML = "<?php echo Notifications::listCarriers(); ?>";
    }
    
    function removeFromWaitlist(q_id){
        
        if (confirm("You are about to delete a user from the wait queue. Click OK to continue or CANCEL to quit.")){  
        
        window.location.href = "/pages/sub/endWaitList.php?q_id=" + q_id + "&loc=1";
        }
     }
    
    function removeAllUsers(){

        if (confirm("You are about to delete ALL wait queue users. Click OK to continue or CANCEL to quit.")){
            return true;
        }
        return false;
    }
    
    <?php if ($staff->getRoleID() < $sv['clear_queue']){ ?>
        function removeAllUsers(){

            if (confirm("You are about to delete ALL wait queue users. Click OK to continue or CANCEL to quit.")){
                return true;
            }
            return false;
        }
    <?php } ?>
    
    function inUseCheck(){
        var operator = document.getElementById("operator1").value;
        var dg_id = document.getElementById("dg_id").value;
        var sBtn = document.getElementById("submitBtn");
        var trV = document.getElementById("tr_verify");
        
        if (operator.length == 10 && dg_id){
            if (window.XMLHttpRequest) {
                // code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp = new XMLHttpRequest();
            }
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    console.log("response"+this.responseText);
                    if (this.responseText == "warn"){
                        console.log("Warning, Learner has on going print");
                        sBtn.disabled = true;
                        sBtn.classList.remove("btn-primary");
                        trV.hidden = false;
                    } else if(this.responseText == "no_need") {
                        sBtn.disabled = false;
                        sBtn.classList.add("btn-primary");
                        trV.hidden  = true;
                    } else {
                        console.log("Invalid Search Criteria");
                        sBtn.disabled = true;
                        sBtn.classList.remove("btn-primary");
                    }
                }
            };

            xmlhttp.open("GET","/pages/sub/isInUse.php?dg_id="+ dg_id +"&operator="+ operator, true);
            xmlhttp.send();
        }
    }
    
    function warnedCB(){
        var sBtn = document.getElementById("submitBtn");
        var warnCB = document.getElementById("warnCB");
        if (warnCB.checked == true){
            sBtn.disabled = false;
            sBtn.classList.add("btn-primary");
        } else {
            sBtn.disabled = true;
            sBtn.classList.remove("btn-primary");
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