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

/*
// insert the api hit into the log
$db->query("INSERT INTO api_log (api_id, hit_date, query) VALUES (?, ?, ?)", array($api_id, date('Y-m-d H:i:s'), $qs));
$db->commit();
*/

// get the events
require_once "EventInfo.class.php";
// if an event id is passed in, get info about specific event
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
    // status can be "closed" or "open"
    require_once "UserInfo.class.php";
    $ui = new UserInfo($rvars['uid'], $rvars['fetp_id']);
    $status = isset($rvars['detail']) && $rvars['detail'] == "closed" ? 'C' : 'O';
    if($rvars['fetp_id']) {
        // array values will lop off the array key b/c angular reorders the object
        $indexed_array = array_values($ui->getFETPRequests($status));
    } else {
        $indexed_array = EventInfo::getAllEvents($rvars['uid'], $status);
        if ($status == 'O') {  // get closed events to check for unrated respsonses
            $closed_events = EventInfo::getAllEvents($rvars['uid'], 'C');
        }
    }
}

header('content-type: application/json; charset=utf-8');
$json = json_encode(array('EventsList' => $indexed_array, 'closedEvents' => $closed_events));

// return JSONP if it's client-side request
print isset($_GET['callback']) ? "{$_GET['callback']}($json)" : $json;

?>
