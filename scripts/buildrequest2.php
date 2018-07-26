<?php
/*
 * Jeff Andre
 * 6/23/2017
 *
 * Build email for RFI
 *
*/
require_once "const.inc.php";
require_once "EventInfo.class.php";

$formvars = json_decode(file_get_contents("php://input"));

// build the email text
$event_info['title'] = (string)$formvars->title;

// if a file preview already exists, get rid of it
if(isset($formvars->file_preview) && file_exists("../".$formvars->file_preview)) {
    $filepreview = "../" . $formvars->file_preview;
    system("unlink $filepreview");
}
$ei = new EventInfo('0'); // event id doesn't matter for buildemail

$file_preview = $ei->buildEmailForEvent($event_info, 'rfi2', '', 'file');

print json_encode(array('status' => 'success', 'file_preview' => $file_preview));
?>
