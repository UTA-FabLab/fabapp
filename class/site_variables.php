<?php
$sv = array();
if($result = $mysqli->query("
    SELECT *
    FROM site_variables
")){
    while( $row = $result->fetch_assoc() ) {
        $sv[$row["name"]] = $row["value"];
    }
        $result->close();
} else {
	$message = $mysqli->error;
}

class Site_Variables {
    private $id;
    private $name;
    private $value;
    private $notes;
    
    public function __construct($id) {
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $id))
            {throw new Exception('Invalid SV Number');}
        if ($result = $mysqli->query("
            SELECT *
            FROM `site_variables`
            WHERE `id` = $id
            LIMIT 1;
        ")){
            $row = $result->fetch_assoc();
            $this->setId($row['id']);
            $this->setName($row['name']);
            $this->setValue($row['value']);
            $this->setNotes($row['notes']);
        }
    }
    
    public static function getALL() {
        global $mysqli;
        $sv_array = array();
        
        if ($result = $mysqli->query("
            SELECT *
            FROM `site_variables`
            WHERE 1;
        ")){
            while($row = $result->fetch_assoc()){
                array_push( $sv_array, new self($row['id']) );
            }
        }
        return $sv_array;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getValue() {
        return $this->value;
    }

    public function getNotes() {
        return $this->notes;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setValue($value) {
        $this->value = $value;
    }

    public function setNotes($notes) {
        $this->notes = $notes;
    }

    public function updateValue($value) {
        global$mysqli;
        
        $id = $this->getId();
        
        if ($stmt = $mysqli->prepare("
            UPDATE `site_variables`
            SET `value` = ?
            WHERE `id` = ?
        ")){
            $bind_param = $stmt->bind_param("si", $value, $id);
            $status = $stmt->execute();
            $stmt->close();
            return $status;
        } else {
            //return "Error in stating Materials Used.";
            return $mysqli->error;
        }
    }
}
?>