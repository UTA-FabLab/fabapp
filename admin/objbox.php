<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
if (!$staff || $staff->getRoleID() < $sv['LvlOfStaff']){
    //Not Authorized to see this Page
    $_SESSION['error_msg'] = "You are unable to view this page.";
    header('Location: /index.php');
    exit();
}
?>
<title><?php echo $sv['site_name'];?> ObjBox</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">Objects in Storage</h1>
        </div>
        <!-- /.col-md-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-gift fa-fw"></i>
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-striped" id="objTable">
                        <thead>
                            <th>Ticket</th>
                            <th>Location</th>
                            <th>Date</th>
                            <th>Operator</th>
                        </thead>
                        <?php if($result = $mysqli->query("
                            SELECT `transactions`.`trans_id`, `objbox`.`address`, `objbox`.`o_start`, `transactions`.`operator`
                            FROM `objbox`
                            JOIN `transactions`
                            ON `objbox`.`trans_id` = `transactions`.`trans_id`
                            WHERE `objbox`.`o_end` IS NULL;
                        ")){
                            while($row = $result->fetch_assoc()){
                                $user = Users::withID($row['operator']);
                                echo("<tr>");
                                    echo("<td><a href=\"/pages/lookup.php?trans_id=$row[trans_id]\">$row[trans_id]</a></td>");
                                    echo("<td>$row[address]</td>");
                                    echo("<td>$row[o_start]</td>"); ?>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                <i class="<?php echo $user->getIcon();?> fa-lg" title="<?php echo $user->getOperator();?>"></i>
                                            </button>
                                            <ul class="dropdown-menu" role="menu">
                                                <li style="padding-left: 5px;"><?php echo $user->getOperator();?></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php }
                        }?>
                    </table>
                </div>
            </div>
        </div>
        <!-- /.col-md-8 -->
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="far fa-file-excel fa-fw"></i> Generate File
                </div>
                <div class="panel-body">
                    <button class="btn btn-primary" disabled="true">Download CSV</button>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
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
        <!-- /.col-md-4 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script>
    $('#objTable').DataTable({
        "iDisplayLength": 25,
        "order": []
    });
</script>