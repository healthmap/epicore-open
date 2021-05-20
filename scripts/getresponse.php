<?php
/* 
get response by response id
pass in fetp_id if it's from the fetp view
pass in the uid if it's from the mod view
*/
require_once "const.inc.php";
require_once "EventInfo.class.php";
require_once "UserContoller3.class.php";

use UserController as userController;

$userData = userController::getUserData();

$formvars = json_decode(file_get_contents("php://input"));

$response_id = isset($formvars->response_id) ? $formvars->response_id : '';
$uid = isset($userData["uid"]) ? $userData["uid"] : '';
$fetp_id = isset($userData["fetp_id"]) ? $userData["fetp_id"] : '';

if(!is_numeric($response_id) || (!is_numeric($uid) && !is_numeric($fetp_id))) {
    print json_encode(array('status' => 'failed', 'reason' => 'missing required fields'));
    exit;
} 

$response_info = EventInfo::getResponse($response_id);
$ei = new EventInfo($response_info['event_id']);
$event_info = $ei->getInfo();
$custom_vars['RESPONSE_PERMISSION'] = $response_info['response_permission']; 
$custom_vars['RESPONSE_TEXT'] = $response_info['response'];
$response_info['filePreview'] = $ei->buildEmailForEvent($event_info, 'response', $custom_vars, 'file');

if($uid) { // MOD
    $org_of_event = $ei->getOrganizationOfRequester();
    // if mod is from the same organization who created the event, then they have perm to follow-up
    if($event_info['status'] != "C" && $userData["org_id"] == $org_of_event && $response_info['responder_id'] > 0) {
        $response_info['authorized_to_followup'] = 1;
    }
    print json_encode($response_info);
// FETP: make sure it was the fetp who responded, otherwise no permission to see
} else if ($fetp_id == $response_info['responder_id']) { 
    print json_encode($response_info);
} else {
    print json_encode(array('status' => 'failed', 'reason' => 'requester does not match responder'));
}

?>
