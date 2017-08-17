<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$errorMsg = "";
$_SESSION['$pickupID'] = "";

//check trans_id
if (empty($_GET["trans_id"])){
    $errorMsg = "Ticket # is Missing.";
} elseif ($staff) {
    $trans_id = $_GET["trans_id"];
    //Exception is thrown if ticket is not found
    $ticket = new Transactions($trans_id);
    if ( $_SESSION['type'] == "home" ){
        $_SESSION['ticket'] = serialize(new Transactions($trans_id));
         $_SESSION['mats_used'] = serialize(Mats_Used::byTrans($trans_id));
    }
    
    //Special Device Groups
    $special_devices = array("vinyl", "embroidery", "uprint", "screen");
    $found = false;
    
    //check device type to determine closing procedure
    if ($ticket->getDevice()->getDg_Parent() == 1){
        //3D Print closing procedures
        //pull down materials
        $mats_used = Mats_Used::byTrans($trans_id);
        
    //We have a NON 3D-printer Ticket
    } elseif ($ticket->getStatus()->getStatus_id() < 12){
        for ($i=0; $i < count($special_devices) && !$found; $i++){
            if ( strcmp($ticket->getDevice()->getDg_Name(), $special_devices[$i]) == 0 ){
                //echo "Found it - ".$device->getDg_Name();
                $found = true;
            }
        }
        
        //regular ticket closing
        // To allow patrons to self close we must check if payment is required
        $mats_used = Mats_Used::byTrans($ticket->getTrans_id());
        $hasCost = false;
        if(count($mats_used) == 0){
            //Check to see if mats for that machine has an associated cost
            $device_mats = Materials::getDeviceMats($ticket->getDevice()->getDg_id());
            foreach($device_mats as $dm){
                if($dm["price"] > 0){
                    $hasCost = true;
                    //echo "No Material Listed, but they do cost money";
                }
            }
        } else {
            foreach($mats_used as $mu){
                if($mu->getMaterial()->getPrice() > 0){
                    $hasCost = true;
                    //echo "Material Listed costs money";
                }
            }
        }
        
        //Check if Device has runtime Costs
        if ($ticket->getDevice()->getBase_price() > 0){
            $hasCost = true;
        }
       
        if( !$found && !$hasCost && ($staff->getRoleID() > 6 || $staff->getOperator() == $ticket->getUser()->getOperator()) && $ticket->getStatus()->getStatus_id() <= 11 ){
            //Complete Status - no costs
            $status_id = 14;
            if(!$msg = $ticket->end($status_id, $staff->getOperator())){
                $errorMsg = $msg;
            } else {
                $_SESSION['type'] = "end";
            }
        }
    }
//Not logged In
} else {
    header("location: /index.php");
}

//When the user hits Submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($errorMsg == "")) {
    
    //Undo Button was Pressed
    if (isset($_POST['undoBtn'])) {
        if ($_SESSION['type'] == "end")
            $backup = unserialize($_SESSION['ticket']);
        echo( "Lets undo this ticket ".$backup->getTrans_id() );
        if ($backup->writeAttr() === true){
            $_SESSION['ticket'] = null;
            header("Location:/index.php");
        } else
            echo"<script> alert('Unable to Undo')</script>";
        $_SESSION['ticket'] = null;
        
    //End Button was Pressed
    } elseif(isset($_POST['endBtn'])) {
        $storage = false; //status id = 14
        $pickup = false; //status id = 20
        $cancel = false; //status id = 15
        $status_id = 0;
        foreach ($mats_used as $mu){
            $mu_id = $mu->getMu_id();
            
            $msg = $mu->setMu_notes($_POST["mu_notes_$mu_id"]);
            if ($msg != true && ($errorMsg == ""))
                $errorMsg = $msg;
            
            //Determine if Material was Already Marked as Paid
            if($mu->getStatus()->getStatus_id() < 20) {
                $msg = $mu->setUnit_used($_POST["uu_$mu_id"]);
                if ($msg != true)
                    {$errorMsg = $msg;}
            
                if (!Status::regexID($_POST["status_$mu_id"]))
                    {$errorMsg = "Invalid Status :".$_POST["status_$mu_id"];}
                elseif ($errorMsg == "") 
                    {$mu->setStatus($_POST["status_$mu_id"]);}
            
                //If mats used are set to both storage and pickup
                // Then Deny
                if($mu->getStatus()->getStatus_id() == 14)//Storage
                    $storage = true;
                if($mu->getStatus()->getStatus_id() == 20)//pick & pay
                    $pickup = true;
                if($mu->getStatus()->getStatus_id() == 15)//Set to cancel
                    $cancel = true;
                //Write the highest status to the Ticket
                if ($status_id < $mu->getStatus()->getStatus_id())
                    $status_id = $mu->getStatus()->getStatus_id();
            } elseif ($mu->getStatus()->getStatus_id() == 20) {
                //Determine the destination for a pre-paid print
                if (!Status::regexID($_POST["status_$mu_id"]))
                    {$errorMsg = "Invalid Status :".$_POST["status_$mu_id"];}
                elseif ($errorMsg == "") 
                    {$mu_s = $_POST["status_$mu_id"];}
            
                //If mats used are set to both storage and pickup
                // Then Deny
                if($mu_s == 14)//Storage
                    $storage = true;
                if($mu_s == 20)//pick
                    $pickup = true;
                if($mu_s == 15)//set to cancel
                    $cancel = true;
                //Write the highest status to the Ticket
                if ($status_id < $mu_s)
                    $status_id = $mu_s;
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
                if(Users::regexUser($_POST["pickID"])){
                    $pickupID = $_POST["pickID"];
                    move($status_id, $ticket, $mats_used, $staff->getOperator(), $pickupID);
                } else {
                    $errorMsg = "Invalid ID Number";
                }
            } else {
                $pickupID = "";
                move($status_id, $ticket, $mats_used, $staff->getOperator(), $pickupID);
            }
        }
    }
}

