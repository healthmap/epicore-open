<?php
require_once "const.inc.php";
require_once "EventInfo.class.php";

// clean variables
$formvars = json_decode(file_get_contents("php://input"));
$eid = strip_tags($formvars->eid);
$superuser = strip_tags($formvars->superuser);

// exit if no event id or user id
if(!$eid || !$superuser) {
    print json_encode(array('status' => 'failed', 'reason' => 'missing required parameters or not a superuser'));
    exit;
}

$event_info = EventInfo::deleteEvent2($eid);
$status = $event_info[0];
$message = $event_info[1];

if($status == 'success') {
    print json_encode(array('status' => 'success'));
} else {
    print json_encode(array('status' => 'failed', 'reason' => $message));
}

?>
