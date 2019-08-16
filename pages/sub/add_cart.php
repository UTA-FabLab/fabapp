<?php
	session_start();
 
	//check if product is already in the cart
	if(!in_array($_GET['id'], $_SESSION['cart_array'])){
		array_push($_SESSION['cart_array'], $_GET['id']);
        array_push($_SESSION['co_quantity'], 1);
        
        $_SESSION['co_price'] = number_format((float)((($_GET['w']*$_GET['h']) * $_GET['p'])* $_SESSION['co_quantity'][sizeof($_SESSION['co_quantity'])-1]+$_SESSION['co_price']), 2, '.', '');
        
        
		$_SESSION['success_msg'] = 'Product added to cart';
	}
	else{
		$_SESSION['error_msg'] = 'Product already in cart';
	}
 
	header('Location: /pages/sheet_goods.php');
?>