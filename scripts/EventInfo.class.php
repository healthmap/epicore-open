<?php
/**
 * EventInfo.php
 * Sue Aman 5 Jan 2010
 * info about an individual alert
 */

require_once 'db.function.php';
require_once 'const.inc.php';
require_once 'PlaceInfo.class.php';
require_once 'UserInfo.class.php';

class EventInfo
{
    function __construct($id)
    {
        $this->id = $id;
        $this->db =& getDB();
    }

    function getInfo() {

        $event_person = $this->getEventPerson($this->id);

        $event_info = $this->db->getRow("SELECT event.*, place.name AS location, place.location_details, concat(place.lat,',',place.lon) AS latlon FROM event, place WHERE event_id = ? AND event.place_id = place.place_id", array($this->id));
        $event_info['org_requester_id'] = self::getOrganizationOfRequester();
        $event_info['html_description'] = str_replace("\n", "<br>", $event_info['description']);
        $event_info['num_responses'] = $this->db->getOne("SELECT count(*) FROM response WHERE event_id = ?", array($this->id));
        $event_info['create_date'] = date('j-M-Y H:i', strtotime($event_info['create_date']));
        $event_info['event_date'] = date('j-M-Y', strtotime($event_info['event_date']));
        $event_info['person'] = $event_person['name'];

        $population = $this->getPopulation();
        $event_info['population'] = $population['population'];
        $event_info['population_details'] = $population['details'];
        $event_info['population_type'] = $population['type'];
        $condition = $this->getConditions($population['type']);
        $event_info['condition'] = $condition['condition'];
        $event_info['condition_details'] = $condition['details'];
        $event_info['hc_details'] = $condition['hc_details'];
        $source = $this->getSource();
        $event_info['source'] = $source['source'];
        $event_info['source_details'] = $source['details'];
        $event_info['purpose'] = $this->getPurpose();

        // outcome
        $event_info['outcome'] = $this->db->getOne("SELECT outcome FROM purpose WHERE event_id = ?", array($this->id));

        // phe description
        $event_info['phe_description'] = $this->db->getOne("SELECT phe_description FROM purpose WHERE event_id = ?", array($this->id));

        // phe additional info
        $event_info['phe_additional'] = $this->db->getOne("SELECT phe_additional FROM purpose WHERE event_id = ?", array($this->id));

        return $event_info;
    }

