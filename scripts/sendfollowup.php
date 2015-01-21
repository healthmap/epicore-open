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

// if the fetp_ids were passed in, use those
// they would be passed in from original form request (as array), 
// or from a follow-up to specific FETP request
// otherwise get the fetp_ids from database for that event (from a follow-up to all request)
$fetp_ids = isset($formvars->fetp_ids) && $formvars->fetp_ids != null ? $formvars->fetp_ids : $ei->getFETPRecipients();
if(!is_array($fetp_ids)) {
    $fetp_ids = array($fetp_ids);
}
$fetp_ids = array_unique($fetp_ids);

// save the fetp_ids in the event_fetp table
if(empty($fetp_ids)) {
    print json_encode(array('status' => 'failed', 'reason' => 'no FETPs to receive request'));
    exit;
}

// get this far, good to insert & send
require_once "EventInfo.class.php";
$ei = new EventInfo($event_id);
// return an array of fetp_id => token_id for auto_login
$tokens = $ei->insertFetpsReceivingEmail($fetp_ids, 1);

$orig_emailtext = $formvars->additionalText ? $formvars->additionalText ."\n\n" : '';
$orig_emailtext .= $ei->buildEmailForEvent($event_info, 'followup');

// now send it to each FETP individually as they each need unique login token id
// cc the initiator of the request for testing only
require_once "AWSMail.class.php";
//$extra_headers['cc'] = $formvars->recipient;
$fetp_emails = UserInfo::getFETPEmails($fetp_ids);
foreach($fetp_emails as $fetp_id => $recipient) {
    $emailtext = str_replace("[TOKEN]", $tokens[$fetp_id], $orig_emailtext);
    AWSMail::mailfunc($recipient, "Request For Information", $emailtext, EMAIL_NOREPLY);
}

print json_encode(array('status' => 'success', 'fetps' => $fetp_ids));

?>
