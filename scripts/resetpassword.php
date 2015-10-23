<?php
/* takes input from reset password form and sends an email to reset the fetp password */
$formvars = json_decode(file_get_contents("php://input"));
require_once "UserInfo.class.php";
require_once "send_email.php";

// get user email
$user_email = strip_tags($formvars->username);
$fetp_id = UserInfo::getFETPid($user_email);
$userinfo = UserInfo::getUserInfobyEmail($user_email);

// send email to reset password
if ($fetp_id) {
    $action = 'resetpassword';
    sendMail($user_email, $userinfo['firstname'], "EpiCore Reset Password", $action, $fetp_id);
    $status = 'success';
}
else{
    $status = 'failed';
}

print json_encode(array('status' => $status));

?>
