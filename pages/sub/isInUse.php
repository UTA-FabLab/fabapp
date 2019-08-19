<?php

/* 
 * License - FabApp V 0.91
 * 2016-2018 CC BY-NC-AS UTA FabLab
 */
 
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/connections/db_connect8.php');
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/class/all_classes.php');

//Grab Device Group, device
if (!empty($_GET["dg_id"]) || !empty($_GET["operator"]) && DeviceGroup::regexDeviceGroup($_GET["dg_id"]) === true && Users::regexUser($_GET["operator"])) {
    $dg = new DeviceGroup($_GET["dg_id"]);
    if (!$dg->is_granular_wait){
        //run query on the using device
        if ($result = $mysqli->query("
            SELECT `operator`
            FROM `devices`
            JOIN `device_group`
            ON `devices`.`dg_id` = `device_group`.`dg_id`
            LEFT JOIN (SELECT `trans_id`, `d_id`, `operator` FROM `transactions` WHERE `status_id` < $status[total_fail]) as t 
            ON `devices`.`d_id` = `t`.`d_id`
            WHERE public_view = 'Y' AND t.`trans_id` IS NOT NULL AND `devices`.`dg_id` = ".$dg->dg_id." AND `operator` = '$_GET[operator]'
            ORDER BY `trans_id` DESC, `device_desc` ASC
        ")){
            if ($result->num_rows == 1) {
                //Operater has an open ticket, issue warning
                echo "warn";
            } else {
                echo "no_need";
            }
        }
    } else {
        //User can wait for a specific device instead of the first one from the group
        //No Check Needed, Learner on device may recieve a new WQ ticket
        echo "no_need";
    }
} else {
    echo "error";
}
?>