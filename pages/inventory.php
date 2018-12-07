<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.9
 */

$LvlOfInventory = 9;  //TODO: find out actual level; change for all

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

// change inventory
if($_SERVER["REQUEST_METHOD"] == "POST" && $staff->getRoleID() >= $LvlOfInventory && isset($_POST['save_material'])) {
    $m_id = filter_input(INPUT_POST, 'm_id');
    $quantity = filter_input(INPUT_POST, 'quantity');
    $mat = new Materials($m_id);
    $original_quantity = Materials::units_in_system($m_id);  // used only as reference incase mistakenly changed

    if(Materials::update_material_quantity($m_id, $quantity, $staff)) {
        $_SESSION['success_msg'] = $mat->getM_name()." updated from ".$original_quantity." to ".$quantity;
    } else {
        $_SESSION['error_msg'] = "Unable to update ".$mat->getM_name();
    }
    header("Location:inventory.php");
} 


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
                            SELECT `materials`.`m_id` as `m_id`, `m_name`, SUM(unit_used) as `sum`, `color_hex`, `unit`
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
                                    <?php if($staff && $staff->getRoleID() >= $LvlOfInventory) {
                                        echo "<td onclick='edit_materials(".$row['m_id'].")'>".number_format($row['sum'])." ".$row['unit']."</td>";
                                    } else {
                                        echo "<td>".number_format($row['sum'])." ".$row['unit']."</td>";
                                    } ?>
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

<div id='material_modal' class='modal'> 
</div>

<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>

<script>
    $('#invTable').DataTable({
        "iDisplayLength": 25,
        "order": []
    });

    function edit_materials(m_id){
        if (Number.isInteger(m_id)){
            if (window.XMLHttpRequest) {
                // code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp = new XMLHttpRequest();
            } else {
                // code for IE6, IE5
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("material_modal").innerHTML = this.responseText;
                }
            };
            xmlhttp.open("GET", "sub/edit_materials.php?m_id=" + m_id, true);
            xmlhttp.send();
        }

        $('#material_modal').modal('show');
    }


</script>