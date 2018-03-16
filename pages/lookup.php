<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
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
    $_SESSION['loc'] = "/index.php";
    echo "<script> alert('$errorMsg'); window.location.href='/index.php';</script>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['undoBtn'])){
        if ($t_backup->writeAttr() === true) {

            if (isset($_SESSION['ac_id'])){
                $ac = new Acct_charge($_SESSION['ac_id']);
                $ac->voidPayment($staff);
            } else{
                echo "<script>console.log(\"lookup.php: Unable Void Payment\");</script>";
            }

            if (isset($_SESSION['backup_ob'])){
                //Look for objbox in memory
                $ob = unserialize($_SESSION['backup_ob']);
                $ob->writeAttr();
                unset($_SESSION['backup_ob']);
            } elseif (is_object($objbox))
                $objbox->unend($staff);
            unset($_SESSION['backup_ticket']);
        } else {
            echo "<script>console.log(\"lookup.php: Unable to WriteAttr to Ticket\");</script>";
        }
        header("Location:/pages/lookup.php?trans_id=".$t_backup->getTrans_id());
        
    } elseif (isset($_POST['payBtn'])){
        
        if (isset($objbox) && $objbox->getO_end() == ""){ //ob
            echo "<script>console.log(\"lookup.php: goto Pickup.php\");</script>";
            header("Location:/pages/pickup.php?operator=".$ticket->getUser()->getOperator());
        } else {
            echo "<script>console.log(\"lookup.php: goto pages/pay.php\");</script>";
            $_SESSION['ticket'] = serialize($ticket);
            header("Location:/pages/pay.php");
        }
    } elseif (isset($_POST['newHome'])){
        $box = filter_input(INPUT_POST, "box");
        $letter = filter_input(INPUT_POST, "letter");
        $objbox->setAddress($box.$letter);
        $objbox->writeAttr();
        header("Location:/pages/lookup.php?trans_id=".$ticket->getTrans_id());
    } elseif(isset($_POST['endBtn'])){
        header("Location:/pages/end.php?trans_id=".$ticket->getTrans_id());
    }
}
?>
<title><?php echo $sv['site_name'];?> Ticket Detail</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php if (isset($operator)) { ?>
                <h1 class="page-header">Details of Most Recent Ticket</h1>
            <?php }  elseif ( isset($objbox) && is_object($objbox) && $objbox->getO_end() == "") { ?>
                <h1 class="page-header"><i class="fas fa-map-marker fa-lg"></i> <?php echo $objbox->getAddress(); $_SESSION['type'] = "lookup"; ?></h1>
            <?php } else {?>
                <h1 class="page-header">Details</h1>
            <?php } ?>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-5">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-ticket-alt fa-lg"></i> Ticket # <b><?php echo $ticket->getTrans_id(); ?></b>
                    <?php if ($staff && $staff->getRoleID() >= $sv['editTrans']){ ?>
                    <div class="pull-right"><form name="undoForm" method="post" action="" autocomplete='off'>
                        <input type="submit" name="editBtn" value="Edit" disabled title="Not Ready Yet :("/>
                    </form></div>
                    <?php } ?>
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
                                        echo " * <i class='$sv[currency]'></i>".$ticket->getDevice()->getBase_price(). "/hour";
                                    }
                                ?></td>
                            </tr>
                        <?php }
                        if ( $staff && ($ticket->getUser()->getOperator() == $staff->getOperator() || $staff->getRoleID() >= $sv['LvlOfStaff']) ){ ?>
                            <tr>
                                <td>Operator</td>
                                <td><i class="<?php echo $ticket->getUser()->getIcon();?> fa-lg" title="<?php echo $ticket->getUser()->getOperator();?>"></i></td>
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
                                <td><?php if ( is_object($ticket->getStaff()) ) 
                                    echo "<i class='".$ticket->getStaff()->getIcon()." fa-lg' title='".$ticket->getStaff()->getOperator()."'></i>";?>
                                </td>
                            </tr>
                        <?php }?>
                    </table>
                </div>
                <!-- /.panel-body -->
				<?php if ($ticket->getDuration() == "") { ?>
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
                                    <td><?php echo $mu->getHeader(); ?></td>
                                </tr>
                            <?php } 
                            if (is_object($mu->getStaff())) {?>
                                <tr>
                                    <td>Staff</td>
                                    <td><?php if ( $staff && $staff->getRoleID() >= $sv['LvlOfStaff'] ){
                                        echo "<i class='".$mu->getStaff()->getIcon()." fa-lg' title='".$mu->getStaff()->getOperator()."'></i>\n";
                                    } else { 
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
                            <input type="submit" name="undoBtn" value="Unend this Ticket"/>
                        </form>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
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
                            <?php if ($staff && $staff->getRoleID() >= $sv['LvlOfStaff']){?>
                                <tr>
                                    <td>Picked Up By</td>
                                    <td><?php 
                                    if ( $objbox->getUser() !== NULL ) {
                                        echo "<i class='".$objbox->getUser()->getIcon()." fa-lg'></i> ".$objbox->getUser()->getOperator();
                                    }?></td>
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
                                    <td><?php echo "<i class='".$objbox->getStaff()->getIcon()." fa-lg' title='".$objbox->getStaff()->getOperator()."'></i>";?></td>
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
                                    <td>Staff ID</td>
                                    <td><?php echo "<i class=\"".$objbox->getStaff()->getIcon()." fa-lg\"></i> ";?></td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                    <?php if ($objbox->getO_end() == ""){ ?>
                        <div align="right" class="panel-footer"><form name="payForm" method="post" action="">
                            <button type="submit" name="payBtn" class="btn btn-danger">
                                Pick-up Print <?php echo "<i class='".$sv['currency']."'></i> ".number_format($ticket->quote(), 2); ?>
                            </button>
                        </form></div>
                        <!-- /.panel-footer -->
                    <?php } ?>
                </div>
                <!-- /.panel -->
            <?php }
            //Look for associated charges
            if($staff && $ticket->getAc() && (($ticket->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff']) ){?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-credit-card fa-lg"></i> Related Charges
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <tr>
                                <td class="col-sm-2">Paid By</td>
                                <td class="col-sm-2">Amount</td>
                                <td class="col-sm-5">Account</td>
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
<div id="addyModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <form name="moveForm" method="post" action="" onsubmit="return verifyMove()">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="modal-title">Change Address</h4>
            </div>
            <div class="modal-body">
                <p id="modal-body" title="Pick a good one">Select New Address</p>
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
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript">
function verifyMove(){
    return true;
}
</script>