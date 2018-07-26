<?php
/**
 * User: jeffandre
 * Date: 3/28/16
 *
 * Sets and returns member status.
 *
 */
$status = 'success';
$message = '';
$member_status = '';

// set member status
$data = json_decode(file_get_contents("php://input"));
$approve_id = strip_tags((string)$data->maillist_id);
$approve_status = strip_tags((string)$data->action);
if ($approve_id && $approve_status) {
    require_once 'UserInfo.class.php';
    UserInfo::setUserStatus($approve_id, $approve_status);
    $member_status = UserInfo::getMemberStatus($approve_id);
    if (!$member_status){
        $status = 'failed';
        $message = 'member not found';
    }
} else{
    $status = 'failed';
    $message = 'invalid paramters';
}

// return member status or error message
if($status == 'success') {
    print json_encode(array('status' => 'success', 'member_status' =>$member_status));
} else {
    print json_encode(array('status' => 'failed', 'message' => $message));
}

