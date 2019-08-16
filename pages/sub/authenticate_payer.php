<?php


/***********************************************************************************************************
*	
*	@author MPZinke
*	CC BY-NC-AS UTA FabLab 2016-2019
*	FabApp V .94
*
*	DESCRIPTION: check if the user (passed through INPUT_GET) is valid to pick up print.  
*	 Echo JSON of whether user is valid
*	FUTURE:
*	BUGS:
*
***********************************************************************************************************/
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

if(isset($_POST["trans_id"]) && isset($_POST["payer"])) {
	$trans_id = $_POST["trans_id"];
	$payer = $_POST["payer"];
	if(Transactions::regexTrans($trans_id) && Users::regexUser($payer)) {
		echo json_encode(array("approved" => AuthRecipients::validatePickUp($trans_id, $payer)));
	}
}

?>