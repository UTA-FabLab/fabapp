<?php

/*
 * License - FabApp V 0.9
 * 2016-2017 CC BY-NC-AS UTA FabLab
 */

/**
 * Description of Acct_charge
 *
 * @author Jon Le
 */
class Acct_charge {
    private $ac_id;
    private $a_id;
    private $trans_id;
    private $ac_date;
    private $user;
    private $staff;
    private $amount;
    private $recon_date;
    private $recon_id;
    private $ac_notes;
    
    public function __construct($ac_id) {
        global $mysqli;
        
        if (!preg_match("/^\d+$/", $ac_id))
            throw new Exception('Invalid Acct Charge Number');
        
        //query server to 
        if($result = $mysqli->query("
            SELECT *
            FROM `acct_charge`
            WHERE `ac_id` = $ac_id
        ")){ 
            $row = $result->fetch_assoc();
        
            $this->setAc_id($row['ac_id']);
            $this->setA_id($row['a_id']);
            $this->setTrans_id($row['trans_id']);
            $this->setAc_date($row['ac_date']);
            $this->setOperator($row['operator']);
            $this->setStaff_id($row['staff_id']);
            $this->setAmount($row['amount']);
            $this->setRecon_date($row['recon_date']);
            $this->setRecon_id($row['recon_id']);
            $this->setAc_notes($row['ac_notes']);
        }
    }
    
    public static function byTrans_id($trans_id){
        global $mysqli;
        $acArray = array();
        
        if ($result = $mysqli->query("
            SELECT `ac_id`
            FROM `acct_charge`
            WHERE `trans_id` = '$trans_id';
        ")){
            while($row = $result->fetch_assoc()){
                array_push( $acArray, new self($row['ac_id']) );
            }
        }
        return $acArray;
        
    }
    
    public static function insertCharge($a_id, $status_id){
        global $mysqli;
        
        if ($a_id == 1) {
            //set value to negative
        }
    }
    
    public static function checkOutstanding($operator){
        global $mysqli;
        global $sv;
        if(!Users::regexUser($operator)) return "Invalid ID";
        
        if ($result = $mysqli->query("
            SELECT `acct_charge`.`trans_id`, `acct_charge`.`amount`
            FROM `acct_charge`
            WHERE `acct_charge`.`operator` = '$operator' AND `acct_charge`.`a_id` = '1';
        ")){
            while($row = $result->fetch_assoc()){
                $amt_owed = floatval($row['amount']);
                if($result2 = $mysqli->query("
                    SELECT `trans_id`, `amount`
                    FROM `acct_charge`
                    WHERE `trans_id` = '".$row['trans_id']."' AND `a_id` > '1';
                ")){
                    while($row2 = $result2->fetch_assoc()){
                        $amt_owed += floatval($row2['amount']);
                    }

                    if ($amt_owed < 0.00) {
                        return "This users owes <i class='fa fa-$sv[currency] fa-fw'></i>".number_format($amt_owed, 2)." for Ticket ".$row['trans_id'];
                    }
                }
            }
        }
        //No Balance Owed
    }
    
    public static function regexAC($ac_id){
        global $mysqli;
        
        //Check to see if it is all numbers
        if(!preg_match("/^\d+$/", $ac_id)){
            return false;
        }
        
        //Check to see if the record exists
        if ($result = $mysqli->query("
            SELECT *
            FROM transactions
            WHERE trans_id = $trans_id
            LIMIT 1;
        ")){
            if ($result->num_rows == 1)
                return true;
            return false;
        } else {
            return false;
        }
    }

    public function getAc_id() {
        return $this->ac_id;
    }

    public function getA_id() {
        return $this->a_id;
    }

    public function getTrans_id() {
        return $this->trans_id;
    }

    public function getAc_date() {
        global $sv;
        
        return date($sv['dateFormat'],strtotime($this->ac_date));
    }

    public function getUser() {
        return $this->user;
    }

    public function getStaff() {
        return $this->staff;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function getRecon_date() {
        return $this->recon_date;
    }

    public function getRecon_id() {
        return $this->recon_id;
    }

    public function getAc_notes() {
        return $this->ac_notes;
    }

    public function setAc_id($ac_id) {
        $this->ac_id = $ac_id;
    }

    public function setA_id($a_id) {
        $this->a_id = $a_id;
    }

    public function setTrans_id($trans_id) {
        $this->trans_id = $trans_id;
    }

    public function setAc_date($ac_date) {
        $this->ac_date = $ac_date;
    }

    private function setOperator($operator) {
        $user = Users::withID($operator);
        $this->user = $user;
    }

    private function setStaff_id($staff_id) {
        $staff = Users::withID($staff_id);
        $this->staff = $staff;
    }

    public function setStaff($staff) {
        $this->staff = $staff;
    }

    public function setAmount($amount) {
        $this->amount = $amount;
    }

    public function setRecon_date($recon_date) {
        $this->recon_date = $recon_date;
    }

    public function setRecon_id($recon_id) {
        $this->recon_id = $recon_id;
    }

    public function setAc_notes($ac_notes) {
        $this->ac_notes = $ac_notes;
    }
}
