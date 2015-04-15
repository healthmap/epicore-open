<?php
/* reply to an RFI */
$formvars = json_decode(file_get_contents("php://input"));
require_once "EventInfo.class.php";
require_once "const.inc.php";
require_once "UserInfo.class.php";

$status = "error";
$path = "success/4";

$event_id = $formvars->event_id;

if(is_numeric($event_id)) {
    // clean data
    $dbdata['responder_id'] = isset($formvars->anonymous) ? 0 : (int)$formvars->fetp_id;
    $dbdata['response'] = strip_tags($formvars->reply);
    $dbdata['response_permission'] = (int)$formvars->response_permission;

    // insert into response table
    $ei = new EventInfo($event_id);
    $event_info = $ei->getInfo();
    $response_id = $ei->insertResponse($dbdata);

    // do this so you get the perm and text formatted correctly for the email
    $response_info = EventInfo::getResponse($response_id);
    $custom_vars['RESPONSE_PERMISSION'] = $response_info['response_permission'];
    $custom_vars['RESPONSE_TEXT'] = $response_info['response'];
    $custom_vars['RESPONSE_ID'] = $response_id;

    $msg = $ei->buildEmailForEvent($event_info, 'response', $custom_vars, 'text');
    $msg_proin = $ei->buildEmailForEvent($event_info, 'response_proin', $custom_vars, 'text');

    // get the person who initiated the event request
    $initiator = $ei->getInitiatorEmail();

    // get all moderators that sent followups for the event
    $moderators = $ei->getFollowupEmail();

    // make email to: list
    $tolist[0] = $initiator;
    $i = 1;
    foreach ($moderators as $moderator){
        if ($moderator['email'] != $initiator)
            $tolist[$i++] = $moderator['email'];
    }

    // get fetp messages
    $fetp_id = $dbdata['responder_id'];
    $messages = $ei->getFetpMessages($fetp_id, $event_id);
    $history = '';

    // build message history for email
    $counter =0;
    foreach ($messages as $message) {
        if ($counter > 0) {  // skip first (current ) message
            $mtype = $message['type'];
            if ($message['type'] == 'Event Notes')
                $mtype = $message['status'] . " event request";
            $mtext = $message['text'];
            $mdatetime = $message['date'];
            $history .= "<div style='background-color: #fff;padding:24px;color:#666;border: 1px solid #B4FEF7;'>";
            $history .= "<p style='margin:12px 0;'>$mtype,  $mdatetime <br></p>$mtext</div><br>";
        }
        $counter++;
    }

    // send email to all moderators
    $emailtext = trim(str_replace("[EVENT_HISTORY]", $history, $msg));
    $proin_emailtext = trim(str_replace("[EVENT_HISTORY]", $history, $msg_proin));
    $extra_headers['text_or_html'] = "html";
    require_once "AWSMail.class.php";
    foreach($tolist as $to) {
        AWSMail::mailfunc($to, "FETP response", $emailtext, EMAIL_NOREPLY, $extra_headers);
    }

    // get fetp email
    $fetp_email = '';
    $fetp_ids[0] = $fetp_id;
    $fetp_emails = UserInfo::getFETPEmails($fetp_ids);
    foreach($fetp_emails as $fetp_id => $recipient) {
        $fetp_email = trim($recipient);
    }

    // get event moderator info
    $emoderator = $ei->getEventPerson($event_id);
    // send a modified copy to pro-in for ProMED event moderator only
    if ($emoderator['organization_id'] == PROMED_ID){
        $name = $emoderator['name'];
        $email = $emoderator['email'];
        $modfetp = "Moderator: $name ($email) <br> FETP:  $fetp_email <br>";
        $custom_emailtext_proin = trim(str_replace("[PRO_IN]", $modfetp, $proin_emailtext));
        $aws_resp = AWSMail::mailfunc(EMAIL_PROIN, "[EPICORE] FETP response", $custom_emailtext_proin, EMAIL_NOREPLY, $extra_headers);
    }

    // send a modified copy to PRO-IN for ProMED followup moderators only
    foreach ($moderators as $moderator){
        if ($moderator['organization_id'] == PROMED_ID && ($moderator['email'] != $initiator)) {
            $name = $moderator['name'];
            $email = $moderator['email'];
            $orgid = $moderator['organization_id'];
            $modfetp = "Moderator: $name ($email) <br> FETP:  $fetp_email <br>";
            $custom_emailtext_proin = trim(str_replace("[PRO_IN]", $modfetp, $proin_emailtext));
            $aws_resp = AWSMail::mailfunc(EMAIL_PROIN, "[EPICORE] FETP response", $custom_emailtext_proin, EMAIL_NOREPLY, $extra_headers);
        }
    }

    $status = "success";
    $path = "success/2";
}

print json_encode(array('status' => $status, 'path' => $path, 'dbdata' => $dbdata));

?>
