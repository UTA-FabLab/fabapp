<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 * 
 */
 
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/connections/db_connect8.php');
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/class/all_classes.php');


if ( !empty($_GET["val"]) && DeviceGroup::regexDgID( filter_input(INPUT_GET, "val")) ){
    $dg = new DeviceGroup( filter_input(INPUT_GET, "val") );
    if (filter_input(INPUT_GET, "loc") == 1){
        if ($result = $mysqli->query("
            SELECT DISTINCT`devices`.`d_id`, `devices`.`device_desc`
            FROM `devices`
            WHERE public_view = 'Y' AND `devices`.`dg_id` = $dg->dg_id
            ORDER BY `device_desc` ASC
        ")){
            while($row = $result->fetch_assoc()){

                echo "<option value=$row[d_id]>$row[device_desc]</option>";
            }
        }
    }
    if (filter_input(INPUT_GET, "loc") == 0){
        if ($result = $mysqli->query("
            SELECT DISTINCT`devices`.`d_id`, `devices`.`device_desc`
            FROM `devices`
            WHERE `devices`.`dg_id` = $dg->dg_id
            ORDER BY `device_desc` ASC
        ")){
            while($row = $result->fetch_assoc()){

                echo "<option value=$row[d_id]>$row[device_desc]</option>";
            }
        }
    }
} else {
    echo "<option selected disabled hidden>Invalid Device Group</option>";
}

?>