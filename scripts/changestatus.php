<?php
/* reply to an RFI */
$formvars = json_decode(file_get_contents("php://input"));
require_once "EventInfo.class.php";
require_once "UserInfo.class.php";
require_once "const.inc.php";
require_once 'ePush.class.php';
require_once "UserContoller3.class.php";

use UserController as userController;

$userData = userController::getUserData();

$event_id = $formvars->event_id;
$user_id = $userData["uid"];
$useful_rids = $formvars->useful_rids;
$usefulpromed_rids = $formvars->usefulpromed_rids;
$notuseful_rids = $formvars->notuseful_rids;

if(is_numeric($event_id) && is_numeric($user_id)) {
    //$thestatus = $formvars->thestatus == "Reopen" ? 'O' : 'C';
    if ($formvars->thestatus == "Reopen")
        $thestatus = 'O';
    elseif ($formvars->thestatus == "Close")
        $thestatus = 'C';
    elseif ($formvars->thestatus == "Update")
        $thestatus = 'U';
    else
        $thestatus = 'none';
    $ei = new EventInfo($event_id);
    $event_info = $ei->getInfo();
    // reason is one of the radio button choices on the close event form
    $reason = isset($formvars->reason) && is_numeric($formvars->reason) ? $formvars->reason : '';
    if ($thestatus == 'O' || $thestatus == 'C') {
        $return_val = $ei->changeStatus($thestatus, $user_id, $formvars->notes, $reason);
    }
    else if($thestatus == 'U') {
        // update response status (useful or not)
        $ei->setResponseStatus($useful_rids, 1);
        $ei->setResponseStatus($usefulpromed_rids, 2);
        $ei->setResponseStatus($notuseful_rids, 0);
        $return_val = 0;
    }
    else{
        $return_val = 0;
    }

    if($return_val == 1) {
        // set response status (useful or not)
        $ei->setResponseStatus($useful_rids, 1);
        $ei->setResponseStatus($usefulpromed_rids, 2);
        $ei->setResponseStatus($notuseful_rids, 0);

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
        $status_type = $formvars->thestatus == "Reopen" ? 'Re-Opened' : 'Closed';
        $status_type_member = $formvars->thestatus == "Reopen" ? 're-opened' : 'closed_member';
        $emailtext_event = $ei->buildEmailForEvent($event_info, $status_type_member, $custom_vars, 'text');
        $extra_headers['text_or_html'] = "html";
        $subject = "Epicore RFI #". $event_id . " - " . $status_type . ": " . $event_info['disease'] . ", " . $event_info['location'];

        // set up push notification
        // $push = new ePush();
        // $pushevent['id'] = $event_id;
        // $pushevent['title'] = $event_info['title'];
        // $pushevent['type'] = $formvars->thestatus == "Reopen" ? 'REOPENED' : 'CLOSED';


        foreach($fetp_emails as $fetp_id => $recipient) {
            // send email
            $idlist[0] = $fetp_id;
            $extra_headers['user_ids'] = $idlist;
            $history = $ei->getEventHistoryFETP($fetp_id, $event_id);
            $emailtext = trim(str_replace("[PRO_IN]", '', $emailtext_event));
            $next_emailtext = trim(str_replace("[EVENT_HISTORY]", $history, $emailtext));
            $custom_emailtext = trim(str_replace("[TOKEN]", $tokens[$fetp_id], $next_emailtext));
            AWSMail::mailfunc($recipient, $subject, $custom_emailtext, EMAIL_NOREPLY, $extra_headers);

            // send push notification
            //$push->sendPush($pushevent, $fetp_id);

        }

        // send email to all moderators for the event /////////////////////
        //get all fetp messages
        $history = $ei->getEventHistoryAll($event_id);
        // make email to: list, and id list
        $tolist = array();
        $idlist = array();

        // get moderator that initiated the event request
        $initiator = $ei->getInitiatorEmail();
        // get all moderators that sent followups for the event
        $fmoderators = $ei->getFollowupEmail();
        // get moderator that changed the event status
        $moderator = $ei->getStatusPerson($event_id, $user_id);

        if ($moderator['email'] !=$initiator['email']){
            array_push($tolist, $initiator['email']);
            array_push($idlist, $initiator['user_id']);
        }
        foreach ($fmoderators as $fmoderator){
            if (($fmoderator['email'] != $moderator['email']) && ($fmoderator['email'] != $initiator['email'])) {
                array_push($tolist, $fmoderator['email']);
                array_push($idlist, $fmoderator['user_id']);
            }
        }

        // send a modified copy to PRO-IN for ProMed moderators only
        if ($moderator['organization_id'] == PROMED_ID){
            array_push($tolist, EMAIL_PROIN);
            array_push($idlist, PROMED_ID);
        }
        // send copy to epicore info
        array_push($tolist, EMAIL_INFO_EPICORE);
        array_push($idlist, EPICORE_ID);

        if ($status_type == 're-opened')
            $status_type_new = 're-opened_proin';
        else
            $status_type_new = 'closed_proin';

        $emailtext_event = $ei->buildEmailForEvent($event_info, $status_type_new, $custom_vars, 'text');

        if (!empty($tolist)) {
            $name = $moderator['name'];
            $email = $moderator['email'];
            //$modfetp = "Moderator: $name ($email) $status_type an RFI";
            $modfetp = "Moderator: $name $status_type an RFI";
            $emailtext = trim(str_replace("[EVENT_HISTORY]", $history, $emailtext_event));
            $custom_emailtext_mods = trim(str_replace("[PRO_IN]", $modfetp, $emailtext));
            $extra_headers['user_ids'] = $idlist;
            AWSMail::mailfunc($tolist, $subject, $custom_emailtext_mods, EMAIL_NOREPLY, $extra_headers);
        }

        print json_encode(array('status' => $status));
        exit;
    } elseif ($thestatus == 'U'){
        print json_encode(array('status' => 'success'));
        exit;
    } elseif ($thestatus == 'none'){
        $error = "invalid RFI status";
    }
    else {
        $error = "requester and owner not the same";
    }
} else {
    $error = "invalid passed params";
}
// if you got here, it failed
print json_encode(array('status' => 'error', 'reason' => $error));
?>
