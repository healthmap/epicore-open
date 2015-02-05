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
    $extra_headers['text_or_html'] = "html";
    require_once "AWSMail.class.php";
    AWSMail::mailfunc($recipient, "FETP response", $msg, EMAIL_NOREPLY, $extra_headers);
    $status = "success";
    $path = "success/2";
}

print json_encode(array('status' => $status, 'path' => $path, 'dbdata' => $dbdata));

?>
