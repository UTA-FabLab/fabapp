<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$errorMsg = "";

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

if ($errorMsg != ""){
    echo $errorMsg;
    //echo "<script> alert('$errorMsg'); window.location.href='/index.php';</script>";
}
?>
<title>FabLab Pick Up</title>
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
                $mats_used = Mats_Used::byTrans($ob->getTrans_id())?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-ticket fa-fw"></i> Ticket # <?php echo $ticket->getTrans_id()."<br>"; ?>
                    </div>
                    <div class="panel-body">
                        <table class ="table table-bordered table-striped"><form>
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
                                    <td><?php echo $mu->getMaterial()->getM_name()?><div class="color-box" style="background-color: #<?php echo $mu->getMaterial()->getColor_hex();?>; float:right;"></div>
                                    </td>
                                    <td>$<?php echo $mu->getMaterial()->getPrice(); ?> at <input type="number" name="uu_<?php 
											echo $mu->getMu_id();?>" id="uu_<?php echo $mu->getMu_id();?>" min="0" max="10000" 
											step="1" style="text-align: right" onchange="calc_<?php echo $ob->getO_id() ?>()" 
											onkeyup="calc_<?php echo $ob->getO_id() ?>()" value="<?php echo $mu->getUnit_used();?>"> grams
                                </tr>
                                <tr>
                                    <td>Print Status</td>
                                    <td>
                                        <select name="status_<?php echo $mu->getMu_id();?>" id="status_<?php echo $mu->getMu_id();?>"
                                                onchange="calc_<?php echo $ob->getO_id() ?>()" onkeyup="calc_<?php echo $ob->getO_id() ?>()">
                                            <option value="" selected disabled hidden>Select</option>
                                            <option value="20" >Pay</option>
                                            <?php $accounts = $ticket->getUser()->getAccounts();
                                            foreach ($accounts as $accts){
                                                echo ("<option value='20@".$accts->getA_id()."'>".$accts->getName()."</option>");
                                            }?>
                                            <option value="12">Failed</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td>Cost</td>
                                <td><div name="total_<?php echo $ob->getO_id();?>" id="total_<?php echo $ob->getO_id();?>">$ 0.00</div></td>
                            </tr>
                            <tr>
                                <td>Placed on Shelf</td>
                                <td><b><?php echo $ob->getAddress();?></b></td>
                            </tr>
                            <tfoot>
                                <td align="center" colspan="2"><input type="submit" name="submit" value="Pick Up" id="submitBtn"></td>
                            </tfoot>
			</form></table>
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
                    <?php echo $sv['inspectPrint'];?>
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
    $mats_used = Mats_Used::byTrans($ob->getTrans_id())?>
    function calc_<?php echo $ob->getO_id() ?>(){ <?php //Declare values
        echo ("\n\t\tvar total = 0\n\t\t");
        foreach($mats_used as $mu){
            echo ("var status_".$mu->getMu_id()." = document.getElementById('status_".$mu->getMu_id()."').value;\n\t\t");
            echo ("var rate_".$mu->getMu_id()." = ".$mu->getMaterial()->getPrice().";\n\t\t");
            echo ("var uu_".$mu->getMu_id()." = document.getElementById('uu_".$mu->getMu_id()."').value;\n\t\t");
            echo ("if (status_".$mu->getMu_id()." != 12)\n\t\t\t");
            echo ("total += rate_".$mu->getMu_id()." * uu_".$mu->getMu_id().";\n\t\t");
        } ?>
        document.getElementById("total_<?php echo $ob->getO_id();?>").innerHTML = "$ " + total.toFixed(2);
    }
<?php } ?>
</script>
<?php
    //Standard call for dependencies
    include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>