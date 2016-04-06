<?php
/* 
get lat/lons of all FETPs to show on a map
*/

require_once "const.inc.php";
require_once 'db.function.php';

$fetps = array();

$db = getDB();
$all_fetps = $db->getAll("select * from fetp where active='Y' and status='A'");


// add all fetps to array
foreach($all_fetps as $fetp){
        array_push($fetps, array("id" =>$fetp['fetp_id'], "icon" => "img/member.png", "latitude" => $fetp['lat'], "longitude" => $fetp['lon'], "show" => true, "title" => "Member Location"));
}


if(empty($fetps)) {
    print json_encode(array('status' => 'error', 'reason' => 'No Members'));
} else {
    print json_encode(array('status' => 'success', 'markers' => $fetps));
}
exit;
?>
