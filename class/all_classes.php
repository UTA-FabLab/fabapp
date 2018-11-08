<?php
/*
 *   License FabApp v 0.9
 *   2015-2018 CC BY-NC-AS UTA FabLab
 * 
 * Generic call for all classes
 */

//replaces above to dynamically call all classes into memory
$files = scandir($_SERVER['DOCUMENT_ROOT']."/class");
for ($i = 2; $i < count($files); $i++) {
    include_once ($_SERVER['DOCUMENT_ROOT']."/class/$files[$i]");
}
// Tell PHP what time zone before doing any date function foo
date_default_timezone_set($sv['timezone']);
?>