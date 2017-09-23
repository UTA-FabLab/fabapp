<?php

/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 */

/**
 * Description of AuthRecipients
 * Checks the AuthRecipients table to see if a users is 
 * authorized to pick up an object.
 *
 * @author Jon Le
 */
class AuthRecipients {
    
    //Check if ID can pickup object
    public static function validatePickUp($ticket, $user){
	global $mysqli;
        $trans_id = $ticket->getTrans_id();
        $operator = $user->getOperator();
        
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
                return "ID:$operator is not authorized to claim this object.";
            }
        } else {
            return "AuthRecip Error - ar1836";
        }
        return true;
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
            return"AuthRecip Error - ar1541";
        }
    }
}