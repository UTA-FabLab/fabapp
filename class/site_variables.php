<?php
$sv = array();
if($result = $mysqli->query("
    SELECT *
    FROM site_variables
")){
    while( $row = $result->fetch_assoc() ) {
        $sv[$row["name"]] = $row["value"];
    }
        $result->close();
} else {
	$message = $mysqli->error;
}
?>