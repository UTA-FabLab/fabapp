<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$device_array = array();
$_SESSION['type'] = "home";
?>
<title>FapApp Dashboard</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Dashboard</h1>
            <?php echo "IP address - ".getenv('REMOTE_ADDR'); ?>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-ticket fa-fw"></i> Device Status
                </div>
                <div class="panel-body">
<table class="table table-striped table-bordered table-hover">
    <tr class="tablerow">
            <td align="right">Ticket</td>
            <td>Device</td>
            <td>Start Time</td>
            <td>Est Remaining Time</td>
            <?php if ($staff) { ?> <td>Action</td><?php } ?>
    </tr>
    <?php if ($result = $mysqli->query("
                    SELECT trans_id, device_desc, t_start, est_time, devices.dg_id, dg_parent, devices.d_id, url
                    FROM devices
                    JOIN device_group
                    ON devices.dg_id = device_group.dg_id
                    LEFT JOIN (SELECT trans_id, t_start, t_end, est_time, d_id FROM transactions WHERE status_id < 12 ORDER BY trans_id DESC) as t 
                    ON devices.d_id = t.d_id
                    WHERE public_view = 'Y'
                    ORDER BY dg_parent DESC, dg_id, `device_desc`
            ")){
                    while ( $row = $result->fetch_assoc() ){ ?>
                    <tr class="tablerow">
                            <?php if($row["t_start"]) { ?>
                                    <td align="right"><?php echo $row["trans_id"]; ?></td>
                                    <?php if($row['url'] && (preg_match($sv['ip_range_1'],getenv('REMOTE_ADDR')) || preg_match($sv['ip_range_2'],getenv('REMOTE_ADDR'))) ){ ?>
                                            <td><?php echo ("<a href=\"http://".$row["url"]."\">".$row["device_desc"]."</a>"); ?></td>
                                    <?php }else{ ?>
                                            <td><?php echo $row["device_desc"]; ?></td><?php }
                                    echo("<td>".date( 'M d g:i a',strtotime($row["t_start"]) )."</td>" );
                                    if( isset($row["est_time"]) ){
                                            echo("<td align=\"center\"><div id=\"est".$row["trans_id"]."\">".$row["est_time"]." </div></td>" ); 
                                    } else 
                                            echo("<td align=\"center\">-</td>"); 
                                    if ($staff) {?>
                                    <td align="center">
                                            <button onclick="endTicket(<?php echo $row["trans_id"].",'".$row["device_desc"]."'"; ?>)">End Ticket</button>
                                    </td>
                                    <?php } if ( isset($row["est_time"])) {
                                            $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $row["est_time"]);
                                            sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
                                            $time_seconds = $hours * 3600 + $minutes * 60 + $seconds;

                                            $time_seconds = $time_seconds - (time() - strtotime($row["t_start"]) ) + $sv["grace_period"];
                                            array_push($device_array, array($row["trans_id"], $time_seconds, $row["dg_parent"]));
                                    } 
                            } else { ?>
                                    <td align="right"></td>
                                    <?php if($row['url'] && (preg_match($sv['ip_range_1'],getenv('REMOTE_ADDR')) || preg_match($sv['ip_range_2'],getenv('REMOTE_ADDR'))) ){ ?>
                                            <td><?php echo ("<a href=\"http://".$row["url"]."\">".$row["device_desc"]."</a>"); ?></td>
                                    <?php }else{ ?>
                                            <td><?php echo $row["device_desc"]; ?></td><?php }?>
                                    <td align="center"> - </td>
                                    <td align="center"> - </td>
                                    <?php if($row["url"] && $staff){ ?>
                                            <td  align="center"><?php echo ("<a href=\"http://".$row["url"]."\">New Ticket</a>"); ?></td>
                                    <?php }elseif($staff) { ?>
                                            <td align="center"><div id="est"><a href="\pages\create.php?<?php echo("d_id=".$row["d_id"])?>">New Ticket</a></div></td>
                                    <?php }?>

                            <?php } ?>
                    </tr>
                            <?php
                    }

            } 
    ?>
</table>
                </div>
            </div>
        </div>
        <!-- /.col-lg-8 -->
        <div class="col-lg-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-linode fa-fw"></i> Inventory
                </div>
                <div class="panel-body">
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th><i class="fa fa-paint-brush fa-fw"></i></th>
                                <th>Qty on Hand</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php //Display Inventory Based on device group
                        if($result = $mysqli->query("
                            SELECT m_name, SUM(unit_used) as sum, color_hex, unit
                            FROM materials
                            LEFT JOIN device_materials
                            ON device_materials.m_id = materials.m_id
                            LEFT JOIN mats_used
                            ON mats_used.m_id = materials.m_id
                            WHERE dg_id = 2
                            GROUP BY m_name
                            ORDER BY m_name ASC;
                        ")){
                            while ($row = $result->fetch_assoc()){ ?>
                            <tr>
                                <td><?php echo $row['m_name']; ?></td>
                                <td><div class="color-box" style="background-color: #<?php echo $row['color_hex'];?>;"/></td>
                                <td><?php echo number_format($row['sum'])." ".$row['unit']; ?></td>
                            </tr>
                            <?php }
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
        <!-- /.col-lg-4 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script>
window.onload = function () {
    <?php foreach ($device_array as $da) { ?>
            var time = <?php echo $da[1];?>;
            var display = document.getElementById('est<?php echo $da[0];?>');
            var dg_parent = <?php if ($da[2]) echo $da[2]; else echo "0";?>;
            startTimer(time, display, dg_parent);

    <?php } ?>
};
</script>