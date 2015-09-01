<?php 
// clearn variables
$formvars = json_decode(file_get_contents("php://input"));
$pvals['email'] = strip_tags($formvars->email);
$pvals['firstname'] = strip_tags($formvars->firstname);
$pvals['lastname'] = strip_tags($formvars->lastname);
$pvals['country'] = strip_tags($formvars->country);

// exit if no email
if(!$pvals['email']) {
    print json_encode(array('status' => 'failed', 'reason' => 'No email specified'));
    exit;
}

require_once "UserInfo.class.php";
$user_id = UserInfo::joinMaillist($pvals);

if(!$user_id) {
    print json_encode(array('status' => 'failed', 'reason' => 'User could not be inserted'));
} else {
    print json_encode(array('status' => 'success', 'uid' => $user_id));
}

?>
