<?php
/*
Return information about an event, or get all events
required param: auth
optional param: event_id, from (responses, followup, events), detail (closed)
*/

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

require_once "db.function.php";
$db = getDB();

// get the events
require_once "EventInfo.class.php";

if(isset($rvars['event_id']) && is_numeric($rvars['event_id'])) {    
    $ei = new EventInfo($rvars['event_id']);
    if($rvars['from'] == "responses") {
        $indexed_array = $ei->getResponses();
    } else {
        $indexed_array = $ei->getInfo(); 
        $indexed_array['filePreview'] = $ei->buildEmailForEvent($indexed_array, 'rfi', '', 'file'); 
        $indexed_array['estatus'] = $ei->getEventStatus();
        $indexed_array['history'] = $ei->getEventHistory();
        $indexed_array['fetp_ids'] = $ei->getFETPRecipients();
    }
} else { // get all events
    $start_date = $rvars['start_date'] ? $rvars['start_date']: V2START_DATE;
    $end_date = $rvars['end_date'] ? $rvars['end_date'] : date("Y-m-d H:i:s");

    if ($rvars['public']){
        // get closed events for public
        $uid = '0'; // no user id value
        //$indexed_array = EventInfo::getEventsCache($uid, 'C', 'database', V2START_DATE);
        $indexed_array = EventInfo::getAllEvents($uid, 'C', $start_date, $end_date);

    } else {
        // status can be "closed" or "open"
        require_once "UserInfo.class.php";
        $ui = new UserInfo($rvars['uid'], $rvars['fetp_id']);
        $status = isset($rvars['detail']) && $rvars['detail'] == "closed" ? 'C' : 'O';
        $num_notrated_repsonses = 0;
        if ($rvars['fetp_id']) {
            // array values will lop off the array key b/c angular reorders the object
            // $indexed_array = array_values($ui->getFETPRequests($status, '', V2START_DATE));
            $indexed_array = is_array($ui->getFETPRequests($status, '', V2START_DATE))? array_values($ui->getFETPRequests($status, '', V2START_DATE)): array(); 
        } else {
            $indexed_array = EventInfo::getAllEvents($rvars['uid'], $status, $start_date, $end_date);
            if ($status == 'O') {  // check for unrated respsonses
                $num_notrated_repsonses = EventInfo::getNumNotRatedResponses($rvars['uid'], V2START_DATE);
            }
        }
    }
}

// header('content-type: application/json; charset=utf-8');
$json = json_encode(array('EventsList' => $indexed_array, 'closedEvents' => $closed_events, 'numNotRatedResponses' => $num_notrated_repsonses));

// return JSONP if it's client-side request
print isset($_GET['callback']) ? "{$_GET['callback']}($json)" : $json;

?>
