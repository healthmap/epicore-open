<?php
/* 
get lat/lons of FETPs to show on a map - only allow this for superusers
*/

$formvars = json_decode(file_get_contents("php://input"));

require_once "const.inc.php";
require_once 'db.function.php';

$fetps = array();

$db = getDB();
$all_fetps = $db->getAll("select * from fetp where active='Y' and status='A'");


// push the centerlat and centerlon onto fetp array for the center marker
// and add all fetps to array
if(is_numeric($formvars->centerlat) && is_numeric($formvars->centerlon)) {
    array_push($fetps, array("id" => 0, "icon" => "img/you.png", "latitude" => $formvars->centerlat, "longitude" => $formvars->centerlon, "show" => true, "title" => "Event Location"));
    foreach($all_fetps as $fetp){
        array_push($fetps, array("id" =>$fetp['fetp_id'], "icon" => "img/member.png", "latitude" => $fetp['lat'], "longitude" => $fetp['lon'], "show" => true, "title" => "Event Location"));
    }
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
