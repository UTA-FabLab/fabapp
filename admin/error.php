<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
if (!$staff || $staff->getRoleID() < 10){
    //Not Authorized to see this Page
    header('Location: /index.php');
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['teBtn'])) {
    $id = filter_input(INPUT_POST, 'teField');
    $user = Users::withID($id);
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
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-ticket fa-lg"></i> Title
                </div>
                <div class="panel-body">
                    <table id="errorTable" class="table table-bordered table-striped" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Page</th>
                                <th>Error Message</th>
                                <th>Staff</th>
                            </tr>
                        </thead>
                        <?php
                        foreach ( Error::getErrors() as $er ){
                            echo "<tr>";
                                echo "<td>".$er->getE_time()."</td>";
                                echo "<td>".$er->getPage()."</td>";
                                echo "<td>".$er->getMsg()."</td>";
                                if (is_object($er->getStaff())){
                                    echo "<td> <i class='fa fa-".$er->getStaff()->getIcon()." fa-lg' title='".$er->getStaff()->getOperator()."'></i></td>";
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
        <!-- /.col-md-8 -->
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-book fa-lg"></i> Look Up Completed Trainings
                </div>
                <div class="panel-body">
                    <form name="teForm" method="POST" action="" autocomplete="off" onsubmit="return stdRegEx('teField', /^\d{10}$/, 'Please enter ID #2')">
                        <div class="input-group custom-search-form">
                            <input type="text" name="teField" id="teField" class="form-control" placeholder="Enter ID #" maxlength="10" size="10"
                                   value="<?php if (isset($id)) echo $id; ?>">
                            <span class="input-group-btn">
                            <button class="btn btn-default" type="submit" name="teBtn">
                                <i class="fa fa-search"></i>
                            </button>
                            </span>
                        </div>
                    </form>
                    <?php if(isset($user)){ ?>
                        <table id="teTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th class="col-sm-2">Time</th>
                                    <th class="col-sm-2">Staff</th>
                                    <th class="col-sm-8">Training Module</th>
                                </tr>
                            </thead>
                            <?php
                            $result = $mysqli->query("  SELECT `completed`, `staff_id`, `title`, `tm_desc`
                                                        FROM `tm_enroll`
                                                        LEFT JOIN `trainingmodule`
                                                        ON `tm_enroll`.`tm_id` = `trainingmodule`.`tm_id`
                                                        WHERE `operator` = '".$user->getOperator()."';");
                            while ($row = $result->fetch_assoc()){
                                echo "<tr>";
                                    echo "<td align='center'><i class='fa fa-clock-o fa-lg' title='".date($sv['dateFormat'], strtotime($row['completed']))."'></i></td>";
                                    $staff = Users::withID($row['staff_id']);
                                    if (is_object($staff)){
                                        echo" <td align='center'><i class='fa fa-".$staff->getIcon()." fa-lg' title='".$staff->getOperator()."'></i></td>";
                                    } else {
                                        echo "<td></td>";
                                    }
                                    echo "<td> $row[title]"; ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                                <span class="fa fa-info-circle" title="Desc"></span>
                                            </button>
                                            <ul class="dropdown-menu pull-right" role="menu">
                                                <li style="padding-left: 5px;"><?php echo $row['tm_desc'];?></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    <?php } ?>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-md-4 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<script type="text/javascript" charset="utf-8">
    window.onload = function() {
        $('#errorTable').DataTable({
            "iDisplayLength": 25
        });
        $('#teTable').DataTable({
            searching: false, 
            paging: false});
    };
</script>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>