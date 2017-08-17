<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$errorMsg = "";
$_SESSION['$pickupID'] = "";

if($staff){
    //Staff members or Higher may use this process
    if($staff->getRoleID() <= 7){
        $errorMsg = "Only staff members may use this process";
    } else {
        $operator = filter_input(INPUT_GET, 'operator', FILTER_VALIDATE_INT);
        $objbox = ObjBox::findObj($operator);
        if (is_string($objbox))
            $errorMsg = $objbox;
    }
} else {
    $errorMsg = "You Must Be Logged In to Pick Up a Print";
}
//We have an error, display the Issue
if ($errorMsg != ""){
    echo "<script> alert('$errorMsg'); window.location.href='/index.php';</script>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $loc = "";
    foreach($objbox as $ob){
        if (isset($_POST['pickBtn_'.$ob->getO_id()])){
            $status_id = 0;
            $account = 0;
            $ticket = new Transactions($ob->getTrans_id());
            $mats_used = Mats_Used::byTrans($ob->getTrans_id());
            //create backups
            $_SESSION['ticket'] = serialize($ticket);
            $_SESSION['mats_used'] = serialize($mats_used);
            $_SESSION['objbox'] = serialize($ob);
            
            foreach($mats_used as $mu){
                $status_id = $_POST["status_".$mu->getMu_id()];
                $status_array = explode("@", $status_id);
                
                $mu->setStaff($staff->getOperator());
                $mu->setUnit_used($_POST["uu_".$mu->getMu_id()]);
                $mu->getStatus()->setStatus_id($status_array[0]);
                $mu->setMu_notes($_POST['mu_notes_'.$mu->getMu_id()]);
		 
                //Print has been marked to be paid to account
                if ($status_array[0] == 20){
                    //check if account is being changed
                    if ($account == 0) {
                        $account = $status_array[1];
                    } elseif ($account != $status_array[1]){
                        $errorMsg = "You Must Select One Account.";
                    }
                }
                
                //Set Highest Status to the transaction
                if ($status_id < $mu->getStatus()->getStatus_id()){
                    $status_id = $mu->getStatus()->getStatus_id();
                }
            }
            
            $ticket->getStatus()->setStatus_id($status_id);
            $ob->setUser($operator);
            
            if ($status_id == 12 && $errorMsg == ""){
                $_SESSION['type'] = "failed";
                //Update the DB
                foreach($mats_used as $mu){
                    $errorMsg = $mu->writeAttr();
                }
                $ticket->writeAttr();
                $ob->pickedUpBy($operator, $staff->getOperator());
                //Display the newly updated Ticket
                $loc = "/pages/lookup.php?trans_id=".$ob->getTrans_id();
            } elseif ($status_id == 20 && $errorMsg == ""){
                $_SESSION['type'] = "pickup";
                //Pass Object to be updated upon Successful payment
                $_SESSION['ticket'] = serialize($ticket);
                $_SESSION['mats_used'] = serialize($mats_used);
                $_SESSION['objbox'] = serialize($ob);
                //Account to be Charged
                $_SESSION['account'] = $account;
                $loc = "/pages/checkout.php";
            } elseif ($status_id == 20 && $errorMsg == ""){
                $errorMsg = "Invalid Status Setting";
            }
            
            if ($errorMsg != ""){
                echo "<script> alert('$errorMsg');</script>";
            } else {
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
            <h1 class="page-header"><i class="fa fa-gift fa-2x"></i> Pick Up 3D Print</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-8">
            <?php foreach($objbox as $ob){
                $ticket = new Transactions($ob->getTrans_id());
                $mats_used = Mats_Used::byTrans($ob->getTrans_id());
                ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-ticket fa-fw"></i> Ticket # <?php echo $ticket->getTrans_id()."<br>"; ?>
                    </div>
                    <div class="panel-body">
                        <table class ="table table-bordered table-striped">
                        <form action="" method="post" autocomplete="off" onsubmit="return validateForm_<?php echo $ob->getO_id();?>()">
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
                            <tr>
                                <td>Status</td>
                                <td><?php echo $ticket->getStatus()->getMsg(); ?></td>
                            </tr>
                            <?php 
                            foreach ($mats_used as $mu){ ?>
                                <tr>
                                    <td><?php echo $mu->getMaterial()->getM_name()
                                            ?><div class="color-box" style="background-color: #<?php echo $mu->getMaterial()->getColor_hex();
                                            ?>; float:right;"></div>
                                    </td>
                                    <td><i class='fa fa-<?php echo $sv['currency'];?> fa-fw'></i> <?php echo $mu->getMaterial()->getPrice(); ?> at <input type="number" name="uu_<?php 
                                        echo $mu->getMu_id();?>" id="uu_<?php echo $mu->getMu_id();?>" min="0" max="10000" 
                                        step="1" style="text-align: right" onchange="calc_<?php echo $ob->getO_id() ?>()" 
                                        onkeyup="calc_<?php echo $ob->getO_id() ?>()" value="<?php echo $mu->getUnit_used();?>"> grams
                                    </td>
                                </tr>
                                <tr>
                                    <td>Print Status</td>
                                    <td>
                                        <select name="status_<?php echo $mu->getMu_id();?>" id="status_<?php echo $mu->getMu_id();?>" onchange="calc_<?php echo $ob->getO_id() ?>()" onkeyup="calc_<?php echo $ob->getO_id() ?>()">
                                            <option value="" selected disabled hidden>Select</option>
                                            <option value="20@2" ><?php echo $sv['paySite_name'];?></option>
                                            <option value="20@4" ><?php echo $sv['interdepartmental'];?></option>
                                            <?php $accounts = $ticket->getUser()->getAccounts();
                                            foreach ($accounts as $accts){
                                                echo ("<option value='20@".$accts->getA_id()."'>".$accts->getName()."</option>\n");
                                            }?>
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
                                    <td><i class="fa fa-pencil-square-o fa-fw"></i>Notes</td>
                                    <td><textarea name="mu_notes_<?php echo $mu->getMu_id();?>" id="mu_notes_<?php echo $mu->getMu_id();?>" class="form-control"><?php echo $mu->getMu_notes();?></textarea></td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td>Cost</td>
                                <td><div name="total_<?php echo $ob->getO_id();?>" id="total_<?php echo $ob->getO_id();?>"><i class='fa fa-<?php echo $sv['currency'];?> fa-fw'></i> 0.00</div></td>
                            </tr>
                            <tr>
                                <td>Placed on Shelf</td>
                                <td><b><?php echo $ob->getAddress();?></b></td>
                            </tr>
                            <tfoot>
                                <td align="center" colspan="2"><input type="submit" name="pickBtn_<?php echo $ob->getO_id(); ?>" value="Pick Up" id="submitBtn"></td>
                            </tfoot>
                        </form>
                        </table>
                    </div>
                </div>
            <?php } ?>
        </div>
        <!-- /.col-lg-8 -->
        
        <div class="col-lg-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-search fa-2x"></i> Inspect your print
                </div>
                <div class="panel-body">
                    <?php echo $sv['inspectPrint']."\n";?>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-subscript fa-fw"></i> Stats
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
        </div>
        <!-- /.col-lg-4 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<script type="text/javascript">
<?php //make a function for each object in storage
foreach ($objbox as $ob) {
    $mats_used = Mats_Used::byTrans($ob->getTrans_id());
    echo "function calc_".$ob->getO_id()."(){\n\t"; //Declare values
     echo ("var total = 0;\n\t");
    foreach($mats_used as $mu){
        echo ("var status_".$mu->getMu_id()." = document.getElementById('status_".$mu->getMu_id()."').value;\n\t");
        echo ("var rate_".$mu->getMu_id()." = ".$mu->getMaterial()->getPrice().";\n\t");
        echo ("var uu_".$mu->getMu_id()." = document.getElementById('uu_".$mu->getMu_id()."').value;\n\t");
        echo ("if (status_".$mu->getMu_id()." != 12)\n\t\t\t");
        echo ("total += rate_".$mu->getMu_id()." * uu_".$mu->getMu_id().";\n");
    } ?>
    document.getElementById("total_<?php echo $ob->getO_id();?>").innerHTML = "<i class='fa fa-<?php echo $sv['currency'];?> fa-fw'></i> " + total.toFixed(2);
}
function validateForm_<?php echo $ob->getO_id() ?>(){
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

<?php } ?>
</script>
<?php
}
    //Standard call for dependencies
    include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>