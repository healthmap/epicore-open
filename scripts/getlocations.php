<?php
/**
 * User: jeffandre
 * Date: 10/28/16
 */
require_once "UserInfo.class.php";
require_once "const.inc.php";
require_once "UserContoller3.class.php";

use UserController as userController;

$userData = userController::getUserData();


// get data
$data = json_decode(file_get_contents("php://input"));
$fetp_id = isset($userData['fetp_id']) ? $userData['fetp_id'] : null;

if ($fetp_id) {
    $locations = UserInfo::getLocations($fetp_id);
    if ($locations) {

        // get location data
        $std_countries = unserialize(COUNTRIES);
        $loc = array();
        $mlocations = array();
        foreach($locations as $location) {
            $loc['location_id'] = $location['location_id'];
            $loc['country'] = $std_countries[$location['countrycode']];
            $loc['city'] = $location['city'];
            $loc['state'] = $location['state'];
            array_push($mlocations, $loc);
        }

        $status = 'success';
    } else {
        $status = 'failed';
        $message = 'failed to get locations';
    }
} else {
    $status = 'failed';
    $message = 'invalid parameters';
}
// return locations or error status
if($status == 'success') {
    print json_encode(array('status' => 'success', 'locations' =>$mlocations));
} else {
    print json_encode(array('status' => 'failed', 'message' => $message));
}