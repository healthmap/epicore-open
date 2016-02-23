<?php
require_once "UserInfo.class.php";

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

require_once "UserInfo.class.php";
$user_info = UserInfo::updateMaillist($pvals);
$status = $user_info[0];
$message = $user_info[1];

if($status == 'success') {
    print json_encode(array('status' => 'success'));
} else {
    print json_encode(array('status' => 'failed', 'reason' => $message));
}

?>