    // returns duplicate event ids after a given date, with matching country and health conditions
    // only checks duplicates for Human and Animal population types
    // returns false if no duplicates found
    static function checkDuplicate($date, $country, $population_type, $health_condition){

        if ($population_type == 'H' || $population_type == 'A') {
            $db = getDB();
            $check_conditions = unserialize(CHECK_CONDITIONS);

            // SQL for matching place ids
            // remove white space
            $country = preg_replace('/\s+/', '', $country);
            $match_country = "SELECT place_id FROM place WHERE name LIKE '%$country%' ";

            // SQL for event ids from matching population
            $match_population = "SELECT event_id FROM population WHERE type = '$population_type'";

            // SQL for health conditions
            $conditions = array();
            foreach ($check_conditions as $condition) {
                if ($health_condition[$condition]){
                    $conditions[] = '( ' .$condition . "= '1' )";
                } else {
                    $conditions[] = '( (' .$condition . " IS NULL) OR ( ". $condition . "= '0') )";
                }
            }
            $hc = implode(" AND ", $conditions);

            // get duplicate event ids
            $event_id = $db->getAll("SELECT e.event_id FROM event e, health_condition hc WHERE e.event_id = hc.event_id AND create_date >= ? 
                                  AND place_id IN ($match_country) AND e.event_id IN ($match_population) AND $hc", array($date));

            // only save event ids and status
            $events = array();
            foreach ($event_id as $eid) {
                $estatus = $db->getOne("SELECT status FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($eid['event_id']));
                // if no value for status, it's open
                $event_status = $estatus ? $estatus : 'O';
                $events[] = array('event_id' => $eid['event_id'], 'status' => $event_status);

            }

            if ($events)
                return $events;
            else
                return false;

        } else {
            return false;
        }

    }

    // returns duplicate event ids after a given date, with matching country and population
    // returns false if no duplicates found
    static function checkDuplicate2($date, $country, $population_type){

        if ($population_type == 'H' || $population_type == 'A' || $population_type == 'U' || $population_type == 'O') {
            $db = getDB();

            // remove white space
            $country = preg_replace('/\s+/', '', $country);
            // get duplicate event ids
            $events = $db->getAll("SELECT event_id, title FROM event WHERE create_date >= ? 
                                  AND place_id IN (SELECT place_id FROM place WHERE name LIKE '%$country%') 
                                  AND event_id IN (SELECT event_id FROM population WHERE type = '$population_type') ", array($date));

            // only save event ids and status
            $duplicate_events = array();
            foreach ($events as $event) {
                $estatus = $db->getOne("SELECT status FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($event['event_id']));
                // if no value for status, it's open
                $event_status = $estatus ? $estatus : 'O';
                $duplicate_events[] = array('event_id' => $event['event_id'], 'title' => $event['title'], 'status' => $event_status);

            }

            if ($duplicate_events)
                return $duplicate_events;
            else
                return false;

        } else {
            return false;
        }

    }

    // Follow an event for a given event id and user id
    // Returns true if following or false if already following or insert error
    static function followEvent($event_id, $user_id){
        $db = getDB();

        // check if following event already
        $res = $db->getAll("SELECT * FROM event_follow WHERE event_id = ? AND user_id = ?", array($event_id, $user_id));
        if ($res){
            return array('status'=>false, 'message' => 'already following event.');
        } else {

            // follow event
            $follow_date = date('Y-m-d H:i:s');
            $active = true;
            $res = $db->query("INSERT INTO event_follow (event_id, user_id, follow_date, active) VALUES(?,?,?,?)", array($event_id, $user_id, $follow_date, $active));

            // check result is not an error
            if (PEAR::isError($res)) {
                //die($res->getMessage());
                return array('status' => false, 'message' => 'database insert error');
            } else {
                $db->commit();
                return array('status' => true, 'message' => 'following event');
            }
        }

    }

    // gets email address(s) of user(s) following an event
    // returns array of email addresses or false if no followers.
    static function getFollowers($event_id){
        $db = getDB();
        $users = $db->getAll("SELECT hmu_id,u.user_id from event_follow ef, user u  WHERE u.user_id = ef.user_id AND ef.event_id = ? ", array($event_id));

        if ($users) {

            // get email for user from hm database
            $hmdb = getDB('hm');

            $followers = array();
            foreach ($users as $user){
                $hmuid = $user['hmu_id'];
                $userid = $user['user_id'];
                $email = $hmdb->getOne("SELECT email FROM hmu WHERE hmu_id = '$hmuid'");
                if ($email) {
                    $followers[] = ['email' => $email, 'user_id' =>$userid, 'hmu_id' =>$hmuid];
                }

            }

            if ($followers){
                return $followers;
            } else {
                return false;
            }

        } else {
            return false;
        }
    }

    function getConditions($type){

        $q = $this->db->getRow("SELECT * from health_condition WHERE event_id = ?", array($this->id));
        if ($q) {

            $condition = array();
            if ($type == 'H'){

                if ($q['respiratory'])
                    $condition[] = "Acute Respiratory";
                if ($q['gastrointestinal'])
                    $condition[] = "Gastrointestinal";
                if ($q['fever_rash'])
                    $condition[] = "Fever & Rash";
                if ($q['jaundice'])
                    $condition[] = "Acute Jaundice";
                if ($q['h_fever'])
                    $condition[] = "Hemorrhagic Fever";
                if ($q['paralysis'])
                    $condition[] = "Acute Flaccid paralysis";
                if ($q['other_neurological'])
                    $condition[] = "Other neurological";
                if ($q['fever_unknown'])
                    $condition[] = "Fever of unknown origin";
                if ($q['renal'])
                    $condition[] = "Renal failure";
                if ($q['unknown'])
                    $condition[] = "Unknown";
                if ($q['other'])
                    $condition[] = $q['other_description'];

            } else if ($type == 'A'){

                if ($q['respiratory_animal']) {
                    $condition[] = "Respiratory";
                }
                if ($q['neurological_animal']) {
                    $condition[] = "Neurological";
                }
                if ($q['hemorrhagic_animal']) {
                    $condition[] = "Haemorrhagic";
                }
                if ($q['vesicular_animal']) {
                    $condition[] = "Vesicular";
                }
                if ($q['reproductive_animal']) {
                    $condition[] = "Reproductive";
                }
                if ($q['gastrointestinal_animal']) {
                    $condition[] = "Gastrointestinal";
                }
                if ($q['multisystemic_animal']) {
                    $condition[] = "Multisystemic";
                }
                if ($q['unknown_animal']) {
                    $condition[] = "Unknown";
                }
                if ($q['other_animal']) {
                    $condition[] = $q['other_animal_description'];
                }

            } else {
                $condition[] = $q['disease_details'];
            }
            return array('condition' => implode(",", $condition), 'details' => $q['ph_details'], 'hc_details' => $q['disease_details']);

        }else {
            return false;
        }
    }

    function getPopulation(){

        $q = $this->db->getRow("SELECT * from population WHERE event_id = ?", array($this->id));
        if ($q) {

            $population = '';
            switch ($q['type']) {
                case "H":
                    $population = 'Human';
                    break;
                case "A":
                    $population = $this->getAnimal($q['animal_type'], $q['other_animal']);
                    break;
                case "E":
                    $population = 'Environmental';
                    break;
                case "U":
                    $population = 'Unknown';
                    break;
                case "O":
                    $population = $q['other'];
                    break;
            }
            return array('population'=>$population, 'details'=>$q['description'], 'type'=>$q['type']);

        }else {
            return false;
        }
    }

    function getAnimal($type, $other_animal) {

        $animal = '';
        switch ($type) {
            case "B":
                $animal = "Birds/Poultry";
                break;
            case "P":
                $animal = "Pigs/Swine";
                break;
            case "C":
                $animal = "Cattle";
                break;
            case "G":
                $animal = "Goats/Sheep";
                break;
            case "D":
                $animal = "Dogs/Cats";
                break;
            case "H":
                $animal = "Horses/Equines";
                break;
            case "O":
                $animal = $other_animal;
                break;
            default:
                break;

        }
        return $animal;
    }

    function getPurpose(){

        $q = $this->db->getRow("SELECT * from purpose WHERE event_id = ?", array($this->id));
        if ($q) {

            $action = $q['purpose'] == "V" ? "Verification" : "Update";
            $type = array();
            $type[] = $action;
            if ($q['causal_agent'])
                $type[] = "PHE Causal Agent";
            if ($q['epidemiology'])
                $type[] = "PHE Epidemiology";
            if ($q['pop_affected'])
                $type[] = "PHE population affected";
            if ($q['location'])
                $type[] = "PHE Location";
            if ($q['size'])
                $type[] = "PHE Size";
            if ($q['test'])
                $type[] = "PHE Test Results";
            if ($q['other_category'])
                $type[] = $q['other'];

            //return $action . ': ' . implode(",", $type);
            return $type;

        }else {
            return false;
        }
    }

    function getSource(){

        $q = $this->db->getRow("SELECT * from source WHERE event_id = ?", array($this->id));

        if ($q) {
            if ($q['source'] == "MR")
                $source = "Media Report";
            else if ($q['source'] == "OR")
                $source = "Official Report";
            else if ($q['source'] == "OC")
                $source = "Other Communication";
            else
                $source = "none";

            return array("source" => $source, "details" => $q['details']);
        }else {
            return false;
        }

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
        // get all event messages
        $messages =  $this->getFetpMessages(null, $this->id);

        // get original event request
        $ei = new EventInfo($this->id);
        $event_info = $ei->getInfo();

        // add event request to messages
        $size = count($messages);
        $messages[$size]['date'] = date('j-M-Y H:i', strtotime($event_info['create_date']));
        $messages[$size]['text'] = nl2br($event_info['description']);
        $messages[$size]['personalized_text'] = $event_info['personalized_text'];
        $messages[$size]['type'] = 'Event Request';
        $messages[$size]['title'] = $event_info['title'];
        $messages[$size]['location'] = $event_info['location'];
        $messages[$size]['location_details'] = $event_info['location_details'];
        $messages[$size]['person'] = $event_info['person'];
        $messages[$size]['organization_id'] = $event_info['org_requester_id'];
        $messages[$size]['disease'] = $event_info['disease'];
        $messages[$size]['event_date'] = date('j-M-Y', strtotime($event_info['event_date']));
        $messages[$size]['event_date_details'] = $event_info['event_date_details'];
        $messages[$size]['population'] = $event_info['population'];
        $messages[$size]['population_details'] = $event_info['population_details'];
        $messages[$size]['condition'] = $event_info['condition'];
        $messages[$size]['condition_details'] = $event_info['condition_details'];
        $messages[$size]['hc_details'] = $event_info['hc_details'];
        $messages[$size]['source'] = $event_info['source'];
        $messages[$size]['source_details'] = $event_info['source_details'];
        $messages[$size]['purpose'] = $event_info['purpose'];

        return $messages;
    }

    function getInitiatorEmail() {
        $user_info = $this->db->getRow("SELECT user.email, user.hmu_id, user.user_id FROM user, event WHERE event_id = ? AND event.requester_id = user.user_id", array($this->id));
        $initiator['user_id'] = $user_info['user_id'];
        if($user_info['email']) {
            $initiator['email'] = $user_info['email'];
        } else { // get it from the healthmap db
             $hmdb = getDB('hm');
             $initiator['email'] = $hmdb->getOne("SELECT email FROM hmu WHERE hmu_id = ?", array($user_info['hmu_id']));
        }
        return $initiator;
    }

    function getFollowupEmail() {
        //get followup moderators for the event
        $moderators = $this->db->getAll("select distinct hmu_id, user_id, email, organization_id from followup, user
                                          where requester_id=user_id and event_id = ?", array($this->id));

        $i=0;
        foreach ($moderators as $moderator) {
            $to[$i]['user_id'] = $moderator['user_id'];
            if ($moderator['email']){
                $to[$i]['email']= $moderator['email'];
                $to[$i]['name']= 'not from healtmap';
                $to[$i++]['organization_id']= $moderator['organization_id'];
            }
            else{// get it from the healthmap db
                $hmdb = getDB('hm');
                $to[$i]['email'] = $hmdb->getOne("SELECT email FROM hmu WHERE hmu_id = ?", array($moderator['hmu_id']));
                $to[$i]['name'] = $hmdb->getOne("SELECT name FROM hmu WHERE hmu_id = ?", array($moderator['hmu_id']));
                $to[$i++]['organization_id'] = 1;
            }
        }

        return $to;

    }

    function changeStatus($status, $requester_id, $notes, $reason, $superuser = false) {
        $initiator_oid = $this->db->getOne("SELECT user.organization_id FROM event, user WHERE event_id = ? AND event.requester_id = user.user_id", array($this->id));
        $requester_oid = $this->db->getOne("SELECT organization_id FROM user WHERE user_id = ?", array($requester_id));
        if(($requester_oid == $initiator_oid) || $superuser) {
            $notes = strip_tags($notes);
            $this->db->query("INSERT INTO event_notes (event_id, action_date, note, reason, status, requester_id) VALUES (?,?,?,?,?,?)",
                            array($this->id, date('Y-m-d H:i:s'), $notes, $reason, $status, $requester_id));
            $this->db->commit();

            return 1;
        }
        return 0;
    }

    function saveResponseFileNames($rid, $filename){

        $this->db->query("INSERT INTO responsefile(response_id, filename) VALUES (?,?)", array($rid,$filename));
        $fid = $this->db->getOne("SELECT LAST_INSERT_ID()");
        $this->db->commit();
        return $fid;

    }

    function getResponseFileNames($rid){
        return $this->db->getAll("SELECT filename FROM responsefile WHERE response_id = ?", array($rid));
    }

    function setResponseStatus($rid, $status) {
        $res = $this->db->query("UPDATE response SET useful='$status' WHERE response_id in ($rid)");

        // check that result is not an error
        if (PEAR::isError($res)) {
            //die($res->getMessage());
            return false;
        }
        else {
            $this->db->commit();
            return true;
        }
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

    function insertResponse2($data_arr) {

        $source = $data_arr['source'];
        $direct_observation = $source->direct_observation ? $source->direct_observation: 0;
        $indirect_report = $source->indirect_report ? $source->indirect_report: 0;
        $media_report = $source->media_report ? $source->media_report: 0;
        $official_report = $source->official_report ? $source->official_report: 0;
        $professional_opinion = $source->professional_opinion ? $source->professional_opinion: 0;
        $other_source = $source->other_source ? $source->other_source: 0;
        $other_source_description = $source->other_source_description ? $source->other_source_description: '';

        $responder_id = is_numeric($data_arr['responder_id']) ? $data_arr['responder_id'] : 0;
        $response = strip_tags($data_arr['response']);
        $perm = is_numeric($data_arr['response_permission']) && $data_arr['response_permission'] < 5 ? $data_arr['response_permission'] : 0;
        $this->db->query("INSERT INTO response (event_id, response, responder_id, response_date, response_permission, direct_observation, indirect_report, media_report, official_report, professional_opinion, other_source, other_source_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            array($this->id, $response, $responder_id, date('Y-m-d H:i:s'), $perm, $direct_observation, $indirect_report, $media_report, $official_report, $professional_opinion, $other_source, $other_source_description));
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

    static function getAllResponses() {
        $db = getDB();
        return $db->getAll("SELECT * FROM response ORDER BY event_id");
    }

    static function getAllFollowups() {
        $db = getDB();
        return $db->getAll("SELECT * FROM followup ORDER BY event_id");
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
        $followup_id = ($followup >= 0) ? $followup : $this->getFollowupId();
        $send_date = date('Y-m-d H:i:s');
        foreach($fetp_arr as $fetp_id) {
            if(is_numeric($fetp_id)) {
                $this->db->query("INSERT INTO event_fetp (event_id, fetp_id, send_date, followup_id) VALUES (?, ?, ?, ?)", array($this->id, $fetp_id, $send_date, $followup_id));
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


    static function updateEvent($data_arr)
    {
        // sanitize the input
        foreach($data_arr as $key => $val) {
            $darr[$key] = strip_tags($val);
        }
        if(!is_numeric($darr['requester_id']) || !is_numeric($darr['event_id'])) {
            return 'invalid requester id or invalid event_id';
        }

        $db = getDB();
        $eid = $db->getOne("SELECT event_id FROM event WHERE event_id = ? ", array($darr['event_id']));
        if ($eid) {

            // update location
            $pid = $db->getOne("SELECT place_id FROM event WHERE event_id = ? ", array($eid));
            $place_id = PlaceInfo::updateLocation($pid, $darr['latlon'], $darr['location']);
            if ($place_id != $pid)
                return $place_id;

            // update the event table
            $q = $db->query("UPDATE event SET title = ?, description = ?, personalized_text = ?, disease = ? WHERE event_id = ?",
                array($darr['title'], $darr['description'], $darr['personalized_text'], $darr['disease'], $darr['event_id']));

            // check that result is not an error
            if (PEAR::isError($q)) {
                //die($res->getMessage());
                return 'failed update event query';
            } else {
                $db->commit();
            }
            return $eid;
        } else {
            return 'event does not exist for event id '. $eid;
        }

    }

    static function updateEventTitle($data_arr)
    {
        // sanitize the input
        foreach($data_arr as $key => $val) {
            $darr[$key] = strip_tags($val);
        }
        if(!is_numeric($darr['event_id'])) {
            return 'invalid event_id';
        }

        $db = getDB();
        $eid = $db->getOne("SELECT event_id FROM event WHERE event_id = ? ", array($darr['event_id']));
        if ($eid) {

            // update the event table
            $q = $db->query("UPDATE event SET title = ? WHERE event_id = ?",
                array($darr['title'], $darr['event_id']));

            // check that result is not an error
            if (PEAR::isError($q)) {
                //die($res->getMessage());
                return 'failed update event query';
            } else {
                $db->commit();
            }
            return 1;
        } else {
            return 'event does not exist for event id '. $eid;
        }
    }

    // update event and related tables, returns event id if updated, or error message if not
    static function updateEvent2($event_info, $event_table)
    {
        // sanitize the input
        foreach($event_info as $key => $val) {
            $darr[$key] = strip_tags($val);
        }
        if(!is_numeric($darr['requester_id']) || !is_numeric($darr['event_id'])) {
            return 'invalid requester id or invalid event_id';
        }

        $db = getDB();
        $eid = $db->getOne("SELECT event_id FROM event WHERE event_id = ? ", array($darr['event_id']));
        if ($eid) {

            // update location
            $pid = $db->getOne("SELECT place_id FROM event WHERE event_id = ? ", array($eid));
            $place_id = PlaceInfo::updateLocation2($pid, $darr['latlon'], $darr['location'], $darr['location_details']);
            if ($place_id != $pid)
                return $place_id;  // error message if error

            // update the event table
            $q = $db->query("UPDATE event SET title = ?, event_date = ?, event_date_details = ? WHERE event_id = ?",
                array($darr['title'],$darr['event_date'],$darr['event_date_details'], $darr['event_id']));

            // check that result is not an error
            if (PEAR::isError($q)) {
                //die($res->getMessage());
                return 'failed update event query';
            } else {
                $db->commit();
            }

            // update related event tables
            $status = 'failed event table';
            foreach ($event_table as $table_name => $table) {
                $table_id = EventInfo::replaceEventTable($table_name, $table);
                if (is_numeric($table_id)) {
                    $status = $eid;     // success
                } else {
                    $status = 'Failed within updateEvent2 function - failed to insert event table: ' . $table_name . ', error message: ' .$table_id;
                    break;
                }
            }

            return $status;

        } else {
            return 'event id: '. $eid . ' does not exist!';
        }

    }

    function buildEmailForEvent($event_info = array(), $type, $custom_vars = array(), $return_type = 'text')
    {
        global $response_permission_lu;
        global $permission_img;

        // if event id is passed in, just pull the email text from the RFI preview, if there is one
        if($return_type == "file" && isset($this) && file_exists("../".EMAILPREVIEWS."$type/".$this->id.".html")) {
            $file_preview = EMAILPREVIEWS . "$type/".$this->id.".html";
            return $file_preview;
        }
    
        $event_info = $event_info ? $event_info : self::getInfo();
        $file_type = $return_type  == "file" ? $type."_file" : $type;
        $emailtext = file_get_contents("../emailtemplates/$file_type.html");

        // for the description and personalized text, replace newlines with <p> for formatting        
        $personalized_text = $description = '';
        // first protect <url> from being stripped - this is a ProMED convention for urls
        $desc = preg_replace('/\<http(.*?)\>/smi', '&lt;http${1}>', $event_info['description']);
        // then strip all other html tags 
        $desc = strip_tags($desc);
        $desc = preg_replace('/&lt;http(.*?)>/smi', '<a href="http${1}">http${1}</a>', $desc);
        foreach (explode("\n", $desc) as $dline) {
            if (trim($dline)) {
                $description .= '<p style="margin:12px 0;">' . $dline . '</p>';
            }
        }
        if($event_info['personalized_text']) {
            foreach (explode("\n", $event_info['personalized_text']) as $ptline) {
                if (trim($ptline)) {
                    $personalized_text .= '<p style="margin:12px 0;">' . $ptline . '</p>';
                }
            }
        }

        // standard event substitutions
        $emailtext = str_replace("[PERSONALIZED_TEXT]", $personalized_text, $emailtext);
        $emailtext = str_replace("[TITLE]", $event_info['title'], $emailtext);
        $emailtext = str_replace("[EVENT_DATE]", date('j-M-Y H:i', strtotime($event_info['create_date'])), $emailtext);
        $emailtext = str_replace("[DESCRIPTION]", $description, $emailtext);
        $emailtext = str_replace("[LOCATION]", $event_info['location'], $emailtext);
        $emailtext = str_replace("[EPICORE_URL]", EPICORE_URL, $emailtext);

        // custom var substitutions 
        foreach($custom_vars as $varname => $varval) {
            if (($varname == 'RESPONSE_TEXT') || ($varname == 'NOTES'))
                $varval = nl2br($varval); //"<pre>$varval</pre>";
            if ($varname == 'RESPONSE_PERMISSION'){ // add traffic light to permissions
                if($varval == $response_permission_lu[1]){
                    $varval = $permission_img[1] . $varval;
                }
                else if($varval == $response_permission_lu[2]){
                    $varval = $permission_img[2] . $varval;
                }
                if($varval == $response_permission_lu[3]){
                    $varval = $permission_img[3] . $varval;
                }
            }
            $emailtext = str_replace("[$varname]", $varval, $emailtext);
        }

        if(isset($this)) {
            $emailtext = str_replace("[EVENT_ID]", $this->id, $emailtext);
        }

        if($return_type == "text") {
            return $emailtext;
        } else {
            // save the email contents in the temp directory for reference- if one is passed in, overwrite it
            $filename = isset($this) ? $this->id : date('YmdHis');
            $file_preview = EMAILPREVIEWS . "$type/$filename" . ".html";
            file_put_contents("../$file_preview", $emailtext);
            return $file_preview;
        }
    }
    function nl2p($text){
        $description = '';
        foreach (explode("\n", $text) as $dline) {
            if (trim($dline)) {
                $description .= '<p>' . $dline . '</p>';
            }
        }
        return $description;
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
        $response_info['response_permission_id'] = $response_info['response_permission'];
        if($response_info['response_permission'] == 0) {
            $response_info['response_permission'] = '';
            $response_info['response'] = $response_permission_lu[0];
        } else {
            $response_info['response_permission'] = $response_permission_lu[$response_info['response_permission']];
        }
        return $response_info;
    }

    static function getResponsesforEvent($event_id){

        $db = getDB();
        $responses = $db->getAll("SELECT * FROM response WHERE event_id = ?", array($event_id));

        $response_info = array();
        foreach ($responses as $response){
            $response_info[] = $response['response'] . ', Action: ' . EventInfo::getResponseAction($response);
        }

        return $response_info;
    }

    static function getResponseAction($response){

        $response_action = array();
        if ($response['direct_observation'])
            $response_action[] = 'Direct Observation';
        if ($response['indirect_report'])
            $response_action[] = 'indirect_report';
        if ($response['media_report'])
            $response_action[] = 'media_report';
        if ($response['official_report'])
            $response_action[] = 'official_report';
        if ($response['professional_opinion'])
            $response_action[] = 'professional_opinion';
        if ($response['other_source'])
            $response_action[] = 'Other source: ' . $response['other_source_description'];

        return implode(',', $response_action);

    }

    static function getAllEvents($uid = '', $status = '', $sdate = '', $edate = '')
    {
        if(!is_numeric($uid)) {
            return 0;
        }
        $start_date = $sdate ? $sdate: V2START_DATE;
        $end_date = $edate ? $edate: date("Y-m-d H:i:s");
        $db = getDB();
        $oid = $db->getOne("SELECT organization_id FROM epicore.user WHERE user_id = ?", array($uid));
        $status = $status ? $status : 'O'; // if status is not passed in, get open events
        // join on the event_fetp table b/c if there is no row in there, the request was never sent (may have been started, but didn't get sent
        $q = $db->query("SELECT DISTINCT(event.event_id), event.*, place.name AS location, place.location_details FROM epicore.place, epicore.event, epicore.event_fetp 
                          WHERE event.place_id = place.place_id AND event.event_id = event_fetp.event_id AND event.create_date >= ? AND event.create_date <= ?
                          ORDER BY event.create_date DESC", array($start_date, $end_date));

        while($row = $q->fetchRow()) {
            // get the current status - open or closed
            $dbstatus = $db->getOne("SELECT status FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($row['event_id']));
            $dbstatus = $dbstatus ? $dbstatus : 'O'; // if no value for status, it's open
            if($status != $dbstatus) {
                continue;
            }

            // get fetp (member) ids
            $q1 = $db->query("SELECT DISTINCT(fetp_id) FROM event_fetp WHERE event_id = ?", array($row['event_id']));
            $fetp_ids = array();
            while($row1 = $q1->fetchRow()) {
                $fetp_ids[] = $row1['fetp_id'];
            }
            $row['member_ids'] = implode(',', $fetp_ids);
            $row['num_members'] = count($fetp_ids);

            // get date of first response
            $row['first_response_date'] = $db->getOne("SELECT MIN(response_date) FROM response WHERE event_id = ?", array($row['event_id']));
            // get notes
            $row['notes'] = $db->getOne("SELECT note FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($row['event_id']));
            // get reason
            $row['reason'] = $db->getOne("SELECT reason FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($row['event_id']));
            // get action date
            $row['action_date'] = $db->getOne("SELECT action_date FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($row['event_id']));
            $row['action_date'] = date('j-M-Y', strtotime($row['action_date']));

            // get organization id for the event
            $row['organization_id'] = $db->getOne("SELECT organization_id FROM user WHERE user.user_id = ?", array($row['requester_id']));

            // get organization name
            $row['organization_name'] = $db->getOne("SELECT name FROM organization WHERE organization_id = ?", array($row['organization_id']));

            // get outcome
            $row['outcome'] = $db->getOne("SELECT outcome FROM purpose WHERE event_id = ?", array($row['event_id']));

            // get phe description
            $row['phe_description'] = $db->getOne("SELECT phe_description FROM purpose WHERE event_id = ?", array($row['event_id']));

            // get phe additional info
            $row['phe_additional'] = $db->getOne("SELECT phe_additional FROM purpose WHERE event_id = ?", array($row['event_id']));

            // get source
            $row['source'] = $db->getOne("SELECT source FROM source WHERE event_id = ?", array($row['event_id']));
            // get source details
            $row['source_details'] = $db->getOne("SELECT details FROM source WHERE event_id = ?", array($row['event_id']));

            // get metrics
            $row['event_metrics_id'] = $db->getOne("SELECT event_metrics_id FROM event_metrics WHERE event_id = ?", array($row['event_id']));
            $metric_score = $db->getOne("SELECT score FROM event_metrics WHERE event_id = ?", array($row['event_id']));
            $row['metric_score'] = is_numeric($metric_score) ? (int)$metric_score : null;
            $row['metric_creation'] = $db->getOne("SELECT creation FROM event_metrics WHERE event_id = ?", array($row['event_id']));
            $row['metric_notes'] = $db->getOne("SELECT notes FROM event_metrics WHERE event_id = ?", array($row['event_id']));
            $row['metric_action'] = $db->getOne("SELECT action FROM event_metrics WHERE event_id = ?", array($row['event_id']));

            // get population, health conditions, source and purpose
            $ei = new EventInfo($row['event_id']);
            $event_info = $ei->getInfo();
            $row['affected_population'] = $event_info['population'];
            $row['affected_population_details'] = $event_info['population_details'];
            $row['human_health_condition'] = $event_info['population_type'] == 'H' ? $event_info['condition']: '';
            $row['animal_health_condition'] = $event_info['population_type'] == 'A' ? $event_info['condition']: '';
            $row['health_condition_details'] = $event_info['hc_details'];
            $row['other_relevant_ph_details'] = $event_info['condition_details'];
            $purpose = $event_info['purpose'];
            $row['verfication_or_update'] = $purpose[0];
            $row['rfi_purpose'] = implode(',',$purpose);
            $row['personalized_message'] = $event_info['personalized_text'];
            $row['rfi_source'] = $event_info['source'];
            $row['rfi_source_details'] = $event_info['source_details'];

            // get responses
            $row['event_responses'] = EventInfo::getResponsesforEvent($row['event_id']);

            // get the number of requests sent for that event
            $row['num_responses'] = $db->getOne("SELECT count(*) FROM response WHERE event_id = ?", array($row['event_id']));
            $row['num_responses_content'] = $db->getOne("SELECT count(*) FROM response WHERE event_id = ? AND response_permission > 0 AND  response_permission < 4", array($row['event_id']));
            $row['num_responses_active'] = $db->getOne("SELECT count(*) FROM response WHERE event_id = ? AND response_permission = '4' ", array($row['event_id']));
            $row['num_responses_nocontent'] = $row['num_responses'] - $row['num_responses_content'] - $row['num_responses_active'];
            $row['num_notrated_responses'] = $db->getOne("SELECT count(*) FROM response WHERE useful IS NULL AND response_permission <>0 AND response_permission <>4 and event_id = ?", array($row['event_id']));
            $row['num_notuseful_responses'] = $db->getOne("SELECT count(*) FROM response WHERE useful ='0' and event_id = ?", array($row['event_id']));
            $row['num_useful_responses'] = $db->getOne("SELECT count(*) FROM response WHERE useful ='1' and event_id = ?", array($row['event_id']));
            $row['num_useful_promed_responses'] = $db->getOne("SELECT count(*) FROM response WHERE useful ='2' and event_id = ?", array($row['event_id']));
            $row['notuseful_responses'] = $db->getAll("SELECT * FROM response WHERE useful ='0' and event_id = ?", array($row['event_id']));
            $row['useful_responses'] = $db->getAll("SELECT * FROM response WHERE useful ='1' and event_id = ?", array($row['event_id']));
            $row['useful_promed_responses'] = $db->getAll("SELECT * FROM response WHERE useful ='2' and event_id = ?", array($row['event_id']));
            $get_followups = $db->query("SELECT send_date,followup_id FROM event_fetp WHERE event_id = ? ORDER BY send_date DESC", array($row['event_id']));
            $ftext = $db->query("SELECT text,followup_id from followup WHERE event_id = ?", array($row['event_id']));
            while($gftext = $ftext->fetchRow()){
                $text[$gftext['followup_id']] = $gftext['text'];
            }

            unset($num_followups);
            while($gfrow = $get_followups->fetchRow()) {
                $send_date = date('j-M-Y H:i', strtotime($gfrow['send_date']));  // Day Month Year
                $num_followups[$gfrow['followup_id']][] = $send_date;
            }

            foreach($num_followups as $followupnum => $datearr) {
                $newdate = date_create_from_format('j-M-Y H:i', $datearr[0]);
                $newdate = date_format($newdate, 'Y-m-d');
                $row['num_followups'][] = array('date' => $datearr[0], 'num' => count($datearr), 'text' => $text[$followupnum], 'iso_date' => $newdate);
            }
            $row['iso_create_date'] = $row['create_date'];
            $row['event_id_int'] = (int)$row['event_id'];
            $row['create_date'] = date('j-M-Y', strtotime($row['create_date']));
            $row['event_date'] = date('j-M-Y', strtotime($row['event_date']));
            $first_response_date = new DateTime($row['first_response_date']);
            $event_create_date = new DateTime($row['iso_create_date']);
            $reaction_time =  $first_response_date->diff($event_create_date);
            //$row['reaction_time'] = $reaction_time->format('%a days, %H:%I'); // days, hh:mm
            $minutes =  $reaction_time->days*24*60 + $reaction_time->h*60 + $reaction_time->i;
            $row['reaction_time'] = $minutes; // total minutes

            //$row['title'] = iconv("UTF-8", "ISO-8859-1//IGNORE", $row['title']);

            $event_person = EventInfo::getEventPerson($row['event_id']);
            $row['person'] = $event_person['name'];
            $place = explode(',',$row['location']);
            if (sizeof($place) == 3){
                $row['country'] = $place[2];
            }
            elseif(sizeof($place) == 2){
                $row['country'] = $place[1];
            }
            elseif(sizeof($place) == 1){
                $row['country'] = $place[0];
            }


            if($uid == $row['requester_id']) {
                $events['yours'][] = $row;
                $events['yourorg_you'][] = $row;
                $events['all'][] = $row;
            } else {
                // get the organization of the user and that of the initiator of the request
                $oid_of_requester = $db->getOne("SELECT organization_id FROM user WHERE user_id = ?", array($row['requester_id']));
                if($oid && $oid == $oid_of_requester) {
                    $events['yourorg'][] = $row;
                    $events['yourorg_you'][] = $row;
                    $events['all'][] = $row;
                } else {
                    $events['other'][] = $row;
                    $events['all'][] = $row;
                }
            }
        }

        return $events;
    }

    // get events from cache or database
    // user_id ('#'), status ('C', 'O'), source ('cache', 'database'), start date = 'yyyy-mm-dd'
    static function getEventsCache($user_id, $status, $source, $start_date = '') {

        // generate cache file name
        $cachekey = md5('events'. $user_id . $status);
        $events_file = "../cache/epicore" . $cachekey. ".json";

        if (file_exists($events_file) && ($source == 'cache')) { // from cache
            $events = json_decode(file_get_contents($events_file));
        } else { // from database
            $events = EventInfo::getAllEvents($user_id, $status, $start_date);
            file_put_contents("$events_file", json_encode($events));
        }
        return $events;
    }

    static function getNumNotRatedResponses($uid = '', $sdate = '')
    {
        if(!is_numeric($uid)) {
            return 0;
        }

        $start_date = $sdate ? $sdate: '2000-01-01';
        $db = getDB();
        $q = $db->query("SELECT * FROM event where requester_id = ? AND create_date > ?", array($uid, $start_date));
        $num_notrated_responses = 0;
        $listofEventIds = [];
        
        while($row = $q->fetchRow()) {
            // get the current status - open or closed
            $status = $db->getOne("SELECT status FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($row['event_id']));
            $status = $status ? $status : 'O'; // if no value for status, it's open
            if ($status == 'C') {
                // $num_notrated_responses += $db->getOne("SELECT count(*) FROM response WHERE useful IS NULL AND response_permission <>0 AND response_permission <>4 AND event_id = ?", array($row['event_id']));
                $sample = $db->getOne("SELECT count(*) FROM response WHERE useful IS NULL AND response_permission <>0 AND response_permission <>4 AND event_id = ?", array($row['event_id']));
                $num_notrated_responses += $sample;
                if($sample !=0){
                    array_push($listofEventIds,array($row['event_id']));
                }
                
            }
        }
        return array($num_notrated_responses,$listofEventIds);
    }

    // Returns inactive open events for a Mod:
    // - events created before $date
    // - AND events with no responses or responses with nothing to contribute before $date
    // - AND events with no followups before $date.
    static function getInactiveEvents($uid = '', $date = '')
    {
        if(!is_numeric($uid)) {
            return 0;
        }
        $db = getDB();
        $q = $db->query("SELECT * FROM event where requester_id = ?", array($uid));
        $notactive_events = array();
        $responses = 0;
        while($row = $q->fetchRow()) {
            // get the current status - open or closed
            $status = $db->getOne("SELECT status FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($row['event_id']));
            $status = $status ? $status : 'O'; // if no value for status, it's open

            // save event id if open and not active
            if ($status == 'O') {
                // check for active responses and followups
                $active_response_ids = $db->getAll("SELECT response_id FROM response WHERE event_id = ? AND response_date >= ?", array($row['event_id'], $date));
                $response_ids = $db->getAll("SELECT response_id FROM response WHERE event_id = ?", array($row['event_id']));
                $active_follwup_ids = $db->getAll("SELECT followup_id FROM followup WHERE event_id = ? AND action_date >= ? ", array($row['event_id'], $date));
                $active_event_ids = $db->getAll("SELECT event_id FROM event WHERE event_id = ? AND create_date >= ? ", array($row['event_id'], $date));
                // save event id if not active
                if (!($active_event_ids || $active_response_ids || $active_follwup_ids)){
                    if ($response_ids){
                        $responses = count($response_ids);
                    }
                    $notactive_events[] = array('event_id' => $row['event_id'], 'title'=> $row['title'], 'date'=> $row['create_date'], 'responses' => $responses);
                }
            }
        }
        return $notactive_events;
    }

    // Returns inactive open events for a Responder:
    // - events created before $date
    // - AND events with no responses or nothing to contribute before $date
    static function getInactiveEvents2($uid = '', $date = '')
    {
        if(!is_numeric($uid)) {
            return 0;
        }
        $v2start_date = V2START_DATE;
        $db = getDB();
        $q = $db->query("SELECT * FROM event where requester_id = ? AND create_date > '$v2start_date'", array($uid));
        $notactive_events = array();
        while($row = $q->fetchRow()) {
            // get the current status - open or closed
            $status = $db->getOne("SELECT status FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($row['event_id']));
            $status = $status ? $status : 'O'; // if no value for status, it's open

            // save event id if open and not active
            if ($status == 'O') {
                // get response ids with content or with active searches created after date
                $content_response_ids = $db->getAll("SELECT response_id FROM response WHERE event_id = ? AND (response_permission >'0') AND (response_permission <= '4') 
                                        AND response_date >= ?", array($row['event_id'], $date));
                // check for active events created after $date
                $active_event_ids = $db->getAll("SELECT event_id FROM event WHERE event_id = ? AND create_date >= ? ", array($row['event_id'], $date));
                // save event if not active
                if (!($active_event_ids || $content_response_ids)){
                    // get response ids with content
                    $response_ids = $db->getAll("SELECT response_id FROM response WHERE (response_permission >'0') AND (response_permission < '4') AND event_id = ?", array($row['event_id']));
                    // get response ids with active search after date
                    $active_response_ids = $db->getAll("SELECT response_id FROM response WHERE (response_permission = '4') AND event_id = ?", array($row['event_id']));

                    $responses = $response_ids ? count($response_ids): 0;
                    $active_responses = $active_response_ids ? count($active_response_ids): 0;
                    $notactive_events[] = array('event_id' => $row['event_id'], 'title'=> $row['title'], 'date'=> $row['create_date'], 'responses' => $responses, 'active_search' => $active_responses);
                }
            }
        }
        return $notactive_events;
    }

    // Returns Mods with inactive events
    static function getModsWithInactiveEvents($active_date){
        $db = getDB();
        $all_mods = UserInfo::getMods();

        $inactive_mods = array();
        foreach($all_mods as $mod){
            $hmuid = $mod['hmu_id'];
            $uid = $db->getOne("SELECT user_id FROM user WHERE hmu_id = '$hmuid'");
            $events = self::getInactiveEvents($uid, $active_date);
            if ($events) {
                $inactive_mods[] = array('email'=> $mod['email'], 'name' => $mod['name'], 'user_id' => $uid, 'events' => $events);
            }
        }
        if ($inactive_mods)
            return $inactive_mods;
        else
            return false;

    }

    static function getModsWithInactiveEvents2($active_date){
        $db = getDB();
        $all_mods = UserInfo::getMods();

        $inactive_mods = array();
        foreach($all_mods as $mod){
            $hmuid = $mod['hmu_id'];
            $uid = $db->getOne("SELECT user_id FROM user WHERE hmu_id = '$hmuid'");
            $events = self::getInactiveEvents2($uid, $active_date);
            if ($events) {
                $inactive_mods[] = array('email'=> $mod['email'], 'name' => $mod['name'], 'user_id' => $uid, 'events' => $events);
            }
        }
        if ($inactive_mods)
            return $inactive_mods;
        else
            return false;
    }

    // get responders for active searches (no content in responses)
    static function getActiveSearchResponders($event_id){
        $db = getDB();
        // get the current status - open or closed
        $status = $db->getOne("SELECT status FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($event_id));
        $status = $status ? $status : 'O'; // if no value for status, it's open
        // if open
        if ($status == 'O') {
            // get active search responders
            $active_responders = $db->getAll("SELECT responder_id, email FROM response, fetp 
                                    WHERE event_id = ? AND response_permission = '4' AND responder_id=fetp_id",
                                    array($event_id));

            return $active_responders;
        } else
            return false;
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
        $q = $db->query("INSERT INTO event (place_id, title, description, personalized_text, disease, create_date, requester_id, search_box, search_countries, alert_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            array($place_id, $darr['title'], $darr['description'], $darr['personalized_text'], $darr['disease'], $create_date, $darr['requester_id'], $darr['search_box'], $darr['search_countries'], $darr['alert_id']));
        $event_id = $db->getOne("SELECT LAST_INSERT_ID()");
        $db->commit();
        return $event_id;
    }

    function getEvent2() {

        // get event
        $event = $this->db->getRow("SELECT event.*, place.name AS location, place.location_details, concat(place.lat,',',place.lon) AS latlon FROM event, place WHERE event_id = ? AND event.place_id = place.place_id", array($this->id));
        $event['event_date'] = date('j-M-Y', strtotime($event['event_date']));

        // get associated tables
        $population = $this->db->getRow("SELECT * FROM population WHERE event_id = ?", array($this->id));
        $condition = $this->db->getRow("SELECT * FROM health_condition WHERE event_id = ?", array($this->id));
        $purpose = $this->db->getRow("SELECT * FROM purpose WHERE event_id = ?", array($this->id));
        $source = $this->db->getRow("SELECT * FROM source WHERE event_id = ?", array($this->id));

        // MYSQL returns tinyint as string ("1", "0") so convert to int
        foreach ($condition as $key => $value){
            $condition[$key] = ($condition[$key] == '1') ?  1 : $condition[$key];
            $condition[$key] = ($condition[$key] == '0') ?  0 : $condition[$key];
        }
        foreach ($purpose as $key => $value){
            $purpose[$key] = ($purpose[$key] == '1') ?  1 : $purpose[$key];
            $purpose[$key] = ($purpose[$key] == '0') ?  0 : $purpose[$key];
        }

        if ($event && $population && $condition && $purpose && $source ) {
            return array('event' => $event, 'population' => $population, 'health_condition' => $condition, 'purpose' => $purpose, 'source' => $source);
        } else {
            return false;
        }
    }

    // insert event and related tables
    // returns and inserted event id if successful (status = 'success'), or status = error message
    static function insertEvent2($event_info, $event_table)
    {
        $db = getDB();
        // sanitize event info
        $darr = array();
        foreach($event_info as $key => $val) {
            $darr[$key] = strip_tags($val);
        }
        if(!is_numeric($darr['requester_id'])) { // check valid id
            return false;
            exit;
        }
        // insert into the place table if doesn't exist
        $place_id = PlaceInfo::insertLocation2($darr['latlon'], $darr['location'], $darr['location_details']);

        // insert into the event table
        $create_date = $darr['create_date'] ? $darr['create_date'] : date('Y-m-d H:i:s');
        $res = $db->query("INSERT INTO event (place_id, title, personalized_text, create_date, requester_id, search_box, search_countries, event_date, event_date_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array($place_id, $darr['title'], $darr['personalized_text'], $create_date, $darr['requester_id'], $darr['search_box'], $darr['search_countries'], $darr['event_date'], $darr['event_date_details']));

        // check result is not an error
        if (PEAR::isError($res)) {
            //die($res->getMessage());
            $status = 'failed to insert event';
            $event_id = false;
        } else {
            $event_id = $db->getOne("SELECT LAST_INSERT_ID()");
            $db->commit();
            $status = 'success';
        }

        // insert related event tables
        if ($event_id) {
            foreach ($event_table as $table_name => $table) {
                $table_id = EventInfo::insertEventTable($table_name, $table, $event_id);
                if (is_numeric($table_id)) {
                    $status = 'success';
                } else {
                    $status = 'Failed within insertEvent2 -- failed to insert event table: ' . $table_name . ', error message: ' .$table_id;
                    break;
                }
            }
        }
        return array('status'=>$status, 'event_id' =>$event_id);

    }

    // returns table id if inserted, or an error message if there is an insert error or the table already exists.
    static function insertEventTable($table_name,$table, $event_id)
    {
        // check valid table name
        $valid_table = ($table_name == 'population' || $table_name == 'health_condition' || $table_name == 'purpose' || $table_name == 'source');
        if (!$valid_table) {
            return 'invalid table name.';
        }
        // insert if table does not exist for the event
        $db = getDB();
        $q1 = "SELECT event_id FROM {$table_name} WHERE event_id = ?";
        $eid = $db->getOne($q1, array($event_id));
        if(!$eid) { // insert if valid table and does not exist
            $pvals = array();
            // sanitize table data
            foreach($table as $key => $val) {
                $pvals[$key] = strip_tags($val);
            }
            $pvals['event_id'] = strip_tags($event_id);

            // insert row
            $key_vals = join(",", array_keys($pvals));
            $qmarks = join(",", array_fill(0, count($pvals), '?'));
            $qvals = array_values($pvals);
            $q2 = "INSERT IGNORE INTO {$table_name} ({$key_vals}) VALUES ({$qmarks})";
            $res = $db->query($q2, $qvals);
            // check that result is not an error
            if (PEAR::isError($res)) {
                //die($res->getMessage());
                return 'database insert error.';
            } else {
                $table_id = $db->getOne("SELECT LAST_INSERT_ID()");
                $db->commit();
                return $table_id;
            }
        }
        else
            return 'table already exists.';
    }

    // returns table id if replaced, or an error message if there is an replace error.
    static function replaceEventTable($table_name,$table)
    {
        // check valid table name
        $valid_table = ($table_name == 'population' || $table_name == 'health_condition' || $table_name == 'purpose' || $table_name == 'source' || $table_name == 'event_metrics');
        if (!$valid_table) {
            return 'invalid table name.';
        }

        // sanitize table data
        $pvals = array();
        foreach($table as $key => $val) {
            $pvals[$key] = strip_tags($val);
        }
        
        // replace row
        $db = getDB();
        $key_vals = join(",", array_keys($pvals));
        $qmarks = join(",", array_fill(0, count($pvals), '?'));
        $qvals = array_values($pvals);
        if($table_name == 'event_metrics'){
            if($pvals['event_metrics_id'] == 0){
                // INSERT -- Old school
                $res = $db->query("INSERT INTO event_metrics (event_metrics_id, event_id, score, creation, notes, action) VALUES (?, ?, ?, ?, ?, ?)",
                array($pvals['event_metrics_id'], $pvals['event_id'], $pvals['score'], $pvals['creation'], $pvals['notes'], $pvals['action']));

            } else {
                // UPDATE
                $res = $db->query("UPDATE event_metrics SET event_id = ?, score = ?, creation = ?, notes = ?, action = ? WHERE event_metrics_id = ?", array($pvals['event_id'], $pvals['score'], $pvals['creation'], $pvals['notes'], $pvals['action'], $pvals['event_metrics_id']));
            }
        } else {
            $q2 = "REPLACE INTO {$table_name} ({$key_vals}) VALUES ({$qmarks})"; 
            $res = $db->query($q2, $qvals);           
        }
        
        // check that result is not an error
        if (PEAR::isError($res)) {
            //die($res->getMessage());
            return 'database replace error.';
        } else {
            $table_id = $db->getOne("SELECT LAST_INSERT_ID()");
            $db->commit();
            /*  
                **********************************************************
                    Added by Sam, Ch157135. For new insert, table id will
                    be the new row id. But on an update it will be 0.
                **********************************************************
            */
            if($table_id == 0){
                return $pvals['event_metrics_id'];
            } else {
                return $table_id;
            }
            
        }
    }

    // update event metrics
    static function updateEventMetrics($table_data){
        $table_name = 'event_metrics';
        $table_id = EventInfo::replaceEventTable($table_name, $table_data);
        // print("*********** TABLE ID in UpdateEventMetrics Function ***********");
        // print_r($table_id);
        if (is_numeric($table_id)) {
            $status = $table_id;     // success
        } else {
            $status = 'Failed within updateEventMetrics -- failed to insert event table: ' . $table_name . ', error message: ' .$table_id;
        }
        return $status;
    }

    // updates purpose, returns true if updated or error message.
    static function updatePurpose($data_arr)
    {
        // sanitize the input
        foreach($data_arr as $key => $val) {
            $darr[$key] = strip_tags($val);
        }
        if(!is_numeric($darr['event_id'])) {
            return 'invalid event_id';
        }

        if(!($darr['outcome'])){
            return 'invalid parameters for purpose update';
        }

        $db = getDB();
        $eid = $db->getOne("SELECT event_id FROM purpose WHERE event_id = ? ", array($darr['event_id']));
        if ($eid) {

            // update the event table
            $q = $db->query("UPDATE purpose SET outcome = ?, phe_description = ?, phe_additional = ? WHERE event_id = ?",
                array($darr['outcome'], $darr['phe_description'], $darr['phe_additional'], $darr['event_id']));

            // check that result is not an error
            if (PEAR::isError($q)) {
                //die($res->getMessage());
                return 'failed update purpose query';
            } else {
                $db->commit();
            }
            return 1;
        } else {
            return 'event id not found.';
        }
    }

    // updates outcome, returns true if updated or error message.
    static function updateOutcome($data_arr)
    {
        // sanitize the input
        foreach($data_arr as $key => $val) {
            $darr[$key] = strip_tags($val);
        }
        if(!is_numeric($darr['event_id'])) {
            return 'invalid event_id';
        }

        if(!($darr['outcome'])){
            return 'invalid parameters for outcome update';
        }

        $db = getDB();
        $eid = $db->getOne("SELECT event_id FROM purpose WHERE event_id = ? ", array($darr['event_id']));
        if ($eid) {

            // update the event table
            $q = $db->query("UPDATE purpose SET outcome = ? WHERE event_id = ?",
                array($darr['outcome'], $darr['event_id']));

            // check that result is not an error
            if (PEAR::isError($q)) {
                //die($res->getMessage());
                return 'failed update outcome query';
            } else {
                $db->commit();
            }
            return 1;
        } else {
            return 'event id not found.';
        }
    }

    static function insertFollowup($data_arr)
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

        $action_date = $darr['action_date'] ? $darr['action_date'] : date('Y-m-d H:i:s');

        // insert into the followup table
        $q = $db->query("INSERT INTO followup (text, requester_id, action_date, event_id, response_id) VALUES (?, ?, ?, ?, ?)",
                        array($darr['text'], $darr['requester_id'], $action_date, $darr['event_id'], $darr['response_id']));
        $followup_id = $db->getOne("SELECT LAST_INSERT_ID()");
        $db->commit();
        return $followup_id;
    }

    static function deleteEvent($eid){
        $db = getDB();
        $event_id = $db->getOne("SELECT event_id FROM event WHERE event_id = ?", array($eid));

        // delete event
        $message = '';
        $status = 'success';
        if ($event_id) {
            $q = $db->query("DELETE FROM event WHERE event_id = ?", array($event_id));

            // check that result is not an error
            if (PEAR::isError($q)) {
                //die($res->getMessage());
                $status = 'failed';
                $message = 'failed to delete event';
            } else {
                $db->commit();
            }

            // delete associated tables
            if($status == 'success') {
                $q = $db->query("DELETE FROM event_notes WHERE event_id = ?", array($event_id));
                // check that result is not an error
                if (PEAR::isError($q)) {
                    //die($res->getMessage());
                    $status = 'failed';
                    $message = 'failed to delete event notes';
                } else {
                    $db->commit();
                }
                $q = $db->query("DELETE FROM event_fetp WHERE event_id = ?", array($event_id));
                // check that result is not an error
                if (PEAR::isError($q)) {
                    //die($res->getMessage());
                    $status = 'failed';
                    $message = 'failed to delete event fetp';
                } else {
                    $db->commit();
                }
                $q = $db->query("DELETE FROM followup WHERE event_id = ?", array($event_id));
                if (PEAR::isError($q)) {
                    //die($res->getMessage());
                    $status = 'failed';
                    $message = 'failed to delete followup';
                } else {
                    $db->commit();
                }
                $q = $db->query("DELETE FROM response WHERE event_id = ?", array($event_id));
                if (PEAR::isError($q)) {
                    //die($res->getMessage());
                    $status = 'failed';
                    $message = 'failed to delete response';
                } else {
                    $db->commit();
                }
            }

        }
        else{
            $status = 'failed';
            $message = 'event does not exist';
        }
        return array($status, $message);
    }

    static function deleteEvent2($eid){
        $db = getDB();
        $event_id = $db->getOne("SELECT event_id FROM event WHERE event_id = ?", array($eid));

        // delete event
        $message = '';
        $status = 'success';
        if ($event_id) {
            $q = $db->query("DELETE FROM event WHERE event_id = ?", array($event_id));

            // check that result is not an error
            if (PEAR::isError($q)) {
                //die($res->getMessage());
                $status = 'failed';
                $message = 'failed to delete event';
            } else {
                $db->commit();
            }

            // delete associated tables
            if($status == 'success') {
                $q = $db->query("DELETE FROM event_notes WHERE event_id = ?", array($event_id));
                // check that result is not an error
                if (PEAR::isError($q)) {
                    //die($res->getMessage());
                    $status = 'failed';
                    $message = 'failed to delete event notes';
                } else {
                    $db->commit();
                }
                $q = $db->query("DELETE FROM event_fetp WHERE event_id = ?", array($event_id));
                // check that result is not an error
                if (PEAR::isError($q)) {
                    //die($res->getMessage());
                    $status = 'failed';
                    $message = 'failed to delete event fetp';
                } else {
                    $db->commit();
                }
                $q = $db->query("DELETE FROM followup WHERE event_id = ?", array($event_id));
                if (PEAR::isError($q)) {
                    //die($res->getMessage());
                    $status = 'failed';
                    $message = 'failed to delete followup';
                } else {
                    $db->commit();
                }
                $q = $db->query("DELETE FROM response WHERE event_id = ?", array($event_id));
                if (PEAR::isError($q)) {
                    //die($res->getMessage());
                    $status = 'failed';
                    $message = 'failed to delete response';
                } else {
                    $db->commit();
                }
                $q = $db->query("DELETE FROM health_condition WHERE event_id = ?", array($event_id));
                if (PEAR::isError($q)) {
                    //die($res->getMessage());
                    $status = 'failed';
                    $message = 'failed to delete health condition';
                } else {
                    $db->commit();
                }
                $q = $db->query("DELETE FROM population WHERE event_id = ?", array($event_id));
                if (PEAR::isError($q)) {
                    //die($res->getMessage());
                    $status = 'failed';
                    $message = 'failed to delete population';
                } else {
                    $db->commit();
                }
                $q = $db->query("DELETE FROM purpose WHERE event_id = ?", array($event_id));
                if (PEAR::isError($q)) {
                    //die($res->getMessage());
                    $status = 'failed';
                    $message = 'failed to delete purpose';
                } else {
                    $db->commit();
                }
                $q = $db->query("DELETE FROM source WHERE event_id = ?", array($event_id));
                if (PEAR::isError($q)) {
                    //die($res->getMessage());
                    $status = 'failed';
                    $message = 'failed to delete source';
                } else {
                    $db->commit();
                }
            }

        }
        else{
            $status = 'failed';
            $message = 'event does not exist';
        }
        return array($status, $message);
    }

    function getFollowupId(){
        $db = getDB();
        return $db->getOne("select MAX(followup_id) from followup WHERE event_id=?", array($this->id));
    }

    // get all response and followup messages for a given event and fetp(s), sorted by date (most recent first)
    function getFetpMessages($fetp_id, $event_id){

        global $countries;

        // get followups sent to fetp(s), and responses from fetp(s)
        $db = getDB();
        if ($fetp_id) { //  get followup sent to single fetp
            $followups = $db->getAll("SELECT text, action_date, requester_id, fetp_id, f.followup_id, count(fetp_id) as fetp_count FROM followup f, event_fetp fe WHERE f.event_id=fe.event_id
                        AND f.followup_id=fe.followup_id AND f.event_id = ? AND fetp_id = ?  GROUP BY fe.followup_id ORDER BY action_date", array($event_id, $fetp_id));
            // get fetp response from single fetp
            $responses = $db->getAll("SELECT response, responder_id, response_date, response_permission, useful from response WHERE event_id = ? AND responder_id = ?
                                  ORDER BY response_date", array($event_id, $fetp_id));
        }
        else{   // get followups sent to all fetps
            $followups = $db->getAll("SELECT text, action_date, requester_id, fetp_id, f.followup_id, count(fetp_id) as fetp_count FROM followup f, event_fetp fe WHERE f.event_id=fe.event_id
                        AND f.followup_id=fe.followup_id AND f.event_id = ? GROUP BY fe.followup_id ORDER BY action_date", array($event_id));
            // get fetp responses from all fetps
            $responses = $db->getAll("SELECT response, response_id, responder_id, response_date, response_permission, useful, countrycode from response, fetp WHERE fetp_id = responder_id AND event_id = ?
                                  ORDER BY response_date", array($event_id));
        }

        // get event notes
        global $status_lu;
        global $response_permission_lu;

        $enotes =  $this->db->getAll("SELECT action_date,note,status,requester_id FROM event_notes WHERE event_id = ? ORDER BY action_date", array($event_id));

        // get info of person for the event
        $event_person = $this->getEventPerson($event_id);

        // get event info
        $ei = new EventInfo($event_id);
        $event_info = $ei->getInfo();


        // save all followups, responses, and event notes in a message array
        $i = 0;
        foreach ($followups as $followup ){
            $followup_person =$this->getFollowupPerson($event_id, $followup['requester_id']);

            $messages[$i]['text'] = nl2br($followup['text']);
            $messages[$i]['fetp_count'] = $followup['fetp_count'];
            $messages[$i]['fetp_id'] = $followup['fetp_id'];
            $messages[$i]['type'] = 'Moderator Response';
            $messages[$i]['requester_id'] = $followup['requester_id'];
            $messages[$i]['followup_id'] = $followup['followup_id'];
            $messages[$i]['person'] = $followup_person['name'];
            $messages[$i]['person_id'] = $followup_person['user_id'];
            $messages[$i]['organization_id'] = $followup_person['organization_id'];
            $messages[$i++]['date'] = date('j-M-Y H:i', strtotime($followup['action_date']));
        }
        foreach ($responses as $response ){

            $messages[$i]['files'] = $this->getResponseFileNames($response['response_id']);
            $messages[$i]['text'] = nl2br($response['response']);
            if ($response['response_permission'] == "0")
                $messages[$i]['text'] = $response_permission_lu["0"];;
            $messages[$i]['permission'] = $response['response_permission'];
            $messages[$i]['type'] = 'Member Response';
            $messages[$i]['response_id'] = $response['response_id'];
            $messages[$i]['fetp_id'] = $response['responder_id'];
            $messages[$i]['country'] = $countries[$response['countrycode']];
            $messages[$i]['useful'] = $response['useful'];
            $messages[$i]['person_id'] = $event_person['user_id'];
            $messages[$i]['organization_id'] = $event_person['organization_id'];
            $messages[$i]['event_location'] = $event_info['location'];
            $messages[$i++]['date'] = date('j-M-Y H:i', strtotime($response['response_date']));
        }
        foreach ($enotes as $enote){
            $status_person =$this->getStatusPerson($event_id, $enote['requester_id']);

            $messages[$i]['text'] = nl2br($enote['note']);
            $messages[$i]['status'] = $status_lu[$enote['status']];
            $messages[$i]['type'] = 'Event Notes';
            $messages[$i]['date'] = date('j-M-Y H:i', strtotime($enote['action_date']));
            $messages[$i]['person'] = $status_person['name'];
            $messages[$i]['person_id'] = $status_person['user_id'];
            $messages[$i++]['organization_id'] = $status_person['organization_id'];
        }

        // sort messages by date
       usort($messages, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $messages;
    }

    function getEventHistoryFETP($fetp_id, $event_id){
        // get fetp messages
        $messages = $this->getFetpMessages($fetp_id, $event_id);
        $history = '';
        // style message history for email
        $counter =0;
        foreach ($messages as $message) {
            if ($counter > 0) {  // skip first (current ) message
                $mtype = $message['type'];
                if ($message['type'] == 'Event Notes')
                    $mtype = $message['status'] . " event request";
                if ($message['type'] == 'Member Response')
                    $mtype = "Your response";
                if ($message['type'] == 'Moderator Response') {
                    if ($message['fetp_count'] > 1)
                        $mtype = "Followup sent to all Members";
                    else
                        $mtype = "Followup sent to you";
                }
                $mtext = $message['text'];
                //$mdatetime = $message['date'];
                $mdatetime = date('j-M-Y H:i', strtotime($message['date']));
                $history .= "<div style='background-color: #fff;padding:24px;color:#666;border: 1px solid #B4FEF7;'>";
                $history .= "<p style='margin:12px 0;'>$mtype,  $mdatetime <br></p>$mtext</div><br>";
            }
            $counter++;
        }
        return $history;
    }

    function getEventHistoryAll($event_id){
        //get all fetp messages
        $messages = $this->getFetpMessages(null, $event_id);
        $history = '';
        // style message history for email
        $counter =0;
        foreach ($messages as $message) {
            if ($counter > 0) {  // skip first (current ) message
                $mtype = $message['type'];
                if ($message['type'] == 'Event Notes')
                    $mtype = $message['person'] . " ". $message['status'] . " event request";
                if ($message['type'] == 'Moderator Response'){
                    if ($message['fetp_count'] > 1)
                        $mtype = $message['person'] . " sent followup to all Members";
                    else
                        $mtype = $message['person'] . " sent followup to 1 Member";
                }
                $mtext = $message['text'];
                //$mdatetime = $message['date'];
                $mdatetime = date('j-M-Y H:i', strtotime($message['date']));
                $history .= "<div style='background-color: #fff;padding:24px;color:#666;border: 1px solid #B4FEF7;'>";
                $history .= "<p style='margin:12px 0;'>$mtype,  $mdatetime <br></p>$mtext</div><br>";
            }
            $counter++;
        }
        return $history;
    }

    // get name, hmu_id, user_id, and org id of person who generated the event
    function getEventPerson($event_id){

        // get hmu_id for event
        $db = getDB();
        $row = $db->getRow("select hmu_id, user_id, organization_id, email from event, user where requester_id=user_id and (event_id=?)", array($event_id));
        $hmu_id = $row['hmu_id'];

        // get user from hmu_id in hm database
        $db = getDB('hm');
        $user = $db->getRow(" select name, username, email from hmu where hmu_id='$hmu_id'");
        $user['user_id'] = $row['user_id'];
        $user['organization_id'] = $row['organization_id'];
        if ($row['email']) {
            $user['email'] = $row['email'];
        }
        return $user;
    }

    // get name, hmu_id, user_id, and org id of person who sent the followup
    function getFollowupPerson($event_id, $requester_id){

        // get hmu_id for event
        $db = getDB();
        $row = $db->getRow("select distinct hmu_id, user_id, organization_id, email from followup, user where requester_id=user_id
                            and event_id=? and requester_id= ?", array($event_id, $requester_id));
        $hmu_id = $row['hmu_id'];

        // get user from hmu_id in hm database
        $db = getDB('hm');
        $user = $db->getRow(" select name, username, email from hmu where hmu_id='$hmu_id'");
        $user['user_id'] = $row['user_id'];
        $user['organization_id'] = $row['organization_id'];
        if ($row['email']) {
            $user['email'] = $row['email'];
        }
        return $user;
    }

    // get name, hmu_id, user_id, and org id of person who changed the event status
    function getStatusPerson($event_id, $requester_id){

        // get hmu_id for event
        $db = getDB();
        $row = $db->getRow("select distinct hmu_id, user_id, organization_id, email from event_notes, user where requester_id=user_id
                            and event_id=? and requester_id= ?", array($event_id, $requester_id));
        $hmu_id = $row['hmu_id'];

        // get user from hmu_id in hm database
        $db = getDB('hm');
        $user = $db->getRow(" select name, username, email from hmu where hmu_id='$hmu_id'");
        $user['user_id'] = $row['user_id'];
        $user['organization_id'] = $row['organization_id'];
        if ($row['email']) {
            $user['email'] = $row['email'];
        }
        return $user;
    }

    // get ALL event stats for all types: yours, yourorg, and other for the csv
    static function getEventStats($uid, $status) {
        // get event info
        $events = EventInfo::getAllEvents($uid, $status);

        // get stats for each type
        $yours = EventInfo::getStats($events['yours'], $status);
        $yourorg = EventInfo::getStats($events['yourorg'], $status);
        $other = EventInfo::getStats($events['other'], $status);
        $stats = array_merge($yours, $yourorg, $other);

        return $stats;
    }

    // get ALL event stats for all types: yours, yourorg, and other for the csv
    static function getEventStats2($uid, $status) {
        // get event info
        $events = EventInfo::getAllEvents($uid, $status, V2START_DATE);

        // get stats for each type
        $yours = EventInfo::getStats2($events['yours'], $status);
        $yourorg = EventInfo::getStats2($events['yourorg'], $status);
        $other = EventInfo::getStats2($events['other'], $status);
        $stats = array_merge($yours, $yourorg, $other);

        return $stats;
    }

    // get event stats for each type formatted for the csv
    static function getStats($events, $status){

        $event_stats = array();
        $stats = array();

        foreach ($events as $event) {

            // basic stats
            $event_stats['status'] = $status;
            $event_stats['notes'] = $event['notes'];
            $event_stats['person'] = $event['person'];
            $event_stats['organization_id'] = $event['organization_id'];
            $event_stats['country'] = $event['country'];
            $event_stats['event_id'] = $event['event_id'];
            $event_stats['disease'] = $event['disease'];
            $event_stats['title'] = $event['title'];
            $event_stats['create_date'] = $event['create_date'];
            $event_stats['location'] = $event['location'];
            $event_stats['description'] = $event['description'];
            $event_stats['personalized_text'] = $event['personalized_text'];
            $event_stats['requester_id'] = $event['requester_id'];
            $event_stats['num_responses'] = $event['num_responses'];
            $event_stats['num_responses_content'] = $event['num_responses_content'];
            $event_stats['num_responses_nocontent'] = $event['num_responses_nocontent'];
            $event_stats['num_notuseful_responses'] = $event['num_notuseful_responses'];
            $event_stats['num_useful_responses'] = $event['num_useful_responses'];
            $event_stats['num_useful_promed_responses'] = $event['num_useful_promed_responses'];
            $event_stats['member_ids'] = '"' . $event["member_ids"] . '"';
            $event_stats['first_response_date'] = $event["first_response_date"];

            // not useful responses
            $notuseful = $event['notuseful_responses'];
            $event_stats['notuseful_ids'] = '';
            foreach ($notuseful as $nu) {
                $event_stats['notuseful_ids'] .= ($event_stats['notuseful_ids'] == '') ? $nu['responder_id'] : ',' . $nu['responder_id'];
            }
            $event_stats['notuseful_ids'] = '"' . $event_stats['notuseful_ids'] . '"';

            // useful responses
            $useful = $event['useful_responses'];
            $event_stats['useful_ids'] = '';
            foreach ($useful as $u) {
                $event_stats['useful_ids'] .= ($event_stats['useful_ids'] == '') ? $u['responder_id'] : ',' . $u['responder_id'];
            }
            $event_stats['useful_ids'] = '"'. $event_stats['useful_ids'] . '"';

            // useful promed responses
            $promed = $event['useful_promed_responses'];
            $event_stats['promed_ids'] = '';
            foreach ($promed as $p) {
                $event_stats['promed_ids'] .= ($event_stats['promed_ids'] == '') ? $p['responder_id'] : ',' . $p['responder_id'];
            }
            $event_stats['promed_ids'] = '"' . $event_stats['promed_ids'] . '"';

            // followups
            $followups = $event['num_followups'];
            $first_request = end($followups);
            $event_stats['num_members'] = $first_request['num'];    //number of members on initial RFI

            // push event stats
            array_push($stats, $event_stats);
        }

        return $stats;
    }

    // get event stats for each type formatted for the csv
    static function getStats2($events, $status){

        $event_stats = array();
        $stats = array();

        foreach ($events as $event) {

            // basic stats
            $event_stats['status'] = $status;
            $event_stats['notes'] = $event['notes'];
            $event_stats['person'] = $event['person'];
            $event_stats['organization_id'] = $event['organization_id'];
            $event_stats['requester_id'] = $event['requester_id'];
            $event_stats['country'] = $event['country'];
            $event_stats['event_id'] = $event['event_id'];
            $event_stats['title'] = $event['title'];
            $event_stats['create_date'] = $event['create_date'];
            $event_stats['event_date'] = $event['event_date'];  // new
            $event_stats['iso_create_date'] = $event['iso_create_date']; // new
            $event_stats['action_date'] = $event['action_date']; // new
            $event_stats['location'] = $event['location'];
            $event_stats['location_details'] = $event['location_details'];  // new
            $event_stats['affected_population'] = $event['affected_population'];  // new
            $event_stats['affected_population_details'] = $event['affected_population_details'];  // new
            $event_stats['human_health_condition'] = $event['human_health_condition'];  // new
            $event_stats['animal_health_condition'] = $event['animal_health_condition'];  // new
            $event_stats['health_condition_details'] = $event['health_condition_details'];  // new
            $event_stats['other_relavant_public_health_details'] = $event['other_relevant_ph_details'];  // new
            $event_stats['verification_or_update'] = $event['verfication_or_update'];  // new
            $event_stats['rfi_purpose'] = $event['rfi_purpose'];  // new
            $event_stats['personalized_message'] = $event['personalized_message'];  // new
            $event_stats['rfi_source'] = $event['rfi_source'];  // new
            $event_stats['rfi_source_details'] = $event['rfi_source_details'];  // new


            if ($event['outcome'] == 'VP')
                $event_stats['outcome'] = 'Verified (+)';
            else if ($event['outcome'] == 'VN')
                $event_stats['outcome'] = 'Verified (-)';
            else if ($event['outcome'] == 'UV')
                $event_stats['outcome'] = 'Unverified';
            else if ($event['outcome'] == 'UP')
                $event_stats['outcome'] = 'Updated (+)';
            else if ($event['outcome'] == 'NU')
                $event_stats['outcome'] = 'Updated (-)';
            else
                $event_stats['outcome'] = 'Pending';  // new

            //$event_stats['description'] = $event['description'];
            //$event_stats['personalized_text'] = $event['personalized_text'];
            $event_stats['num_responses'] = $event['num_responses'];
            $event_stats['num_responses_content'] = $event['num_responses_content'];
            $event_stats['num_responses_nocontent'] = $event['num_responses_nocontent'];
            $event_stats['num_notuseful_responses'] = $event['num_notuseful_responses'];
            $event_stats['num_useful_responses'] = $event['num_useful_responses'];
            $event_stats['num_useful_promed_responses'] = $event['num_useful_promed_responses'];
            $event_stats['member_ids'] = '"' . $event["member_ids"] . '"';
            $event_stats['first_response_date'] = $event["first_response_date"];

            // not useful responses
            $notuseful = $event['notuseful_responses'];
            $event_stats['notuseful_ids'] = '';
            foreach ($notuseful as $nu) {
                $event_stats['notuseful_ids'] .= ($event_stats['notuseful_ids'] == '') ? $nu['responder_id'] : ',' . $nu['responder_id'];
            }
            $event_stats['notuseful_ids'] = '"' . $event_stats['notuseful_ids'] . '"';

            // useful responses
            $useful = $event['useful_responses'];
            $event_stats['useful_ids'] = '';
            foreach ($useful as $u) {
                $event_stats['useful_ids'] .= ($event_stats['useful_ids'] == '') ? $u['responder_id'] : ',' . $u['responder_id'];
            }
            $event_stats['useful_ids'] = '"'. $event_stats['useful_ids'] . '"';


            $event_stats['phe_summary_description'] = $event['phe_description'];  // new
            $event_stats['phe_summary_additional_information'] = $event['phe_additional'];  // new


            // useful promed responses
            $promed = $event['useful_promed_responses'];
            $event_stats['promed_ids'] = '';
            foreach ($promed as $p) {
                $event_stats['promed_ids'] .= ($event_stats['promed_ids'] == '') ? $p['responder_id'] : ',' . $p['responder_id'];
            }
            $event_stats['promed_ids'] = '"' . $event_stats['promed_ids'] . '"';

            // followups
            $followups = $event['num_followups'];
            $first_request = end($followups);
            $event_stats['num_members'] = $first_request['num'];    //number of members on initial RFI

            // get event responses
            $event_responses = $event['event_responses'];
            $max_responses = 100;
            for ($i= 0; $i < $max_responses; $i++){
                $response_n = 'response_'. (string)($i+1);
                if ($event_responses && $event_responses[$i])
                    $event_stats[$response_n] = $event_responses[$i];
                else
                    $event_stats[$response_n] = '';
            }

            // push event stats
            array_push($stats, $event_stats);
        }

        return $stats;
    }

}
?>
