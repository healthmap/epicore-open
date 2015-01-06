<?php
/* 
get lat/lons of FETPs to show on a map - only allow this for superusers
*/
$formvars = json_decode(file_get_contents("php://input"));
require_once "const.inc.php";
if(!in_array($formvars->uid, $super_users)) {
    print json_encode(array('status' => 'failed', 'reason' => 'permission denied', 'uid' => $formvars->uid));
    exit;
}
require_once "UserInfo.class.php";
$fetps = UserInfo::getFETPs();
print json_encode(array('status' => 'success', 'markers' => $fetps));
exit;
?>
