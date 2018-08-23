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
if ($_SERVER["REQUEST_METHOD"] == "POST"){
	//print new wait tab and advance the number
    if( isset($_POST['print_s']) ){
        $i = $sv['next']+1;
        wait($i);
        updateWait(array (  "serving" => $_POST['serving'],
                            "eServing" => $_POST['eServing'],
                            "bServing" => $_POST['bServing'],
                            "mServing" => $_POST['mServing'],
                            "misc" => $_POST['misc']));
        
    } elseif( isset($_POST['reset']) ){
        updateWait(array ('serving' => 0, 'next' => 0));
		
    } elseif( isset($_POST['print_e']) ){
        $i = $sv['eNext']+1;
        wait("E".$i);
        updateWait(array (  "serving" => $_POST['serving'],
                            "eServing" => $_POST['eServing'],
                            "bServing" => $_POST['bServing'],
                            "mServing" => $_POST['mServing'],
                            "misc" => $_POST['misc']));
        
    } elseif( isset($_POST['eReset']) ){
        updateWait(array ('eServing' => 0, 'eNext' => 0));
		
    } elseif( isset($_POST['print_b']) ){
        $i = $sv['bNext']+1;
        wait("B".$i);
        updateWait(array (  "serving" => $_POST['serving'],
                            "eServing" => $_POST['eServing'],
                            "bServing" => $_POST['bServing'],
                            "mServing" => $_POST['mServing'],
                            "misc" => $_POST['misc']));

    } elseif( isset($_POST['bReset']) ){
        updateWait(array ('bServing' => 0, 'bNext' => 0));
		
    } elseif( isset($_POST['print_m']) ){
        $i = $sv['mNext']+1;
        wait("M".$i);
        updateWait(array (  "serving" => $_POST['serving'],
                            "eServing" => $_POST['eServing'],
                            "bServing" => $_POST['bServing'],
                            "mServing" => $_POST['mServing'],
                            "misc" => $_POST['misc']));
		
    } elseif( isset($_POST['mReset']) ){
        updateWait(array ('mServing' => 0, 'mNext' => 0, "misc" => "Misc"));

    } elseif( isset($_POST['saveBtn']) ){
        updateWait(array (  "serving" => $_POST['serving'],
                            "eServing" => $_POST['eServing'],
                            "bServing" => $_POST['bServing'],
                            "mServing" => $_POST['mServing'],
                            "misc" => $_POST['misc']));
    }
}


function updateWait($pair){
    global $mysqli;
    $str = $value = $key = "";
    
    if ($stmt = $mysqli->prepare("
        UPDATE `site_variables`
        SET value = ?
        WHERE `site_variables`.`name` = ?;
    ")){
        foreach( $pair as $key => $value){
            if (is_int($value)){
                $str = "is";
            } else {
                $str = "ss";
            }
            $stmt->bind_param($str, $value, $key);
            echo "<script>console.log( \"Debug : k:$key - v$value & $str\");</script>";
            if ($stmt->execute() === true ) {
                $row = $stmt->affected_rows;
            } else {
                $_SESSION['error_msg'] = $stmt->error;
                return;
            }
        }
    } else {
        $_SESSION['error_msg'] = $stmt->error;
        return;
    }
    
    $stmt->close();
    header("Location:/admin/now_serving.php");
}
?>
<title><?php echo $sv['site_name'];?> Now Serving</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Now Serving</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <form method="post" action="" onsubmit="return confirm_form()" name="ns_form">
                    <div class="panel-heading">
                        <i class="fas fa-list-ol fa-lg"></i> Now Serving
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped table-bordered">
                            <tr>
                                <td>Equipment</td>
                                <td>Now Serving</td>
                                <td>Next #</td>
                                <td>Action</td>
                            </tr>
                            <tr id="next">
                                <td>PolyPrinter</td>
                                <td align="center"><input type="number" size="1" value="<?php echo $sv['serving']; ?>" min="0" style="width: 3em" name="serving" tabindex="1"/></td>
                                <td align="center"><button class="btn btn-basic" title="Click to issue the next Wait-Tab"
                                            name='print_s'><?php echo $sv['next']+1; ?> <i class="fas fa-print"></button></td>
                                <td align="center"><button class="btn btn-basic" name="reset" onclick="clicked='PolyPrinter'">Reset</button></td>
                            </tr>
                            <tr id="next">
                                <td>Epilog Laser</td>
                                <td align="center">E<input type="number" size="1" value="<?php echo $sv['eServing']; ?>" min="0" style="width: 3em" name="eServing" tabindex="2"/></td>
                                <td align="center"><button class="btn btn-basic" title="Click to issue the next Wait-Tab"
                                            name='print_e'>E<?php echo $sv['eNext']+1; ?> <i class="fas fa-print"></button></td>
                                <td align="center"><button class="btn btn-basic" name="eReset" onclick="clicked='Epilog Laser'">Reset</button></td>
                            </tr>
                            <tr id="next">
                                <td>Boss Laser</td>
                                <td align="center">B<input type="number" size="1" value="<?php echo $sv['bServing']; ?>" min="0" style="width: 3em" name="bServing" tabindex="3"/></td>
                                <td align="center"><button class="btn btn-basic" title="Click to issue the next Wait-Tab"
                                            name='print_b'>B<?php echo $sv['bNext']+1; ?> <i class="fas fa-print"></button></td>
                                <td align="center"><button class="btn btn-basic" name="bReset" onclick="clicked='Boss Laser'">Reset</button></td>
                            </tr>
                            <tr id="next">
                                <td><input type="text" value="<?php echo $sv['misc'];?>" name="misc"/></td>
                                <td align="center">M<input type="number" size="1" value="<?php echo $sv['mServing']; ?>" min="0" style="width: 3em" name="mServing" tabindex="4"/></td>
                                <td align="center"><button class="btn btn-basic" title="Click to issue the next Wait-Tab"
                                            name='print_m'>M<?php echo $sv['mNext']+1; ?> <i class="fas fa-print"> </button></td>
                                <td align="center"><button class="btn btn-basic" name="mReset" onclick="clicked='<?php echo $sv['misc'];?>'">Reset</button></td>
                            </tr>
                        </table>
                    </div>
                    <div class="panel-footer">
                        <div align="right">
                            <button type="submit" class="btn btn-success" name="saveBtn">Save</button>
                        </div>
                    </div>
                </form>
            </div>
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
function confirm_form(){
    loadingModal()
    if (typeof clicked !== 'undefined'){
        if (confirm('Are you sure you want to reset the Wait-Tab for '+ clicked + '?')) {
            return true;
        } else {
            return false;
        }
    }
    return true;
}
$(document).keypress(
    function(event){
     if (event.which == '13') {
        event.preventDefault();
      }


});
</script>