<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 * 
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$device_array = array();
$_SESSION['type'] = "home";

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
        wait($i);
        advanceNum($i, "eNext");
        
    } elseif( isset($_POST['print_b']) ){
        $i = $sv['bNext']+1;
        wait($i);
        advanceNum($i, "bNext");
        
    } elseif( isset($_POST['print_m']) ){
        $i = $sv['mNext']+1;
        wait($i);
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
                                            } else {
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
            <?php } ?>
                    
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-warehouse fa-fw"></i> Inventory
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
</script>