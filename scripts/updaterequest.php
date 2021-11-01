<?php
/* process the Epicore RFI form 
 this is called at the end of all 3 pages, so save
 event info and fetp filter info, then send emails
*/

require_once "const.inc.php";
require_once "EventInfo.class.php";
require_once "UserInfo.class.php";
require_once "AWSMail.class.php";
require_once "UserContoller3.class.php";

use UserController as userController;

$userData = userController::getUserData();

$formvars = json_decode(file_get_contents("php://input"));

// update the event info
$event_info['event_id'] = (int)$formvars->event_id;
$event_info['requester_id'] = (int)$userData["uid"];
$event_info['latlon'] = (string)$formvars->latlon;
$event_info['location'] = (string)$formvars->location;
$event_info['title'] = (string)$formvars->title;
$event_info['description'] = (string)$formvars->description;
$event_info['personalized_text'] = $formvars->additionalText ? (string)$formvars->additionalText : '';
$event_info['disease'] = (string)$formvars->disease;

$event_id = EventInfo::updateEvent($event_info);

if ($event_id == $event_info['event_id'])
    print json_encode(array('status' => 'success'));
else
    print json_encode(array('status' => 'failed', 'reason' => $event_id));

?>
