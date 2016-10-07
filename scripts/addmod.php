<?php
/**
 *
 * Add Moderator.
 *
 */
// validate user

// get data
$data = json_decode(file_get_contents("php://input"));
$mod_email = strip_tags((string)$data->mod_email);
$mod_org_id = strip_tags((string)$data->mod_org_id);

// add mod
if ($mod_email && $mod_org_id) {
    require_once 'UserInfo.class.php';
    $mod_status = UserInfo::addMod($mod_email, $mod_org_id);
    if (is_numeric($mod_status)){
        $status = 'success';
        $mod_id = $mod_status;
    } else {
        $status = 'failed';
        $message = $mod_status;
    }
} else{
    $status = 'failed';
    $message = 'invalid paramters';
}

// return mod id or error status
if($status == 'success') {
    print json_encode(array('status' => 'success', 'mod_id' =>$mod_id));
} else {
    print json_encode(array('status' => 'failed', 'message' => $message));
}

