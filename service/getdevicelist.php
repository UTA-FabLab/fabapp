<?php
/*
 * CC BY-NC-AS UTA FabLab 2016-2017
 * FabApp V 0.9
 */
 
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/connections/db_connect8.php');
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/class/all_classes.php');

$dg_id = $_GET["dg_id"];

if($dg_id !="" && DeviceGroup::regexDgID($dg_id)){
	$result = $mysqli->query ( "SELECT * FROM devices where dg_id = $dg_id" );
	
	echo "<select>";
	while($row = mysqli_fetch_array($result))
	{
		echo '<option value="'.$row["d_id"].'">'; echo ucwords($row["device_desc"]); echo "</option>";
	}
	echo "</select>";
	
}



?>





<?php
// Standard call for dependencies
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/pages/footer.php');
?>