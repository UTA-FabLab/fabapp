<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.9
 */
 //This will import all of the CSS and HTML code nessary to build the basic page
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$errorMsg = "";

//Check if user is logged in & Check for ticket in a sesion variable
if ( $staff && isset($_SESSION['ticket']) ){
    //Unpackage the temporal state the ticket to be closed
    $ticket = unserialize($_SESSION['ticket']);

    if ( isset($_SESSION['pickupUser']) ){
        $user = unserialize($_SESSION['pickupUser']);
    } else {
        $user = $ticket->getUser();
    }
} else {
    echo "<script> console.log('Pay.php : No Ticket to be found'); </script>";
    //Not logged In
    header("location: /index.php");
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['payBtn']) && $errorMsg == ""){
    $selectPay = filter_input(INPUT_POST, "selectPay");
    if ( preg_match("/^\d{1,3}$/", $selectPay) ){
        //The person paying may not be the person who is authorized to pick up a print
        $payee = filter_input(INPUT_POST,'payee');
        echo "<script> console.log('SP: $selectPay, payee: $payee'); </script>";
        $result = Acct_charge::insertCharge($ticket, $selectPay, $payee, $staff);
    }
    
    if (is_int($result)){
        //Update ObjBox if needed
        $ob = ObjBox::byTrans($ticket->getTrans_id());
        if (is_object($ob)){
            $msg = $ob->pickedUpBy($user, $staff);
            if (is_string($msg)){
                $errorMsg = $msg;
            }
        }
        
        //all good goto lookup
        unset($_SESSION['ticket']);
        if ( isset($_SESSION['pickupUser']) ){
            unset($_SESSION['pickupUser']);
        }
        $_SESSION['ac_id'] = $result;
        header("Location:lookup.php?trans_id=".$ticket->getTrans_id());
    } else {
        //Must be error
        $errorMsg = $result;
    }
}

