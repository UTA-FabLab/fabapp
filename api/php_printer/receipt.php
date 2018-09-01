<?php

require 'autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

/*
 *  receipt.php : RESTful interface for thermal printer
 *
 *  Arun Kalahasti & Jonathan Le, FabLab Dev
 *	FabLab @ University of Texas at Arlington
 
 *  version: 0.2 alpha (2017-06-02)
 *
*/

// Prints then replies via JSON data exchange
// =======================================
// Header:
//	FabLab logo
//	Current Date and Time
//	Transaction ID
// =======================================
// Receipt types:
// 1) Transaction ID only
// 2) Start Ticket
//		- Filename
//		- Estimated Time
// 3) End Ticket
//		- Filename
//		- Cost
//		- Filament Used
//		- Time Ticket Opened
//		- Time Ticket Closed
//		- Actual Ticket Duration
// =======================================
// Footer:
//	Barcode:
//		Transaction ID
//	QR Code:
//		Unique Transaction URL
//	Website URL
//	Phone Number


//require_once( __DIR__."/../../connections/tp_connect.php");
require_once($_SERVER['DOCUMENT_ROOT']."/connections/tp_connect.php");

$json_out = array();

// Set up printer
try {
	$connector = new NetworkPrintConnector( $tphost, $tpport);
	$printer = new Printer($connector);
} catch (Exception $setup_error) {
	$json_out["ERROR"] = $setup_error->getMessage();
	ErrorExitR(2);
}

//$input_data = array( "type" => "start_ticket", "trans_id" => 10000,"filename" => "Test Script", "est_cost" => "0.00",
//					"est_material" => "Test Material", "est_duration" => "00:00:00", "m_name" => "Test Material",
//					"device_name" => "Test Machine");

// Input posted with "Content-Type: application/json" header
$input_data = json_decode(file_get_contents('php://input'), true);
if (! ($input_data)) {
	$json_out["ERROR"] = "Unable to decode JSON message - check syntax";
	ErrorExitR(1);
}

// Tell PHP what time zone before doing any date function foo 
date_default_timezone_set('America/Chicago');

// Extract message type and transaction id from incoming JSON
$trans_id	= $input_data["trans_id"];
if (! ($trans_id)) {
	$json_out["ERROR"] = "trans_id key not found";
	ErrorExitR(2);
}
$type		= $input_data["type"];
if (! ($type)) {
	$type = "trans_id";
}

// Print Header
PrintHeader();

try {
	// Check the request type
	if (strtolower($type) == "trans_id") {
		// No main body
	} elseif (strtolower($type) == "start_ticket") {
		$filename = $input_data["filename"];
		$est_cost = $input_data["est_cost"];
		$est_material = $input_data["est_material"];
		$est_duration = $input_data["est_duration"];
		$filament_type = $input_data["m_name"];
		$device_name = $input_data["device_name"];
		StartTicket($filename, $est_cost, $est_material, $est_duration, $filament_type, $device_name);
	} elseif (strtolower($type) == "end_ticket") {
		$filename = $input_data["filename"];
		$cost = $input_data["cost"];
		$filament_used = $input_data["filament_used"];
		$time_opened = $input_data["time_opened"];
		$time_closed = $input_data["time_closed"];
		$duration = $input_data["duration"];
		EndTicket($filename,$cost,$filament_used,$time_opened,$time_closed,$duration);
	} else {
		$json_out["ERROR"] = "Unknown type: $type";
		ErrorExitR(1);
	}
} catch (Exception $print_error) {
	$json_out["ERROR"] = $print_error->getMessage();
	ErrorExitR(1);
}

// Print Footer
PrintFooter();

// Close Printer Connection
try {
	
	$printer -> feed();
    $printer -> cut();
    
    /* Close printer */
    $printer -> close();
	
} catch (Exception $e) {
    echo "Couldn't print to this printer: " . $e -> getMessage() . "\n";
}

// Output JSON and exit
header("Content-Type: application/json");
echo json_encode($json_out);
exit(0);


////////////////////////////////////////////////////////////////
//                           Functions
////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////
//
//  EndTicket
//  Prints end tran ticket. Contains all possible information.

function EndTicket ($filename,$cost,$filament_used,$time_opened,$time_closed,$duration) {
    global $json_out;
	global $printer;
	
	try {
		
		$printer -> feed();
		$printer -> text("Filename:  ".$filename);
		$printer -> feed();
		$printer -> text("Cost:  $".$cost);
		$printer -> feed();
		$printer -> text("Filament Used:  ".$filament_used);
		$printer -> feed();
		$printer -> text("Time Started:  ".$time_opened);
		$printer -> feed();
		$printer -> text("Time Ended:  ".$time_closed);
		$printer -> feed();
		$printer -> text("Duration:  ".$duration);
		$printer -> feed();
		
		$printer -> feed();
		
	} catch (Exception $print_error) {
		$json_out["ERROR"] = $print_error->getMessage();
		ErrorExitR(1);
	}
}

////////////////////////////////////////////////////////////////
//
//  StartTicket
//  Prints start ticket. 

