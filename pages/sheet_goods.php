<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 *   Author: Sammy Hamwi
 */
 //This will import all of the CSS and HTML code necessary to build the basic page
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

if (!empty($_SESSION['sheet_ticket'])){
    unset($_SESSION['sheet_ticket']);
}

session_start();
//initialize cart if not set or is unset
if(!isset($_SESSION['cart_array'])){
    $_SESSION['cart_array'] = array();
    $_SESSION['co_quantity'] = array();
    $_SESSION['co_price'] = 0.0;
}

//Submit results
$resultStr = "";
$number_of_sheet_tables = 0;
if (!$staff || $staff->getRoleID() < $sv['LvlOfStaff']){
    //Not Authorized to see this Page
    $_SESSION['error_msg'] = "You are unable to view this page.";
    header('Location: /index.php');
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_material'])) {
    $inv_id_quantity = filter_input(INPUT_POST, 'inv_id_quantity');
    $m_id_quantity = filter_input(INPUT_POST, 'm_id_quantity');
    $new_amount = filter_input(INPUT_POST, 'new_quantity');
    $mat = new Materials($m_id_quantity);
    $original_amount = Materials::sheet_quantity($inv_id_quantity);
    
    if(preg_match('/^[1-9]\d*$/', $new_amount) || $new_amount==0){
        if(Materials::update_sheet_quantity($inv_id_quantity, $new_amount)) {
            $_SESSION['success_msg'] = $mat->m_name." updated from ".$original_amount." On Hand to ".
                                       $new_amount." On Hand";
        } else {
            $_SESSION['error_msg'] = "Unable to update ".$mat->m_name;
        }
    } else {
        $_SESSION['error_msg'] = "Bad quantity input ".$new_amount;
    }
    header("Location: /pages/sheet_goods.php");
} 

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sell_sheet'])) {
    
    if(!isset($_POST['p_id']) || !preg_match('/^[0-9]{10}$/', $_POST['sell_operator'])){
        $_SESSION['error_msg'] = "Bad Input";
        header("Location: /pages/sheet_goods.php");
    }
    
    $sell_op = Users::withID(filter_input(INPUT_POST, 'sell_operator'));
    $status_id = $status["sheet_sale"];
    $p_id = filter_input(INPUT_POST, 'p_id');
    
    if(!empty($sell_op) || !empty($status_id) || !empty($p_id)){
        if(Acct_charge::checkOutstanding(filter_input(INPUT_POST, 'sell_operator')) != false){
            $_SESSION['error_msg'] = "User has Outstanding Charge(s): ".filter_input(INPUT_POST, 'sell_operator');
            header("Location: /pages/sheet_goods.php");
        } else {
            $trans_id = Transactions::insert_new_transaction($sell_op, 68, "00:00:00", $p_id, $status_id, $staff);
            $sheet_ticket = new Transactions($trans_id);
            $_SESSION['sheet_ticket'] = serialize($sheet_ticket);
            
            header("Location: /pages/pay_sheet_goods.php");
        }
    } else {
        $_SESSION['error_msg'] = "Bad Input";
        header("Location: /pages/sheet_goods.php");
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['endCartBtn'])) {
    unset($_SESSION['cart_array']);
    unset($_SESSION['co_quantity']);
    unset($_SESSION['co_price']);
    header("Location: /pages/sheet_goods.php");
    $_SESSION['success_msg'] = "Cart has been successfully emptied";
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['refreshBtn'])) {
    $_SESSION['co_price'] = 0.0;
    for ($i = 0; $i < sizeof($_SESSION['cart_array']); $i++) {
        $temp_quan = "cart_quantity".$i;
        $_SESSION['co_quantity'][$i] = filter_input(INPUT_POST, $temp_quan);
    }
    for ($v = 0; $v < sizeof($_SESSION['cart_array']); $v++) {
        $temp_inv = $_SESSION['cart_array'][$v];
        if ($result = $mysqli->query("
                SELECT *
                FROM sheet_good_inventory SI JOIN materials M ON SI.m_id = M.m_id
                WHERE SI.inv_id=$temp_inv AND SI.quantity != 0;
        ")) {
            while ($row = $result->fetch_assoc()) {
                $_SESSION['co_price'] = number_format((float)(((($row["width"]*$row["height"]) * $row["price"])* $_SESSION['co_quantity'][$v])+$_SESSION['co_price']), 2, '.', '');
            }
        }
    }
    header("Location: /pages/sheet_goods.php");
    //$_SESSION['success_msg'] = "Cart Total: $".$_SESSION['co_price'];
}


?>
<html>
<head>
    <title>FabApp - Sheet Goods</title>
</head>
<body>
    <div id="page-wrapper">
        <!-- Page Title -->
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">FabApp Sheet Goods</h1>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <!-- /.row -->
        
        <div class="row">
            
            <!-- Sheet Goods Inventory Table -->
            <div class="col-md-7">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-warehouse"></i> Sheet Goods Inventory
                    </div>
                    <!-- /.panel-heading -->
                    <div class="panel-body">
                        <div class="table-responsive">
                            <ul class="nav nav-tabs">
                                <!-- Load all sheet good variants as a tab that have at least one sheet good inventory in that inventory -->
                                <?php if ($result = Materials::getTabResult($sv['sheet_goods_parent'])) {
                                    $count = 0;
                                    while ($row = $result->fetch_assoc()) { ?>
                                        <li class="<?php if ($count == 0) echo "active";?>">
                                            <a <?php echo("href=\"#".$row["m_id"]."\""); ?>  data-toggle="tab" aria-expanded="false"> <?php echo($row["m_name"]); ?> </a>
                                        </li>
                                    <?php 
                                    if ($count == 0){
                                        $first_mid = $row["m_id"];  
                                    }   
                                    $count++;                                                                  
                                    }
                                } ?>
                            </ul>
                            <div class="tab-content">
                                <?php
                                if ($Tabresult = Materials::getTabResult($sv['sheet_goods_parent'])) {
                                    while($tab = $Tabresult->fetch_assoc()){
                                        $number_of_sheet_tables++; ?>
                                
                                        <div class="tab-pane fade <?php if ($first_mid == $tab["m_id"]) echo "in active";?>" <?php echo("id=\"".$tab["m_id"]."\"") ?> >
                                            <table class="table table-striped table-bordered table-hover" <?php echo("id=\"sheetsTable_$number_of_sheet_tables\"") ?>>
                                                <thead>
                                                    <tr class="tablerow">
                                                        <th><i class="fas fa-square"></i> Sheet Material</th>
                                                        <th><i class="fas fa-ruler-combined"></i> Size (Inches)</th>
                                                        <th><i class="fas fa-money-bill-wave-alt"></i> Cost</th>
                                                        <th><i class="fas fa-boxes"></i> Quantity</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if ($result = $mysqli->query("
                                                            SELECT *
                                                            FROM sheet_good_inventory SI JOIN materials M ON SI.m_id = M.m_id
                                                            WHERE SI.m_parent=$tab[m_id] AND SI.quantity != 0;
                                                    ")) {
                                                        while ($row = $result->fetch_assoc()) { ?>
                                                            <tr class="tablerow">

                                                                <!-- Name -->
                                                                <td align="center"><?php echo($row['m_name']); ?><div class="color-box" style="background-color: #<?php echo($row['color_hex']);?>;"/></td>
                                                                
                                                                <!-- Size -->
                                                                <td align="center"><?php echo($row['width']." x ".$row['height']) ?></td>
                                                                
                                                                <!-- Cost -->
                                                                <td align="center"><?php echo("$".number_format((float)(($row['width']*$row['height']) * $row['price']), 2, '.', '')) ?></td>

                                                                <!-- Quanity -->
                                                                <td align="center" ondblclick='edit_materials(<?php echo($row['inv_id'].",".$row['m_id'].",".$row['width'].",".$row['height']);?>)'><?php echo($row['quantity']); ?><div class="pull-right"><!--<button class="btn btn-s btn-success" onclick="goToPay(<?php //echo($row['inv_ID'].",".$row['m_id'].",".$row['width'].",".$row['height']);?>)" data-toggle="tooltip" data-placement="top" title="Sell this Sheet Good">Sell</button>--><span class="pull-right"><a href="sub/add_cart.php?id=<?php echo ("".$row['inv_id']."&h=".$row['height']."&w=".$row['width']."&p=".$row['price']); ?>" class="btn btn-success btn-sm"><i class="fas fa-cart-plus"></i></a></span></div></td>

                                                            </tr>
                                                        <?php }
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php }
                                } ?>
                            </div>
                        </div>
                        <!-- /.table-responsive -->
                    </div>
                </div>
            </div>
            <!-- /.col-md-8 -->
            
            <!-- Shopping Cart -->
            <div class="col-md-5">
                <?php if (!empty($_SESSION['cart_array'])){ ?>
                <div class="panel panel-default">
                    <div class="panel-heading" style="background-color: #B5E6E6;">
                        <i class="fas fa-shopping-cart"></i> Cart Items: <b><?php $cart_quan = 0; for ($i = 0; $i < sizeof($_SESSION['cart_array']); $i++) {
                                                                                        $cart_quan = $cart_quan + $_SESSION['co_quantity'][$i];
                                                                                    } echo ($cart_quan); ?></b>
                        <div class="pull-right">
                            <button  class="btn btn-xs" data-toggle="collapse" data-target="#cartPanel1 , #cartPanel2"><i class="fas fa-bars"></i></button> 
                        </div>
                    </div>
                    <!-- /.panel-heading -->
                    <form name="testForm" method="post" action="" autocomplete="off">
                        <div class="panel-body collapse in" id="cartPanel1">
                            <table class="table table-condensed" id="invTable">
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
                                                <input type="number" name="cart_quantity<?php echo ($ii); ?>" id="cart_quantity<?php echo ($ii); ?>" max="<?php echo($row['quantity']); ?>" min="1" value="<?php echo ($_SESSION['co_quantity'][$ii]); ?>" step="1" placeholder="Enter Quantity" style="width:75%;"/>
                                            </td>
                                            <td>
                                                <?php echo ("$".number_format((float)((($row["width"]*$row["height"]) * $row["price"])* $_SESSION['co_quantity'][$ii]), 2, '.', '')); ?>
                                                <div class="pull-right"><a href="sub/delete_cart.php?id=<?php echo ("".$_SESSION['cart_array'][$ii]."&h=".$row['height']."&w=".$row['width']."&p=".$row['price']); ?>" class="btn btn-warning btn-xs" style="background-color: #FF7171;"><i class="fas fa-trash-alt"></i></a></div>

                                            </td>
                                            <?php } } ?>
                                        </tr>
                                    <?php } ?>
                                        <tr>
                                            <td></td><td></td><td><div class="pull-right"><b>Total:</b></div></td>
                                            <td>
                                                <b><?php echo ("$".$_SESSION['co_price']); ?></b>&nbsp;&nbsp;<div class="pull-right"><button type="submit" class="btn btn-success btn-xs" name="refreshBtn" style="background-color: #41BC11;"><i class="fas fa-sync-alt"></i></button></div>
                                            </td>
                                        </tr>
                                <?php } else { ?>
                                    <tr><td colspan="3"><div style='text-align: center'>Shopping Cart is Empty!</div></td></tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- /.panel-body -->
                        <div class="panel-footer collapse in clearfix" id="cartPanel2" style="background-color: #B5E6E6;">
                                <div class="pull-right"><button class="btn btn-success btn-sm" type="button" style="background-color: #41BC11;" onclick="return goToPay()" data-toggle="tooltip" data-placement="top">Checkout</button></div>
                                <div class="pull-left"><button type="submit" class="btn btn-warning btn-sm" name="endCartBtn" style="background-color: #FF7171;">Empty Cart</button></div>
                        </div>
                    </form>
                </div>
                <!-- /.panel -->
                <?php } else { ?>
                <div class="panel panel-default">
                    <div class="panel-heading" style="background-color: #F8F9B6;">
                        <i class="fas fa-shopping-cart"></i> Cart Items: <b><?php echo sizeof($_SESSION['cart_array']); ?></b>
                    </div>
                </div>
                <?php } ?>
            </div>
            <!-- /.col-md-4 -->
        </div>
    </div> 