if ($errorMsg != ""){
    echo "<script> console.log('Error P70: $errorMsg'); </script>";
    echo "<script> window.onload = function(){goModal(\"Error\", \"$errorMsg\", false);}</script>";
}
?>
<title><?php echo $sv['site_name'];?> Pay</title>
<?php if (isset($ticket)){ ?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">Summary for <i class="fas fa-ticket-alt"></i> # <?php echo $ticket->getTrans_id(); ?></h1>
            This ticket has not been finalized until you confirm payment.
        </div>
        <!-- /.col-md-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-ticket-alt fa-lg"></i> # <?php echo $ticket->getTrans_id(); ?>
                </div>
                <div class="panel-body">
                    <table class ="table table-bordered table-striped">
                        <tr>
                            <td>Device</td>
                            <td><?php echo $ticket->getDevice()->getDevice_desc(); ?></td>
                        </tr>
                        <tr>
                            <td>Time</td>
                            <td><?php echo $ticket->getT_start()." - ".$ticket->getT_end(); ?></td>
                        </tr>
                        <?php if ($ticket->getDuration() != "") { ?>
                            <tr>
                                <td>Duration</td>
                                <td><?php
                                    echo $ticket->getDuration();
                                    //Display Device per hour cost
                                    if ($ticket->getDevice()->getBase_price() > .000001){
                                        echo " * <i class='$sv[currency]'></i> ".$ticket->getDevice()->getBase_price(). "/hour";
                                    }
                                ?></td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td>Operator</td>
                            <td><i class="<?php echo $ticket->getUser()->getIcon();?> fa-lg" title="<?php echo $ticket->getUser()->getOperator();?>"></i></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td><?php echo $ticket->getStatus()->getMsg(); ?></td>
                        </tr>
                        <tr>
                            <td>Staff</td>
                            <td><?php if ( $ticket->getStaff() ) 
                                echo "<i class='".$ticket->getStaff()->getIcon()." fa-lg' title='".$ticket->getStaff()->getOperator()."'></i>";?>
                            </td>
                        </tr>
                    </table>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
            <?php foreach ($ticket->getMats_used() as $mu) { ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="far fa-life-ring fa-lg"></i> Material
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped">
                            <tr class="tablerow">
                                <?php if ($mu->getMaterial()->getPrice() > 0){ ?>
                                    <td class="col-md-5">
                                        <?php echo $mu->getMaterial()->getM_name();
                                        if ($mu->getMaterial()->getColor_hex()){ echo "<div class=\"color-box\" style=\"background-color: #".$mu->getMaterial()->getColor_hex()."\"/>\n"; }?>
                                    </td>
                                    <td class="col-md-7">
                                        <?php printf("<i class='%s'></i>%.2f x " ,$sv['currency'], $mu->getMaterial()->getPrice());
                                        echo $mu->getUnit_Used()." ".$mu->getMaterial()->getUnit()."\n"; ?>
                                        <i class="far fa-calendar-alt fa-lg" title="<?php echo $mu->getMu_date()?>"/> by 
                                        <?php if (is_object($mu->getStaff())) {
                                            echo "<i class='".$mu->getStaff()->getIcon()."' title='".$mu->getStaff()->getOperator()."'></i>\n";
                                        } ?>
                                    </td>
                                <?php } else {?>
                                    <td class="col-md-5"><?php echo $mu->getMaterial()->getM_name();?></td>
                                    <td class="col-md-7"></td>
                                <?php } ?>
                            </tr>
                            <tr class="tablerow">
                                <td>Material Status</td>
                                <td><?php echo $mu->getStatus()->getMsg()?></td>
                            </tr>
                            <?php if (strcmp($mu->getHeader(), "") != 0){ ?>
                                <tr>
                                    <td>File Name</td>
                                    <td><?php echo $mu->getHeader(); ?></td>
                                </tr>
                            <?php }
                            if ($staff && $staff->getRoleID() >= $sv['LvlOfStaff'] && strcmp($mu->getMu_notes(), "") != 0){ ?>
                                <tr>
                                    <td><i class="fas edit fa-lg"></i>Notes</td>
                                    <td><?php echo $mu->getMu_notes();?></td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php }
            if ($objbox = ObjBox::byTrans($ticket->getTrans_id())) { ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-gift fa-lg"></i> Storage
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <td>Placed Into Storage</td>
                                <td><?php echo $objbox->getO_start(); ?></td>
                            </tr>
                            <tr>
                                <td>Address</td>
                                <td><?php echo $objbox->getAddress(); ?></td>
                            </tr>
                            <tr>
                                <td>Staff</td>
                                <td><?php echo "<i class='".$objbox->getStaff()->getIcon()." fa-lg' title='".$objbox->getStaff()->getOperator()."'></i>";?></td>
                            </tr>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php } ?>
        </div>
        <!-- /.col-md-6 -->
        <div class="col-md-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fas fa-calculator"></i> Method of Payment
                </div>
                <form method="post" action="" onsubmit="return openWin()">
                <div class="panel-body">
                    <table class="table table-bordered">
                        <tr>
                            <td>Payment </td>
                            <td><select name="selectPay" id="selectPay" onchange="updateBtn(this.value)">
                                <option hidden selected>Select</option>
                                <?php
                                    $accounts = Accounts::listAccts($user, $staff);
                                    $ac_owed = Acct_charge::checkOutstanding($ticket->getUser()->getOperator());
                                    foreach($accounts as $a){
                                        if (isset($ac_owed[$ticket->getTrans_id()]) && $a->getA_id() == 1){
                                            //Don't Show it
                                        } else {
                                            echo("<option value='".$a->getA_id()."' title=\"".$a->getDescription()."\">".$a->getName()."</option>");
                                        }
                                    }
                                ?>
                            </select></td>
                        </tr>
                        <tr>
                            <td>Payee</td>
                            <td><input type="text" class="form-control" placeholder="Enter ID #" value="<?php echo $user->getOperator();?>"
                                    maxlength="10" name="payee" id="payee"></td>
                        </tr>
                        <tr>
                            <td>Amount</td>
                            <td>
                                <b><?php printf("<i class='%s'></i> %.2f" ,$sv['currency'], $ticket->quote()); ?></b>
                            </td>
                        </tr>
                        <tr>
                            <td>Ticket #</td>
                            <td><b><?php echo $ticket->getTrans_id(); ?></b></td>
                        </tr>
                    </table>
                </div>
                <!-- /.panel-body -->
                <div class="panel-footer" align="right">
                    <button id="payBtn" name="payBtn" class="btn btn-primary" disabled>Submit</button>
                </div>
            </form></div>
            <!-- /.panel -->
            <?php //Look for associated charges Panel
            if($staff && $ticket->getAc() && (($ticket->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff']) ){ ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-credit-card fa-lg"></i> Related Charges
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <tr>
                                <td class="col-sm-1">By</td>
                                <td class="col-sm-2">Amount</td>
                                <td class="col-sm-7">Account</td>
                                <td class="col-sm-2">Staff</td>
                            </tr>
                            <?php foreach ($ticket->getAc() as $ac){
                                if ($ac->getAccount()->getA_id() == 1 )
                                    echo"\n\t\t<tr class=\"danger\">";
                                else 
                                    echo"\n\t\t<tr>";
                                    if ( is_object($ac->getUser()) ) {
                                        if (($ac->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff'] ){
                                            echo "<td><i class='".$ac->getUser()->getIcon()." fa-lg' title='".$ac->getUser()->getOperator()."'></i></td>";
                                        } else {
                                            echo "<td><i class='".$ac->getUser()->getIcon()." fa-lg'></i></td>";
                                        }
                                    } else {
                                        echo "<td>-</td>";
                                    }
                                    if ( ($ticket->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff'] ){
                                        echo "<td><i class='".$sv['currency']."'></i> ".number_format($ac->getAmount(), 2)."</td>";
                                    }
                                    echo "<td><i class='far fa-calendar-alt' title='".$ac->getAc_date()."'> ".$ac->getAccount()->getName()."</i></td>";
                                    echo "<td><i class='".$ac->getStaff()->getIcon()." fa-lg' title='".$ac->getStaff()->getOperator()."'></i>";
                                    if ($ac->getAc_notes()){ ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                                <span class="fas fa-music" title="Notes"></span>
                                            </button>
                                            <ul class="dropdown-menu pull-right" role="menu">
                                                <li style="padding-left: 5px;"><?php echo $ac->getAc_notes();?></li>
                                            </ul>
                                        </div>
                                    <?php }
                                    echo "</td>";
                                echo"</tr>\n";
                            } ?>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php } ?>
        </div>
        <!-- /.col-md-6 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<script>
var myWindow;
var openBoolean = false;
var btn = document.getElementById("payBtn");
function openWin() {
    var selectPay = document.getElementById("selectPay").value;
    if (selectPay === "2" && (<?php echo $ticket->quote();?> >= .01)){
        if (!openBoolean) {
            myWindow = window.open("<?php echo $sv['paySite'];?>", "myWindow", "top=100,width=750,height=500");
            btn.classList.toggle("btn-danger");
            btn.innerHTML = "Confirm Payment";
            openBoolean = !openBoolean;
            return false;
        } else {
            var message = "Did you take payment from CSGold? \nDid you logout of <?php echo $sv['paySite_name'];?>?";
            var answer = confirm(message);
            if (answer){
                myWindow.close();
                setTimeout(function(){console.log("waiting");},1500);
                openBoolean = !openBoolean;
                return true;
            } else {
                btn.classList.toggle("btn-danger");
                btn.innerHTML = "Launch <?php echo $sv['paySite_name'];?>";
            }
            openBoolean = !openBoolean;
            return false;
        }
    }
}
function updateBtn(x){
    if (x == 2){
        if (<?php echo $ticket->quote();?> >= .01){
            btn.innerHTML = "Launch <?php echo $sv['paySite_name'];?>";
        } else {
            btn.innerHTML = "Complete";
        }
    } else {
        btn.innerHTML = "Submit";
    }
    
    if (stdRegEx("payee", /^\d{10}$/, "Please enter ID #")){
        btn.disabled = false;
    } else {
        btn.disabled = true;
    }
    
}
</script>
<?php } else { ?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">No Ticket <i class="fas fa-ticket-alt"></i></h1>
        </div>
        <!-- /.col-md-12 -->
    </div>
</div>
<!-- /#page-wrapper -->
<?php  } ?>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>