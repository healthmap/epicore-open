<?php
require_once "UserInfo.class.php";

$mods = UserInfo::getMods();
if ($mods){
    $status = 'success';
} else {
    $status = 'failed';
}

// return moderators or error status
if($status == 'success') {
    print json_encode(array('status' => 'success', 'mods' =>$mods));
} else {
    print json_encode(array('status' => 'failed', 'message' => 'Failed to get moderators'));
}