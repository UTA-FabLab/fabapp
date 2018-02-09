<?php

/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 *
 * Ajax called by training_certificate.php
 */
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

if (!empty(filter_input(INPUT_GET, "dg_id"))) {
    if (DeviceGroup::regexDgID(filter_input(INPUT_GET, "dg_id"))) {
        $dg_id = filter_input(INPUT_GET, "dg_id");
        $query = "  SELECT `devices`.`device_desc`
                    FROM `devices`
                    WHERE `devices`.`dg_id` = $dg_id
                    UNION
                    SELECT `devices`.`device_desc`
                    FROM `devices`
                    LEFT JOIN `device_group`
                    ON `devices`.`dg_id` = `device_group`.`dg_id`
                    WHERE `device_group`.`dg_parent` = '$dg_id'
                    ORDER BY `device_desc`;";
    } else {
        echo ("<td id=\"td_deviceList\">Invalid DG_ID</td>");
        exit();
    }
} else {
    echo ("<td id=\"td_deviceList\">No DG_ID provided</td>");
        exit();
} 

if ($result = $mysqli->query($query)){
    if ($result->num_rows == 0){
        echo ("<td id=\"td_deviceList\"></td>");
    } else {
        echo ("<td id=\"td_deviceList\">");
        while($row = $result->fetch_assoc()){
            echo ("$row[device_desc], &ensp;");
        }
        echo ("<td id=\"td_deviceList\"></td>");
    }
} else {
	echo ("<td id=\"td_deviceList\">$mysqli->error</td>");
}?>