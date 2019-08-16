<?php 
$dbhost = "dbhost";
$dbuser = "dbuser";
$dbpass = "dbpass";
$dbdatabase = "fabapp";
// Connecting to mysql database
$mysqli = new mysqli($dbhost, $dbuser, $dbpass) or die(mysql_error());

// Selecting database 
$mysqli->select_db($dbdatabase) or die(mysql_error());
?>
