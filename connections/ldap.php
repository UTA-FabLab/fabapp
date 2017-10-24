<?php

/*
 *  ldap.php : LDAP test
 *
 *
*/

//local function for testing & bypass
function AuthenticateUser($netid, $password) {
    $attribute = '';
    $ldap_server = '';
    $ldap_baseDN = '';
    $ldap_bindDN = "";

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
                @ldap_free_result($result);
                if (!$entry) {
                    throw new Exception(@ldap_error($connection), @ldap_errno($connection));
                }
                // Grab attribute value from record
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
