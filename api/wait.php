<?php
require_once ($_SERVER['DOCUMENT_ROOT'].'/api/php_printer/autoload.php');
require_once ($_SERVER['DOCUMENT_ROOT']."/connections/tp_connect.php");
date_default_timezone_set('America/Chicago');

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

function wait(){
	global $tphost;
	global $tpport;

	// Set up Printer
	try {
		$connector = new NetworkPrintConnector( $tphost, $tpport);
		$printer = new Printer($connector);
	} catch (Exception $setup_error) {
		$json_out["ERROR"] = $setup_error->getMessage();
	}

	//header
	try {
		$img = EscposImage::load("../../images/fablab2.png", 0);
		
		$printer -> setJustification(Printer::JUSTIFY_CENTER);
		$printer -> graphics($img);
		$printer -> feed();
		$printer -> text(date("F jS Y h:i A"));
		$printer -> feed();
		//$printer -> text("Ticket: " . $trans_id);
		$printer -> text("Wait-Tab: 000");
		$printer -> feed();
		
	//body
		$printer -> feed();
		$printer -> text("3D Printer");

	// Print Footer
		$printer -> feed();
		$printer -> text("http://fablab.uta.edu/");
		$printer -> feed();
		
	} catch (Exception $print_error) {
		$json_out["ERROR"] = $print_error->getMessage();
	}

	// Close Printer Connection
	try {
		
		$printer -> feed();
		$printer -> cut();
		
		/* Close printer */
		$printer -> close();
		
	} catch (Exception $e) {
		echo "Couldn't print to this printer: " . $e -> getMessage() . "\n";
	}
}
?>