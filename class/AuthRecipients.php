<?php

/*
 *   Jon Le 2016-2018
 *   FabApp V 0.91
 */

/**
 * Description of AuthRecipients
 * Checks the AuthRecipients table to see if a users is 
 * authorized to pick up an object.
 *
 * @author Jon Le
 */
class AuthRecipients {
    
    public static function add($ticket, $user){
        global $mysqli;
        
        if (is_object($ticket)){
            $trans_id = $ticket->getTrans_id();
        }
        if (is_object($user)){
            $operator = $user->getOperator();
        } else {
            $operator = $user;
        }
        
        if ($result = $mysqli->query("
            SELECT *
            FROM `authrecipients`
            WHERE `authrecipients`.`operator` = '$operator'
            LIMIT 1;
        ")){
            if(($result->num_rows) == 1){
                return "ID: $operator has already been added.";
            }
        } else {
            return "AR Error - 39";
        }
        
        if ( $stmt = $mysqli->prepare("
            INSERT INTO `authrecipients` (`trans_id`, `operator`) 
            VALUES (?, ?);
        ")){
            if (!$stmt->bind_param("ss", $trans_id, $operator))
                    return "AR Bind Error 47";
            if ($stmt->execute()){
                return true;
            } else {
                return "AR Execute Error 51";
            }
        } else {
            return "AR Prep Error 54";
        }
    }
    
    //Create a string array of operators
    public static function listArs($ticket){
        global $mysqli;
        $ars = "";
        
        if (is_object($ticket)){
            $trans_id = $ticket->getTrans_id();
        }
        
        if ($result = $mysqli->query("
            SELECT `operator`
            FROM `authrecipients`
            WHERE `authrecipients`.`trans_id` = '$trans_id'
        ")){
            while ($row = $result->fetch_assoc()){
                $ars .= "$row[operator], ";
            }
            return substr($ars, 0, -2);;
        } else {
            return "AuthRecip Error - AR75";
        }
    }
    
    //Create entry into ObjBox to denote 3rd Party pickup
    public static function markPickUp($trans_id, $operator, $staff_id){
        global $mysqli;
        
        if (!Users::regexUser($operator)) {return "Invalid ID: ".$operator;}
        if (!Users::regexUser($staff_id)) {return "Invalid ID: ".$staff_id;}
        
        if ($mysqli->query("
            INSERT INTO objbox 
                (`trans_id`,`o_start`,`o_end`,`address`,`operator`,`staff_id`)
            VALUES
                ('$trans_id', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, '00','$operator','$staff_id');
        ")){
            return true;
        } else {
            return"AuthRecip Error - ar73";
        }
    }
    
    //Check if ID can pickup object
    public static function validatePickUp($ticket, $user){
        global $mysqli;
        
        if (is_object($ticket)){
            $trans_id = $ticket->getTrans_id();
        } else {
            $trans_id = $ticket;
        }
        if (is_object($user)){
            $operator = $user->getOperator();
        } else {
            $operator = $user;
        }
        
        
        if ($ticket->getUser()->getOperator() == $operator) {
            return true;
        }
        
        if ($result = $mysqli->query("
            SELECT *
            FROM `authrecipients`
            WHERE `authrecipients`.`operator` = '$operator' AND `authrecipients`.`trans_id` = '$trans_id'
            LIMIT 1;
        ")){
            if(($result->num_rows) == 0){
                return "ID: $operator is not authorized to claim this object.";
            }
        } else {
            return "AuthRecip Error - ar107";
        }
        return true;
    }
}