<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
include_once ($_SERVER['DOCUMENT_ROOT'].'/api/gatekeeper.php');
$time = $operator = $errorMsg = "";
$error = $loadError = false;

// Check Device ID
if (empty($_GET["d_id"])){
    $errorMsg = "Device ID is Missing.";
    $error = true;
} else {
    $d_id = $_GET["d_id"];
    if (!Devices::regexDID($d_id)) {
        $errorMsg = "Bad device ID";	
        $error = true;
    } elseif($result = $mysqli->query("
        SELECT device_desc, dg_name, d_duration, devices.dg_id, device_id
        FROM devices
        JOIN device_group 
        ON devices.dg_id = device_group.dg_id
        WHERE device_id = $d_id
        LIMIT 1;
    ")){
        if ($result->num_rows == 0){
            $errorMsg = "Device Not Found.";
            $error = true;
        } else {
            $row = $result->fetch_assoc();
            $d_duration = $row["d_duration"];
            $device_desc = $row["device_desc"];
            $device_id = $row["device_id"];
            $dg_id = $row["dg_id"];
            $device_mats = Materials::getDeviceMats($dg_id);
            $dg_name = $row["dg_name"];
            //convert from 00:00:00 to hour decimal
            $timeArry = explode(':', $d_duration);
            $hour = $timeArry[0];
            $minutes = $timeArry[1];
            $limit = $hour + $minutes/60;
        }
    } else {
        $errorMsg = $mysqli->error;
        $error = true;
    }
}

//When the user hits Submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ticketBtn'])) {
    //set status id to "Powered On"
    $status_id = 10;
    
    //Call Gatekeeper to regex UTAID and validate if User is authorized
    foreach (gatekeeper($_POST["operator"], $device_id) as $key => $value){
        $gk_msg[$key] =  $value;
    }

    if ($gk_msg["authorized"] == "N"){
        $errorMsg = "Status Code:".$gk_msg["status_id"]." - ".$gk_msg["ERROR"];
        $error = true;
    }    
    
    // Check if time has been selected
    if ( $limit > 0 ) { 
        $time = "$hour:$minutes:00";
    } elseif (!isset($_POST["hours"]) && !isset($_POST["minutes"])){
        $errorMsg = "Specify Time".$_POST["hours"];
        $error = true;
    } elseif ( preg_match("/^\d{1,3}$/",$_POST["hours"]) == 0 && preg_match("/^\d{1,2}$/",$_POST["minutes"]) == 0) {
        $errorMsg = "Invalid Time - ".$_POST["hours"].":".$_POST["minutes"];
        $error = true;
    } else {
        $hh = $_POST["hours"];
        $mm = $_POST["minutes"];
        $time = $_POST["hours"].":".$_POST["minutes"].":00";
    }
    
    if ($error){
        echo "<script> alert('$errorMsg')</script>";
    } elseif ($loadError) {
        echo "Error Loading Page";
    } elseif (strcmp($dg_name,"uprint") == 0){//process form for uPrint
        uPrintForm($_POST["operator"], $d_id, $time, $_POST["p_id"], $status_id, $device_mats, $staff->getOperator());
    } elseif (strcmp($dg_name,"screen") == 0) {//process form for screen printing
        echo "Screen Form";
    } else {//Generic Transaction Form
        genericForm($_POST["operator"], $d_id, $time, $_POST["p_id"], $status_id, $staff->getOperator(), $_POST['m_id']);
    }
}

function uPrintForm($operator, $d_id, $est_time, $p_id, $status_id, $device_mats, $staff_id){
    global $_POST;
    $error = false;
    
    //Regex Volume
    for( $i=0; $i < count($device_mats); $i++){
        $m_id = $device_mats[$i]['m_id'];
        if (!Mats_Used::regexUnit_Used($_POST["m_$m_id"]))
            return;
        $device_mats[$i]['volume'] = $_POST["m_$m_id"];
    }
    
    //Attempt to create new Ticket
    $trans_id = Transactions::insertTrans($operator, $d_id, $est_time, $p_id, $status_id, $staff_id);
    if (is_int($trans_id)) {
        foreach($device_mats as $dm){
            if(Mats_Used::insert_Mats_used($insID, $dm["m_id"], $dm["volume"], $status_id, $staff_id, "") === false)
                echo "<script> alert('Can Not Add Materials Used\\nfor ticket - $insID')</script>";
        }
        $_SESSION['type'] = "uprint";
        header("Location:/pay.php?trans_id=".$trans_id);
    } else {
        //$insID is not an integer, it must be an error Message
        echo "<script> alert('Can Not Create a new Ticket - $trans_id')</script>";
    }
}

