<?php
/* 
get lat/lons of FETPs to show on a map - only allow this for superusers
*/

$formvars = json_decode(file_get_contents("php://input"));

require_once "const.inc.php";
//if(in_array($formvars->uid, $super_users)) {
//    require_once "UserInfo.class.php";
//    $fetps = UserInfo::getFETPs();
//} else {
    $fetps = array();
//}

// push the centerlat and centerlon onto fetp array for the center marker
if(is_numeric($formvars->centerlat) && is_numeric($formvars->centerlon)) {
    array_push($fetps, array("id" => 0, "icon" => "img/you.png", "latitude" => $formvars->centerlat, "longitude" => $formvars->centerlon, "show" => true, "title" => "Event Location"));
    print json_encode(array('status' => 'success', 'markers' => $fetps));
    exit;
}

if(empty($fetps)) {
    print json_encode(array('status' => 'error', 'reason' => 'permission denied, and no center specified'));
} else {
    print json_encode(array('status' => 'success', 'markers' => $fetps));
}
exit;
?>
