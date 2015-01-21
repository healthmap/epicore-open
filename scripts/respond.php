<?
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
    $ei->insertResponse($dbdata);

    // build the email message
    $msg = $ei->buildEmailForEvent($event_info, 'response')."\n\n";
    if($dbdata['response_permission'] == 0) {
        $msg .= "FETP indicated he/she had nothing to contribute regarding the outbreak at this time.";
    } else {
        $msg .= "The permission level for this response is:\nMod may " . $response_permission_lu[$dbdata['response_permission']] . "\n\n";
    }
    $msg .= $dbdata['response']. "\n\n";
    $msg = str_replace("[FETP_ID]", $dbdata['responder_id'], $msg);

    // send the response to the person who initiated the event request
    $recipient = $ei->getInitiatorEmail();
    require_once "AWSMail.class.php";
    AWSMail::mailfunc($recipient, "FETP response", $msg, EMAIL_NOREPLY);
    $status = "success";
    $path = "success/2";
}
print json_encode(array('status' => $status, 'path' => $path, 'dbdata' => $dbdata));
?>
