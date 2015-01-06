<?
/* process the Epicore RFI form 
- step 1 : saves title, descr and location, get FETPs for next screen
*/
require_once "const.inc.php";
$formvars = json_decode(file_get_contents("php://input"));
$users = '';

// store the title, location and description in db
require_once "EventInfo.class.php";
$dbdata['latlon'] = (string)$formvars->latlon;
$dbdata['location'] = (string)$formvars->location;
$dbdata['title'] = (string)$formvars->title;
$dbdata['description'] = (string)$formvars->description;
$dbdata['requester_id'] = (int)$formvars->uid;
$event_id = isset($formvars->event_id) && is_numeric($formvars->event_id) ? $formvars->event_id : '';
if($event_id) {
    $ei = new EventInfo($event_id);
    $ei->updateEvent($dbdata);
} else {
    $event_id = EventInfo::insertEvent($dbdata);
    $ei = new EventInfo($event_id);
}

// build the email text for the preview screen
$emailtext = $ei->buildEmailForEvent();

// get FETP info for next screen: first get the bounding box from lat/lon
require_once "PlaceInfo.class.php";
list($lat,$lon) = split(",", $dbdata['latlon']);
$radius = (int)$formvars->radius ? (int)$formvars->radius : DEFAULT_RADIUS;
$bbox = PlaceInfo::getBoundingBox($lat, $lon, $radius);

// get the FETPs in that bounding box
require_once "UserInfo.class.php";
$userinfo = UserInfo::getFETPsInLocation('radius', $bbox);
    
print json_encode(array('status' => 'success', 'path' => "request2", 'radius' => $radius, 'userList' => $userinfo[0], 'userIds' => $userinfo[1], 'eventId' => $event_id, 'emailText' => $emailtext));

?>
