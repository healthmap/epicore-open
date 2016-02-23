<?php
require_once "UserInfo.class.php";

// clean variables
$formvars = json_decode(file_get_contents("php://input"));
$uid = strip_tags($formvars->uid);

// exit if no user id
if(!$uid) {
    print json_encode(array('status' => 'failed', 'reason' => 'missing required parameters uid:' . $uid));
    exit;
}

require_once "UserInfo.class.php";
$user_info = UserInfo::deleteMaillist($uid);
$status = $user_info[0];
$message = $user_info[1];

if($status == 'success') {
    print json_encode(array('status' => 'success'));
} else {
    print json_encode(array('status' => 'failed', 'reason' => $message));
}

?>