function StartTicket ($filename, $est_cost, $est_material, $est_duration, $filament_type, $device_name) {
    global $json_out;
	global $printer;
	
	try {
		$printer -> feed();
		$printer -> text("Device:   ".$device_name);
		$printer -> feed();
		$printer -> text("Color:   ".$filament_type);
		$printer -> feed();
		$printer -> text("Est. Amount:   ".$est_material);
		$printer -> feed();
		$printer -> text("Est. Cost:   ");
		$printer -> text("$ ".number_format($est_cost,2));
		$printer -> feed();
		$printer -> text("Est. Duration:   ".$est_duration);
		$printer -> feed();
		$printer -> text("File:   ".$filename);
		$printer -> feed(3);
		$printer -> text("Address: ______________________");
		$printer -> feed();
		
	} catch (Exception $print_error) {
		$json_out["ERROR"] = $print_error->getMessage();
		ErrorExitR(1);
	}
}

////////////////////////////////////////////////////////////////
//
//  PrintHeader
//  Prints Generic Header

function PrintHeader () {
    global $json_out;
	global $printer;
	global $trans_id;
	
	try {
		$img = EscposImage::load("../../images/fablab2.png", 0);
		
		$printer -> setJustification(Printer::JUSTIFY_CENTER);
		$printer -> graphics($img);
		$printer -> feed();
		$printer -> text(date("F j, Y h:i A"));
		$printer -> feed();
		$printer -> text("Ticket: " . $trans_id);
		$printer -> feed();
		
	} catch (Exception $print_error) {
		$json_out["ERROR"] = $print_error->getMessage();
		ErrorExitR(1);
	}
}


////////////////////////////////////////////////////////////////
//
//  PrintFooter
//  Prints Generic Footer

function PrintFooter () {
    global $json_out;
	global $printer;
	global $trans_id;
	
	$qr = "http://fabapp.uta.edu/look.php?trans_id=".$trans_id;
	
	try {
		
		$printer -> feed();
		//$printer -> qrCode($qr, Printer::QR_ECLEVEL_L, 5, Printer::QR_MODEL_2);
		//$printer -> feed();
		//$printer->setBarcodeTextPosition(Printer::BARCODE_TEXT_BELOW);
		//$printer->barcode( (string)$trans_id, Printer::BARCODE_CODE39);
		//$printer -> feed();
		$printer -> text("Potential Problems?  ( Y )  ( N )");
		$printer -> feed();
		$printer -> text("NOTES: _________________________");
		$printer -> feed(2);
		$printer -> text("________________________________");
		$printer -> feed(2);
		$printer -> text("________________________________");
		$printer -> feed(3);
		//$printer -> text("|______________|________________|");
		//$printer -> feed();
		//$printer ->setFont(Printer::FONT_B);
		//$printer -> text("learner            staff   ");
		$printer -> graphics(EscposImage::load("../../images/sig.png", 0));
		$printer ->setFont();
		$printer -> feed();
		$printer -> text("http://fablab.uta.edu/");
		$printer -> feed();
		$printer -> text("(817) 272-1785");
		$printer -> feed();
		
	} catch (Exception $print_error) {
		$json_out["ERROR"] = $print_error->getMessage();
		ErrorExitR(1);
	}
}

////////////////////////////////////////////////////////////////
//
//  ErrorExitR
//  Sends error message and quits 


function ErrorExitR ($exit_status) {
    global $json_out;
	global $printer;
	
	if ($printer) {
		try {
			// Print Error
			$printer -> text("ERROR:\n");
			$printer -> text(prettyPrint(json_encode($json_out)));
			
			// Feed and cut ticket
			$printer -> feed();
			$printer -> cut();
		} catch (Exception $fatal_error) {
			$json_out["ERROR"] = $fatal_error->getMessage() . " Crashed during error printing";
		}
		// Close printer
		$printer -> close();
	}
	
    header("Content-Type: application/json");
    echo json_encode($json_out);
	
	
    exit($exit_status);
}


////////////////////////////////////////////////////////////////
//
//  prettyPrint
//  This function will take JSON string and indent it very readable.
//
//	Code created with the help of Stack Overflow question
//	http://stackoverflow.com/questions/6054033/pretty-printing-json-with-php
//	Question by Zach Rattner:
//	http://stackoverflow.com/users/371408/zach-rattner
//	Answer by Kendall Hopkins:
//	http://stackoverflow.com/users/188044/kendall-hopkins

function prettyPrint( $json )
{
    $result = '';
    $level = 0;
    $in_quotes = false;
    $in_escape = false;
    $ends_line_level = NULL;
    $json_length = strlen( $json );

    for( $i = 0; $i < $json_length; $i++ ) {
        $char = $json[$i];
        $new_line_level = NULL;
        $post = "";
        if( $ends_line_level !== NULL ) {
            $new_line_level = $ends_line_level;
            $ends_line_level = NULL;
        }
        if ( $in_escape ) {
            $in_escape = false;
        } else if( $char === '"' ) {
            $in_quotes = !$in_quotes;
        } else if( ! $in_quotes ) {
            switch( $char ) {
                case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;

                case '{': case '[':
                    $level++;
                case ',':
                    $ends_line_level = $level;
                    break;

                case ':':
                    $post = " ";
                    break;

                case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
            }
        } else if ( $char === '\\' ) {
            $in_escape = true;
        }
        if( $new_line_level !== NULL ) {
            $result .= "\n".str_repeat( "\t", $new_line_level );
        }
        $result .= $char.$post;
    }

    return $result;
}

?>