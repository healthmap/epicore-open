<?php
require_once "UserInfo.class.php";
require_once "send_email.php";

// clean variables
$formvars = json_decode(file_get_contents("php://input"));
$uid = strip_tags($formvars->uid);

// exit if no user id
if(!$uid) {
    print json_encode(array('status' => 'failed', 'reason' => 'missing required parameters uid:' . $uid));
    exit;
}

require_once "UserInfo.class.php";
$uinfo = UserInfo::getUserInfo($uid);
$action = 'delete';
sendMail($uinfo['email'], $uinfo['firstname'], "EpiCore Unsubscription Notification", $action, $uinfo['maillist_id']);

$result = UserInfo::deleteMaillist($uid);
$status = $result[0];
$message = $result[1];

if($status == 'success') {
    print json_encode(array('status' => 'success'));
} else {
    print json_encode(array('status' => 'failed', 'reason' => $message));
}

?>
