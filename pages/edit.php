<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$errorMsg = "";

if (!is_object($staff)){
    $errorMsg = "Please Login";
} elseif ($staff->getRoleID() < $sv['editTrans']) {
    $errorMsg = "Not Authorized to Edit Ticket";
} elseif (isset($_SESSION["edit_trans"]) && $_SESSION['type'] == "lookup"){
    if (Transactions::regexTrans($_SESSION["edit_trans"])){
        $trans_id = $_SESSION["edit_trans"];
        $ticket = new Transactions($trans_id);
        //Create List of Account IDs that are accessible by both the learner and the Staff memeber
        $accounts = Accounts::listAccts($ticket->getUser(), $staff);
        $a_ids = array();
        foreach ($accounts as $a){
            array_push($a_ids, $a->getA_id());
        }
    } else {
        $errorMsg = "Invalid Ticket Number";
    }
} else {
    $errorMsg = "Edit Parameter is Missing";
}

if ($errorMsg != ""){
    $_SESSION['error_msg'] = $errorMsg;
    header("Location:/index.php");
    exit();
} elseif($staff->getOperator() == $ticket->getUser()->getOperator()) {
    $_SESSION['error_msg'] = "You are unable to edit your own ticket.";
    header("Location:/pages/lookup.php?trans_id=".$ticket->getTrans_id());
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //Catch Possible Errors
    $err_catch = 0;
    if (isset($_POST['saveBtn'])){
        //Update Materials
        foreach($ticket->getMats_used() as $mu){
            $mu_id = $mu->getMu_id();
            $err_catch += $mu->edit(filter_input(INPUT_POST,"mu_mat_$mu_id"), filter_input(INPUT_POST,"uu_$mu_id"), 
                    filter_input(INPUT_POST,"mu_status_$mu_id"), filter_input(INPUT_POST,"mu_staff_$mu_id"), 
                    filter_input(INPUT_POST,"mu_notes_$mu_id"));
        }
        //Update Ticket
        $err_catch = $ticket->edit($err_catch, filter_input(INPUT_POST,"d_id"), filter_input(INPUT_POST,"t_start_picker"),
                filter_input(INPUT_POST,"t_end_picker"), filter_input(INPUT_POST,"h"),  filter_input(INPUT_POST,"m"), 
                filter_input(INPUT_POST,"s"), filter_input(INPUT_POST,"operator"),
                filter_input(INPUT_POST,"status_id"), filter_input(INPUT_POST,"staff_id"));
        
        //Update Storage
        if ($objbox = ObjBox::byTrans($ticket->getTrans_id())) {
            $err_catch = $objbox->edit($err_catch, filter_input(INPUT_POST,"ob_start_picker"),
                    filter_input(INPUT_POST,"ob_end_picker"), filter_input(INPUT_POST,"ob_operator"),
                    filter_input(INPUT_POST,"ob_staff"));
        }
        
        //Update Account
        if($ticket->getAc()){
            foreach ($ticket->getAc() as $ac){
                if(in_array($ac->getAccount()->getA_id(), $a_ids)){
                   $ac_id = $ac->getAc_id();
                   $err_catch = $ac->edit($err_catch, filter_input(INPUT_POST,"ac_operator_$ac_id"),
                           filter_input(INPUT_POST,"ac_amount_$ac_id"), filter_input(INPUT_POST,"ac_date_picker_$ac_id"),
                           filter_input(INPUT_POST,"ac_acct_$ac_id"), filter_input(INPUT_POST,"ac_staff_$ac_id"),
                           filter_input(INPUT_POST,"ac_notes_$ac_id"));
                }
            }
        }
        
        if (is_string($err_catch)){
            $_SESSION['error_msg'] = $err_catch;
            header("Location:/pages/edit.php");
        } elseif($err_catch == 0) {
            //No changes
            $_SESSION['success_msg'] = "No Changes Made.";
            header("Location:/pages/lookup.php?trans_id=".$ticket->getTrans_id());
        } elseif($err_catch == true) {
            $_SESSION['success_msg'] = "Ticket has been updated. Code $err_catch";
            header("Location:/pages/lookup.php?trans_id=".$ticket->getTrans_id());
        } else {
            $_SESSION['error_msg'] = $err_catch;
        }
    }
}
?>
<title><?php echo $sv['site_name'];?> Edit Detail</title>
<div id="page-wrapper"><form name="saveForm" method="post" action="" onsubmit="return validateForm()" autocomplete='off'>
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Edit Details <input class="btn btn-info" type="submit" name="saveBtn" value="Save"/></h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-5">
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <i class="fas fa-ticket-alt fa-lg"></i> Ticket # <b><?php echo $ticket->getTrans_id(); ?></b>
                </div>
                <div class="panel-body">
                    <table class ="table table-bordered table-striped">
                        <tr>
                            <td>Device</td>
                            <td>
                                <select name="d_id" class='form-control' tabindex="1" onchange="selectDevice(this)">
                                    <?php $result = $mysqli->query("SELECT * FROM `devices` WHERE 1 ORDER BY `device_desc`;");
                                    while($row = $result->fetch_assoc()){
                                        if ($row["d_id"] == $ticket->getDevice()->getD_id()){
                                            echo "<option selected value=$row[d_id]>$row[device_desc]</option>";
                                        } else {
                                            echo "<option value=$row[d_id]>$row[device_desc]</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Start Time</td>
                            <td>
                                <div class="form-group">
                                    <div class='input-group date' id='t_start_picker'>
                                        <input type='text' class="form-control" value="<?php echo $ticket->getT_start_picker(); ?>" name="t_start_picker"  id="t_start" tabindex="1"/>
                                        <span class="input-group-addon">
                                            <span class="fas fa-calendar-alt"></span>
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>End Time</td>
                            <td>
                                <div class="form-group">
                                    <div class='input-group date' id='t_end_picker'>
                                        <input type='text' class="form-control" value="<?php echo $ticket->getT_end_picker(); ?>" name="t_end_picker" id="t_end" tabindex="1"/>
                                        <span class="input-group-addon">
                                            <span class="fas fa-calendar-alt"></span>
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Duration
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                        <i class="fas fa-info"></i>
                                    </button>
                                    <ul class="dropdown-menu" role="menu">
                                        <li style="padding-left: 5px;">If the End Time is set and all 3 fields are left blank, then the Duration will automatically calculated.</li>
                                    </ul>
                                </div>
                            </td>
                            <td><?php
                                if($ticket->getDuration_raw() != ""){
                                    $dArray = explode(":", $ticket->getDuration_raw());
                                    echo ("<input type='number' value='$dArray[0]' max='800' min='0' style='width: 4em' tabindex='1' name='h'/>h ");
                                    echo ("<input type='number' value='$dArray[1]' max='59' min='0' style='width: 4em' tabindex='1' name='m'/>m ");
                                    echo ("<input type='number' value='$dArray[2]' max='59' min='0' style='width: 4em' tabindex='1' name='s'/>s");
                                } else { ?>
                                    <input type='number' max='800' min='0' style='width: 4em' tabindex="1" name='h'/>h
                                    <input type='number' max='59' min='0' style='width: 4em' tabindex="1" name='m'/>m
                                    <input type='number' max='59' min='0' style='width: 4em' tabindex="1" name='s'/>s
                            <?php } ?></td>
                        </tr>
                        <?php if ($ticket->getDevice()->getBase_price() > .005){ ?>
                            <tr>
                                <td>Cost</td>
                                <td>
                                    <?php echo " <i class='$sv[currency]'></i>".$ticket->getDevice()->getBase_price(). "/hour"; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td>Operator</td>
                            <td>
                                <input type="text" name="operator" id="operator" placeholder="1000000000" value="<?php echo $ticket->getUser()->getOperator();?>"
                                maxlength="10" class='form-control' tabindex="1">
                            </td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td><select name="status_id" id="status_id" class='form-control' tabindex="1"><?php
                                $available_status = array(10,11,12,13,14,15,20);
                                $t_status = $ticket->getStatus()->getStatus_id();
                                $sArray = Status::getList();
                                foreach($available_status as $as){
                                    if ($t_status == $as){
                                        echo ("<option value='$as' selected>$sArray[$as]</option>");
                                    } else {
                                        echo ("<option value='$as'>$sArray[$as]</option>");
                                    }
                                }
                            ?></select></td>
                        </tr>
                        <tr>
                            <td>Staff</td>
                            <td><?php if ( is_object($ticket->getStaff()) ) { ?>
                                <input type="text" name="staff_id" id="staff_id" placeholder="1000000000" value="<?php echo $ticket->getStaff()->getOperator();?>"
                                maxlength="10" class='form-control' tabindex="1">
                            <?php } else { ?>
                                <input type="text" name="staff_id" id="staff_id" placeholder="1000000000" maxlength="10" class='form-control' tabindex="1">
                            <?php } ?></td>
                        </tr>
                    </table>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
            <?php foreach ($ticket->getMats_used() as $mu) {
                $mu_id = $mu->getMu_id();//pull ID to get unique identifier?>
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <i class="far fa-life-ring fa-lg"></i> Material
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped" style="table-layout:fixed">
                            <tr>
                                <td class="col-md-4">Material</td>
                                <td class="col-md-8">
                                    <select name="mu_mat_<?php echo $mu_id;?>" id="mu_mat_<?php echo $mu_id;?>" class='form-control' tabindex="2">
                                        <?php
                                        //List all Materials that are available to that device
                                        $mats_array = Materials::getDeviceMats($ticket->getDevice()->getDg()->getDg_id());
                                        foreach($mats_array as $ma){
                                            if ($ma->getM_id() == $mu->getMaterial()->getM_id()){
                                                echo "<option selected value=".$ma->getM_id().">".$ma->getM_name()."</option>";
                                            } else {
                                                echo "<option value=".$ma->getM_id().">".$ma->getM_name()."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <?php if ($mu->getMaterial()->getMeasurable() == "Y"){ ?>
                                    <td>
                                        Quantity Used
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-addon"><?php printf("<i class='%s'></i> %.2f x " ,$sv['currency'], $mu->getMaterial()->getPrice());?></span>
                                            <input class='form-control' type="number" name="uu_<?php echo $mu_id;?>" id="uu_<?php echo $mu_id;?>" autocomplete="off" tabindex="2"
                                           value="<?php echo ($mu->getUnit_used());?>" min="0" max="9999" style="text-align:right;"/>
                                            <span class="input-group-addon"><?php echo $mu->getMaterial()->getUnit()."\n"; ?></span>
                                        </div>
                                    </td>
                                <?php } ?>
                            </tr>
                            <tr>
                                <td>Time</td>
                                <td><?php echo $mu->getMu_date()?></td>
                            </tr>
                            <tr>
                                <td>Material Status</td>
                                <td>
                                    <select name="mu_status_<?php echo $mu_id;?>" id="mu_status_<?php echo $mu_id;?>" class='form-control' tabindex="2"><?php
                                    $available_status = array(10,11,12,13,14,15,20);
                                    $t_status = $mu->getStatus()->getStatus_id();
                                    $sArray = Status::getList();
                                    foreach($available_status as $as){
                                        if ($t_status == $as){
                                            echo ("<option value='$as' selected>$sArray[$as]</option>");
                                        } else {
                                            echo ("<option value='$as'>$sArray[$as]</option>");
                                        }
                                    } ?></select>
                                </td>
                            </tr>
                            <?php if (strcmp($mu->getHeader(), "") != 0){ ?>
                                <tr>
                                    <td>File Name</td>
                                    <td><div style="word-wrap: break-word;"><?php echo $mu->getHeader(); ?></div></td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td>Staff</td>
                                <td>
                                    <input type="text" name="mu_staff_<?php echo $mu_id;?>" id="mu_staff_<?php echo $mu_id;?>" placeholder="1000000000" value="<?php if (is_object($mu->getStaff())) {echo $mu->getStaff()->getOperator();}?>"
                                        maxlength="10" class='form-control' tabindex="2">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="fas fa-edit"></i>Notes
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                            <i class="fas fa-info"></i>
                                        </button>
                                        <ul class="dropdown-menu" role="menu">
                                            <li style="padding-left: 5px;">Please state why this ticket needed to be edited.</li>
                                        </ul>
                                    </div>
                                </td>
                                <td><textarea name="mu_notes_<?php echo $mu_id;?>" id="mu_notes_<?php echo $mu_id;?>" class="form-control" tabindex="2"><?php echo $mu->getMu_notes();?></textarea></td>
                            </tr>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php } ?>
        </div>
        <!-- /.col-lg-5 -->
        <div class="col-lg-6">
            <?php if ($objbox = ObjBox::byTrans($ticket->getTrans_id())) { ?>
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <i class="fas fa-gift fa-lg"></i> Storage
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <td>Placed Into Storage</td>
                                <td>
                                    <div class="form-group">
                                        <div class='input-group date' id='ob_start_picker'>
                                            <input type='text' class="form-control" value="<?php echo $objbox->getO_start_picker(); ?>" name="ob_start_picker" id="ob_start" tabindex="3"/>
                                            <span class="input-group-addon">
                                                <span class="fas fa-calendar-alt"></span>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Removed On</td>
                                <td>
                                    <div class="form-group">
                                        <div class='input-group date' id='ob_end_picker'>
                                            <input type='text' class="form-control" value="<?php echo $objbox->getO_end_picker(); ?>" name="ob_end_picker" id="ob_end" tabindex="3"/>
                                            <span class="input-group-addon">
                                                <span class="fas fa-calendar-alt"></span>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Picked Up By</td>
                                <td><input type="text" name="ob_operator" placeholder="1000000000" value="<?php if( $objbox->getUser() !== NULL ) echo $objbox->getUser()->getOperator();?>"
                                maxlength="10" class='form-control' tabindex="3"></td>
                            </tr>
                            <tr>
                                <td>Address</td>
                                <td><?php echo $objbox->getAddress(); ?></td>
                            </tr>
                            <tr>
                                <td>Staff</td>
                                <td>
                                    <input type="text" name="ob_staff" placeholder="1000000000" value="<?php echo $objbox->getStaff()->getOperator();?>"
                                    maxlength="10" class='form-control' tabindex="3">
                                </td>
                            </tr>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php } 
            //Look for associated charges
            if($ticket->getAc()){?>
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <i class="fas fa-credit-card fa-lg"></i> Related Charges
                    </div>
                    <div class="panel-body">
                    <?php
                    //Show Each Account charge
                    foreach ($ticket->getAc() as $ac){ ?>
                        <table class="table table-bordered">
                            <?php
                                $ac_id = $ac->getAc_id();
                                //Show editing fields if they have access to the Account of the Charge
                                if(in_array($ac->getAccount()->getA_id(), $a_ids)){
                                    if ( is_object($ac->getUser()) ) { ?>
                                        <tr>
                                            <td colspan="2" align="center" class="active"><b>Account Charge # <?php echo $ac_id;?></b></td>
                                        </tr>
                                        <tr>
                                            <td>Paid By</td>
                                            <td>
                                                <input type="text" name="ac_operator_<?php echo $ac_id;?>" id="ac_operator_<?php echo $ac_id;?>" placeholder="1000000000" value="<?php echo $ac->getUser()->getOperator();?>"
                                                    maxlength="10" class="form-control"  tabindex="4">
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <tr>
                                            <td>Paid By</td>
                                            <td>
                                                <input type="text" name="ac_operator_<?php echo $ac_id;?>" placeholder="1000000000" maxlength="10" class="form-control" tabindex="4">
                                            </td>
                                        </tr>
                                    <?php } ?>
                                        <tr>
                                            <td>Amount</td>
                                            <td>
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <span class="input-group-addon">$</span>
                                                        <input type="number" name="ac_amount_<?php echo $ac_id;?>" value="<?php echo $ac->getAmount();?>" min="0" step="0.01" class="form-control" tabindex="4"/>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Date</td>
                                            <td>
                                                <div class="form-group">
                                                    <div class='input-group date' id='ac_date_picker'>
                                                        <input type='text' class="form-control" value="<?php echo $ac->getAc_date_picker();?>" name="ac_date_picker_<?php echo $ac_id;?>" id="ac_date_<?php echo $ac_id;?>" tabindex="4"/>
                                                        <span class="input-group-addon">
                                                            <span class="fas fa-calendar-alt"></span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Account</td>
                                            <td>
                                                <select name="ac_acct_<?php echo $ac_id;?>" class="form-control" tabindex="4"><?php
                                                    foreach($accounts as $a){
                                                        if ($a->getA_id() == $ac->getAccount()->getA_id()){
                                                            echo("<option value='".$a->getA_id()."' title=\"".$a->getDescription()."\" SELECTED>".$a->getName()."</option>");
                                                        } else {
                                                            echo("<option value='".$a->getA_id()."' title=\"".$a->getDescription()."\">".$a->getName()."</option>");
                                                        }
                                                    }
                                                ?></select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Staff</td>
                                            <td>
                                                <input type="text" name="ac_staff_<?php echo $ac_id;?>" id="ac_staff_<?php echo $ac_id;?>" placeholder="1000000000" value="<?php echo $ac->getStaff()->getOperator();?>"
                                                        maxlength="10" class="form-control" tabindex="4">
                                            </td>
                                        </tr>
                                        <?php if ($ac->getAc_notes()){ ?><tr>
                                            <td>Notes</td>
                                            <td>
                                                <textarea name="ac_notes_<?php echo $ac_id;?>" id="ac_notes_<?php echo $ac_id;?>" class="form-control" tabindex="2"><?php echo $ac->getAc_notes();?></textarea>
                                            </td>
                                        <?php } ?></tr>
                                    <?php //Locked View of Acct Charge, staff does not have access to this account
                                    } else {
                                        if ( is_object($ac->getUser()) ) { ?> <tr>
                                            <td colspan="2" align="center" class="active"><b>Account Charge # <?php echo $ac_id;?></b></td>
                                        </tr>
                                        <tr>
                                            <td>Paid By</td>
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
                                        </tr><?php } ?>
                                        <tr>
                                            <td>Amount</td>
                                            <td><?php echo "<i class='".$sv['currency']."'></i> ".number_format($ac->getAmount(), 2);?></td>
                                        </tr>
                                        <tr>
                                            <td>Account</td>
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
                                        </tr>
                                        <tr>
                                            <td>Staff</td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                        <i class="<?php echo $ac->getStaff()->getIcon();?> fa-lg" title="<?php echo $ac->getStaff()->getOperator();?>"></i>
                                                    </button>
                                                    <ul class="dropdown-menu" role="menu">
                                                        <li style="padding-left: 5px;"><?php echo $ac->getStaff()->getOperator();?></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php if ($ac->getAc_notes()){ ?>
                                        <tr>
                                            <td>Notes</td>
                                            <td>
                                                <?php echo $ac->getAc_notes();?>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    <?php } ?>
                        </table>
                        <?php } ?>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            <?php } ?>
        </div>
        <!-- /.col-lg-5 -->
    </div>
    <!-- /.row -->
</form></div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript">
$(function () {$('#t_start_picker').datetimepicker();});
$(function () {$('#t_end_picker').datetimepicker();});
$(function () {$('#ob_start_picker').datetimepicker();});
$(function () {$('#ob_end_picker').datetimepicker();});
$(function () {$('#ac_date_picker').datetimepicker();});
function validateForm() {
    var date_array = ['t_start','t_end'];
    var id_array = ["operator", "staff_id"];
    <?php if ($objbox = ObjBox::byTrans($ticket->getTrans_id())) {
        echo("date_array.push('ob_start');");
    } ?>
    <?php foreach($ticket->getAc() as $ac) {
        $ac_id = $ac->getAc_id();
        if (in_array($ac->getAccount()->getA_id(), $a_ids)){?>
            id_array.push("ac_staff_<?php echo $ac_id;?>");
            id_array.push("ac_operator_<?php echo $ac_id;?>");
            date_array.push("ac_date_<?php echo $ac_id;?>");
        <?php }
    } 
    foreach($ticket->getMats_used() as $mu) { 
        $mu_id = $mu->getMu_id();?>
        id_array.push("mu_staff_<?php echo $mu_id;?>");
        //Status Select Check
        var x = document.getElementById('mu_status_<?php echo $mu_id;?>').value;
        //check Notes Field
        var notes = document.getElementById('mu_notes_<?php echo $mu_id;?>').value;
        if (notes.length == 0 && x == 12){
            msg = 'Please state why this Ticket has been edited and marked failed.';
        } else if (notes.length == 0 && x == 15){
            msg = 'Please state why this Ticket has been edited and marked cancelled.';
        } else if (notes.length == 0){
            msg = 'Please state why this Ticket has been edited.';
        } else if (notes.length < 10){
            msg = 'Please Explain More about why this Ticket has been edited.';
        } else {
            msg = '';
        }
        
        if (msg != ''){
            //Msg has been set, send alert and stop form from sending
            alert(msg);
            document.getElementById('mu_notes_<?php echo $mu_id;?>').focus();
            return false;
        }
    <?php } ?>
    var status_id = document.getElementById("status_id").value;
    if (status_id == 10){
        var index = date_array.indexOf('t_end');
        if (index > -1){
            date_array.splice(index,1);
        }
    }
    for (var i=0; i < date_array.length; i++){
        if (stdRegEx(date_array[i], /(\d{2})\/(\d{2})\/(\d{4}) (\d{1,2}):(\d{2}) ([a-zA-Z]{2})/, "Invalid Date Time") === false){
            var x = document.getElementById(date_array[i]).value;
            console.log(date_array[i]+x);
            return false;
        }
    }
    for (var i=0; i < id_array.length; i++){
        var x = document.getElementById(id_array[i]).value;
        if (stdRegEx(id_array[i], /<?php echo $sv['regexUser'];?>/, "Invalid Operator ID # "+ x) === false){
            console.log(id_array[i]+x);
            return false;
        }
    }
}
function selectDevice(element){
    if (window.XMLHttpRequest) {
        // code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    } else {
        // code for IE6, IE5
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            <?php //Target all MU's
            foreach($ticket->getMats_used() as $mu) { ?>
            document.getElementById("mu_mat_<?php echo $mu->getMu_id();?>").innerHTML = this.responseText;
            <?php } ?>
        }
    };
    device = "d_id=" + element.value;
    xmlhttp.open("GET","sub/edit_mu.php?" + device,true);
    xmlhttp.send();
}
</script>