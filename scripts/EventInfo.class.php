<?php
/**
 * EventInfo.php
 * Sue Aman 5 Jan 2010
 * info about an individual alert
 */

require_once 'db.function.php';
require_once 'const.inc.php';
require_once 'PlaceInfo.class.php';
//require_once 'cache.function.php';

class EventInfo
{
    function __construct($id)
    {
        $this->id = $id;
        $this->db =& getDB();
    }

    function getInfo() {
        $event_info = $this->db->getRow("SELECT event.*, place.name AS location FROM event, place WHERE event_id = ? AND event.place_id = place.place_id", array($this->id));
        $event_info['org_requester_id'] = self::getOrganizationOfRequester();
        $event_info['html_description'] = str_replace("\n", "<br>", $event_info['description']);
        $event_info['num_responses'] = $this->db->getOne("SELECT count(*) FROM response WHERE event_id = ?", array($this->id));
        $event_info['create_date'] = date('n/j/Y H:i', strtotime($event_info['create_date']));
        return $event_info;
    }

    function getOrganizationOfRequester() {
        return $this->db->getOne("SELECT user.organization_id FROM event, user WHERE event.event_id = ? AND event.requester_id = user.user_id", array($this->id));
    }

    function getEventStatus() {
        $dbstatus = $this->db->getOne("SELECT status FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($this->id));
        // if no value for status, it's open
        return $dbstatus ? $dbstatus : 'O';
    }

    function getEventHistory() {
        global $status_lu;

        $open_date = $this->db->getOne("SELECT create_date FROM event WHERE event_id = ?", array($this->id));

        $history[strtotime($open_date)] = "event opened";

        $q = $this->db->query("SELECT action_date,note,status FROM event_notes WHERE event_id = ? ORDER BY action_date", array($this->id));
        while($row = $q->fetchRow()) {
            $note = $row['note'] ? " (Note: ".$row['note'].")" : '';
            $history[strtotime($row['action_date'])] = "event " . $status_lu[$row['status']] . $note;
        }

        // get any followups sent
        $fuq = $this->db->query("SELECT send_date, fetp_id FROM event_fetp WHERE event_id = ? AND followup = 1", array($this->id));
        while($furow = $fuq->fetchRow()) {
            $ts = strtotime($furow['send_date']);
            $numfetps[$ts]++;
            $history[$ts] = "sent followup to ".$numfetps[$ts]." FETPs";
        }

        ksort($history);

        foreach($history as $ts => $desc) {
            $history_arr[] = date('n/j/Y H:i', $ts) . ": ".$desc;
        }

        return $history_arr;
    }

    function getInitiatorEmail() {
        $user_info = $this->db->getRow("SELECT user.email, user.hmu_id FROM user, event WHERE event_id = ? AND event.requester_id = user.user_id", array($this->id));
        if($user_info['email']) {
            return $user_info['email'];
        } else { // get it from the healthmap db
             $hmdb = getDB('hm');
             return $hmdb->getOne("SELECT email FROM hmu WHERE hmu_id = ?", array($user_info['hmu_id']));
        }
    }

    function changeStatus($status, $requester_id, $notes, $reason) {
        $initiator_id = $this->db->getOne("SELECT requester_id FROM event WHERE event_id = ?", array($this->id));
        if($requester_id == $initiator_id) {
            $notes = strip_tags($notes);
            $this->db->query("INSERT INTO event_notes (event_id, action_date, note, reason, status) VALUES (?,?,?,?,?)", array($this->id, date('Y-m-d H:i:s'), $notes, $reason, $status));
            $this->db->commit();
            return 1;
        }
        return 0;
    }

    function insertResponse($data_arr) {

        $responder_id = is_numeric($data_arr['responder_id']) ? $data_arr['responder_id'] : 0;
        $response = strip_tags($data_arr['response']);
        $perm = is_numeric($data_arr['response_permission']) && $data_arr['response_permission'] < 5 ? $data_arr['response_permission'] : 0;
        $this->db->query("INSERT INTO response (event_id, response, responder_id, response_date, response_permission) VALUES (?, ?, ?, ?, ?)", array($this->id, $response, $responder_id, date('Y-m-d H:i:s'), $perm));
        $response_id = $this->db->getOne("SELECT LAST_INSERT_ID()");
        $this->db->commit();
        return $response_id;
    }

    function getResponses() {
        global $response_permission_lu;
        $respvals = $this->db->getRow("SELECT requester_id, title, place.name AS location FROM event, place WHERE event_id = ? AND event.place_id = place.place_id", array($this->id));
        $respvals['org_requester_id'] = self::getOrganizationOfRequester();
        $respvals['responses'] = array();
        $respq = $this->db->query("SELECT * FROM response WHERE event_id = ?", array($this->id));
        while($resprow = $respq->fetchRow()) {
            $resprow['response_date'] = date('n/j/Y H:i', strtotime($resprow['response_date']));
            $resprow['response'] = substr($resprow['response'], 0, 100) . "...";
            $resprow['anonymous'] = $resprow['responder_id'] > 0 ? 'N' : 'Y';
            $resprow['showlink'] = $resprow['responder_id'] > 0 ? true : false;
            // if no perm level is set, FETP indicated he/she had no reply
            if($resprow['response_permission'] == 0) {
                $resprow['response'] = $response_permission_lu[0];
            }
            $respvals['responses'][] = $resprow;
        }
        return $respvals;
    }

    function getFETPRecipients() {
        $q = $this->db->query("SELECT DISTINCT(fetp_id) FROM event_fetp WHERE event_id = ?", array($this->id));
        while($row = $q->fetchRow()) {
            $fetp_ids[] = $row['fetp_id'];
        }
        return $fetp_ids;
    }

    function insertFetpsReceivingEmail($fetp_arr, $followup)
    {
        $followup = $followup ? $followup : '0';
        $send_date = date('Y-m-d H:i:s');
        foreach($fetp_arr as $fetp_id) {
            if(is_numeric($fetp_id)) {
                $this->db->query("INSERT INTO event_fetp (event_id, fetp_id, send_date, followup) VALUES (?, ?, ?, ?)", array($this->id, $fetp_id, $send_date, $followup));
                // generate a random token for link to response (will allow for auto-login)
                $token = md5(uniqid(rand(), true));
                $this->db->query("INSERT INTO ticket (fetp_id, val, exp) VALUES (?, ?, ?)", array($fetp_id, $token, date('Y-m-d H:i:s', strtotime("+10 days"))));
                $this->db->commit();
                // build an array of FETP_id => token_id to return
                $tokens[$fetp_id] = $token;
            }
        }
        return $tokens;
    }

    function updateEvent($data_arr)
    {
        // sanitize the input
        foreach($data_arr as $key => $val) {
            $darr[$key] = strip_tags($val);
        }
        if(!is_numeric($darr['requester_id'])) {
            return 0;
            exit;
        }
        // insert into the place table if doesn't exist
        $place_id = PlaceInfo::insertLocation($darr['latlon'], $darr['location']);
        // update the event table
        $q = $this->db->query("UPDATE event SET place_id = ?, title = ?, description = ? WHERE event_id = ?", array($place_id, $darr['title'], $darr['description'], $this->id));
        $this->db->commit();
        return $this->id;
    }

    function buildEmailForEvent($event_info = array(), $type, $custom_text, $with_link = '') 
    {
        $event_info = $event_info ? $event_info : self::getInfo();
        if($type == "followup") {
            $emailtext = EMAIL_TEXT_FOLLOWUP;
        } elseif($type == "followup_specific") {
            $emailtext = EMAIL_TEXT_FOLLOWUP_SPECIFIC;
        } elseif($type == "O" || $type == "reopen") { // Re-open
            $emailtext = EMAIL_TEXT_REOPENED;
        } elseif($type == "C" || $type == "close") { // Closed 
            $emailtext = EMAIL_TEXT_CLOSED;
        } elseif($type == "reply") {
            $emailtext = EMAIL_TEXT_RESPONSE;
        } elseif($type == "get_reply") {
            $emailtext = EMAIL_TEXT_SHOW_RESPONSE;
        } else {
            $emailtext = EMAIL_TEXT_RFI;
        }
        // don't show the response link in preview modes in forms, only on actual email
        if($with_link) {
            $emailtext .= "\n\n".RESPONSE_LINK;
        }
        $personalized_text = $event_info['personalized_text']  ? $event_info['personalized_text'] . "\n\n" : '';
        $emailtext = str_replace("[PERSONALIZED_TEXT]", $personalized_text, $emailtext);
        $emailtext = str_replace("[TITLE]", $event_info['title'], $emailtext);
        $emailtext = str_replace("[EVENT_DATE]", $event_info['create_date'], $emailtext);
        $emailtext = str_replace("[DESCRIPTION]", $event_info['description'], $emailtext);
        $emailtext = str_replace("[LOCATION]", $event_info['location'], $emailtext);
        $custom_text = $custom_text ? "\n\n".$custom_text."\n\n" : '';
        $emailtext = str_replace("[CUSTOM_TEXT]", $custom_text, $emailtext);
        if(isset($this)) {
            $emailtext = str_replace("[EVENT_ID]", $this->id, $emailtext);
        }
        return $emailtext;
    }

    static function getResponse($response_id) {
        global $response_permission_lu;
        $db = getDB();
        $response_info = $db->getRow("SELECT response_id, responder_id, response, response_date, event.event_id, event.title, event.description, event.create_date, event.requester_id AS event_requester_id, response_permission, place.name AS location FROM event, response, place WHERE response_id = ? AND response.event_id = event.event_id AND event.place_id = place.place_id", array($response_id));
        if(empty($response_info)) {
            return 0;
        }
        $response_info['response_date'] = date('n/j/Y H:i', strtotime($response_info['response_date'])); 
        $response_info['event_date'] = date('n/j/Y H:i', strtotime($response_info['create_date'])); 
        // response perm of 0 means the FETP responded that he/she had no response
        if($response_info['response_permission'] == 0) {
            $response_info['response_permission'] = '';
            $response_info['response'] = $response_permission_lu[0];
        } else {
            $response_info['response_permission'] = 'Permission level: ' . $response_permission_lu[$response_info['response_permission']];
        }
        return $response_info;
    }

    static function getAllEvents($uid = '', $status = '')
    {
        if(!is_numeric($uid)) {
            return 0;
        }
        $db = getDB();
        $oid = $db->getOne("SELECT organization_id FROM user WHERE user_id = ?", array($uid));
        $status = $status ? $status : 'O'; // if status is not passed in, get open events
        // join on the event_fetp table b/c if there is no row in there, the request was never sent (may have been started, but didn't get sent
        $q = $db->query("SELECT DISTINCT(event.event_id), event.*, place.name AS location FROM place, event, event_fetp WHERE event.place_id = place.place_id AND event.event_id = event_fetp.event_id ORDER BY event.create_date DESC");
        while($row = $q->fetchRow()) {
            // get the current status - open or closed
            $dbstatus = $db->getOne("SELECT status FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($row['event_id']));
            $dbstatus = $dbstatus ? $dbstatus : 'O'; // if no value for status, it's open
            if($status != $dbstatus) {
                continue;
            }
            // get the number of requests sent for that event
            $row['num_responses'] = $db->getOne("SELECT count(*) FROM response WHERE event_id = ?", array($row['event_id']));
            $row['num_requests'] = $db->getOne("SELECT count(*) FROM event_fetp WHERE event_id = ?", array($row['event_id']));
            $row['create_date'] = date('n/j/Y H:i', strtotime($row['create_date']));
            if($uid == $row['requester_id']) {
                $events['yours'][] = $row;
            } else {
                // get the organization of the user and that of the initiator of the request
                $oid_of_requester = $db->getOne("SELECT organization_id FROM user WHERE user_id = ?", array($row['requester_id']));
                if($oid && $oid == $oid_of_requester) {
                    $events['yourorg'][] = $row;
                } else {
                    $events['other'][] = $row;
                }
            }
        }
        return $events;
    }

    static function insertEvent($data_arr) 
    {
        $db = getDB();
        // sanitize the input
        foreach($data_arr as $key => $val) {
            $darr[$key] = strip_tags($val);
        }
        if(!is_numeric($darr['requester_id'])) {
            return 0;
            exit;
        }
        // insert into the place table if doesn't exist
        $place_id = PlaceInfo::insertLocation($darr['latlon'], $darr['location']);

        $create_date = $darr['create_date'] ? $darr['create_date'] : date('Y-m-d H:i:s');

        // insert into the event table
        $q = $db->query("INSERT INTO event (place_id, title, description, personalized_text, create_date, requester_id, search_box, search_countries) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", array($place_id, $darr['title'], $darr['description'], $darr['personalized_text'], $create_date, $darr['requester_id'], $darr['search_box'], $darr['search_countries']));
        $event_id = $db->getOne("SELECT LAST_INSERT_ID()");
        $db->commit();
        return $event_id;
    }

}
?>
