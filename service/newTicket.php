<?php
/*
 * CC BY-NC-AS UTA FabLab 2016-2017
 * FabApp V 0.9
 */
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/pages/header.php');

if ($staff && $staff->getRoleID () < 7) {
    // Not Authorized to see this Page
    header ( 'Location: /index.php' );
}
?>
<title><?php echo $sv['site_name'];?> Report Issue</title>
<body>
    <div id="page-wrapper">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Report Issue</h1>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <!-- /.row -->
        <div class="row">
            <div class="col-lg-10">
                <div class="alert alert-danger" role = "alert" id="errordiv" style="display:none;">
                    <p id="errormessage"></p>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-ticket fa-fw"></i> New Ticket
                    </div>
                    <form name="scform" method= "POST"  action="/service/insertSC.php" onsubmit="return validateForm();">
                        <table class="table table-striped">
                            <tr>
                                <td>Device Group</td>
                                <td>
                                    <select class="form-control" name="devGrp" id="devGrp" onChange="change_group()" >
                                        <option value="" > Select Group</option>
                                        <?php
                                            if (! $result = $mysqli->query ( "SELECT `dg_id`, `dg_desc` FROM `device_group` ORDER BY `dg_name` ASC" )) {
                                            	die("There was an error loading device_group ");
                                            }
                                            
                                            while ( $rows = mysqli_fetch_array ( $result ) ) {
                                            	$public_devices = $mysqli->query ( "SELECT * FROM `devices` WHERE `dg_id` = '$rows[dg_id]'");
                                            	if($public_devices->num_rows > 0)
                                                    echo "<option value=" . $rows ['dg_id'] . ">" . $rows ['dg_desc'] . "</option>";
                                            }
                                            ?> 
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Device</td>
                                <td>
                                    <select class="form-control" name="deviceList" id="deviceList">
                                        <option value =""> Select Group First</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Service Level</td>
                                <td>
                                    <?php
                                        if (! $result = $mysqli->query ( "SELECT * FROM service_lvl" )) {
                                            die("There was an error loading device_group ");
                                        }
                                        
                                        while ( $rows = mysqli_fetch_array ( $result ) ) {
                                            echo '<label class="radio-inline">';
                                            echo '<input type="radio" name="optradio" value="'.$rows["sl_id"].'">'.$rows["msg"].'        ';
                                            echo '</label>';
                                        }
                                        ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Notes:</td>
                                <td>
                                    <div class="form-group">
                                        <textarea class="form-control" id="notes" rows="5" name="notes"
                                            style="resize: none"></textarea>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Staff ID</td>
                                <td><?php echo $staff->getOperator();?></td>
                            </tr>
                            <tr>
                                <td>Current Date</td>
                                <td><?php echo $date = date("m/d/Y h:i a", time());?></td>
                            </tr>
                            <tr>
                                <td><input class="btn btn-primary pull-right" type="reset"
                                    value="Reset"></td>
                                <td><input class="btn btn-primary" type="submit" value="Submit"></td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>
        </div>
        <!-- /.col-lg-8 -->
    </div>
    <!-- /.row -->
    <!-- /#page-wrapper -->
</body>
<script type="text/javascript">
    function validateRadio(radios){
    	for(i = 0; i< radios.length ; ++i){
            if(radios[i].checked){
                return true;
            }
    	}
    	return false;
    }
    
    
    function validateForm(){
    	var dg = document.getElementById("devGrp").value;
    	var dev= document.getElementById("deviceList").value;
    	var notes = document.getElementById("notes").value;
    	var radiocheck= false;
        
    	if(validateRadio(document.forms["scform"]["optradio"])){
            radiocheck = true;
    	}
    
    	if(dg == "" || dev == "" ||  notes =="" || radiocheck == false){
            document.getElementById('errordiv').style.display = 'block';
            document.getElementById("errormessage").innerHTML = "All fields are required";
            return false;
    	}
    
    
    	
    }
    
    function change_group(){
        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            // code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("deviceList").innerHTML = this.responseText;
            }
        };
        xmlhttp.open("GET","/service/getdevicelist.php?dg_id="+ document.getElementById("devGrp").value, true);
        xmlhttp.send();
    }
</script>
<?php
// Standard call for dependencies
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/pages/footer.php');
?>