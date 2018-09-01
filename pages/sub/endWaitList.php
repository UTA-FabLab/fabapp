<?php

include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');

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
    
        header("Location:/index.php");
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
 
function sendMessage($q_id, $message)
{
    $person = new Wait_queue($q_id);
    Notifications::sendNotification($person->getOperator(), "Fabapp Notification", $message);
}


?>