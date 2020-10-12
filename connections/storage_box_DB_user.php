<?php
$dbhost = "";
$dbuser = "";
$dbpass = "";
$dbdatabase = "";
// Connecting to mysql database
$storage_box_DB_user = new mysqli($dbhost, $dbuser, $dbpass) or die(mysql_error());

// Selecting database 
$storage_box_DB_user->select_db($dbdatabase) or die(mysql_error());
?>