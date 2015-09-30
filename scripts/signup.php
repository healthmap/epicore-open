<?php 
// clearn variables
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
}

?>
