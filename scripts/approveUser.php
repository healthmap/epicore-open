<?php
/**
 * User: jeffandre
 * Date: 2/25/16
 *
 * Sets user status and sends email.
 *
 */
require_once 'db.function.php';
require_once 'UserInfo.class.php';

$db = getDB();

$status = 'success';
$message = '';
// get applicant and set status
$data = json_decode(file_get_contents("php://input"));
$fetp_id = strip_tags((string)$data->fetp_id);
$approve_status = strip_tags((string)$data->status);

// set status and send email
if ($fetp_id && $approve_status){
    $fetp_info = UserInfo::getFETP($fetp_id);
    if ($fetp_info['maillist_id']){
        // approve
        UserInfo::setUserStatus($fetp_info['maillist_id'], $approve_status);
        // set user has taken online course
        $online = true;
        $inperson = false;
        UserInfo::setCourseType($fetp_info['maillist_id'], $online, $inperson);

    }else{
        $status = 'failed';
        $message = 'fetp does not exist.';
    }
} else{
    $status = 'failed';
    $message = 'invalid paramters.';
}

// return status
if($status == 'success') {
    print json_encode(array('status' => 'success'));
} else {
    print json_encode(array('status' => 'failed', 'message' => $message));
}

?>
