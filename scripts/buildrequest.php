<?
/* 
 Sue Aman 1/2015
 process the Epicore RFI form 
 this is called upon the second form submission
 takes event info and builds the email html template
 makes it a temporary html file so it can be served as an iframe in step 3
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

// if a file preview already exists, get rid of it
if(isset($formvars->file_preview) && file_exists("../".$formvars->file_preview)) {
    $filepreview = "../" . $formvars->file_preview;
    system("unlink $filepreview");
}

$file_preview = EventInfo::buildEmailForEvent($event_info, 'rfi', '', 'file');

print json_encode(array('status' => 'success', 'file_preview' => $file_preview));
?>
