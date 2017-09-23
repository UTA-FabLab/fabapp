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
    private $acct;
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
            $this->setAcct($row['acct']);
            $this->setbalance($row['balance']);
            $this->setOperator($row['operator']);
        } else 
            throw new Exception("Invalid Account Constructor");
    }
    
    public function getA_id() {
        return $this->a_id;
    }

    public function getName() {
        return $this->name;
    }

    public function getAcct() {
        return $this->acct;
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

    public function setAcct($acct) {
        $this->acct = $acct;
    }

    private function setBalance($balance) {
        $this->balance = $balance;
    }

    public function setOperator($operator) {
        $this->operator = $operator;
    }
}
