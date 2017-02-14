<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$errorMsg = "";

//check trans_id
if (empty($_GET["trans_id"])){
    $errorMsg = "Ticket # is Missing.";
} elseif ($staff) {
    $trans_id = $_GET["trans_id"];
    $ticket = new Transactions($trans_id);
    if ( $_SESSION['type'] == "home" )
        $_SESSION['backup'] = new Transactions($trans_id);
    
    //Special Device Groups
    $special_devices = array("vinyl", "embroidery", "sew", "uprint", "screen");
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
        foreach($mats_used as $mu){
            if($mu->getMaterial()->getPrice() > 0){
                $hasCost = true;
            }
        }
       
        if( !$found && !$hasCost && ($staff->getRoleID() > 6 || $staff->getOperator() == $ticket->getUser()->getOperator()) && $ticket->getStatus()->getStatus_id() <= 11 ){
            //Complete Status
            $status_id = 14;
            if(!$msg = $ticket->end($status_id, $staff->getOperator())){
                $errorMsg = $msg;
            }
        }
    }    
} else {
    header("location: /index.php");
}
if ($errorMsg != ""){
    echo "<script> alert(\"$errorMsg\")</script>";
} else 
    $_SESSION['type'] = "end";


//When the user hits Submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    
    //Undo Button was Pressed
    if (isset($_POST['undoBtn'])) {
        if ($_SESSION['type'] == "end")
            $backup = $_SESSION['backup'];
        echo( "Lets undo this ticket ".$backup->getTrans_id() );
        if ($backup->writeAttr()){
            $_SESSION['backup'] = null;
            header("Location:/index.php");
        } else
            echo"<br> Failed";
        $_SESSION['backup'] = null;
        
    //End Button was Pressed
    } elseif(isset($_POST['endBtn'])) {
        $storage = false; //status id = 14
        $pickup = false; //status id = 20
        $status_id = 0;
        foreach ($mats_used as $mu){
            $mu_id = $mu->getMu_id();
            if (!Mats_Used::regexUnit_Used($_POST["uu_$mu_id"])) return;
            if (!Status::regexID($_POST["status_$mu_id"])) return;
            $mu->setUnit_used($_POST["uu_$mu_id"]);
            $mu->getStatus()->setStatus_id($_POST["status_$mu_id"]);
            $mu->setMu_notes($_POST["mu_notes_$mu_id"]);
            
            //If mats used are set to both storage and pickup
            // Then Deny
            if($mu->getStatus()->getStatus_id() == 14)
                $storage = true;
            if($mu->getStatus()->getStatus_id() == 20)
                $pickup = true;
            //Write the highest status to the Ticket
            if ($status_id < $mu->getStatus()->getStatus_id())
                $status_id = $mu->getStatus()->getStatus_id();
        }
        
        if ($pickup && $storage){
            echo "<script> alert('You must decide either to Pickup or Move to Storage')</script>";
        } else {
            move($status_id, $ticket, $mats_used, $staff->getOperator());
        }
    }
}

