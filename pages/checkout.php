<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$total = 0.0;

if ($_SESSION['type'] == "end"){
    $ticket  = unserialize($_SESSION['ticket']);
    $mats_used = unserialize($_SESSION['mats_used']);
    $user = ($_SESSION['$pickupID'] ? Users::withID($_SESSION['$pickupID']) : Users::withID($ticket->getUser()->getOperator()) );
    
    //Run through the mats to calculate total
    foreach ($mats_used as $mu) {
        $total += $mu->getMaterial()->getPrice() * $mu->getUnit_used();
    }
}
?>
<title><?php echo $sv['site_name'];?> Checkout</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Checkout</h1>
            Verify and complete
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-ticket fa-fw"></i> Ticket <?php echo $ticket->getTrans_id();?>
                </div>
                <div class="panel-body">
                    <table class ="table table-bordered">
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
                                    <td><i class="fa fa-<?php if ( $ticket->getUser()->getIcon() ) echo $ticket->getUser()->getIcon(); else echo "user";?> fa-fw"></i>
                                        <?php if ($ticket->getUser()->getOperator() == $user->getOperator()) {
                                            echo $ticket->getUser()->getOperator();
                                        } else {
                                            echo "*******".substr($ticket->getUser()->getOperator(),7);
                                        } ?></td>
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
                                <td>Staff</td>
                                <td><i class="fa fa-<?php
                                if ( $ticket->getStaff()->getIcon() ) 
                                    echo $ticket->getStaff()->getIcon(); 
                                else 
                                    echo "user"; ?> fa-fw"></i></td>
                            </tr>
                            <?php }
                        } ?>
                    </table>
                </div>
            </div>
            <?php foreach ($mats_used as $mu) { ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-life-bouy fa-fw"></i> Material
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <tr class="tablerow info">
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
                            <tr>
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
        <!-- /.col-lg-6 -->
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-credit-card fa-fw"></i> Pay by...
                </div>
                <div class="panel-body">
                    <table class="table table-bordered">
                    <form>
                        <tr>
                            <td class="col-md-4">Select Method<br>of Payment</td>
                            <td class="col-md-8"><select name="status" id="status" onchange="" onkeyup="">
                                    <option value="" selected disabled hidden>Select</option>
                                    <option value="20@2" ><?php echo $sv['paySite_name'];?></option>
                                    <option value="20@4" ><?php echo $sv['interdepartmental'];?></option>
                                    <?php $accounts = $user->getAccounts();
                                    if ($accounts){
                                        foreach ($accounts as $accts){
                                            echo ("<option value='20@".$accts->getA_id()."'>".$accts->getName()."</option>\n");
                                        }
                                    }?>
                                    <option value="12">Failed</option>
                            </select></td>
                        </tr>
                        <tr class="success">
                            <td>Operator</td>
                            <td><b><i class="fa fa-<?php if ( $user->getIcon() ) echo $user->getIcon(); else echo "user";?> fa-fw"></i>
                                        <?php echo $user->getOperator();?></b></td>
                        </tr>
                        <tr class="success">
                            <td>Total</td>
                            <td><b><i class="fa fa-dollar fa-fw"></i><?php echo $total;?></b></td>
                        </tr>
                        <tr class="success">
                            <td><i class="fa fa-ticket fa-fw"></i> Ticket </td>
                            <td><b><?php echo $ticket->getTrans_id();?></b></td>
                        </tr>
                        <tr>
                            <td>Notes</td>
                            <td><textarea name="notes" id="notes" class="form-control" rows="4"></textarea></td>
                        </tr>
                        <tr class="tablefooter active">
                            <td colspan="2" align="right"> <input type="submit" name="payBtn" value="Pay"></td>
                        </tr>
                    </form>
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