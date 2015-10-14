<?php
require_once "const.inc.php";
require_once "AWSMail.class.php";

// clean variables
$formvars = json_decode(file_get_contents("php://input"));

foreach($formvars as $name => $val) {
    $pvals[$name] = strip_tags($val);
}

// exit if no email
if(!$pvals['email']) {
    print json_encode(array('status' => 'failed', 'reason' => 'No email specified'));
    exit;
}
$pvals['apply_date'] = date('Y-m-d H:i:s');

require_once "UserInfo.class.php";
$user_info = UserInfo::applyMaillist($pvals);
$exists = $user_info[0];
$user_id = $user_info[1];

if(!$user_id) {
    print json_encode(array('status' => 'failed', 'reason' => 'User could not be inserted'));
} else {
    print json_encode(array('status' => 'success', 'uid' => $user_id, 'exists' => $exists));
    sendMail($pvals['email'], $pvals['firstname'],'EpiCore Application Received');
}


function sendMail($email, $name, $subject){
    // send email
    $emailtemplate = file_get_contents("../emailtemplates/application.html");
    $extra_headers['text_or_html'] = "html";
    $emailtext = str_replace("[NAME]", $name, $emailtemplate);
    $emailtext = str_replace("[SUBJECT]", $subject, $emailtext);
    $aws_resp = AWSMail::mailfunc($email, $subject, $emailtext, EMAIL_INFO_EPICORE, $extra_headers);
}
?>
