<?php
	session_start();
 
	//remove the id from our cart array
	$key = array_search($_GET['id'], $_SESSION['cart_array']);	
    $_SESSION['co_price'] = number_format((float)($_SESSION['co_price'] - ((($_GET['w']*$_GET['h']) * $_GET['p'])* $_SESSION['co_quantity'][$key])), 2, '.', '');
	unset($_SESSION['cart_array'][$key]);
    unset($_SESSION['co_quantity'][$key]);

	//rearrange array after unset
	$_SESSION['cart_array'] = array_values($_SESSION['cart_array']);
    $_SESSION['co_quantity'] = array_values($_SESSION['co_quantity']);

 
	$_SESSION['success_msg'] = "Product deleted from cart";
	header('Location: /pages/sheet_goods.php');
?>