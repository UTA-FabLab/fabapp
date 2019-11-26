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
            SELECT DISTINCT `sheet_good_inventory`.`inv_id` , `sheet_good_inventory`.`quantity` , `sheet_good_inventory`.`width` , `sheet_good_inventory`.`height`
            FROM  `sheet_good_inventory`
            WHERE `sheet_good_inventory`.`m_ID`='$m_id' ;")){
        while($row = $result->fetch_assoc()){
            echo "<option value=\"$row[inv_id]\">$row[quantity] On Hand: $row[width]in x $row[height]in</option>";
        }
    }
} else {
    echo "<option selected disabled hidden>Invalid Sheet Parent</option>";
}

?>