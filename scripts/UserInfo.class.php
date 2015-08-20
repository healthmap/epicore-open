<?php
/**
 * UserInfo.php
 * Sue Aman 5 Jan 2010
 * info about an individual user of Epicore 
 */

require_once 'db.function.php';
require_once 'const.inc.php';
require_once 'PlaceInfo.class.php';
require_once 'pbkdf2.php';
//require_once 'cache.function.php';

class UserInfo
{
    function __construct($user_id, $fetp_id)
    {
        // the id coming in is EITHER a user_id (mod) or fetp_id
        $this->id = $user_id ? $user_id : $fetp_id;
        $this->db =& getDB();
    }

    function getOrganizationId()
    {
        return $this->db->getOne("SELECT organization_id FROM user WHERE user_id = ?", array($this->id));
    }

    function getFETPRequests($status)
    {
        $q = $this->db->query("SELECT event.event_id, event.title, event_fetp.send_date, place.name AS location FROM event_fetp, event, place WHERE fetp_id = ? AND event_fetp.event_id = event.event_id AND event.place_id = place.place_id ORDER BY send_date DESC", array($this->id));
        $status = $status ? $status : 'O';
        while($row = $q->fetchRow()) {
            // responses are recorded by the FETPs user id, not FETP_id
            // get the current status of event - open or closed
            $dbstatus = $this->db->getOne("SELECT status FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($row['event_id']));
            $dbstatus = $dbstatus ? $dbstatus : 'O'; // if no value for status, it's open
            if($dbstatus == $status) {
                $requests[$row['event_id']]['event_id'] = $row['event_id'];
                $requests[$row['event_id']]['title'] = $row['title'];
                $requests[$row['event_id']]['location'] = $row['location'];
                // make send date an array, because there may be multiple
                $requests[$row['event_id']]['send_dates'][] = date('n/j/Y H:i', strtotime($row['send_date']));
                
                // get the FETPs responses to that event
                $response_dates = [];
                $respq = $this->db->query("SELECT response_id, response_date FROM response WHERE responder_id = ? AND event_id = ? ORDER BY response_date DESC", array($this->id, $row['event_id']));
                while($resprow = $respq->fetchRow()) {
                    $response_dates[] = date('n/j/Y H:i', strtotime($resprow['response_date']));
                    //$requests[$row['event_id']]['response_dates'][$resprow['response_id']] = date('n/j/Y H:i', strtotime($resprow['response_date']));
                }

                $requests[$row['event_id']]['response_dates'] = $response_dates;
            }
        }
        return $requests;
    }

    static function authenticateUser($dbdata) 
    {
        $email = strip_tags($dbdata['email']);
        // first try the HealthMap database
        $db = getDB('hm');
        $user = $db->getRow("SELECT hmu_id, username, email, pword_hash FROM hmu WHERE (username = ? OR email = ?) AND confirmed = 1", array($email, $email));
        $resp = validate_password($dbdata['password'], $user['pword_hash']);
        $db = getDB();
        if($resp) {
            $epicore_info = $db->getRow("SELECT user.*, organization.name AS orgname FROM user LEFT JOIN organization ON user.organization_id = organization.organization_id WHERE hmu_id = ?", array($user['hmu_id']));
            $user['user_id'] = $epicore_info['user_id'];
            $user['organization_id'] = $epicore_info['organization_id'];
            $user['orgname'] = $epicore_info['orgname'];
            unset($user['pword_hash']);
            return $user;
        } else { // try the epicore database
            $pword_hash = hash('sha256', $dbdata['password']);
            return $db->getRow("SELECT user.*, organization.name AS orgname FROM user LEFT JOIN organization ON user.organization_id = organization.organization_id WHERE email = ? AND pword_hash = ?", array($email, $pword_hash));
        }
    }

    static function authenticateMod($ticket_id) 
    {
        $hmdb = getDB('hm');
        $hmu_id = $hmdb->getOne("SELECT hmu_id FROM ticket WHERE val = ? AND exp > now()", array($ticket_id));
        if(!$hmu_id) {
            return 0;
        }
        $user = $hmdb->getRow("SELECT hmu_id, username, email FROM hmu WHERE hmu_id = ?", array($hmu_id));
        $db = getDB();
        $epicore_info = $db->getRow("SELECT user.*, organization.name AS orgname FROM user LEFT JOIN organization ON user.organization_id = organization.organization_id WHERE user.hmu_id = ?", array($hmu_id));
        $user['user_id'] = $epicore_info['user_id'];
        $user['organization_id'] = $epicore_info['organization_id'];
        $user['orgname'] = $epicore_info['orgname'];
        return $user;
    }

    static function authenticateFetp($ticket_id)
    {
        $db = getDB();
        return $db->getRow("SELECT fetp_id FROM ticket WHERE val = ? AND exp > now()", array($ticket_id));
    }

    /* filtertype is countries or radius; filterval is either array of country codes or array of bounding box values */
    static function getFETPsInLocation($filtertype, $filterval)
    {
        /* WEBSERVICE ------------
        for tephinet, we could call the function getFETPEligible 
        and pass POST param countrycode OR boundingbox (lat1,lat2,lon1,lon2)
        ------------------- END WEBSERVICE */

        $db = getDB();
        if($filtertype == "radius") {
            $q = $db->query("SELECT fetp_id FROM fetp WHERE lat > ? AND lat < ? AND lon > ? AND lon < ?", $filterval);
        } else {
            $qmarks = join(",", array_fill(0, count($filterval), '?'));
            $q = $db->query("SELECT fetp_id FROM fetp WHERE countrycode in ($qmarks)", $filterval);
        }
        while($row = $q->fetchRow()) {
            $send_ids[] = $row['fetp_id'];
        }
        $send_ids = array_unique($send_ids);
        // if we ever apply filter on training, this will be what we send back
        //$userlist = array('sending' => count($send_ids), 'all' => count($unique_users), 'ddd' => count($ddd_trained), 'graduate' => count($training_status['Graduate']), 'na' => count($training_status['N/A']), 'trainee' => count($training_status['Trainee']), 'unspecified' => count($training_status['unspecified']));
        $userlist = array('sending' => count($send_ids));
        return array($userlist, $send_ids);
    }

    /* use the webservice on tephinet to get email addresses for an array of ids */
    static function getFETPEmails($fetp_ids)
    {
        // call to tephinet webservice
        require_once "GetURL.class.php";
        $url = TEPHINET_BASE . 'epicore/getemails';
        $gurl = GetURL::getInstance();
        $fields_string = 'consumer_key='.TEPHINET_CONSUMER_KEY;
        foreach($fetp_ids as $id) {
            // get the tephinet ID from the fetp table
            if(is_numeric($id)) {
                $tephinet_id = $db->getOne("SELECT tephinet_id FROM fetp WHERE fetp_id = ?", $id);
                $fields_string .= '&ids[]='.$tephinet_id;
            }
        }
        $result = $gurl->post($url, $fields_string);
        $email_addresses = json_decode($result);
        return $email_addresses;
    }

    /* use the webservice on tephinet to get FETP-eligible ids and location info */
    static function getFETPEligible()
    {
        // call to tephinet webservice
        require_once "GetURL.class.php";
        $url = TEPHINET_BASE . 'epicore/getfetps';
        $gurl = GetURL::getInstance();
        $fields_string = 'consumer_key='.TEPHINET_CONSUMER_KEY;
        $result = $gurl->post($url, $fields_string);
        $fetpinfo = json_decode($result);
        $db = getDB();
        $mapping = array('lat' => 'latitude','lon' => 'longitude','countrycode' => 'country');
        foreach($fetpinfo as $fetpobj) {
            if(is_numeric($fetpobj->uid)) {
                $fetpinfo = $db->getRow("SELECT * FROM fetp WHERE tephinet_id = ?", array($fetpobj->uid));
                // already in our table, need to update
                if($fetpinfo) {
                    foreach($mapping as $epicore_field => $tephinet_field) {
                        $tephinet_value = $tephinet_field == "country" ? strtoupper($fetpobj->$tephinet_field) : $fetpobj->$tephinet_field;
                        $epicore_value = $fetpinfo[$epicore_field];
                        if($epicore_value != $tephinet_value) {
                            $db->query("UPDATE fetp SET $epicore_field = ? WHERE tephinet_id = ?", array($tephinet_value, $fetpobj->uid));
                            $db->query("INSERT INTO editlog (old_val,new_val,tablename,fieldname,change_date) VALUES (?,?,?,?,?)", array($epicore_value, $tephinet_value, 'fetp', $epicore_field, date('Y-m-d H:i:s')));
                        }
                    }
                } else { // insert
                    $db->query("INSERT INTO fetp (lat,lon,countrycode,tephinet_id) VALUES (?,?,?,?)", array($fetpobj->latitude,$fetpobj->longitude,strtoupper($fetpobj->country),$fetpobj->uid));
                }
                $db->commit();
            }
        }
        return $fetpinfo;
    }

    // returns user id if a new user is inserted and false if the user already exists.
    static function joinMaillist($pvals)
    {
        $db = getDB();
        $user_id = $db->getOne("SELECT maillist_id FROM maillist WHERE email = ?", array($pvals['email']));
        if(!$user_id) { // insert if not
            $key_vals = join(",", array_keys($pvals));
            $qmarks = join(",", array_fill(0, count($pvals), '?'));
            $qvals = array_values($pvals);
            $db->query("INSERT INTO maillist ($key_vals) VALUES ($qmarks)", $qvals);
            $user_id = $db->getOne("SELECT LAST_INSERT_ID()");
            $db->commit();
            return $user_id;
        }
        else
            return false;

    }

}
?>
