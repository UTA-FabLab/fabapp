<?php

/* 
 * License - FabApp V 0.9
 * 2015-2017 CC BY-NC-AS UTA FabLab
 */
session_start();

//Remove Staff From Memory
unset($_SESSION['staff']);
$_SESSION["timeOut"] = 0;
if (!empty($_GET["n"])){
    $_SESSION['netID'] = null;
    unset($_SESSION['loc']);
    header("Location:/index.php");
} else {
    header("Location:".$_SESSION['loc']);
}

?>