<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */

/**
 * Transactions
 * A ticket is generated every time an operator uses a piece of equipment.
 * @author Jon Le
 */
include_once ($_SERVER['DOCUMENT_ROOT'].'/connections/db_connect8.php');
include_once ($_SERVER['DOCUMENT_ROOT'].'/class/all_classes.php');
include_once '/api/gatekeeper.php';

//$obj = new Transactions();
//$obj->insertTrans(1000129288, 1, "00:00:00", 1, 3);

//$user = Users::withID(1000129288);
//$user = Users::withRF("100055d");
//echo "Role ". $user->getRoleID() ."<br />";
//echo "Title ". Role::getTitle($user->getRoleID());

/*
//Staff Test
$staff = Staff::withID(1000129288);
echo "Role ". $staff->getRoleID() ."<br />";
echo "Title ". Role::getTitle($staff->getRoleID()) ."<br />";
if ( $staff->getIcon() ) 
	echo $staff->getIcon() ."<br />"; 
else 
	echo "user" ."<br />";
$staff = NULL;

    $trans_id = 4;
    $m_id = 1;
    $unit_used = 5.0;
    $status_id = 1;
    $staff_id = "1000129288";
    $mu_notes = "Learner has issue";
*
// Test Email
//SendMail("jonathan.le@mavs.uta.edu", "Test", "Test Message");
function SendMail($to, $subject, $message){
	$headers = 'From: no-reply@fablab.uta.edu' . "\r\n".
				'Reply-To: no-reply@fablab.uta.edu' . "\r\n".
				'X-Mailer: PHP/' . phpversion();
	if ( mail($to, $subject, $message, $headers) ){
		return true;
	} else {
		return false;
	}
}
*/
?>