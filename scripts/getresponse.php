<?
/* get response by response id
*/
require_once "const.inc.php";
$formvars = json_decode(file_get_contents("php://input"));

require_once "EventInfo.class.php";

if(!isset($formvars->response_id) || !is_numeric($formvars->response_id)) {
    print json_encode(array('status' => 'failed', 'reason' => 'missing required fields'));
    exit;
} 

if( (isset($formvars->uid) && is_numeric($formvars->uid)) || (isset($formvars->fetp_id) && is_numeric($formvars->fetp_id)) ) {
    $response_info = EventInfo::getResponse($formvars->response_id);
    if($formvars->uid) { // MOD
        $ei = new EventInfo($response_info['event_id']);
        $org_of_event = $ei->getOrganizationOfRequester();
        // if mod is from the same organization who created the event and the user was not anonymous, then they have perm to follow-up
        if($formvars->org_id == $org_of_event && $response_info['responder_id'] > 0) {
            $response_info['authorized_to_followup'] = 1;
        }
        print json_encode($response_info);
    } else if ($formvars->fetp_id == $response_info['responder_id']) { // FETP: make sure it was the fetp who responded, otherwise no permission to see
        print json_encode($response_info);
    } else {
        print json_encode(array('status' => 'failed', 'reason' => 'requester does not match responder'));
    }
} else {
    print json_encode(array('status' => 'failed', 'reason' => 'missing required fields'));
} 
exit;

?>
