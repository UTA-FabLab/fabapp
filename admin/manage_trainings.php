<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

if (!$staff || $staff->getRoleID() < $sv['LvlOfStaff']){
    //Not Authorized to see this Page
    header('Location: /index.php');
    $_SESSION['error_msg'] = "Insufficient role level to access, You must be a Trainer.";
}

if($_SESSION['type'] == 'success'){
    //fire off modal & timer
    echo "<script type='text/javascript'> window.onload = function(){success()}</script>";
}

//add or edit html argument
if ( !empty($_GET['add']) && $staff){
    //declare empty fields
    $title = $tm_desc = $duration = $tm_required = $file_name = $class_size = $device = $tm_stamp = "";
    $input = explode("=", $_GET['add']);
    if( preg_match('(dg_id)', $input[0]) ){
        if($result = $mysqli->query("
            SELECT *
            FROM `device_group`
            WHERE `dg_id` = '$input[1]'
        ")){
            $row = $result->fetch_assoc();
            $device = $row['dg_desc'];

            $_SESSION['type'] = 'input_add';
        } else {
            $_SESSION['type'] = 'ERROR';
        }
    } elseif( preg_match('(d_id)', $input[0]) ) {
        if($result = $mysqli->query("
            SELECT *
            FROM `devices`
            WHERE `d_id` = '$input[1]'
        ")){
            $row = $result->fetch_assoc();
            $device = $row['device_desc'];

            $_SESSION['type'] = 'input_add';
        } else {
            $_SESSION['type'] = 'ERROR';
        }
    } else {
        $_SESSION['type'] = 'ERROR';
    }

} elseif ( !empty($_GET['edit']) && $staff ){
    $_SESSION['type'] = 'input_edit';
	
    //verify if the input is all numbers
    if ( !preg_match("/^\d+$/", $_GET['edit']) ){
        $_SESSION['type'] = 'error';
    } else {
        $tm_id = $_GET['edit'];
    }

    //populate fields
    if( $result = $mysqli->query("
        SELECT *
        FROM `trainingmodule`
        LEFT JOIN `devices`
        ON `devices`.`d_id` = `trainingmodule`.`d_id`
        LEFT JOIN `device_group`
        ON `device_group`.`dg_id` = `trainingmodule`.`dg_id`
        WHERE `tm_id` = $tm_id
        LIMIT 1;
    ")){
        $row = $result->fetch_assoc();
        $title = $row['title'];
        $tm_desc = $row['tm_desc'];
        $duration = $row['duration'];
        $d_id = $row['d_id'];
        $dg_id = $row['dg_id'];
        $tm_required = $row['tm_required'];
        $file_name = $row['file_name'];
        $class_size = $row['class_size'];
        $tm_stamp = $row['tm_stamp'];
        
        //dg or d description
        if ($row['device_desc']){
            $device = $row['device_desc'];
        } else {
            $device = $row['dg_desc'];
        }
    } else {
        echo $mysqli->error;
        $_SESSION['type'] = 'error';
    }
} else {
    //no arugements passed through the url
    $_SESSION['type'] = 'home';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_SESSION['type'] == 'input_add' && !empty($_GET['add'])) {
        $title = $_POST['title'];
        $tm_desc = $_POST['tm_desc'];
        $duration = $_POST["hours"].":".$_POST["minutes"].":00";
        $tm_required = $_POST['tm_required'];
        $class_size = $_POST['class_size'];
        $d_id = $dg_id = "";

        if( preg_match('(dg_id)', $input[0]) ){
            $dg_id = $input[1];
        } elseif( preg_match('(d_id)', $input[0]) ) {
            $d_id = $input[1];
        }
        
        $result = TrainingModule::insertTM($title, $tm_desc, $duration, $d_id, $dg_id, $tm_required, $class_size, $staff);
        if (is_int($result)){
            echo "<script> alert('insert id is $result')</script>";
            $_SESSION['type'] = 'success';
            header("Location:manage_trainings.php?edit=$result");
            exit();
        } else {
            //error detected
           echo "<script> alert(\"Invalid Input - $result\")</script>";
        }
        
    } elseif ($_SESSION['type'] == 'input_edit' && !empty($_GET['edit'])){
        $title = $_POST['title'];
        $tm_desc = $_POST['tm_desc'];
        $duration = $_POST["hours"].":".$_POST["minutes"].":00";
        $tm_required = $_POST['tm_required'];
        $class_size = $_POST['class_size'];
        
        $result = TrainingModule::editTM($tm_id, $title, $tm_desc, $duration, $d_id, $dg_id, $tm_required, $class_size, $staff);
        if (is_int($result)){
            if ($result == 1){
                echo "<script> alert('insert id is $result')</script>";
                header("Location:manage_trainings.php?edit=$tm_id");
                $_SESSION['type'] = 'success';
            } elseif($result == 0){
                echo "<script> alert('Nothing Changed')</script>";
            } else {
                //error detected
               echo "<script> alert('ERROR - $result')</script>";
            }
        } else {
            //error detected
            echo "<script> alert('Invalid Input - $result')</script>";
            //header("Location:manage_trainings.php?edit=");
        }
    }
}
?>
<title><?php echo $sv['site_name'];?> Manage Trainings</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Manage Trainings</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <?php //Check if logged in
    if($staff){
        //Check if page needs to display an input form
        if( preg_match('(input_)', $_SESSION['type']) ){ ?>
            <div class="row">
                <div class="col-md-9">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fas fa-search fa-lg"></i>
                            <?php if( preg_match('(input_add)', $_SESSION['type']) ){
                                echo "Add Training Module to $device";
                            } else {
                                echo "Edit Training Module for $device";
                            }?>
                                <div class="pull-right"> <a href='/admin/manage_trainings.php'><i class="fas fa-reply fa-lg"></i>Go Back</a> </div>
                        </div>
                        <div class="panel-body">
                            <table class="table table-striped table-bordered"><form method="post" action="" autocomplete='off' id="tmForm">
                                <tbody>
                                    <tr>
                                        <td><a href="#" data-toggle="tooltip" data-placement="top" title="Name of the training for the <?php echo $device;?>">Title</a></td>
                                        <td>
                                            <input value="<?php echo $title;?>" tabindex="1" name="title" id="title" name="title" disabled class="form-control"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><a href="#" data-toggle="tooltip" data-placement="top" title="A brief description of what this training covers">Description</a></td>
                                        <?php echo "<td><textarea class='form-control' rows='".(floor(strlen($tm_desc)/80)+2)."' tabindex='2' name='tm_desc' id='tm_desc' disabled>$tm_desc</textarea></td>"; ?>
                                    </tr>
                                    <tr>
                                        <td><a href="#" data-toggle="tooltip" data-placement="top" title="Expected duration for the training">Duration</a></td>
                                        <td><?php
                                            if (strcmp($duration,"") == 0){
                                                    $sArray = array(0,0);
                                                } else {
                                                    $sArray = explode(":", $duration);
                                                }
                                            ?>
                                            <input type="number" name="hours" id="hours" tabindex="3" min="0" max="100" step="1" 
                                                    placeholder="hh" value="<?php echo $sArray[0]?>" style="text-align: right" disabled /> Hours &ensp;
                                            <select name="minutes" id="minutes" tabindex="4" disabled>
                                                <?php //Determine the default value
                                                    $min = array("00","15","30","45");
                                                    foreach($min as $m){
                                                        if($m == $sArray[1]){
                                                            echo "<option value='$m' selected>$m</option>";
                                                        } else {
                                                            echo "<option value='$m'>$m</option>";
                                                        }
                                                    }
                                                ?>
                                            </select> Minutes
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><a href="#" data-toggle="tooltip" data-placement="top" title="Is this required prior to use?">Required</a></td>
                                        <td><select name="tm_required" id="tm_required" tabindex="5" disabled>
                                            <option value="" hidden>Select</option>
                                            <?php //Determine which option is selected
                                                if ($tm_required == 'Y'){
                                                    echo"<option value='Y' selected>Y</option>";
                                                    echo"<option value='N'>N</option>";
                                                } elseif ($tm_required == 'N') {
                                                    echo"<option value='Y'>Y</option>";
                                                    echo"<option value='N' selected>N</option>";
                                                } else {
                                                    echo"<option value='' hidden selected>Select</option>";
                                                    echo"<option value='Y'>Y</option>";
                                                    echo"<option value='N'>N</option>";
                                                }
                                            ?>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><a href="#" data-toggle="tooltip" data-placement="top" title="Training Rubric">File Name</a></td>
                                        <td><input value="<?php echo $file_name;?>" name="file_name" id="file_name" tabindex="6" disabled /></td>
                                    </tr>
                                    <tr>
                                        <td><a href="#" data-toggle="tooltip" data-placement="top" title="Maximum Class Size">Class Size</a></td>
                                        <td><input type="number" value="<?php echo $class_size;?>" min="0" max="999" style="text-align:right;" name="class_size" id="class_size" tabindex="7" disabled /></td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan=2 align='right'>
                                            <?php if( preg_match('(input_edit)', $_SESSION['type']) ){
                                                echo "Updated On ".date( 'M d, Y g:i a',strtotime($tm_stamp) )."&ensp;";
                                            } 
                                            if($staff->getRoleID() >= $sv['minRoleTrainer']){ ?>
                                                <button type="button" class="btn btn-basic btn-md" onclick="editTM()" tabindex="8" id="editBtn">Edit</button>
                                            <?php } else { ?>
                                                <button type="button" class="btn btn-basic btn-md" tabindex="8" disabled>Edit</button>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
			</div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-md-9 -->
                <div class="col-md-3">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fas fa-table fa-lg"></i> Stats for this class
                        </div>
                        <div class="panel-body">
                            <table class="table table-condensed">
                                <tbody>
                                    <?php if(isset($tm_id) && $result = $mysqli->query("
                                        SELECT count(*) as count
                                        FROM `tm_enroll`
                                        WHERE `tm_id` = $tm_id;
                                    ")){
                                        $row = $result->fetch_assoc()?>
                                        <tr>
                                            <td><i class="far fa-check-circle fa-lg"></i> Certificates Issued</td>
                                            <td><?php echo $row['count'];?></td>
                                        </tr>
                                    <?php } else { ?>
                                        <tr>
                                        <td>Training Enrollments</td><td>-</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-md-3 -->
            </div>
            <!-- /.row -->
        <?php //Display the regular input
        } else { ?>
            <div class="row">
                <div class="col-md-9">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fas fa-search fa-lg"></i> Select Device or Group
                        </div>
                        <div class="panel-body">
                            <div align="center">
                                <select name="d_id" id="d_id" onchange="selectDevice(this)" tabindex="1">
                                    <option disabled hidden selected value="">Device</option>
                                    <?php if($result = $mysqli->query("
                                        SELECT d_id, device_desc
                                        FROM devices
                                        ORDER BY device_desc
                                    ")){
                                        while($row = $result->fetch_assoc()){
                                            echo("<option value='".$row["d_id"]."'>".$row["device_desc"]."</option>");
                                        }
                                    } else {
                                        echo ("Device list Error - SQL ERROR");
                                    }?>
                                </select> or <select name="dg_id" id="dg_id" onchange="selectDevice(this)" tabindex="2">
                                        <option disabled hidden selected value="">Device Group</option>
                                        <?php if($result = $mysqli->query("
                                            SELECT dg_id, dg_desc
                                            FROM device_group
                                            ORDER BY dg_desc
                                        ")){
                                            while($row = $result->fetch_assoc()){
                                                echo("<option value='".$row["dg_id"]."'>".$row["dg_desc"]."</option>");
                                            }
                                        } else {
                                            echo ("Device list Error - SQL ERROR");
                                        }?>
                                </select>
                            </div>
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fas fa-edit fa-lg"></i> Training Modules
                            <div class="pull-right">
                                <?php if($staff->getRoleID() >= $sv['minRoleTrainer']){ ?>
                                    <button type="button" id="addBtn" onclick="addTM()">Add</button>
                                <?php } else { ?>
                                    Add
                                <?php } ?>
                            </div>
                        </div>
                        <div class="panel-body">
                            <table class="table table-bordered">
                                <tr>
                                    <td><a href="#" data-toogle="tooltop" data-placement="top" title="All devices covered by this Device Group">Devices</a></td>
                                    <td id="td_deviceList"></td>
                                </tr>
                            </table>
                            <table class="table table-striped table-bordered table-hover" id="tm">
                            </table>
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-md-9 -->
                <div class="col-md-3">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fas fa-table fa-lg"></i> Total Stats
                        </div>
                        <div class="panel-body">
                            <table class="table table-condensed">
                                <tbody>
                                <?php if($result = $mysqli->query("
                                    SELECT count(*) as count
                                    FROM `trainingmodule`
                                ")){
                                    $row = $result->fetch_assoc()?>
                                    <tr>
                                        <td><i class="far fa-file fa-lg"></i> Training Modules</td>
                                        <td><?php echo $row['count'];?></td>
                                    </tr>
                                <?php } else { ?>
                                    <tr>
                                        <td><i class="far fa-file fa-lg"></i> Training Modules</td><td>-</td></tr>
                                <?php } 
                                if($result = $mysqli->query("
                                        SELECT count(*) as count
                                        FROM `tm_enroll`
                                        WHERE `current` = 'Y'
                                ")){
                                    $row = $result->fetch_assoc()?>
                                    <tr>
                                        <td><i class="far fa-check-circle fa-lg"></i> Certificates Issued</td>
                                        <td><?php echo $row['count'];?></td>
                                    </tr>
                                <?php } else { ?>
                                    <tr>
                                        <td>Training Enrollments</td><td>-</td></tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-md-3 -->
            </div>
            <!-- /.row -->
        <?php }   
    } else { ?>
        <div align="center">
            <div class="col-md-4 col-md-offset-4">
                <img src="/images/hal.png" class="img-responsive" alt="I'm Sorry I can't do that for you">
                <i>I'm sorry, I cant do that for you</i>
            </div>
        </div>
        <!-- /.row -->
    <?php }?>
</div>
<!-- /#page-wrapper -->
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript">
    var device = "";
    var list = ["title", "tm_desc", "hours", "minutes", "tm_required", "class_size"];
<?php if($staff && !preg_match('(input_)', $_SESSION['type']) ) { ?>
    window.onload = function(){
        document.getElementById("dg_id").selectedIndex = 0;
        document.getElementById("d_id").selectedIndex = 0;
    };
<?php } ?>
<?php if ( $staff && strcmp( $_SESSION['type'], "input_add") == 0){ ?>
    var addBtn = document.getElementById("editBtn");
    //change the button
    addBtn.firstChild.data = "Save";
    addBtn.classList.remove('btn-basic');
    addBtn.classList.add('btn-info');

    //enable fields
    for( i = 0; i < list.length; i++){
        document.getElementById(list[i]).disabled=false;
    }
<?php } ?>
    
    function addTM(){
        if (device  != ""){
            var dest = "/admin/manage_trainings.php?add=";
            dest = dest.concat(device);
            console.log(dest);
            window.location.href = dest;
        } else {
            message = "Please select a device or group.";
            var answer = alert(message);
        }
    }

    //enable edit fields
    function editTM(){
        var btn = document.getElementById("editBtn");
        var form = document.getElementById("tmForm");
        var message = "";
        var emtpy = false;

        //Btn is in Save Mode
        if (btn.firstChild.data == "Save"){
            if (!document.getElementById("title").value){
                message = "Please give this training a title.";
                emtpy = true;
            }
            if (!document.getElementById("tm_desc").value){
                message = "Provide a description.";
                emtpy = true;
            }
            if ( (document.getElementById("hours").value+document.getElementById("minutes").value/60) == 0){
                message = "State the duration of the training.";
                emtpy = true;
            }
            if (!document.getElementById("tm_required").value){
                message = "Select if this training is required prior to operating the device";
                emtpy = true;
            }
            if (!document.getElementById("class_size").value){
                message = "Indicate the maximum class size";
                emtpy = true;
            }
            
            if(emtpy){
                alert(message);
                return false;
            } else {
                form.submit();
            }
            
        } else {
            //determine the btn state
            var message = "Are you sure you want to edit?";
            var answer = confirm(message);
            if (answer){
                //change the button
                btn.firstChild.data = "Save";
                btn.classList.remove('btn-basic');
                btn.classList.add('btn-info');

                //enable fields
                for( i = 0; i < list.length; i++){
                    document.getElementById(list[i]).disabled=false;
                }
            }
        }
    }

    //AJAX call to build a table of training modules for the specified device
    function selectDevice(element){
        if (element.id === 'd_id'){
            document.getElementById("dg_id").selectedIndex = 0;
        } else if (element.id === 'dg_id') {
            document.getElementById("d_id").selectedIndex = 0;
        }
        document.getElementById("td_deviceList").innerHTML = "";

        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            // code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                document.getElementById("tm").innerHTML = this.responseText;
            }
        };
        device = element.id + "=" + element.value
        xmlhttp.open("GET","sub/getTM.php?" + device,true);
        xmlhttp.send();
        //List Devices
        if (element.id == 'dg_id') {
            if (window.XMLHttpRequest) {
                // code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp2 = new XMLHttpRequest();
            } else {
                // code for IE6, IE5
                xmlhttp2 = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlhttp2.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("td_deviceList").innerHTML = this.responseText;
                }
            };
            device = element.id + "=" + element.value;
            xmlhttp2.open("GET","sub/certDevices.php?" + device,true);
            xmlhttp2.send();
        }
    }
    
    function success(){
        //fire off modal & timer
        document.getElementById("modal-title").innerHTML = "Success";
        document.getElementById("modal-body").innerHTML = "Training has been saved! <a href='/admin/manage_trainings.php'>Go Back</a>";
        $('#popModal').modal('show');
        setTimeout(function(){$('#popModal').modal('hide')}, 3000);
    }

    //redirect training module to view/edit page
    function viewTM(tm, title){
        var dest = "/admin/manage_trainings.php?edit=";
        dest = dest.concat(tm);
        window.location.href = dest;
    }
</script>