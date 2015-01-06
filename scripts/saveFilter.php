<?php
/* process the Epicore RFI form 
- part of step 2 : SAVES the filters of FETPs chosen 
*/
$formvars = json_decode(file_get_contents("php://input"));

$event_id = $formvars->event_id;
if(!is_numeric($event_id)) {
    print json_encode(array('status' => 'error', 'reason' => 'Invalid event id'));
    exit;
}

$search_info['search_countries'] = $formvars->search_countries ? join(",", $formvars->search_countries) : '';
$search_info['search_box'] = $formvars->search_box ? join(",", $formvars->search_box) : '';
$search_info['search_radius'] = $formvars->search_radius ? $formvars->search_radius : '';

// update the event to have the correct filter info
require_once "EventInfo.class.php";
$ei = new EventInfo($event_id);
$ei->updateEventFilterInfo($search_info);

print json_encode(array('status' => 'success'));

?>
