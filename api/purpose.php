<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Cache-Control, Origin, X-Requested-With, Content-Type, Accept, Key, X-Api-Key, Authorization");

/*
 *  purpose.php : purpose list interface
 *
 *	Arun Kalahasti & Jon Le
 *  version: 0.9 alpha (2018-03-14)
 *
*/

// Requests/replies via JSON data exchange
// =======================================
// 1) Get Purpose By Device ID

require_once( __DIR__."/../connections/db_connect8.php");
include_once ($_SERVER['DOCUMENT_ROOT'].'/class/all_classes.php');
$json_out = array();

//Compare Header API Key with site variable's API Key
$headers = apache_request_headers();
if ($sv['api_key'] == "") {
    $json_out["api_key"] = "Not Set";
} elseif (isset($headers['authorization'])) {
    if ($sv['api_key'] != $headers['authorization'] ){
        $json_out["ERROR"] = "Unable to authenticate Device";
        http_response_code(401);
        ErrorExit(1);
    }
} elseif (isset($headers['Authorization'])) {
    if ($sv['api_key'] != $headers['Authorization'] ){
        $json_out["ERROR"] = "Unable to Authenticate Device";
        http_response_code(401);
        ErrorExit(1);
    }
} else {
    $json_out["ERROR"] = "Header Are Not Set";
    http_response_code(401);
    ErrorExit(1);
}

if (!isset($json_out["ERROR"])){
	$result = mysqli_query($mysqli, "
		SELECT `p_id`, `p_title` AS purpose FROM `purpose` WHERE 1;
	");
	if ($mysqli->error) {
		$json_out["authorized"] = "N";
		$json_out["ERROR"] = $mysqli->error;
	} else {
		while($row = $result->fetch_array(MYSQL_ASSOC)) {
			$json_out[] = $row;
		}
	}
}


// Output JSON and exit
header("Content-Type: application/json");
echo json_encode($json_out);
exit(0);