<div id='material_modal' class='modal'> 
</div>
<div id='sell_modal' class='modal'> 
</div>
</body>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script>
    var str;
    for(var i=1; i<= <?php echo $number_of_sheet_tables;?>; i++){
        str = "#sheetsTable_"+i
        $(str).DataTable({
                    "iDisplayLength": 10,
                    "order": []
                    });
    }
    
    function Submitter(){

        if (confirm("You are about to submit this query. Click OK to continue or CANCEL to quit.")){
            return true;
        }
        return false;
    } 
    
    function goToPay() {
            if (window.XMLHttpRequest) {
                // code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp = new XMLHttpRequest();
            } else {
                // code for IE6, IE5
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("sell_modal").innerHTML = this.responseText;
                }
            };
            xmlhttp.open("GET", "sub/sell_sheet.php", true);
            xmlhttp.send();

        $('#sell_modal').modal('show');
    }
    
    function edit_materials(inv_id, m_id, width, height){
        if (Number.isInteger(m_id) && Number.isInteger(inv_id)){
            if (window.XMLHttpRequest) {
                // code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp = new XMLHttpRequest();
            } else {
                // code for IE6, IE5
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("material_modal").innerHTML = this.responseText;
                }
            };
            xmlhttp.open("GET", "sub/edit_sheet.php?inv_id=" + inv_id + "&m_id=" + m_id + "&width=" + width + "&height=" + height, true);
            xmlhttp.send();
        }

        $('#material_modal').modal('show');
    }
    
</script>
</html>
