<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$errorMsg = "";
$mats_used = null;

if (isset($_GET["trans_id"])){
    if (Transactions::regexTrans($_GET['trans_id'])){
        $trans_id = $_GET['trans_id'];
        $ticket = new Transactions($trans_id);
        $mats_used = Mats_Used::byTrans($trans_id);
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
                //Fetch All Materials Related to transaction
                $mats_used = Mats_Used::byTrans($ticket->getTrans_id());
            } else {
                $errorMsg = "No Transactions Found for ID# $operator";
            }
	} else {
		$message = "Error - UTA LookUp";
	}
    } else {
        $errorMsg = "Invalid Operator ID";
    }
} else {
    $errorMsg = "Search Parameter is Missing";
}

if ($errorMsg != ""){
    echo "<script> alert('$errorMsg'); window.location.href='/index.php';</script>";
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['unfail'])) {
    if (array_key_exists('objbox', $_SESSION)){
        $ob_backup = unserialize($_SESSION['objbox']);
        $ob_backup->writeAttr();
        $_SESSION['objbox'] = null;
    }
    
    $t_backup = unserialize($_SESSION['ticket']);
    $t_backup->writeAttr();
    $_SESSION['ticket'] = null;

    $mats_used_backup = unserialize($_SESSION['mats_used']);
    foreach( $mats_used_backup as $mub){
        $msg = $mub->writeAttr();
    }
    $_SESSION['mats_used'] = null;
    $_SESSION['type'] = "undo";
    header("Location:/pages/lookup.php?trans_id=".$t_backup->getTrans_id());
}
?>
<title><?php echo $sv['site_name'];?> Ticket Detail</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php if (isset($operator)) { ?>
                <h1 class="page-header">Details of Most Recent Ticket</h1>
            <?php } elseif (strcmp($_SESSION['type'], 'failed') == 0) { ?>
                <h1 class="page-header">Details</h1><form name="undoForm" method="post" action=""><input type="submit" value="Unmark as Failed" name="unfail"></form>
            <?php }  elseif (strcmp($_SESSION['type'], 'end') == 0) { ?>
                <h1 class="page-header">Details: Address - <?php if ($objbox = ObjBox::byTrans($ticket->getTrans_id())) {echo $objbox->getAddress();} $_SESSION['type'] = "lookup"; ?></h1>
            <?php } else {?>
                <h1 class="page-header">Details</h1>
            <?php } ?>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-7">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-ticket fa-fw"></i> Ticket # <b><?php echo $ticket->getTrans_id(); ?></b>
                </div>
                <div class="panel-body">
                    <table class ="table table-bordered table-striped">
                        <tr>
                            <td>Device</td>
                            <td><?php echo $ticket->device->getDevice_desc(); ?></td>
                        </tr>
                        <tr>
                            <td>Time</td>
                            <td><?php echo $ticket->getT_start()." - ".$ticket->getT_end(); ?></td>
                        </tr>
                        <tr>
                            <td>Duration</td>
                            <td><?php echo $ticket->getDuration(); ?></td>
                        </tr>
                        <?php if ($staff) {
                            if ($staff->getRoleID() > 6){ ?>
                                <tr>
                                    <td>Operator</td>
                                    <td><i class="fa fa-<?php if ( $ticket->getUser()->getIcon() ) echo $ticket->getUser()->getIcon(); else echo "user";?> fa-fw"></i><?php echo $ticket->getUser()->getOperator();?></td>
                                </tr>
                            <?php }
                        }?>
                        <tr>
                            <td>Status</td>
                            <td><?php echo $ticket->getStatus()->getMsg(); ?></td>
                        </tr>
                        <?php if ($staff) {
                            if ($staff->getRoleID() > 6){ ?>
                            <tr>
                                <td>Staff ID</td>
                                <td><i class="fa fa-<?php
                                if ( $ticket->getStaff()->getIcon() ) 
                                    echo $ticket->getStaff()->getIcon(); 
                                else 
                                    echo "user"; ?> fa-fw"></i><?php 
                                echo " ".$ticket->getStaff()->getOperator();?></td>
                            </tr>
                            <?php }
                        } ?>
                    </table>
                </div>
                
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
            <?php foreach ($mats_used as $mu) {?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-life-bouy fa-fw"></i> Material
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped">
                            <tr class="tablerow">
                                <?php if ($mu->getMaterial()->getPrice() > 0){ ?>
                                    <td class="col-md-3"><?php echo $mu->getMaterial()->getM_name();?></td>
                                    <td class="col-md-9">
                                        <?php printf("$%.2f x ", $mu->getMaterial()->getPrice());
                                        echo $mu->getUnit_Used()." ".$mu->getMaterial()->getUnit(); ?>
                                    </td>
                                <?php } else {?>
                                    <td class="col-md-3"><?php echo $mu->getMaterial()->getM_name();?></td>
                                    <td class="col-md-9"></td>
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
                            if ($staff) {
                                if ($staff->getRoleID() > 6 && strcmp($mu->getMu_notes(), "") != 0){ ?>
                                    <tr>
                                        <td><i class="fa fa-pencil-square-o fa-fw"></i>Notes</td>
                                        <td><?php echo $mu->getMu_notes();?></td>
                                    </tr>
                                <?php }
                            }?>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php } ?>
            
        </div>
        <!-- /.col-lg-7 -->
        <div class="col-lg-5">
            <?php if ($objbox = ObjBox::byTrans($ticket->getTrans_id())) { ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-Cube fa-fw"></i> Storage
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
                            <?php if ($staff) {
                                if ($staff->getRoleID() > 6){?>
                                    <tr>
                                        <td>Picked Up By</td>
                                        <td><?php 
                                        if ( $objbox->getUser() !== NULL ) {
                                            if ( $ticket->getUser()->getIcon() ) 
                                                echo "<i class='fa fa-".$objbox->getUser()->getIcon()." fa-fw'></i>";
                                            else 
                                                echo "<i class='fa fa-user fa-fw'></i>";
                                            echo " ".$objbox->getUser()->getOperator();
                                        }?></td>
                                    </tr>
                                    <tr>
                                        <td>Address</td>
                                        <td><input type="submit" value="<?php echo $objbox->getAddress(); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td>Staff ID</td>
                                        <td><i class="fa fa-<?php 
                                        if ( $objbox->getStaff()->getIcon() ) {
                                            echo $objbox->getStaff()->getIcon(); 
                                        } else {
                                            echo "user"; 
                                        } ?> fa-fw"></i><?php 
                                        echo " ".$objbox->getStaff()->getOperator();?></td>
                                    </tr>
                                <?php } else { ?>
                                    <tr>
                                        <td>Picked Up By</td>
                                        <td><i class="fa fa-<?php 
                                        if ( $objbox->getUser()->getIcon() ) {
                                            echo $objbox->getUser()->getIcon(); 
                                        } else {
                                            echo "user"; 
                                        } ?> fa-fw"></i></td>
                                    </tr>
                                <?php } 
                            } ?>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php } ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-credit-card-alt fa-fw"></i> Paid By
                </div>
                <div class="panel-body">
                    <table>
                        <tr>
                            <td></td>
                            <td></td>
                        </tr>
                    </table>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-lg-5 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>