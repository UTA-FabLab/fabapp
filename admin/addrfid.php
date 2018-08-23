<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
if (!$staff || $staff->getRoleID() < $sv['minRoleTrainer']){
    //Not Authorized to see this Page
    header('Location: /index.php');
    $_SESSION['error_msg'] = "Insufficient role level to access, sorry.";
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["rfBtn"])){
    $operator = filter_input(INPUT_POST, "operator");
    $user = Users::withID($operator);
    $rfid = filter_input(INPUT_POST, "rfid");
    if(is_string($user)) {
        $errorMsg = $user;
    } elseif($user->getRfid_no()){
        if (filter_input(INPUT_POST, "override") == "yes"){
            $msg = $user->updateRFID($staff, $rfid);
            if (is_string($msg)){
                $errorMsg = $msg;
            } else {
                $_SESSION['success_msg'] = "ID:$operator's RFID was updated.";
                $operator = $rfid = "";
                echo "<script> window.location.href = '".$_SERVER['REQUEST_URI']."'</script>";
            }
        } else {
            echo "<script> window.onload = function(){goModal('Error',\"You must indicate that you want to overwrite <br>the exisiting RFID number.\", false)}</script>";
        }
        
    } else {
        $msg = $user->insertRFID($staff, $rfid);
        if (is_string($msg)){
            echo "<script> window.onload = function(){goModal('Error',\"$msg\", false)}</script>";
        } else {
            $_SESSION['success_msg'] = "RFID:$rfid was added to $operator.";
            $operator = $rfid = "";
            echo "<script> window.location.href = '".$_SERVER['REQUEST_URI']."'</script>";
        }
    }
} elseif($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["matchBtn"])){
    $match_operator = filter_input(INPUT_POST, "match_operator");
    $match_rfid = filter_input(INPUT_POST, "match_rfid");
    $match_user = Users::withID($match_operator);
    if(is_string($match_user)) {
        $errorMsg = $match_user;
    } elseif($match_user->getRfid_no() == $match_rfid){
        $_SESSION['success_msg'] = "These are a Match!";
        echo "<script> window.location.href = '".$_SERVER['REQUEST_URI']."'</script>";
    } else {
        $errorMsg = "No Match Found for ID:$match_operator with RFID:$match_rfid";
    }
}

