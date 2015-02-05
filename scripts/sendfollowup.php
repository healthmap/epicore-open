<?
/* 
send followup to an existing RFI
*/
$formvars = json_decode(file_get_contents("php://input"));

require_once "const.inc.php";
require_once "EventInfo.class.php";
require_once "UserInfo.class.php";

$event_id = $formvars->event_id;
$requester_id = $formvars->uid;
if(!is_numeric($event_id) || !is_numeric($requester_id)) {
    print json_encode(array('status' => 'failed', 'reason' => 'invalid event id or requester id'));
    exit;
}

$ei = new EventInfo($event_id);
$event_info = $ei->getInfo();

// make sure the person trying to send the email was the originator of the request
// or from the same organization
if($requester_id != $event_info['requester_id']) {
    $rui = new UserInfo($requester_id);
    $roid = $rui->getOrganizationId();
    if($event_info['org_requester_id'] != $roid) {
        print json_encode(array('status' => 'failed', 'reason' => 'unauthorized', 'requester' => $requester_id, 'owner' => $event_info['requester_id']));
        exit;
    }
}

// start building the email text
$custom_vars['NOTES'] = $formvars->additionalText ? $formvars->additionalText : '';

// if response_id is passed in, get the fetp_id from the response table
if(isset($formvars->response_id) && is_numeric($formvars->response_id))  {
    $response_info = EventInfo::getResponse($formvars->response_id);
    $fetp_ids = array($response_info['responder_id']);
    $custom_vars['RESPONSE_DATE'] = $response_info['response_date'];
    $custom_vars['RESPONSE_PERMISSION'] = $response_info['response_permission'];
    $custom_vars['RESPONSE_TEXT'] = $response_info['response'];
    $followupText = $ei->buildEmailForEvent($event_info, "followup-specific", $custom_vars, 'text');
} else { // if no respsonse_id (follow-up to all), get fetp_ids from database for that event
    $fetp_ids = $ei->getFETPRecipients();
    $followupText = $ei->buildEmailForEvent($event_info, "followup", $custom_vars, 'text');
}

// save the fetp_ids in the event_fetp table
if(empty($fetp_ids)) {
    print json_encode(array('status' => 'failed', 'reason' => 'no FETPs to receive request'));
    exit;
}

// get this far, good to insert & send
// return an array of fetp_id => token_id for auto_login
$tokens = $ei->insertFetpsReceivingEmail($fetp_ids, 1);

// now send it to each FETP individually as they each need unique login token id
// cc the initiator of the request for testing only
require_once "AWSMail.class.php";
$fetp_emails = UserInfo::getFETPEmails($fetp_ids);
$extra_headers['text_or_html'] = "html";


foreach($fetp_emails as $fetp_id => $recipient) {
    $emailtext = trim(str_replace("[TOKEN]", $tokens[$fetp_id], $followupText));
    $retval = AWSMail::mailfunc($recipient, "Request For Information", $emailtext, EMAIL_NOREPLY, $extra_headers);
}

print json_encode(array('status' => 'success', 'fetps' => $fetp_ids));

?>
