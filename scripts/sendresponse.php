<?php
/* reply to an RFI */
$formvars = json_decode(file_get_contents("php://input"));
require_once "EventInfo.class.php";
require_once "const.inc.php";

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

    // send the response to the person who initiated the event request
    $recipient = $ei->getInitiatorEmail();

    // get all moderators that sent followups for the event
    //$moderators = $ei->getFollowupEmail();

    // get fetp messages
    $fetp_id = $dbdata['responder_id'];
    $messages = $ei->getFetpMessages($fetp_id, $event_id);
    $history = '';
    // style message history for email
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

    $emailtext = trim(str_replace("[EVENT_HISTORY]", $history, $msg));
    $extra_headers['text_or_html'] = "html";
    require_once "AWSMail.class.php";
    AWSMail::mailfunc($recipient, "FETP response", $emailtext, EMAIL_NOREPLY, $extra_headers);
    $status = "success";
    $path = "success/2";
}

print json_encode(array('status' => $status, 'path' => $path, 'dbdata' => $dbdata));

?>
