<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
 //This will import all of the CSS and HTML code necessary to build the basic page
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

//Submit results
$resultStr = "";
if (!$staff || $staff->getRoleID() < 10){
    //Not Authorized to see this Page
    header('Location: /index.php');
    $_SESSION['error_msg'] = "Insufficient role level to access, You must be an admin.</a>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ( isset($_POST['addBtn']) ){
        $operator = filter_input(INPUT_POST, "op2");
        $user = Users::withID($operator);
        $role_id = filter_input(INPUT_POST,'selectRole');
        $icon_code = filter_input(INPUT_POST,'icon_code');
        
        
        if ( preg_match("/^\d{1,2}$/", $role_id) == 1  && $role_id <= $staff->getRoleID()){
            $str = $user->insertUser($staff, $role_id, $icon_code);
            if (is_string($str)){
                $resultStr = $str;
            } else {
                if ($mysqli->query("
                    UPDATE `users`
                    SET `icon` = '$icon_code'
                    WHERE `operator` = '$operator';
                ")) {
                    $resultStr = "Operator ID $operator has been successfully added";
                }
                else {
                    $resultStr = $mysqli->error;
                }
            }
            
        } else {
            echo "<script>window.onload = function(){goModal('Error',\"Invalid Role\", false)}</script>";
        }
        
    }
    if ( isset($_POST['modifyBtn']) ){
        $operator1 = filter_input(INPUT_POST, "operators");
        $user1 = Users::withID($operator1);
        $role_id1 = filter_input(INPUT_POST,'selectRole1');
        $icon_code1 = filter_input(INPUT_POST,'icon_code1');
        
        if((filter_input(INPUT_POST,'selectRole1') == NULL && filter_input(INPUT_POST,'icon_code1') == NULL) || $role_id1 > $staff->getRoleID()){
            echo "<script>window.onload = function(){goModal('Error',\"You must modify the 'Role' or 'Icon' to successfully run this query.\", false)}</script>";
        }
        else {
            if (filter_input(INPUT_POST,'selectRole1') == NULL){
                $role_id1 = filter_input(INPUT_POST,'u_r_id');
            }

            if ( preg_match("/^\d{1,2}$/", $role_id1) == 1 ){
                $str = $user1->insertUser($staff, $role_id1, $icon_code1);
                if (is_string($str)){
                    $resultStr = $str;
                } else {
                    if (filter_input(INPUT_POST,'icon_code1') != NULL){
                        if ($mysqli->query("
                            UPDATE `users`
                            SET `icon` = '$icon_code1'
                            WHERE `operator` = '$operator1';
                        ")) {
                            $resultStr = "Operator ID $operator1 has been successfully updated";
                        }
                        else {
                            $resultStr = $mysqli->error;
                        }
                    } else {
                        $resultStr = "Operator ID $operator1 has been successfully updated";
                    }
                }

            } else {
                echo "<script>window.onload = function(){goModal('Error',\"Invalid Role\", false)}</script>";
            }            
        }
        
    }
}
?>

<html>
<head>
    <title>FabApp - OnBoarding</title>
    <link href="\vendor\iconpicker\css\fontawesome-iconpicker.min.css" rel="stylesheet">
</head>
<body>
    <div id="page-wrapper">

        <!-- Page Title -->
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">FabApp OnBoarding</h1>
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
                        <i class="fas fa-users-cog"></i> User Management
                    </div>
                    <div class="panel-body">
                        <div class="table">
                            <ul class="nav nav-tabs">
						          <li class="active"><a data-toggle="tab" aria-expanded="false" href="#2020202020202">Add User <i class="fas fa-user-plus"></i></a></li>
						          <li><a data-toggle="tab" aria-expanded="false" href="#3030303030303">Modify User <i class="fas fa-user-edit"></i></a></li>
                            </ul>
                            <div class="tab-content">
                                <div id="2020202020202" class="tab-pane fade in active">
                                    <div class="row">
                                        &nbsp;&nbsp;&nbsp;&nbsp;
                                    </div>
                                    <div class="panel panel-default">
                                        <div class="panel-body">
                                            <table class="table table-bordered table-striped table-hover">
                                                <form method="POST" action="" id="myForm" autocomplete='off' onsubmit="return validateID()">
                                                    <tr>
                                                        <td>
                                                            <b data-toggle="tooltip" data-placement="top" title="email contact information">Operator ID: </b>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="op2" id="op2" maxlength="10" size="10" placeholder="1000000000" />
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <b data-toggle="tooltip" data-placement="top" title="Select Device">Role: </b>
                                                        </td>
                                                        <td>
                                                            <select class="form-control" name="selectRole" id="selectRole">
                                                                <option value="" disabled selected>Select Role</option>
                                                                <?php
                                                                    $staff_role = $staff->getRoleID();
                                                                    $result = $mysqli->query("SELECT * FROM `role` WHERE `r_id`<= '$staff_role' ORDER BY `r_id` DESC;");
                                                                    while($row = $result->fetch_assoc()){
                                                                        echo "<option value=\"$row[r_id]\">$row[title]</option>";
                                                                    }
                                                                ?>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <!-- <div class="pull-left">
                                                                <a class="btn btn-default btn-xs" href="https://www.fontawesome.com/icons?d=gallery;" target="_blank" title="Click to view FontAwesome icon gallery"><i class="fas fa-info"></i></a>
                                                            </div>
                                                            &nbsp; -->
                                                            <b data-toggle="tooltip" data-placement="top" title="FontAwesome icon code">Icon Code: </b>
                                                            <br>Include Icon <input type="checkbox" id="iconBox" />
                                                        </td>
                                                        <td>
                                                            <input class="form-control icp demo" onKeyDown="return false" value="fas fa-user" type="text" id="icon_code" name="icon_code" disabled>
                                                        </td>
                                                    </tr>
                                                    <tfoot>
                                                        <tr>
                                                            <td colspan="2">
                                                                <div class="pull-right">
                                                                    <button type="submit" name="addBtn" class="btn btn-success" onclick="return Submitter()">Add User</button>
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
                                <div id="3030303030303" class="tab-pane fade">
                                    <div class="row">
                                        &nbsp;&nbsp;&nbsp;&nbsp;
                                    </div>
                                    <div class="panel panel-default">
                                        <div class="panel-body">
                                            <table class="table table-bordered table-striped table-hover">
                                                <form method="POST" action="" id="myForm" autocomplete='off'>
                                                    <tr>
                                                        <td>
                                                            <b data-toggle="tooltip" data-placement="top" title="Select Variant">Operator ID:</b>
                                                        </td>
                                                        <td>
                                                            <div class="col-md-6">
                                                            <select class="form-control" name="u_r_id" id="u_r_id" onchange="change_operator()" tabindex="1">
                                                                <option value="" disabled selected>Select Role</option>
                                                                <?php
                                                                    $staff_role = $staff->getRoleID();
                                                                    $result = $mysqli->query("
                                                                    SELECT DISTINCT `role`.`r_id` , `role`.`title` 
                                                                    FROM `role` , `users`
                                                                    WHERE `users`.`r_id` = `role`.`r_id` AND `users`.`r_id` <= '$staff_role'
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
                                                    <tr>
                                                        <td>
                                                            <b data-toggle="tooltip" data-placement="top" title="Select Role">Updated Role: </b>
                                                            <br>Don't Update <input type="checkbox" id="roleBox"/>
                                                        </td>
                                                        <td>
                                                            <select class="form-control" name="selectRole1" id="selectRole1">
                                                                <option value="" disabled selected>Select Role</option>
                                                                <?php
                                                                    $staff_role = $staff->getRoleID();
                                                                    $result = $mysqli->query("SELECT * FROM `role` WHERE `r_id`<= '$staff_role' ORDER BY `r_id` DESC;");
                                                                    while($row = $result->fetch_assoc()){
                                                                        echo "<option value=\"$row[r_id]\">$row[title]</option>";
                                                                    }
                                                                ?>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <b data-toggle="tooltip" data-placement="top" title="FontAwesome icon code">Icon Code: </b>
                                                            <br>Don't Update <input type="checkbox" id="iconBox1" checked/>
                                                        </td>
                                                        <td>
                                                            <input class="form-control icp demo" onKeyDown="return false" value="fas fa-user" type="text" id="icon_code1" name="icon_code1" disabled>
                                                        </td>
                                                    </tr>
                                                    <tfoot>
                                                        <tr>
                                                            <td colspan="2">
                                                                <div class="pull-right">
                                                                    <button type="submit" name="modifyBtn" class="btn btn-success" onclick="return Submitter()">Modify User</button>
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-md-12 -->
        </div>
        <!-- /.row -->
		
        <div class="row">
            &nbsp;&nbsp;&nbsp;&nbsp;
        </div>
        <div class="row">
            &nbsp;&nbsp;&nbsp;&nbsp;
        </div>
        <div class="row">
            &nbsp;&nbsp;&nbsp;&nbsp;
        </div>
		
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-user-friends"></i> FabApp Users
                        <div class="pull-right">
                            <button  class="btn btn-xs" data-toggle="collapse" data-target="#userPanel"><i class="fas fa-bars"></i></i></button> 
                        </div>
                    </div>
                    <!-- /.panel-heading -->
                    <div class="panel-body collapse in" id="userPanel">
                        <div class="table-responsive">
                            <ul class="nav nav-tabs">
                                <!-- Load all device groups as a tab that have at least one device in that group -->
                                <?php if ($result = Users::getTabResult()) {
                                    $count = 0;
                                    while ($row = $result->fetch_assoc()) { ?>
                                        <li class="<?php if ($count == 0) echo "active";?>">
                                            <a <?php echo("href=\"#".$row["r_id"]."\""); ?>  data-toggle="tab" aria-expanded="false"> <?php echo($row["title"]); ?> </a>
                                        </li>
                                    <?php 
                                    if ($count == 0){
                                        //create a way to display the first wait_queue table tab by saving which dg_id it is to variable 'first_dgid'
                                        $first_rid = $row["r_id"];  
                                    }   
                                    $count++;                                                                  
                                    }
                                } ?>
                            </ul>
                            <div class="tab-content">
                                <?php
                                if ($Tabresult = Users::getTabResult()) {
                                    while($tab = $Tabresult->fetch_assoc()){
                                        $number_of_user_tables++;


                                        // Give all of the dynamic tables a name so they can be called when their tab is clicked ?>
                                        <div class="tab-pane fade <?php if ($first_rid == $tab["r_id"]) echo "in active";?>" <?php echo("id=\"".$tab["r_id"]."\"") ?> >
                                            <table class="table table-striped table-bordered table-hover" <?php echo("id=\"userTable_$number_of_user_tables\"") ?>>
                                                <thead>
                                                    <tr class="tablerow">
                                                        <th><i class="far fa-user"></i> Operator</th>
                                                        <th><i class="fas fa-bullseye"></i> Icon</th>
                                                        <!--<th><i class="far fa-flag"></i> Date Added</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php // Display all of the students in the wait queue for a device group
                                                    if ($result = $mysqli->query("
                                                            SELECT *
                                                            FROM users U JOIN role R ON U.r_id = R.r_id
                                                            WHERE U.r_id=$tab[r_id];
                                                    ")) {
                                                        while ($row = $result->fetch_assoc()) { ?>
                                                            <tr class="tablerow">

                                                                <!-- Operator -->
                                                                <td align="center"><?php echo($row['operator']) ?></td>

                                                                <!-- Icon --> 
                                                                <td align="center">
                                                                    <?php $user = Users::withID($row['operator']);?>
                                                                    <i class="<?php echo $user->getIcon()?> fa-lg"></i>
                                                                </td>

                                                            </tr>
                                                        <?php }
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php }
                                } ?>
                            </div>
                        </div>
                        <!-- /.table-responsive -->
                    </div>
                </div>
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

function validate2(){
    if (stdRegEx("operator", /<?php echo $sv['regexUser'];?>/, "Invalid Operator ID #") === false){
        return false;
    }
    if (stdRegEx("selectDevice", /^\d{1,}$/, "Select a Device") === false){
        return false;
    }
}
    
function button_text(element) {
    element.value = (element.value == "Open Tools") ? "Hide Tools" : "Open Tools";
}
    
function Submitter(){

    if (confirm("You are about to submit this query. Click OK to continue or CANCEL to quit.")){
        return true;
    }
    return false;
} 
    
function validateID(){
    if (stdRegEx("selectRole", /^\d{1,2}$/, "Select a Role") === false){
        return false;
    }
    if (stdRegEx("op2", /<?php echo $sv['regexUser'];?>/, "Invalid Operator ID #") === false){
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
    
$('.demo').iconpicker();

$('.demo').iconpicker({

  // Popover title (optional) only if specified in the template
  title: false, 

  // use this value as the current item and ignore the original
  selected: false, 

  // use this value as the current item if input or element value is empty
  defaultValue: false, 

  // (has some issues with auto and CSS). auto, top, bottom, left, right
  placement: 'bottom', 

  // If true, the popover will be repositioned to another position when collapses with the window borders
  collision: 'none', 

  // fade in/out on show/hide ?
  animation: true, 

  // hide iconpicker automatically when a value is picked. 
  // it is ignored if mustAccept is not false and the accept button is visible
  hideOnSelect: false,

  // show footer
  showFooter: false,

  // If true, the search will be added to the footer instead of the title
  searchInFooter: false, 

  // only applicable when there's an iconpicker-btn-accept button in the popover footer
  mustAccept: false, 

  // Appends this class when to the selected item
  selectedCustomClass: 'bg-primary', 

  // list of icon classes 
  icons: [], 

  fullClassFormatter: function(val) {
      return 'fa ' + val;
  },

  // children input selector
  input: 'input,.iconpicker-input', 

  // use the input as a search box too?
  inputSearch: false, 

  // Appends the popover to a specific element. 
  // If not set, the selected element or element parent is used
  container: false, 

  // children component jQuery selector or object, relative to the container element
  component: '.input-group-addon,.iconpicker-component',

  // Plugin templates:
  templates: {
    popover: '<div class="iconpicker-popover popover"><div class="arrow"></div>' +
        '<div class="popover-title"></div><div class="popover-content"></div></div>',
    footer: '<div class="popover-footer"></div>',
    buttons: '<button class="iconpicker-btn iconpicker-btn-cancel btn btn-default btn-sm">Cancel</button>' +
        ' <button class="iconpicker-btn iconpicker-btn-accept btn btn-primary btn-sm">Accept</button>',
    search: '<input type="search" class="form-control iconpicker-search" placeholder="Type to filter" />',
    iconpicker: '<div class="iconpicker"><div class="iconpicker-items"></div></div>',
    iconpickerItem: '<a role="button" href="#" class="iconpicker-item"><i></i></a>',
  }
  
});

    
document.getElementById('iconBox').onchange = function() {
    document.getElementById('icon_code').disabled = !this.checked;
};
document.getElementById('iconBox1').onchange = function() {
    document.getElementById('icon_code1').disabled = this.checked;
};

document.getElementById('roleBox').onchange = function() {
    document.getElementById('selectRole1').disabled = this.checked;
};

var str;
for(var i=1; i<= <?php echo $number_of_user_tables;?>; i++){
    str = "#userTable_"+i
    $(str).DataTable({
                "iDisplayLength": 20,
                "order": []
                });
}
    
    
</script>
</html>
