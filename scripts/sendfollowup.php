<?php
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
$roid =0 ;
if($requester_id != $event_info['requester_id']) {
    $rui = new UserInfo($requester_id,null);
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
    $followupText_proin = $ei->buildEmailForEvent($event_info, "followup-specific_proin", $custom_vars, 'text');
} else { // if no respsonse_id (follow-up to all), get fetp_ids from database for that event
    $fetp_ids = $ei->getFETPRecipients();
    $followupText = $ei->buildEmailForEvent($event_info, "followup", $custom_vars, 'text');
    $followupText_proin = $ei->buildEmailForEvent($event_info, "followup_proin", $custom_vars, 'text');
}

// save the fetp_ids in the event_fetp table
if(empty($fetp_ids)) {
    print json_encode(array('status' => 'failed', 'reason' => 'no FETPs to receive request'));
    exit;
}

// save new followup info in database
$followup_info['text'] = $custom_vars['NOTES'];
$followup_info['requester_id'] = $requester_id;
$followup_info['event_id'] = $event_id;
$followup_id = EventInfo::insertFollowup($followup_info);

// get this far, good to insert & send
// return an array of fetp_id => token_id for auto_login
$tokens = $ei->insertFetpsReceivingEmail($fetp_ids, $followup_id);

// get followup moderator
$moderator = $ei->getFollowupPerson($event_id, $requester_id);

// now send it to each FETP individually as they each need unique login token id
// cc the initiator of the request for testing only
require_once "AWSMail.class.php";
$fetp_emails = UserInfo::getFETPEmails($fetp_ids);
$extra_headers['text_or_html'] = "html";
foreach($fetp_emails as $fetp_id => $recipient) {
    // get fetp messages
    $messages = $ei->getFetpMessages($fetp_id, $event_id);
    $history = '';
    // style message history for email
    $counter =0;
    foreach ($messages as $message) {
        if ($counter > 0) {  // skip first (current ) message
            $mtype = $message['type'];
            if ($message['type'] == 'Event Notes')
                $mtype = $message['status'] . "event request";
            $mtext = $message['text'];
            $mdatetime = $message['date'];
            $history .= "<div style='background-color: #fff;padding:24px;color:#666;border: 1px solid #B4FEF7;'>";
            $history .= "<p style='margin:12px 0;'>$mtype,  $mdatetime <br></p>$mtext</div><br>";
        }
        $counter++;
    }

    $emailtext = trim(str_replace("[EVENT_HISTORY]", $history, $followupText));
    $emailtext = trim(str_replace("[TOKEN]", $tokens[$fetp_id], $emailtext));
    $retval = AWSMail::mailfunc($recipient, "Request For Information", $emailtext, EMAIL_NOREPLY, $extra_headers);
    // send a modified copy to PRO-IN for ProMed moderators only
    if ($moderator['organization_id'] == PROMED_ID){
        $name = $moderator['name'];
        $email = $moderator['email'];
        $modfetp = "Moderator: $name ($email) <br> FETP:  $recipient <br>";
        $proin_emailtext = trim(str_replace("[EVENT_HISTORY]", $history, $followupText_proin));
        $custom_emailtext_proin = trim(str_replace("[PRO_IN]", $modfetp, $proin_emailtext));
        $retval = AWSMail::mailfunc(EMAIL_PROIN, "[EPICORE] Request For Information", $custom_emailtext_proin, EMAIL_NOREPLY, $extra_headers);
    }
}

print json_encode(array('status' => 'success', 'fetps' => $fetp_ids));

?>
