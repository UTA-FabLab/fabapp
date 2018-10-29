<?php 
/*
 * Jon Le 2016-2018
 * FabApp V 0.91
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

if (!isset($staff) || ($staff->getRoleID() < $sv['LvlOfStaff'] && $staff->getRoleID() != $sv['serviceTechnican'])) {
    //Not Authorized to see this Page
    $_SESSION['error_msg'] = "Not Authorized to view this page";
    header('Location: /index.php');
}
if (!empty($_GET["d_id"]) && Devices::regexDID($_GET["d_id"])) {
    $device = new Devices(filter_input(INPUT_GET, 'd_id'));
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['btnHistory'])){
    $d_id = filter_input(INPUT_POST, 'devices');
    if (Devices::regexDID($d_id)){
        header("Location:/pages/sr_issue.php?d_id=$d_id");
    }
}
?>
<title><?php echo $sv['site_name'];?> Open Service Issues</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Open Service Issues</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-history fa-fw"></i> Unresolved Issues
                </div>
                <table id="historyTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Device Name</th>
                            <th>Opened By</th>
                            <th>Reply Count</th>
                            <th>Solved</th>
                            <th>Service Notes</th>
                        </tr>
                    </thead>
                    <?php $result = Service_call::openSC();
                    while($row = $result->fetch_assoc()){ ?>
                    <tr>
                        <td><?php echo $row['device_desc']; ?></td>
                        <td>
                            <?php $staff = Staff::withID($row['staff_id']); ?>
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                    <i class='far fa-calendar-alt' title="<?php echo date($sv['dateFormat'], strtotime($row["sc_time"])); ?>"></i>
                                </button>
                                <ul class="dropdown-menu" role="menu">
                                    <li style="padding-left: 5px;"><?php echo date($sv['dateFormat'], strtotime($row["sc_time"])); ?></li>
                                </ul>
                            </div>
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
            <!--/panel -->
        </div>
        <!-- /.col-lg-8 -->
        <div class="col-lg-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="far fa-comment fa-fw"></i> Report New Issue
                </div>
                <div class="panel-body">
                    <form method="post" action="" onsubmit="return validateBtn()">
                    <table class="table table-bordered table-striped table-hover">
                        <tr>
                            <td><span title="Which device does this wait ticket belong to?">Select Device Group</span></td>
                            <td>
                                <select name="dg_id" id="dg_id" onchange="change_dg()" tabindex="2">
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
                                <select name="devices" id="devices" tabindex="2">
                                    <option value =""> Select Group First</option>
                                </select>
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
            "order": [1, "desc"]
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
    
    function  validateBtn(){
        if (!stdRegEx("devices", /^\d+/, "Select a Device")){
            return false;
        }
        
    }
</script>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>