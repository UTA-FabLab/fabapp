<?php
/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 */

/**
 * Description of Accounts
 *
 * @author Jon Le
 */
class Accounts {
    private $a_id;
    private $name;
    private $description;
    private $balance;
    private $operator;
    
    public function __construct($a_id) {
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $a_id))
            throw new Exception("Invalid Account ID");
        
        if($result = $mysqli->query("
            SELECT *
            FROM `accounts`
            WHERE `a_id` = $a_id
            LIMIT 1;
        ")){
            $row = $result->fetch_assoc();
            $this->setA_id($row['a_id']);
            $this->setName($row['name']);
            $this->setDescription($row['description']);
            $this->setbalance($row['balance']);
            $this->setOperator($row['operator']);
        } else 
            throw new Exception("Invalid Account Constructor");
    }
    
    public static function listAccts($user, $staff){
        global $mysqli;
        global $sv;
        $accounts = array();
        $init = array(1,2);
        
        //Pull available accounts for user
        foreach ($user->getAccounts() as $a){
            array_push($init, $a->getA_id());
        }
        
        if ($staff->getRoleID() >= $sv['ShareAccts']){
            //Pull available accounts for Staff
            foreach ($staff->getAccounts() as $a){
                array_push($init, $a->getA_id());
            }
        }
        
        //Remove any duplicates
        $init = array_unique($init);
        
        if($result = $mysqli->query("
            SELECT *
            FROM `accounts`
            WHERE 1;
        ")){
            while($row = $result->fetch_assoc()){
                if (in_array($row['a_id'],$init) ){
                    array_push($accounts, new Accounts($row['a_id']));
                }
            }
        }
        return $accounts;
    }
    
    public function getA_id() {
        return $this->a_id;
    }

    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getBalance() {
        return $this->balance;
    }

    public function getOperator() {
        return $this->operator;
    }

    public function setA_id($a_id) {
        $this->a_id = $a_id;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    private function setBalance($balance) {
        $this->balance = $balance;
    }

    public function setOperator($operator) {
        $this->operator = $operator;
    }
    
    public function updateBalance($amount){
        global $mysqli;
        
        if ($result = $mysqli->query("
            SELECT `balance`
            FROM `accounts`
            WHERE `a_id` = $this->a_id;
        ")){
            $row = $result->fetch_assoc();
            $balance = $row['balance'] + $amount;
            if ($mysqli->query("
                UPDATE `accounts`
                SET `balance` = '$balance'
                WHERE `a_id` = $this->a_id;
            ")){
                if ($mysqli->affected_rows == 1){
                    return $mysqli->affected_rows;
                } else {
                    return "No Change";
                }
            }
        }
        
    }
}
