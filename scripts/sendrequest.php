<?
/* process the Epicore RFI form 
 this is called at the end of all 3 pages, so save
 event info and fetp filter info, then send emails
*/
require_once "const.inc.php";
require_once "EventInfo.class.php";
require_once "UserInfo.class.php";

$formvars = json_decode(file_get_contents("php://input"));

// store the event info in event table
$event_info['latlon'] = (string)$formvars->latlon;
$event_info['location'] = (string)$formvars->location;
$event_info['title'] = (string)$formvars->title;
$event_info['description'] = (string)$formvars->description;
$event_info['requester_id'] = (int)$formvars->uid;
$event_info['search_countries'] = $formvars->search_countries ? $formvars->search_countries : '';
$event_info['search_box'] = $formvars->search_box ? $formvars->search_box : '';

$event_id = EventInfo::insertEvent($event_info);
$ei = new EventInfo($event_id);

// return an array of fetp_id => token_id for auto_login
$fetp_ids = explode(",", $formvars->fetp_ids);
$tokens = $ei->insertFetpsReceivingEmail($fetp_ids, 0);

$custom_text = $formvars->additionalText ? (string)$formvars->additionalText . "\n\n" : '';
$orig_emailtext .= $ei->buildEmailForEvent($event_info, 'rfi', $custom_text);

// now send it to each FETP individually as they each need unique login token id
require_once "AWSMail.class.php";
$fetp_emails = UserInfo::getFETPEmails($fetp_ids);
foreach($fetp_emails as $fetp_id => $recipient) {
    $emailtext = str_replace("[TOKEN]", $tokens[$fetp_id], $orig_emailtext);
    AWSMail::mailfunc($recipient, "Request For Information", $emailtext, EMAIL_NOREPLY);
}

print json_encode(array('status' => 'success', 'fetps' => $fetp_ids));

?>
