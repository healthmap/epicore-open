<?php
/* process the Epicore RFI form 
- part of step 2 : filters FETPs chosen
*/
require_once "UserInfo.class.php";
$formvars = json_decode(file_get_contents("php://input"));

// training choices filter is not being used at the moment - may come back at some point
//$filter = isset($formvars->trainingchoices) ? $formvars->trainingchoices : array();
$bbox = '';

if($formvars->filtertype == "country") {
    if (!isset($formvars->countries) || empty($formvars->countries)) {
        $userinfo = array( array('sending' => 0, 'all' => 0, 'ddd' => 0, 'graduate' => 0, 'na' => 0, 'trainee' => 0, 'unspecified' => 0), array(), array());
    } else {
        $userinfo = UserInfo::getFETPsInLocation('countries', $formvars->countries);
    }
    $calledfrom = "country";
} else {
    $calledfrom = "radius";
    if(!isset($formvars->bbox)) {
        // get FETP info: first get the bounding box from lat/lon
        require_once "PlaceInfo.class.php";
<<<<<<< HEAD
        list($lat,$lon) = split(",", (string)$formvars->latlon);
=======
        list($lat,$lon) = explode(",", (string)$formvars->latlon);
>>>>>>> epicore-ng/main
        $radius = (int)$formvars->radius ? (int)$formvars->radius : DEFAULT_RADIUS;
        $bbox = PlaceInfo::getBoundingBox($lat, $lon, $radius);
    } else {
        $bbox = $formvars->bbox;
    }
    // get the FETPs in that bounding box
    $userinfo = UserInfo::getFETPsInLocation('radius', $bbox);
}

print json_encode(array('status' => 'success', 'calledfrom' => $calledfrom, 'bbox' => $bbox, 'userList' => $userinfo[0], 'userIds' => $userinfo[1], 'uniqueList' => $userinfo[2]));

?>
