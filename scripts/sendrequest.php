<?php
/* process the Epicore RFI form 
 this is called at the end of all 3 pages, so save
 event info and fetp filter info, then send emails
*/

require_once "const.inc.php";
require_once "EventInfo.class.php";
require_once "UserInfo.class.php";
require_once "AWSMail.class.php";

$formvars = json_decode(file_get_contents("php://input"));

// store the event info in event table
$event_info['latlon'] = (string)$formvars->latlon;
$event_info['location'] = (string)$formvars->location;
$event_info['title'] = (string)$formvars->title;
$event_info['description'] = (string)$formvars->description;
$event_info['requester_id'] = (int)$formvars->uid;
$event_info['search_countries'] = $formvars->search_countries ? $formvars->search_countries : '';
$event_info['search_box'] = $formvars->search_box ? $formvars->search_box : '';
$event_info['create_date'] = date('Y-m-d H:i:s');
$event_info['personalized_text'] = $formvars->additionalText ? (string)$formvars->additionalText : '';

$event_id = EventInfo::insertEvent($event_info);
$ei = new EventInfo($event_id);

// now send it to each FETP individually as they each need unique login token id
$fetp_ids = explode(",", $formvars->fetp_ids);
$tokens = $ei->insertFetpsReceivingEmail($fetp_ids, 0);
$fetp_emails = UserInfo::getFETPEmails($fetp_ids);
$extra_headers['text_or_html'] = "html";

$emailtext = $ei->buildEmailForEvent($event_info, 'rfi', '', 'text');
$proin_emailtext = $ei->buildEmailForEvent($event_info, 'rfi_proin', '', 'text');

foreach($fetp_emails as $fetp_id => $recipient) {
    $idlist[0] = $fetp_id;
    $extra_headers['user_ids'] = $idlist;
    $recipient = trim($recipient);
    $custom_emailtext = trim(str_replace("[TOKEN]", $tokens[$fetp_id], $emailtext));
    $aws_resp = AWSMail::mailfunc($recipient, "EPICORE Request For Information", $custom_emailtext, EMAIL_NOREPLY, $extra_headers);

}
// get event moderator name
$moderator = $ei->getEventPerson($event_id);
// send a modified copy to pro-in for ProMED moderators only
if ($moderator['organization_id'] == PROMED_ID){
    $name = $moderator['name'];
    $email = $moderator['email'];
    $modfetp = "Moderator: $name ($email) sent the following RFI";
    $custom_emailtext_proin = trim(str_replace("[PRO_IN]", $modfetp, $proin_emailtext));
    $aws_resp = AWSMail::mailfunc(EMAIL_PROIN, "EPICORE Request For Information", $custom_emailtext_proin, EMAIL_NOREPLY, $extra_headers);
}

print json_encode(array('status' => 'success', 'fetps' => $fetp_ids));

?>
