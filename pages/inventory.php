<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.9
 */

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

?>
<title><?php echo $sv['site_name'];?> Inventory</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">Inventory</h1>
        </div>
        <!-- /.col-md-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-warehouse fa-fw"></i> Inventory
                </div>
                <div class="panel-body">
                    <table class="table table-condensed" id="invTable">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th><i class="fas fa-paint-brush fa-fw"></i></th>
                                <th>Qty on Hand</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php //Display Inventory Based on device group
                        if($result = $mysqli->query("
                            SELECT `m_name`, SUM(unit_used) as `sum`, `color_hex`, `unit`
                            FROM `materials`
                            LEFT JOIN `mats_used`
                            ON mats_used.m_id = `materials`.`m_id`
                            WHERE `materials`.`measurable` = 'Y'
                            GROUP BY `m_name`, `color_hex`, `unit`
                            ORDER BY `m_name` ASC;
                        ")){
                            while ($row = $result->fetch_assoc()){ ?>
                                <tr>
                                    <td><?php echo $row['m_name']; ?></td>
                                    <td><div class="color-box" style="background-color: #<?php echo $row['color_hex'];?>;"/></td>
                                    <td><?php echo number_format($row['sum'])." ".$row['unit']; ?></td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr><td colspan="3">None</td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-md-8 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script>
$('#invTable').DataTable({
    "iDisplayLength": 25,
    "order": []
});
</script>