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
        case "fablab_leads":
            if ($password == $sv['backdoor_pass']){
                return "1000000000";
            } else {
                return "Invalid Password";
            }
        case "polyprinter":
            if ($password == $sv['service_pass']){
                return "0009051523";
            } else {
                return "Invalid Password";
            }
        default:{
            try {
                // Connect
                $connection = @ldap_connect($ldap_server);
                if (!$connection) {
                    throw new Exception(sprintf("Can't connect to '%s'.", $ldap_server), 0x5b);
                }
                // Bind 
                if(!@ldap_bind($connection,$ldap_bindDN,$password)) {
                    throw new Exception(@ldap_error($connection), @ldap_errno($connection));
                }
                // Search
                $result = @ldap_search($connection, $ldap_baseDN, "uid=" . $netid);
                if (!$result) {
                    throw new Exception(@ldap_error($connection), @ldap_errno($connection));
                }
                // Select first record in result (should only be one)
                $entry = @ldap_first_entry($connection, $result);
                //DO NOT ATTEMPT TO CLEAR $result UNTIL SOMETIME AFTER THE if() STATEMENT
                if (!$entry) {
                    throw new Exception(@ldap_error($connection), @ldap_errno($connection));
                }
                // Grab attribute value from record
                @ldap_free_result($result);             //DO NOT PUT THIS RIGHT NEXT TO THE ldap_search() CALL, CRASHES SERVER
                $uta_id = @ldap_get_values($connection, $entry, $attribute);
                if (!$uta_id) {
                    //throw new Exception(@ldap_error($connection), @ldap_errno($connection));
                    return $attribute .": ". ldap_error($connection);
                } else {
                        $value = $uta_id[0];
                    return $value;
                }
            } catch (Exception $e){
                return "$e";
            }
            
        }
    }
}
?>