function genericForm($operator, $d_id, $est_time, $p_id, $status_id, $staff_id, $m_id){
    //Attempt to create new Ticket
    $trans_id = Transactions::insertTrans($operator, $d_id, $est_time, $p_id, $status_id, $staff_id);
    if (is_int($trans_id)) {
        if(strcmp($m_id,"none") != 0)
            if(Mats_Used::insert_Mats($trans_id, $m_id, $status_id, $staff_id) === false)
                echo "<script> alert('Can Not Indicate Materials Used\\nfor ticket - $trans_id')</script>";
        header("Location:/index.php");
    } else {
        //$insID is not an integer, it must be an error Message
        echo "<script> alert('Can Not Create a new Ticket - $trans_id')</script>";
    }
}
?>
<title>FabLab Create Ticket</title>
<div id="page-wrapper">
<?php //if logged in and have role id of 8 or greater
if ($staff) {
    if ($staff->getRoleID() > 7 && !$loadError){
?>
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Create new Ticket for the <?php echo $device_desc; ?></h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-8">
            <div class="panel panel-default">
                <div class="panel-body">
                    <table class="table table-striped table-bordered table-hover">
                        <form id="cform" name="cform" method="post" action="" onsubmit="return validateForm()" autocomplete='off'>
                        <tr class="tablerow">
                                <td align="Center">Device</td>
                                <td><?php echo $device_desc?></td>
                        </tr>
                        <tr class="tablerow">
                                <td align="center">ID Number</td>
                                <td><input type="text" name="operator" placeholder="1000000000" value="<?php echo $operator;?>"
                                 maxlength="10" size="10" autofocus tabindex="1"></td>
                        </tr>
                        <?php //Material Input for uPrint
                        if (strcmp($dg_name,"uprint") == 0){ ?>
                            <tr class="tableheader">
                                    <td align="center" colspan=2>Payment Required First<div id="uprint" style="float:right">$ 0.00 </div></td>
                            </tr>
                            <?php foreach($device_mats as $dm) { ?>
                                <tr class="tablerow">
                                    <td align="center"><?php echo $dm["m_name"];?></td>
                                    <td><input type="number" name="<?php echo "m_$dm[m_id]";?>" 
                                        id="<?php echo "m_$dm[m_id]";?>"
                                        min="0" max="1000" step=".01" tabindex="2" onchange="uPrint()"
                                        onkeyup="uPrint()"> in^3 <?php echo " - $$dm[price] per $dm[unit]";?></td>
                                </tr>
                            <?php }
                        } elseif(strcmp($dg_name,"screen") == 0){ ?>
                            <tr class="tablerow">
                                <td align="Center">Material</td>
                                <td><select name="m_id" id="m_id" tabindex="2">
                                    <option disabled hidden selected value="">Select</option>
                                        <?php foreach($device_mats as $dm) {
                                            echo("<option value='".$dm["m_id"]."'>".$dm["m_name"]."</option>");
                                        } ?>
                                </select></td>
                            </tr>
                        <?php } else { ?>
                            <tr class="tablerow">
                                <?php if (strcmp($dg_name,"vinyl") == 0){ ?>
                                    <td align="Center">Material</td>
                                    <td><b>Indicate the vinyl used when closing this ticket.</b>
                                        <select name="m_id" id="m_id" tabindex="2" disabled="true" hidden>
                                            <option selected value="none">None</option>
                                        </select>
                                    </td>
                                <?php } elseif(count($device_mats) > 0){ ?>
                                    <td align="Center">Material</td>
                                    <td><select name="m_id" id="m_id" tabindex="2">
                                        <option disabled hidden selected value="">Select</option>
                                        <?php foreach($device_mats as $dm) {
                                            echo("<option value='".$dm["m_id"]."'>".$dm["m_name"]."</option>");
                                        } ?>
                                    </select></td>
                                <?php } else { ?>
                                    <td align="Center">Material</td>
                                    <td><select name="m_id" id="m_id" tabindex="2" disabled="true">
                                        <option selected value="none">None</option>
                                    </select></td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                        <tr class="tablerow">
                                <?php if ($limit > 0){ ?>
                                <td align="center">Max Time</td>
                                    <td>
                                        <input type="text" size="1" name="hours" disabled value="<?php echo $limit?>"></input> Hour
                                    </td>
                                <?php } else {?>
                                    <td align="center">Estimated Time</td>
                                    <td>
                                        <input type="number" name="hours" id="hours" tabindex="6" min="0" max="100" 
                                            step="1" placeholder="hh" ></input>Hours
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
                                <?php } ?>
                        </tr>
                        <tr class="tablerow">
                            <?php //Build Purpose Option List
                            if($pArray = Purpose::getList()){ ?>
                                <td align="Center">Purpose of Visit</td>
                                <td><select name="p_id" tabindex="8">
                                    <option disabled hidden selected value="">Select</option>
                                <?php foreach($pArray as $key => $value){ 
                                    echo("<option value='$key'>$value</option>");
                                }
                            } ?>
                        </tr>
                        <tr class="tablerow">
                                <td align="center"><input type="button" onclick="resetForm()" value="Reset form"></td>
                                <td align="right"><input type="submit" name="ticketBtn" value="Submit" tabindex="9"></td>
                        </tr>
                        </form>
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
                            WHERE dg_id = $dg_id
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
<script type="text/javascript">
function resetForm() {
    document.getElementById("cform").reset();
}
	
function validateForm() {
    var x = document.forms["cform"]["operator"].value;
	var reg = /^\d{10}$/;
	//Mav ID Check
    if (x == null || x == "" || !reg.test(x)) {
        if (!reg.test(x)) {
			alert("Invalid ID #");
		}
		document.forms["cform"]["operator"].focus();
        return false;
    }
    //Material Check Uprint
    <?php if (strcmp($dg_name,"uprint") == 0) {
        foreach($device_mats as $dm){ 
            echo ("var m_$dm[m_id] = document.forms['cform']['m_$dm[m_id]'].value;\n\t");
            echo ("if (m_$dm[m_id] == null || m_$dm[m_id] == '') {");
            echo ("\n\t\talert('Please enter uPrint Volume');");
            echo ("\n\t\tdocument.forms['cform']['m_$dm[m_id]'].focus();");
            echo ("\n\t\treturn false;");
            echo ("\n\t}\n\t");
        } ?>
    <?php } else {?>
        //Material Check
        var y = document.forms["cform"]["m_id"].value;
        if (y == null || y == "") {
            alert("Please select a material");
            document.forms["cform"]["m_id"].focus();
            return false;
        }
    <?php } echo "\n";?>
    //Time Check
    <?php if ($limit == 0) {?>
        var h = parseInt(document.forms["cform"]["hours"].value);
        var m = parseInt(document.forms["cform"]["minutes"].value);
        h = h + m/60;
        if (h == 0){
            alert("Please Estimate Time.");
            document.forms["cform"]["hours"].focus();
            return false;
        }
    <?php } ?>
    //Purpose Check
    var p = document.forms["cform"]["p_id"].value;
    if (p == null || p == "") {
        alert("Please select a Purpose");
        document.forms["cform"]["p_id"].focus();
        return false;
    }
}

<?php if (strcmp($dg_name,"uprint") == 0){ ?>
function uPrint () { <?php
    $s = "var total = ( conv_rate*(";
    echo ("\n\tvar conv_rate = $sv[uprint_conv];\n\t");
    foreach($device_mats as $dm){
        echo ("var rate$dm[m_id] = $dm[price];\n\t");
        echo("var m_$dm[m_id] = document.getElementById('m_$dm[m_id]').value;\n\t");
        $s .="m_$dm[m_id] * rate$dm[m_id] +";
    }
    $s = substr($s, 0, -1);
    echo ("$s)).toFixed(2);\n"); ?>
    document.getElementById("uprint").innerHTML = "$ " + total;
    }
<?php } ?>
</script>

<?php } else {
//Insufficent Role Code or Load Error ?>
    <div class="row">
        <div class="col-lg-12">
                <h1 class="page-header">Insufficent Clearance Code or Load Error.</h1>
                Please Notify Staff. <?php echo "$loadError - $errorMsg";?>
        </div>
        <!-- /.col-lg-12 -->
    </div>
</div>
<?php } } else { 
    ?>
    <div class="row">
        <div class="col-lg-12">
                <h1 class="page-header">Create new Ticket for the <?php echo $device_desc; ?></h1>
                Please Log In
        </div>
        <!-- /.col-lg-12 -->
    </div>
</div>
<!-- /#page-wrapper -->
<?php } 
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>