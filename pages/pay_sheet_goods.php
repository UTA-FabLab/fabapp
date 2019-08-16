<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 *   Author: Sammy Hamwi
 */
 //This will import all of the CSS and HTML code necessary to build the basic page
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

session_start();

$array_values="";
$resultStr = "";
$errorMsg = "";
if (!$staff || $staff->getRoleID() < $sv['LvlOfStaff'] || !isset($_SESSION['sheet_ticket']) ){
    //Not Authorized to see this Page
    $_SESSION['error_msg'] = "You are unable to view this page.";
    header('Location: /index.php');
    exit();
} else {
    $sheet_ticket = unserialize($_SESSION['sheet_ticket']);
    $user = $sheet_ticket->getUser();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['payBtn']) && $errorMsg == ""){
    
    if (!is_numeric(filter_input(INPUT_POST, "selectPay"))){
        $_SESSION['error_msg'] = "Bad Payment Selection";
        header("Location: /pages/pay_sheet_goods.php");
    } else {
        $selectPay = filter_input(INPUT_POST, "selectPay");
        $amount = $_SESSION['co_price'];
        
        if ( preg_match("/^\d{1,3}$/", $selectPay) ){
            //The person paying may not be the person who is authorized to pick up a print
            $payee = $sheet_ticket->getUser()->getOperator();
            echo "<script> console.log('SP: $selectPay, payee: $payee'); </script>";
            $result = Acct_charge::insertSheetCharge($sheet_ticket, $selectPay, $payee, $staff, $amount);
        }

        if (is_int($result)){
            if($errorMsg == ""){
                for ($i = 0; $i < sizeof($_SESSION['cart_array']); $i++) {
					Materials::sold_sheet_quantity($_SESSION['cart_array'][$i], $_SESSION['co_quantity'][$i]);
					Transactions::insertSheetTrans($sheet_ticket->getTrans_id(), $_SESSION['cart_array'][$i], $_SESSION['co_quantity'][$i]);
                }
                //all good goto lookup
                unset($_SESSION['sheet_ticket']);
                unset($_SESSION['cart_array']);
                unset($_SESSION['co_quantity']);
                unset($_SESSION['co_price']);
                
                $_SESSION['success_msg'] = " Sheet Good Ticket has been Completed.";
                header("Location:lookup.php?trans_id=".$sheet_ticket->getTrans_id());
            }
        } else {
            //Must be error
            unset($_SESSION['sheet_ticket']);
            unset($_SESSION['cart_array']);
            unset($_SESSION['co_quantity']);
            unset($_SESSION['co_price']);
            $errorMsg = $result;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['undoBtn'])){
    Transactions::endSheetTicket($sheet_ticket->getTrans_id(), 2);
    
    unset($_SESSION['sheet_ticket']);
    unset($_SESSION['sell_inv_id']);
    unset($_SESSION['sell_quantity']);
    
    $_SESSION['success_msg'] = " Sheet Good Ticket has been Un-Done.";
    header("Location: /pages/sheet_goods.php");
}

if ($errorMsg != ""){
    $_SESSION['error_msg'] = $errorMsg;
    header("Location: /pages/sheet_goods.php");
}

?>
<html>
<head>
    <title>FabApp - Pay Sheet Goods</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
</head>
<body>
    <div id="page-wrapper">

        <!-- Page Title -->
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">FabApp Sheet Goods Payment</h1>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <!-- /.row -->
        
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <i class="fas fa-calculator"></i> Method of Payment
                    </div>
                    <form method="post" action="" onsubmit="return openWin()" autocomplete="off">
                        <div class="panel-body">
                            <div class="pull-right">
                            <button id="undoBtn" name="undoBtn" class="btn btn-xs btn-warning"><i class="fas fa-undo"></i> Undo/Go Back</button>
                            </div>
                            <table class="table table-bordered">
                                <tr>
                                    <td>Payment </td>
                                    <td><select name="selectPay" id="selectPay" onchange="updateBtn(this.value)">
                                        <option hidden selected>Select</option>
                                        <?php
                                            $accounts = Accounts::listAccts($user, $staff);
                                            $ac_owed = Acct_charge::checkOutstanding($sheet_ticket->getUser()->getOperator());
                                            foreach($accounts as $a){
                                                if (isset($ac_owed[$sheet_ticket->getTrans_id()]) && $a->getA_id() == 1){
                                                    //Don't Show it
                                                } else {
                                                    echo("<option value='".$a->getA_id()."' title=\"".$a->getDescription()."\">".$a->getName()."</option>");
                                                }
                                            }
                                        ?>
                                    </select></td>
                                </tr>
                                <tr>
                                    <td><b>Payee</b></td>
                                    <td><input disabled type="text" class="form-control" name="payee" id="payee" value="<?php echo($sheet_ticket->getUser()->getOperator()); ?>"
                                            maxlength="10"></td>
                                </tr>
                                <tr>
                                    <td><b>Amount</b></td>
                                    <td><?php echo ("$".$_SESSION['co_price']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <!-- /.panel-body -->
                        <div class="panel-footer" align="right">
                            <button id="payBtn" name="payBtn" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
                <!-- /.panel -->
                <?php //Look for associated charges Panel
                if($staff && $sheet_ticket->getAc() && (($sheet_ticket->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff']) ){ ?>
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
                                <?php foreach ($sheet_ticket->getAc() as $ac){
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
                                        if ( ($sheet_ticket->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff'] ){
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
            
            <div class="col-md-6">
                <h2 align="center">Confirmed Cart</h2>
                <table class="table table-condensed" id="invTable1">
                    <thead>
                        <tr>
                            <th>Sheet</th>
                            <th>Size (Inches)</th>
                            <th>Quantity</th>
                            <th>Price</th>
                        </tr>
                    </thead>   
                    <tbody>
                    <?php 
                    if(!empty($_SESSION['cart_array'])){
                        for ($ii = 0; $ii < sizeof($_SESSION['cart_array']); $ii++) { ?>
                            <tr>
                                <?php
                                $temp_v = $_SESSION['cart_array'][$ii];
                                if ($result = $mysqli->query("
                                        SELECT *
                                        FROM sheet_good_inventory SI JOIN materials M ON SI.m_id = M.m_id
                                        WHERE SI.inv_id=$temp_v AND SI.quantity != 0;
                                ")) {
                                    while ($row = $result->fetch_assoc()) { ?>
                                <td>
                                    <?php echo ($row['m_name']); ?>
                                </td>
                                <td>
                                    <?php echo ($row['width']." x ".$row['height']); ?>
                                </td>
                                <td>
                                    <t><?php echo ($_SESSION['co_quantity'][$ii]); ?></t>
                                </td>
                                <td>
                                    <?php echo ("$".number_format((float)((($row["width"]*$row["height"]) * $row["price"])* $_SESSION['co_quantity'][$ii]), 2, '.', '')); ?>
                                </td>
                                <?php } } ?>
                            </tr>
                        <?php } ?>
                            <tr>
                                <td>
                                </td>
                                <td>
                                </td>
                                <td>
                                </td>
                                <td>
                                    <b><?php echo ("Total: $".$_SESSION['co_price']); ?></b>
                                </td>
                            </tr>
                            
                            
                   <?php } else { ?>
                        <tr><td colspan="3"><div style='text-align: center'>Shopping Cart is Empty!</div></td></tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            
        </div>
        <!-- /.row -->
        
    </div> 
<div id="sgModal" class="modal">
</div>
</body>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript">
    
    var myWindow;
    var openBoolean = false;
    var btn = document.getElementById("payBtn");
    function openWin() {
        var selectPay = document.getElementById("selectPay").value;
        if (selectPay === "2"){
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
            if (x == 2){
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
</html>
