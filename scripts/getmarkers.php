<?php
/* 
get lat/lons of FETPs to show on a map - only allow this for superusers
*/
$formvars = json_decode(file_get_contents("php://input"));
require_once "const.inc.php";
require_once 'db.function.php';
$fetps = array();

$db = getDB();
$all_fetps = $db->getAll("select m.maillist_id, m.animal_health, m.human_health, m.env_health, f.*
                            FROM fetp f
                            JOIN maillist m ON m.maillist_id = f.maillist_id
                            WHERE f.active='Y' 
                            AND f.status='A'");
$all_locations = $db->getAll("SELECT ml.maillist_id, ml.animal_health, ml.human_health, ml.env_health, f.fetp_id, m.*
							FROM member_location m
							JOIN fetp f ON f.fetp_id = m.fetp_id
							JOIN maillist ml ON ml.maillist_id = f.maillist_id");

// push the centerlat and centerlon onto fetp array for the center marker
// and add all fetps to array
if(is_numeric($formvars->centerlat) && is_numeric($formvars->centerlon)) {
    array_push($fetps, array("id" => 0, "icon" => "img/you.png", "latitude" => $formvars->centerlat, "longitude" => $formvars->centerlon, "show" => true, "title" => "Event Location", "animalExp" => "UNK"));
    foreach($all_fetps as $fetp){
        if(is_numeric($fetp['animal_health']) == 1) { //animal_health expertise
            array_push($fetps, array("id" =>$fetp['fetp_id'], "icon" => "img/member_animalHealthExp.png", "latitude" => $fetp['lat'], "longitude" => $fetp['lon'], "show" => true, "title" => "Event Location", "animalExp" => $fetp['animal_health']));
        } else {
            array_push($fetps, array("id" =>$fetp['fetp_id'], "icon" => "img/member.png", "latitude" => $fetp['lat'], "longitude" => $fetp['lon'], "show" => true, "title" => "Event Location", "animalExp" => $fetp['animal_health']));
        }
    }

    foreach($all_locations as $location){
        // array_push($fetps, array("id" =>$location['location_id'], "icon" => "img/member.png", "latitude" => $location['lat'], "longitude" => $location['lon'], "show" => true, "title" => "Event Location" "animalExp" => ""));
        $found = false;
        foreach($all_fetps as $fetp){
            if($location['fetp_id'] == ($fetp['fetp_id'])) { 
                if(is_numeric($fetp['animal_health']) == 1) { //animal_health expertise
                    $found = true;
                    array_push($fetps, array("id" =>$location['location_id'], "icon" => "img/member_animalHealthExp.png", "latitude" => $location['lat'], "longitude" => $location['lon'], "show" => true, "title" => "Event Location", "animalExp" => $fetp['animal_health']));
                    break;
                }
            }
        }
        if(!$found) {
            array_push($fetps, array("id" =>$location['location_id'], "icon" => "img/member.png", "latitude" => $location['lat'], "longitude" => $location['lon'], "show" => true, "title" => "Event Location", "animalExp" => "0"));
        }
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