function move($status_id, $ticket, $mats_used, $staff_id){
    //Mark Transaction as Complete
    $errorMsg = $ticket->end($status_id, $staff_id);
    if (is_string($errorMsg)){
        echo "<script> alert('$errorMsg')</script>";
        return;
    }
    
    //Write Materials Used to the DB
    foreach($mats_used as $mu){
        $errorMsg = $mu->writeAttr();
        if (is_string($errorMsg)){
            echo "<script> alert('$errorMsg')</script>";
            return;
        }
    }
    
    //Assigns Address & Move Object into storage
    $o_id = ObjBox::insert_Obj($ticket->getTrans_id(), $staff_id);
    if (is_string($o_id)){
        echo "<script> alert('$o_id')</script>";
        return; // exit because ObjBox has Error
    }
    
    //send page to reports to view address
    if ($status_id == 14){
        $_SESSION['type'] = "end";
        header('Location:/pages/lookup.php?trans_id='.$ticket->getTrans_id());
        
    //
    } elseif ($status_id == 20) {
        $_SESSION['type'] = "end";
        header('Location:/pages/pay.php?trans_id='.$ticket->getTrans_id());
    }
}
?>
<title>FabLab - End Ticket</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <?php if ($ticket->getDevice()->getDg_Parent() == 1) { ?>
                <h1 class="page-header">3D Prints</h1>
            <?php } elseif ($ticket->getDuration() == "" && !$hasCost) { ?>
                <h1 class="page-header">Not Authorized to end Ticket</h1>
                You must be either staff or closing your own ticket.
            <?php }  elseif ($ticket->getDuration() == "" && $hasCost) { ?>
                <h1 class="page-header">Ticket Not CLOSED</h1>
                This ticket requires payment.
                <script>alert("This ticket requires payment.");</script>
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
                        <?php if ($staff) { if ($staff->getRoleID() > 6 && $ticket->getDevice()->getDg_Parent() == 1) {
                            //Qualfy Role is staff and 3D Print Job
                            ?>
                            <form name="endForm" method="post" action="" autocomplete="off" onsubmit="return validateForm()">
                                <tr>
                                    <td>Device</td>
                                    <td><?php echo $ticket->getDevice()->getDevice_desc();?></td>
                                </tr>
                                <tr >
                                    <td>ID #</td>
                                    <td><?php echo $ticket->getUser()->getOperator();?></td>
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
                                    $mu_id = $mu->getMu_id();?>
                                    <tr>
                                        <td colspan="2">
                                            <table class="table table-bordered"><tbody>
                                                <tr class="tablerow info">
                                                    <td><?php echo $mu->getMaterial()->getM_name();?></td>
                                                    <td>
                                                        <?php printf("$%.2f x ", $mu->getMaterial()->getPrice());?>
                                                        <input type="number" name="uu_<?php echo $mu_id;?>" id="uu_<?php echo $mu_id;?>" autocomplete="off" value="" min="0" max="9999" step="1" onchange="calc()" onkeyup="calc()" style="text-align:right;">
                                                        <?php echo $mu->getMaterial()->getUnit(); ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Print Status</td>
                                                    <td><select name="status_<?php echo $mu_id;?>" id="status_<?php echo $mu_id;?>" onchange="calc()">
                                                            <option value="" selected hidden="true">Select</option>
                                                        <option value="14">Move to Storage</option>
                                                        <option value="20">Pickup Now</option>
                                                        <option value="12">Failed</option>
                                                    </select></td>
                                                </tr>
                                                <?php
                                                if (strcmp($mu->getFileName(), "") != 0){ ?>
                                                    <tr>
                                                        <td>File Name</td>
                                                        <td><?php echo $mu->getFileName(); ?></td>
                                                    </tr>
                                                <?php } ?>
                                                <tr>
                                                    <td><i class="fa fa-pencil-square-o fa-fw"></i>Notes</td>
                                                    <td><textarea name="mu_notes_<?php echo $mu_id;?>" id="mu_notes_<?php echo $mu_id;?>" class="form-control"><?php echo $mu->getMu_notes();?></textarea></td>
                                                </tr>
                                            </tbody></table>
                                        </td>
                                    </tr>
                                <?php } ?>
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
                                <?php if (strcmp($mu->getFileName(), "") != 0){ ?>
                                    <tr>
                                        <td>File Name</td>
                                        <td><?php echo $mu->getFileName(); ?></td>
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
        echo ("\t\tdocument.getElementById('status_$mu_id').focus();\n");
        echo ("\t\treturn false;\n\t}\n");
        echo ("\tif (x == 14){\n");
        echo ("\t\tstorage = true;\n\t} else if(x == 20) {\n");
        echo ("\t\tpickup = true;\n\t}");
        
        //check Notes Field
        echo ("\n\tif (x == 12){\n");
        echo ("\t\t//Notes Field Required for Failed Prints \n");
        echo ("\t\tvar notes = document.getElementById('mu_notes_$mu_id').value;\n");
        echo ("\t\tvar msg = '';\n");
        echo ("\t\tif (notes.length < 10){\n");
        echo ("\t\t\tmsg='Please Explain More......'\n");
        echo ("\t\t\tif (notes.length == 0){\n");
        echo ("\t\t\t\tmsg='Please state why this might have failed'\n");
        echo ("\t\t\t}\n");
        echo ("\t\t\talert(msg);\n");
        echo ("\t\t\tdocument.getElementById('mu_notes_$mu_id').focus();\n");
        echo ("\t\t\treturn false;\n\t\t}\n\t}\n\n\t");
    }?>
    
    if (pickup && storage){
        alert("You must decide either to Pickup or Move to Storage");
        return false;
    }
}

function calc (){
    var total = 0;
    <?php 
    foreach ($mats_used as $mu) {
        $mu_id = $mu->getMu_id();
        echo ("\n\t//Material:$mu_id\n");
        echo ("\tvar status_$mu_id = document.getElementById('status_$mu_id').value;\n");
        echo ("\tvar rate_$mu_id = ".$mu->getMaterial()->getPrice().";\n");
        echo ("\tvar vol_$mu_id = document.getElementById('uu_$mu_id').value;\n");
        echo ("\tif (status_$mu_id != 12)\n");
        echo ("\t\ttotal += rate_$mu_id * vol_$mu_id;\n");
    }
    ?>
            
    document.getElementById("total").innerHTML = "Total : <i class='fa fa-dollar fa-fw'></i> " + total.toFixed(2);
}
<?php } ?>
</script>