<?php
/*
 * Jeff Andre
 * 10/1/2017
 *
 * Checks for duplicate Event (RFI) and returns duplicates (event ids) if found.
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

$health_conditions = (array) $formvars->health_condition;

// get duplicates (if any)
if ($formvars->population_type && $country && $health_conditions) {
    
    // now minus seven days
    $one_week_ago = strtotime("-7 day");
    $date = date('Y-m-d', $one_week_ago);

    // get population type
    $population_type = $formvars->population_type;

    // check for duplicate RFIs
    $events = EventInfo::checkDuplicate($date, $country, $population_type, $health_conditions);
    // $events = EventInfo::checkDuplicate2($date, $country, $population_type);

    // get duplicate events if found
    if ($events) {
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
print json_encode(array('status' => $status, 'message' => $message, 'events' => $events));

?>