if (isset($errorMsg)){
    echo "<script>window.onload = function(){goModal('Error',\"$errorMsg\", false)}</script>";
}
?>
<title><?php echo $sv['site_name'];?> Add RFID</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">Add RFID</h1>
        </div>
        <!-- /.col-md-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-7">
            <?php if ($staff->getRoleID() >= $sv['editRfid']) { ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-wifi fa-lg"></i> Assign an RFID to a Learner
                    </div>
                    <div class="panel-body">
                        <form onsubmit="return validateForm()" id="rfForm" method="post" autocomplete="off" action="">
                            <table class="table table-bordered table-striped table-hover">
                                <tr>
                                    <td>ID Number</td>
                                    <td><input type="text" name="operator" id="operator" placeholder="1000000000" 
                                            value="<?php if(isset($operator)) echo $operator;?>" maxlength="10" 
                                            size="10" autofocus tabindex="1" onkeyup="checkForRFID(this, this.value)">
                                    </td>
                                </tr>
                                <tr>
                                    <td>RFID (HEX)</td>
                                    <td><input type="text" name="rfid_h" id="rfid_h" placeholder="RFID HEX Value" 
                                        value="<?php if(isset($rfid_h)) echo $rfid_h;?>" tabindex="2" onkeyup="convertRFID(this, this.value, 'rfid')"
                                        size="11" maxlength="8">
                                    </td>
                                </tr>
                                <tr>
                                    <td>RFID (Decimal)</td>
                                    <td id="rfid_td"><input type="text" name="rfid" id="rfid" placeholder="RFID Number" 
                                        value="<?php if(isset($user)){echo $user->getRfid_no();} elseif(isset($rfid)) {echo $rfid;}?>" tabindex="3" 
                                        title="Enter the HEX value above, this field is the converted value.">
                                        <?php if(isset($user) && is_object($user) && $user->getRfid_no()){ ?>
                                            <label for="override">Overwrite Existing RFID? </label>
                                            <input type="checkbox" name="override" id="override" tabindex="4" value="yes">
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2"><button class="btn btn-basic pull-right" name="rfBtn" tabindex="5">Add RFID</button></td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </div>
                <!-- /.panel-body -->
            <?php } else { ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-wifi fa-lg"></i> Assign an RFID to a Learner
                    </div>
                    <div class="panel-body">
                        <?php
                            echo ("To Add/Edit an RFID, you must be logged in as ".ROLE::getTitle($sv['editRfid'])." or higher.");
                        ?></div>
                </div>
                <!-- /.panel-body -->
            <?php } ?>
        </div>
        <!-- /.col-md-7 -->
        <div class="col-md-5">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <a href="#" data-toggle="tooltip" data-placement="top" title="Below is the most recenly used RFID tag used on a power tail"><i class="fas fa-wifi fa-lg"> Recently Used RFID</i></a>
                </div>
                <div class="panel-body">
                    <?php echo $sv['lastRfid'];?>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <a href="#" data-toggle="tooltip" data-placement="top" title="Verify that a learner's RFID belongs to them, enter 2 of the 3 values."><i class="fas fa-street-view fa-lg"> Read Match</i></a>
                </div>
                <div class="panel-body">
                    <form id="readForm" method="post" autocomplete="off" action=""><table class="table table-bordered table-striped">
                        <tr>
                            <td>ID Number</td>
                            <td><input type="text" name="match_operator" id="match_operator" placeholder="1000000000" 
                                        value="<?php if(isset($match_operator)) echo $match_operator;?>" maxlength="10" 
                                        size="10" autofocus tabindex="10"></td>
                        </tr>
                        <tr>
                            <td>RFID (HEX)</td>
                            <td><input type="text" name="match_hex" id="match_hex" placeholder="RFID HEX Value" 
                                    tabindex="11" onkeyup="convertRFID(this, this.value, 'match_rfid')"
                                    size="11" maxlength="8"></td>
                        </tr>
                        <tr>
                            <td>RFID (Decimal)</td>
                            <td><input type="text" name="match_rfid" id="match_rfid" placeholder="RFID Number" 
                                value="<?php if(isset($match_rfid)) echo $match_rfid;?>" tabindex="12" size="11">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><button class="btn btn-basic pull-right" tabindex="13" name="matchBtn">Match Me</button></td>
                        </tr>
                    </table></form>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-md-5 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>

<script type="text/javascript">
    function validateForm() {
        if (stdRegEx("operator", /<?php echo $sv['regexUser'];?>/, "Invalid ID #") === false){
            return false;
        }
        if (stdRegEx("rfid", /^\d{4,12}$/, "Invalid RFID #") === false){
            return false;
        }
    }
    // Determine if the operator id already has an RFID tag
    function checkForRFID(ele, operator){
        if (ele.maxLength == operator.length || operator.length == 0){
            if (window.XMLHttpRequest) {
                // code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp = new XMLHttpRequest();
            } else {
                // code for IE6, IE5
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("rfid_td").innerHTML = this.responseText;
                }
            };
            xmlhttp.open("GET","sub/checkforrfid.php?operator=" + operator,true);
            xmlhttp.send();
        }
    }
    function convertRFID(ele, rfid_h, target){
        var tmp = [];
        var rfid_d = [];
        var i = 2;
        if (rfid_h.length == 8){
            do{ tmp.push(rfid_h.substring(0, i)) }
            while( (rfid_h = rfid_h.substring(i, rfid_h.length)) != "" );
            for (i = 0; i < tmp.length; i++) {
                rfid_d[i] = parseInt(tmp[i], 16);
            }
            var rfid_dec = rfid_d.join("");
            document.getElementById(target).value = rfid_dec;
        }
    }
</script>