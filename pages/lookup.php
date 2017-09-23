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
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-ticket fa-lg"></i> Ticket # <b><?php echo $ticket->getTrans_id(); ?></b>
                    <?php if ($staff && $staff->getRoleID() >= $sv['editTrans']){ ?>
                    <div class="pull-right"><form name="undoForm" method="post" action="">
                            <input type="submit" name="editBtn" value="Edit" disabled title="Not Ready Yet :("/>
                    </form></div>
                    <?php } ?>
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
                        <?php if ( $staff && ($ticket->getUser()->getOperator() == $staff->getOperator() || $staff->getRoleID() >= $sv['LvlOfStaff']) ){ ?>
                            <tr>
                                <td>Operator</td>
                                <td><i class="fa fa-<?php echo $ticket->getUser()->getIcon();?> fa-lg" title="<?php echo $ticket->getUser()->getOperator();?>"></i></td>
                            </tr>
                        <?php } else { ?>
                            <tr>
                                <td>Operator</td>
                                <td><i class="fa fa-<?php echo $ticket->getUser()->getIcon();?> fa-lg"></i></td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td>Status</td>
                            <td><?php echo $ticket->getStatus()->getMsg(); ?></td>
                        </tr>
                        <?php if ($staff && $staff->getRoleID() >= $sv['LvlOfStaff']){ ?>
                            <tr>
                                <td>Staff</td>
                                <td><?php if ( $ticket->getStaff() ) 
                                    echo "<i class='fa fa-".$ticket->getStaff()->getIcon()." fa-lg' title='".$ticket->getStaff()->getOperator()."'></i>";?>
                                </td>
                            </tr>
                        <?php }?>
                    </table>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
            <?php foreach ($mats_used as $mu) {?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-life-bouy fa-lg"></i> Material
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
                                        <?php printf("<i class='fa fa-%s fa-fw'></i>%.2f x " ,$sv['currency'], $mu->getMaterial()->getPrice());
                                        echo $mu->getUnit_Used()." ".$mu->getMaterial()->getUnit()."\n"; ?>
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
                            <?php } ?>
                            <tr>
                                <td>Staff ID</td>
                                <td><?php if ($staff && $staff->getRoleID() >= $sv['LvlOfStaff'] && $mu->getStaff()){
                                    echo "<i class='fa fa-".$mu->getStaff()->getIcon()." fa-lg' title='".$mu->getStaff()->getOperator()."'></i>\n";
                                } elseif ($mu->getStaff()) { 
                                    echo "<i class='fa fa-".$mu->getStaff()->getIcon()." fa-lg'></i>\n";
                                }?></td>
                            </tr>
                            <?php if ($staff && $staff->getRoleID() >= $sv['LvlOfStaff'] && strcmp($mu->getMu_notes(), "") != 0){ ?>
                                <tr>
                                    <td><i class="fa fa-pencil-square-o fa-lg"></i>Notes</td>
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
        <!-- /.col-md-6 -->
        <div class="col-md-6">
            <?php if ($objbox = ObjBox::byTrans($ticket->getTrans_id())) { ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-Cube fa-lg"></i> Storage
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
                            <?php if ($staff && $staff->getRoleID() >= $sv['LvlOfStaff']){?>
                                <tr>
                                    <td>Picked Up By</td>
                                    <td><?php 
                                    if ( $objbox->getUser() !== NULL ) {
                                        echo "<i class='fa fa-".$objbox->getUser()->getIcon()." fa-lg'></i> ".$objbox->getUser()->getOperator();
                                    }?></td>
                                </tr>
                                <tr>
                                    <td>Address</td>
                                    <td><input type="submit" value="<?php echo $objbox->getAddress(); ?>"></td>
                                </tr>
                                <tr>
                                    <td>Staff ID</td>
                                    <td><?php echo "<i class='fa fa-".$objbox->getStaff()->getIcon()." fa-lg' title='".$objbox->getStaff()->getOperator()."'></i>";?></td>
                                </tr>
                            <?php } else { ?>
                                <tr>
                                    <td>Picked Up By</td>
                                    <td><?php
                                    if ( $objbox->getUser() !== NULL ) {
                                        echo "<i class='fa fa-".$objbox->getUser()->getIcon()." fa-lg'></i>";
                                    }?></td>
                                </tr>
                                <tr>
                                    <td>Staff ID</td>
                                    <td><?php echo "<i class=\"fa fa-".$objbox->getStaff()->getIcon()." fa-lg\"></i> ";?></td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php }
            //Look for associated charges
            if($staff && $ticket->getAc() && (($ticket->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff']) ){?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-credit-card-alt fa-lg"></i> Related Charges
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <tr>
                                <td class="col-sm-1">By</td>
                                <td class="col-sm-2">Amount</td>
                                <td class="col-sm-7">Date</td>
                                <td class="col-sm-2">Staff</td>
                            </tr>
                            <?php foreach ($ticket->getAc() as $ac){
                                echo"\n\t\t<tr>";
                                    if ( $ac->getUser() ) {
                                        if (($ac->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff'] ){
                                            echo "<td><i class='fa fa-".$ac->getUser()->getIcon()." fa-lg' title='".$ac->getUser()->getOperator()."'></i></td>";
                                        } else {
                                            echo "<td><i class='fa fa-".$ac->getUser()->getIcon()." fa-lg'></i></td>";
                                        }
                                    } else {
                                        echo "<td>-</td>";
                                    }
                                    if ( ($ticket->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff'] ){
                                        echo "<td><i class='fa fa-".$sv['currency']." fa-fw'></i>".sprintf("%.2f", $ac->getAmount())."</td>";
                                    }
                                    echo "<td>".$ac->getAc_date()."</td>";
                                    echo "<td><i class='fa fa-".$ac->getStaff()->getIcon()." fa-lg' title='".$ac->getStaff()->getOperator()."'></i>";
                                    if ($ac->getAc_notes()){ ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                                <span class="fa fa-music" title="Notes"></span>
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
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>