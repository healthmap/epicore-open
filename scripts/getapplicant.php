<?php
/*
* Jeff Andre, Feb 21 2016
* gets applicant info
*/

require_once "const.inc.php";
require_once  "UserContoller3.class.php";

use UserController as userController;

$userData = userController::getUserData();

$formvars = json_decode(file_get_contents("php://input"));
if (isset($formvars->uid)) {
    $uid = $userData["uid"];
}
if (isset($formvars->fetp_id)) {
    $uid = $userData["fetp_id"];
}

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
        echo json_encode(array('status' => 'failed', 'reason' => 'invalid member id'));
        exit;
    }
} else {
    echo json_encode(array('status' => 'failed', 'reason' => 'missing required parameters'));
    exit;
}

echo json_encode($applicant);
exit;

?>
