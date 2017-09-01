<?php

/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/connections/db_connect8.php');

if (!empty($_GET["d_id"])) {
    $query = "SELECT * FROM `trainingmodule` WHERE d_id = '".$_GET['d_id']."' ORDER BY `title`";
} elseif (!empty($_GET["dg_id"])) {
    $query = "SELECT * FROM `trainingmodule` WHERE dg_id = '".$_GET['dg_id']."' ORDER BY `title`";
} else {
    echo ("<tr class='tablerow' id='tm'><td colspan=4>ERROR</td>");
} ?>

<?php if ($result = $mysqli->query($query)){
    if ($result->num_rows == 0){
        echo ("<tr class='tablerow' id='tm'><td colspan=4>None</td>");
    } else {
        echo ("<tr class='tablerow' id='tm'><td>Title</td><td>Description</td><td>Duration</td><td>Required</td><td></td></tr>");
        while($row = $result->fetch_assoc()){
            echo ("<tr class='tablerow' id='tm'><td>$row[title]</a></td><td>$row[tm_desc]</td><td>".
                    $row['duration']."</td><td>$row[tm_required]</td><td><button onclick=\"viewTM($row[tm_id],'$row[title]')\">View</button></td></tr>");
        }
    }
} else {
    echo ("<tr class='tablerow' id='tm'><td colspan=4>ERROR</td>");
    echo $mysqli->error;
}?>