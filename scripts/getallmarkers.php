<?php
/* 
get lat/lons of all FETPs to show on a map
*/

require_once "const.inc.php";
require_once 'db.function.php';

$fetps = array();

$db = getDB();
$all_fetps = $db->getAll("select * from fetp where active='Y' and status='A'");

$country_members = array();
$std_countries = unserialize(COUNTRIES);
// add all fetps to array
foreach($all_fetps as $fetp){
    $country = $std_countries[$fetp['countrycode']];
    array_push($fetps, array("id" =>$fetp['fetp_id'], "icon" => "img/member.png", "latitude" => $fetp['lat'], "longitude" => $fetp['lon'], "country" => $country, "show" => true, "title" => "Member Location"));
    if ($country){
        if ($country_members[$country] >= 1)
            $country_members[$country]++;
        else
            $country_members[$country] = 1;
    }
}


if(empty($fetps)) {
    print json_encode(array('status' => 'error', 'reason' => 'No Members'));
} else {
    print json_encode(array('status' => 'success', 'markers' => $fetps, 'country_members' => $country_members));
}
exit;
?>
