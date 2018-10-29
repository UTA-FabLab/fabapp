<?php 
/*
 * Jon Le 2016-2018
 * FabApp V 0.91
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

if (!isset($staff) || ($staff->getRoleID() < $sv['LvlOfStaff'] && $staff->getRoleID() != $sv['serviceTechnican'])) {
    // Not Authorized to see this Page
    $_SESSION['error_msg'] = "You must be logged in to report an issue.";
    header ( 'Location: /index.php' );
}
if (!empty(filter_input(INPUT_GET, "d_id")) && Devices::regexDID($_GET["d_id"])) {
    $device = new Devices(filter_input(INPUT_GET, 'd_id'));
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && filter_has_var(INPUT_POST, 'btnHistory') ){
    $d_id = explode("-", filter_input(INPUT_POST, 'devices'));
    if (Devices::regexDID($d_id[0])){
        $device = new Devices($d_id[0]);
    }
} elseif($_SERVER["REQUEST_METHOD"] === "POST" && filter_has_var(INPUT_POST, 'issueBtn')) {
    //issueBtn
    $d_id = filter_input(INPUT_POST, 'd_id');
    header("Location:/pages/sr_issue.php?d_id=$d_id");
}
?>
<title><?php echo $sv['site_name'];?> Service History</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Service History</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <?php if (isset($device)){ ?>
        <div class="col-lg-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-history fa-fw"></i>Service History : 
                        <?php Devices::printDot($staff, $device->getD_id()); 
                        echo $device->getDevice_desc();?>
                </div>
                <div class="panel-body">
                    <table id="historyTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Opened By</th>
                                <th>Reply Count</th>
                                <th>Solved</th>
                                <th>Service Notes</th>
                            </tr>
                        </thead>
                        <?php $result = Service_call::byDevice($device);
                        while($row = $result->fetch_assoc()){ ?>
                        <tr>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                        <i class='far fa-calendar-alt' title="<?php echo date($sv['dateFormat'], strtotime($row["sc_time"])); ?>"></i>
                                    </button>
                                    <ul class="dropdown-menu" role="menu">
                                        <li style="padding-left: 5px;"><?php echo date($sv['dateFormat'], strtotime($row["sc_time"])); ?></li>
                                    </ul>
                                </div>
                                <?php $staff = Staff::withID($row['staff_id']); ?>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                        <i class="<?php echo $staff->getIcon();?> fa-lg" title="<?php echo $staff->getOperator();?>"></i>
                                    </button>
                                    <ul class="dropdown-menu" role="menu">
                                        <li style="padding-left: 5px;"><?php echo $staff->getOperator();?></li>
                                    </ul>
                                </div>
                            </td>
                            <td><?php 
                                $sr = Service_reply::bySc_id($row['sc_id']);
                                echo("<a href = '/pages/sr_log.php?sc_id=$row[sc_id]'>".count($sr)."</a>"); 
                            ?></td>
                            <td>
                                <?php if($row['solved'] == 'Y'){
                                    echo("<a href = '/pages/sr_log.php?sc_id=$row[sc_id]'>Complete</a>");
                                } else {
                                    echo("<a href = '/pages/sr_log.php?sc_id=$row[sc_id]'>Incomplete</a>");
                                } ?>
                            </td>
                            <td>
                                <?php Service_lvl::getDot($row['sl_id']);
                                echo "<strong>" . Service_lvl::sltoMsg($row['sl_id']) . "</strong> - " . $row['sc_notes'];?>
                            </td>
                        </tr>
                        <?php } ?>
                    </table>
                </div>
                <div class="panel-footer clearfix">
                    <form method="post" action="">
                        <input type="text" hidden value="<?php echo $device->getD_id(); ?>" name="d_id">
                        <div class="pull-right">
                            <button type="submit" class="btn btn-warning" name="issueBtn">Report Issue</button>
                        </div>
                    </form>
                </div>
            </div>
            <!--/panel -->
        </div>
        <!-- /.col-lg-8 -->
<?php } else { ?>
        <div class="col-lg-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-history fa-fw"></i>Service History
                </div>
                <div class="panel-body">
                    No Device Selected
                </div>
            </div>
            <!--/panel -->
        </div>
        <!-- /.col-lg-8 -->
<?php } ?>
        <div class="col-lg-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-search fa-fw"></i>Select a device to see the service history
                </div>
                <div class="panel-body">
            <form method="post" action="">
                    <table class="table table-bordered table-striped table-hover">
                        <tr>
                            <td><span title="Which device does this wait ticket belong to?">Select Device Group</span></td>
                            <td>
                                <select class="form-control" name="dg_id" id="dg_id" onchange="change_dg()" tabindex="2">
                                    <option disabled hidden selected value="">Device Group</option>
                                    <?php if($result = $mysqli->query("
                                        SELECT DISTINCT `device_group`.`dg_id`, `device_group`.`dg_desc`
                                        FROM `devices`
                                        LEFT JOIN `device_group`
                                        ON `device_group`.`dg_id` = `devices`.`dg_id`
                                        ORDER BY `dg_desc`
                                    ")){
                                            while($row = $result->fetch_assoc()){
                                            echo("<option value='$row[dg_id]'>$row[dg_desc]</option>");
                                        }
                                    } else {
                                        echo ("Device list Error - SQL ERROR");
                                    }?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Select Device
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-addon" id="dot_span">
                                        <i class='fas fa-circle fa-lg' style='color:gainsboro'></i>
                                    </span>
                                    <select class="form-control" name="devices" id="devices" tabindex="2" onchange="change_dot()">
                                        <option value =""> Select Group First</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="panel-footer clearfix">
                    <div class="pull-right">
                        <button class="btn btn-primary" name="btnHistory">Select</button>
                    </div>
                </div>
                </form>
            </div>
            <!--panel -->
        </div>
        <!-- /.col-lg-4 -->
    </div>
    <!-- /.row -->
</div>
<script type="text/javascript" charset="utf-8">
    window.onload = function() {
        $('#historyTable').DataTable({
            "order": [0, "desc"]
        });
    };
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
        
        xmlhttp.open("GET","/pages/sub/getDevices.php?dg_id="+ document.getElementById("dg_id").value, true);
        xmlhttp.send();
    }
</script>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>