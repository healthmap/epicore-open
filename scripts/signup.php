<?php
require_once "const.inc.php";
require_once "AWSMail.class.php";
require_once "send_email.php";
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
$pvals['apply_date'] = date('Y-m-d H:i:s');

require_once "UserInfo.class.php";
$user_info = UserInfo::applyMaillist($pvals);
$exists = $user_info[0];
$user_id = $user_info[1];

if($exists) {
    print json_encode(array('status' => 'failed', 'reason' => 'User already exists and could not be inserted'));
} else {
    print json_encode(array('status' => 'success', 'uid' => $user_id, 'exists' => $exists));

    // send login/set password email to users that have taken the course
    $fetpid = UserInfo::getFETPid($pvals['email']);
    $fetpinfo = UserInfo::getFETP($fetpid);
    if (($fetpinfo['active'] == 'N') && ($fetpinfo['status'] == 'A')){
        $status = 'preapproved';
        sendMail($pvals['email'], $pvals['firstname'],'EpiCore Project Official Launch', $status, $fetpid);
        //for debugging only
        // $awsMailResult = sendMail($pvals['email'], $pvals['firstname'],'EpiCore Project Official Launch', $status, $fetpid);
        // print json_encode(array('aws-mail-status-for-preapproved' => $awsMailResult));
    }
    else{ // send application received email to others
        $status = 'apply';
        sendMail($pvals['email'], $pvals['firstname'],'EpiCore Application Received', $status, $user_id);
        //for debugging only
        //$awsMailResult = sendMail($pvals['email'], $pvals['firstname'],'EpiCore Application Received', $status, $user_id);
        //print json_encode(array('aws-mail-status-for-apply' => $awsMailResult));
    }
}

?>
