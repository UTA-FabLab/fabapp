 <?php
/*
 *   CC BY-NC-AS UTA FabLab 2015-2016 
 */
 
/*
 * Role
 * 
 * @author Jon Le
 */
class Role{
	
    public static function getTitle($r_id){
        global $mysqli;

        if (preg_match("/^\d+$/",$r_id) == 0) {
            echo "Invalid RoleID - $r_id";
            return false;
        }

        if ($result = $mysqli->query("
            SELECT title
            FROM Role
            WHERE r_id = $r_id
            Limit 1;
        ")){
            $row = $result->fetch_assoc();
            return $row["title"];
        } else {
            echo mysqli_error($mysqli);
        }
    }
	
    public static function listRoles(){
        global $mysqli;

        if ($result = $mysqli->query("
            SELECT r_id, title
            FROM Role
            WHERE 1;
        ")){
            return $result;
        } else {
            echo mysqli_error($mysqli);
        }
    }
}
 ?>