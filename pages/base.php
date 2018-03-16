<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
 //This will import all of the CSS and HTML code necessary to build the basic page
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

/*	
 *	When the user presses the button on this screen the form will first call the javascript function located in the onsubmit.
 *	If TRUE will return that value and will submit the page via the method = "post"
 *	ServerSide - the page will reload and below will test if the those two conditions have been met.
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['BaseBtn']) ){
    //Classically it could be retrieved as $_POST['field1'] key/value pair
    //php will be looking for the names of each field
    $field1 = filter_input(INPUT_POST,'field1');
    $field2 = filter_input(INPUT_POST,'field2');



    //////////////////////Method 2 for Regular Expressions
    //  This will look for 1 to many digits
    $roleid = filter_input(INPUT_POST,'roleSelect');
    if (preg_match("/^\d+$/", $roleid) == 0){
        //  If you delete or comment out the JS that checks for this I have the serverside script to
        //  double check this field value.  (never trust the user)
        echo "<script> alert('Invalid RoleID $roleid')</script>";
        //I called for an EXIT because this should not have been a reach able error
        exit();
    }


    //////////////////////Method 2 for Reguar Expressions
    //  At this point you need to decide where you want to scrub the user input
    //  You must be aware that they user could have malicious intent, see SQL injection
    //  Here I can already assume that this value must be an integar and it must already
    //  exist in the device_group table.  I will call a static function to verify this.
    $dg_id = filter_input(INPUT_POST,'dg_id');
    //	func() returns true if all conditions are met
    // See line 48 of DeviceGroup.php
    if (DeviceGroup::regexDgID($dg_id)){
        //Call javascript to alert the user
        echo "<script> alert('You selected $dg_id')</script>";

        //  I have found it useful to redirect the user to a new page. If the user navigates 
        //	backwards to this page it will have the POST script still set and this might duplicate the intent of the form.
        //	A non-issue for search forms, but if you are inserting a record this could be problematic.
        header("Location:base.php?variable=$dg_id");
    } else {
        echo "<script> alert('POST SCRIPT - nothing was selected')</script>";
    }
}

/*
 *	I am pulling values from the url, like before you should scrub these values before doing anything with them.
 *	
 */
if ( !empty($_GET['variable'])){
    $dg_id = filter_input(INPUT_GET, "variable");
    if (DeviceGroup::regexDgID($dg_id)){
        $dg = new DeviceGroup($dg_id);
    }
}
?>
<title><?php echo $sv['site_name'];?> Base</title>
<div id="page-wrapper">
    <div class="row">
        <div class="col-md-12">
            <?php if (isset($dg)) { ?>
                <h1 class="page-header"><?php echo $dg->getDg_desc();?></h1>
                Notice that all the values have been reset.  We have been redirect via the php command header().  This will remove the html POST intent, and if you hit refresh you will not see the browser asking you are you sure you want to reload this page.
            <?php } else { ?>
                <h1 class="page-header">Page Name</h1>
            <?php } ?>
        </div>
        <!-- /.col-md-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-ticket-alt fa-fw"></i> Form Example
                </div>
                <div class="panel-body">
                    <form name="anything" method="post" action="" onsubmit="return validateForm()" autocomplete='off'>
                        <table class="table table-striped table-bordered table-hover">
                        <tr>
                            <td align="Center"><a href="#" data-toggle="tooltip" data-placement="top" title="Convert a hyperlink into a ToolTip">Hover Over Me</a></td>
                            <td><input value="<?php if (isset($field1)) echo $field1;?>" name="field1" id="field1" tabindex="1" /></td>
                        </tr>
                        <tr>
                            <td align="Center">Field 2</td>
                            <td><input value="<?php if (isset($field2)) echo $field2;?>" name="field2" id="field2" tabindex="2" /></td>
                        </tr>
                        <tr>
                            <td>Select Device Group</td>
                            <td>
                                <select name="dg_id" tabindex="3">
                                    <option disabled hidden selected value="">Device Group</option>
                                    <?php if($result = $mysqli->query("
                                        SELECT DISTINCT `device_group`.`dg_id`, `device_group`.`dg_desc`
                                        FROM `device_group`
                                        WHERE 1;
                                    ")){
                                        while($row = $result->fetch_assoc()){
                                            echo("<option value='$row[dg_id]'>$row[dg_desc]</option>");
                                        }
                                    } else {
                                        echo ("Device list Error - SQL ERROR");
                                    }?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Role</td>
                            <td>
                                <select name="roleSelect" id="roleSelect" tabindex="4">
                                    <option value="" disabled hidden selected>Select</option>
                                    <?php 
                                    $result = $mysqli->query("SELECT * FROM `role` WHERE 1");
                                    while ( $row = $result->fetch_assoc() ){
                                        echo"<option value='$row[r_id]'>$row[title]</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <a href="https://www.w3schools.com/bootstrap/bootstrap_buttons.asp"> Button Style Info </a>
                            </td>
                            <td>
                                <button class="btn btn-danger btn-md pull-right" tabindex="5" name="BaseBtn">BaseBtn</button>
                            </td>
                        </tr>
                    </table>
                    </form>
                </div>
            </div>
        </div>
        <!-- /.col-md-8 -->
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-calculator fa-fw"></i> Side menu
                </div>
                <div class="panel-body">
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
<script>
function validateForm(){
	//to improve UX, I'd recommend using this JS script to perform regular expression checks
	// pass the following arguments to stdRegex(id of input, the regular expression you wish to use, invalid message)
	if (stdRegEx("field1", "/<?php echo $sv['regexUser'];?>/", "Field 1 is Empty") === false){
		return false;
	}
	
    var field2 = document.getElementById('field2').value;
    if (field2 === null || field2 === "") {
        alert("Field 2 is Empty");
        document.getElementById('field2').focus();
        return false;
    }
    
    var x = document.getElementById('roleSelect').value;
    var reg = /^\d{1,2}$/;
    if (x === null || x === "" || !reg.test(x)) {
        alert("RoleSelect 2 is Empty");
        document.getElementById('roleSelect').focus();
        return false;
    }
    //Everything above was good
    return true;
}
</script>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>