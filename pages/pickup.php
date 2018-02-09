<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$errorMsg = "";
unset($_SESSION['pickupUser']);

if($staff){
    //Staff members or Higher may use this process
    if($staff->getRoleID() <= 7){
        $errorMsg = "Only staff members may use this process";
    } else {
        $user = Users::withID(filter_input(INPUT_GET, "operator"));
        if (is_object($user)){
            $objbox_array = ObjBox::findObj($user);
            if (is_string($objbox_array)){
                $errorMsg = $objbox_array;
                echo "<script>console.log(\"$errorMsg\");</script>";
            }
        } else {
			echo "<script>console.log(\"$user\");</script>";
		}
    }
} else {
    $errorMsg = "You Must Be Logged In to Pick Up a Print";
}
//We have an error, display the Issue
if ($errorMsg != ""){
    echo "<script> window.onload = function(){goModal('Error', \"$errorMsg\", false)}; //window.location.href='/index.php';}</script>";
    
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $errorMsg == "") {
    $loc = "";
    foreach($objbox_array as $ob){
        if (isset($_POST['pickBtn_'.$ob->getO_id()])){
            echo "<script>console.log('OB Btn: ".$ob->getO_id()."');</script>";
            $status_id = 0;
            $account = 0;
            $ticket = $ob->getTransaction();
            //Make a backup
            $_SESSION['backup_ticket'] = serialize($ticket);
            $_SESSION['backup_ob'] = serialize($ob);
            
            foreach($ticket->getMats_used() as $mu){
                $mu_id = $mu->getMu_id();
                
                $mu_s = $_POST["status_".$mu->getMu_id()];
                $mu->setStaff($staff->getOperator());
                $mu->setUnit_used($_POST["uu_".$mu->getMu_id()]);
                $mu->getStatus()->setStatus_id($mu_s);
                $mu->setMu_notes($_POST['mu_notes_'.$mu->getMu_id()]);
                
                if ($mu_s > $status_id){
                    $status_id = $mu_s;
                }
            }
            
            $ticket->getStatus()->setStatus_id($status_id);
            /*
            if ($status_id == 12 && $errorMsg == ""){
                //$_SESSION['type'] = "failed";
                //Write the state to the DB
                $rtn = $ticket->writeAttr();
                if ($rtn == true){
                    $ob->pickedUpBy($user, $staff);
                    //Display the newly updated Ticket
                    $loc = "/pages/lookup.php?trans_id=".$ob->getTransaction()->getTrans_id();
                }
            } elseif ($status_id == 20 && $errorMsg == ""){
             */
            if ($status_id == 12 or $status_id == 20){
                echo "<script>console.log(\"Quote: ".$ticket->quote()."\");</script>";
                if ($ticket->quote() >= .005){
                    //Pass Object to be updated upon Successful payment
                    $_SESSION['ticket'] = serialize($ticket);
                    //Account to be Charged
                    $_SESSION['pre_select'] = $account;
                    $loc = "/pages/pay.php";
                } else {
                    if ($ticket->writeAttr()){
                        $ob->pickedUpBy($user, $staff);
                        //unset used session variables
                        $loc = "/pages/lookup.php?trans_id=".$ob->getTransaction()->getTrans_id();
                    } else {
                        $errorMsg = "Unable to Update Ticket";
                    }
                }
            } elseif ($errorMsg == ""){
                $errorMsg = "Invalid Status Setting";
            }
            
            if ($errorMsg != ""){
                echo "<script>console.log(\"pickup.php: $errorMsg\");</script>";
                echo "<script> alert('$errorMsg');</script>";
            } else {
               echo "<script>console.log('Destination: $loc');</script>";
               header("Location:".$loc);
            }
        }
    }
}
if ($staff) {
?>
<title><?php echo $sv['site_name'];?> Pick Up</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><i class="fas fa-gift fa-2x"></i> Pick Up 3D Print</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-7">
            <?php if(isset($objbox_array) && is_array($objbox_array)){foreach($objbox_array as $ob){
                $ticket = $ob->getTransaction();
                $mats_used = Mats_Used::byTrans($ob->getTransaction()->getTrans_id());
                ?>
                <form action="" method="post" autocomplete="off" onsubmit="return validateForm_<?php echo $ob->getO_id();?>()">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-ticket-alt"></i> Ticket # <?php echo $ticket->getTrans_id(); ?>
                        <span class="pull-right"><i class="fas fa-map-marker fa-lg" title="Address"></i> <?php echo $ob->getAddress(); ?></span>
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
                            <tr>
                                <td>Status</td>
                                <td><?php echo $ticket->getStatus()->getMsg(); ?></td>
                            </tr>
                            <tr>
                                <td>Address</td>
                                <td><b><?php echo $ob->getAddress();?></b></td>
                            </tr>
                            <?php 
                            foreach ($mats_used as $mu){ ?>
                                <tr>
                                    <td colspan="2">
                                        <table class="table table-bordered"><tbody>
                                            <tr class="tablerow info">
                                                <td><?php echo $mu->getMaterial()->getM_name()?>
                                                    <div class="color-box" style="background-color: #<?php echo $mu->getMaterial()->getColor_hex();?>; float:right;"></div>
                                                </td>
                                                <td>
                                                    <?php if ($mu->getStatus()->getStatus_id() == 20){ ?>
                                                        <i class='<?php echo $sv['currency'];?>'></i> <?php echo $mu->getMaterial()->getPrice(); ?> at <input type="number" name="uu_<?php 
                                                            echo $mu->getMu_id();?>" id="uu_<?php echo $mu->getMu_id();?>" min="0" max="10000" 
                                                            step="1" style="text-align: right" onclick="calc_<?php echo $ob->getO_id() ?>()" 
                                                            onkeyup="calc_<?php echo $ob->getO_id() ?>()" value="<?php echo $mu->getUnit_used();?>" readonly> grams
                                                    <?php } else { ?>
                                                        <i class='<?php echo $sv['currency'];?>'></i> <?php echo $mu->getMaterial()->getPrice(); ?> at <input type="number" name="uu_<?php 
                                                            echo $mu->getMu_id();?>" id="uu_<?php echo $mu->getMu_id();?>" min="0" max="10000" 
                                                            step="1" style="text-align: right" onclick="calc_<?php echo $ob->getO_id() ?>()" 
                                                            onkeyup="calc_<?php echo $ob->getO_id() ?>()" value="<?php echo $mu->getUnit_used();?>"> grams
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Print Status</td>
                                                <td>
                                                    <select name="status_<?php echo $mu->getMu_id();?>" id="status_<?php echo $mu->getMu_id();?>" onchange="calc_<?php echo $ob->getO_id() ?>()" onkeyup="calc_<?php echo $ob->getO_id() ?>()">
                                                        <option value="" selected disabled hidden>Select</option>
                                                        <option value="20">Pick Up</option>
                                                        <option value="12">Failed</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <?php
                                            if (strcmp($mu->getHeader(), "") != 0){ ?>
                                                <tr>
                                                    <td>File Name</td>
                                                    <td><?php echo $mu->getHeader(); ?></td>
                                                </tr>
                                            <?php } ?>
                                            <tr>
                                                <td><i class="fas fa-edit"></i>Notes</td>
                                                <td><textarea name="mu_notes_<?php echo $mu->getMu_id();?>" id="mu_notes_<?php echo $mu->getMu_id();?>" class="form-control"><?php echo $mu->getMu_notes();?></textarea></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td align="right" colspan="2">
                                    <div name="total_<?php echo $ob->getO_id();?>" id="total_<?php echo $ob->getO_id();?>">Total <i class='<?php echo $sv['currency'];?>'></i> 0.00</div>
                                </td>
                            </tr>
                            <tr class="tablefooter">
                                <td align="right" colspan="2"><input type="submit" name="pickBtn_<?php echo $ob->getO_id(); ?>" value="Pick Up" id="submitBtn"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                </form>
            <?php }} else { ?>
                <div class="panel panel-default">
                    <div class="panel-heading" style="color:Tomato">
                        <i class="fas fa-exclamation-triangle" ></i> No Objects in Storage
                    </div>
                    <div class="panel-body">
                        <a href="/pages/lookup.php?operator=<?php echo $user->getOperator(); ?>" title="Click to look up the user's last ticket"><i class="fas fa-link"></i> Goto Last Ticket</a>
                    </div>
                </div>
                    
            <?php } ?>
        </div>
        <!-- /.col-lg-7 -->
        
        <div class="col-lg-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-search fa-2x"></i> Inspect your print
                </div>
                <div class="panel-body">
                    <?php echo $sv['inspectPrint']."\n";?>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-subscript"></i> ObjectBox Stats
                    <div class="pull-right">
                        <button type="button" class="btn btn-default btn-xs dropdown-toggle" aria-expanded="false"
                                data-toggle="collapse" data-target="#obs">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </div>
                <div class="panel-body collapse" id="obs">
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
            <?php //Look for associated charges
            if( isset($ticket) && $ticket->getAc() ){?>
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
        </div>
        <!-- /.col-lg-4 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<script type="text/javascript">
<?php //make a function for each object in storage
if(is_array($objbox_array)){foreach ($objbox_array as $ob) {
    $mats_used = Mats_Used::byTrans($ob->getTransaction()->getTrans_id());
    echo "function calc_".$ob->getO_id()."(){\n\t"; //Declare values
    echo ("var total = 0;\n\t");
    foreach($mats_used as $mu){
        if ($mu->getStatus()->getStatus_id() == 20) {
            echo("//value is already contained in ticket->quote()\n\t");
            echo ("var status_".$mu->getMu_id()." = document.getElementById('status_".$mu->getMu_id()."').value;\n\t");
            echo ("var rate_".$mu->getMu_id()." = ".$mu->getMaterial()->getPrice().";\n\t");
            echo ("var uu_".$mu->getMu_id()." = document.getElementById('uu_".$mu->getMu_id()."').value;\n\t");
            echo ("if (status_".$mu->getMu_id()." == 12)\n\t\t\t");
            //echo ("total -= rate_".$mu->getMu_id()." * uu_".$mu->getMu_id().";\n");
            echo ("total -= rate_".$mu->getMu_id()." * uu_".$mu->getMu_id().";\n");
        } else {
            echo ("var status_".$mu->getMu_id()." = document.getElementById('status_".$mu->getMu_id()."').value;\n\t");
            echo ("var rate_".$mu->getMu_id()." = ".$mu->getMaterial()->getPrice().";\n\t");
            echo ("var uu_".$mu->getMu_id()." = document.getElementById('uu_".$mu->getMu_id()."').value;\n\t");
            echo ("if (status_".$mu->getMu_id()." != 12)\n\t\t\t");
            echo ("total += rate_".$mu->getMu_id()." * uu_".$mu->getMu_id().";\n");
        }
    } ?>
    document.getElementById("total_<?php echo $ob->getO_id();?>").innerHTML = "Total <i class='<?php echo $sv['currency'];?>'></i> " + total.toFixed(2);
}
function validateForm_<?php echo $ob->getO_id() ?>(){
    passive = true;
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
        echo ("\t\t\treturn false;\n\t\t}\n\t}\n");
    }?>
    
    if (pickup && storage){
        alert("You must decide either to Pickup or Move to Storage");
        return false;
    }
}

<?php }} ?>
</script>
<?php
}
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>