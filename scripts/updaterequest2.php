<?php
/* process the Epicore RFI form 
 this is called at the end of all 3 pages, so save
 event info and fetp filter info, then send emails
*/

require_once "const.inc.php";
require_once "EventInfo.class.php";
require_once "UserInfo.class.php";
require_once "AWSMail.class.php";

$formvars = json_decode(file_get_contents("php://input"));

//event info
$event_info['event_id'] = (int)$formvars->event_id;
$event_info['requester_id'] = (int)$formvars->uid;
$event_info['latlon'] = (string)$formvars->location->latlon;
$event_info['location'] = (string)$formvars->location->location;
$event_info['location_details'] = (string)$formvars->location->location_details;
$event_info['event_date'] = date_format(date_create($formvars->location->event_date), "Y-m-d");
$event_info['event_date_details'] = (string)$formvars->location->event_date_details;
$event_info['title'] = (string)$formvars->title;

// related tables
$event_table['health_condition'] = $formvars->health_condition;
$event_table['population'] = $formvars->population;
$event_table['purpose'] = $formvars->purpose;
$event_table['source'] = $formvars->source;


$event_id = EventInfo::updateEvent2($event_info, $event_table);

$status = "success";

if ($event_id == $event_info['event_id'])
    print json_encode(array('status' => $status));
else
    print json_encode(array('status' => 'failed', 'reason' => $event_id));

// print json_encode(array('status' => $status));

?>
