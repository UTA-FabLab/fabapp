<?php

/* 
 * License - FabApp V 0.9
 * 2015-2016 CC BY-NC-AS UTA FabLab
 */
session_start();
if (!empty($_GET["n"])){
    $_SESSION['netID'] = null;
} 
$_SESSION["staff"] = null;
$_SESSION["timeOut"] = 0;
header("Location:".$_SESSION['loc']);

?>