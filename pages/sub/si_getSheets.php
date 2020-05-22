<?php

/***********************************************************************************************************
*   
*   @author Sammy Hamwi
*   EDITED by: MPZinke on 2020.05.22 to prevent SQL injections and improve ajax comm.
*
*   DESCRIPTION:	-Query variants of materials for sheetgoods based on m_id
*   FUTURE: 
*   BUGS: 
*
***********************************************************************************************************/
 
include_once ($_SERVER['DOCUMENT_ROOT'] . '/connections/db_connect8.php');
include_once ($_SERVER['DOCUMENT_ROOT'] . '/class/all_classes.php');


if(empty($_GET["val"])) echo "<option selected disabled hidden>Invalid Sheet Parent</option>";
else
{
	$m_id = filter_input(INPUT_GET, "val");
	if(!is_numeric($m_id)) exit();  // prevent SQL injection
	if($result = $mysqli->query(
			"SELECT DISTINCT `sheet_good_inventory`.`inv_id`, `sheet_good_inventory`.`quantity`,
			`sheet_good_inventory`.`width`, `sheet_good_inventory`.`height`
			FROM  `sheet_good_inventory`
			WHERE `sheet_good_inventory`.`m_ID`=$m_id;")
	)
	{
		$options = "<option disabled selected hidden>Select Variant</option>";
		while($row = $result->fetch_assoc())
			$options .= "<option value='$row[inv_id]'>$row[quantity] On Hand: $row[width]in x $row[height]in</option>";
		echo $options;
	}
	else echo "<option selected disabled hidden>SQL ERROR</option>";
}


?>