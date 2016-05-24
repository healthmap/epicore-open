<?php
/* takes input from the Epicore set password form, authenticates user, and sets password */
$formvars = json_decode(file_get_contents("php://input"));
require_once "UserInfo.class.php";
$status = 'failed';

// authenticate fetp and get info
$ticket = strip_tags($formvars->ticket_id);
$authfetp = UserInfo::authenticateFetp($ticket);
$fetpinfo = UserInfo::getFETP($authfetp['fetp_id']);
$fetpinfo['username'] = "MEMBER ". $authfetp['fetp_id'];

// get username/email and password
$username = strip_tags($formvars->username);
$password = strip_tags($formvars->password);

// set password if username matches authenticated email
$emailmatch = (strcasecmp($fetpinfo['email'], $username) == 0) ? true: false;
if(is_numeric($authfetp['fetp_id']) && ($authfetp['fetp_id'] > 0) && $emailmatch) {
    $password_set = UserInfo::setFETPpassword($authfetp['fetp_id'],$password);
    if ($password_set){
        $status = 'success';

        // geocode fetp if not already done
        if (!$fetpinfo['lat']){
            UserInfo::geocodeFETP($fetpinfo['email']);
        }

        // set user active for pending_preapproved users (active = N, status = A)
        if (($fetpinfo['active'] == 'N') && ($fetpinfo['status'] == 'A')){
            $uinfo = UserInfo::getUserInfobyEmail($fetpinfo['email']);
            UserInfo::setUserStatus($uinfo['maillist_id'], 'preapproved');
            $fetpinfo['active'] = 'Y';
        }
    }
}

print json_encode(array('status' => $status, 'uinfo' => $fetpinfo));

?>
