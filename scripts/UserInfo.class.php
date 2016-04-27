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
require_once "AWSMail.class.php";
require_once "send_email.php";
require_once "Geocode.php";

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
        $q = $this->db->query("SELECT event.event_id, event.title, event.disease, event.create_date, event_fetp.send_date, place.name AS location
                                FROM event_fetp, event, place
                                WHERE fetp_id = ? AND event_fetp.event_id = event.event_id AND event.place_id = place.place_id
                                ORDER BY send_date DESC", array($this->id));
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
                $requests[$row['event_id']]['disease'] = $row['disease'];
                $requests[$row['event_id']]['iso_create_date'] = $row['create_date'];
                $requests[$row['event_id']]['create_date'] = date('j-M-Y H:i', strtotime($row['create_date']));
                // make send date an array, because there may be multiple
                $requests[$row['event_id']]['send_dates'][] = date('j-M-Y H:i', strtotime($row['send_date']));
                $requests[$row['event_id']]['iso_send_dates'][] = $row['send_date'];
                $requests[$row['event_id']]['last_send_date'] = $requests[$row['event_id']]['iso_send_dates'][0];
                
                // get the FETPs responses to that event
                $respq = $this->db->query("SELECT response_id, response_date FROM response WHERE responder_id = ? AND event_id = ? ORDER BY response_date DESC", array($this->id, $row['event_id']));
                $response_dates = [];
                while($resprow = $respq->fetchRow()) {
                    array_push($response_dates, date('j-M-Y H:i', strtotime($resprow['response_date'])));
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
                $uinfo = $db->getRow("SELECT fetp_id, pword_hash, lat, lon, countrycode, active, email, status FROM fetp WHERE email = ?", array($email));
                $uinfo['username'] = "Member ".$uinfo['fetp_id'];
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
            $q = $db->query("SELECT fetp_id FROM fetp WHERE active = 'Y' AND lat > ? AND lat < ? AND lon > ? AND lon < ?", $filterval);
        } else {
            $qmarks = join(",", array_fill(0, count($filterval), '?'));
            $q = $db->query("SELECT fetp_id FROM fetp WHERE active = 'Y' AND countrycode in ($qmarks)", $filterval);
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
    REMOVED TEPHINET FOR NOW
    */
    static function getFETPEmails($fetp_ids)
    {
        // pull all fetp_id/emails from our db
        $db = getDB();

        if (is_array($fetp_ids) && !empty($fetp_ids)) {
            $qmarks = join(",", array_fill(0, count($fetp_ids), '?'));
            $email_hash = $db->getAssoc("SELECT fetp_id, email FROM fetp WHERE active='Y' AND email is NOT NULL AND fetp_id in ($qmarks)", FALSE, $fetp_ids);
            return $email_hash;
        } else {
            $email_hash = $db->getAssoc("SELECT fetp_id, email FROM fetp WHERE active='Y' AND email is NOT NULL");
        }
        
        // call to tephinet webservice
        /*require_once "GetURL.class.php";
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
        }*/
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
    static function joinfetp($pvals)
    {
        $db = getDB();
        $user_id = $db->getOne("SELECT fetp_id FROM fetp WHERE email = ?", array($pvals['email']));
        if(!$user_id) { // insert if not
            $key_vals = join(",", array_keys($pvals));
            $qmarks = join(",", array_fill(0, count($pvals), '?'));
            $qvals = array_values($pvals);
            $db->query("INSERT INTO fetp ($key_vals) VALUES ($qmarks)", $qvals);
            $user_id = $db->getOne("SELECT LAST_INSERT_ID()");
            $db->commit();
            return $user_id;
        }
        else
            return false;
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
            $db->query("INSERT IGNORE INTO maillist ($key_vals) VALUES ($qmarks)", $qvals);
            $user_id = $db->getOne("SELECT LAST_INSERT_ID()");
            $db->commit();
            return $user_id;
        }
        else
            return false;

    }

    // returns user id of a new inserted user and existing=0, or id of existing user and existing=1
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
            $db->query("INSERT IGNORE INTO maillist ($key_vals) VALUES ($qmarks)", $qvals);
            $user_id = $db->getOne("SELECT LAST_INSERT_ID()");
            $db->commit();
        }
        return array($exists, $user_id);
    }

    // returns user id of updated user or false if error
    static function updateMaillist($pvals)
    {
        $db = getDB();
        $user_id = $db->getOne("SELECT maillist_id FROM maillist WHERE maillist_id = ?", array($pvals['maillist_id']));

        // get member info to check if location changed
        $member_info = UserInfo::getUserInfo($user_id);

        // update maillist
        $message = '';
        if($user_id) {
            $q = $db->query("UPDATE maillist SET email = ?, firstname = ?, lastname = ?, country = ?, city =?, state=?,
                        university1 =?, major1=?, degree1=?, other_degree1=?, school_country1=?,
                        university2 =?, major2=?, degree2=?, other_degree2=?, school_country2=?,
                        university3 =?, major3=?, degree3=?, other_degree3=?, school_country3=?,
                        training=?, fetp_training=?, other_training=?, other_fetp_training=?, health_exp=?, human_health=?, animal_health=?, env_health=?, health_exp_none=?,
                        job_title=?, organization=?, sector=?, health_org_university=?, health_org_doh=?, health_org_clinic=?, health_org_other=?, health_org_none=?,
                        epicoreworkshop=?, epicoreworkshop_type=?, conference=?, conference_type=?, promoemail=?, promoemail_type=?,
                        othercontact=?, othercontact_type=?, online_course=?, inperson_course=?, info_accurate=?, rfi_agreement=?
                        WHERE maillist_id = ?",
                        array($pvals['email'], $pvals['firstname'], $pvals['lastname'], $pvals['country'], $pvals['city'], $pvals['state'],
                            $pvals['university1'], $pvals['major1'], $pvals['degree1'], $pvals['other_degree1'], $pvals['school_country1'],
                            $pvals['university2'], $pvals['major2'], $pvals['degree2'], $pvals['other_degree2'], $pvals['school_country2'],
                            $pvals['university3'], $pvals['major3'], $pvals['degree3'], $pvals['other_degree3'], $pvals['school_country3'],
                            $pvals['training'], $pvals['fetp_training'], $pvals['other_training'], $pvals['other_fetp_training'], $pvals['health_exp'], $pvals['human_health'], $pvals['animal_health'], $pvals['env_health'], $pvals['health_exp_none'],
                            $pvals['job_title'], $pvals['organization'], $pvals['sector'], $pvals['health_org_university'], $pvals['health_org_doh'], $pvals['health_org_clinic'], $pvals['health_org_other'],$pvals['health_org_none'],
                            $pvals['epicoreworkshop'], $pvals['epicoreworkshop_type'],$pvals['conference'], $pvals['conference_type'], $pvals['promoemail'], $pvals['promoemail_type'],
                            $pvals['othercontact'], $pvals['othercontact_type'], $pvals['online_course'], $pvals['inperson_course'], $pvals['info_accurate'], $pvals['rfi_agreement'], $pvals['maillist_id']));

            // check that result is not an error
            if (PEAR::isError($q)) {
                //die($res->getMessage());
                $status = 'failed';
                $message = 'failed maillist update';
            } else {
                $status = 'success';
                $db->commit();
            }

            // get associated fetp
            $fetp_info = UserInfo::getFETPbyMid($pvals['maillist_id']);

            // udate fetp email
            if ($fetp_info['email'] != $pvals['email'] && $status == 'success'){
                $s = UserInfo::updateFETPemail($fetp_info['fetp_id'], $pvals['email']);
                if($s){
                    $status = 'success';
                }
                else{
                    $status = 'failed';
                    $message = 'failed to update fetp email';
                }
            }
            // update country code and geocode if country or city changed
            if ((($fetp_info['countrycode'] != $pvals['country']) || ($member_info['city'] != $pvals['city'])) && $status == 'success'){
                //update country code
                $country_code = $pvals['country'];
                $fetp_id = $fetp_info['fetp_id'];
                $q = $db->query("update fetp set countrycode='$country_code' where fetp_id='$fetp_id'");

                // check that result is not an error
                if (PEAR::isError($q)) {
                    //die($res->getMessage());
                    $status = 'failed';
                    $message = 'failed to update country/city';
                } else {
                    $status = 'success';
                    $db->commit();
                }
                if ($status == 'success') {
                    // geocode
                    $s = UserInfo::geocodeFETP($pvals['email']);
                    if ($s) {
                        $status = 'success';
                    } else {
                        $status = 'failed';
                        $message = 'failed to geocode member location';
                    }
                }
            }


        } else {
            $status = 'failed';
            $message = 'invalid maillist id';

        }
        return array($status, $message);
    }

    static function deleteMaillist($mid)
    {
        $db = getDB();
        $user_id = $db->getOne("SELECT maillist_id FROM maillist WHERE maillist_id = ?", array($mid));

        // update maillist
        $message = '';
        $status = 'success';
        if ($user_id) {
            $q = $db->query("DELETE FROM maillist WHERE maillist_id = ?", array($user_id));

            // check that result is not an error
            if (PEAR::isError($q)) {
                //die($res->getMessage());
                $status = 'failed';
                $message = 'failed maillist delete';
            } else {
                $db->commit();
            }

            // delete associated fetp
            $fetp_info = UserInfo::getFETPbyMid($user_id);
            if($fetp_info && $status == 'success')
                $q = $db->query("DELETE FROM fetp WHERE maillist_id=?", array($user_id));

            // check that result is not an error
            if (PEAR::isError($q)) {
                //die($res->getMessage());
                $status = 'failed';
                $message = 'failed maillist delete';
            } else {
                $db->commit();
            }

        }
        else{
            $status = 'failed';
            $message = 'user does not exist';
        }
        return array($status, $message);
    }

    // set new password for an fetp
    static function setFETPpassword($fetp_id, $password){
        $pword = UserInfo::createPassword($password);
        if ($fetp_id) {
            $db = getDB();
            $db->query("UPDATE fetp SET pword_hash='$pword[1]' WHERE fetp_id='$fetp_id'");
            $db->commit();
            return true;
        }
        else
            return false;
    }

    static function getFETPid($email){
        $db = getDB();
        $fetp_id = $db->getOne("SELECT fetp_id FROM fetp WHERE email='$email'");
        if ($fetp_id)
            return $fetp_id;
        else
            return false;
    }

    static function getFETP($fetp_id){
        $db = getDB();
        $fetpinfo = $db->getRow("SELECT * FROM fetp WHERE fetp_id='$fetp_id'");
        if ($fetpinfo)
            return $fetpinfo;
        else
            return false;
    }

    static function getFETPbyMid($mid){
        $db = getDB();
        $fetpinfo = $db->getRow("SELECT * FROM fetp WHERE maillist_id='$mid'");
        if ($fetpinfo)
            return $fetpinfo;
        else
            return false;
    }

    static function updateFETPemail($fetp_id,$email){

        if ($fetp_id) {
            $db = getDB();
            $db->query("UPDATE fetp SET email='$email' WHERE fetp_id='$fetp_id'");
            $db->commit();
            return true;
        }
        else
            return false;
    }

    static function getUserInfobyEmail($email){
        $db = getDB();
        $userinfo = $db->getRow("SELECT * FROM maillist WHERE email='$email'");
        if ($userinfo)
            return $userinfo;
        else
            return false;
    }

    static function getUserInfo($uid){
        $db = getDB();
        $userinfo = $db->getRow("SELECT * FROM maillist WHERE maillist_id='$uid'");
        if ($userinfo)
            return $userinfo;
        else
            return false;
    }

    // sets fetp status to pending, approved, pending_preapproved, preapproved or unsubscribed.
    // also sends email for pending, pending_preapproved, and approved status.
    static function setUserStatus($approve_id, $status)
    {
        $db = getDB();
        $userinfo = UserInfo::getUserInfo($approve_id);
        if ($userinfo) {
            $approve_email = $userinfo['email'];
            $approve_name = $userinfo['firstname'];
            $approve_countrycode = $userinfo['country'];
            $approve_id = $userinfo['maillist_id'];

            if ($status == 'pending') {

                // copy maillist to new fetp if it does not exist and set fetp status to 'P'
                $fetpemail = $db->getOne("select email from fetp where email='$approve_email'");
                if (!$fetpemail) {
                    $db->query("INSERT INTO fetp (email, countrycode, active, status, maillist_id)
                        VALUES ('$approve_email', '$approve_countrycode', 'N','P', '$approve_id')");
                    $db->commit();

                    // geocode fetp
                    UserInfo::geocodeFETP($approve_email);
                }
                else{
                    $db->query("update fetp set active='N', status='P' where email='$approve_email'");
                    $db->commit();
                }

                $accept_date = date('Y-m-d H:i:s', strtotime('now'));
                $db->query("update maillist set accept_date='$accept_date', approvestatus='Y' where maillist_id=$approve_id");
                $db->commit();
                $fetp_id = UserInfo::getFETPid($approve_email);
                sendMail($approve_email, $approve_name, "EpiCore Application Decision", $status, $fetp_id);

            }
            else if ($status == 'pending_preapproved') {

                // copy maillist to new fetp if it does not exist and set to pending_preapproved (active = N, status = A)
                $fetpemail = $db->getOne("select email from fetp where email='$approve_email'");
                if (!$fetpemail) {
                    $db->query("INSERT INTO fetp (email, countrycode, active, status, maillist_id)
                        VALUES ('$approve_email', '$approve_countrycode', 'N','A', '$approve_id')");
                    $db->commit();

                    // geocode fetp
                    UserInfo::geocodeFETP($approve_email);
                }
                else{
                    $db->query("update fetp set active='N', status='A' where email='$approve_email'");
                    $db->commit();
                }

                $accept_date = date('Y-m-d H:i:s', strtotime('now'));
                $db->query("update maillist set accept_date='$accept_date', approvestatus='Y' where maillist_id=$approve_id");
                $db->commit();
                $fetp_id = UserInfo::getFETPid($approve_email);
                $status = 'preapproved';
                sendMail($approve_email, $approve_name, "We heartily welcome our new EpiCore Member!", $status, $fetp_id);

            }
            else if (($status == 'approved') ||($status == 'preapproved')) {
                $db->query("update fetp set active='Y', status='A' where email='$approve_email'");
                $db->commit();
                $approve_date = date('Y-m-d H:i:s', strtotime('now'));
                $db->query("update maillist set approve_date='$approve_date', approvestatus='Y' where maillist_id=$approve_id");
                $db->commit();

                if ($status == 'approved') {
                    $fetp_id = UserInfo::getFETPid($approve_email);
                    sendMail($approve_email, $approve_name, "Congratulations!", $status, $fetp_id);
                }
            }
            else if ($status == 'declined') {

                $db->query("update maillist set approvestatus='N' where maillist_id=$approve_id");
                $db->commit();

                $fetp_id = UserInfo::getFETPid($approve_email);
                if ($fetp_id) {
                    $db->query("update fetp set active='N' where email='$approve_email'");
                    $db->commit();
                }

                sendMail($approve_email, $approve_name, "EpiCore Application Decision", $status, $approve_id);

            }
            else if ($status == 'unsubscribed') {
                $db->query("update maillist set approvestatus='Y' where maillist_id=$approve_id");
                $db->commit();
                $db->query("update fetp set active='N' where email='$approve_email'");
                $db->commit();
            }

        }

    }

    // geocodes fetp based on location from maillist, and returns true if success or false if no info found in maillist.
    static function geocodeFETP($email){
        // get maillist info for fetp
        $userinfo = UserInfo::getUserInfobyEmail($email);

        if($userinfo) {
            //geocode
            $address = $userinfo['city'] . ', ' . $userinfo['state'] . ', ' . $userinfo['country'];
            $position = Geocode::getLocationDetail('address', $address);
            $lat = $position[0];
            $lon = $position[1];

            // update lat/lon
            $db = getDB();
            $db->query("update fetp set lat = '$lat', lon = '$lon' where email='$email'");
            $db->commit();

            return true;
        }
        else
            return false;
    }

    static function setCourseType($approve_id, $online, $inperson){
        $db = getDB();
        $db->query("update maillist set online_course='$online', inperson_course='$inperson' where maillist_id='$approve_id'");
        $db->commit();
    }

    // get all members info
    static function getMembers(){
        global $countries;

        // get all applicants and fetps
        $db = getDB();
        $applicants = $db->getAll("select * from maillist");
        $fetps = $db->getAll("select * from fetp");

        // set all applicants status based on applicant approvestatus and fetp active/status fields
        // approvestatus    fetp-active  fetp-status     app-status
        // 'N'              x               x             Denied          Application denied
        //  not N           null            null          Inactive        Applied
        //  Y              'N'              P            Pending         Pending training and needs to set password
        //  Y               'Y'              A            Approved        Finished training and set password
        //  Y               'N'              A            Pre-approved    Finished training and needs to set password

        $n = 0;
        foreach ($applicants as $applicant){
            $applicants[$n]['status'] = 'Inactive';
            if ($applicants[$n]['approvestatus'] == 'N'){
                $applicants[$n]['status'] = 'Denied';
            }
            else {
                foreach ($fetps as $fetp) {
                    $emailmatch = (strcasecmp($fetp['email'], $applicant['email']) == 0);
                    if ($emailmatch && ($fetp['active'] == 'N') && ($fetp['status'] == "A")) {
                        $applicants[$n]['status'] = "Pre-approved";
                    } else if ($emailmatch && ($fetp['active'] == 'N') && ($fetp['status'] == "P")) {
                        $applicants[$n]['status'] = "Pending";
                    } else if ($emailmatch && ($fetp['active'] == 'Y') && ($fetp['status'] == "A")) {
                        $applicants[$n]['status'] = "Approved";
                    }
                    if ($emailmatch) {
                        $applicants[$n]['pword'] = $fetp['pword_hash'] ? 'Yes' : null;
                        $applicants[$n]['member_id'] = $fetp['fetp_id'];;

                    }
                }
            }
            $applicants[$n]['apply_date_iso'] = $applicants[$n]['apply_date'];
            $applicants[$n]['approve_date_iso'] = $applicants[$n]['approve_date'];
            $applicants[$n]['accept_date_iso'] = $applicants[$n]['accept_date'];
            $applicants[$n]['apply_date'] = date('m.d.y', strtotime($applicants[$n]['apply_date']));
            $applicants[$n]['approve_date'] = $applicants[$n]['approve_date'] ?  date('m.d.y', strtotime($applicants[$n]['approve_date'])) : $applicants[$n]['approve_date'];
            $applicants[$n]['accept_date'] = $applicants[$n]['accept_date'] ?  date('m.d.y', strtotime($applicants[$n]['accept_date'])) : $applicants[$n]['accept_date'];
            $applicants[$n]['country_code'] = $applicants[$n]['country'];
            $applicants[$n]['country'] = $countries[$applicants[$n]['country']];
            $n++;
        }

        return $applicants;

    }
    
    static function getMemberStatus($member_id){

        $db = getDB();
        $member = $db->getRow("SELECT approvestatus, active, status FROM maillist m, fetp f WHERE m.maillist_id='$member_id' AND f.maillist_id='$member_id'");

        if ($member['approvestatus'] != 'Y'){
            $mstatus = 'Denied';
        } else if (($member['active'] == 'N') && ($member['status'] == "A")) {
            $mstatus = "Pre-approved";
        } else if (($member['active'] == 'N') && ($member['status'] == "P")) {
            $mstatus = "Pending";
        } else if (($member['active'] == 'Y') && ($member['status'] == "A")) {
            $mstatus = "Approved";
        } else {
            $mstatus = 'Inactive';
        }
        return $mstatus;
    }

    // get all members for csv file
    static function getMembersInfo($members) {
        $std_countries = unserialize(COUNTRIES);


        // save all member info
        $user = array();
        $all_members = array();
        foreach($members as $applicant){
            $user['Application Date'] = $applicant['apply_date'];
            $user['Approval Date'] = $applicant['approve_date'];
            $user['Acceptance Date'] = $applicant['accept_date'];
            //$user['Name'] = $applicant['firstname'] . ' ' . $applicant['lastname'];
            $user['First name'] = $applicant['firstname'];
            $user['Last name'] = $applicant['lastname'];
            $user['email'] = $applicant['email'];
            $user['Member ID'] = $applicant['member_id'];
            $user['City'] = $applicant['city'];
            $user['State/Province'] = $applicant['state'];
            $user['Country'] = $std_countries[$applicant['country_code']];
            $user['Job Title'] = $applicant['job_title'];
            $user['Organization'] = $applicant['organization'];
            //Sector
            $user['Sector'] = '';
            if ($applicant['sector'] == 'G')
                $user['Sector'] = 'Governmental';
            elseif ($applicant['sector'] == 'N')
                $user['Sector'] = 'Non-governmental/Nonprofit';
            elseif ($applicant['sector'] == 'P')
                $user['Sector'] = 'Private';
            // Organization Category
            $user['Organization Category'] = '';
            if ($applicant['health_org_university'])
                $user['Organization Category'] = 'University or any academic or research institution, ';
            if ($applicant['health_org_doh'])
                $user['Organization Category'] .= 'Ministry / Department of Health, ';
            if ($applicant['health_org_clinic'])
                $user['Organization Category'] .= 'Medical clinic, ';
            if ($applicant['health_org_other'])
                $user['Organization Category'] .= 'Other health-related organizations, ';
            if ($applicant['health_org_none'])
                $user['Organization Category'] = 'No category';


            //Degrees
            $user['Degrees'] = '';
            if ($applicant['bachelors_type'])
                $user['Degrees'] = $applicant['bachelors_type'] . ', ';
            if ($applicant['gradstudent_type'])
                $user['Degrees'] .= $applicant['gradstudent_type'] . ', ';
            if ($applicant['masters_type'])
                $user['Degrees'] .= $applicant['masters_type'] . ', ';
            if ($applicant['medical_type'])
                $user['Degrees'] .= $applicant['medical_type'] . ', ';
            if ($applicant['doctorate_type'])
                $user['Degrees'] .= $applicant['doctorate_type'] . ', ';
            if ($applicant['otherdegree_type'])
                $user['Degrees'] .= $applicant['otherdegree_type'] . ', ';

            // Universities
            $user['Universities'] = $applicant['universities'];

            // Universities 1-3
            $user['University1'] = $applicant['university1'];
            $user['Country1'] = $std_countries[$applicant['school_country1']];
            $user['Major1'] = $applicant['major1'];
            $user['Degree1'] = $applicant['degree1'] ? $applicant['degree1']: $applicant['other_degree1'];
            $user['University2'] = $applicant['university2'];
            $user['Country2'] = $std_countries[$applicant['school_country2']];
            $user['Major2'] = $applicant['major2'];
            $user['Degree2'] = $applicant['degree2'] ? $applicant['degree2']: $applicant['other_degree2'];
            $user['University3'] = $applicant['university3'];
            $user['Country3'] = $std_countries[$applicant['school_country3']];
            $user['Major3'] = $applicant['major3'];
            $user['Degree3'] = $applicant['degree3'] ? $applicant['degree3']: $applicant['other_degree3'];

            // Health experience
            $user['Health Experience'] = '';
            if ($applicant['human_health'])
                $user['Health Experience'] = 'Human health, ';
            if ($applicant['animal_health'])
                $user['Health Experience'] .= 'Animal health, ';
            if ($applicant['env_health'])
                $user['Health Experience'] .= 'Environmental, ';
            if ($applicant['health_exp_none'])
                $user['Health Experience'] = 'None';

            // Basic Knowledge
            $user['Basic Knowledge'] = '';
            if ($applicant['clinical_med_adult'])
                $user['Basic Knowledge'] = 'Clinical Medicine – Adult, ';
            if ($applicant['clinical_med_pediatric'])
                $user['Basic Knowledge'] .= 'Clinical Medicine – Pediatric, ';
            if ($applicant['clinical_med_vet'])
                $user['Basic Knowledge'] .= 'Clinical Medicine – Vet, ';
            if ($applicant['research'])
                $user['Basic Knowledge'] .= '>Research, ';
            if ($applicant['microbiology'])
                $user['Basic Knowledge'] .= 'microbiology, ';
            if ($applicant['virology'])
                $user['Basic Knowledge'] .= 'virology, ';
            if ($applicant['parasitology'])
                $user['Basic Knowledge'] .= 'parasitology, ';
            if ($applicant['vaccinology'])
                $user['Basic Knowledge'] .= 'vaccinology, ';
            if ($applicant['epidemiology'])
                $user['Basic Knowledge'] .= 'epidemiology, ';
            if ($applicant['biotechnology'])
                $user['Basic Knowledge'] .= 'biotechnology, ';
            if ($applicant['pharmacy'])
                $user['Basic Knowledge'] .= 'pharmacy, ';
            if ($applicant['publichealth'])
                $user['Basic Knowledge'] .= 'public health, ';
            if ($applicant['disease_surv'])
                $user['Basic Knowledge'] .= 'disease surveillance, ';
            if ($applicant['informatics'])
                $user['Basic Knowledge'] .= 'informatics, ';
            if ($applicant['biostatistics'])
                $user['Basic Knowledge'] .= 'biostatistics, ';
            if ($applicant['other_knowledge'])
                $user['Basic Knowledge'] .= $applicant['other_knowledge_type'];
            // fetp training
            $user['FETP Training (TEPHINET)'] = $applicant['fetp_training'] ? $applicant['fetp_training']: 'none';
            $user['FETP Training (outside TEPHINET)'] = $applicant['other_fetp_training'] ? $applicant['other_fetp_training']: 'none';
            // years experience
            $user['Years of Experience'] = '';
            if ($applicant['health_exp'] == 'A')
                $user['Years of Experience'] = 'none';
            elseif ($applicant['health_exp'] == 'B')
                $user['Years of Experience'] = 'less than 3 years';
            elseif ($applicant['health_exp'] == 'C')
                $user['Years of Experience'] = '3-5 years';
            elseif ($applicant['health_exp'] == 'D')
                $user['Years of Experience'] = '5-10 years';
            elseif ($applicant['health_exp'] == 'E')
                $user['Years of Experience'] = 'More than 10 years';
            //Heard about by
            $user['Heard about Epicore by'] = '';
            if ($applicant['googlesearch'])
                $user['Heard about Epicore by'] = 'Googlesearch, ';
            if ($applicant['conference'])
                $user['Heard about Epicore by'] .= 'Conference: ' . $applicant['conference_type']. ', ';
            if ($applicant['nextgenu'])
                $user['Heard about Epicore by'] .= 'NextGenU, ';
            if ($applicant['epicoreworkshop'])
                $user['Heard about Epicore by'] .= 'Workshop: ' . $applicant['epicoreworkshop_type']. ', ';
            if ($applicant['promoemail'])
                $user['Heard about Epicore by'] .= 'Email: ' . $applicant['promoemail_type']. ', ';
            if ($applicant['othercontact'])
                $user['Heard about Epicore by'] .= 'Other: ' . $applicant['othercontact_type']. ', ';
            // user course type
            $user['Course Type'] = '';
            if ($applicant['online_course'])
                $user['Course Type'] = 'online';
            elseif ($applicant['inperson_course'])
                $user['Course Type'] = 'inperson';
            //user status
            $user['User Status'] = $applicant['status'] == 'Pending' ? 'Accepted' : $applicant['status'] ;
            // user set password
            $user['pword'] = $applicant['pword'];

            // save user in the array
            array_push($all_members, $user);

        }

        return $all_members;
    }
}
?>
