<?
/* process the Epicore RFI form 
 this is called upon the second form submission
 takes event info and builds the email text for display on step 3
*/
require_once "const.inc.php";
require_once "EventInfo.class.php";

$formvars = json_decode(file_get_contents("php://input"));

// build the email text
$event_info['location'] = (string)$formvars->location;
$event_info['title'] = (string)$formvars->title;
$event_info['description'] = (string)$formvars->description;
$event_info['create_date'] = date('n/j/Y H:i');
$event_info['personalized_text'] = (string)$formvars->additionalText;

$emailtext = EventInfo::buildEmailForEvent($event_info, 'rfi');

print json_encode(array('status' => 'success', 'emailtext' => $emailtext));

?>
