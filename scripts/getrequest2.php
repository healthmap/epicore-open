<?php
/*
* Jeff Andre, 7/7/2017
* get request by event id
*/
require_once "const.inc.php";
$formvars = json_decode(file_get_contents("php://input"));



// get info about specific event
if(isset($formvars->event_id) && is_numeric($formvars->event_id)) {
    // get the event
    require_once "db.function.php";
    $db = getDB();
    require_once "EventInfo.class.php";
    $ei = new EventInfo($formvars->event_id);
    $event_info = $ei->getEvent2();
    if (!$event_info){
        echo json_encode(array('status' => 'failed', 'reason' => 'invalid event id'));
        exit;
    }
} else {
    echo json_encode(array('status' => 'failed', 'reason' => 'missing required fields'));
    exit;
}

echo json_encode($event_info);
exit;

?>
