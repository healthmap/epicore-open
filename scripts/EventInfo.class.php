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

        $event_person = $this->getEventPerson($this->id);

        $event_info = $this->db->getRow("SELECT event.*, place.name AS location FROM event, place WHERE event_id = ? AND event.place_id = place.place_id", array($this->id));
        $event_info['org_requester_id'] = self::getOrganizationOfRequester();
        $event_info['html_description'] = str_replace("\n", "<br>", $event_info['description']);
        $event_info['num_responses'] = $this->db->getOne("SELECT count(*) FROM response WHERE event_id = ?", array($this->id));
        $event_info['create_date'] = date('j-M-Y H:i', strtotime($event_info['create_date']));
        $event_info['person'] = $event_person['name'];

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
        $messages[$size]['person'] = $event_info['person'];
        $messages[$size]['organization_id'] = $event_info['org_requester_id'];

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

    function changeStatus($status, $requester_id, $notes, $reason) {
        $initiator_oid = $this->db->getOne("SELECT user.organization_id FROM event, user WHERE event_id = ? AND event.requester_id = user.user_id", array($this->id));
        $requester_oid = $this->db->getOne("SELECT organization_id FROM user WHERE user_id = ?", array($requester_id));
        if($requester_oid == $initiator_oid) {
            $notes = strip_tags($notes);
            $this->db->query("INSERT INTO event_notes (event_id, action_date, note, reason, status, requester_id) VALUES (?,?,?,?,?,?)",
                            array($this->id, date('Y-m-d H:i:s'), $notes, $reason, $status, $requester_id));
            $this->db->commit();
            return 1;
        }
        return 0;
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

    function buildEmailForEvent($event_info = array(), $type, $custom_vars = array(), $return_type = 'text')
    {
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
        if($response_info['response_permission'] == 0) {
            $response_info['response_permission'] = '';
            $response_info['response'] = $response_permission_lu[0];
        } else {
            $response_info['response_permission'] = $response_permission_lu[$response_info['response_permission']];
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
            $row['num_responses_content'] = $db->getOne("SELECT count(*) FROM response WHERE event_id = ? AND response_permission <>0 ", array($row['event_id']));
            $row['num_responses_nocontent'] = $row['num_responses'] - $row['num_responses_content'];
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
                $row['num_followups'][] = array('date' => $datearr[0], 'num' => count($datearr), 'text' => $text[$followupnum]);
            }
            $row['iso_create_date'] = $row['create_date'];
            $row['event_id_int'] = (int)$row['event_id'];
            $row['create_date'] = date('j-M-Y H:i', strtotime($row['create_date']));

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
        $q = $db->query("INSERT INTO event (place_id, title, description, personalized_text, create_date, requester_id, search_box, search_countries, alert_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", array($place_id, $darr['title'], $darr['description'], $darr['personalized_text'], $create_date, $darr['requester_id'], $darr['search_box'], $darr['search_countries'], $darr['alert_id']));
        $event_id = $db->getOne("SELECT LAST_INSERT_ID()");
        $db->commit();
        return $event_id;
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

    function getFollowupId(){
        $db = getDB();
        return $db->getOne("select MAX(followup_id) from followup WHERE event_id=?", array($this->id));
    }

    // get all response and followup messages for a given event and fetp(s), sorted by date (most recent first)
    function getFetpMessages($fetp_id, $event_id){

        // get followups sent to fetp(s), and responses from fetp(s)
        $db = getDB();
        if ($fetp_id) { //  get followup sent to single fetp
            $followups = $db->getAll("SELECT text, action_date, requester_id, fetp_id, f.followup_id, count(fetp_id) as fetp_count FROM followup f, event_fetp fe WHERE f.event_id=fe.event_id
                        AND f.followup_id=fe.followup_id AND f.event_id = ? AND fetp_id = ?  GROUP BY text ORDER BY action_date", array($event_id, $fetp_id));
            // get fetp response from single fetp
            $responses = $db->getAll("SELECT response, responder_id, response_date, response_permission, useful from response WHERE event_id = ? AND responder_id = ?
                                  ORDER BY response_date", array($event_id, $fetp_id));
        }
        else{   // get followups sent to all fetps
            $followups = $db->getAll("SELECT text, action_date, requester_id, fetp_id, f.followup_id, count(fetp_id) as fetp_count FROM followup f, event_fetp fe WHERE f.event_id=fe.event_id
                        AND f.followup_id=fe.followup_id AND f.event_id = ? GROUP BY text ORDER BY action_date", array($event_id));
            // get fetp responses from all fetps
            $responses = $db->getAll("SELECT response, response_id, responder_id, response_date, response_permission, useful from response WHERE event_id = ?
                                  ORDER BY response_date", array($event_id));
        }

        // get event notes
        global $status_lu;
        global $response_permission_lu;

        $enotes =  $this->db->getAll("SELECT action_date,note,status,requester_id FROM event_notes WHERE event_id = ? ORDER BY action_date", array($event_id));

        // get info of person for the event
        $event_person = $this->getEventPerson($event_id);

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

            $messages[$i]['text'] = nl2br($response['response']);
            if ($response['response_permission'] == "0")
                $messages[$i]['text'] = $response_permission_lu["0"];;
            $messages[$i]['permission'] = $response['response_permission'];
            $messages[$i]['type'] = 'Member Response';
            $messages[$i]['response_id'] = $response['response_id'];
            $messages[$i]['fetp_id'] = $response['responder_id'];
            $messages[$i]['useful'] = $response['useful'];
            $messages[$i]['person_id'] = $event_person['user_id'];
            $messages[$i]['organization_id'] = $event_person['organization_id'];
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
}
?>
