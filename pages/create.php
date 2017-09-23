<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
require_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/api/gatekeeper.php');
$time = $errorMsg = "";
$error = false;

// Check Device ID
if (empty($_GET["d_id"])){
    $errorMsg = "Device ID is Missing.";
    $error = true;
} else {
    $d_id = filter_input(INPUT_GET,'d_id');
    if (!Devices::regexDID($d_id)) {
        $errorMsg = "Bad device ID";	
        $error = true;
    } else {
        $device = new Devices($d_id);
        $device_mats = Materials::getDeviceMats($device->getDg()->getDg_id());
        //convert the time limit of a device
        $timeArry = explode(':', $device->getD_duration());
        $hour = $timeArry[0];
        $minutes = $timeArry[1];
        $limit = $hour + $minutes/60;
    }
}

//When the user hits Submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ticketBtn'])) {
    //set status id to "Powered On"
    $status_id = 0;
    
    //Call Gatekeeper to regex UTAID and validate if User is authorized
    foreach (gatekeeper($_POST["operator"], $device->getD_id()) as $key => $value){
        $gk_msg[$key] =  $value;
    }
    

    if ($gk_msg["authorized"] == "N"){
        $errorMsg = "Status Code:".$gk_msg["status_id"]." - ".$gk_msg["ERROR"];
        $error = true;
    } else {
	$status_id = $gk_msg['status_id'];
        $operator = Users::withID(filter_input(INPUT_POST,'operator'));
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
        $time = $_POST["hours"].":".$_POST["minutes"].":00";
    }
    
    $p_id = filter_input(INPUT_POST,'p_id');
    if (!Purpose::regexID($p_id)){
        $errorMsg = "Invalid Purpose Code : $p_id";
        $error = true;
    }
    
    if ($error){
        echo "<script type='text/javascript'> window.onload = function(){goModal('Error',\"$errorMsg\", false)}</script>";
    } elseif(count($device_mats) == 0 || $device->getDg()->getSelectMatsFirst() == "N"){
        //echo "<div class='pull-right'>no mats</div>";
        genericForm($operator, $device, $time, $p_id, $status_id, $staff);
    } elseif($device->getDg()->getPayFirst() == "Y"){
        //echo "<div class='pull-right'>payFirst</div>";
        payNow($operator, $device, $time, $p_id, $status_id, $device_mats, $staff);
    } elseif($device->getDg()->getSelectMatsFirst() == "Y"){
        //echo "<div class='pull-right'>selectMatsFirst</div>";
        $m_id = filter_input(INPUT_POST,'m_id');
        if (Materials::regexID($m_id)){
            withMatsForm($operator, $device, $time, $p_id, $status_id, $staff, $m_id);
        }
    } else {
        echo "<script type='text/javascript'> window.onload = function(){goModal('Error',\"C46: No Combinations Available\", false)}</script>";
    }
}

function payNow($operator, $device, $est_time, $p_id, $status_id, $device_mats, $staff){
    global $_POST;
    $unit_used = array();
    
    //Regex Volume
    for( $i=0; $i < count($device_mats); $i++){
        $m_id = $device_mats[$i]->getM_id();
        if (!Mats_Used::regexUnit_Used($_POST["m_$m_id"])){
            echo "<script type='text/javascript'> window.onload = function(){goModal('Error',\"Invalid Volume\", false)}</script>";
            return;
        }
        array_push($unit_used, filter_input(INPUT_POST, "m_$m_id"));
    }
    
    //Attempt to create new Ticket
    $trans_id = Transactions::insertTrans($operator, $device->getD_id(), $est_time, $p_id, $status_id, $staff);
    if (is_int($trans_id)) {
        //foreach($device_mats as $dm){
        for( $i=0; $i < count($device_mats); $i++){
            $mu_id = Mats_Used::insert_Mats_used($trans_id, $device_mats[$i]->getM_id(), $unit_used[$i], $status_id, $staff, "");
            if(!is_int($mu_id)){
                echo "<script type='text/javascript'> window.onload = function(){goModal('Error',\"Can not add materials used - $mu_id\", false)}</script>";
                return;
            }
        }
        $_SESSION['type'] = "payNow";
        header("Location:checkout.php?trans_id=".$trans_id);
    } else {
        //$insID is not an integer, it must be an error Message
        echo "<script type='text/javascript'> window.onload = function(){goModal('Error',\"Can Not Create a new Ticket - $trans_id\", false)}</script>";
    }
}

