<?
/* reply to an RFI */
$formvars = json_decode(file_get_contents("php://input"));
require_once "EventInfo.class.php";
require_once "UserInfo.class.php";
require_once "const.inc.php";

$event_id = $formvars->event_id;
$user_id = $formvars->uid;
if(is_numeric($event_id) && is_numeric($user_id)) {
    $thestatus = $formvars->thestatus == "Reopen" ? 'O' : 'C';
    $ei = new EventInfo($event_id);
    $event_info = $ei->getInfo();
    // reason is one of the radio button choices on the close event form
    $reason = isset($formvars->reason) && is_numeric($formvars->reason) ? $formvars->reason : '';
    $return_val = $ei->changeStatus($thestatus, $user_id, $formvars->notes, $reason);
    $response_link = '';
    if($return_val == 1) {
         $status = "success"; 
        // now send the notes to all orig FETPs specifying status change
        $fetp_ids = $ei->getFETPRecipients();    
        require_once "AWSMail.class.php";
        $fetp_emails = UserInfo::getFETPEmails($fetp_ids);
        // return an array of fetp_id => token_id for auto_login
        // reopening, so send the FETP a clink to log in
        if($thestatus == "O") {
            $custom_text = "The reason for re-opening is: ";
            // the second argument is "2" for the follow-up field, indicating a re-open request
            $tokens = $ei->insertFetpsReceivingEmail($fetp_ids, 2);
            // include a link to respond only if the event is re-opened
            $response_link = 'true';
        } else {
            $custom_text = "The reason for the closure is: ";
            $custom_text .= $reason_lu[$reason] ? "\n".$reason_lu[$reason]."\n" : "";
        }
        $custom_text .= "\n\n".$formvars->notes;
        $orig_emailtext .= $ei->buildEmailForEvent($event_info, $thestatus, $custom_text, $response_link);
        foreach($fetp_emails as $fetp_id => $recipient) {
            $emailtext = str_replace("[TOKEN]", $tokens[$fetp_id], $orig_emailtext);
            AWSMail::mailfunc($recipient, "Status Change: Request For Information", $emailtext, EMAIL_NOREPLY);
        }
        print json_encode(array('status' => $status));
        exit;
    } else {
        $error = "requester and owner not the same";
    }
} else {
    $error = "invalid passed params";
}
// if you got here, it failed
print json_encode(array('status' => 'error', 'reason' => $error));
?>
