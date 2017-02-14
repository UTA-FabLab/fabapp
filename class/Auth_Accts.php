<?php
/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 */

/**
 * Description of Auth_Accts
 *
 * @author Jon Le
 */

include_once ($_SERVER['DOCUMENT_ROOT']."/class/Accounts.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/Users.php");

class Auth_Accts {
    private $aa_id;
    private $account;
    private $user;
    private $valid;
    private $aa_date;
    private $staff;
    
    public function __construct($aa_id) {
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $aa_id))
            throw new Exception ("Invalid Operator ID");
        
        if($result = $mysqli->query("
            SELECT *
            FROM `auth_accts`
            WHERE `aa_id` = $aa_id
            LIMIT 1;
        ")){
            $row = $result->fetch_assoc();
            $this->setAa_id($row['aa_id']);
            $this->setAccount($row['a_id']);
            $this->setUser($row['operator']);
            $this->setValid($row['valid']);
            $this->setAa_date($row['aa_date']);
            $this->setStaff($row['staff_id']);
        } else 
            throw new Exception ("Invalid Auth Acct's Search");
    }
    
    public function getAa_id() {
        return $this->aa_id;
    }

    public function getAccount() {
        return $this->account;
    }

    public function getUser() {
        return $this->user;
    }

    public function getValid() {
        return $this->valid;
    }

    public function getAa_date() {
        return $this->aa_date;
    }

    public function getStaff() {
        return $this->staff;
    }

    public function setAa_id($aa_id) {
        $this->aa_id = $aa_id;
    }

    public function setAccount($account) {
        $this->account = $account;
    }

    public function setUser($operator) {
        $this->user = new Users($operator);
    }

    public function setValid($valid) {
        $this->valid = $valid;
    }

    public function setAa_date($aa_date) {
        $this->aa_date = $aa_date;
    }

    public function setStaff($staff_id) {
        $this->staff = new Users($staff_id);
    }
}
