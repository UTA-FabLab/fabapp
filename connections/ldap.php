<?php

/*
 *  ldap.php : LDAP test
 *   FabApp v 0.91
 *
*/

//local function for testing & bypass
function AuthenticateUser($netid, $password) {
    global $sv;
    
    $attribute = 'utaEmplID';
    $ldap_server = 'ldaps://ldap.cedar.uta.edu';
    $ldap_baseDN = 'cn=accounts,dc=uta,dc=edu';
    $ldap_bindDN = "uid=$netid,cn=accounts,dc=uta,dc=edu";
    
    //switch case to return roles
    switch ($netid){
        case "learner":
            return 1000000002;
        case "staff":
            return 1000000008;
        case "lead":
            return 1000000009;
        case "admin":
            return 1000000010;
        case "super":
            return 1000845009;
    }
}
?>
