<?php
/* process the Epicore RFI form 
 this is called at the end of all 3 pages, so save
 event info and fetp filter info, then send emails
*/

require_once "const.inc.php";
require_once "EventInfo.class.php";
require_once "UserInfo.class.php";
require_once "AWSMail.class.php";
require_once 'ePush.class.php';
require_once "UserContoller3.class.php";

use UserController as userController;

$userData = userController::getUserData();

$formvars = json_decode(file_get_contents("php://input"));

// store the event info in event table
$event_info['latlon'] = (string)$formvars->latlon;
$event_info['location'] = (string)$formvars->location;
$event_info['title'] = (string)$formvars->title;
$event_info['description'] = (string)$formvars->description;
$event_info['requester_id'] = (int)$userData["uid"];
$event_info['search_countries'] = $formvars->search_countries ? $formvars->search_countries : '';
$event_info['search_box'] = $formvars->search_box ? $formvars->search_box : '';
$event_info['create_date'] = date('Y-m-d H:i:s');
$event_info['personalized_text'] = $formvars->additionalText ? (string)$formvars->additionalText : '';
$event_info['disease'] = (string)$formvars->disease;
$event_info['alert_id'] = (int)$formvars->alert_id;

$event_id = EventInfo::insertEvent($event_info);
$ei = new EventInfo($event_id);
$subject = "EPICORE RFI #" . $event_id. " - " . $event_info['disease'] . ", " . $event_info['location'];

// now send it to each FETP individually as they each need unique login token id
$fetp_ids = explode(",", $formvars->fetp_ids);
$tokens = $ei->insertFetpsReceivingEmail($fetp_ids, 0);
$fetp_emails = UserInfo::getFETPEmails($fetp_ids);
$extra_headers['text_or_html'] = "html";

$emailtext = $ei->buildEmailForEvent($event_info, 'rfi', '', 'text');

// set up push notification
// $push = new ePush();
// $pushevent['id'] = $event_id;
// $pushevent['title'] = $event_info['title'];
// $pushevent['type'] = 'RFI';

foreach($fetp_emails as $fetp_id => $recipient) {
    // send email
    $idlist[0] = $fetp_id;
    $extra_headers['user_ids'] = $idlist;
    $recipient = trim($recipient);
    $custom_emailtext = trim(str_replace("[TOKEN]", $tokens[$fetp_id], $emailtext));
    $aws_resp = AWSMail::mailfunc($recipient, $subject, $custom_emailtext, EMAIL_NOREPLY, $extra_headers);

    // send push notification
    $push->sendPush($pushevent, $fetp_id);

}

// build copy email
$proin_emailtext = $ei->buildEmailForEvent($event_info, 'rfi_proin', '', 'text');
$moderator = $ei->getEventPerson($event_id); // get event moderator name
$name = $moderator['name'];
$email = $moderator['email'];
//$modfetp = "Moderator: $name ($email) sent the following RFI";
$modfetp = "Moderator: $name sent the following RFI";
$custom_emailtext_proin = trim(str_replace("[PRO_IN]", $modfetp, $proin_emailtext));

// send copy to pro-in for ProMED moderators only
if ($moderator['organization_id'] == PROMED_ID){
    $idlist[0] = PROMED_ID;
    $extra_headers['user_ids'] = $idlist;
    $aws_resp = AWSMail::mailfunc(EMAIL_PROIN, $subject, $custom_emailtext_proin, EMAIL_NOREPLY, $extra_headers);
}

// send copy to epicore info
$idlist[0] = EPICORE_ID;
$extra_headers['user_ids'] = $idlist;
$aws_resp = AWSMail::mailfunc(EMAIL_INFO_EPICORE, $subject, $custom_emailtext_proin, EMAIL_NOREPLY, $extra_headers);

print json_encode(array('status' => 'success', 'fetps' => $fetp_ids));

?>
