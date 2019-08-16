<?php 
$dbhost = "localhost";
$dbuser = "fabapp";
$dbpass = "CL1fdbO0ulX2nPUX";
$dbdatabase = "fabapp";
// Connecting to mysql database
$mysqli = new mysqli($dbhost, $dbuser, $dbpass) or die(mysql_error());

// Selecting database 
$mysqli->select_db($dbdatabase) or die(mysql_error());
?>
