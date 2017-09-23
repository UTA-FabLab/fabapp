<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$errorMsg = $hasCost = "";
$_SESSION['pickupID'] = "";

//check trans_id
if (empty($_GET["trans_id"])){
    $errorMsg = "Ticket # is Missing.";
} elseif ($staff) {
    $trans_id = filter_input(INPUT_GET, "trans_id", FILTER_VALIDATE_INT);
    try {
        //Exception is thrown if ticket is not found
        $ticket = new Transactions($trans_id);
    } catch(Exception $e) {
        $errorMsg = $e->getMessage();
        $_SESSION['type'] = "error";
    }
    
    if ( $_SESSION['type'] == "home" && !isset($_POST['undoBtn'])){
        //create backup
        $_SESSION['ticket'] = serialize($ticket);
    
        //Assumes the ticket already has MU added to it or NONE
        $device_mats = Materials::getDeviceMats($ticket->getDevice()->getDg()->getDg_id());
        if ($ticket->getDevice()->getDg()->getSelectMatsFirst() == "Y" || count($device_mats) == 0) {
            //Try to end ticket, if there are no associated costs
            $status_id = 14; //Set to Complete
            $end_result = $ticket->end($status_id, $staff);
            if(is_string($end_result)){
                $errorMsg = $end_result;
            } elseif ($end_result) {
                $_SESSION['type'] = "end";
            } else {
                //end_result must be false and there for there are costs associated with operation the equipment itself
                $hasCost = $ticket->quote();
            }
        } else {
            //There might be materials that we need to add to this ticket
            //Ticket set end time and duration, determine if there are costs
            $hasCost = $ticket->quote();
        }
    }
    
} else {
    //Not logged In
    header("location: /index.php");
}

//When the user hits Submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($errorMsg == "")) {
    
    //Undo Button was Pressed
    if (isset($_POST['undoBtn'])) {
        if ($_SESSION['type'] == "end"){
            $backup = unserialize($_SESSION['ticket']);
            //echo( "Lets undo this ticket ".$backup->getTrans_id() );
            
            if ($backup->writeAttr() === true){
                $_SESSION['ticket'] = null;
                header("Location:/index.php");
            } else {
                $errorMsg = "Unable to Un-End Ticket";
            }
            $_SESSION['ticket'] = null;
        }
        
    //End Button was Pressed
    } elseif(isset($_POST['endBtn'])) {
        //Decision Check of Mats_used, only one destination allowed
        $storage = false; //status id = 14
        $cancel = false; //status id = 15
        $pickup = false; //status id = 20
        $status_id = 0;
        
        foreach ($ticket->getMats_used() as $mu){
            $mu_id = $mu->getMu_id();
            
            $mu->setMu_notes($_POST["mu_notes_$mu_id"]);
            
            //Determine if Material was Already Marked as Paid
            if($mu->getStatus()->getStatus_id() < 20) {
                $msg = $mu->setUnit_used($_POST["uu_$mu_id"]);
                if (is_string($msg))
                    {$errorMsg = $msg;}
            
                if ($errorMsg == "") {
                    $msg = $mu->setStatus($_POST["status_$mu_id"]);
                    if (is_string($msg))
                        {$errorMsg = $msg;}
                }
            
                //If mats used are set to both storage and pickup
                // Then Deny
                if($mu->getStatus()->getStatus_id() == 14)//Storage OR Complete
                    $storage = true;
                if($mu->getStatus()->getStatus_id() == 15)//Set to cancel
                    $cancel = true;
                if($mu->getStatus()->getStatus_id() == 20)//pick & pay
                    $pickup = true;
                //Write the highest status to the Ticket
                if ($status_id < $mu->getStatus()->getStatus_id())
                    $status_id = $mu->getStatus()->getStatus_id();
                
            } elseif ($mu->getStatus()->getStatus_id() == 20) {
                //Determine the destination for a pre-paid print
                if (!Status::regexID($_POST["status_$mu_id"]))
                    {$errorMsg = "Invalid Status :".$_POST["status_$mu_id"];}
                elseif ($errorMsg == "") 
                    {$mu_s = filter_input(INPUT_POST, "status_$mu_id");}
            
                //If mats used are set to both storage and pickup
                // Then Deny
                if($mu_s == 14)//Storage OR Complete
                    {$storage = true;}
                if($mu_s == 15)//set to cancel
                    {$cancel = true;}
                if($mu_s == 20)//pick
                    {$pickup = true;}
                //Write the highest status to the Ticket
                if ($status_id < $mu_s)
                    {$status_id = $mu_s;}
            } else {
                $errorMsg = "End Error - Not sure what to do.";
            }
        }
        
        if ($pickup && $storage){
            $errorMsg = "You must decide either to Pickup or Move to Storage";
        } elseif ($cancel && ($pickup && $storage)) {
            $errorMsg = "To cancel ticket, all must be set to cancel.";
        } elseif ($errorMsg == "") {
            if($status_id == 20){
                $user = Users::withID(filter_input(INPUT_POST, "pickID"));
                if(is_string($user)){
                    $errorMsg = $user;
                } else {
                    move($status_id, $ticket, $staff, $user);
                }
            } elseif($status_id == 14 && $ticket->getDevice()->getDG()->getStorable() == "Y") {
                $user = "";
                move($status_id, $ticket, $staff, $user);
            }
        }
    }
}

