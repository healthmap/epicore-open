<?php
/*
 * Jeff Andre
 * 10/6/2017
 *
 * Track duplicate Event (RFI).
 * Returns success status if following event.
 *
*/

require_once "EventInfo.class.php";

$formvars = json_decode(file_get_contents("php://input"));

if ($formvars->event_id && $formvars->user_id) {

    $follow_event_id = $formvars->event_id;
    $user_id = $formvars->user_id;

    // follow event
    $following = EventInfo::followEvent($follow_event_id, $user_id);

    // get duplicate event info if found
    if ($following['status']) {
        $status = 'success';
        $message = $following['message'];
    } else {
        $status = 'failed';
        $message = $following['message'];
    }

} else {
    $status = 'failed';
    $message = 'Missing parameters.';
}

print json_encode(array('status' => $status, 'message' => $message));

?>
