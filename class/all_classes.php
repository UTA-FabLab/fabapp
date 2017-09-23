<?php
/*
 *   License FabApp v 0.9
 *   2015-2016 CC BY-NC-AS UTA FabLab
 * 
 * Generic call for all classes
 */

/*
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Accounts.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Auth_Accts.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Devices.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/DeviceGroup.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Materials.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Mats_Used.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/ObjBox.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Purpose.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Role.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Service_call.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Service_reply.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/site_variables.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Staff.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Status.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/TrainingModule.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Transactions.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Users.php");
 */

//replaces above to dynamically call all classes into memory
$files = scandir($_SERVER['DOCUMENT_ROOT']."/class");
for ($i = 3; $i < count($files); $i++) {
    include_once ($_SERVER['DOCUMENT_ROOT']."/class/$files[$i]");
}
date_default_timezone_set($sv['timezone']);
?>