if ($errorMsg != ""){
    //Display Error Msg as JS alert
    //echo "<script> alert('$errorMsg'); window.location='/pages/end.php?trans_id=".$ticket->getTrans_id()."';</script>";
    echo "<script type='text/javascript'> window.onload = function(){goModal('ERROR',\"$errorMsg\", false)}</script>";
}

function move($status_id, $ticket, $staff, $user){
    global $errorMsg;
    
    if ($status_id == 12){ //Failed
        $_SESSION['type'] = "failed";
        //Mark Transaction w/ Failed Status
        $msg = $ticket->end($status_id, $staff);
        if (is_string($msg)){
            $errorMsg = $msg;
            return;
        }
        
        //Write Failed Ticket to the DB
        foreach($ticket->getMats_used() as $mu){
            $msg = $mu->updateUsed($staff);
            if (is_string($msg)){
                $errorMsg = $msg;
                return;
            }
        }
        header('Location:/pages/lookup.php?trans_id='.$ticket->getTrans_id());
        
    } elseif ($status_id == 14){
        // Status was set to complete, Insert into ObjBox
        // Send to look up page to view Address
        //Mark Transaction w/ Status ID
        $msg = $ticket->end($staff_id);
        if (is_string($msg)){
            $errorMsg = $msg;
            return;
        }
        
        //Write Materials Used to the DB
        foreach($mats_used as $mu){
            $msg = $mu->updateUsed($staff_id);
            if (is_string($msg)){
                $errorMsg = $msg;
                return;
            }
        }

        //Make a home for an Object to be placed into storage
        $msg = ObjBox::insert_Obj($ticket->getTrans_id(), $staff_id);
        if (is_string($msg)){
            $errorMsg = $msg;
            return; // exit because ObjBox has Error
        }
        $_SESSION['type'] = "end";
        header('Location:/pages/lookup.php?trans_id='.$ticket->getTrans_id());
		
    } elseif ($status_id == 15) { //Cancelled Print assess if there needs to be a charge
        $_SESSION['type'] = "end";
        //Pass Object to be updated upon Successful payment
        $ticket->setStatus($status_id);
        $_SESSION['ticket'] = serialize($ticket);
        $_SESSION['mats_used'] = serialize($mats_used);
        header('Location:/pages/checkout.php');
        
    } elseif ($status_id == 20 ) { // Pick Up now & charge to accts
        //send page to the payment
        $_SESSION['type'] = "end";
        echo "Pick Up now & charge to accts";
        
        //check if user is authorized to pickup print
        $msg = AuthRecipients::validatePickUp($ticket, $user);
        if (is_string($msg)) {
            $errorMsg = $msg;
            return;
        } elseif ($msg === true) {
            $_SESSION['pickupID'] = $pickupID;
        }
        
        //Pass Object to be updated upon Successful payment
        $ticket->setStatus_id($status_id);
        $_SESSION['ticket'] = serialize($ticket);
        $_SESSION['mats_used'] = serialize($mats_used);
        header('Location:/pages/checkout.php');
        
    } else {
        $errorMsg = "Invalid Status - E189";
    }
}
?>
<title><?php echo $sv['site_name'];?> End Ticket</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php if ($hasCost || $ticket) {
                echo "<h1 class='page-header'>Ticket Not Closed, Yet</h1> This ticket requires payment.";
            }  elseif (false) {
                echo "<h1 class='page-header'>Not Authorized To End Ticket</h1> You must be either staff or closing your own ticket.";
            } else {
                echo "<h1 class='page-header'>End Ticket</h1>";
            } ?>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-7">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-ticket fa-fw"></i> Ticket #<?php echo $ticket->getTrans_id();?>
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-bordered">
                        <?php  //Qualify Role is staff and 3D Print Job
                        if ($staff->getRoleID() >= $sv['LvlOfStaff'] && $ticket->getDevice()->getDg()->getStorable() == "Y") { ?>
                            <form name="endForm" method="post" action="" autocomplete="off" onsubmit="return validateForm()">
                                <tr>
                                    <td>Device</td>
                                    <td><?php echo $ticket->getDevice()->getDevice_desc();?></td>
                                </tr>
                                <tr>
                                    <td>Operator</td>
                                    <?php echo "<td><i class='fa fa-".$ticket->getUser()->getIcon()." fa-lg' title='".$ticket->getUser()->getOperator()."'></i></td>"; ?>
                                </tr>
                                <tr>
                                    <td>Ticket</td>
                                    <td><?php echo $ticket->getTrans_id();?></td>
                                </tr>
                                <tr>
                                    <td>Time</td>
                                    <td><?php echo $ticket->getT_start()." - ".$ticket->getT_end(); ?></td>
                                </tr>
                                <?php if ($ticket->getDuration() != "") { ?>
                                    <tr>
                                        <td>Duration</td>
                                        <?php echo("<td>".$ticket->getDuration()."</td>" ); ?>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <td>Status</td>
                                    <td><?php echo $ticket->getStatus()->getMsg(); ?></td>
                                </tr>
                                <?php foreach ($ticket->getMats_used() as $mu) {
                                    $mu_id = $mu->getMu_id();
                                    //Material has already been marked as paid
                                    if ($mu->getStatus()->getStatus_id() == 20) { ?>
                                        <tr>
                                            <td colspan="2">
                                                <table class="table table-bordered"><tbody>
                                                    <tr class="tablerow info">
                                                        <td><?php echo $mu->getMaterial()->getM_name();?></td>
                                                        <td>
                                                            <i class='fa fa-<?php echo $sv['currency'];?> fa-fw'></i> <?php echo $mu->getMaterial()->getPrice()." x "; ?>
                                                            <input type="number" name="uu_<?php echo $mu_id;?>" id="uu_<?php echo $mu_id;?>" autocomplete="off" value="<?php echo ($mu->getUnit_used());?>" min="0" max="9999" style="text-align:right;" disabled="true">
                                                            <?php echo (" ".$mu->getMaterial()->getUnit()."\n"); ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Print Status</td>
                                                        <td><select name="status_<?php echo $mu_id;?>" id="status_<?php echo $mu_id;?>" onchange="calc()">
                                                            <option value="" selected hidden="true">Select</option>
                                                            <option value="14">Move to Storage</option>
                                                            <option value="20">Pickup Now</option>
                                                        </select></td>
                                                    </tr>
                                                    <?php
                                                    if (strcmp($mu->getHeader(), "") != 0){ ?>
                                                        <tr>
                                                            <td>File Name</td>
                                                            <td><?php echo $mu->getHeader(); ?></td>
                                                        </tr>
                                                    <?php } ?>
                                                    <tr>
                                                        <td><i class="fa fa-pencil-square-o fa-fw"></i>Notes</td>
                                                        <td><textarea name="mu_notes_<?php echo $mu_id;?>" id="mu_notes_<?php echo $mu_id;?>" class="form-control"><?php echo $mu->getMu_notes();?></textarea></td>
                                                    </tr>
                                                </tbody></table>
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="2">
                                                <table class="table table-bordered"><tbody>
                                                    <tr class="tablerow info">
                                                        <td>
                                                            <?php echo $mu->getMaterial()->getM_name();
                                                            if ($mu->getMaterial()->getColor_hex()){ ?>
                                                                <div class="color-box" style="background-color: #<?php echo $mu->getMaterial()->getColor_hex();?>;"/>
                                                            <?php } ?>
                                                        </td>
                                                        <td>
                                                            <i class='fa fa-<?php echo $sv['currency'];?> fa-fw'></i> <?php echo sprintf("%.2f",$mu->getMaterial()->getPrice())." x \n";?>
                                                            <input type="number" name="uu_<?php echo $mu_id;?>" id="uu_<?php echo $mu_id;?>" autocomplete="off" value="" min="0" max="9999" step="1" onchange="calc()" onkeyup="calc()" onclick="calc()" style="text-align:right;">
                                                            <?php echo $mu->getMaterial()->getUnit()."\n"; ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Print Status</td>
                                                        <td><select name="status_<?php echo $mu_id;?>" id="status_<?php echo $mu_id;?>" onchange="calc()">
                                                            <option value="" selected hidden="true">Select</option>
                                                            <option value="14">Move to Storage</option>
                                                            <option value="20">Pickup Now</option>
                                                            <option value="12">Fail</option>
                                                            <option value="15">Cancel</option>
                                                        </select></td>
                                                    </tr>
                                                    <?php
                                                    if ($mu->getHeader() != ""){ ?>
                                                        <tr>
                                                            <td>File Name</td>
                                                            <td><?php echo $mu->getHeader(); ?></td>
                                                        </tr>
                                                    <?php } ?>
                                                    <tr>
                                                        <td><i class="fa fa-pencil-square-o fa-fw"></i>Notes</td>
                                                        <td><textarea name="mu_notes_<?php echo $mu_id;?>" id="mu_notes_<?php echo $mu_id;?>" class="form-control"><?php echo $mu->getMu_notes();?></textarea></td>
                                                    </tr>
                                                </tbody></table>
                                            </td>
                                        </tr>
                                    <?php }
                                } ?>
                                <tr id="pickTR" style="display: none;">
                                    <td>Confirm ID</td>
                                    <td><input type="text" name="pickID" class="form-control" placeholder="Enter ID #" maxlength="10" size="10"></td>
                                </tr>
                                <tr>
                                    <td colspan="2"><div id="total" style="float:right;">Total : <i class="fa fa-dollar fa-fw"></i> 0.00</div></td>
                                </tr>
                                <tr class="tablefooter">
                                    <td align="right" colspan="2">
                                        <input type="submit" name="endBtn" value="End 3D Print"/>
                                    </td>
                                </tr>
                            </form>
<?php //Basic Ticket Closing
                        } else { ?>
                            <tr>
                                <td>Device</td>
                                <td><?php echo $ticket->getDevice()->getDevice_desc();?></td>
                            </tr>
                            <tr>
                                <td>Ticket</td>
                                <td><?php echo $ticket->getTrans_id();?></td>
                            </tr>
                            <tr>
                                <td>Time</td>
                                <td><?php echo $ticket->getT_start()." - ".$ticket->getT_end(); ?></td>
                            </tr>
                            <?php if ($ticket->getDuration() != "") { ?>
                                <tr>
                                    <td>Duration</td>
                                    <?php echo("<td>".$ticket->getDuration()."</td>" ); ?>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td>Status</td>
                                <td><?php echo $ticket->getStatus()->getMsg(); ?></td>
                            </tr>
                            <?php foreach ($ticket->getMats_used() as $mu) {?>
                                <tr class="tablerow">
                                    <?php if ($mu->getMaterial()->getPrice() > 0){ ?>
                                        <td class="col-md-3">
                                            <?php echo $mu->getMaterial()->getM_name();
                                            if ($mu->getMaterial()->getColor_hex()){ ?>
                                                <div class="color-box" style="background-color: #<?php echo $mu->getMaterial()->getColor_hex();?>;"/>
                                            <?php } ?>
                                        </td>
                                        <td class="col-md-9">
                                            <?php printf("<i class='fa fa-%s fa-fw'></i>%.2f x " ,$sv['currency'], $mu->getMaterial()->getPrice());
                                            echo $mu->getUnit_Used()." ".$mu->getMaterial()->getUnit(); ?>
                                        </td>
                                    <?php } else {?>
                                        <td class="col-md-3">Material</td>
                                        <td class="col-md-9"><?php echo $mu->getMaterial()->getM_name();?></td>
                                    <?php } ?>
                                </tr>
                                <tr class="tablerow">
                                    <td>Material Status</td>
                                    <td><?php echo $mu->getStatus()->getMsg()?></td>
                                </tr>
                                <?php if ($mu->getHeader() != ""){ ?>
                                    <tr>
                                        <td>File Name</td>
                                        <td><?php echo $mu->getHeader(); ?></td>
                                    </tr>
                                <?php }
                                if ($staff && $staff->getRoleID() >= $sv['LvlOfStaff'] && $mu->getMu_notes() != ""){ ?>
                                    <tr>
                                        <td><i class="fa fa-pencil-square-o fa-fw"></i>Notes</td>
                                        <td><?php echo $mu->getMu_notes();?></td>
                                    </tr>
                                <?php }
                            }
                            if ($_SESSION['type'] == "end"){ ?>
                                <tr class="tablefooter">
                                    <td align="right" colspan="2">
                                        <form name="undoForm" method="post" action="">
                                            <input type="submit" name="undoBtn" value="Unend this Ticket"/>
                                        </form>
                                    </td>
                                </tr>
                            <?php }
                        }?>
                    </table>
                </div>
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-lg-7 -->
        <div class="col-md-5">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-database fa-fw"></i> Stats
                </div>
                <div class="panel-body">
                    <table class="table table-bordered">
                        <tr>
                            <td>Balance</td>
                            <td><?php printf("<i class='fa fa-%s fa-fw'></i>%.2f" ,$sv['currency'], $hasCost); ?></td>
                        </tr>
                    </table>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
            <?php
            //Look for associated charges
            if($staff && $ticket->getAc() && (($ticket->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff']) ){ ?>
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
                                        echo "<td><i class='fa fa-".$sv['currency']." fa-fw'></i>".number_format($ac->getAmount(), 2)."</td>";
                                    }
                                    echo "<td>".$ac->getAc_date()."</td>";
                                    echo "<td><i class='fa fa-".$ac->getStaff()->getIcon()." fa-lg' title='".$ac->getStaff()->getOperator()."'></i>";
                                    if ($ac->getAc_notes()){ ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                                <span class="fa fa-info-circle" title="Notes"></span>
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
        <!-- /.col-md-5 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript">
<?php if ($ticket->getMats_used() !== NULL){ ?>
function validateForm(){
    var storage = false;//14
    var cancel = false;//15
    var pickup = false;//20
    <?php foreach($ticket->getMats_used() as $mu) {
        $mu_id = $mu->getMu_id();
        echo ("//Make sure material has been weighed\n");
        echo ("\tvar x = document.getElementById('uu_$mu_id').value;\n");
        echo ("\tif (x == null || x == ''){\n");
        echo ("\t\talert('Please Weigh Object');\n");
        echo ("\t\tdocument.getElementById('uu_$mu_id').focus();\n");
        echo ("\t\treturn false;\n");
        echo ("\t}\n");
        
        //Check Status dropdown
        echo ("\n\t//Status Select Check\n");
        echo ("\tvar x = document.getElementById('status_$mu_id').value;\n");
        echo ("\tif (x == null || x == ''){\n");
        echo ("\t\talert('Please Select Status');\n");
        echo ("\t\tdocument.getElementById('status_$mu_id').focus();");
        echo ("\n\t\treturn false;\n\t}");
        echo ("\n\tif (x == 14){");
        echo ("\n\t\tstorage = true;\n\t} else if(x == 20) {");
        echo ("\n\t\tpickup = true;\n\t} else if(x == 15) {");
        echo ("\n\t\tcancel = true;\n\t} ");
        
        //check Notes Field
        echo ("\n\tif (x == 12 || x == 15){\n");
        echo ("\t\t//Notes Field Required for Failed or Canceled Prints \n");
        echo ("\t\tvar notes = document.getElementById('mu_notes_$mu_id').value;\n");
        echo ("\t\tvar msg = '';\n");
        echo ("\t\tif (notes.length < 10){\n");
        echo ("\t\t\tmsg='Please Explain More......'\n");
        echo ("\t\t\tif (notes.length == 0){\n");
        echo ("\t\t\t\tmsg='Please state why this might have failed or need to be canceled'\n");
        echo ("\t\t\t}\n");
        echo ("\t\t\talert(msg);\n");
        echo ("\t\t\tdocument.getElementById('mu_notes_$mu_id').focus();\n");
        echo ("\t\t\treturn false;\n\t\t}\n\t}\n\n\t");
    }?>
    
    if (pickup && storage){
        alert("You must decide either to Pickup or Move to Storage");
        return false;
    } else if (cancel && (pickup && storage)) {
        alert('To cancel ticket, all materials must be set to CANCEL.');
        return false;
    }
    
    //if pickup check ID for #
}

function calc (){
    var total = 0;
    <?php 
    foreach ($ticket->getMats_used() as $mu) {
        $mu_id = $mu->getMu_id();
        echo ("\n\t//Material: $mu_id");
        echo ("\n\tvar status_$mu_id = document.getElementById('status_$mu_id').value;");
        echo ("\n\tvar rate_$mu_id = ".$mu->getMaterial()->getPrice().";");
        echo ("\n\tvar vol_$mu_id = document.getElementById('uu_$mu_id').value;");
        echo ("\n\n\tif (status_$mu_id != 12){");
        echo ("\n\t\ttotal += rate_$mu_id * vol_$mu_id;\n\t}");
        echo ("\n\n\tif (status_$mu_id == 20 || status_$mu_id == 15) {");
        echo ("\n\t\tdocument.getElementById('pickTR').style.display = 'table-row';\n\t} else {");
        echo ("\n\t\tdocument.getElementById('pickTR').style.display = 'none';\n\t}");
    }
    ?> 
    document.getElementById("total").innerHTML = "Total : <i class='fa fa-<?php echo $sv['currency'];?> fa-fw'></i> " + total.toFixed(2);
}
<?php } ?>
</script>