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
    if($netid == 'super') return "1000845009";
    if($netid == 'admin') return "1000000010";
    if($netid == 'lead') return "1000000009";
    if($netid == 'staff') return "1000000008";
    if($netid == 'service') return "1000000007";
    if($netid == 'learner') return "1000000002";
}
?>
