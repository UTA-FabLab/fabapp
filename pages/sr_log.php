<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
$sl_id = $sr_notes = $notes = "";

if (!isset($staff) || ($staff->getRoleID() < $sv['LvlOfStaff'] && $staff->getRoleID() != $sv['serviceTechnican'])) {
    //Not Authorized to see this Page
    $_SESSION['error_msg'] = "Not Authorized to view this page";
    header('Location: /index.php');
}

if ( !empty(filter_input(INPUT_GET, "sc_id")) ) {
    $sc = new Service_call(filter_input(INPUT_GET, 'sc_id'));
    $sl_id = $sc->getSl()->getSl_id();
} else {
    $_SESSION['error_msg'] = "Invalid Service Issue Number";
    header('Location: /index.php');
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['srBtn']) && 
        ($staff->getRoleID() >= $sv['staffTechnican'] || $staff->getRoleID() == $sv['serviceTechnican'])){
    $device_status = filter_input(INPUT_POST, 'status');
    $sl_id = filter_input(INPUT_POST, 'sl_id');
    $sr_notes = filter_input(INPUT_POST, 'sr_notes');
    $msg = $sc->insert_reply($staff, $device_status, $sl_id, $sr_notes);
    if (is_string($msg)){
        //display error message
        $errorMsg = $msg;
    } else {
        header("Location:sr_log.php?sc_id=".$sc->getSc_id());
    }
} elseif ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['editSC']) && 
        $sc->getStaff()->getOperator() == $staff->getOperator()) {
    $notes = filter_input(INPUT_POST, 'notes');
    $sc->updateNotes($notes);
    header("Location:sr_log.php?sc_id=".$sc->getSc_id());
}
?>
<title><?php echo $sv['site_name'];?> Service Replies</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Reply to Service Issue for <?php echo $sc->getDevice()->getDevice_desc(); ?></h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-5">
            <?php if (isset($errorMsg)) { ?>
                <div class="alert alert-danger" role = "alert" id="errordiv">
                    <?php echo $errorMsg; ?>
                </div>
            <?php } ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    Overall Status : <?php Devices::printDot($staff, $sc->getDevice()->getD_id()); echo $sc->getDevice()->getDevice_desc(); ?>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-fire fa-fw"></i> Reported Issue
                </div>
                <div class="panel-body">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <td class="col-sm-3">Issue Status</td>
                            <td class="col-sm-9"><?php if ($sc->getSolved() == "Y") echo "Complete"; else echo "Incomplete"; ?></td>
                        </tr>
                        <tr>
                            <td>Service Level</td>
                            <td><?php Service_lvl::getDot($sc->getSl()->getSl_id()); echo $sc->getSl()->getMsg()?></td>
                        </tr>
                        <tr>
                            <td>Staff</td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                        <i class="<?php echo $sc->getStaff()->getIcon();?> fa-lg" title="<?php echo $sc->getStaff()->getOperator();?>"></i>
                                    </button>
                                    <ul class="dropdown-menu" role="menu">
                                        <li style="padding-left: 5px;"><?php echo $sc->getStaff()->getOperator();?></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Notes</td>
                            <td><?php echo $sc->getSc_notes(); ?></td>
                        </tr>
                    </table>
                </div>
                <?php if ($sc->getStaff()->getOperator() == $staff->getOperator()) { ?>
                <div class='panel-footer clearfix'>
                    <button class='btn btn-primary pull-right' onclick='editSR()'>Edit</button>
                </div>
                <?php } ?>
            </div>
        </div>
        <!-- /.col-md-5 -->
        <div class="col-md-6">
            <?php if ($staff->getRoleID() >= $sv['staffTechnican'] || $staff->getRoleID() == $sv['serviceTechnican']){ ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="far fa-comment fa-fw"></i> Reply to Issue
                    </div>
                    <div class="panel-body">
                        <form name="reply" method= "POST" action="" onsubmit="return validateReply();">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <td class="col-sm-3">
                                    Issue Status
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                            <i class="fas fa-info fa-lg" title="Read Me"></i>
                                        </button>
                                        <ul class="dropdown-menu" role="menu">
                                            <li style="padding-left: 5px;">Mark Complete if the reported issue has been resolved.</li>
                                        </ul>
                                    </div>
                                </td>
                                <td class="col-sm-9">
                                    <input type="radio" name="status" value="complete" id="complete" onchange="radioBtn(this)"><label for="complete">&ensp; Complete</label><br>
                                    <input type="radio" name="status" value="incomplete" id="incomplete" onchange="radioBtn(this)"><label for="incomplete">&ensp; Incomplete</label>
                                </td>
                            <tr>
                                <td>
                                    Change Service Level
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                            <i class="fas fa-info fa-lg" title="Read Me"></i>
                                        </button>
                                        <ul class="dropdown-menu" role="menu">
                                            <li style="padding-left: 5px;">Increase or decrease the severity of the issue.</li>
                                        </ul>
                                    </div>
                                </td>
                                <td>
                                    <select class="form-control" name="sl_id" id="sl_id" onchange="change_sldot()" disabled>
                                        <option value = "" hidden> <i class="fas fa-fire"/>Select</option>
                                        <?php //List available Service Levels
                                        $slArray = Service_lvl::getList();
                                        foreach ($slArray as $sl){
                                            if ($sl_id == $sl->getSl_id()){
                                                echo("<option value='".$sl->getSl_id()."' selected>".$sl->getMsg()."</option>");
                                            } else {
                                                echo("<option value='".$sl->getSl_id()."'>".$sl->getMsg()."</option>");
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Notes:</td>
                                <td><div class="form-group">
                                <textarea class="form-control" rows="5" name="sr_notes"  id="sr_notes" style="resize: none"><?php echo $sr_notes; ?></textarea>
                                </div></td>
                            </tr>
                        </table>
                    </div>
                    <div class="panel-footer clearfix">
                        <div class="pull-right"><input class="btn btn-primary" type="submit" name="srBtn" value="Submit"></div>
                    </div></form>
                </div>
                <!-- /.panel -->
            <?php } ?>
        </div>
        <!-- /.col-md-6 -->
    </div>
    <!-- /.row -->
    <?php if (count ($sc->getSR()) > 0) { ?>
        <div class="row">
            <div class="col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="far fa-comment fa-fw"></i> Previous Issue Replies
                    </div>
                    <div class="panel-body">
                        <table id='replies' class="table table-striped table-bordered" border='1'>
                            <thead><tr>
                                <th>On</th>
                                <th>By</th>
                                <th>Service Reply Notes</th>
                            </thead></tr>
                            <?php foreach ($sc->getSR() as $sr){ ?>
                                <tr>
                                    <td><?php echo $sr->getSr_time();?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                <i class="<?php echo $sr->getStaff()->getIcon();?> fa-lg" title="<?php echo $sr->getStaff()->getOperator();?>"></i>
                                            </button>
                                            <ul class="dropdown-menu" role="menu">
                                                <li style="padding-left: 5px;"><?php echo $sr->getStaff()->getOperator();?></li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td><?php echo $sr->getSr_notes(); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody></table>
                       </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
            </div>
            <!-- /.col-md-8 -->
        </div>
    <?php } ?>
</div>
<!-- /#page-wrapper -->
<div id="editModal" class="modal">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Edit Notes of the Issue</h4>
            </div>
            <form name="aaaForm" method="post" action="" onsubmit="return validateEdit()">
            <div class="modal-body">
                <textarea class="form-control" id="notes" rows="5" name="notes"
                    style="resize: none"><?php if ($notes == "") {echo $sc->getSc_notes();} else {echo $notes;} ?></textarea>
            </div>
            <div class="modal-footer">
                  <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary" name="editSC">Save</button>
            </div> 
            </form>
        </div>
    </div>
</div>
<!-- Modal -->
<script type="text/javascript">
function change_sldot(){
    var sl_id = document.getElementById("sl_id").value;
    var sl_dot = document.getElementById("sl_dot");
    if(sl_id == 1) {
        sl_dot.style.color = "green";
        sl_dot.classList.remove("fa-times");
        sl_dot.classList.add("fa-circle");
    } else if(sl_id < 7) {
        sl_dot.style.color = "yellow";
        sl_dot.classList.remove("fa-times");
        sl_dot.classList.add("fa-circle");
    } else {
        //7+ is Non-useable
        sl_dot.style.color = "red";
        sl_dot.classList.add("fa-times");
    }
}

function editSR(){
    //toggle modal
    $("#editModal").modal();
}

function radioBtn(ele) {
    var selectEle = document.getElementById("sl_id");
    if (ele.value == "complete"){
        selectEle.disabled = true;
        var options = selectEle.options;
        // Find default selected option
        for (var i=0, iLen=options.length; i<iLen; i++) {

            if (options[i].defaultSelected) {
                selectEle.selectedIndex = i;
                change_sldot();
                return;
            }
        }
    } else {
        selectEle.disabled = false;
    }
}

function validateReply(){
    
    if(!$('input[name=status]:checked').val()){
        alert("Select Status of Issue");
        return false;
    }
    
    if (!stdRegEx("sr_notes", /^.{10,}/, "Please Write a Reply to the Issue")){
        return false;
    }
    if (!stdRegEx("sl_id", /^\d+/, "Select the Severity of the Issue")){
        return false;
    }
    //remove disabled to allow submission
    document.getElementById("sl_id").disabled = false;
}

function validateEdit(){
    if (!stdRegEx("notes", /^.{10,}/, "Please describe the issue with this device.")){
        return false;
    }
}

window.onload = function() {
    $('#replies').DataTable();
};
</script>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
