<?php
/*
 * License - FabApp V 0.9
 * 2015-2016 CC BY-NC-AS UTA FabLab
 */

/*
 * Description of Staff an extension of class Users
 * Intended for use when a users logs into FabApp
 * Not-exclusive to employees
 * @author Jon Le
 */

include_once ($_SERVER['DOCUMENT_ROOT']."/class/Users.php");
include_once ($_SERVER['DOCUMENT_ROOT']."/class/site_variables.php");

class Staff extends Users{
    //current url location
    private $loc = null;
    private $timeLimit;
    
    public function __construct() {
        global $sv;
        parent::__construct();
        
        $this->setTimeLimit($sv["limit"]);
    }
    
    public static function withID($operator){
        $instance = new self();
        $instance->createWithID($operator);
        return $instance;
    }
    
    public function setLoc($url){
        $this->loc = $url;
    }
    public function getLoc(){
        return $this->loc;
    }
    
    public function getTimeLimit(){
        return $this->timeLimit;
    }
    
    public function setTimeLimit($limit){
        $this->timeLimit = $limit;
    }
}