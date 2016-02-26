<?php
/*
* Jeff Andre, Feb 21 2016
* gets applicant info
*/
require_once "const.inc.php";
$formvars = json_decode(file_get_contents("php://input"));
$uid = strip_tags($formvars->uid);
$idtype = strip_tags($formvars->idtype);

// get info about specific event
if($uid && $idtype) {
    // get the event
    require_once "db.function.php";
    $db = getDB();
    require_once "UserInfo.class.php";
    if ($idtype == 'fetp'){
        $fetp_info = UserInfo::getFETP($uid);
        $mid = $fetp_info['maillist_id'];
        $applicant = UserInfo::getUserInfo($fetp_info['maillist_id']);
    }else{
        $applicant = UserInfo::getUserInfo($uid);
    }

    if (!$applicant){
        print json_encode(array('status' => 'failed', 'reason' => 'invalid member id'));
        exit;
    }
} else {
    print json_encode(array('status' => 'failed', 'reason' => 'missing required parameters'));
    exit;
}

header('content-type: application/json; charset=utf-8');
print json_encode($applicant);
exit;

?>
