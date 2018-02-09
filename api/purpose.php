<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Cache-Control, Origin, X-Requested-With, Content-Type, Accept, Key, X-Api-Key, Authorization");

/*
 *  purpose.php : purpose list interface
 *
 *	Arun Kalahasti, 
 *  version: 0.1 alpha (2016-05-20)
 *
*/

// Requests/replies via JSON data exchange
// =======================================
// 1) Get Materials By Device ID

require_once( __DIR__."/../connections/db_connect8.php");
include_once ($_SERVER['DOCUMENT_ROOT'].'/class/all_classes.php');
include 'gatekeeper.php'; 
$json_out = array();

//Compare Header API Key with site variable's API Key
$headers = apache_request_headers();
if(isset($headers['Authorization'])){
    if ($sv['api_key'] != $headers['Authorization'] ){
        $json_out["authorized"] = "N";
        $json_out["ERROR"] = "Unable to Authenticate";
    } else {
        $result = mysqli_query($mysqli, "
            SELECT `p_id`, `p_title` AS purpose FROM `purpose` WHERE 1;
        ");
        if ($mysqli->error) {
            $json_out["authorized"] = "N";
            $json_out["ERROR"] = $mysqli->error;
        }

        while($row = $result->fetch_array(MYSQL_ASSOC)) {
            $json_out[] = $row;
        }
    }
} else {
    $json_out["authorized"] = "N";
    $json_out["ERROR"] = "Header Not Set";
}


// Output JSON and exit
header("Content-Type: application/json");
echo json_encode($json_out);
exit(0);