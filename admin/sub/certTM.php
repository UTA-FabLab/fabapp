<?php

/*
 * License - FabApp V 0.9
 * 2016-2018 CC BY-NC-AS UTA FabLab
 *
 * Ajax called by training_certificate.php
 */
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

if (!empty(filter_input(INPUT_GET, "d_id"))) {
    if (Devices::regexDID(filter_input(INPUT_GET, "d_id"))) {
        $query = "SELECT * FROM `trainingmodule` WHERE d_id = '".filter_input(INPUT_GET, "d_id")."' ORDER BY `title`";
    } else {
        echo ("<option disabled hidden selected value='ERROR'>ERROR - d</option>");
        exit();
    }
} elseif (!empty(filter_input(INPUT_GET, "dg_id"))) {
    if (DeviceGroup::regexDgID(filter_input(INPUT_GET, "dg_id"))) {
        $query = "SELECT * FROM `trainingmodule` WHERE dg_id = '".filter_input(INPUT_GET, "dg_id")."' ORDER BY `title`";
    } else {
        echo ("<option disabled hidden selected value='ERROR'>ERROR - dg</option>");
        exit();
    }
} else {
    echo ("<option disabled hidden selected value='ERROR'>ERROR - INPUT</option>");
    exit();
} 

if ($result = $mysqli->query($query)){
    if ($result->num_rows == 0){
        echo ("<option>None</option>");
    } else {
        echo ("<option hidden value=''>Select</option>");
        while($row = $result->fetch_assoc()){
            echo ("<option value='$row[tm_id]'>$row[title]</option>");
        }
    }
} else {
    echo ("<option disabled hidden selected value='ERROR'> ERROR</option>");
    echo $mysqli->error;
}?>