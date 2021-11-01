<?php
/*
Return information about an event, or get all events
required param: auth
optional param: event_id, from (responses, followup, events), detail (closed)
*/

<<<<<<< HEAD
=======
require_once "UserContoller3.class.php";

use UserController as userController;

>>>>>>> epicore-ng/main
// check for authoriziation token in query string
if(!$_GET['auth']) {
    print "Sorry you are not authorized to use this service.";
    exit;
}

// sanitize incoming variables
foreach($_GET as $key => $val) {
    $val = strip_tags($val);
    if($key != "auth") {
        if($qs) { $qs .= "&"; }
        $qs .= "$key=$val";
    }
    $rvars[$key] = $val;
}

<<<<<<< HEAD
require_once "db.function.php";
$db = getDB();

/*
// insert the api hit into the log
$db->query("INSERT INTO api_log (api_id, hit_date, query) VALUES (?, ?, ?)", array($api_id, date('Y-m-d H:i:s'), $qs));
$db->commit();
*/

// get the events
require_once "EventInfo.class.php";
if(isset($rvars['event_id']) && is_numeric($rvars['event_id'])) {
    $ei = new EventInfo($rvars['event_id']);
    if($rvars['from'] == "responses") {
        $indexed_array = $ei->getResponses();
    } else {
=======
$rvars["uid"] = $userData["uid"];
$rvars["fetp_id"] = $userData["fetp_id"];


//logged user verification
if($rvars['from'] != "events_public") {
   
    if (!userController::isUserValid()) {
        // echo json_encode(false);
        return false;
    }
}
$userData = userController::getUserData();

require_once "db.function.php";
$db = getDB();

// get the events
require_once "EventInfo.class.php";

if(isset($rvars['event_id']) && is_numeric($rvars['event_id'])) {    
    $ei = new EventInfo($rvars['event_id']);
    
    if($rvars['from'] == "responses") {
        //  echo 'from responses page';
        $indexed_array = $ei->getResponses();
    
    } else if($rvars['from'] == "events_public") { //for the articles page
        // echo 'public rfi details page';
        $indexed_arrayRow = $ei->getInfo(); 
        //filePreview: Not required for now - do not remove
        //$indexed_arrayFile['filePreview'] = $ei->buildEmailForEvent($indexed_arrayRow, 'rfi', '', 'file'); 
        $public_dash_row = EventInfo::fetchPublicDashboardValuesOnly($indexed_arrayRow);

        if($public_dash_row['outcome'] === 'VP' || $public_dash_row['outcome'] === 'VN' || $public_dash_row['outcome'] === 'UP') {
            //echo '1.-page';
            $indexed_array = $public_dash_row;
            $indexed_array['estatus'] = $ei->getEventStatus();
            $indexed_array['history'] = $ei->getEventHistory();

            //histoy contains all extra info which need to be cleaned for public rfi view
            foreach($indexed_array['history'] as $elementKey => $element) {
                foreach($element as $valueKey => $value) {
                    if($valueKey && $valueKey !== 'date'){
                        //delete this particular object from the $indexed_array
                        unset($indexed_array['history'][$elementKey][$valueKey]);
                    } 
                }
            }  
               
            //fetpId filepreview: Not required for now - do not remove
            // $indexed_array['fetp_ids'] = $ei->getFETPRecipients();
            // $indexed_array['filePreview'] =  $indexed_arrayFile['filePreview'];
        } else {
            //no public RFI(s) to display
            $indexed_array = array();
            $indexed_array['error-message'] = 'Restricted information. Please contact your administrator';
        }
        

    } else {
         //echo 'with login-single-event-id-view-page';
>>>>>>> epicore-ng/main
        $indexed_array = $ei->getInfo(); 
        $indexed_array['filePreview'] = $ei->buildEmailForEvent($indexed_array, 'rfi', '', 'file'); 
        $indexed_array['estatus'] = $ei->getEventStatus();
        $indexed_array['history'] = $ei->getEventHistory();
        $indexed_array['fetp_ids'] = $ei->getFETPRecipients();
    }
} else { // get all events
<<<<<<< HEAD
    if ($rvars['public']){
        // get closed events for public
        $uid = '0'; // no user id value
        $indexed_array = EventInfo::getEventsCache($uid, 'C', 'database', V2START_DATE);

    } else {
        // status can be "closed" or "open"
        require_once "UserInfo.class.php";
        $ui = new UserInfo($rvars['uid'], $rvars['fetp_id']);
        $status = isset($rvars['detail']) && $rvars['detail'] == "closed" ? 'C' : 'O';
        $num_notrated_repsonses = 0;
        if ($rvars['fetp_id']) {
            // array values will lop off the array key b/c angular reorders the object
            $indexed_array = array_values($ui->getFETPRequests($status, '', V2START_DATE));
        } else {
            if ($status == 'C') {
                //$indexed_array = EventInfo::getEventsCache($rvars['uid'], 'C', 'cache');
                // use database for now until cache update is working: need to update cache when status changes.
                // Status of an event can change from the dashboard or from the auto-close cron job
                $indexed_array = EventInfo::getEventsCache($rvars['uid'], 'C', 'database', V2START_DATE);
            } else {
                $indexed_array = EventInfo::getAllEvents($rvars['uid'], $status, V2START_DATE);
            }
            if ($status == 'O') {  // check for unrated respsonses
                $num_notrated_repsonses = EventInfo::getNumNotRatedResponses($rvars['uid'], V2START_DATE);
=======
    $start_date = $rvars['start_date'] ? $rvars['start_date']: V2START_DATE;
    $end_date = $rvars['end_date'] ? $rvars['end_date'] : date("Y-m-d H:i:s");
    
    if ($rvars['public']){
        //  echo 'public rfi list page';
        // get closed events for public
        $uid = '0'; // no user id value
        //$indexed_array = EventInfo::getEventsCache($uid, 'C', 'database', V2START_DATE);
        $indexed_array = EventInfo::getAllEvents($uid, 'C', $start_date, $end_date);

    } else {
        // echo 'with login-dashboard-list-page';
        // status can be "closed" or "open"
        require_once "UserInfo.class.php";
        $ui = new UserInfo($userData['uid'], $userData['fetp_id']);
        $status = isset($rvars['detail']) && $rvars['detail'] == "closed" ? 'C' : 'O';
        $num_notrated_repsonses = 0;
        if ($userData['fetp_id']) {
            // array values will lop off the array key b/c angular reorders the object
            // $indexed_array = array_values($ui->getFETPRequests($status, '', V2START_DATE));
            $indexed_array = is_array($ui->getFETPRequests($status, '', V2START_DATE))? array_values($ui->getFETPRequests($status, '', V2START_DATE)): array(); 
        } else {
            $indexed_array = EventInfo::getAllEvents($userData['uid'], $status, $start_date, $end_date);
            if ($status == 'O') {  // check for unrated respsonses
                $num_notrated_repsonses = EventInfo::getNumNotRatedResponses($userData['uid'], V2START_DATE);
>>>>>>> epicore-ng/main
            }
        }
    }
}

<<<<<<< HEAD
header('content-type: application/json; charset=utf-8');
=======
// header('content-type: application/json; charset=utf-8');
>>>>>>> epicore-ng/main
$json = json_encode(array('EventsList' => $indexed_array, 'closedEvents' => $closed_events, 'numNotRatedResponses' => $num_notrated_repsonses));

// return JSONP if it's client-side request
print isset($_GET['callback']) ? "{$_GET['callback']}($json)" : $json;

?>
