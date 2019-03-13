<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$_SESSION['type'] = "lookup";
$errorMsg = "";

if (isset($_GET["trans_id"])){
    if (Transactions::regexTrans($_GET['trans_id'])){
        $trans_id = $_GET['trans_id'];
        $ticket = new Transactions($trans_id);
    } else {
        $errorMsg = "Invalid Ticket Number";
    }
} elseif (isset($_GET["operator"])){
    
    if (Users::regexUSER ($_GET['operator'])){
        $operator = $_GET['operator'];
        //Get Last Ticket of that ID
        if($result = $mysqli->query("
            SELECT trans_id
            FROM transactions
            WHERE transactions.operator = '$operator'
            ORDER BY t_start DESC
            Limit 1
        ")){
            if( $result->num_rows > 0){
                $row = $result->fetch_assoc();
                $ticket = new Transactions($row['trans_id']);
            } else {
                $errorMsg = "No Transactions Found for ID# $operator";
            }
        } else {
            $message = "Error - ID LookUp";
        }
    } else {
        $errorMsg = "Invalid Operator ID";
    }
} else {
    $errorMsg = "Search Parameter is Missing";
}

if ($errorMsg == ""){
    //Determine if there is a storage location for this ticket
    $objbox = ObjBox::byTrans($ticket->getTrans_id());
    if ($objbox === false){
        unset($objbox);
    }

    if ( isset($_SESSION['backup_ticket']) ){
        //pull the original state of the ticket into memory
        $t_backup = unserialize($_SESSION['backup_ticket']);
    }
}

if ($errorMsg != ""){
    $_SESSION['error_msg'] = $errorMsg;
    header("Location:/index.php");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['aarBtn'])){
        //Insert AAR function
        $msg = AuthRecipients::add($ticket, filter_input(INPUT_POST, "operator"));
        if (is_string($msg)){
            $_SESSION['error_msg'] = $msg;
            header("Location:/pages/lookup.php?trans_id=".$ticket->getTrans_id());
        } elseif($msg === true){
            
            $_SESSION['success_msg'] = "ID has been added.";
            header("Location:/pages/lookup.php?trans_id=".$ticket->getTrans_id());
        }
        
    } elseif (isset($_POST['editForm'])){
        echo "<script>console.log(\"lookup.php: goto edit.php\");</script>";
        $_SESSION["edit_trans"] = $trans_id;
        header("Location:/pages/edit.php");
        
    } elseif (isset($_POST['endBtn'])){
        header("Location:/pages/end.php?trans_id=".$ticket->getTrans_id());
        
    } elseif (isset($_POST['newBtn'])){
        if ($ticket->getDevice()->getUrl() == ""){
            header("Location:/pages/create.php?d_id=".$ticket->getDevice()->getD_id());
        } else {
            header("Location: https://".$ticket->getDevice()->getUrl());
        }
        
    } elseif (isset($_POST['newHome'])){
        $box = filter_input(INPUT_POST, "box");
        $letter = filter_input(INPUT_POST, "letter");
        $objbox->setAddress($box.$letter);
        $objbox->writeAttr();
        header("Location:/pages/lookup.php?trans_id=".$ticket->getTrans_id());
        
    } elseif (isset($_POST['payBtn'])){
        if (isset($objbox) && $objbox->getO_end() == ""){ //ob
            echo "<script>console.log(\"lookup.php: goto Pickup.php\");</script>";
            header("Location:/pages/pickup.php?operator=".$ticket->getUser()->getOperator());
        } else {
            echo "<script>console.log(\"lookup.php: goto pages/pay.php\");</script>";
            $_SESSION['ticket'] = serialize($ticket);
            header("Location:/pages/pay.php");
        }
        
    } elseif (isset($_POST['printForm'])){
        $str = $ticket->printTicket($ticket->getTrans_id());
        if (is_string($str)){
            $_SESSION['error_msg'] = $str;
        } else {
            $_SESSION['success_msg'] = "Now Printing";
        }
        header("Location:/pages/lookup.php?trans_id=".$ticket->getTrans_id());
        
    } elseif (isset($_POST['undoBtn'])){
        if ($t_backup->writeAttr() === true) {

            if (isset($_SESSION['ac_id'])){
                $ac = new Acct_charge($_SESSION['ac_id']);
                if ($ac->getTrans_id() == $t_backup->getTrans_id()){
                    $str = $ac->voidPayment($staff);
                    unset($_SESSION['ac_id']);
                }
                if (is_string($str)){
                    $_SESSION['error_msg'] = $str;
                }
            } else{
                echo "<script>console.log(\"lookup.php: Unable Void Payment\");</script>";
            }

            if (isset($_SESSION['backup_ob'])){
                //Look for objbox in memory
                $ob = unserialize($_SESSION['backup_ob']);
                $ob->writeAttr();
                unset($_SESSION['backup_ob']);
            } elseif (isset($objbox) && is_object($objbox))
                $objbox->unend($staff);
            unset($_SESSION['backup_ticket']);
        } else {
            echo "<script>console.log(\"lookup.php: Unable to WriteAttr to Ticket\");</script>";
        }
        header("Location:/pages/lookup.php?trans_id=".$t_backup->getTrans_id());
    }
}
?>
<title><?php echo $sv['site_name'];?> Ticket Detail</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php if ( isset($objbox) && $objbox->getO_end() == "") { ?>
                <h1 class="page-header"><i class="fas fa-map-marker fa-lg"></i> <?php echo $objbox->getAddress(); $_SESSION['type'] = "lookup"; ?></h1>
            <?php } else {?>
                
                <?php if ($ticket->getStatus()->getStatus_id() >= 12){ ?>
                    <h1 class="page-header">Ticket Details<form method="post" action="">
                        <input type="submit" name="newBtn" class="btn" value="New <?php echo $ticket->getDevice()->getDevice_desc(); ?> Ticket"/>
                    </form></h1>
                <?php } else { ?>
                    <h1 class="page-header">Ticket Details</h1>
                <?php } ?>
            <?php } ?>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-5">
            <div class="panel panel-default">
                <div class="panel-heading clearfix">
                    <i class="fas fa-ticket-alt fa-lg"></i> Ticket # <b><?php echo $ticket->getTrans_id(); ?></b>
                    <?php if (isset($staff)){ ?>
                        <div class="pull-right">
                            <div class="btn-group">
                                <button type="button" class="btn btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right" role="menu">
                                    <li>
                                        <a href="javascript: addBtn()" />Add Authorized Recipient</a>
                                    </li>
                                    <?php if ($staff->getRoleID() >= $sv['editTrans']){ ?>
                                        <li>
                                            <a href="javascript: editBtn()" class="bg-warning"/>Edit</a>
                                        </li>
                                    <?php }?>
                                    <?php if ($staff->getRoleID() >= $sv['LvlOfStaff']) { ?>
                                        <li>
                                            <a href="javascript: printBtn();"/>Print</a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    <?php }?>
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
                                        echo " * <i class='$sv[currency]'></i>".$ticket->getDevice()->getBase_price(). "/hour"; ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                <i class="fas fa-info"></i>
                                            </button>
                                            <ul class="dropdown-menu" role="menu">
                                                <li style="padding-left: 5px;">The minium charge is <?php echo $sv["minTime"];?> hour.</li>
                                            </ul>
                                        </div>
                                    <?php }
                                    if($ticket->getEst_time()){ ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                <i class="fas fa-stopwatch fa-lg" title="<?php echo $ticket->getEst_time();?>"></i>
                                            </button>
                                            <ul class="dropdown-menu" role="menu">
                                                <li style="padding-left: 5px;">Estimated Time <?php echo $ticket->getEst_time();?></li>
                                            </ul>
                                        </div>
                                    <?php }
                                ?></td>
                            </tr>
                        <?php } else  if ($ticket->getDevice()->getBase_price() > .005){ ?>
                            <tr>
                                <td>Cost
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                            <i class="fas fa-info"></i>
                                        </button>
                                        <ul class="dropdown-menu" role="menu">
                                            <li style="padding-left: 5px;">The minium charge is <?php echo $sv["minTime"];?> hour.</li>
                                        </ul>
                                    </div>
                                </td>
                                <td>
                                    <?php echo " <i class='$sv[currency]'></i>".$ticket->getDevice()->getBase_price(). "/hour"; ?>
                                </td>
                            </tr>
                        <?php }
                        if ( $staff && ($ticket->getUser()->getOperator() == $staff->getOperator() || $staff->getRoleID() >= $sv['LvlOfStaff']) ){ ?>
                            <tr>
                                <td>Operator</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                            <i class="<?php echo $ticket->getUser()->getIcon();?> fa-lg" title="<?php echo $ticket->getUser()->getOperator();?>"></i>
                                        </button>
                                        <ul class="dropdown-menu" role="menu">
                                            <li style="padding-left: 5px;"><?php echo $ticket->getUser()->getOperator();?></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php } else { ?>
                            <tr>
                                <td>Operator</td>
                                <td><i class="<?php echo $ticket->getUser()->getIcon();?> fa-lg"></i></td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td>Status</td>
                            <td><?php echo $ticket->getStatus()->getMsg(); ?></td>
                        </tr>
                        <?php if ($staff && $staff->getRoleID() >= $sv['LvlOfStaff']){ ?>
                            <tr>
                                <td>Staff</td>
                                <td><?php if ( is_object($ticket->getStaff()) ) { ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                            <i class="<?php echo $ticket->getStaff()->getIcon();?> fa-lg" title="<?php echo $ticket->getStaff()->getOperator();?>"></i>
                                        </button>
                                        <ul class="dropdown-menu" role="menu">
                                            <li style="padding-left: 5px;"><?php echo $ticket->getStaff()->getOperator();?></li>
                                        </ul>
                                    </div>
                                <?php } ?>
                                </td>
                            </tr>
                        <?php } else { ?>
                            <tr>
                                <td>Staff</td>
                                <td><?php if ( is_object($ticket->getStaff()) ) { ?>
									<i class="<?php echo $ticket->getStaff()->getIcon();?> fa-lg" title="<?php echo $ticket->getStaff()->getOperator();?>"></i>
								<?php } else {echo "-";} ?></td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
                <!-- /.panel-body -->
                <?php if ($ticket->getStatus()->getStatus_id() <= 11 && isset($staff) && ($staff->getRoleID() >= $sv['LvlOfStaff'] || $staff->getOperator() == $ticket->getUser()->getOperator())) { ?>
                    <div class="panel-footer">
                        <div align="right"><form name="moveForm" method="post" action="">
                            <button type="submit" class="btn btn-primary" name="endBtn">End</button>
                        </form></div>
                    </div>
                <?php } ?>
            </div>
            <!-- /.panel -->
            <?php foreach ($ticket->getMats_used() as $mu) {?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="far fa-life-ring fa-lg"></i> Material
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped" style="table-layout:fixed">
                            <tr class="tablerow">
                                <?php if ($mu->getMaterial()->getMeasurable() == "Y"){ ?>
                                    <td class="col-md-5">
                                        <?php echo $mu->getMaterial()->getM_name();
                                        if ($mu->getMaterial()->getColor_hex()){ echo "<div class=\"color-box\" style=\"background-color: #".$mu->getMaterial()->getColor_hex()."\"/>\n"; }?>
                                    </td>
                                    <td class="col-md-7">
                                        <?php printf("<i class='%s'></i>%.2f x " ,$sv['currency'], $mu->getMaterial()->getPrice());
                                        echo $mu->getUnit_Used()." ".$mu->getMaterial()->getUnit()."\n"; ?>
                                    </td>
                                <?php } else {?>
                                    <td class="col-md-5"><?php echo $mu->getMaterial()->getM_name();?></td>
                                    <td class="col-md-7"></td>
                                <?php } ?>
                            </tr>
                            <tr class="tablerow">
                                <td>Time</td>
                                <td><?php echo $mu->getMu_date()?></td>
                            </tr>
                            <tr class="tablerow">
                                <td>Material Status</td>
                                <td><?php echo $mu->getStatus()->getMsg()?></td>
                            </tr>
                            <?php if (strcmp($mu->getHeader(), "") != 0){ ?>
                                <tr>
                                    <td>File Name</td>
                                    <td><div style="word-wrap: break-word;"><?php echo $mu->getHeader(); ?></div></td>
                                </tr>
                            <?php } 
                            if (is_object($mu->getStaff())) {?>
                                <tr>
                                    <td>Staff</td>
                                    <td><?php if ( $staff && $staff->getRoleID() >= $sv['LvlOfStaff'] ){ ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                <i class="<?php echo $mu->getStaff()->getIcon();?> fa-lg" title="<?php echo $mu->getStaff()->getOperator();?>"></i>
                                            </button>
                                            <ul class="dropdown-menu" role="menu">
                                                <li style="padding-left: 5px;"><?php echo $mu->getStaff()->getOperator();?></li>
                                            </ul>
                                        </div>
                                    <?php } else { 
                                        echo "<i class='".$mu->getStaff()->getIcon()." fa-lg'></i>\n";
                                    }?></td>
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
            <?php } ?> 
        </div>
        <!-- /.col-lg-5 -->
        <div class="col-lg-5">
            <?php if (isset($t_backup) && $staff && $t_backup->getTrans_id() == $ticket->getTrans_id() && $staff->getRoleID() >= $sv['LvlOfStaff'] &&
			($t_backup != $ticket)){ ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-undo fa-lg" title="Undo"></i> Undo...
                    </div>
                    <div class="panel-body">
                        Did you accidentally close the wrong ticket?
                        <i class="fas fa-info-circle" title="The previous state of this ticket is stored in memory."></i>
                        <form name="undoForm" method="post" action="">
                            <input type="submit" name="undoBtn" class="btn" value="Unend this Ticket"/>
                        </form>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php } ?>
            <?php if (isset($staff) && $staff->getRoleID() >= $sv['LvlOfStaff'] && $ticket->getStatus()->getStatus_id() == 12){ ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-wrench fa-lg" title="Undo"></i> Service Ticket
                    </div>
                    <div class="panel-body">
                        Since it failed, should we Report an Issue with <?php echo $ticket->getDevice()->getDevice_desc(); ?>
                        <form name="undoForm" method="post" action="/pages/sr_issue.php?d_id=<?php echo $ticket->getDevice()->getD_id(); ?>">
                            <input type="submit" name="issueBtn" class="btn btn-warning" value="Report Issue"/>
                        </form>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php } ?>
            <?php if ($ars = AuthRecipients::listArs($ticket)) { ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="far fa-address-book fa-lg"></i> Authorized Recipients
                    </div>
                    <div class="panel-body">
                        <?php echo $ars; ?>
                    </div>
                </div>
            <?php } ?>
            <?php if ($objbox = ObjBox::byTrans($ticket->getTrans_id())) { ?>
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
                                <td>Removed On</td>
                                <td><?php echo $objbox->getO_end(); ?></td>
                            </tr>
                            <?php if (isset($staff) && $staff->getRoleID() >= $sv['LvlOfStaff']){?>
                                <tr>
                                    <td>Picked Up By</td>
                                    <td><?php 
                                    if ( is_object($objbox->getUser()) ) {
                                        echo "<i class='".$objbox->getUser()->getIcon()." fa-lg'></i> ".$objbox->getUser()->getOperator();
                                    } ?></td>
                                </tr>
                                <tr>
                                    <td>Address</td>
                                    <td>
                                        <?php if ($objbox->getO_end()){
                                            echo $objbox->getAddress();
                                        } else { ?>
                                            <input type="submit" value="<?php echo $objbox->getAddress(); ?>" class="btn btn-success"
                                                   data-toggle="modal" data-target="#addyModal">
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Staff</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                <i class="<?php echo $objbox->getStaff()->getIcon();?> fa-lg" title="<?php echo $objbox->getStaff()->getOperator();?>"></i>
                                            </button>
                                            <ul class="dropdown-menu" role="menu">
                                                <li style="padding-left: 5px;"><?php echo $objbox->getStaff()->getOperator();?></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php } else { ?>
                                <tr>
                                    <td>Picked Up By</td>
                                    <td><?php
                                    if ( $objbox->getUser() !== NULL ) {
                                        echo "<i class='".$objbox->getUser()->getIcon()." fa-lg'></i>";
                                    }?></td>
                                </tr>
                                <tr>
                                    <td>Staff</td>
                                    <td><?php echo "<i class=\"".$objbox->getStaff()->getIcon()." fa-lg\"></i> ";?></td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                    <?php if ($objbox->getO_end() == ""){ ?>
                        <div align="right" class="panel-footer"><form name="payForm" method="post" action="">
                            <button type="submit" name="payBtn" class="btn btn-danger">
                                Pick-up Print <?php echo "<i class='".$sv['currency']."'></i> ".number_format($ticket->quote("mats"), 2); ?>
                            </button>
                        </form></div>
                        <!-- /.panel-footer -->
                    <?php } ?>
                </div>
                <!-- /.panel -->
            <?php }
            //Look for associated charges
            if(isset($staff) && $ticket->getAc() && (($ticket->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff']) ){ ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-credit-card fa-lg"></i> Related Charges
                        <?php if (false && $staff && $staff->getRoleID() >= $sv['editTrans']){ ?>
                            <div class="pull-right"><form name="newCharge" method="post" action="" autocomplete='off'>
                                <input type="submit" name="newCharge" value="Add New Charge"/>
                            </form></div>
                        <?php } ?>
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <tr>
                                <td class="col-sm-2">Paid By</td>
                                <td class="col-sm-2">Amount</td>
                                <td class="col-sm-3">Account</td>
                                <td class="col-sm-3">Staff</td>
                            </tr>
                            <?php foreach ($ticket->getAc() as $ac){
                                if ($ac->getAccount()->getA_id() == 1 )
                                    echo"\n\t\t<tr class=\"danger\">";
                                else 
                                    echo"\n\t\t<tr>";
                                
                                    if ( is_object($ac->getUser()) ) {
                                        if (($ac->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff'] ){ ?>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                        <i class="<?php echo $ac->getUser()->getIcon();?> fa-lg" title="<?php echo $ac->getUser()->getOperator();?>"></i>
                                                    </button>
                                                    <ul class="dropdown-menu" role="menu">
                                                        <li style="padding-left: 5px;"><?php echo $ac->getUser()->getOperator();?></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        <?php } else {
                                            echo "<td><i class='".$ac->getUser()->getIcon()." fa-lg'></i></td>";
                                        }
                                    } else {
                                        echo "<td>-</td>";
                                    }
                                    if ( ($ticket->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff'] ){
                                        echo "<td><i class='".$sv['currency']."'></i> ".number_format($ac->getAmount(), 2)."</td>";
                                    } ?>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                <i class='far fa-calendar-alt' title="<?php echo $ac->getAc_date();?>"></i>
                                            </button>
                                            <ul class="dropdown-menu" role="menu">
                                                <li style="padding-left: 5px;"><?php echo $ac->getAc_date();?></li>
                                            </ul>
                                        </div>
                                        <?php echo $ac->getAccount()->getName();?>
                                    </td>
                                    <td>
                                        <?php if ($staff->getRoleID() >= $sv['LvlOfStaff']){ ?>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                    <i class="<?php echo $ac->getStaff()->getIcon();?> fa-lg" title="<?php echo $ac->getStaff()->getOperator();?>"></i>
                                                </button>
                                                <ul class="dropdown-menu" role="menu">
                                                    <li style="padding-left: 5px;"><?php echo $ac->getStaff()->getOperator();?></li>
                                                </ul>
                                            </div>
                                            <?php if ($ac->getAc_notes()){ ?>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                        <span class="fas fa-music" title="Notes"></span>
                                                    </button>
                                                    <ul class="dropdown-menu pull-right" role="menu">
                                                        <li style="padding-left: 5px;"><?php echo $ac->getAc_notes();?></li>
                                                    </ul>
                                                </div>
                                            <?php } ?>
                                        <?php } else {
                                            echo "<i class='".$ac->getStaff()->getIcon()." fa-lg'></i>";
                                        } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                    <?php //Determine if there is a balance owed on this ticket
                    $ac_owed = Acct_charge::checkOutstanding($ticket->getUser()->getOperator());
                    if (isset($ac_owed[$ticket->getTrans_id()])){ ?>
                        <div align="right" class="panel-footer"><form name="payForm" method="post" action="">
                            <button type="submit" name="payBtn" class="btn btn-danger">
                                Pay <?php echo "<i class='".$sv['currency']."'></i> ".number_format($ac_owed[$ticket->getTrans_id()], 2); ?>
                            </button>
                        </form></div>
                        <!-- /.panel-footer -->
                    <?php } ?>
                </div>
                <!-- /.panel -->
            <?php } ?>
        </div>
        <!-- /.col-lg-5 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<div id="addyModal" class="modal">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <form name="moveForm" method="post" action="" onsubmit="return verifyMove()">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Change Address</h4>
            </div>
            <div class="modal-body">
                <p title="Pick a good one">Select New Address</p>
                <select name="box" id="box">
                    <option hidden selected value="">Select Shelf</option>
                    <?php
                        foreach(ObjBox::getAddyNumber() as $addyN){
                            echo "<option value=\"$addyN\">$addyN</option>";
                        }
                    ?>
                </select>
                <select name="letter" id="letter">
                    <option hidden selected value="">Select Letter</option>
                    <?php
                        foreach(ObjBox::getAddyLetter() as $addyL){
                            echo "<option value=\"$addyL\">$addyL</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="modal-footer">
                  <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary" name="newHome">Save</button>
            </div> 
            </form>
        </div>
    </div>
</div>
<!-- Modal -->

<div id="AARModal" class="modal">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add Authorized Recipient</h4>
            </div>
            <?php if (isset($staff) && $staff->getOperator() == $ticket->getUser()->getOperator()) { ?>
                <form name="aarForm" method="post" action="" onsubmit="return validateAAR()">
                <div class="modal-body">
                    <p>Authorized the following recipient to pick up and pay for this ticket.
                    At the time of pickup, only the person & ID present can pay.</p>
                    <input type="text" name="operator" id="operator" placeholder="1000000000"
                                maxlength="10" size="10" tabindex="1">
                </div>
                <div class="modal-footer">
                      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-primary" name="aarBtn">Add</button>
                </div> 
                </form>
            <?php } else { ?>
                <div class="modal-body">
                    <p>Authorized the following recipient to pick up and pay for this ticket.
                    At the time of pickup, only the person & ID present can pay.</p>
                    <input type="text" name="operator" id="operator" placeholder="1000000000"
                                maxlength="10" size="10" tabindex="1" disabled>
                    <div class='alert-warning'>Only the ticket owner may add a recipient.</div>
                </div>
                <div class="modal-footer">
                      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-primary" name="noBtn" disabled>Add</button>
                </div> 
            <?php } ?>
        </div>
    </div>
</div>
<!-- Modal -->
<!--Hidden Forms-->
<form id="printForm" action="" method="post">
    <input type="text" name="printForm" hidden/>   
</form>
<form id="editForm" action="" method="post">
    <input type="text" name="editForm" hidden/>
</form>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript">

function validateAAR(){
    if (!stdRegEx("operator", /^\d{10}/, "Invalid Operator ID")){
        return false;
    }
    return true;
}

function verifyMove(){
    //Box Check
    var b = document.forms["moveForm"]["box"].value;
    if (b === null || b === "") {
        alert("Please select a Shelf Number");
        document.forms["moveForm"]["box"].focus();
        return false;
    }
    //Letter Check
    var l = document.forms["moveForm"]["letter"].value;
    if (l === null || l === "") {
        alert("Please select a Letter");
        document.forms["moveForm"]["letter"].focus();
        return false;
    }
}

function editBtn(){
    document.getElementById("editForm").submit();
}
function addBtn(){
    //toggle modal
    $("#AARModal").modal();
}
function printBtn(){
    if (confirm("Print?")){
        document.getElementById("printForm").submit();
    }
}
</script>