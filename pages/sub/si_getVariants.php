<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 * 
 */
 
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/connections/db_connect8.php');
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/class/all_classes.php');


if ( !empty($_GET["val"])){
    $m_id = filter_input(INPUT_GET, "val");
    echo "<option disabled>Select Variant</option>";
    if ($result = $mysqli->query("
        SELECT DISTINCT `materials`.`m_id`, `materials`.`m_name`
        FROM `materials`
        WHERE `materials`.`m_parent` = ".$m_id."
        ORDER BY `m_name` ASC
    ")){
        while($row = $result->fetch_assoc()){
            echo "<option value=$row[m_id]>$row[m_name]</option>";
        }
    }
} else {
    echo "<option selected disabled hidden>Invalid Sheet Parent</option>";
}

?>