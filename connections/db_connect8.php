<?php

$dbhost = "localhost";
$dbuser = "root";
$dbpass = "mysql";
$dbdatabase = "fabapp-permissions";
// Connecting to mysql database
$mysqli = new mysqli($dbhost, $dbuser, $dbpass) or die(mysql_error());

// Selecting database 
$mysqli->select_db($dbdatabase) or die(mysql_error());
?>
