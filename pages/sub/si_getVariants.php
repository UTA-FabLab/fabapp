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
 
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/connections/db_connect8.php');
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/class/all_classes.php');


if(empty($_GET["val"])) echo "<option selected disabled hidden>Invalid Sheet Parent</option>";
else
{
	$m_id = filter_input(INPUT_GET, "val");
	if(!is_numeric($m_id)) exit();  // prevent SQL injection
	if ($result = $mysqli->query(
		"SELECT DISTINCT `materials`.`m_id`, `materials`.`m_name`
		FROM `materials`
		WHERE `materials`.`m_parent` = $m_id
		ORDER BY `m_name` ASC"
	))
	{
		if($result->num_rows == 0) echo "<option disabled hidden selected>NONE</option>";
		else
		{
			$options = "<option disabled hidden selected>Select Variant</option>";
			while($row = $result->fetch_assoc())
				$options .= "<option value=$row[m_id]>$row[m_name]</option>";
			echo $options;
		}
	}
}

?>