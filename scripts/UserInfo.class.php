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
                $respq = $this->db->query("SELECT response_id, response_date FROM response WHERE responder_id = ? AND event_id = ? ORDER BY response_date DESC", array($this->id, $row['event_id']));
                $response_dates = [];
                while($resprow = $respq->fetchRow()) {
                    array_push($response_dates, date('n/j/Y H:i', strtotime($resprow['response_date'])));
                    //$requests[$row['event_id']]['response_dates'][$resprow['response_id']] = date('n/j/Y H:i', strtotime($resprow['response_date']));
                }
                $requests[$row['event_id']]['response_dates'] = array_unique($response_dates);
            }
        }
        return $requests;
    }

    static function createPassword($password = '') {
        $password = $password ? $password : substr(md5(rand().rand()), 0, 8);
        $pword_hash = create_hash($password);
        return array($password, $pword_hash);
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
            $uinfo = $db->getRow("SELECT user.user_id, user.hmu_id, user.organization_id, organization.name AS orgname FROM user LEFT JOIN organization ON user.organization_id = organization.organization_id WHERE hmu_id = ?", array($user['hmu_id']));
            $uinfo['username'] = $user['username'];
            $uinfo['email'] = $user['email'];
            return $uinfo;
        } else { 
            // first try the MOD user table.  If none, try the FETP user table.
            $uinfo = $db->getRow("SELECT user.*, organization.name AS orgname FROM user LEFT JOIN organization ON user.organization_id = organization.organization_id WHERE email = ?", array($email));
            if(!$uinfo['user_id']) {
                $uinfo = $db->getRow("SELECT fetp_id, pword_hash, lat, lon, countrycode, active FROM fetp WHERE email = ?", array($email));
                $uinfo['username'] = "FETP ".$uinfo['fetp_id'];
            }
            if($uinfo['user_id'] || $uinfo['fetp_id']) {
                $resp = validate_password($dbdata['password'], $uinfo['pword_hash']);
                if($resp) {
                    unset($uinfo['pword_hash']);
                    return $uinfo;
                }
            }
            return 0;
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

    /* 
    if it's a non-Tephinet FETP, get the email from epicore db, otherwise
    use the webservice on tephinet to get email addresses for an array of ids 
    */
    static function getFETPEmails($fetp_ids)
    {
        // pull all fetp_id/emails from our db
        $db = getDB();
        $email_hash = $db->getAssoc("SELECT fetp_id, email FROM fetp WHERE active='Y' AND email is NOT NULL");
        
        // call to tephinet webservice
        require_once "GetURL.class.php";
        $url = TEPHINET_BASE . 'epicore/getemails';
        $gurl = GetURL::getInstance();
        $fields_string = 'consumer_key='.TEPHINET_CONSUMER_KEY;
        foreach($fetp_ids as $id) {
            // get the tephinet ID from the fetp table
            if(is_numeric($id) && !isset($email_hash[$id])) {
                $tephinet_id = $db->getOne("SELECT tephinet_id FROM fetp WHERE fetp_id = ?", $id);
                $fetp_lu[$tephinet_id] = $id;
                $fields_string .= '&ids[]='.$tephinet_id;
            }
        }
        $result = $gurl->post($url, $fields_string);
        $email_addresses = json_decode($result);
        foreach($email_addresses as $tephinet_obj) {
            $email_hash[$fetp_lu[$tephinet_obj->uid]] = $tephinet_obj->mail;
        }
        return $email_hash;
    }

    /* use the webservice on tephinet to get FETP-eligible ids and location info 
        compare the info to the fetp table in epicore and update as needed
    */
    function getFETPEligible()
    {
        // call to tephinet webservice
        require_once "GetURL.class.php";
        $url = TEPHINET_BASE . 'epicore/getfetps';
        $gurl = GetURL::getInstance();
        $fields_string = 'consumer_key='.TEPHINET_CONSUMER_KEY;
        $result = $gurl->post($url, $fields_string);
        $fetpinfo = json_decode($result);
        $mapping = array('lat' => 'latitude','lon' => 'longitude','countrycode' => 'country');
        $existq = $this->db->query("SELECT * FROM fetp WHERE tephinet_id is NOT NULL");
        while($existr = $existq->fetchRow()) {
            $existids[$existr['tephinet_id']] = $existr;
        }
        foreach($fetpinfo as $fetpobj) {
            if(is_numeric($fetpobj->uid)) {
                // already in our table, may need to update info
                if($existids[$fetpobj->uid]) {
                    if($existids[$fetpobj->uid]['active'] == 'N') {
                        $this->db->query("UPDATE fetp SET active = 'Y' WHERE tephinet_id = ?", array($fetpobj->uid));
                    }
                    foreach($mapping as $epicore_field => $tephinet_field) {
                        $tephinet_value = $tephinet_field == "country" ? strtoupper($fetpobj->$tephinet_field) : $fetpobj->$tephinet_field;
                        $epicore_value = $existids[$fetpobj->uid][$epicore_field];
                        $t_compare_val = $epicore_field == "lat" || $epicore_field == "lon" ? round($tephinet_value, 2) : $tephinet_value;
                        $e_compare_val = $epicore_field == "lat" || $epicore_field == "lon" ? round($epicore_value, 2) : $epicore_value;
                        // rounding b/c of weirdness with precision
                        if($e_compare_val != $t_compare_val) {
                            $this->db->query("UPDATE fetp SET $epicore_field = ? WHERE tephinet_id = ?", array($tephinet_value, $fetpobj->uid));
                            $this->db->query("INSERT INTO editlog (old_val,new_val,tablename,fieldname,change_date) VALUES (?,?,?,?,?)", array($epicore_value, $tephinet_value, 'fetp', $epicore_field, date('Y-m-d H:i:s')));
                        }
                    }
                    unset($existids[$fetpobj->uid]);
                } else { // insert
                    $this->db->query("INSERT INTO fetp (lat,lon,countrycode,tephinet_id) VALUES (?,?,?,?)", array($fetpobj->latitude,$fetpobj->longitude,strtoupper($fetpobj->country),$fetpobj->uid));
                }
            }
        }
        // anything that's left over from original db needs to be set to inactive b/c not eligible on tephinet anymore
        foreach($existids as $tid => $fetpvals) {
            if($fetpvals['active'] == 'Y') {
                $this->db->query("UPDATE fetp SET active='N' WHERE tephinet_id = ?", array($tid));
            }
        }
        $this->db->commit();
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

    // returns user id if a new user is inserted, or true if the user already exists and was updated
    static function applyMaillist($pvals)
    {
        $db = getDB();
        $user_id = $db->getOne("SELECT maillist_id FROM maillist WHERE email = ?", array($pvals['email']));
        if($user_id) { 
            $exists = 1;
        // insert if not
        } else {
            $exists = 0;
            $key_vals = join(",", array_keys($pvals));
            $qmarks = join(",", array_fill(0, count($pvals), '?'));
            $qvals = array_values($pvals);
            $applydate = date('Y-m-d H:i:s', strtotime('now'));
            $db->query("INSERT INTO maillist ($key_vals) VALUES ($qmarks)", $qvals);
            $user_id = $db->getOne("SELECT LAST_INSERT_ID()");
            $db->commit();
        }
        return array($exists, $user_id);
    }

}
?>
