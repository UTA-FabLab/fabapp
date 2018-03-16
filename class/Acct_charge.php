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
    private $account;
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
            WHERE `ac_id` = '$ac_id';
        ")){
            if ($result->num_rows == 0 ){
                throw new Exception("AC Not Found : $ac_id");
            }
            $row = $result->fetch_assoc();
        
            $this->setAc_id($row['ac_id']);
            $this->setAccount($row['a_id']);
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
            WHERE `trans_id` = '$trans_id'
            ORDER BY `ac_id` ASC;
        ")){
            while($row = $result->fetch_assoc()){
                array_push( $acArray, new self($row['ac_id']) );
            }
        }
        return $acArray;
        
    }
    
    //Returns an Dictionary of Transactions and their balances
    public static function checkOutstanding($operator){
        global $mysqli;
        global $sv;
        $ac_owed = array();
        $any_outstanding = false;
        
        if(!Users::regexUser($operator)) return "Invalid ID";
        
        if ($result = $mysqli->query("
            SELECT `acct_charge`.`trans_id`, `acct_charge`.`amount`
            FROM `acct_charge`
            WHERE `acct_charge`.`operator` = '$operator' AND `acct_charge`.`a_id` = '1';
        ")){
            while($row = $result->fetch_assoc()){
                //Add together all outstanding charges
                $amt_owed = floatval($row['amount']);
                if (isset($ac_owed[$row['trans_id']])){
                    $ac_owed[$row['trans_id']] += floatval($row['amount']);
                } else {
                    $ac_owed[$row['trans_id']] = floatval($row['amount']);
                }
            }
            foreach (array_keys($ac_owed) as $aco_key){
                //Subtract any charges that are paid with any other account
                if($result = $mysqli->query("
                    SELECT `trans_id`, `amount`
                    FROM `acct_charge`
                    WHERE `trans_id` = '$aco_key' AND `a_id` > '1';
                ")){
                    while($row = $result->fetch_assoc()){
                        $ac_owed[$aco_key] -= floatval($row['amount']);
                    }

                    if ($ac_owed[$aco_key] > 0.005) {
                        $any_outstanding = true;
                    } else {
                        unset($ac_owed[$aco_key]);
                    }
                }
            }
            return $ac_owed;
        }
        //No Balance Owed
    }
    
    public static function insertCharge($ticket, $a_id, $payee, $staff){
        global $mysqli;
        $trans_id = $ticket->getTrans_id();
        $amount = $ticket->quote();
        
        if ($amount < .005){
            return "No Balance Due: ".$amount;
        }
        
        if ($a_id == 1) {
            $acct = new Accounts(1);
            $acct->updateBalance($amount);
            $ac_notes = "Debit Charge";
        } else {
            $ac_owed = Acct_charge::checkOutstanding($ticket->getUser()->getOperator());
            //if ticket has a related acct charge to a_id == 1
            if (isset($ac_owed[$trans_id])){
                //invert the amount owed and reduce the account balance
                $acct = new Accounts(1);
                $acct->updateBalance(-1.0 * $amount);
                if ($mysqli->query("
                    INSERT INTO `acct_charge` 
                        (`a_id`, `trans_id`, `ac_date`, `operator`, `staff_id`, `amount`, `ac_notes`) 
                    VALUES
                        ('1', '$trans_id', CURRENT_TIME(), '$payee','".$staff->getOperator()."', '".-1.0 * $amount."', \"Credit Charge\");
                ")){ } else {
                    return $mysqli->error;
                }
            }
            //insert AC to credit AR
            $ac_notes = NULL;
        }
        
        $s_operator = $staff->getOperator();
        if ($stmt = $mysqli->prepare("
            INSERT INTO `acct_charge` 
                (`a_id`, `trans_id`, `ac_date`, `operator`, `staff_id`, `amount`, `ac_notes`)
            VALUES
                (?, ?, CURRENT_TIME(), ?, ?, ?, ?);
        ")){
            $stmt->bind_param("iissds", $a_id, $trans_id, $payee, $s_operator, $amount, $ac_notes);
            $stmt->execute();
            $ac_id = $mysqli->insert_id;
            
            //Update Account's Balance
            $acct = new Accounts($a_id);
            $acct->updateBalance($amount);
            
            //If all everything is good, Write all attributes of the ticket to the DB
            if ($ticket->writeAttr()){
                return $ac_id;
            } else {
                return "AC163 - Error Writing Ticket ";
            }
        } else {
            return $mysqli->error;
        }
        return false;
    }

    public function getAc_id() {
        return $this->ac_id;
    }

    public function getAc_date() {
        global $sv;
        
        return date($sv['dateFormat'],strtotime($this->ac_date));
    }

    public function getAccount() {
        return $this->account;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function getTrans_id() {
        return $this->trans_id;
    }

    public function getUser() {
        return $this->user;
    }

    public function getStaff() {
        return $this->staff;
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
    
    public static function regexAC($ac_id){
        global $mysqli;
        
        //Check to see if it is all numbers
        if(!preg_match("/^\d+$/", $ac_id)){
            return false;
        }
        
        //Check to see if the record exists
        if ($result = $mysqli->query("
            SELECT *
            FROM `acct_charge`
            WHERE `ac_id` = '$ac_id'
            LIMIT 1;
        ")){
            if ($result->num_rows == 1)
                return true;
            return false;
        } else {
            return false;
        }
    }

    public function setAc_id($ac_id) {
        $this->ac_id = $ac_id;
    }

    public function setAccount($a_id) {
        $this->account = new Accounts($a_id);
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
    
    public function voidPayment($staff){
        global $mysqli;
        
        if ($mysqli->query("
            UPDATE `acct_charge`
            SET `ac_date` = CURRENT_TIMESTAMP,  `staff_id` = '".$staff->getOperator()."',
                `amount` = 0.0, `ac_notes` = 'Void payment'
            WHERE `acct_charge`.`ac_id` = $this->ac_id;
        ")){
            if ($mysqli->affected_rows == 1){
                //Remove amount from Acct balance
                $ticket = new Transactions($this->getTrans_id());
                $amount = $ticket->quote();
                $this->account->updateBalance(-$amount);
            }
        } else {
            return"AC306 ERROR - ".$mysqli->error;
        }
        
        //Void Debit Charge of OutStanding Fine
        if ($result = $mysqli->query("
            SELECT `acct_charge`.`ac_id`, `acct_charge`.`amount`
            FROM `acct_charge`
            WHERE `acct_charge`.`trans_id` = '".$this->getTrans_id()."' AND `acct_charge`.`a_id` = '1'
            ORDER BY `ac_id` DESC;
        ")){
            while($row = $result->fetch_assoc()){
                if ($row['amount'] < .001){
                    if ($mysqli->query("
                        UPDATE `acct_charge`
                        SET `ac_date` = CURRENT_TIMESTAMP,  `staff_id` = '".$staff->getOperator()."',
                            `amount` = 0.0, `ac_notes` = 'Void Payment'
                        WHERE `acct_charge`.`ac_id` = $row[ac_id];
                    ")){
                        if ($mysqli->affected_rows == 1){
                            //Remove amount from Acct balance
                            $ticket = new Transactions($this->getTrans_id());
                            $acct1 = new Accounts(1);
                            $acct1->updateBalance(abs($row['amount']));
                        }
                    } else {
                        return"AC293 ERROR - ".$mysqli->error;
                    }
                }
            }
        }
    }
}
