<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
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
        $mysqli->query("INSERT INTO `transactions` (`d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`)
                        VALUES ('".$device->getD_id()."', '$operator', '01:00:00', '2018-05-08 12:00:00', '2018-05-08 13:00:00', '01:00:00', '20', '1', '$staff_id');");
        $ins_id = $mysqli->insert_id;
        $mysqli->query("INSERT INTO `mats_used` (`trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`, `mu_notes`)
                        VALUES ('$ins_id', '18', '-4', CURRENT_TIMESTAMP, '20', '1000000010', 'Left Without Paying'); ");
        $mysqli->query("INSERT INTO `acct_charge` (`ac_id`, `a_id`, `trans_id`, `ac_date`, `operator`, `staff_id`, `amount`, `ac_notes`, `recon_date`, `recon_id`)
                        VALUES (NULL, '1', '$ins_id', CURRENT_TIMESTAMP, $operator, '1000000010', '.20', 'Debit Charge', NULL, NULL);");
        $resultStr .= "Outstanding: ".$ins_id;

        //Ticket In Storage
        $mysqli->query("INSERT INTO `transactions` (`d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`)
                        VALUES ('".$device->getD_id()."', $operator, '01:00:00', '2018-05-08 12:00:00', '2018-01-08 13:00:00', '01:00:00', '14', '1', '1000000010');");
        $sto1 = $mysqli->insert_id;
        $mysqli->query("INSERT INTO `mats_used` (`trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`, `mu_notes`)
                        VALUES ($sto1, '16', '-25', '2018-05-08 13:00:00', '14', $staff_id, 'Object In Storage');");
        $mysqli->query("INSERT INTO `objbox` (o_start, address, trans_id, staff_id)
                        VALUES ('2018-01-08 13:00:00', '3F', $sto1, '1000000010');");
        //Ticket in Storage2
        $mysqli->query("INSERT INTO `transactions` (`d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`)
                    VALUES ('".$device->getD_id()."', $operator, '01:00:00', '2018-06-1 12:00:00', '2018-06-1 13:00:00', '01:00:00', '14', '1', '1000000010');");
        $sto2 = $mysqli->insert_id;
        $mysqli->query("INSERT INTO `mats_used` (`trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`, `mu_notes`)
                        VALUES ($sto2, '16', '-20', '2018-06-1 13:00:00', '14', $staff_id, 'Object In Storage');");
        $mysqli->query("INSERT INTO `objbox` (o_start, address, trans_id, staff_id)
                        VALUES ('2018-06-1 13:00:00', '1A', $sto2, '1000000010');");
        $resultStr .= ", Storage: $sto1 & $sto2";

        //Live Ticket
        $mysqli->query("INSERT INTO `transactions` (`d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`)
                        VALUES ('".$device->getD_id()."', $operator, '01:00:00', CURRENT_TIMESTAMP, NULL, NULL, '10', '1', '1000000010');");
        $ins_id = $mysqli->insert_id;
        $mysqli->query("INSERT INTO `mats_used` (`trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`, `mu_notes`)
                        VALUES ('$ins_id', '14', '-4', CURRENT_TIMESTAMP, '10', '1000000010', NULL);");
        $resultStr .= ", Live Ticket: ".$ins_id."</br></br></br>";
    } elseif ( isset($_POST['addBtn']) ){
        $operator = filter_input(INPUT_POST, "op2");
        $user = Users::withID($operator);
        $role_id = filter_input(INPUT_POST,'selectRole');
        if ( preg_match("/^\d{1,2}$/", $role_id) == 1 ){
            $str = $user->insertUser($staff, $role_id);
            if (is_string($str)){
                $resultStr = $str;
            } else {
                $resultStr = "Staff ID $operator has been assigned $role_id :".Role::getTitle($role_id).".";
            }
            
        } else {
            echo "<script>window.onload = function(){goModal('Error',\"Invalid Role\", false)}</script>";
        }
        
    } elseif(isset($_POST['fillBtn'])){
        $pp = array(21,22,23,24,25,26,27,28,29);
        foreach($pp as $p){
            $mysqli->query("INSERT INTO `transactions` (`d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`)
                        VALUES ('$p', '20000000$p', '".rand(1,10).":$p:00', CURRENT_TIMESTAMP, NULL, NULL, '10', '1', '10000000$p');");
            $ins_id = $mysqli->insert_id;
            $mysqli->query("INSERT INTO `mats_used` (`trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`, `mu_notes`)
                            VALUES ('$ins_id', '14', '-4', CURRENT_TIMESTAMP, '10', '1000000010', 'Filled for Testing');");
            $resultStr .= ", Live Ticket: ".$ins_id."</br></br></br>";
        }
    }
}
?>
<html>
<head>
    <title>FabApp - OnBoarding</title>
</head>
<body>
<div id="page-wrapper">
    <div class="row">
        <div class="col-md-12">
            <h1>Welcome to FabApp OnBoarding</h1>
        </div>
        <!-- /.col-md-12 -->
    </div>
    <?php if ($resultStr != ""){ ?>
        <div class="alert alert-success">
            <?php echo $resultStr; ?>
        </div>
    <?php } ?>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-ticket-alt fa-fw"></i> Setup Training Tickets
                </div>
                <div class="panel-body">
                    <form method="POST" action="" autocomplete='off' onsubmit="return validate2()">
                        <select name="selectDevice" id="selectDevice">
                            <option hidden selected value="">Select Device</option>
                            <?php
                                $result = $mysqli->query(" SELECT * FROM `devices` WHERE 1 ORDER BY `device_desc`;");
                                while($row = $result->fetch_assoc()){
                                    echo "<option value=\"$row[d_id]\">$row[device_desc]</option>";
                                }
                            ?>
                        </select>
                        <br/>
                        <input type="text" name="operator" id="operator" maxlength="10" size="10" placeholder="Enter ID #" />
                        <br/>
                        <button type="submit" name="onboardBtn" class="btn btn-info">Submit</button>
                    </form>
                </div>
            </div>
            <!-- /.panel -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-plus fa-fw"></i> Add/Modify Staff
                </div>
                <div class="panel-body">
                    <form method="POST" action="" autocomplete='off' onsubmit="return validateID()">
                        <select name="selectRole" id="selectRole">
                            <option hidden selected value="">Select Role</option>
                            <?php
                                $result = $mysqli->query("SELECT * FROM `role`");
                                while($row = $result->fetch_assoc()){
                                    echo "<option value=\"$row[r_id]\">$row[title]</option>";
                                }
                            ?>
                        </select>
                        <input type="text" name="op2" id="op2" maxlength="10" size="10" placeholder="Enter ID #" />
                        <br/>
                        <button type="submit" name="addBtn" class="btn btn-primary">Add Staff</button>
                    </form>
                </div>
            </div>
            <!-- /.panel -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-gas-pump fa-fw"></i> Fill PolyPrinters
                </div>
                <div class="panel-body">
                    <form method="POST" action="" autocomplete='off'>
                        <button type="submit" name="fillBtn" class="btn btn-primary">Fill All PolyPrinters</button>
                    </form>
                </div>
            </div>
            <!-- /.panel -->
        </div>
    </div>
</body>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript">
function validate2(){
    if (stdRegEx("operator", /<?php echo $sv['regexUser'];?>/, "Invalid Operator ID #") === false){
        return false;
    }
    if (stdRegEx("selectDevice", /^\d{1,}$/, "Select a Device") === false){
        return false;
    }
}
function validateID(){
    if (stdRegEx("selectRole", /^\d{1,2}$/, "Select a Role") === false){
        return false;
    }
    if (stdRegEx("op2", /<?php echo $sv['regexUser'];?>/, "Invalid Operator ID #") === false){
        return false;
    }
}
</script>
</html>
