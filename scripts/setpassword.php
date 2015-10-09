<?php
/* takes input from the Epicore set password form, authenticates user, and sets password */
$formvars = json_decode(file_get_contents("php://input"));
require_once "UserInfo.class.php";
$status = 'failed';

// authenticate fetp
$ticket = strip_tags($formvars->ticket_id);
$fetpinfo = UserInfo::authenticateFetp($ticket);
$uinfo = UserInfo::getFETP($fetpinfo['fetp_id']);
$uinfo['username'] = "FETP ". $fetpinfo['fetp_id'];

// getpassword
$username = strip_tags($formvars->username);
$password = strip_tags($formvars->password);

// set password if username matches authenticated email
if(is_numeric($fetpinfo['fetp_id']) && ($fetpinfo['fetp_id'] > 0) && ($username == $uinfo['email'])) {
    $password_set = UserInfo::setFETPpassword($fetpinfo['fetp_id'],$password);
    if ($password_set)
        $status = 'success';
}

print json_encode(array('status' => $status, 'uinfo' => $uinfo));

?>
