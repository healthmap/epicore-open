<?php
/**
 * User: jeffandre
 * Date: 10/28/16
 */
require_once "UserInfo.class.php";

// get data
$data = json_decode(file_get_contents("php://input"));
$lid = strip_tags((string)$data->location_id);

if ($lid) {
    $q = UserInfo::deleteLocation($lid);
    if ($q) {
        $status = $q['status'];
        $message = $q['message'];
    } else {
        $status = 'failed';
        $message = 'database access failure';
    }
} else {
    $status = 'failed';
    $message = 'invalid parameters';
}
// return status
print json_encode(array('status' => $status, 'message' =>$message));
