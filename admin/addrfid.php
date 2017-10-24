<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
if (!$staff || $staff->getRoleID() < 7){
    //Not Authorized to see this Page
    header('Location: index.php');
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["rfBtn"])){
    $operator = filter_input(INPUT_POST, "operator");
    $user = Users::withID($operator);
    $rfid = filter_input(INPUT_POST, "rfid");
    if($user->getRfid_no()){
        if (filter_input(INPUT_POST, "override") == "yes"){
            $msg = $user->updateRFID($staff, $rfid);
            if (is_string($msg)){
                echo "<script> window.onload = function(){goModal('Error',\"$msg\", false)}</script>";
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
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-wifi fa-lg"></i> Input Values
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
                                <td>RFID</td>
                                <td id="rfid_td"><input type="text" name="rfid" id="rfid" placeholder="RFID Number" 
                                    value="<?php if(isset($rfid)) echo $rfid;?>" tabindex="2">
                                    <?php if(isset($operator) && $user->getRfid_no()){ ?>
                                        <label for="override">Overwrite Existing RFID? </label>
                                        <input type="checkbox" name="override" id="override" tabindex="3" value="yes">
                                    <?php } ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><button class="btn btn-basic pull-right" name="rfBtn" tabindex="4">Add RFID</button></td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>
        </div>
        <!-- /.col-md-8 -->
        <div class="col-lg-4">
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

<script type="text/javascript">
    function validateForm() {
        if (stdRegEx("operator", /^\d{10}$/, "Invalid ID #") === false){
            return false;
        }
        if (stdRegEx("rfid", /^\d{5,}$/, "Invalid RFID #") === false){
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
</script>