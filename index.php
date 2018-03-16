<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$device_array = array();
$_SESSION['type'] = "home";

//print details of $staff
//print_r($staff);
?>
<title><?php echo $sv['site_name'];?> Dashboard</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">Dashboard</h1>
        </div>
        <!-- /.col-md-12 -->
    </div>
    <!-- /.row -->
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
                                <th>Start Time</th>
                                <th>Est Remaining Time</th>
                                <?php if ($staff) { ?> <th>Action</th><?php } ?>
                            </tr>
                        </thead>
                        <?php if ($result = $mysqli->query("
                            SELECT trans_id, device_desc, t_start, est_time, devices.dg_id, dg_parent, devices.d_id, url, operator, status_id
                            FROM devices
                            JOIN device_group
                            ON devices.dg_id = device_group.dg_id
                            LEFT JOIN (SELECT trans_id, t_start, t_end, est_time, d_id, operator, status_id FROM transactions WHERE status_id < 12 ORDER BY trans_id DESC) as t 
                            ON devices.d_id = t.d_id
                            WHERE public_view = 'Y'
                            ORDER BY dg_id, `device_desc`
                        ")){
                            while ( $row = $result->fetch_assoc() ){ ?>
                                <tr class="tablerow">
                                    <?php if($row["t_start"]) {
                                        $ticket = new Transactions($row['trans_id']); ?>
                                        <td align="right"><?php echo ("<a href=\"pages/lookup.php?trans_id=$row[trans_id]\">$row[trans_id]</a>"); ?></td>
                                        <td>
                                            <?php if($ticket->getDevice()->getUrl() && (preg_match($sv['ip_range_1'],getenv('REMOTE_ADDR')) || preg_match($sv['ip_range_2'],getenv('REMOTE_ADDR'))) ){
                                                    Devices::printDot($staff, $row['d_id'], $ticket->getDevice()->getD_id());
                                                    //echo ("<a href=\"http://".$row["url"]."\">".$row["device_desc"]."</a>");
                                                    echo ("<a href=\"http://".$ticket->getDevice()->getUrl()."\">".$ticket->getDevice()->getDevice_desc()."</a>");
                                                ?>
                                            <?php } else {
                                                Devices::printDot($staff, $ticket->getDevice()->getD_id());
                                                echo $ticket->getDevice()->getDevice_desc();
                                            } ?>
                                        </td>
                                        <?php echo("<td>".date( 'M d g:i a',strtotime($row["t_start"]) )."</td>" );
                                        if( $row["status_id"] == 11) {
                                            echo("<td align='center'>".$ticket->getStatus()->getMsg()."</td>");
                                        } elseif (isset($row["est_time"])) {
                                            echo("<td align='center'><div id=\"est".$row["trans_id"]."\">".$row["est_time"]." </div></td>" );
                                            $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $row["est_time"]);
                                            sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
                                            $time_seconds = $hours * 3600 + $minutes * 60 + $seconds- (time() - strtotime($row["t_start"]) ) + $sv["grace_period"];
                                            array_push($device_array, array($row["trans_id"], $time_seconds, $row["dg_parent"]));
                                        } else 
                                            echo("<td align=\"center\">-</td>"); 
                                        if ($staff && ($staff->getRoleID() >= $sv['LvlOfStaff'] || $staff->getOperator() == $ticket->getUser()->getOperator())) { ?>
                                            <td align="center">
                                                <button onclick="endTicket(<?php echo $row["trans_id"].",'".$row["device_desc"]."'"; ?>)">End Ticket</button>
                                            </td>
                                        <?php } elseif ($staff) {
                                            echo("<td align='center'></td>");
                                        }
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
                                        <td align="center"> - </td>
                                        <td align="center"> - </td>
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
                                        }
                                    } ?>
                                </tr>
                            <?php }
                        } ?>
                    </table>
                </div>
            </div>
        </div>
        <!-- /.col-md-8 -->
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fab fa-linode fa-fw"></i> Inventory
                </div>
                <div class="panel-body">
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th><i class="fas fa-paint-brush fa-fw"></i></th>
                                <?php if ($staff && $staff->getRoleID() >= $sv['LvlOfStaff']){?>
                                        <th>Qty on Hand</th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php //Display Inventory Based on device group
                        if($result = $mysqli->query("
                            SELECT `m_name`, SUM(unit_used) as `sum`, `color_hex`, `unit`
                            FROM `materials`
                            LEFT JOIN `mats_used`
                            ON mats_used.m_id = `materials`.`m_id`
                            WHERE `m_parent` = 1
                            GROUP BY `m_name`, `color_hex`, `unit`
                            ORDER BY `m_name` ASC;
                        ")){
                            while ($row = $result->fetch_assoc()){
                                //if ($staff && $staff->getRoleID() >= $sv['LvlOfStaff']){ 
                                if (true){?>
                                    <tr>
                                        <td><?php echo $row['m_name']; ?></td>
                                        <td><div class="color-box" style="background-color: #<?php echo $row['color_hex'];?>;"/></td>
                                        <td><?php echo number_format($row['sum'])." ".$row['unit']; ?></td>
                                    </tr>
                                <?php } else {?>
                                    <tr>
                                        <td><?php echo $row['m_name']; ?></td>
                                        <td><div class="color-box" style="background-color: #<?php echo $row['color_hex'];?>;"/></td>
                                    </tr>
                                <?php }
                            }
                        } else { ?>
                            <tr><td colspan="3">None</td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
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
	var display = document.getElementById('est<?php echo $da[0];?>');
	var dg_parent = <?php if ($da[2]) echo $da[2]; else echo "0";?>;
	startTimer(time, display, dg_parent);
	
<?php } ?>
    $('#indexTable').DataTable({
        "iDisplayLength": 25,
        "order": []
    });
</script>