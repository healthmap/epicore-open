<?php
/* 
send followup to an existing RFI
*/
$formvars = json_decode(file_get_contents("php://input"));

require_once "const.inc.php";
require_once "EventInfo.class.php";
require_once "UserInfo.class.php";
require_once 'ePush.class.php';

$event_id = $formvars->event_id;
$requester_id = $formvars->uid;
$superuser = (int)$formvars->superuser;

if(!is_numeric($event_id) || !is_numeric($requester_id)) {
    print json_encode(array('status' => 'failed', 'reason' => 'invalid event id or requester id'));
    exit;
}

$ei = new EventInfo($event_id);
$event_info = $ei->getInfo();

// make sure the person trying to send the email was the originator of the request
// or from the same organization or is a superuser
$roid =0 ;
//if($requester_id != $event_info['requester_id']) {
  //  $rui = new UserInfo($requester_id,null);
   // $roid = $rui->getOrganizationId();
   // if(($event_info['org_requester_id'] != $roid) || $superuser == 1) {
    //    print json_encode(array('status' => 'failed', 'reason' => 'unauthorized', 'requester' => $requester_id, 'owner' => $event_info['requester_id']));
     //   exit;
  //  }
//}

/* ****************************************************************
   
   Following is added by Sam, CH157135.
   Above condition is checking to see if the email is being sent
   by originator, same organization. But, check for superuser is 
   implemented differently. 

    // make sure the person trying to send the email was the originator of the request
    // or from the same organization or is a superuser

*****************************************************************/

if($superuser != 1) {
if($requester_id != $event_info['requester_id']) {
    $rui = new UserInfo($requester_id,null);
    $roid = $rui->getOrganizationId();
    if(($event_info['org_requester_id'] != $roid)) {
        print json_encode(array('status' => 'failed', 'reason' => 'unauthorized', 'requester' => $requester_id, 'owner' => $event_info['requester_id']));
        exit;
    }
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
    $followupText = $ei->buildEmailForEvent($event_info, "followup-specific2", $custom_vars, 'text');
    $followupText_proin = $ei->buildEmailForEvent($event_info, "followup-specific_proin2", $custom_vars, 'text');
    $followup_info['response_id'] = $formvars->response_id;
    $subject = "EPICORE RFI #". $event_id . " - Requester Response: " . $event_info['title'];
} else { // if no respsonse_id (follow-up to all), get fetp_ids from database for that event
    $fetp_ids = $ei->getFETPRecipients();
    $followupText = $ei->buildEmailForEvent($event_info, "followup2", $custom_vars, 'text');
    $followupText_proin = $ei->buildEmailForEvent($event_info, "followup_proin2", $custom_vars, 'text');
    $subject = "EPICORE RFI # " . $event_id . " Follow-up : " . $event_info['title'];
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

// set up push notification
$push = new ePush();
$pushevent['id'] = $event_id;
$pushevent['title'] = $event_info['title'];
$pushevent['type'] = 'FOLLOWUP';

// now send it to each FETP individually as they each need unique login token id
require_once "AWSMail.class.php";
$fetp_emails = UserInfo::getFETPEmails($fetp_ids);
$extra_headers['text_or_html'] = "html";
foreach($fetp_emails as $fetp_id => $recipient) {

    // send email
    $idlist[0] = $fetp_id;
    $extra_headers['user_ids'] = $idlist;
    $history = $ei->getEventHistoryFETP($fetp_id, $event_id);
    $emailtext = trim(str_replace("[EVENT_HISTORY]", $history, $followupText));
    $emailtext = trim(str_replace("[TOKEN]", $tokens[$fetp_id], $emailtext));
    $retval = AWSMail::mailfunc($recipient, $subject, $emailtext, EMAIL_NOREPLY, $extra_headers);

    // send push notification
    //$push->sendPush($pushevent, $fetp_id);

}

// send email to all moderators for the event /////////////////////
//get all fetp messages
$history = $ei->getEventHistoryAll($event_id);
// make email to: list, and id list
$tolist = array();
$idlist = array();

// get the person who initiated the event request
$initiator = $ei->getInitiatorEmail();
// get all moderators that sent followups for the event
$fmoderators = $ei->getFollowupEmail();


// get followup moderator
$moderator = $ei->getFollowupPerson($event_id, $requester_id);

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

// send copy to mods following the Event
$followers = EventInfo::getFollowers($event_id);
foreach ($followers as $follower){
    array_push($tolist, $follower['email']);
    array_push($idlist, $follower['user_id']);
}

// send email
if (!empty($tolist)) {
    $name = $moderator['name'];
    $email = $moderator['email'];
    //$modfetp = "Moderator: $name ($email) sent this followup to an EpiCore RFI";
    $modfetp = "Requester: $name sent this followup to an EpiCore RFI";
    $proin_emailtext = trim(str_replace("[EVENT_HISTORY]", $history, $followupText_proin));
    $custom_emailtext_proin = trim(str_replace("[PRO_IN]", $modfetp, $proin_emailtext));
    $extra_headers['user_ids'] = $idlist;
    $retval = AWSMail::mailfunc($tolist, $subject, $custom_emailtext_proin, EMAIL_NOREPLY, $extra_headers);
}

print json_encode(array('status' => 'success', 'fetps' => $fetp_ids));

?>
