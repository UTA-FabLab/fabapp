<?php

/* 
 * FabApp V 0.91
 * 2016-2018 Jon Le
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/connections/db_connect8.php');
include_once ($_SERVER['DOCUMENT_ROOT'].'/class/all_classes.php');
//look up current device status
$dot = 0;
$color = "gainsboro";
$symbol = "circle";
if (filter_input(INPUT_GET, "d_id") && Devices::regexDID(filter_input(INPUT_GET, "d_id"))){
    $d_id = filter_input(INPUT_GET, "d_id");
    
    if($result = $mysqli->query("
        SELECT `sl_id` 
        FROM `service_call` 
        WHERE `d_id` = '$d_id' AND `solved` = 'N' 
        ORDER BY `sl_id` DESC
    ")){
        while ($row = $result->fetch_assoc()){
            if($row['sl_id'] > $dot) {
                $dot = $row['sl_id'];
            }
        }
        if($dot <= 1) {
            $color = "green";
        } elseif($dot < 7) {
            $color = "yellow";
        } else {
            $color = "red";
            $symbol = "times";
        }
    }
}

echo "<i class='fas fa-$symbol fa-lg' style='color:".$color."'></i>&nbsp;";
?>

