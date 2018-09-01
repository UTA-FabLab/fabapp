<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 */

//Thermal Reciept Dependancies
require_once ($_SERVER['DOCUMENT_ROOT'].'/api/php_printer/autoload.php');
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

class Wait_queue {
    private $device;
    private $device_group;
    private $start_time;
    private $end_time;
    private $q_id;
    private $valid;
    private $operator;
    private $phone_num;
    private $email;
    private $last_contacted;
    
    public function __construct($q_id){
        global $mysqli;

        if ($result = $mysqli->query("
            SELECT `Q_id`, `Operator`, `Dev_id`, `Devgr_id`, `Start_date`, `End_date`, `last_contact`, `valid`, `estTime`, `Op_email` AS `email`, `Op_phone` AS `phone`
            FROM wait_queue 
            WHERE `Q_id` = $q_id
            LIMIT 1;
        ")){
            if ($result->num_rows == 0 ){
                throw new Exception("Queue Number Not Found : $q_id");
            }
            $row = $result->fetch_assoc();
            $this->setWaitId($row['Q_id']);
            $this->setOperator($row['Operator']);
            $this->setDevId($row['Dev_id']);
            $this->setDevgrId($row['Devgr_id']);
            $this->setStartTime($row['Start_date']);
            $this->setEndTime($row['End_date']);
            $this->setValid($row['valid']);
            
            if (isset($row['phone'])) 
                $this->setPhone($row['phone']); 
            else $this->setPhone(NULL);

            if (isset($row['email'])) 
                $this->setEmail($row['email']);
            else $this->setEmail(NULL);
            
            if (isset($row['last_contact'])) 
                $this->setLastContact($row['last_contact']);
            else $this->setLastContact(NULL);
        }
        
    }
    

    public static function insertWaitQueue($operator, $d_id, $dg_id, $phone, $email) {
        global $mysqli;
        
        /**
         * TODO: variable validation
         * d_id, dg_id
         */
        
        //Validate input variables
        if (!self::regexPhone($phone) && !empty($phone)) {
            echo ("<div style='text-align: center'>
                    <div class='alert alert-danger'>
                        Bad Phone Number - $phone
                    </div> </div>");
            return "Bad phone number";
        }
        
        if (!self::regexOperator($operator)) {
            echo ("<div style='text-align: center'>
                    <div class='alert alert-danger'>
                        Bad Operator Number - $operator
                    </div> </div>");
            return "Bad operator number";
        }
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
            echo ("<div style='text-align: center'>
                    <div class='alert alert-danger'>
                        Bad Email - $email
                    </div> </div>");
            return "Bad email";
        }
        
        
        if(isset($d_id) && $dg_id!=2) {

            if ($mysqli->query("
                INSERT INTO `wait_queue` 
                  (`operator`,`dev_id`,`Devgr_id`,`start_date`, `Op_email`, `Op_phone`, `last_contact`) 
                VALUES
                    ('$operator','$d_id','$dg_id',CURRENT_TIMESTAMP, '$email', '$phone', CURRENT_TIMESTAMP);

            ")){        
                Notifications::sendNotification($operator, "Fabapp Notification", "You have signed up for fabapp notifications. Your ticket number is: ".$mysqli->insert_id."", 'From: Fabapp Notifications' . "\r\n" .'');
                Wait_queue::calculateDeviceWaitTimes();
                echo ("<div style='text-align: center'>
                    <div class='alert alert-success'>
                        Successfully added to wait queue and updated contact info!
                    </div> </div>");

                return $mysqli->insert_id;
            } else {
                echo ("<div style='text-align: center'>
                    <div class='alert alert-danger'>
                        ".$mysqli->error."
                    </div> </div>");
                return $mysqli->error;
            }

        } else {
            if ($mysqli->query("
                INSERT INTO wait_queue 
                  (`operator`, `Devgr_id`,`start_date`, `Op_email`, `Op_phone`, `last_contact`) 
                VALUES
                    ('$operator','$dg_id',CURRENT_TIMESTAMP, '$email', '$phone', CURRENT_TIMESTAMP);
            ")){        
                Notifications::sendNotification($operator, "Fabapp Notification", "You have signed up for fabapp notifications. Your ticket number is: ".$mysqli->insert_id."", 'From: Fabapp Notifications' . "\r\n" .'');
                Wait_queue::calculateWaitTimes();
                echo ("<div style='text-align: center'>
                    <div class='alert alert-success'>
                        Successfully added to wait queue and updated contact info!
                    </div> </div>");

                return $mysqli->insert_id;
            } else {
                echo ("<div style='text-align: center'>
                    <div class='alert alert-danger'>
                        Error2 updating contact info!
                    </div> </div>");
                return $mysqli->error;
            }
        }
    }
    

    public static function isOperatorWaiting($operator) {
        global $mysqli;
        if ($result = $mysqli->query("
            SELECT COUNT(*) AS `Total`
            FROM `wait_queue`
            WHERE `Operator` = $operator AND `valid` = 'Y';
        "))
        {
            // If the count is greater than zero, then return true
            $row = $result->fetch_assoc();
            
            if ($row['Total'] > 0)
            {
                echo ("This operator is waiting on another ticket, so it's info won't be deleted");
                return true;
            }
            return false;
        }

        return false;
    }

    public static function deleteFromWaitQueue($queueItem)
    {
        global $mysqli;
        global $operator;

        // Get the email or phone number of the student to send them a confirmation notification
        if ($result = $mysqli->query("
            SELECT `Op_email`, `Op_phone`, `last_contact`
            FROM `wait_queue`
            WHERE `Operator` = $queueItem->operator AND valid = 'Y'
            LIMIT 1;
        ")) {
            $row = $result->fetch_assoc();
            if (isset($row['Op_phone'])) {
                // Send a notification that they have canceled their wait queue ticket
                Notifications::sendNotification($queueItem->operator, "Fabapp Notification", "Your Wait Ticket has been cancelled", 'From: Fabapp Notifications' . "\r\n" .'');
            }                 
        }
        else {
            echo ("Could not retrieve phone number of customer ID #$queueItem->operator");
        }
        
            if ($mysqli->query("
                UPDATE `wait_queue`
                SET `valid` = 'N', `End_date` = CURRENT_TIMESTAMP
                WHERE `Q_id` = $queueItem->q_id;
            ")) {
                echo("\nSuccessfully changed valid bit to 'N'!");
            }
            else {
                return $mysqli->error;
            }
        
    
        // If they are not waiting for any other jobs, then delete their contact information
        if (!Wait_queue::isOperatorWaiting($queueItem->operator)) {
            Wait_queue::deleteContactInfo($queueItem->operator);
        }

        // Calculate new wait times based off a person leaving the wait queue
        Wait_queue::calculateWaitTimes();
    }

    public static function transferFromWaitQueue($operator, $d_id)
    {
        global $mysqli;

        //if id && d_id are in wait_queue table
        //elseif id &&dg_id are in wait_queue table
        if ($result = $mysqli->query("
                SELECT `Q_id`
                FROM `wait_queue`
                WHERE `Operator` = '$operator' AND `valid` = 'Y' AND `Dev_id` = '$d_id';
        ")){
            if($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $q_id = $row['Q_id'];
            } else if($result->num_rows == 0) {
                // The operator + d_id combination does not exist, lets try to get the device group number and check if that combination is present in wait queue
                // First, get the dg_id associated with the device
                if ($result = $mysqli->query("
                        SELECT `dg_id`
                        FROM `devices`
                        WHERE `d_id` = '$d_id';
                ")){
                    // fetch and store dg-id
                    $row = $result->fetch_assoc();
                    $dg_id = $row['dg_id'];
                    // check if the user has a valid wait ticket for that device group
                    if ($result = $mysqli->query("
                            SELECT `Q_id`
                            FROM `wait_queue`
                            WHERE `Operator` = '$operator' AND `valid` = 'Y' AND `Devgr_id` = '$dg_id';
                    ")){
                        if($result->num_rows == 1) {
                            $row = $result->fetch_assoc();
                            $q_id = $row['Q_id'];
                        } else {
                            return;
                        }
                    } else {
                        return $mysqli->error;
                    }
                } else {
                    return $mysqli->error;
                }
            } else {
                return;
            }
        } else {
            return $mysqli->error;
        }
        
        // Get the email or phone number of the student to send them a confirmation notification
        if ($result = $mysqli->query("
            SELECT `Op_email`, `Op_phone`, `last_contact`
            FROM `wait_queue`
            WHERE `Operator` = $operator AND valid = 'Y'
            LIMIT 1;
        ")) {
            $row = $result->fetch_assoc();
            if (isset($row['Op_phone'])) {
                // Send a notification that they have canceled their wait queue ticket
                Notifications::sendNotification($operator, "Fabapp Notification", "Your Wait Ticket has been completed.", 'From: Fabapp Notifications' . "\r\n" .'');
            }                 
        }
        else {
            echo ("Could not retrieve phone number of customer ID #$operator");
        }

        if ($mysqli->query("
            UPDATE `wait_queue`
            SET `valid` = 'N', `End_date` = CURRENT_TIMESTAMP
            WHERE `Q_id` = $q_id;
        ")) {
            echo("\nSuccessfully changed valid bit to 'N'!");
        } else {
            return $mysqli->error;
        }
        
    
        // If they are not waiting for any other jobs, then delete their contact information
        if (!Wait_queue::isOperatorWaiting($operator)) {
            Wait_queue::deleteContactInfo($operator);
        }

        // Calculate new wait times based off a person leaving the wait queue
        Wait_queue::calculateWaitTimes();
    }

    public static function deleteContactInfo($operator)
    {
        global $mysqli;
        if ($mysqli->query("
            UPDATE `wait_queue`
            SET `Op_email`=NULL, `Op_phone`=NULL
            WHERE `Operator` = $operator;
        "))
        {
            echo("\nSuccessfully deleted $operator contact info!");
        } else {
            echo ("Error deleting $operator contact info!");
        }
    }
    
    public static function updateContactInfo($operator, $phone, $email)
    {
        global $mysqli;
        $status= 0;
        
        //Validate input variables
        if (!self::regexPhone($phone) && !empty($phone)) {
            echo ("<div style='text-align: center'>
                    <div class='alert alert-danger'>
                        Bad Phone Number - $phone
                    </div> </div>");
            $status = 1;
            return $status;
        }

        if(!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
            echo ("<div style='text-align: center'>
                    <div class='alert alert-danger'>
                        Bad Email - $email
                    </div> </div>");
            $status = 1;
            return $status;
        }
        
        if ($status == 0){
            if ($mysqli->query("
                UPDATE `wait_queue`
                SET `Op_email` = '$email' , `Op_phone` = '$phone'
                WHERE `Operator` = '$operator' AND valid='Y';
            "))
            {
                return $status;
            } else {
                echo ("Error updating $operator contact info!");
            }
        }
    }
    public static function calculateWaitTimes()
    {
        global $mysqli;

        // Find all of the device groups that are being waited for
        if ($result= $mysqli->query("
            SELECT DISTINCT Devgr_id
            FROM wait_queue
            WHERE Devgr_id IS NOT NULL;
        ")) {

            // For each group find how many devices are in the group and their current wait times
            while ($row = $result->fetch_assoc())
            {
                $device_group = $row['Devgr_id'];
                if ($result2 = $mysqli->query("
                    SELECT `devices`.`d_id`, `t_start`, `est_time`, `t_end`
                    FROM `devices` JOIN `device_group` ON `devices`.`dg_id` = `device_group`.`dg_id`
                    LEFT JOIN (SELECT `trans_id`, `t_start`, `t_end`, `est_time`, `d_id`, `operator`, `status_id` FROM `transactions` WHERE `status_id` < 12 ORDER BY `trans_id` DESC) as t 
                    ON `devices`.`d_id` = `t`.`d_id`
                    WHERE `public_view` = 'Y' AND `device_group`.`dg_id` = $device_group AND `devices`.`d_id` NOT IN (
                    
                        SELECT `d_id`
                        FROM `service_call`
                        WHERE `solved` = 'N'
                    )
                    ORDER BY `device_group`.`dg_id`, `device_desc`
                ")) {
                    // Create an array with size equal to the number of devices in that group that holds the number of seconds to wait 
                    $estTimes = array();

                    // Gather all of the times
                    while ($row2 = $result2->fetch_assoc())
                    {
                        if (!isset($row2['t_start']))
                        {
                            // Free Device because the start time is not set
                            array_push($estTimes, 0);
                        }
                        elseif (isset($row2['t_start']) && isset($row2['est_time']) && !isset($row2['t_end']))
                        {
                            list($hours, $minutes, $seconds) = explode(":", $row2['est_time']);
                            $estSeconds = ($hours * 3600 + $minutes * 60 + $seconds);
                            $timeLeft = strtotime($row2['t_start']) + $estSeconds - strtotime("now");

                            // The estimated time has expired but the print has not been ended by the staff
                            if ($timeLeft <= 0) {
                                array_push($estTimes, 0);
                            }

                            // The print is ongoing so log the time
                            else {
                                array_push($estTimes, $timeLeft);
                            }
                        }
                    }


                    
                    // Sort the array
                    sort($estTimes);

                    //echo "<br/><br/><br/><br/><br/><br/><br/>";
                    //echo '<pre>'; print_r($estTimes); echo '</pre>';

                    // Assign estimated wait times to those in the wait queue
                    // if the number of devices in the queue is greater than the number of devices in the group, then do not estimate times for those customers
                    if ($result2 = $mysqli->query("
                        SELECT Q_id
                        FROM wait_queue WQ JOIN device_group DG ON WQ.devgr_id = DG.dg_id
                        WHERE valid = 'Y' AND WQ.Devgr_id = $device_group
                        ORDER BY Q_id;
                    ")) {
                        
                        // For each device waiting in this device group
                        $count = 0;
                        while ($row2 = $result2->fetch_assoc())
                        {
                            // If their wait number is smaller than the number of devices in this device group then give them an estimated time
                            if ($count < count($estTimes)) {
                                $rhours = floor($estTimes[$count] / 3600);
                                $rmins = floor($estTimes[$count] / 60 % 60);
                                $rsecs = floor($estTimes[$count] % 60);
                                $timeFormat = sprintf('%02d:%02d:%02d', $rhours, $rmins, $rsecs);
                                
                                //echo ($timeFormat."<br/>");

                                if ($result3 = $mysqli->query("
                                    UPDATE wait_queue
                                    SET estTime = '$timeFormat'
                                    WHERE Q_id = ".$row2['Q_id']."
                                "));
                            }

                            // If their wait number is greater than the number of devices in this device group then do not estimate their time
                            else {
                                if ($result3 = $mysqli->query("
                                    UPDATE wait_queue
                                    SET estTime = NULL
                                    WHERE Q_id = ".$row2['Q_id']."
                                "));
                            }

                            $count++;
                        }
                    }
                }
            }
        }
    }

    public static function calculateDeviceWaitTimes()
    {
        global $mysqli;

        // Find all of the device groups that are being waited for
        if ($result= $mysqli->query("
            SELECT DISTINCT Dev_id
            FROM wait_queue
            WHERE Dev_id IS NOT NULL;
        ")) {

            // For each group find how many devices are in the group and their current wait times
            while ($row = $result->fetch_assoc())
            {
                $device_id = $row['Dev_id'];
                if ($result2 = $mysqli->query("
                    SELECT `devices`.`d_id`, `t_start`, `est_time`, `t_end`
                    FROM `devices` JOIN `device_group` ON `devices`.`dg_id` = `device_group`.`dg_id`
                        LEFT JOIN (SELECT `trans_id`, `t_start`, `t_end`, `est_time`, `d_id`, `operator`, `status_id` FROM `transactions` WHERE `status_id` < 12 ORDER BY `trans_id` DESC) as t 
                        ON `devices`.`d_id` = `t`.`d_id`
                    WHERE `public_view` = 'Y' AND `devices`.`d_id` = $device_id AND `devices`.`d_id` NOT IN 
                    (
                        SELECT `d_id`
                        FROM `service_call`
                        WHERE `solved` = 'N'
                    )
                    ORDER BY `device_group`.`dg_id`, `device_desc`
                ")) {
                    // Create an array with size equal to the number of devices in that group that holds the number of seconds to wait 
                    global $estTimes;

                    // Set the remaining time of the current job on the device
                    while ($row2 = $result2->fetch_assoc())
                    {
                        if (!isset($row2['t_start']))
                        {
                            // Free Device because the start time is not set
                            $estTimes = 0;
                        }
                        elseif (isset($row2['t_start']) && isset($row2['est_time']) && !isset($row2['t_end']))
                        {
                            list($hours, $minutes, $seconds) = explode(":", $row2['est_time']);
                            $estSeconds = ($hours * 3600 + $minutes * 60 + $seconds);
                            $timeLeft = strtotime($row2['t_start']) + $estSeconds - strtotime("now");

                            // The estimated time has expired but the print has not been ended by the staff
                            if ($timeLeft <= 0) {
                                $estTimes = 0;
                            }

                            // The print is ongoing so log the time
                            else {
                                $estTimes = $timeLeft;
                            }
                        }
                    }

                    // Assign estimated wait times to those in the wait queue
                    // if the number of devices in the queue is greater than the number of devices in the group, then do not estimate times for those customers
                    if ($result2 = $mysqli->query("
                        SELECT Q_id
                        FROM wait_queue WQ JOIN devices D ON WQ.Dev_id = D.d_id
                        WHERE valid = 'Y' AND WQ.Dev_id = $device_id
                        ORDER BY Q_id;
                    ")) {
                        
                        // For each person waiting in this device
                        $count = 0;
                        while ($row2 = $result2->fetch_assoc())
                        {
                            // If they are the first person waiting, then assign them an estimated wait time
                            if ($count < 1) {
                                $rhours = floor($estTimes / 3600);
                                $rmins = floor($estTimes / 60 % 60);
                                $rsecs = floor($estTimes % 60);
                                $timeFormat = sprintf('%02d:%02d:%02d', $rhours, $rmins, $rsecs);

                                if ($result3 = $mysqli->query("
                                    UPDATE wait_queue
                                    SET estTime = '$timeFormat'
                                    WHERE Q_id = ".$row2['Q_id']."
                                ")) { }
                            }

                            // If they are NOT the first person waiting, then do not give them an estimated wait time
                            else {
                                if ($result3 = $mysqli->query("
                                    UPDATE `wait_queue`
                                    SET `estTime` = NULL
                                    WHERE `Q_id` = ".$row2['Q_id']."
                                ")) { }
                            }

                            $count++;
                        }
                    }
                }
            }
        }
    }

    public static function getWaitForGroup($dg_id) {

        global $mysqli;
               
        // Get all of the wait times from the active tickets
        if ($result2 = $mysqli->query("
            SELECT `devices`.`d_id`, `t_start`, `est_time`, `t_end`
            FROM `devices` JOIN `device_group` ON `devices`.`dg_id` = `device_group`.`dg_id`
            LEFT JOIN (SELECT `trans_id`, `t_start`, `t_end`, `est_time`, `d_id`, `operator`, `status_id` FROM `transactions` WHERE `status_id` < 12 ORDER BY `trans_id` DESC) as t 
            ON `devices`.`d_id` = `t`.`d_id`
            WHERE `public_view` = 'Y' AND `device_group`.`dg_id` = $dg_id AND `devices`.`d_id` NOT IN (
            
                SELECT `d_id`
                FROM `service_call`
                WHERE `solved` = 'N'
            )
            ORDER BY `device_group`.`dg_id`, `device_desc`
        ")) {
            // Create an array with size equal to the number of devices in that group that holds the number of seconds to wait
            $estTimes = array();

            // Gather all of the times
            while ($row2 = $result2->fetch_assoc()) {
                if (!isset($row2['t_start'])) {
                    // Free Device because the start time is not set
                    array_push($estTimes, 0);
                } elseif (isset($row2['t_start']) && isset($row2['est_time']) && !isset($row2['t_end'])) {
                    list($hours, $minutes, $seconds) = explode(":", $row2['est_time']);
                    $estSeconds = ($hours * 3600 + $minutes * 60 + $seconds);
                    $timeLeft = strtotime($row2['t_start']) + $estSeconds - strtotime("now");

                    // The estimated time has expired but the print has not been ended by the staff
                    if ($timeLeft <= 0) {
                        array_push($estTimes, 0);
                    }

                    // The print is ongoing so log the time
                    else {
                        array_push($estTimes, $timeLeft);
                    }
                }
            }
        }
        // Sort the array
        sort($estTimes);

        //echo "<br/><br/><br/><br/><br/><br/><br/>";
        //echo '<pre>'; print_r($estTimes); echo '</pre>';

        // Assign estimated wait times to those in the wait queue
        // if the number of devices in the queue is greater than the number of devices in the group, then do not estimate times for those customers
        if ($result2 = $mysqli->query("
            SELECT Q_id
            FROM wait_queue WQ JOIN device_group DG ON WQ.devgr_id = DG.dg_id
            WHERE valid = 'Y' AND WQ.Devgr_id = $device_group
            ORDER BY Q_id;
        ")) {

            // For each device waiting in this device group
            $count = 0;
            while ($row2 = $result2->fetch_assoc())
            {
                // If their wait number is smaller than the number of devices in this device group then give them an estimated time
                if ($count < count($estTimes)) {
                    $rhours = floor($estTimes[$count] / 3600);
                    $rmins = floor($estTimes[$count] / 60 % 60);
                    $rsecs = floor($estTimes[$count] % 60);
                    $timeFormat = sprintf('%02d:%02d:%02d', $rhours, $rmins, $rsecs);

                    //echo ($timeFormat."<br/>");

                    if ($result3 = $mysqli->query("
                        UPDATE wait_queue
                        SET estTime = '$timeFormat'
                        WHERE Q_id = ".$row2['Q_id']."
                    "));
                }

                // If their wait number is greater than the number of devices in this device group then do not estimate their time
                else {
                    if ($result3 = $mysqli->query("
                        UPDATE wait_queue
                        SET estTime = NULL
                        WHERE Q_id = ".$row2['Q_id']."
                    "));
                }

                $count++;
            }
        }
    }

    public static function hasWait(){
        global $mysqli;
        return mysqli_num_rows($mysqli->query(" SELECT * FROM `wait_queue` WHERE `valid`='Y' "))>0;
    }
    
    public static function getTabResult(){
        global $mysqli;
        if ($result = $mysqli->query("
            SELECT `dg_id`, `device_group`.`dg_desc`
            FROM `device_group`
            LEFT JOIN `wait_queue`
            ON `wait_queue`.`Devgr_id` = `device_group`.`dg_id`
            WHERE `wait_queue`.`valid`= 'Y';
        ")){
            return  $result;
        } else {
            return false;
        }
    }


    public static function regexPhone($phone) {
        if ( preg_match("/^\d{10}$/", $phone) == 1 )
            return true;
        return false;
    }
    
    public static function regexOperator($op_id) {
        if ( preg_match("/^\d{10}$/", $op_id) == 1 )
            return true;
        return false;
    }

    public static function regexQ_id($q_id){
        global $mysqli;

        if (preg_match("/^\d+$/", $q_id) == 0){
            //echo "Invalid Device Group.";
            return false;
        }

        //Check to see if device exists
        if ($result = $mysqli->query("
            SELECT *
            FROM `wait_queue`
            WHERE `q_id` = '$q_id';
        ")){
            if ($result->num_rows == 1)
                return true;
            return "DG construct: Result not unique";
        } else {
            return "DG Construct: Error with table";
        }
    }
    
    
    public static function printTicket($q_id, $timeLeft, $device){
        global $mysqli;
        global $sv;
        $est_cost = 0;

        //Pull Ticket Related Information
        $ticket = new self($q_id);

        // Set up Printer Connection
        $tp_array = explode("|", $sv['thermalPrinter1']);
        $tpHost = $tp_array[0];
        $tpPort = $tp_array[1];
        try {
            $connector = new NetworkPrintConnector( $tpHost, $tpPort);
            $printer = new Printer($connector);
        } catch (Exception $e) {
            return "Couldn't print to this printer: " . $e -> getMessage() . "\n";
        }

        try {
            $img = EscposImage::load($_SERVER['DOCUMENT_ROOT']."/images/fablab2.png", 0);

            $printer -> setJustification(Printer::JUSTIFY_CENTER);
            $printer -> graphics($img);
            $printer -> feed();
            $printer -> setEmphasis(true);
            $printer -> text(date("F jS Y h:i A"));
            $printer -> setEmphasis(false);
            $printer -> feed();

            //Wait Tab Number
            $printer -> setTextSize(4, 4);
            $printer -> text($ticket->getQ_ID());
            $printer -> setTextSize(1, 1);
            $printer -> feed();
            $printer -> text("Device:   ".$device);
            $printer -> setTextSize(1, 1);
            if (isset($timeLeft)){
                $printer -> feed();
                $printer -> text("Est. Duration:   ".$timeLeft); 
            }
            $printer -> feed(2);  
            
            //body
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer -> setEmphasis(true);
            $printer -> text("1. ");
            $printer -> setEmphasis(false);
            $printer -> text("Check http://fabapp.uta.edu for the\n");
            $printer -> text("lastest status on who's being called and\n");
            $printer -> text("get an alert.\n");
            $printer -> feed();

            $printer -> setEmphasis(true);
            $printer -> text("2. ");
            $printer -> setEmphasis(false);
            $printer -> text("FabApp only gives estimates & more\n");
            $printer -> text("than one machine may become available\n");
            $printer -> text("at a time. Stay in the lab if your\n");
            $printer -> text("number is close.\n");
            $printer -> feed();

            $printer -> setEmphasis(true);
            $printer -> text("3. ");
            $printer -> setEmphasis(false);
            $printer -> text("Prep your files while you wait to\n");
            $printer -> text("reduce lag time.\n");
            $printer -> feed();
            $printer -> setJustification(Printer::JUSTIFY_CENTER);
            $printer -> text("Potential Problems?  ( Y )  ( N )");
            $printer -> feed();
            $printer -> graphics(EscposImage::load($_SERVER['DOCUMENT_ROOT']."/images/sig.png", 0));
            $printer -> feed();


            // Print Footer
            $printer -> feed();
            $printer -> setJustification(Printer::JUSTIFY_CENTER);
            $printer -> text("Our full waiting list policy can be\n");
            $printer -> text("viewed at ");
            $printer -> setEmphasis(true);
            $printer -> text("http://fablab.uta.edu/policy");
            $printer -> feed();
            
        } catch (Exception $print_error) {
            //echo $print_error->getMessage();
            $printer -> text($print_error->getMessage());
        }

        // Close Printer Connection
        try {
            $printer -> feed();
            $printer -> cut();

            /* Close printer */
            $printer -> close();
        } catch (Exception $e) {
            echo "Couldn't print to this printer: " . $e -> getMessage() . "\n";
        }
    }
    
    public function setWaitId($q_id) {
        $this->q_id = $q_id;
    }

    public function setValid($valid) {
        $this->valid = $valid;
    }

    public function setStartTime($start_time) {
        $this->start_time = $start_time;
    }

    public function setEndTime($end_time) {
        $this->end_time = $end_time;
    }

    public function setDevId($d_id) {
        $this->device = $d_id;
    }

    public function setDevgrId($dg_id) {
        $this->device_group = $dg_id;
    }

    public function setOperator($op) {
        $this->operator = $op;
    }

    public function setPhone($phone) {
        $this->phone_num = $phone;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setLastContact($lastContact) {
        $this->last_contacted = $lastContact;
    }

    public function getPhone() {
        return $this->phone_num;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getLastContacted() {
        return $this->last_contacted;
    }

    public function getOperator() {
        return $this->operator;
    }

    public function getQ_ID() {
        return $this->q_id;
    }

    public function getContactInfo() {
        return array($this->getPhone(), $this->getEmail(), $this->getLastContacted());
    }
    
    public function getStartTime() {
        return $this->start_time;
    }
    

}
?>
