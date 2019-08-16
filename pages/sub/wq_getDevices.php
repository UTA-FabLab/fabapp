<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 * 
 */
 
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/connections/db_connect8.php');
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/class/all_classes.php');


if ( !empty($_GET["val"]) && DeviceGroup::regexDeviceGroup( filter_input(INPUT_GET, "val")) ){
    $dg = new DeviceGroup( filter_input(INPUT_GET, "val") );
    if ($dg->is_granular_wait){
        if ($result = $mysqli->query("
            SELECT DISTINCT`devices`.`d_id`, `devices`.`device_desc`
            FROM `devices`
            LEFT JOIN `service_call`
            ON `service_call`.`d_id` = `devices`.`d_id`
            WHERE public_view = 'Y' AND `devices`.`dg_id` = ".$dg->dg_id."
                    AND `devices`.`d_id` NOT IN (
                    SELECT `d_id`
                    FROM `service_call`
                    WHERE `solved` = 'N' AND `sl_id` >= 7
                )
            ORDER BY `device_desc` ASC
        ")){
            while($row = $result->fetch_assoc()){
                echo "<option value=$row[d_id]>$row[device_desc]</option>";
            }
        }
    } else {
        echo "<option value=''>"; echo "No Selection Needed"; echo "</option>";
    }
} else {
    echo "<option selected disabled hidden>Invalid Device Group</option>";
}

?>