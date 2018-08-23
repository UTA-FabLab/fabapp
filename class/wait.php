<?php

/*
 * License - FabApp V 0.9
 * 2016-2018 CC BY-NC-AS UTA FabLab
 */

/**
 * Description of wait
 *
 * @author Jon Le
 */
require_once ($_SERVER['DOCUMENT_ROOT'].'/api/php_printer/autoload.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/connections/tp_connect.php');
date_default_timezone_set('America/Chicago');

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;


function wait($str){
    global $tp;
    global $sv;

    // Set up Printer
    try {
        $tpn = 0;
        $connector = new NetworkPrintConnector( $tp[$tpn][0], $tp[$tpn][1]);
        $printer = new Printer($connector);
    } catch (Exception $e) {
        return "Couldn't print to this printer: " . $e -> getMessage() . "\n";
    }

    //header
    try {
        $img = EscposImage::load($_SERVER['DOCUMENT_ROOT']."/images/fablab2.png", 0);

        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> graphics($img);
        $printer -> feed();
        $printer -> setEmphasis(true);
        $printer -> text(date("F jS Y h:i A"));
        $printer -> setEmphasis(false);
        $printer -> feed();

    //Wait Tab Number
        $printer -> setTextSize(4, 4);
        $printer -> text($str);
        $printer -> setTextSize(1, 1);
        $printer -> feed();
        if(preg_match("/^E\d+/", $str)) {
            $printer -> text("Epilog Laser Wait-Tab");
        } elseif(preg_match("/^B\d+/", $str)) {
            $printer -> text("Boss Laser Wait-Tab");
        } elseif(preg_match("/^M\d+/", $str)) {
            $printer -> text($sv['misc']." Wait-Tab");
        } elseif(preg_match("/^\d+$/", $str)) {
            $printer -> text("PolyPrinter Wait-Tab");
        }
        $printer -> feed(2);

    //body
        $printer -> setJustification(Printer::JUSTIFY_LEFT);
        $printer -> setEmphasis(true);
        $printer -> text("1. ");
        $printer -> setEmphasis(false);
        $printer -> text("Check http://fabapp.uta.edu for the\n");
        $printer -> text("lastest status on who's being called and\n");
        $printer -> text("get an alert.\n");
        $printer -> feed();

        $printer -> setEmphasis(true);
        $printer -> text("2. ");
        $printer -> setEmphasis(false);
        $printer -> text("FabApp only gives estimates & more\n");
        $printer -> text("than one machine may become available\n");
        $printer -> text("at a time. Stay in the lab if your\n");
        $printer -> text("number is close.\n");
        $printer -> feed();

        $printer -> setEmphasis(true);
        $printer -> text("3. ");
        $printer -> setEmphasis(false);
        $printer -> text("Prep your files while you wait to\n");
        $printer -> text("reduce lag time.\n");
        $printer -> feed();
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> text("Potential Problems?  ( Y )  ( N )");
        $printer -> feed();
        $printer -> graphics(EscposImage::load($_SERVER['DOCUMENT_ROOT']."/images/sig.png", 0));
        $printer -> feed();


        // Print Footer
        $printer -> feed();
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> text("Our full waiting list policy can be\n");
        $printer -> text("viewed at ");
        $printer -> setEmphasis(true);
        $printer -> text("http://fablab.uta.edu/policy");
        $printer -> feed();

    } catch (Exception $print_error) {
        //echo $print_error->getMessage();
        $printer -> text($print_error->getMessage());
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