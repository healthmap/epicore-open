<?php
/*
 * Jeff Andre
 * June 7, 2017
 *
 * Saves RFI info in the database and sends email to selected members
*/

require_once "const.inc.php";
require_once "EventInfo.class.php";
require_once "UserInfo.class.php";
require_once "AWSMail.class.php";
require_once 'ePush.class.php';

$formvars = json_decode(file_get_contents("php://input"));


// Save RFI in database
if ($formvars->uid && $formvars->fetp_ids && $formvars->population && $formvars->health_condition && $formvars->location && $formvars->purpose && $formvars->source) {

    // event info
    $event_info['latlon'] = (string)$formvars->location->latlon;
    $event_info['location'] = (string)$formvars->location->location;
    $event_info['location_details'] = (string)$formvars->location->location_details;
    $event_info['requester_id'] = (int)$formvars->uid;
    $event_info['search_countries'] = $formvars->search_countries ? $formvars->search_countries : '';
    $event_info['search_box'] = $formvars->search_box ? $formvars->search_box : '';
    $event_info['create_date'] = date('Y-m-d H:i:s');
    $event_info['event_date'] = date_format(date_create($formvars->location->event_date), "Y-m-d");
    $event_info['event_date_details'] = (string)$formvars->location->event_date_details;


    // related tables
    $event_table['health_condition'] = $formvars->health_condition;
    $event_table['population'] = $formvars->population;
    $event_table['purpose'] = $formvars->purpose;
    $event_table['source'] = $formvars->source;

    // insert event into database
    //$event_status = EventInfo::insertEvent2($event_info, $event_table);

    $status = 'success';
} else {

    $status = 'Missing parameters.';

}


// Send email to selected members

print json_encode(array('status' => $status, 'event'=>$event_info, 'event_table' =>$event_table, 'event_status' => $event_status));
exit;


?>
