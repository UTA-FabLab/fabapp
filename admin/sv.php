<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
if (!$staff || $staff->getRoleID() < $sv['editSV']){
    //Not Authorized to see this Page
    header('Location: /index.php');
    $_SESSION['error_msg'] = "Insufficient role level to access, sorry.";
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['Save'])) {
    foreach($_POST as $key => $value){
        if (strpos($key, "value") !== false){
            $str = explode("_", $key);
            $sv_id = $str[1];
            $sv_value = $value;
            break;
        }
    }
    if(preg_match("/^\d+$/",$sv_id)){
        $sv_o = new Site_Variables($sv_id);
        if($sv_o->updateValue($sv_value)){
            $_SESSION['success_msg'] = $sv_o->getName()." has been updated.";
            header("Location: /admin/sv.php");
        } else {
            $_SESSION['error_msg'] = "Unable to edit ".$sv_o->getName();
            header("Location: /admin/sv.php");
        }
    } else {
        echo "<script>window.onload = function(){goModal('Error',\"Invalid Site Varible Key\", false)}</script>";
    }
}
?>
<title><?php echo $sv['site_name'];?> Site Variables</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Site Variables</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-10">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-sliders-h fa-lg"></i>
                    <div class="pull-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <span class="fas fa-info"></span>
                            </button>
                            <ul class="dropdown-menu pull-right" role="menu">
                                <li>
                                    <a>Click on the row to edit</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-bordered table-hover" id="svTable" style="table-layout: fixed; width: 100%">
                        <thead>
                            <tr class="tablerow">
                                <th class="col-sm-2">Key</th>
                                <th class="col-sm-4">Value</th>
                                <th class="col-sm-6">Description</th>
                            </tr>
                        </thead>
                        
                        <?php //Fetch all values in the db to generate a table
                        $sv_array = Site_Variables::getAll();
                        foreach($sv_array as $sva){
                            echo "<tr onclick='edit_sv(".$sva->getId().")'>";
                            echo ("<td>".$sva->getName()."</td>");
                            echo ("<td style='word-wrap: break-word'>".$sva->getValue()."</td>");
                            echo ("<td>".$sva->getNotes()."</td>");
                            echo "</tr>";
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
        <!-- /.col-md-10 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<div id="svModal" class="modal">
</div>
<!-- Modal -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script>
        $('#svTable').DataTable({
        "iDisplayLength": 25,
        "order": []
    });
    
function edit_sv(sv_id){
    if (Number.isInteger(sv_id)){
        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            // code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("svModal").innerHTML = this.responseText;
            }
        };
        xmlhttp.open("GET","sub/svEditModal.php?sv_id=" + sv_id,true);
        xmlhttp.send();
    }
    $('#svModal').modal('show');
}
</script>