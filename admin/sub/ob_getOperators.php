<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 * 
 */
 
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/connections/db_connect8.php');
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/class/all_classes.php');


if ( !empty($_GET["val"])){
    $r_id = filter_input(INPUT_GET, "val");
    if(!is_numeric($r_id) && !is_int($r_id)) exit();  // prevent SQL injection

    if ($result = $mysqli->query(
        "SELECT DISTINCT `users`.`user_id`
        FROM `users`
        WHERE `users`.`r_id` = $r_id
        ORDER BY `user_id`;"
    ))
    {
        $response = "<option disabled>Select Operator</option>";
        while($row = $result->fetch_assoc()){
            $response .= "<option value=$row[user_id]>$row[user_id]</option>";
        }
        echo $response;
    }
    else echo "ob_getOperators.php: DB error: $mysqli->error";
}
else  echo "<option selected disabled hidden>Invalid Role</option>";

?>