<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$errorMsg = $hasCost = "";
unset($_SESSION['pickupUser']);

//check trans_id
if (empty($_GET["trans_id"])){
    $errorMsg = "Ticket # is Missing.";
	$_SESSION['type'] = "error";
} elseif ($staff) {
    $trans_id = filter_input(INPUT_GET, "trans_id", FILTER_VALIDATE_INT);
    try {
        //Exception is thrown if ticket is not found
        $ticket = new Transactions($trans_id);
    } catch(Exception $e) {
        $errorMsg = $e->getMessage();
        $_SESSION['type'] = "error";
    }
	
    //If status is 12(failed) or higher there is nothing else to do
    // to this ticket.
    if ($ticket->getStatus()->getStatus_id() > 11){
        $_SESSION['success_msg'] = "This Ticket ".$ticket->getTrans_id()." remains unchanged.";
        header("Location:/pages/lookup.php?trans_id=".$ticket->getTrans_id());
        exit();
    }
	
    //Create backup if origin was from the home page
    if ( $_SESSION['type'] == "home" && !isset($_POST['undoBtn'])){
        $_SESSION['backup_ticket'] = serialize($ticket);
    }
    
    //Determine if any material needs to be measured
    $measurable = "N";
    $device_mats = Materials::getDeviceMats($ticket->getDevice()->getDg()->getDg_id());
    if ($ticket->getDevice()->getDG()->getSelectMatsFirst() == "Y" && count($device_mats) > 0){
        foreach($ticket->getMats_used() as $mu){
            if ($mu->getMaterial()->getMeasurable() == "Y") {
                $measurable = "Y";
                break;
            }
        }
        foreach($device_mats as $mu){
            if ($mu->getMeasurable() == "Y") {
                $measurable = "Y";
                break;
            }
        }
    }
    
    $hasCost = $ticket->quote();
    if ($measurable == "N" && $hasCost < .005) {
        $msg = "";
        //No Mats or costs associated with this ticket
        $msg = $ticket->end(14, $staff);
        if (is_string($msg)){
            $errorMsg = $msg;
        } else {
            $_SESSION['type'] = "end";
            header("Location:/pages/lookup.php?trans_id=".$ticket->getTrans_id());
            exit();
        }
    } else {
        //$errorMsg = "No End";
        $errorMsg;
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
            $backup = unserialize($_SESSION['backup_ticket']);
            //echo( "Lets undo this ticket ".$backup->getTrans_id() );
            
            //Test if Back->trans_id() == $_GET['trans_id'] 
           
            if ($backup->writeAttr() === true){
                unset($_SESSION['backup_ticket']);
                // "Ticket #".$backup->getTrans_id()." has been restored.";
                header("Location:/index.php");
            } else {
                $errorMsg = "Unable to Un-End Ticket";
            }
        } else {
            $errorMsg = "Unable to Un-End Ticket";
        }
        
    //End Button was Pressed
    } elseif(isset($_POST['endBtn'])) {
        //Decision Check of Mats_used, only one destination allowed
        $storage = false; //status id = 14
        $cancel = false; //status id = 15
        $pickup = false; //status id = 20
        $status_id = 0;
		
        /* 
        *  Walk through the list of materials that are already associated with the
        *  ticket to determine their individual status and amount used.
        *
        *  Rewrite - pull keys from Post grab material ID from uu_m_id.
        *  Which will allow for us to add many materials to one ticket.
        */
        foreach ($ticket->getMats_used() as $mu){
            //mu_id is used to find the unique html fields
            $mu_id = $mu->getMu_id();
            
            $mu->setStaff($staff);
            $mu->setMu_notes(filter_input(INPUT_POST, "mu_notes_$mu_id", FILTER_SANITIZE_MAGIC_QUOTES));
            $string = filter_input(INPUT_POST, "mu_notes_$mu_id", FILTER_SANITIZE_MAGIC_QUOTES);
			
            if (!Status::regexID($_POST["status_$mu_id"]))
                {$errorMsg = "Invalid Status - MU$mu_id: ".filter_input(INPUT_POST, "status_$mu_id");}
            elseif ($errorMsg == "")
                {$mu_s = filter_input(INPUT_POST, "status_$mu_id");}

            //If mats used are set to both storage and pickup then DENY
            if($mu_s == 14)//Storage OR Complete
                {$storage = true;}
            if($mu_s == 15)//set to cancel
                {$cancel = true;}
            if($mu_s == 20)//Pick UP
                {$pickup = true;}
            //Write the highest status to the Ticket
            if ($status_id < $mu_s)
                {$status_id = $mu_s;}
            
            //Determine if Material was Already Marked as Paid
            if($mu->getStatus()->getStatus_id() < 20) {
                $msg = $mu->setUnit_used($_POST["uu_$mu_id"]);
                if (is_string($msg))
                    {$errorMsg = $msg;}
            
                $mu->setStatus_id($mu_s);
                
            }
            echo "<script>console.log( 'Debug MU ID $mu_id, Status:$mu_s');</script>";
        }
        
        if ($pickup && $storage){
            $errorMsg = "You must decide either to Pickup or Move to Storage";
        } elseif ($cancel && ($pickup || $storage)) {
            $errorMsg = "To cancel ticket, all must be set to cancel.";
        } elseif ($errorMsg == "") { //No Errors Thus Far
            //Mark Transaction w/ Status ID
            $ticket->setStaff($staff);
            $ticket->setStatus_id($status_id);
			
            //Based on the overall status determine the destination.
            if ($status_id == 12){
                //Mark Transaction w/ Failed Status
                $_SESSION['type'] = "failed";
                $msg = $ticket->end($status_id, $staff);
                if (is_string($msg)){
                    $errorMsg = $msg;
                    echo "<script>console.log( \"Debug ErroMsg e167: $errorMsg\");</script>";
                } else {
                    header('Location:/pages/lookup.php?trans_id='.$ticket->getTrans_id());
                }
                
            //} elseif ($status_id == 14 && $ticket->getDevice()->getDG()->getStorable() == "Y") {
            } elseif ($status_id == 14) {
                if ($ticket->getDevice()->getDG()->getStorable() == "Y"){
                    $msg = $ticket->move($staff);
                    $msg = true;
                } elseif ($hasCost > .005) {
                    //Ticket has a balance, Store ticket state
                    $_SESSION['ticket'] = serialize($ticket);
                    header('Location:/pages/pay.php');
                }
                if (is_string($msg)){
                    $errorMsg = $msg;
                    echo "<script>console.log( \"Debug ErroMsg e183: $errorMsg\");</script>";
                } else {
                    $_SESSION['type'] = "end";
                    header('Location:/pages/lookup.php?trans_id='.$ticket->getTrans_id());
                }

            } elseif ($status_id == 15 || $status_id == 20){
                //Cancelled or Pay Now
                //Assess if there needs to be a charge
                
                //check if user is authorized to pickup print
                if ($ticket->getDevice()->getDg()->getStorable() == "Y") {
                    $user = Users::withID(filter_input(INPUT_POST, "pickID"));
                    $msg = AuthRecipients::validatePickUp($ticket, $user);
                    if (is_string($msg)) {
                        $errorMsg = $msg;
                        echo "<script>console.log( 'Debug ErroMsg e200: $errorMsg');</script>";
                    } elseif ($msg === true) {
                        $_SESSION['pickupUser'] = serialize($user);
                    }
                }

                if(is_string($user)){
                    $errorMsg = $user;
                    echo "<script>console.log( 'Debug ErroMsg e210: $errorMsg');</script>";
                } elseif ($errorMsg == "" && $ticket->quote() < .005) {
                    $ticket->writeAttr();
                    header('Location:/pages/lookup.php?trans_id='.$ticket->getTrans_id());
                } elseif ($errorMsg == ""){
                    //Store ticket state
                    $_SESSION['ticket'] = serialize($ticket);
                    header('Location:/pages/pay.php');
                }
            }
        }
    }
}
if ($errorMsg != ""){
    echo "<script>window.onload = function(){goModal('Error',\"$errorMsg\", false)}</script>";
}
?>
<title><?php echo $sv['site_name'];?> End Ticket</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php if ($hasCost) {
                echo "<h1 class='page-header'>Ticket Not Closed, Yet</h1> This ticket may require payment.";
            }  elseif (false) {
                echo "<h1 class='page-header'>Not Authorized To End Ticket</h1> You must be either staff or closing your own ticket.";
            } else {
                echo "<h1 class='page-header'>End Ticket</h1>";
            }?>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-7">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-ticket-alt fa-fw"></i> Ticket #<?php echo $ticket->getTrans_id();?>
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-bordered">
                        <form name="endForm" method="post" action="" autocomplete="off" onsubmit="return validateForm()">
                            <tr>
                                <td>Device</td>
                                <td><?php echo $ticket->getDevice()->getDevice_desc();?></td>
                            </tr>
                            <tr>
                                <td>Operator</td>
                                <?php echo "<td><i class='".$ticket->getUser()->getIcon()." fa-lg' title='".$ticket->getUser()->getOperator()."'></i></td>"; ?>
                            </tr>
                            <tr>
                                <td>Ticket</td>
                                <td><?php echo $ticket->getTrans_id();?></td>
                            </tr>
                            <tr>
                                <td>Time</td>
                                <td><?php echo $ticket->getT_start()." - ".$ticket->getT_end(); ?></td>
                            </tr>
                            <?php if ($ticket->getEst_time() != "") { ?>
                                <tr>
                                    <td>Estimated Time</td>	
                                    <td><?php echo $ticket->getEst_time(); ?></td>
                                </tr>
                            <?php } ?>
                            <?php if ($ticket->getDuration() != "") { ?>
                                <tr>
                                    <td>Duration</td>
                                    <td><?php
                                        echo $ticket->getDuration();
                                        //Display Device per hour cost
                                        if ($ticket->getDevice()->getBase_price() > .000001){
                                            echo " * <i class='$sv[currency] fa-fw'></i>".$ticket->getDevice()->getBase_price(). "/hour";
                                        }
                                    ?></td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td>Status</td>
                                <td><?php echo $ticket->getStatus()->getMsg(); ?></td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <?php  //Qualify Role is staff and 3D Print Job
                                    if ($staff->getRoleID() >= $sv['LvlOfStaff'] && $ticket->getDevice()->getDg()->getStorable() == "Y") {
                                        //List each Material that is already associated with the ticket
                                        foreach ($ticket->getMats_used() as $mu) {
                                            $mu_id = $mu->getMu_id();//pull ID to get unique identifier?>
                                            <table class="table table-bordered"><tbody>
                                                <?php //if ($mu->getStatus()->getStatus_id() == 20) { //Material has already been marked as paid
                                                // Alt use DG's PayFirst
                                                if( $ticket->getDevice()->getDG()->getPayFirst() == "Y"){?>
                                                    <tr class="tablerow info">
                                                        <td>
                                                            <?php echo $mu->getMaterial()->getM_name();
                                                            if ($mu->getMaterial()->getColor_hex()){ ?>
                                                                <div class="color-box" style="background-color: #<?php echo $mu->getMaterial()->getColor_hex();?>;"/>
                                                            <?php } ?>
                                                        </td>
                                                        <td>
                                                            <i class='<?php echo $sv['currency'];?> fa-fw'></i> <?php echo $mu->getMaterial()->getPrice()." x "; ?>
                                                            <input type="number" name="uu_<?php echo $mu_id;?>" id="uu_<?php echo $mu_id;?>" autocomplete="off" value="<?php echo ($mu->getUnit_used());?>" min="0" max="9999" style="text-align:right;" readonly="true">
                                                            <?php echo ($mu->getMaterial()->getUnit());
                                                            if ($mu->getMu_date()){ ?>
                                                                <i class="far fa-calendar-alt fa-lg" title="<?php echo $mu->getMu_date();?>"></i>
                                                            <?php } 
                                                            if ( is_object($mu->getStaff()) ){ ?>
                                                                <i class="<?php echo $mu->getStaff()->getIcon();?> fa-lg" title="<?php echo $mu->getStaff()->getOperator(); ?>"></i>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Material Status</td>
                                                        <td><select name="status_<?php echo $mu_id;?>" id="status_<?php echo $mu_id;?>" onchange="calc()">
                                                            <option value="" selected hidden="true">Select</option>
                                                            <option value="14">Move to Storage</option>
                                                            <option value="20">Pickup Now</option>
                                                        </select></td>
                                                    </tr>
                                                <?php //Output from this device can be stored
                                                } else { ?>
                                                    <tr class="tablerow info">
                                                        <td>
                                                            <?php echo $mu->getMaterial()->getM_name();
                                                            if ($mu->getMaterial()->getColor_hex()){ ?>
                                                                <div class="color-box" style="background-color: #<?php echo $mu->getMaterial()->getColor_hex();?>;"/>
                                                            <?php } ?>
                                                        </td>
                                                        <td>
                                                            <i class='fa fa-<?php echo $sv['currency'];?> fa-fw'></i> <?php echo sprintf("%.2f",$mu->getMaterial()->getPrice())." x \n";?>
                                                            <input type="number" name="uu_<?php echo $mu_id;?>" id="uu_<?php echo $mu_id;?>" autocomplete="off" value="<?php echo ($mu->getUnit_used());?>" min="0" max="9999" onchange="calc()" onkeyup="calc()" onclick="calc()" style="text-align:right;">
                                                            <?php echo $mu->getMaterial()->getUnit();
                                                            if ($mu->getMu_date()){ ?>
                                                                <i class="far fa-calendar-alt fa-lg" title="<?php echo $mu->getMu_date();?>"></i>
                                                            <?php } 
                                                            if ( is_object($mu->getStaff()) ){?>
                                                                <i class="<?php echo $mu->getStaff()->getIcon();?> fa-lg" title="<?php echo $mu->getStaff()->getOperator(); ?>"></i>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Material Status</td>
                                                        <td><select name="status_<?php echo $mu_id;?>" id="status_<?php echo $mu_id;?>" onchange="calc()">
                                                            <option value="" selected hidden="true">Select</option>
                                                            <option value="14">Move to Storage</option>
                                                            <option value="20">Pickup Now</option>
                                                            <option value="12">Fail</option>
                                                            <option value="15">Cancel</option>
                                                        </select></td>
                                                    </tr>
                                                <?php } ?>
                                                <?php if ($mu->getHeader() != ""){ ?>
                                                    <tr>
                                                        <td>File Name</td>
                                                        <td><?php echo $mu->getHeader(); ?></td>
                                                    </tr>
                                                <?php } ?>
                                                <tr>
                                                    <td><i class="fas fa-edit"></i>Notes</td>
                                                    <td><textarea name="mu_notes_<?php echo $mu_id;?>" id="mu_notes_<?php echo $mu_id;?>" class="form-control"><?php echo $mu->getMu_notes();?></textarea></td>
                                                </tr>
                                            </tbody></table>
                                        <?php }
                                    //Basic Ticket Closing
                                    } else {
                                        foreach ($ticket->getMats_used() as $mu) {
                                            $mu_id = $mu->getMu_id();//pull ID to get unique identifier?>
                                            <table class="table table-bordered"><tbody>
                                                <tr class="tablerow info">
                                                    <?php if ($mu->getMaterial()->getPrice() > 0 || $mu->getMaterial()->getMeasurable() == "Y"){ ?>
                                                        <td class="col-md-5">
                                                            <?php echo $mu->getMaterial()->getM_name();
                                                            if ($mu->getMaterial()->getColor_hex()){ echo "<div class=\"color-box\" style=\"background-color: #".$mu->getMaterial()->getColor_hex()."\"/>\n"; }?>
                                                        </td>
                                                        <td class="col-md-7">
                                                            <i class='fa fa-<?php echo $sv['currency'];?> fa-fw'></i> <?php echo sprintf("%.2f",$mu->getMaterial()->getPrice())." x \n";?>
                                                            <input type="number" name="uu_<?php echo $mu_id;?>" id="uu_<?php echo $mu_id;?>" autocomplete="off" value="<?php echo ($mu->getUnit_used());?>" min="0" max="9999" onchange="calc()" onkeyup="calc()" onclick="calc()" style="text-align:right;">
                                                            <?php echo $mu->getMaterial()->getUnit();
                                                            if ($mu->getMu_date()){ ?>
                                                                <i class="far fa-calendar-alt fa-lg" title="<?php echo $mu->getMu_date();?>"></i>
                                                            <?php } 
                                                            if ( is_object($mu->getStaff()) ){?>
                                                                <i class="<?php echo $mu->getStaff()->getIcon();?> fa-lg" title="<?php echo $mu->getStaff()->getOperator(); ?>"></i>
                                                            <?php } ?>
                                                        </td>
                                                    <?php } else {?>
                                                        <td colspan="2"><?php echo $mu->getMaterial()->getM_name();?></td>
                                                    <?php } ?>
                                                </tr>
                                                <td>Material Status</td>
                                                <td><select name="status_<?php echo $mu_id;?>" id="status_<?php echo $mu_id;?>" onchange="calc()">
                                                    <option value="" selected hidden="true">Select</option>
                                                    <option value="20">Complete</option>
                                                    <option value="12">Fail</option>
                                                    <option value="15">Cancel</option>
                                                </select></td>
                                                <?php if (strcmp($mu->getHeader(), "") != 0){ ?>
                                                    <tr>
                                                        <td>File Name</td>
                                                        <td><?php echo $mu->getHeader(); ?></td>
                                                    </tr>
                                                <?php } ?>
                                                <tr>
                                                    <td>Staff</td>
                                                    <td><?php if ($staff && $staff->getRoleID() >= $sv['LvlOfStaff'] && $mu->getStaff()){
                                                        echo "<i class='".$mu->getStaff()->getIcon()." fa-lg' title='".$mu->getStaff()->getOperator()."'></i>\n";
                                                    } elseif ($mu->getStaff()) { 
                                                        echo "<i class='".$mu->getStaff()->getIcon()." fa-lg'></i>\n";
                                                    }?></td>
                                                </tr>
                                                <?php if ($staff && $staff->getRoleID() >= $sv['LvlOfStaff']){ ?>
                                                    <tr>
                                                        <td><i class="fas fa-edit"></i>Notes</td>
                                                        <td><textarea name="mu_notes_<?php echo $mu_id;?>" id="mu_notes_<?php echo $mu_id;?>" class="form-control"><?php echo $mu->getMu_notes();?></textarea></td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody></table>
                                        <?php }
                                    } ?>
                                </td>
                            </tr>
                            <?php //Qualify Role is staff and 3D Print Job
                            if ($staff->getRoleID() >= $sv['LvlOfStaff'] && $ticket->getDevice()->getDg()->getStorable() == "Y") { ?>
                                <tr id="pickTR" style="display: none;">
                                    <td>Confirm ID</td>
                                    <td><input type="text" name="pickID" class="form-control" placeholder="Enter ID #" maxlength="10" size="10"></td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td colspan="2"><div id="total" style="float:right;">Total : <?php printf("<i class='%s'></i> %.2f" ,$sv['currency'], $hasCost); ?></div></td>
                            </tr>
                            <tr class="tablefooter">
                                <td align="right" colspan="2">
                                    <input type="submit" name="endBtn" value="Submit" class="btn btn-danger"/>
                                </td>
                            </tr>
                        </form>
                    </table>
                </div>
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-md-7 -->
        <div class="col-md-5">
            <?php //Look for associated charges Panel
            if($staff && $ticket->getAc() && (($ticket->getUser()->getOperator() == $staff->getOperator()) || $staff->getRoleID() >= $sv['LvlOfStaff']) ){ ?>
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
                                                <li style="padding-left: 5px;"><?php echo $ac->getAccount()->getA_id().": ".$ac->getAc_notes();?></li>
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
            <?php //ObjectBox Panel Stats 
            if ($ticket->getDevice()->getDg()->getStorable() == "Y") { ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-gift"></i> ObjectBox Stats
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-hover">
                            <tr>
                                <td>Capacity</td>
                                <td><?php echo $sv['box_number'] * $sv['letter'];?></td>
                            </tr>
                            <tr>
                                <td>In Storage</td>
                                <td><?php echo ObjBox::inStorage();?></td>
                            </tr>
                            <tr>
                                <td>Total Objects Managed</td>
                                <td><?php echo ObjBox::lifetimeObj();?></td>
                            </tr>
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
    //var prePaid = <?php echo $ticket->totalAC();?>;
    var hasCost = <?php echo $hasCost;?>;
    var success = 0; //determinate for charging for machine costs.
    <?php 
    foreach ($ticket->getMats_used() as $mu) {
        $mu_id = $mu->getMu_id();
        echo ("\n\t//Material: $mu_id");
        echo ("\n\tvar status_$mu_id = document.getElementById('status_$mu_id').value;");
        echo ("\n\tvar rate_$mu_id = ".$mu->getMaterial()->getPrice().";");
        echo ("\n\tvar vol_$mu_id = document.getElementById('uu_$mu_id').value;");
        //drop from calculation because it is already included in the HasCost attribute
        if ($mu->getStatus()->getStatus_id() < 20){
            echo ("\n\n\tif (status_$mu_id != 12){");
            echo ("\n\t\ttotal += rate_$mu_id * vol_$mu_id;");
            echo ("\n\t\tsuccess = 1;\n\t}");
        }
        if ($staff->getRoleID() >= $sv['LvlOfStaff'] && $ticket->getDevice()->getDg()->getStorable() == "Y") {
            echo ("\n\n\tif (status_$mu_id == 20 || status_$mu_id == 15) {");
            echo ("\n\t\tdocument.getElementById('pickTR').style.display = 'table-row';\n\t} else {");
            echo ("\n\t\tdocument.getElementById('pickTR').style.display = 'none';\n\t}");
        }
    }
    ?>
    total = total + .001 + hasCost * success;
    document.getElementById("total").innerHTML = "Total : <i class='<?php echo $sv['currency'];?>'></i> " + total.toFixed(2);
}
<?php } ?>
</script>