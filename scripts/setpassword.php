<?php
/* takes input from the Epicore set password form, authenticates user, and sets password */
$formvars = json_decode(file_get_contents("php://input"));
require_once "UserInfo.class.php";
$status = 'failed';

// authenticate fetp and get info
$ticket = strip_tags($formvars->ticket_id);
$authfetp = UserInfo::authenticateFetp($ticket);
$fetpinfo = UserInfo::getFETP($authfetp['fetp_id']);
$fetpinfo['username'] = "FETP ". $authfetp['fetp_id'];

// get username/email and password
$username = strip_tags($formvars->username);
$password = strip_tags($formvars->password);

// set password if username matches authenticated email
if(is_numeric($authfetp['fetp_id']) && ($authfetp['fetp_id'] > 0) && ($username == $fetpinfo['email'])) {
    $password_set = UserInfo::setFETPpassword($authfetp['fetp_id'],$password);
    if ($password_set){
        $status = 'success';

        // geocode fetp if not already done
        if (!$fetpinfo['lat']){
            UserInfo::geocodeFETP($fetpinfo['email']);
        }
    }
}

print json_encode(array('status' => $status, 'uinfo' => $fetpinfo));

?>
