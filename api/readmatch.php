<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");

require_once( __DIR__."/../connections/db_connect8.php");
$input_data = json_decode(file_get_contents('php://input'), true);

/*
 *  CC BY-NC-AS UTA FabLab 2016-2017
 * 
 *  Suleiman Barakat & Jon Le
 *	FabLab @ University of Texas at Arlington
 *  version: 0.9 beta (2017-01-16)
 */

/*
Missing Logic

Check if rfid_no already exist in the table, if (TRUE) return false;

Check if operator already has an rfid_no, if(TRUE) return false;

Check if operator already has entry in the user's table, if (!TRUE) SQL("Insert...");
*/
$type = $input_data['type'];
$rfid = $input_data['rfid'];
$operator =  $input_data['operator_id'];

if( $type == "create" ){

	$sql = "INSERT INTO rfid (rfid_no, operator) values ($rfid, $operator)";
	$result = $mysqli->query($sql);
	
	$sql = "INSERT INTO users (operator, r_id) values ($operator, 3)";
	$result = $mysqli->query($sql);
	
	echo "done";

}if( $type == "check" ){
	
	
	$sql = "SELECT operator FROM rfid WHERE rfid_no = $rfid AND operator = $operator";
	$result = $mysqli->query($sql);
	
	$row = mysqli_fetch_row( $result );
	
	if( count($row) == 0 ){
		echo "No";
	}else{
		echo "Yes";
	}
	
}


?>