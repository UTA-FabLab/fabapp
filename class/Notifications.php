<?php
class Notifications {
    private $operator;
    private $phone_num;
    private $email;
    private $last_contacted;

    public static function sendNotification($q_id, $subject, $message, $status) {
        global $mysqli;
        $hasbeenContacted = false;
        // This function queries the carrier table and sends an email to all combinations

            //Query the phone number and email
        if ($result = $mysqli->query("
            SELECT `Op_phone` AS `Phone`, `Op_email` AS `Email`
            FROM `wait_queue`
            WHERE `Q_id` = $q_id AND valid='Y'
        ")) 
        {
            $row = $result->fetch_assoc();
            $phone = $row['Phone'];
            $email = $row['Email'];

            if (!empty($phone)) {
                if ($result = $mysqli->query("
                    SELECT email
                    FROM carrier
                    WHERE 1;
                ")) {
                    while ($row = $result->fetch_assoc()) {
                        list($a, $b) = explode('number', $row['email']);

                        self::SendMail("".$phone."".$b."", $subject, $message);
                        
                        if ($status==1)
                        {
                            $hasbeenContacted = true;
                        }
                    }
                } else {
                    echo("Carrier query failed!");
                }
            }
            
            if (!empty($email)) {
                self::SendMail($email, $subject, $message);

                if ($status==1)
                {
                    $hasbeenContacted = true;
                }
            }
    
            if ($hasbeenContacted == true) {
                // Update the database to display that the student has been contacted
                if ($mysqli->query("
                    UPDATE `wait_queue`
                    SET `last_contact` = CURRENT_TIMESTAMP
                    WHERE `Q_id` = $q_id AND valid='Y'
                ")) {
                }
            }
            return $hasbeenContacted;
        }
    }
    
    public static function SendMail($to, $subject, $message){
        $headers =  'From: no-reply@fablab.uta.edu' . "\r\n".
                    'Reply-To: no-reply@fablab.uta.edu' . "\r\n".
                    'X-Mailer: PHP/' . phpversion();
        if ( mail($to, $subject, $message, $headers) ){
            return true;
        } else {
            return false;
        }
    }
    
    public static function setLastNotified($q_id){
        global $mysqli;
        if ($mysqli->query("
            UPDATE `wait_queue`
            SET `last_contact` = CURRENT_TIMESTAMP
            WHERE `Q_id` = $q_id AND valid='Y'
        ")) {
        }
    }
    
}
?>
