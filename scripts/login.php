<?php
/* take the input from the Epicore login form and authenticate user */
$formvars = json_decode(file_get_contents("php://input"));

require_once "const.inc.php";
require_once "UserInfo.class.php";
$status = "incorrect password";
$path = "home";

if(isset($formvars->ticket_id) && $formvars->usertype == "fetp") { // ticket system is for FETPs
    $uinfo = UserInfo::authenticateFetp(strip_tags($formvars->ticket_id));
    $user_id = $uinfo['fetp_id'];
    // for now set the fetp_id as the username
    $uinfo['username'] = "Member $user_id";
} else {
    if($formvars->ticket_id) { // ticket system for mods coming from dashboard
        $uinfo = UserInfo::authenticateMod($formvars->ticket_id);
    } else { // login system is for mods
        $dbdata['email'] = strip_tags($formvars->username);
        $dbdata['password'] = strip_tags($formvars->password); 
        $uinfo = UserInfo::authenticateUser($dbdata);
    }
    $user_id = isset($uinfo['fetp_id']) ? $uinfo['fetp_id'] : $uinfo['user_id'];
}

// make sure it's a valid user id (or fetp id)
if(is_numeric($user_id) && $user_id > 0) {
    // if it was a mod who successfully logged in, let's now repopulate the fetp table with latest eligible tephinet ids
    //if(isset($uinfo['organization_id']) && $uinfo['organization_id'] > 0) {
    //    $ui = new UserInfo($user_id);
    //    $ui->getFETPEligible();
    //}
    $status = "success";
    // if it was a ticket with an event id, go directly to the "respond" page
    // if it was a ticket with an alert id, go directly to the "request" page
    if(isset($formvars->event_id) && is_numeric($formvars->event_id)) {
        $path = "events/".$formvars->event_id;
    } elseif (isset($formvars->alert_id) && is_numeric($formvars->alert_id)) {
        $path = "request/".$formvars->alert_id;
    } else {
        $path = "events";
    }
    $uinfo['superuser'] = (isset($uinfo['user_id']) && in_array($uinfo['user_id'], $super_users)) ? true: false;
}

print json_encode(array('status' => $status, 'path' => $path, 'uinfo' => $uinfo));
?>
