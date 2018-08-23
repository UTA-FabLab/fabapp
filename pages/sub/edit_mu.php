<?php

/* 
 * License - FabApp V 0.9
 * 2016-2018 CC BY-NC-AS UTA FabLab
 * 
 * Ajax called by edit.php
 */
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

if (!empty(filter_input(INPUT_GET, "d_id"))) {
    $device = new Devices(filter_input(INPUT_GET, "d_id"));
    $mats = Materials::getDeviceMats($device->getDg()->getDG_id());
} else {
    echo ("<option disabled hidden selected value='ERROR'>ERROR - INPUT</option>");
    exit();
}

if (count($mats) == 0){
    echo ("<option>None</option>");
} else {
    echo ("<option hidden value=''>Select</option>");
    foreach($mats as $m){
        echo ("<option value='".$m->getM_id()."'>".$m->getM_name()."</option>");
    }
}
?>