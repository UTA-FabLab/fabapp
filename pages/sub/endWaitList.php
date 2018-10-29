<?php

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

if (!$staff || $staff->getRoleID() < $sv['LvlOfStaff']){
    //Not Authorized to see this Page
    $_SESSION['error_msg'] = "You are unable to view this page.";
    header('Location: /index.php');
    exit();
}

// Checks
if (isset($_GET['q_id'])) {

    // If the message is set then send a message to the user with this queue ID
    if (isset($_GET['message'])) {
        sendMessage($_REQUEST['q_id'], $_REQUEST['message']);
    }

    // Remove the user from the wait queue
    else {
        removeFromQueue($_REQUEST['q_id']);
    }
    if ($_REQUEST['loc'] == 0) {
        header("Location:/index.php");
    }
    if ($_REQUEST['loc'] == 1) {
        header("Location:/pages/wait_ticket.php");
    }
    
}

function removeFromQueue($q_id) {
    try {
        $queueItem = new Wait_queue($q_id);
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        $_SESSION['type'] = "error";
    }
    
    // Delete the user from the waitlist
    Wait_queue::deleteFromWaitQueue($queueItem);
}
 
function sendMessage($q_id, $message) {
    $message = $message.date($sv['dateFormat'], strtotime("now")+$sv["wait_period"]);
    Notifications::sendNotification($q_id, "FabApp Notification", $message, 1);
}

?>