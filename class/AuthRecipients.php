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
			$trans_id = $ticket->trans_id;
		}
		if (is_object($user)){
			$operator = $user->getOperator();
		} else {
			$operator = $user;
		}
		
		if ($result = $mysqli->query("
			SELECT *
			FROM `authrecipients`
			WHERE `authrecipients`.`operator` = '$operator' AND `authrecipients`.`trans_id`= $trans_id
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
				return null;
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
			$trans_id = $ticket->trans_id;
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
		}
		return "AuthRecip Error - AR75";
	}

	
	//Check if ID can pickup object
	public static function validatePickUp($ticket, $user){
		global $mysqli;

		if(!is_object($ticket)) $ticket = new Transactions($ticket);
		$operator = is_object($user) ? $user->operator : $user;

		// always allowed to pick up own print
		if(is_object($ticket) && $ticket->user->operator == $operator) return true;

		if ($result = $mysqli->query("
			SELECT *
			FROM `authrecipients`
			WHERE `authrecipients`.`operator` = '$operator' AND `authrecipients`.`trans_id` = '$ticket->trans_id'
			LIMIT 1;
		")){
			return ($result->num_rows != 0);
		}
		// cannot check DB, default to false
		return false;
	}
}