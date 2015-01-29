<?php
/* 
get response by response id
pass in fetp_id if it's from the fetp view
pass in the uid if it's from the mod view
*/
require_once "const.inc.php";
require_once "EventInfo.class.php";

$formvars = json_decode(file_get_contents("php://input"));

$response_id = isset($formvars->response_id) ? $formvars->response_id : '';
$uid = isset($formvars->uid) ? $formvars->uid : '';
$fetp_id = isset($formvars->fetp_id) ? $formvars->fetp_id : '';

if(!is_numeric($response_id) || (!is_numeric($uid) && !is_numeric($fetp_id))) {
    print json_encode(array('status' => 'failed', 'reason' => 'missing required fields'));
    exit;
} 

$response_info = EventInfo::getResponse($response_id);
$ei = new EventInfo($response_info['event_id']);
$event_info = $ei->getInfo();
$followupText = $ei->buildEmailForEvent($event_info, 'get_reply');
$response_emailtext .= $response_info['response'];
if($response_info['response_permission']) {
    $response_emailtext .= "\n\n" . $response_info['response_permission'];
}
$response_emailtext .= "\n";

$response_info['followupText'] = str_replace("[RESPONSE_INFO]", $response_emailtext, $followupText);

if($uid) { // MOD
    if($formvars->frompage == "followup") {
        $followupText = $ei->buildEmailForEvent($event_info, 'followup_specific');
        $followupText = str_replace("[RESPONSE_DATE]", $response_info['response_date'], $followupText);
        $response = $response_info['response'];
        if($response_info['response_permission']) {
            $response .= "\n\n".$response_info['response_permission'];
        }
        $response_info['followupText'] = str_replace("[RESPONSE_TEXT]", $response, $followupText);
    }
    $org_of_event = $ei->getOrganizationOfRequester();
    // if mod is from the same organization who created the event, then they have perm to follow-up
    if($formvars->org_id == $org_of_event && $response_info['responder_id'] > 0) {
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
