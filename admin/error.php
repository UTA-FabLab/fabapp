<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
if (!$staff || $staff->getRoleID() < $sv['LvlOfStaff']){
    //Not Authorized to see this Page
    header('Location: /index.php');
    $_SESSION['error_msg'] = "Insufficient role level to access, sorry.";
    exit();
}
?>
<title><?php echo $sv['site_name'];?> Error</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">Error Log</h1>
        </div>
        <!-- /.col-md-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-10">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-bolt fa-lg"></i> Error Log
                </div>
                <div class="panel-body">
                    <table id="errorTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th class="col-sm-2">Date</th>
                                <th class="col-sm-2">Page</th>
                                <th class="col-sm-6">Error Message</th>
                                <th class="col-sm-2">Staff</th>
                            </tr>
                        </thead>
                        <?php
                        foreach ( Error::getErrors() as $er ){
                            echo "<tr>";
                                echo "<td>".$er->getE_time()."</td>";
                                echo "<td>".$er->getPage()."</td>";
                                echo "<td>".$er->getMsg()."</td>";
                                if (is_object($er->getStaff())){
                                    echo "<td> <i class='".$er->getStaff()->getIcon()." fa-lg' title='".$er->getStaff()->getOperator()."'></i></td>";
                                } else {
                                    echo "<td></td>";
                                }
                            echo "</tr>";    
                        } ?>
                        <tfoot>
                            <tr>
                                <th>Date</th>
                                <th>Page</th>
                                <th>Error Message</th>
                                <th>Staff</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <!-- /.col-lg-10 -->
        <div class="col-lg-2">
        </div>
        <!-- /.col-lg-2 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript" charset="utf-8">
    $('#errorTable').DataTable({
        "iDisplayLength": 25,
        "order": [[ 0, "desc" ]]
    });
</script>