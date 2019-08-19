<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */
 //This will import all of the CSS and HTML code necessary to build the basic page
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

//Submit results
$resultStr = "";
if (!$staff || $staff->getRoleID() < $sv['minRoleTrainer']){
    //Not Authorized to see this Page
    header('Location: /index.php');
    $_SESSION['error_msg'] = "Insufficient role level to access, You must be a <a href='https://www.pokemon.com/us/pokemon-trainer-club/login'>Trainer.</a>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ( isset($_POST['onboardBtn']) ){
        $staff_id = filter_input(INPUT_POST,'operator');
        if ($staff_id){
            $resultStr = "Staff ID: ".$staff_id;
            $operator = "2".substr($staff_id, 1);
            $resultStr .= ", Learner: ".$operator." | ";
        } else {
            echo "no operator, ";
        }
        $d_id = filter_input(INPUT_POST, 'selectDevice');
        if ($d_id){
            $device = new Devices($d_id);
            echo " assigned to ".$device->getDevice_desc()."<br>";
        } else {
            echo " no device <br>";
        }

        //Unpaid Ticket
        $mysqli->query("INSERT INTO `transactions` (`d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`, `notes`)
                        VALUES ('$device->d_id', '$operator', '01:00:00', '2018-05-08 12:00:00', '2018-05-08 13:00:00', '01:00:00', '$status[charge_to_acct]', '1', '$staff_id', 'Left without paying');");
        $ins_id = $mysqli->insert_id;
        $mysqli->query("INSERT INTO `mats_used` (`trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`)
                        VALUES ('$ins_id', '18', '-4', CURRENT_TIMESTAMP, '$status[used]', '1000000010'); ");
        $mysqli->query("INSERT INTO `acct_charge` (`ac_id`, `a_id`, `trans_id`, `ac_date`, `operator`, `staff_id`, `amount`, `ac_notes`, `recon_date`, `recon_id`)
                        VALUES (NULL, '1', '$ins_id', CURRENT_TIMESTAMP, $operator, '1000000010', '.20', 'Debit Charge', NULL, NULL);");
        $resultStr .= "Outstanding: ".$ins_id;

        //Ticket In Storage
        $mysqli->query("INSERT INTO `transactions` (`d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`, `notes`)
                        VALUES ('$device->d_id', $operator, '01:00:00', '2018-05-08 12:00:00', '2018-01-08 13:00:00', '01:00:00', '$status[stored]', '1', '1000000010', 'Object In Storage');");
        $sto1 = $mysqli->insert_id;
        $mysqli->query("INSERT INTO `mats_used` (`trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`)
                        VALUES ($sto1, '16', '-25', '2018-05-08 13:00:00', '$status[used]', $staff_id);");
        $mysqli->query("UPDATE `storage_box` 
                                SET `item_change_time` = '2018-01-08 13:00:00', `drawer` = '3', `unit` = 'F', `trans_id` = $sto1, `staff_id` = '1000000010');");
        //Ticket in Storage2
        $mysqli->query("INSERT INTO `transactions` (`d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`, `notes`)
                    VALUES ('$device->d_id', $operator, '01:00:00', '2018-06-1 12:00:00', '2018-06-1 13:00:00', '01:00:00', '$status[stored]', '1', '1000000010', 'Object In Storage');");
        $sto2 = $mysqli->insert_id;
        $mysqli->query("INSERT INTO `mats_used` (`trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`)
                        VALUES ($sto2, '16', '-20', '2018-06-1 13:00:00', '$status[used]', $staff_id);");
        $mysqli->query("UPDATE `storage_box` 
                                SET `item_change_time` = '2018-01-08 13:00:00', `drawer` = '1', `unit` = 'D', `trans_id` = $sto1, `staff_id` = '1000000010');");
        $resultStr .= ", Storage: $sto1 & $sto2";

        //Live Ticket
        $mysqli->query("INSERT INTO `transactions` (`d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`)
                        VALUES ('$device->d_id', $operator, '01:00:00', CURRENT_TIMESTAMP, NULL, NULL, '$status[active]', '1', '1000000010');");
        $ins_id = $mysqli->insert_id;
        $mysqli->query("INSERT INTO `mats_used` (`trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`, `mu_notes`)
                        VALUES ('$ins_id', '14', '-4', CURRENT_TIMESTAMP, '$status[used]', '1000000010', NULL);");
        $resultStr .= ", Live Ticket: ".$ins_id."</br></br></br>";
    } elseif ( isset($_POST['addBtn']) ){
        $operator = filter_input(INPUT_POST, "op2");
        $icon_code = filter_input(INPUT_POST, "u_icon");
        $user = Users::withID($operator);
        $role_id = filter_input(INPUT_POST,'selectRole');
        $icon_code = filter_input(INPUT_POST,'icon_code');
        
        if("" == $icon_code){
            $_SESSION['error_msg'] = "icon stuyff";
            header('Location: /admin/onboarding.php');
            exit();
        }   
        
        if ( preg_match("/^\d{1,2}$/", $role_id) == 1 ){
            $str = $user->insertUser($staff, $role_id);
            if (is_string($str)){
                $resultStr = $str;
            } else {
                $resultStr = "Staff ID $operator has been assigned $role_id :".Role::getTitle($role_id).".".$icon_code."    ddddd";
            }
            
        } else {
            echo "<script>window.onload = function(){goModal('Error',\"Invalid Role\", false)}</script>";
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
        <div class="col-md-7">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-user-friends"></i> FabApp Users
                </div>
                <!-- /.panel-heading -->
                <div class="panel-body">
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
        <!-- /.col-md-8 -->
        <div class="col-md-5">
            <div class="row">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fas fa-plus fa-fw" aria-hidden="true"></i> Add/Modify User
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped table-hover">
                            <form method="POST" action="" id="myForm" autocomplete='off' onsubmit="return validateID()">
                                <tr>
                                    <td>
                                        <b data-toggle="tooltip" data-placement="top" title="Select Device">Role: </b>
                                    </td>
                                    <td>
                                        <select class="form-control" name="selectRole" id="selectRole">
                                            <option hidden selected value="">Select Role</option>
                                            <?php
                                                $result = $mysqli->query("SELECT * FROM `role`");
                                                while($row = $result->fetch_assoc()){
                                                    echo "<option value=\"$row[r_id]\">$row[title]</option>";
                                                }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <b data-toggle="tooltip" data-placement="top" title="email contact information">Operator ID: </b>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="op2" id="op2" maxlength="10" size="10" placeholder="Enter ID #" />
                                    </td>
                                </tr>
                                <tfoot>
                                    <tr>
                                        <td colspan="2">
                                            <div class="pull-right">
                                                <button type="submit" name="addBtn" class="btn btn-success" onclick="return Submitter()">Add/Modify User</button>
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
            <div class="row">
                <div class="col-md-10 col-md-offset-1">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fas fa-user" aria-hidden="true"></i> Update User Icon
                        </div>
                        <div class="panel-body">
                            <table class="table table-bordered table-striped table-hover">
                                <form method="POST" action="" id="myForm1" autocomplete='off'>
                                    <tr>
                                        <td>
                                            <b data-toggle="tooltip" data-placement="top" title="email contact information">Operator ID: </b>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="op3" id="op3" maxlength="10" size="10" placeholder="Enter ID #" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="pull-left">
                                                <a class="btn btn-default btn-xs" href="https://www.fontawesome.com/icons?d=gallery;" target="_blank" title="Click to view FontAwesome icon gallery"><i class="fas fa-info"></i></a>
                                            </div>
                                            &nbsp;
                                            <b data-toggle="tooltip" data-placement="top" title="FontAwesome icon code">Icon Code: </b>
                                        </td>
                                        <td>
                                            <input class="form-control icp demo" value="fas fa-anchor" type="text" id="icon_code" name="icon_code">
                                        </td>
                                    </tr>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2">
                                                <div class="pull-right">
                                                    <button type="submit" name="iconBtn" class="btn btn-info" onclick="return Submitter()">Update Icon</button>
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
    
    <div class="row">
        <div class="row">
            &nbsp;&nbsp;&nbsp;&nbsp;
        </div>
        <!-- /.row -->

        <div class="row">
            &nbsp;&nbsp;&nbsp;&nbsp;
        </div>
        <!-- /.row -->

        <div class="row">
            &nbsp;&nbsp;&nbsp;&nbsp;
        </div>
        <!-- /.row -->

        <div class="row">
            &nbsp;&nbsp;&nbsp;&nbsp;
        </div>
        <!-- /.row -->
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="panel panel-default">
                    <div class="col-lg-12">
                            <h2 style="text-align:center;" class="page-header"> FabApp Advanced Tools <br>(For Developer Use Only)</br></h2>
                            <div style="text-align:center;">
                                <input class='btn btn-warning' type='button' data-toggle='collapse' data-target='.dev_tools_field'
							  onclick='button_text(this)' aria-expanded='false' aria-controls='collapse' value='Open Tools'/>
                            </div>
                    </div>
            &nbsp;&nbsp;&nbsp;&nbsp;
                <div class="panel-body">
                    <div class='col-md-12 dev_tools_field collapse'>
                        <div class="col-md-8">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <i class="fa fa-address-card" aria-hidden="true"></i> Setup Training Tickets
                                </div>
                                <div class="panel-body">
                                    <table class="table table-bordered table-striped table-hover">
                                        <form method="POST" action="" autocomplete='off' onsubmit="return validate2()">
                                            <tr>
                                                <td>
                                                    <b data-toggle="tooltip" data-placement="top" title="Select Device">Device: </b>
                                                </td>
                                                <td>
                                                    <select class="form-control" name="selectDevice" id="selectDevice">
                                                        <option hidden selected value="">Select Device</option>
                                                        <?php
                                                            $result = $mysqli->query(" SELECT * FROM `devices` WHERE 1 ORDER BY `device_desc`;");
                                                            while($row = $result->fetch_assoc()){
                                                                echo "<option value=\"$row[d_id]\">$row[device_desc]</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <b data-toggle="tooltip" data-placement="top" title="email contact information">Operator ID: </b>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="operator" id="operator" maxlength="10" size="10" placeholder="Enter ID #" />
                                                </td>
                                            </tr>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="2">
                                                        <div class="pull-left"><button type="submit" name="onboardBtn" class="btn btn-primary" onclick="return Submitter()">Submit</button></div>
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
                        <div class="col-md-4">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <i class="fas fa-gas-pump fa-fw"></i> Fill PolyPrinters
                                </div>
                                <div class="panel-body">
                                    <form method="POST" action="" autocomplete='off'>
                                        <div style="text-align:center;"><button type="submit" name="fillBtn" class="btn btn-primary" onclick="return Submitter()">Fill All PolyPrinters</button></div>
                                    </form>
                                </div>
                            </div>
                            <!-- /.panel -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.panel --> 
        </div>
    </div>
    <!-- /.row -->
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