function genericForm($operator, $device, $est_time, $p_id, $status_id, $staff){
    //Attempt to create new Ticket
    $trans_id = Transactions::insertTrans($operator, $device->getD_id(), $est_time, $p_id, $status_id, $staff);
    if (is_int($trans_id)) {
        header("Location:lookup.php?trans_id=".$trans_id);
    } else {
        //$insID is not an integer, it must be an error Message
        echo "<script type='text/javascript'> window.onload = function(){goModal('Error',\"Can Not Create a new Ticket - $trans_id\", false)}</script>";
    }
}

function withMatsForm($operator, $device, $est_time, $p_id, $status_id, $staff, $m_id){
    //Attempt to create new Ticket
    $trans_id = Transactions::insertTrans($operator, $device->getD_id(), $est_time, $p_id, $status_id, $staff);
    if (is_int($trans_id)) {
        if(strcmp($m_id,"none") != 0){
            $mu_id = Mats_Used::insert_Mats($trans_id, $m_id, $status_id, $staff);
            if(is_int($mu_id)){
                header("Location:lookup.php?trans_id=".$trans_id);
            } else {
                echo "<script type='text/javascript'> window.onload = function(){goModal('Error',\"Can Not Indicate Materials Used\\nfor ticket - $mu_id\", false)}</script>";
            }
        }
    } else {
        //$insID is not an integer, it must be an error Message
        echo "<script type='text/javascript'> window.onload = function(){goModal('Error',\"$trans_id\", false)}</script>";
    }
}
?>
<title><?php echo $sv['site_name'];?> Create Ticket</title>
<div id="page-wrapper">
<?php //if logged in and have role id of 8 or greater
if ($staff) {
    if ($staff->getRoleID() > 7 && !$error){
?>
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">Create Ticket for the <?php echo $device->getDevice_desc(); ?></h1>
        </div>
        <!-- /.col-md-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-body">
                    <table class="table table-striped table-bordered table-hover">
                        <form id="cform" name="cform" method="post" action="" onsubmit="return validateForm()" autocomplete='off'>
                        <tr class="tablerow">
                            <td align="Center">Device</td>
                            <td><?php echo $device->getDevice_desc()?></td>
                        </tr>
                        <tr class="tablerow">
                            <td align="center">ID Number</td>
                            <td><input type="text" name="operator" placeholder="1000000000" value="<?php if(isset($operator)) echo $operator->getOperator();?>"
                                maxlength="10" size="10" autofocus tabindex="1"></td>
                        </tr>                      
                        <?php //if no materials are related to this device group
                        if (count($device_mats) == 0){?>
                            <tr class="tablerow">
                                <td align="Center">Material</td>
                                <td><select name="m_id" id="m_id" tabindex="2" disabled="true">
                                    <option selected value="none">None</option>
                                </select></td>
                            </tr>
                        <?php //list all mats associated with device if payFirst
                        } elseif ($device->getDg()->getpayFirst() == "Y"){?>
                            <tr class="tableheader warning">
                                <td align="center" colspan=2>Payment Required First
                                    <div id="quote" style="float:right"><?php echo"<i class='fa fa-$sv[currency] fa-fw'></i>"; ?> 0.00</div>
                                </td>
                            </tr>
                            <?php foreach($device_mats as $dm) { ?>
                                <tr class="tablerow">
                                    <td align="center"><?php echo $dm->getM_name();?></td>
                                    <td><input type="number" name="<?php echo "m_".$dm->getM_id();?>" 
                                        id="<?php echo "m_".$dm->getM_id();?>" min="0" max="1000" step=".01" tabindex="2" onchange="quote()"
                                        onclick="quote()" onkeyup="quote()"><?php printf("%s * <i class='fa fa-%s fa-fw'></i>%.2f" ,
                                        $dm->getUnit(), $sv['currency'], $dm->getPrice()); ?></td>
                                </tr>
                            <?php }
                        //Show materials drop down if SelectMatsFirst
                        } elseif($device->getDg()->getSelectMatsFirst() == "Y"){?>
                            <td align="Center">Material</td>
                            <td><select name="m_id" id="m_id" tabindex="2">
                                <option disabled hidden selected value="">Select</option>
                                <?php foreach($device_mats as $dm) {
                                    echo("<option value='".$dm->getM_id()."'>".$dm->getM_name()."</option>");
                                } ?>
                            </select></td>
                        <?php //Mats are selected at the end
                         } else { ?>
                            <tr class="tablerow">
                                <td align="Center">Material</td>
                                <td><b>Indicate the material used when closing this ticket.</b>
                                    <select name="m_id" id="m_id" tabindex="2" disabled="true" hidden>
                                        <option selected value="none">None</option>
                                    </select>
                                </td>
                            </tr>
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
        <!-- /.col-md-8 -->
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
    document.getElementById("quote").innerHTML = "<?php echo"<i class='fa fa-$sv[currency] fa-fw'></i>"; ?> 0.00";
}
	
function validateForm() {
    var x = document.forms["cform"]["operator"].value;
	var reg = /^\d{10}$/;
	//Mav ID Check
    if (x === null || x === "" || !reg.test(x)) {
        if (!reg.test(x)) {
                alert("Invalid ID #");
            }
            document.forms["cform"]["operator"].focus();
        return false;
    }
    //Material Check Uprint
    <?php if ($device->getDg()->getPayFirst() == "Y") {
        foreach($device_mats as $dm){ 
            echo ("var m_".$dm->getM_id()." = document.forms['cform']['m_".$dm->getM_id()."'].value;\n\t");
            echo ("if (m_".$dm->getM_id()." == null || m_".$dm->getM_id()." == '') {");
            echo ("\n\t\talert('Please enter amount used');");
            echo ("\n\t\tdocument.forms['cform']['m_".$dm->getM_id()."'].focus();");
            echo ("\n\t\treturn false;");
            echo ("\n\t}\n\t");
        } ?>
    <?php } else {?>
        //Material Check
        var y = document.forms["cform"]["m_id"].value;
        if (y === null || y === "") {
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
        if (h === 0){
            alert("Please Estimate Time.");
            document.forms["cform"]["hours"].focus();
            return false;
        }
    <?php } ?>
    //Purpose Check
    var p = document.forms["cform"]["p_id"].value;
    if (p === null || p === "") {
        alert("Please select a Purpose");
        document.forms["cform"]["p_id"].focus();
        return false;
    }
}

<?php //payFirst change to accept all materials
if (strcmp($device->getDg()->getDg_Name(),"uprint") == 0){ ?>
function quote () { <?php
    $s = "var total = (";
    foreach($device_mats as $dm){
        echo ("var rate".$dm->getM_id()." = ".$dm->getPrice().";\n\t");
        echo("var m_".$dm->getM_id()." = document.getElementById('m_".$dm->getM_id()."').value;\n\t");
        $s .="m_".$dm->getM_id()." * rate".$dm->getM_id()." +";
    }
    echo (substr($s, 0, -1).").toFixed(2);\n"); ?>
    document.getElementById("quote").innerHTML = "<?php echo"<i class='fa fa-$sv[currency] fa-fw'></i>"; ?> " + total;
    }
<?php } ?>
</script>

<?php } else {
//Insufficent Role Code or Load Error ?>
    <div class="row">
        <div class="col-md-12">
                <h1 class="page-header">Error</h1>
                Please Notify Staff. <?php echo $errorMsg;?>
        </div>
        <!-- /.col-md-12 -->
    </div>
</div>
<?php } } else { 
    ?>
    <div class="row">
        <div class="col-md-12">
                <h1 class="page-header">Create new Ticket for the <?php echo $device->getDevice_desc(); ?></h1>
                Please Log In
        </div>
        <!-- /.col-md-12 -->
    </div>
</div>
<!-- /#page-wrapper -->
<?php } 
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>