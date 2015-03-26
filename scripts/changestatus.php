<?php
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
    if($return_val == 1) {
        $status = "success"; 
        // now send the notes to all orig FETPs specifying status change
        $fetp_ids = $ei->getFETPRecipients();    
        require_once "AWSMail.class.php";
        $fetp_emails = UserInfo::getFETPEmails($fetp_ids);
        // return an array of fetp_id => token_id for auto_login
        // reopening, so send the FETP a clink to log in
        if($thestatus == "O") {
            // the second argument is "2" for the follow-up field, indicating a re-open request
            $tokens = $ei->insertFetpsReceivingEmail($fetp_ids, 2);
        } else {
            $custom_vars['REASON'] = $reason_lu[$reason] ? $reason_lu[$reason] : "";
        }
        $custom_vars['NOTES'] = $formvars->notes;
        $status_type = $formvars->thestatus == "Reopen" ? 're-opened' : 'closed';
        $emailtext = $ei->buildEmailForEvent($event_info, $status_type, $custom_vars, 'text');
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
                    if($mtype == "Event Notes")
                        $mtype = "Event " . $message['status'];
                    $mtext = $message['text'];
                    $mdatetime = $message['date'];
                    $history .= "<div style='background-color: #fff;padding:24px;color:#666;border: 1px solid #B4FEF7;'>";
                    $history .= "<p style='margin:12px 0;'>$mtype,  $mdatetime <br></p>$mtext</div><br>";
                }
                $counter++;
            }

            $emailtext = trim(str_replace("[EVENT_HISTORY]", $history, $emailtext));
            $custom_emailtext = trim(str_replace("[TOKEN]", $tokens[$fetp_id], $emailtext));
            AWSMail::mailfunc($recipient, "An Epicore RFI has been $status_type", $custom_emailtext, EMAIL_NOREPLY, $extra_headers);
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
