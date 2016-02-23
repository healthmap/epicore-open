<?php
/*
* Jeff Andre, Feb 21 2016
* gets applicant info
*/
require_once "const.inc.php";
$formvars = json_decode(file_get_contents("php://input"));
$uid = strip_tags($formvars->uid);

// get info about specific event
if($uid) {
    // get the event
    require_once "db.function.php";
    $db = getDB();
    require_once "UserInfo.class.php";
    $applicant = UserInfo::getUserInfo($uid);

    if (!$applicant){
        print json_encode(array('status' => 'failed', 'reason' => 'invalid applicant id'));
        exit;
    }
} else {
    print json_encode(array('status' => 'failed', 'reason' => 'missing required parameters uid:' . $uid));
    exit;
}

header('content-type: application/json; charset=utf-8');
print json_encode($applicant);
exit;

?>
