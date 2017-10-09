<?php
/*
 * Jeff Andre
 * 10/1/2017
 *
 * Checks for duplicate Event (RFI) and returns first duplicate event id if found.
 *
*/

require_once "EventInfo.class.php";

$formvars = json_decode(file_get_contents("php://input"));

// get country
$place = explode(',',(string)$formvars->location);
if (sizeof($place) == 3){
    $country = $place[2];
}
elseif(sizeof($place) == 2){
    $country = $place[1];
}
elseif(sizeof($place) == 1){
    $country = $place[0];
} else {
    $country = false;
}

// Save RFI in database and send to selected members
$dup_event_info = '';
$event_id = -1;
if ($formvars->population_type && $formvars->health_condition && $country) {

    // now minus seven days
    $one_week_ago = strtotime("-7 day");
    $date = date('Y-m-d', $one_week_ago);

    // get population type
    $population_type = $formvars->population_type;

    // get health conditions
    $health_condition = $formvars->health_condition;

    // initialize all conditions to false
    $conditions = array();

    // set conditions to new health conditions
    foreach($health_condition as $key => $val) {
        $conditions[$key] = strip_tags($val);
    }

    // check for duplicate RFI
    $event_id = EventInfo::checkDuplicate($date, $country, $population_type, $conditions);

    // get duplicate event info if found
    if ($event_id[0]) {
        $status = 'success';
        $message = 'duplicate RFI found.';
    } else {
        $status = 'notfound';
        $message = 'duplicate RFI not found.';
    }

} else {
    $status = 'failed';
    $message = 'Missing parameters.';
}

// return first event id if found
print json_encode(array('status' => $status, 'message' => $message, 'event_id' => $event_id[0]));

?>
