<?php
 
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/connections/db_connect8.php');
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/class/all_classes.php');



if (!empty($_GET["val"])) {
    $value = $_GET["val"];

        sscanf($value, "%d", $dg_id);

        echo ("dg_id = $dg_id");

        if ($dg_id !="" && $dg_id !="2" && DeviceGroup::regexDgID($dg_id)) {
            // Select all of the MAV IDs that are waiting for this device group
            $result = $mysqli->query ( "
                SELECT DISTINCT D.`device_desc`, D.`d_id`
                FROM `devices` D, `transactions` T
                WHERE D.`dg_id`=$dg_id AND T.`d_id`=D.`d_id` AND T.`t_end` IS NULL
                ORDER BY `device_desc`
            " );
	
            echo "<select>";
            while($row = mysqli_fetch_array($result))
            {
                echo '<option value="'.$row["d_id"].'">'; echo $row["device_desc"]; echo "</option>";
            }
            if (mysqli_num_rows($result)==0) {
            echo "<option disabled>"; echo "A Device Is Available To Use"; echo "</option>";  
            }
            echo "</select>";
            
        
        }
        else{
            $result = $mysqli->query ( "
                SELECT DISTINCT D.`device_desc`, D.`d_id`
                FROM `devices` D, `transactions` T
                WHERE D.`dg_id`=2 AND T.`t_end` IS NULL AND T.`d_id`=D.`d_id`
                ORDER BY `device_desc`
            " );
            
            echo "<select>";
            
            if (mysqli_num_rows($result) < 9) {
                echo "<option disabled>"; echo "A Printer Is Available To Use"; echo "</option>";  
            }
            else{
                echo "<option>"; echo "No Selection Needed"; echo "</option>";  
            }
           
            echo "</select>";
        }
    
}

?>

<?php
// Standard call for dependencies
include_once ($_SERVER ['DOCUMENT_ROOT'] . '/pages/footer.php');
?>