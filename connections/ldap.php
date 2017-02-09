<?php

/*
 *  ldap.php : LDAP test 
 *
 *
*/

//local function for testing & bypass
function AuthenticateUser($netid, $password, $attribute) {
    //return false;
    
    //switch case to return roles
    switch ($netid){
        case "learner":
            return "1000000002";
        case "community":
            return "1000000004";
        case "service":
            return "1000000007";
        case "staff":
            return "1000000008";
        case "super":
            return "1000000009";
        case "admin":
            return "1000000010";
        default:
            return "1000000001";
    }
}
?>