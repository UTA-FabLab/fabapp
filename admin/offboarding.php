<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
 //This will import all of the CSS and HTML code necessary to build the basic page
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

//Submit results
$resultStr = "";
if (!$user || !$user->validate($ROLE["admin"]))
{
    //Not Authorized to see this Page
    header('Location: /index.php');
    $_SESSION['error_msg'] = "Insufficient role level to access, You must be an admin.</a>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ( isset($_POST['offBoardBtn']) ){
        $operator = filter_input(INPUT_POST, "operators");
        $offboard_result = Users::offboarding($operator);
        
        if ($offboard_result == 1){
            $resultStr = "Successfully offboarded user: ".$operator;
        } 
        else {
            echo "<script>window.onload = function(){goModal('Error',\"Error offboarding user.\", false)}</script>";
        }
        
        
    }
}
?>

<html>
<head>
    <title>FabApp - OffBoarding</title>
    <link href="\vendor\iconpicker\css\fontawesome-iconpicker.min.css" rel="stylesheet">
</head>
<body>
    <div id="page-wrapper">

        <!-- Page Title -->
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">FabApp OffBoarding</h1>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <!-- /.row -->

        <?php if ($resultStr != ""){ ?>
            <div class="alert alert-success">
                <?php echo $resultStr; ?>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading" style="background-color: #B5E6E6;">
                        <i class="fas fa-user-times"></i> OffBoarding
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped table-hover">
                            <form method="POST" action="" id="myForm" autocomplete='off' onsubmit="return validateID()">
                                <tr>
                                    <td>
                                        <b data-toggle="tooltip" data-placement="top" title="Select Variant">Operator ID:</b>
                                    </td>
                                    <td>
                                        <div class="col-md-6">
                                        <select class="form-control" name="u_r_id" id="u_r_id" onchange="change_operator()" tabindex="1">
                                            <option value="" disabled selected>Select Role</option>
                                            <?php
                                                $result = $mysqli->query("
                                                SELECT DISTINCT `role`.`r_id` , `role`.`title` 
                                                FROM `role` , `users`
                                                WHERE `users`.`r_id` = `role`.`r_id` AND `users`.`r_id` <= $user->r_id AND `users`.`r_id` > 2
                                                ORDER BY `r_id` DESC;");
                                                while($row = $result->fetch_assoc()){
                                                    echo "<option value=\"$row[r_id]\">$row[title]</option>";
                                                }
                                            ?>
                                        </select>
                                        </div>


                                        <div class="col-md-6">
                                        <select class="form-control" name="operators" id="operators" tabindex="1">
                                            <option value =""> Select Role First</option>
                                        </select>   
                                        </div>
                                    </td>
                                </tr>
                                <tfoot>
                                    <tr>
                                        <td colspan="2">
                                            <div class="pull-right">
                                                <button type="submit" name="offBoardBtn" class="btn btn-warning" style="background-color: #FF7171;" onclick="return Submitter()">OffBoard User</button>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </form>
                        </table>
                    </div>
                    <!-- /.panel-body -->
                </div>
                <!-- /.panel --> 
            </div>
            <!-- /.col-md-12 -->
        </div>
        <!-- /.row -->
    </div>
</body>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script src="\vendor\iconpicker\js\fontawesome-iconpicker.js"></script>
<script type="text/javascript">
    
function Submitter(){

    if (confirm("You are about to submit this query. Click OK to continue or CANCEL to quit.")){
        return true;
    }
    return false;
} 
    
function validateID(){
    if (stdRegEx("u_r_id", /^\d{1,2}$/, "Select a Role") === false){
        return false;
    }
    if (stdRegEx("operators", /<?php echo $sv['regexUser'];?>/, "Invalid Operator ID #") === false){
        return false;
    }
}
    
function change_operator(){
    if (window.XMLHttpRequest) {
        // code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    } else {
        // code for IE6, IE5
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("operators").innerHTML = this.responseText;
        }
    };

    xmlhttp.open("GET","/admin/sub/ob_getOperators.php?val="+ document.getElementById("u_r_id").value, true);
    xmlhttp.send();
    inUseCheck();
}
    
</script>
</html>
