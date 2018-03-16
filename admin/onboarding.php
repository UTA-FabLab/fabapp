<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
 //This will import all of the CSS and HTML code necessary to build the basic page
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
if (!$staff || $staff->getRoleID() < 10){
    //Not Authorized to see this Page
    header('Location: /index.php');
	$_SESSION['error_msg'] = "In sufficient role level to access, sorry.";
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
    <!-- /.row -->
	<div class="row">
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id = filter_input(INPUT_POST,'operator');
    if ($staff_id){
        echo "Staff ID: ".$staff_id;
        $operator = "2".substr($staff_id, 1);
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
	
    //OutStanding Ticket
    $mysqli->query("INSERT INTO `transactions` (`d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`)
                    VALUES ('".$device->getD_id()."', '$operator', '01:00:00', '2018-02-08 12:00:00', '2018-02-08 13:00:00', '01:00:00', '20', '1', '$staff_id');");
    $ins_id = $mysqli->insert_id;
    $mysqli->query("INSERT INTO `mats_used` (`trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`, `mu_notes`)
                    VALUES ('$ins_id', '18', '-4', CURRENT_TIMESTAMP, '20', '1000000010', 'Left Without Paying'); ");
    $mysqli->query("INSERT INTO `acct_charge` (`ac_id`, `a_id`, `trans_id`, `ac_date`, `operator`, `staff_id`, `amount`, `ac_notes`, `recon_date`, `recon_id`)
                    VALUES (NULL, '1', '$ins_id', CURRENT_TIMESTAMP, $operator, '1000000010', '.20', 'Debit Charge', NULL, NULL);");
    echo "Outstanding: ".$ins_id;

    //Ticket In Storage
    $mysqli->query("INSERT INTO `transactions` (`d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`)
                    VALUES ('".$device->getD_id()."', $operator, '01:00:00', '2018-01-08 12:00:00', '2018-01-08 13:00:00', '01:00:00', '14', '1', '1000000010');");
    $sto1 = $mysqli->insert_id;
    $mysqli->query("INSERT INTO `mats_used` (`trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`, `mu_notes`)
                    VALUES ($sto1, '16', '-25', '2018-01-08 13:00:00', '14', $staff_id, 'Object In Storage');");
    $mysqli->query("INSERT INTO `objbox` (o_start, address, trans_id, staff_id)
                    VALUES ('2018-01-08 13:00:00', '3F', $sto1, '1000000010');");
    //Ticket in Storage2
    $mysqli->query("INSERT INTO `transactions` (`d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`)
                VALUES ('".$device->getD_id()."', $operator, '01:00:00', '2018-02-20 12:00:00', '2018-02-20 13:00:00', '01:00:00', '14', '1', '1000000010');");
    $sto2 = $mysqli->insert_id;
    $mysqli->query("INSERT INTO `mats_used` (`trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`, `mu_notes`)
                    VALUES ($sto2, '16', '-20', '2018-02-20 13:00:00', '14', $staff_id, 'Object In Storage');");
    $mysqli->query("INSERT INTO `objbox` (o_start, address, trans_id, staff_id)
                    VALUES ('2018-02-20 13:00:00', '1A', $sto2, '1000000010');");
    echo ", Storage: $sto1 & $sto2";

    //Live Ticket
    $mysqli->query("INSERT INTO `transactions` (`d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`)
                    VALUES ('".$device->getD_id()."', $operator, '01:00:00', CURRENT_TIMESTAMP, NULL, NULL, '10', '1', '1000000010');");
    $ins_id = $mysqli->insert_id;
    $mysqli->query("INSERT INTO `mats_used` (`trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`, `mu_notes`)
                    VALUES ('$ins_id', '14', '-4', CURRENT_TIMESTAMP, '10', '1000000010', NULL);");
    echo ", Live Ticket: ".$ins_id."</br></br></br>";
}
?>
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-ticket-alt fa-fw"></i> Setup Tickets
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
                        <input type="text" name="operator" id="operator" maxlength="10" size="10" placeholder="Enter ID #" />
                        <button type="submit">Submit</button>
                    </form>
                </div>
            </div>
        </div>
	</div>
</body>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script type="text/javascript">
function validate2(){
        if (stdRegEx("operator", /<?php echo $sv['regexUser'];?>/, "Invalid ID #") === false){
            return false;
        }
        if (stdRegEx("selectDevice", /^\d{1,}$/, "Invalid ID #") === false){
            return false;
        }
}
</script>
</html>
