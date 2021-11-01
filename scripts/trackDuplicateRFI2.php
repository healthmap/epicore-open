<?php
/*
 * Jeff Andre
 * 10/6/2017
 *
 * Track duplicate Events (RFI).
 * Returns success status if following events.
 *
*/

require_once "EventInfo.class.php";

$formvars = json_decode(file_get_contents("php://input"));
$status = 'success';
$message = '';
if ($formvars->event_ids && $formvars->user_id) {

    $follow_event_ids = $formvars->event_ids;
    $user_id = $formvars->user_id;

    foreach ($follow_event_ids as $event_id ) {
        // follow event
        $following = EventInfo::followEvent($event_id, $user_id);

        // get duplicate event info if found
        if ($following['status']) {
            $status = 'success';
            $message = $following['message'];
        } else {
            $status = 'failed';
            $message = $following['message'] . " For event id: " . $event_id;
            break;
        }
    }

} else {
    $status = 'failed';
    $message = 'Missing parameters.';
}

print json_encode(array('status' => $status, 'message' => $message));

?>
