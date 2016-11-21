<?php
/**
 * User: jeffandre
 * Date: 11/21/16
 *
 * Sets and returns member location status.
 *
 */
$status = 'success';
$message = '';
$location_status = '';

// set member status
$data = json_decode(file_get_contents("php://input"));
$member_id = strip_tags((string)$data->maillist_id);
$location_status = strip_tags((string)$data->action);
if ($member_id && $location_status) {
    require_once 'UserInfo.class.php';
    $location_status = UserInfo::setLocationStatus($member_id, $location_status);
    if (!$location_status){
        $status = 'failed';
        $message = 'member not found';
    } else {
        $status = 'success';
    }
} else{
    $status = 'failed';
    $message = 'invalid paramters';
}

// return location status or error message
if($status == 'success') {
    print json_encode(array('status' => 'success', 'location_status' =>$location_status));
} else {
    print json_encode(array('status' => 'failed', 'message' => $message));
}