if ($errorMsg != ""){
    //Display Error Msg as JS alert
    echo "<script> alert('$errorMsg'); window.location='/pages/end.php?trans_id=".$ticket->getTrans_id()."';</script>";
}

function move($status_id, $ticket, $mats_used, $staff_id, $pickupID){
    global $errorMsg;
    
    // Status was set to complete, Insert into ObjBox
    // Send to look up page to view Address
    if ($status_id == 14){
        //Mark Transaction w/ Status ID
        $msg = $ticket->end($status_id, $staff_id);
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
		
    //send page to the payment
    } elseif ($status_id == 20 ) { // Pick Up now & charge to accts
        $_SESSION['type'] = "end";
        echo "Pick Up now & charge to accts";
        
        //check if authorized to pickup print
        if ($ticket->getUser()->getOperator() == $pickupID) {
            $_SESSION['$pickupID'] = $pickupID;
        } else {
            $msg = AuthRecipients::validatePickUp($ticket->getTrans_id(), $pickupID);
            if (is_string($msg)) {
                $errorMsg = $msg;
                return;
            } elseif ($msg === true) {
                $_SESSION['$pickupID'] = $pickupID;
            }
        }
        
        //Pass Object to be updated upon Successful payment
        $ticket->setStatus($status_id);
        $_SESSION['ticket'] = serialize($ticket);
        $_SESSION['mats_used'] = serialize($mats_used);
        header('Location:/pages/checkout.php');
        
    } elseif ($status_id == 15) { //Cancelled Print assess if there needs to be a charge
        $_SESSION['type'] = "end";
        //Pass Object to be updated upon Successful payment
        $ticket->setStatus($status_id);
        $_SESSION['ticket'] = serialize($ticket);
        $_SESSION['mats_used'] = serialize($mats_used);
        header('Location:/pages/checkout.php');
        
    } elseif ($status_id == 12){ //Failed
        $_SESSION['type'] = "failed";
        //Mark Transaction w/ Failed Status
        $msg = $ticket->end($status_id, $staff_id);
        if (is_string($msg)){
            $errorMsg = $msg;
            return;
        }
        
        //Write Failed Ticket to the DB
        foreach($mats_used as $mu){
            $msg = $mu->updateUsed($staff_id);
            if (is_string($msg)){
                $errorMsg = $msg;
                return;
            }
        }
        header('Location:/pages/lookup.php?trans_id='.$ticket->getTrans_id());
    } else {
        $errorMsg = "Invalid Status - E189";
    }
}
?>
<title><?php echo $sv['site_name'];?> End Ticket</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php if ($ticket->getDevice()->getDg_Parent() == 1) { ?>
                <h1 class="page-header">3D Print</h1>
            <?php } elseif ($ticket->getDuration() == "" && $hasCost) { ?>
                <h1 class="page-header">Ticket Not Closed, Yet</h1>
                This ticket may require payment.
            <?php }  elseif ($ticket->getDuration() == "" && !$hasCost) { ?>
                <h1 class="page-header">Not Authorized To End Ticket</h1>
                You must be either staff or closing your own ticket.
            <?php } else {?>
                <h1 class="page-header">End Ticket</h1>
            <?php } ?>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <?php if ($staff) { ?>
                    <i class="fa fa-ticket fa-fw"></i> Ticket #<?php echo $ticket->getTrans_id();?>
                    <?php } ?>
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-bordered">
                        <?php  //Qualify Role is staff and 3D Print Job
                        if ($staff) { if ($staff->getRoleID() > 6 && $ticket->getDevice()->getDg_Parent() == 1) {
                            //Process uPrint job
                            //if (uPrint) {do...stuff} 
                            ?>
                            <form name="endForm" method="post" action="" autocomplete="off" onsubmit="return validateForm()">
                                <tr>
                                    <td>Device</td>
                                    <td><?php echo $ticket->getDevice()->getDevice_desc();?></td>
                                </tr>
                                <tr>
                                    <td>Operator</td>
                                    <td><i class="fa fa-<?php if ( $ticket->getUser()->getIcon() ) echo $ticket->getUser()->getIcon(); else echo "user";?> fa-fw"></i><?php echo "*******".substr($ticket->getUser()->getOperator(),7);?></td>
                                </tr>
                                <tr>
                                    <td>Ticket</td>
                                    <td><?php echo $ticket->getTrans_id();?></td>
                                </tr>
                                <tr>
                                    <td>Start</td>
                                    <td><?php echo$ticket->getT_start(); ?></td>
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
                                <?php foreach ($mats_used as $mu) {
                                    if ($mu->getStatus()->getStatus_id() == 20) {
                                        $mu_id = $mu->getMu_id();?>
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
                                    <?php } else {
                                        $mu_id = $mu->getMu_id(); ?>
                                        <tr>
                                            <td colspan="2">
                                                <table class="table table-bordered"><tbody>
                                                    <tr class="tablerow info">
                                                        <td><?php echo $mu->getMaterial()->getM_name();?></td>
                                                        <td>
                                                            <i class='fa fa-<?php echo $sv['currency'];?> fa-fw'></i> <?php echo $mu->getMaterial()->getPrice()." x \n";?>
                                                            <input type="number" name="uu_<?php echo $mu_id;?>" id="uu_<?php echo $mu_id;?>" autocomplete="off" value="" min="0" max="9999" step="1" onchange="calc()" onkeyup="calc()" style="text-align:right;">
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
                                    <?php }
                                }?>
                                <tr id="pickTR" style="display: none;">
                                    <td>Pick-Up ID</td>
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
                        <?php
//Basic Ticket Closing
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
                                <td>Start</td>
                                <td><?php echo $ticket->getT_start();?></td>
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
                            <?php foreach ($mats_used as $mu) {?>
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
                                }
                            }
                            if ($ticket->getDuration() != "" ){ ?>
                                <tr class="tablefooter">
                                    <td align="right" colspan="2">
                                        <form name="undoForm" method="post" action="">
                                            <input type="submit" name="undoBtn" value="Unend this Ticket"/>
                                        </form>
                                    </td>
                                </tr>
                            <?php }
                        } } ?>
                    </table>
                </div>
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-lg-8 -->
        <div class="col-lg-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-database fa-fw"></i> Stats
                </div>
                <div class="panel-body">
                    
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-lg-4 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript">
<?php if (isset($mats_used)){ ?>
function validateForm(){
    var storage = false;//14
    var pickup = false;//20
    var cancel = false;//15
    <?php foreach($mats_used as $mu) {
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
        echo ("\t\t//Notes Field Required for Failed Prints \n");
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
}

function calc (){
    var total = 0;
    <?php 
    foreach ($mats_used as $mu) {
        $mu_id = $mu->getMu_id();
        echo ("\n\t//Material:$mu_id");
        echo ("\n\tvar status_$mu_id = document.getElementById('status_$mu_id').value;");
        echo ("\n\tvar rate_$mu_id = ".$mu->getMaterial()->getPrice().";");
        echo ("\n\tvar vol_$mu_id = document.getElementById('uu_$mu_id').value;");
        echo ("\n\n\tif (status_$mu_id != 12){");
        echo ("\n\t\ttotal += rate_$mu_id * vol_$mu_id;\n\t}");
        echo ("\n\n\tif (status_$mu_id == 20) {");
        echo ("\n\t\tdocument.getElementById('pickTR').style.display = 'table-row';\n\t} else {");
        echo ("\n\t\tdocument.getElementById('pickTR').style.display = 'none';\n\t}");
    }
    ?>
    total += .001;  
    document.getElementById("total").innerHTML = "Total : <i class='fa fa-<?php echo $sv['currency'];?> fa-fw'></i> " + total.toFixed(2);
}
<?php } ?>
</script>