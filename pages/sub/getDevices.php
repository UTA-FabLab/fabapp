<?php

/* 
 * FabApp V 0.91
 * 2016-2018 Jon Le
 */
 
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/connections/db_connect8.php');
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/class/all_classes.php');

if (!empty($_GET["dg_id"]) && DeviceGroup::regexDgID($_GET["dg_id"])) {
    //$dg = new DeviceGroup();
    
    if ($result = $mysqli->query("
        SELECT `d_id`, `device_desc`
        FROM `devices`
        WHERE `dg_id` = '".filter_input(INPUT_GET, "dg_id")."'
    ")){
        echo ("<option value='' selected hidden>Select</option>");
        while($row = $result->fetch_assoc()){
            echo ("<option value='$row[d_id]'>$row[device_desc] </option>");
        }
    } else {
        echo ("<option value=''>Invalid Query</option>");
        //echo ($mysqli->error);
    }
} else {
    echo ("<option value=''>Invalid Device Group</option>");
}
?>