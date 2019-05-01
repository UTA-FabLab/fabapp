<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$device_array = array();
$_SESSION['type'] = "home";
$number_of_queue_tables = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['alertBtn'])){
        echo "<script>console.log( \"Debug : alertBtn\");</script>";
        $myNum = strtoupper($_POST["myNum"]);
        $_SESSION['myNum'] = $myNum;
    }
    //print new wait tab and advance the number
    if( isset($_POST['print_s']) ){
        $i = $sv['next']+1;
        wait($i);
        advanceNum($i, "next");
        
    } elseif( isset($_POST['print_e']) ){
        $i = $sv['eNext']+1;
        wait("E".$i);
        advanceNum($i, "eNext");
        
    } elseif( isset($_POST['print_b']) ){
        $i = $sv['bNext']+1;
        wait("B".$i);
        advanceNum($i, "bNext");
        
    } elseif( isset($_POST['print_m']) ){
        $i = $sv['mNext']+1;
        wait("M".$i);
        advanceNum($i, "mNext");
    }
}
function advanceNum($i, $str){
    global $mysqli;
    
    if ($result = $mysqli->query("
      UPDATE site_variables
      SET value = $i
      WHERE site_variables.name = '$str';
    ")){
        header("Location: /index.php");
    } else {
        $_SESSION['error_msg'] = "SQL Error";
        header("Location: /index.php");
    }
    exit();
}
?>
<title><?php echo $sv['site_name'];?> Dashboard</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">Dashboard</h1>
        </div>
        <!-- /.col-md-12 -->
    </div>
    <!-- Wait Queue -->
    <?php if (Wait_queue::hasWait()) { ?>
        <div class="row">
            <div class="col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-list-ol"></i>  Wait Queue
                        <div class="pull-right">
                            <button  class="btn btn-xs" data-toggle="collapse" data-target="#waitPanel"><i class="fas fa-bars"></i></button>
                        </div>
                    </div>
                    <!-- /.panel-heading -->
                    <div class="panel-body collapse in" id="waitPanel">
                        <div class="table-responsive">
                            <ul class="nav nav-tabs">
                                <!-- Load all device groups as a tab that have at least one device in that group -->
                                <?php if ($result = Wait_queue::getTabResult()) {
                                    $count = 0;
                                    while ($row = $result->fetch_assoc()) { ?>
                                        <li class="<?php if ($count == 0) echo "active";?>">
                                            <a <?php echo("href=\"#".$row["dg_id"]."\""); ?>  data-toggle="tab" aria-expanded="false"> <?php echo($row["dg_desc"]); ?> </a>
                                        </li>
                                        <?php //create a way to display the first wait_queue table tab by saving which dg_id it is to variable 'first_dgid'
                                        if ($count == 0){
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
                                                        <th><i class="far fa-user"></i> Operator</th>
                                                        <?php if ($tab["dg_id"]==2) { ?> <th><i class="far fa-flag"></i> Device Group</th><?php } ?>
                                                        <?php if ($tab["dg_id"]!=2) { ?> <th><i class="far fa-flag"></i> Device</th><?php } ?>
                                                        <th><i class="far fa-clock"></i> Time Left</th>
                                                        <?php if ($staff && ($staff->getRoleID() >= $sv['LvlOfStaff'])) { ?> 
                                                            <th><i class="far fa-flag"></i> Alerts</th>
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
   

                                                        while ($row = $result->fetch_assoc()) {
                                                            $user = Users::withID($row['Operator']);?>
                                                            <tr class="tablerow">

                                                                <!-- Wait Queue Number -->
                                                                <td align="center">
                                                                    <?php echo($row['Q_id']) ?>
                                                                </td>

                                                                <!-- Operator ID --> 
                                                                <?php if ($staff && ($staff->getRoleID() >= $sv['LvlOfStaff'])) { ?>
                                                                    <td>
                                                                        <a class="<?php echo $user->getIcon()?> fa-lg" title="<?php echo($row['Operator']) ?>"  href="/pages/waitUserInfo.php?q_id=<?php echo $row["Q_id"]?>&loc=0"></a>
                                                                        <?php if (!empty($row['Op_phone'])) { ?> <i class="fas fa-mobile"   title="<?php echo ($row['Op_phone']) ?>"></i> <?php } ?>
                                                                        <?php if (!empty($row['Op_email'])) { ?> <i class="fas fa-envelope" title="<?php echo ($row['Op_email']) ?>"></i> <?php } ?>
                                                                    </td>
                                                                <?php } else { ?>
                                                                    <td>
                                                                        <i class="<?php echo $user->getIcon()?> fa-lg"/>
                                                                    </td>
                                                                <?php } ?>

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
                                                                    $time_seconds = $hours * 3600 + $minutes * 60 + $seconds + $sv["grace_period"];
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
                                                                                    onclick="sendManualMessage(<?php echo $row["Q_id"]?>, '<?php echo $msg;?>', 0)">
                                                                                <!-- make note that adding explanation points may cause errors with notifications -->
                                                                                    Send Alert
                                                                            </button>
                                                                        </div>
                                                                    <?php } ?>
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
                            <div class="pull-right">
                                <button  class="btn btn-xs" data-toggle="collapse" data-target="#processPanel"><i class="fas fa-bars"></i></button>
                            </div>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body collapse in" id="processPanel">
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
                                                            LEFT JOIN (SELECT trans_id, t_start, t_end, d_id, operator, status_id FROM transactions WHERE status_id < 12  ORDER BY trans_id DESC) as t
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
                                    </tr><br>
                                </div>
                                <!-- /.col-md-11 -->
                            </div>
                            <button align="pull-right" class="btn btn-primary" type="button" id="addBtn" onclick="newTicket()">Create Ticket</button>
                        </div>
                        <!-- /.panel-body -->
                    </div>
                </div>
                <!-- /.col-md-4 -->
            <?php } ?>
        </div>
        <!-- /.row -->
    <?php } ?>
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-cubes fa-lg"></i> Device Status
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-bordered table-hover" id="indexTable">
                        <thead>
                            <tr class="tablerow">
                                <th align="right">Ticket</th>
                                <th>Device</th>
                                <?php if ($staff) { ?> <th>Action</th><?php } ?>
                                <th>Start Time</th>
                                <th>Est Remaining Time</th>
                            </tr>
                        </thead>
                        <?php if ($result = $mysqli->query("
                            SELECT trans_id, device_desc, t_start, est_time, devices.d_id, url, status_id
                            FROM `devices`
                            JOIN `device_group`
                            ON `devices`.`dg_id` = `device_group`.`dg_id`
                            LEFT JOIN (SELECT trans_id, t_start, est_time, d_id, status_id FROM transactions WHERE status_id < 12 ORDER BY trans_id DESC) as t 
                            ON `devices`.`d_id` = `t`.`d_id`
                            WHERE public_view = 'Y'
                            ORDER BY `trans_id` DESC, `device_desc` ASC
                        ")){
                            while ( $row = $result->fetch_assoc() ){ ?>
                                <tr class="tablerow">
                                    <?php if($row["t_start"]) {
                                        $ticket = new Transactions($row['trans_id']); ?>
                                        <td align="right"><?php echo ("<a href=\"pages/lookup.php?trans_id=$row[trans_id]\">$row[trans_id]</a>"); ?></td>
                                        <td>
                                            <?php if($ticket->getDevice()->getUrl() && (preg_match($sv['ip_range_1'],getenv('REMOTE_ADDR')) || preg_match($sv['ip_range_2'],getenv('REMOTE_ADDR'))) ){
                                                Devices::printDot($staff, $ticket->getDevice()->getD_id());
                                                echo ("<a href=\"http://".$ticket->getDevice()->getUrl()."\">".$ticket->getDevice()->getDevice_desc()."</a>");
                                            } else {
                                                Devices::printDot($staff, $ticket->getDevice()->getD_id());
                                                echo $ticket->getDevice()->getDevice_desc();
                                            } ?>
                                        </td>
                                        <?php if ($staff && ($staff->getRoleID() >= $sv['LvlOfStaff'] || $staff->getOperator() == $ticket->getUser()->getOperator())) { ?>
                                            <td align="center">
                                                <?php if ($row["status_id"] == 11) { ?>
                                                    <button class="btn btn-primary" onclick="endTicket(<?php echo "$row[trans_id],'$row[device_desc]','".$staff->getLong_close()."'"; ?>)">End</button>
                                                <?php } else { ?>
                                                    <button class="btn btn-basic" onclick="endTicket(<?php echo "$row[trans_id],'$row[device_desc]','".$staff->getLong_close()."'"; ?>)">End</button>
                                                <?php } ?>
                                            </td>
                                        <?php } elseif ($staff) { ?>
                                            <td align='center'></td>
                                        <?php }
                                        echo("<td>".date( 'M d g:i a',strtotime($row["t_start"]) )."</td>" );
                                        if( $row["status_id"] == 11) {
                                            echo("<td align='center'>".$ticket->getStatus()->getMsg()."</td>");
                                        } elseif (isset($row["est_time"])) {
                                            echo("<td align='center'><div id=\"t$row[trans_id]\">$row[est_time] </div></td>" );
                                            $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $row["est_time"]);
                                            sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
                                            $time_seconds = $hours * 3600 + $minutes * 60 + $seconds- (time() - strtotime($row["t_start"]) ) + $sv["grace_period"];
                                            array_push($device_array, array("t".$row["trans_id"], $time_seconds));
                                        } else 
                                            echo("<td align=\"center\">-</td>");
                                    } else { ?>
                                        <td align="right"></td>
                                        <td>
                                            <?php if($row['url'] && (preg_match($sv['ip_range_1'],getenv('REMOTE_ADDR')) || preg_match($sv['ip_range_2'],getenv('REMOTE_ADDR'))) ){ 
                                                Devices::printDot($staff, $row['d_id']);
                                                echo ("<a href=\"http://".$row["url"]."\">".$row["device_desc"]."</a>");
                                            } else {
                                                Devices::printDot($staff, $row['d_id']);
                                                echo $row['device_desc'];
                                            } ?>
                                        </td>
                                        <?php if($row["url"] && $staff){
                                            if ($staff->getRoleID() > 6){?>
                                                <td  align="center"><?php echo ("<a href=\"http://".$row["url"]."\">New Ticket</a>"); ?></td>
                                            <?php } else
                                                echo("<td align=\"center\">-</td>");
                                        } elseif($staff) {
                                            if ($staff->getRoleID() > 6){?>
                                                <td align="center"><div id="est"><a href="\pages\create.php?<?php echo("d_id=".$row["d_id"])?>">New Ticket</a></div></td>
                                            <?php } else
                                                echo("<td align=\"center\">-</td>");
                                        } ?>
                                        <td align="center"> - </td>
                                        <td align="center"> - </td>
                                    <?php } ?>
                                </tr>
                            <?php }
                        } ?>
                    </table>
                </div>
            </div>
        </div>
        <!-- /.col-md-8 -->
        <div class="col-md-4">
            <?php if ($sv['next'] >= 1 || $sv['eNext'] >= 1 || $sv['bNext'] >= 1 || $sv['mNext'] >= 1){ ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-list-ol fa-lg"></i> Now Serving
                    </div>
                    <div class="panel-body" id="now_serving_panel">
                        <div align="center" ><a href='http://fablab.uta.edu/policy/' style='color:blue'>UTA FabLab's Wait Policy</a></div>
                        <table class="table table-striped table-bordered" >
                            <?php if (is_object($staff) && $staff->getRoleID() >= $sv['LvlOfStaff']){ ?> <form method="post" action="">
                                <tr>
                                    <td>Equipment</td>
                                    <td>Now Serving</td>
                                    <td>Next #</td>
                                </tr>
                                <?php if ($sv['next'] != 0){ ?><tr id="next">
                                    <td>PolyPrinter</td>
                                    <td align="center"><h4 id="serving"><?php echo $sv['serving']; ?></h4></td>
                                    <td align="center"><button class="btn btn-basic" title="Click to issue the next Wait-Tab"
                                            name='print_s' onclick="loadingModal()"><?php echo $sv['next']+1; ?> <i class="fas fa-print"> </button></td>
                                </tr><?php } ?>
                                <?php if ($sv['eNext'] != 0){ ?><tr id="next">
                                    <td>Epilog Laser</td>
                                    <td align="center"><h4 id="eServing">E<?php echo $sv['eServing']; ?></h4></td>
                                    <td align="center"><button class="btn btn-basic" title="Click to issue the next Wait-Tab"
                                            name='print_e' onclick="loadingModal()">E<?php echo $sv['eNext']+1; ?> <i class="fas fa-print"> </button></td>
                                </tr><?php } ?>
                                <?php if ($sv['bNext'] != 0){ ?><tr id="next">
                                    <td>Boss Laser</td>
                                    <td align="center"><h4 id="bServing">B<?php echo $sv['bServing']; ?></h4></td>
                                    <td align="center"><button class="btn btn-basic" title="Click to issue the next Wait-Tab"
                                            name='print_b' onclick="loadingModal()">B<?php echo $sv['bNext']+1; ?> <i class="fas fa-print"> </button></td>
                                </tr><?php } ?>
                                <?php if ($sv['mNext'] != 0){ ?><tr id="next">
                                    <td><?php echo $sv['misc'];?></td>
                                    <td align="center"><h4 id="mServing">M<?php echo $sv['mServing']; ?></h4></td>
                                    <td align="center"><button class="btn btn-basic" title="Click to issue the next Wait-Tab"
                                            name='print_m' onclick="loadingModal()">M<?php echo $sv['mNext']+1; ?> <i class="fas fa-print"> </button></td>
                                </tr><?php } ?>
                            </form><?php } else { ?>
                                <tr>
                                    <td>Equipment</td>
                                    <td>Now Serving</td>
                                    <td>Next #</td>
                                </tr>
                                <?php if ($sv['next'] != 0){ ?><tr id="next">
                                    <td>PolyPrinter</td>
                                    <td align="center"><h4 id="serving"><?php echo $sv['serving']; ?></h4></td>
                                    <td align="center" title="Next Issuable Number"><?php echo $sv['next']+1; ?></td>
                                </tr><?php } ?>
                                <?php if ($sv['eNext'] != 0){ ?><tr id="next">
                                    <td>Epilog Laser</td>
                                    <td align="center"><h4 id="eServing">E<?php echo $sv['eServing']; ?></h4></td>
                                    <td align="center" title="Next Issuable Number">E<?php echo $sv['eNext']+1; ?></td>
                                </tr><?php } ?>
                                <?php if ($sv['bNext'] != 0){ ?><tr id="next">
                                    <td>Boss Laser</td>
                                    <td align="center"><h4 id="bServing">B<?php echo $sv['bServing']; ?></h4></td>
                                    <td align="center" title="Next Issuable Number">B<?php echo $sv['bNext']+1; ?></td>
                                </tr><?php } ?>
                                <?php if ($sv['mNext'] != 0){ ?><tr id="next">
                                    <td><?php echo $sv['misc'];?></td>
                                    <td align="center"><h4 id="mServing">M<?php echo $sv['mServing']; ?></h4></td>
                                    <td align="center" title="Next Issuable Number">M<?php echo $sv['mNext']+1; ?></td>
                                </tr><?php } ?>
                            <?php } ?>
                        </table>
                    </div>
                    <div class="panel-footer"><form method="post" action="">
                        <input name="myNum" id="myNum" type="text" title="Have a Wait-Tab? Enter your number and get a browser based alert." value='<?php if (isset($_SESSION['myNum'])){echo $_SESSION['myNum'];}?>'/>
                        <button class="btn btn-warning" title="Have a Wait-Tab? Enter your number and get a browser based alert." name="alertBtn">Pop-Up Alert</button>
                    </div></form>
                </div>
                <!-- /.panel -->
            <?php } ?>
        </div>
        <!-- /.col-md-4 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script>
<?php foreach ($device_array as $da) { ?>
    var time = <?php echo $da[1];?>;
    var display = document.getElementById('<?php echo $da[0];?>');
    startTimer(time, display);
    
<?php } ?>
    $('#indexTable').DataTable({
        "iDisplayLength": 25,
        "order": []
    });
<?php if(!is_object($staff) && ($sv['next'] >= 1 || $sv['eNext'] >= 1 || $sv['bNext'] >= 1 || $sv['mNext'] >= 1)) { ?>
    //Update page if number changes, check every
    setInterval(function(){
        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            // code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("now_serving_panel").innerHTML = this.responseText;
            }
        };
        xmlhttp.open("GET","pages/sub/getWait.php",true);
        xmlhttp.send();
        myNum = document.getElementById("myNum").value;

        <?php if($sv["serving"] != 0) { ?>
            var x = document.getElementById("serving").innerHTML;
            if (x == myNum){
                var msg = "Your Number: " + myNum + " has been called.";
                setTimeout(function(){alert(msg);window.location = "index.php";}, 2500);
                document.getElementById("myNum").value = "";
            }
        <?php } ?>
        <?php if($sv["eServing"] != 0) { ?>
            var x = document.getElementById("eServing").innerHTML;
            if (x == myNum){
                var msg = "Your Number: " + myNum + " has been called.";
                setTimeout(function(){alert(msg);window.location = "index.php";}, 2500);
                document.getElementById("myNum").value = "";
            }
        <?php } ?>
        <?php if($sv["bServing"] != 0) { ?>
            var x = document.getElementById("bServing").innerHTML;
            if (x == myNum){
                var msg = "Your Number: " + myNum + " has been called.";
                setTimeout(function(){alert(msg);window.location = "index.php";}, 2500);
                document.getElementById("myNum").value = "";
            }
        <?php } ?>
        <?php if($sv["mServing"] != 0) { ?>
            var x = document.getElementById("mServing").innerHTML;
            if (x == myNum){
                var msg = "Your Number: " + myNum + " has been called.";
                setTimeout(function(){alert(msg);window.location = "index.php";}, 2500);
                document.getElementById("myNum").value = "";
            }
        <?php } ?>
    }, 5000);
<?php } ?>
    
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
    
    var str;
    for(var i=1; i<= <?php echo $number_of_queue_tables;?>; i++){
        str = "#waitTable_"+i
        $(str).DataTable({
                    "iDisplayLength": 10,
                    "order": []
                    });
    }
    
</script>