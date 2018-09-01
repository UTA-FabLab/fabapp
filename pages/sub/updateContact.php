<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');


?>
<title><?php echo $sv['site_name'];?> Update Contact Info</title>
<div id="page-wrapper">
    <?php $operator = ($_REQUEST['operator']); ?>
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Update Contact Info</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="far fa-bell" aria-hidden="true"></i> Update Contact Info : <?php echo $operator?>
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped table-hover"><form name="wqform" id="wqform" autocomplete="off" method="POST" action="">
                    <?php    if ($result = $mysqli->query("
                                SELECT WQ.`Op_email` , WQ.`Op_phone`
                                FROM `wait_queue` WQ
                                WHERE WQ.`Operator`=$operator AND WQ.`valid`='Y';
                                ")) { 
                            while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td><b href="#" data-toggle="tooltip" data-placement="top" title="email contact information">Email Address: </b></td>
                                <td><input type="text" name="op-email" id="op-email" class="form-control" value="<?php echo $row["Op_email"]?>" placeholder="example@mail.com" maxlength="100" size="10"/></td>
                            </tr>
                            <tr>
                                <td><b href="#" data-toggle="tooltip" data-placement="top" title="phone contact information">Phone Number: </b></td>
                                <td><input type="text" name="op-phone" id="op-phone" class="form-control" value="<?php echo $row["Op_phone"]?>" placeholder="1234567890" maxlength="10" size="10"/></td>
                            </tr>
                            <?php }
                            }
                            else {
				                die ("There was an error loading.");
				            }?>
                            <tr>
                                    <div class="form-group">
                                        <?php
                                                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitBtn'])) {
                                                    $em = filter_input(INPUT_POST,'op-email');
                                                    $ph = filter_input(INPUT_POST, 'op-phone');
                                                    $status = Wait_queue::updateContactInfo($operator, $ph, $em);
                                                    if ($status == 0) {
                                                    header("Location:/index.php");
                                                    }
                                                }
                                        ?>
                                    </div>
                            </tr>
                            <tfoot>
                                <tr>
                                    <td colspan="2"><div class="pull-right"><input type="submit" name="submitBtn" value="Submit"></div></td>
                                </tr>
                            </tfoot>
                        </form>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel -->
        </div>
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->

<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
    
?>

<script type="text/javascript">
    
</script>