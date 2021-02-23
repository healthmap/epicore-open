<?php
/**
 *
 * Add Member location.
 *
 */

// get data
$data = json_decode(file_get_contents("php://input"));
$pvals = array();
$pvals['city'] = strip_tags((string)$data->city);
$pvals['state'] = strip_tags((string)$data->state);
$pvals['countrycode'] = strip_tags((string)$data->countrycode);
$pvals['lat'] = strip_tags((string)$data->latitude);
$pvals['lon'] = strip_tags((string)$data->longitude);
$pvals['fetp_id'] = strip_tags((string)$data->fetp_id);

// add location
$status = 'success';
$message = '';
$location_id = '';

if ($pvals['city'] && $pvals['state'] && $pvals['countrycode'] && $pvals['lat'] && $pvals['lon']) {
    require_once 'UserInfo.class.php';
    $location_status = UserInfo::addLocation($pvals);
    if (is_numeric($location_status)){
        $status = 'success';
        $location_id = $location_status;
    } else {
        $status = 'failed';
        $message = 'location already exists.';
    }

} else{
    $status = 'failed';
    $message = 'invalid parameters';
}

// return mod id or error status
if($status == 'success') {
    print json_encode(array('status' => 'success', 'location_id' =>$location_id));
} else {
    print json_encode(array('status' => 'failed', 'message' => $message));
}
