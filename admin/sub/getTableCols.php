<?php  

include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

if (!empty(filter_input(INPUT_GET, "table_id"))) {
	$table = new Table(filter_input(INPUT_GET, "table_id")); //
	echo json_encode($table->get_columns());
 }?>