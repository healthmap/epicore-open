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
require_once (dirname(__FILE__) ."/Model/Role.php");



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
    //As of V3 changes
    //email also inserted into user table
    static function addMod($email, $org_id, $mod_name , $role = 1){
        if ($email && is_numeric($org_id)) {

            $db1 = getDB();
            $hmu_id = $db1->getOne("SELECT hmu_id from hm_hmu WHERE email = ?", array($email));
            
            if ($hmu_id) {
                $db2 = getDB();
                $max_org_id = 50;
               
                if ($org_id >=1 and $org_id <= $max_org_id) {
                    $hid = $db2->getOne("SELECT hmu_id FROM user WHERE hmu_id ='$hmu_id' ");

                    if ($hid != $hmu_id) {
                        // $db2->query("INSERT INTO user (organization_id, hmu_id , roleId) VALUES (?,'$hmu_id' , $role)", array($org_id));
                        $db2->query("INSERT INTO user (organization_id, email, hmu_id , roleId) VALUES (?, ?, '$hmu_id',$role)", array($org_id, $email));
                        $user_id = $db2->getOne("SELECT LAST_INSERT_ID()");
                        $db2->commit();
                        return $user_id;
                    } else {
                        return "moderator is already in the system";
                    }
                }
                else
                    return "org id out of range";

            } else {
                // return "healthmap email address not found";
                //Epicore-V3 changes
                //Ideally we should not be using the hm_hmu table as we have merged hm schema to epicore. Due to time and budget constraints we will continue to use the hm_hmu table as its used in all joins of the EventsAPI
                //and external clients insert tickets using hmu_id.
                //Future development should make sure to remove hm_hmu table and use the 'user' table as single source for 'requester users and for direct login.
                
                //New epicore user added from epicoreUI
                //insert into hm_hmu 
                //insert into user with f.k hm_hmu_id
                $db3 = getDB();
                $affiliation='BCH-Requester';
                $curDatetime = date('Y-m-d H:i:s');
                $confirmed = 1;
                $db3->query("INSERT INTO hm_hmu (email , name, affiliation, confirmed, date_created) VALUES (? , ?, '$affiliation',  '$confirmed', '$curDatetime')", array($email, $mod_name));
                $new_hmu_id = $db3->getOne("SELECT LAST_INSERT_ID()");
                $db3->commit();
                
                if ($new_hmu_id) {

                        $max_org_id = 50;
                        if ($org_id >=1 and $org_id <= $max_org_id) {
                            $hid = $db3->getOne("SELECT hmu_id FROM user WHERE hmu_id ='$new_hmu_id' ");
                            if ($hid != $new_hmu_id) {
                                $db3->query("INSERT INTO user (organization_id, email, hmu_id , roleId) VALUES (?, ?, '$new_hmu_id',$role)", array($org_id, $email));
                                $user_id = $db3->getOne("SELECT LAST_INSERT_ID()");
                                $db3->commit();
                                return $user_id;
                            } else {
                                return "moderator is already in the system";
                            }
                        }
                        else {
                            return "org id out of range";
                        }
                } else {
                    return "Invalid parameters. Cannot add user.";
                }
            }
        } else
            return "invalid parameters";
    }

    static function getMods(){

        // get hmu id's from Epicore Moderators
        $db1 = getDB('');
        $users = $db1->getAll("SELECT hmu_id, organization_id  FROM user");
        $hmuids = array();
        foreach ($users as $user){
            array_push($hmuids, $user['hmu_id']);
        }
        $hmuid_list = implode(",",array_filter($hmuids));

        // get name, email of Epicore mods from healthmap hmu table
        if ($users){
            $db2 = getDB();
            $mods = $db2->getAll("SELECT hmu_id, email, name from hm_hmu WHERE hmu_id in ($hmuid_list)");
            $i=0;
            $six_months_ago = date("Y-m-d H:i:s", strtotime("-6 months"));
            if ($mods) {
                foreach ($mods as $mod){
                    $hmu_id = $mod['hmu_id'];
                    $user_id = $db1->getOne("SELECT user_id FROM user WHERE hmu_id = $hmu_id");
                    $mod['user_id'] = $user_id;
                    $user_org_id = $db1->getOne("SELECT organization_id FROM user WHERE hmu_id = $hmu_id");
                    $mod['org_name'] = $db1->getOne("SELECT name FROM organization WHERE organization_id = ?", array($user_org_id));
                    $mod['rfi_total'] = (int)$db1->getOne("SELECT count(*) from event WHERE requester_id=?", array($user_id));
                    $mod['rfi_6months'] = (int)$db1->getOne("SELECT count(*) from event WHERE requester_id=?  AND create_date > ?", array($user_id, $six_months_ago));
                    $q_scores = $db1->getAll("SELECT score FROM event, event_metrics 
	                                                    WHERE requester_id=? AND event.event_id=event_metrics.event_id
                                                        ORDER BY event.event_id DESC LIMIT 5", array($user_id));
                    $scores = array('','','','','');
                    $n = 0;
                    foreach($q_scores as $score){
                        $scores[$n++] = $score['score'];
                    }
                    $mod['rfi_score1'] = $scores[0];
                    $mod['rfi_score2'] = $scores[1];
                    $mod['rfi_score3'] = $scores[2];
                    $mod['rfi_score4'] = $scores[3];
                    $mod['rfi_score5'] = $scores[4];

                    $mods[$i++] = $mod;
                }
                return $mods;
            }
            else
                return false;
        }
        else
            return false;
    }

    function getFETPRequests($status, $fetp_id = '', $sdate = '')
    {
        $member_id = $fetp_id ? $fetp_id : $this->id;
        $start_date = $sdate ? $sdate: '2000-01-01';
        $q = $this->db->query("SELECT event.event_id, event.title, event.disease, event.create_date, event.event_date, event.requester_id, event_fetp.send_date, place.name AS location
                                FROM event_fetp, event, place
                                WHERE fetp_id = ? AND event_fetp.event_id = event.event_id AND event.place_id = place.place_id AND event.create_date > ?
                                ORDER BY send_date DESC", array($member_id, $start_date));
        $status = $status ? $status : 'O';
        $requests = array();
        while($row = $q->fetchRow()) {
            // responses are recorded by the FETPs user id, not FETP_id
            // get the current status of event - open or closed
            $dbstatus = $this->db->getOne("SELECT status FROM event_notes WHERE event_id = ? ORDER BY action_date DESC LIMIT 1", array($row['event_id']));
            $dbstatus = $dbstatus ? $dbstatus : 'O'; // if no value for status, it's open
            // get event outcome
            $event_outcome = $this->db->getOne("SELECT outcome FROM purpose WHERE event_id = ?", array($row['event_id']));
            // get phe description
            $event_phe_description = $this->db->getOne("SELECT phe_description FROM purpose WHERE event_id = ?", array($row['event_id']));
            // get phe additional info
            $event_phe_additional = $this->db->getOne("SELECT phe_additional FROM purpose WHERE event_id = ?", array($row['event_id']));

            // get source
            $event_source = $this->db->getOne("SELECT source FROM source WHERE event_id = ?", array($row['event_id']));
            // get source details
            $event_source_details = $this->db->getOne("SELECT details FROM source WHERE event_id = ?", array($row['event_id']));

            // get organization id for the event
            $org_id = $this->db->getOne("SELECT organization_id FROM user WHERE user.user_id = ?", array($row['requester_id']));
            // get organization name
            $org_name = $this->db->getOne("SELECT name FROM organization WHERE organization_id = ?", array($org_id));

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
            if($dbstatus == $status) {
                $requests[$row['event_id']]['event_id'] = $row['event_id'];
                $requests[$row['event_id']]['event_id_int'] = (int)$row['event_id'];
                $requests[$row['event_id']]['title'] = $row['title'];
                $requests[$row['event_id']]['location'] = isset($row['location'])  ? $row['location']: '';
                $requests[$row['event_id']]['country'] = isset($row['country']) ? $row['country']: '';
                $requests[$row['event_id']]['disease'] = $row['disease']? $row['disease']: '';
                $requests[$row['event_id']]['iso_create_date'] = $row['create_date'];
                $requests[$row['event_id']]['create_date'] = date('j-M-Y', strtotime($row['create_date']));
                $requests[$row['event_id']]['event_date'] = date('j-M-Y', strtotime($row['event_date']));
                $requests[$row['event_id']]['due_date'] = date('j-M-Y', strtotime("+7 day",strtotime($row['create_date'])));
                // make send date an array, because there may be multiple
                $requests[$row['event_id']]['send_dates'][] = date('j-M-Y H:i', strtotime($row['send_date']));
                $requests[$row['event_id']]['iso_send_dates'][] = $row['send_date'];
                $requests[$row['event_id']]['last_send_date'] = $requests[$row['event_id']]['iso_send_dates'][0];
                
                // get the FETPs responses to that event
                $respq = $this->db->query("SELECT response_id, response_date, useful, response_permission FROM response WHERE responder_id = ? AND event_id = ? ORDER BY response_date DESC", array($member_id, $row['event_id']));
                $response_dates = [];
                $response_use = [];
                $response_permission = [];
                while($resprow = $respq->fetchRow()) {
                    array_push($response_dates, date('j-M-Y H:i', strtotime($resprow['response_date'])));
                    array_push($response_use, $resprow['useful']);
                    array_push($response_permission, $resprow['response_permission']);
                }
                $requests[$row['event_id']]['response_dates'] = array_unique($response_dates);
                $requests[$row['event_id']]['response_use'] = $response_use;
                $requests[$row['event_id']]['outcome'] = $event_outcome;
                $requests[$row['event_id']]['phe_description'] = $event_phe_description;
                $requests[$row['event_id']]['phe_additional'] = $event_phe_additional;
                $requests[$row['event_id']]['source'] = $event_source;
                $requests[$row['event_id']]['source_details'] = $event_source_details;

                $requests[$row['event_id']]['organization_name'] = $org_name;

                if (in_array('1',$response_permission) || in_array('2',$response_permission) || in_array('3',$response_permission)) {
                    $requests[$row['event_id']]['activity'] = '3';  // Contribution
                } elseif (in_array('4',$response_permission)) {
                    $requests[$row['event_id']]['activity'] = '2';  // Active Search
                } elseif (in_array('0',$response_permission)) {
                    $requests[$row['event_id']]['activity'] = '1';  // No Answer
                } else {
                    $requests[$row['event_id']]['activity'] = '1';
                }
                $requests[$row['event_id']]['response_permission'] = $response_permission;
            }
        }
        return $requests;
    }

    static function createPassword($password = '') {
        $password = $password ? $password : substr(md5(rand().rand()), 0, 8);
        $pword_hash = create_hash($password);
        return array($password, $pword_hash);
    }

    static function authenticateUser($dbdata , $passwordValidIsRequired = true)
    {
        $email = strip_tags($dbdata['email']);
        // first try the HealthMap database
        $db = getDB();
        
        if($passwordValidIsRequired) {
            $user = $db->getRow("SELECT hmu_id, username, email, pword_hash from hm_hmu WHERE (username = ? OR email = ?) AND confirmed = 1", array($email, $email));
      
            if(is_a($user, 'DB_Error')) {
                print_r($user);
                die('user error!');
            }
            $resp = validate_password($dbdata['password'], $user['pword_hash']);
            if ($resp) {
                $uinfo = $db->getRow("SELECT user.user_id, user.hmu_id, user.organization_id, organization.name AS orgname , role.id as roleId , role.name as roleName FROM user 
                    INNER JOIN role ON role.id = user.roleId
                    LEFT JOIN epicore.organization ON user.organization_id = organization.organization_id WHERE hmu_id = ?", array($user['hmu_id']));
                if (is_a($uinfo, 'DB_Error')) {
                    print_r($uinfo);
                    die('uinfo error!');
                }
                $uinfo['username'] = $user['username'];
                $uinfo['email'] = $user['email'];
                return $uinfo;
            } else {
                // first try the MOD user table.  If none, try the FETP user table.
                $uinfo = $db->getRow("SELECT user.*, organization.name AS orgname , role.id as roleId , role.name as roleName FROM user INNER JOIN role ON role.id = user.roleId LEFT JOIN organization ON user.organization_id = organization.organization_id WHERE email = ?", array($email));
     
                if (!$uinfo['user_id']) {

                    $uinfo = $db->getRow("SELECT fetp_id, pword_hash, lat, lon, countrycode, active, email, status, locations ,  role.id as roleId , role.name as roleName FROM fetp 
                        INNER JOIN role ON role.id = fetp.roleId WHERE email = ?", array($email));
                    $uinfo['username'] = "Member " . $uinfo['fetp_id'];
                }
                if ($uinfo['user_id'] || $uinfo['fetp_id']) {

                    $resp = validate_password($dbdata['password'], $uinfo['pword_hash']);
                    if ($resp) {
                        
                        unset($uinfo['pword_hash']);
                        return $uinfo;
                    } else {
                        return $uinfo; // new signed up user on cognito. This is req/resp whose pwd in cognito
                    }
                }
                return 0;
            }
        }

        $user = $db->getRow("SELECT hmu_id, username, email, pword_hash from hm_hmu WHERE (username = ? OR email = ?) AND confirmed = 1", array($email, $email));
        if(is_null($user))
        {
            $user = $db->getRow("SELECT email , fetp_id , role.id as roleId , role.name as roleName , pword_hash , lat , lon , countrycode , active ,
                    status , locations
                    from fetp 
                    INNER JOIN role ON role.id = fetp.roleId
                    WHERE email = ?", array($dbdata['email']));
            if (is_a($user, 'DB_Error')) {
                print_r($user);
                die('uinfo error!');
            }
        }
        else
        {
            $user = $db->getRow("SELECT user.user_id, user.email, user.hmu_id, user.organization_id, organization.name AS orgname , role.id as roleId , role.name as roleName , hm_hmu.username as username , hm_hmu.email as hm_email
                FROM user
                INNER JOIN role ON role.id = user.roleId
                LEFT JOIN hm_hmu ON hm_hmu.hmu_id = user.hmu_id 
                LEFT JOIN organization ON user.organization_id = organization.organization_id WHERE hm_hmu.hmu_id = ?", array($user['hmu_id']));
        }
        if(isset($user['username']))
        {
            $uinfo['username'] = "Member " . $user['username'];
        }
        if(isset($user['hmu_id'])){
            $uinfo['username'] = $user['username'];
            $uinfo['hmu_id'] = $user['hmu_id'];
        }
        if(isset($user['fetp_id'])){
            $uinfo['username'] = "Member " . $user['fetp_id'];
            $uinfo['fetp_id'] = $user['fetp_id'];
        }

        if(isset($user['lat']) && !empty($user['lat']))
        {
            $uinfo['lat'] = $user['lat'];
        }
        if(isset($user['lon']) && !empty($user['lon']))
        {
            $uinfo['lon'] = $user['lon'];
        }
        if(isset($user['countrycode']) && !empty($user['countrycode']))
        {
            $uinfo['countrycode'] = $user['countrycode'];
        }
        if(isset($user['active']) && !empty($user['active']))
        {
            $uinfo['active'] = $user['active'];
        }
        if(isset($user['locations']) && !empty($user['locations']))
        {
            $uinfo['locations'] = $user['locations'];
        }
        if(isset($user['roleId']) && !empty($user['roleId'])){
            $uinfo['roleId'] = $user['roleId'];
        }
        if(isset($user['roleName']) && !empty($user['roleName'])){
            $uinfo['roleName'] = $user['roleName'];
        }
        if(isset($user['orgname']) && !empty($user['orgname'])){
            $uinfo['orgname'] = $user['orgname'];
        }
        if(isset($user['organization_id']) && !empty($user['organization_id'])) {
            $uinfo['organization_id'] = $user['organization_id'];
        }
        if(isset($user['user_id']) && !empty($user['user_id'])){
            $uinfo['user_id'] = $user['user_id'];
        }

        $uinfo['email'] = $user['email'];
        if($uinfo['email'] === null && isset($user['hm_email']) && !empty($user['hm_email'])){
            $uinfo['email'] = $user['hm_email'];
        }

        $uinfo['superuser'] = false;

        if(is_null( $uinfo['email'] ))
        {
            return false;
        }
        return $uinfo;
    }

    static function authenticateMod($ticket_id) 
    {
        $db = getDB();
 
        $user = null;
        $epicore_info = null;
        $ticket_info = $db->getRow("SELECT * FROM hm_ticket WHERE val = ? AND exp > now()", array($ticket_id));
    
        $hmu_id = $ticket_info['hmu_id'];
        $user_id = $ticket_info['user_id'];

        if(!$hmu_id && !$user_id) {
            return 0;
        }

        //if hmu_id found
        if(!$user_id) {
            $user = $db->getRow("SELECT hmu_id, username, email from hm_hmu WHERE hmu_id = ?", array($hmu_id));
            $epicore_info = $db->getRow("SELECT user.*, organization.name AS orgname, role.id as roleId , role.name as roleName FROM user 
            INNER JOIN role ON role.id = user.roleId
            LEFT JOIN organization ON user.organization_id = organization.organization_id WHERE user.hmu_id = ?", array($hmu_id));
        } else {
            //new epicore users have user_id associated with ticket
            $user = $db->getRow("SELECT user_id, email, hmu_id from user WHERE user_id = ?", array($user_id));
            $epicore_info = $db->getRow("SELECT user.*, organization.name AS orgname, role.id as roleId , role.name as roleName FROM user 
            INNER JOIN role ON role.id = user.roleId
            LEFT JOIN organization ON user.organization_id = organization.organization_id WHERE user.user_id = ?", array($user_id));
            
            //Should not be used
            //Scenario-not old req coming from promed-with user-id in hm_ticket - just a testing scenation
            
        }
        $user['user_id'] = $epicore_info['user_id'];
        $user['organization_id'] = $epicore_info['organization_id'];
        $user['orgname'] = $epicore_info['orgname'];
        $user['ticket_id'] = $ticket_id;
        if(isset($epicore_info['roleId']) && !empty($epicore_info['roleId'])){
            $user['roleId'] = $epicore_info['roleId'];
        }
        if(isset($epicore_info['roleName']) && !empty($epicore_info['roleName'])){
            $user['roleName'] = $epicore_info['roleName'];
        }
        // print_r($user);
        return $user;
    }

    static function authenticateFetpByEmail(string $email)
    {
        $db = getDB();
        return $db->getRow("SELECT fetp_id FROM fetp WHERE email = ?", array($email));
    }

    static function authenticateUserByEmail(string $email) //requesters
    {
        $db = getDB();
        return $db->getOne("select user.user_id from hm_hmu hm inner join user ON hm.hmu_id = user.hmu_id where hm.email = ?", array($email));
    }

    static function authenticateFetp($ticket_id)
    {
        $db = getDB();
        return $db->getRow("SELECT fetp_id FROM epicore.ticket WHERE val = ? AND exp > now()", array($ticket_id));
    }

    /* filtertype is countries or radius; filterval is either array of country codes or array of bounding box values */
    static function getFETPsInLocation($filtertype, $filterval)
    {
        /* WEBSERVICE ------------
        for tephinet, we could call the function getFETPEligible 
        and pass POST param countrycode OR boundingbox (lat1,lat2,lon1,lon2)
        ------------------- END WEBSERVICE */

        $db = getDB();
        $location_send_ids = array();
        $send_ids = array();
        // get fetps within radius (box) or countries
        if($filtertype == "radius") {
            $q = $db->query("SELECT fetp_id FROM fetp WHERE active = 'Y' AND lat > ? AND lat < ? AND lon > ? AND lon < ?", $filterval);
        } else {
            $qmarks = join(",", array_fill(0, count($filterval), '?'));
            $q = $db->query("SELECT fetp_id FROM fetp WHERE active = 'Y' AND countrycode in ($qmarks)", $filterval);
        }
        while($row = $q->fetchRow()) {
            $send_ids[] = $row['fetp_id'];
        }
        // get other fetp locations within radius (box) or countries
        if($filtertype == "radius") {
            $q = $db->query("SELECT l.fetp_id FROM member_location l, fetp f WHERE l.fetp_id = f.fetp_id AND f.active = 'Y' AND l.lat > ? AND l.lat < ? AND l.lon > ? AND l.lon < ?", $filterval);
        } else {
            $qmarks = join(",", array_fill(0, count($filterval), '?'));
            $q = $db->query("SELECT l.fetp_id FROM member_location l, fetp f WHERE l.fetp_id = f.fetp_id AND f.active = 'Y' AND l.countrycode in ($qmarks)", $filterval);
        }
        while($row = $q->fetchRow()) {
            $location_send_ids[] = $row['fetp_id'];
        }
        $send_ids = array_unique($send_ids);
        $location_send_ids = array_unique($location_send_ids);
        // if we ever apply filter on training, this will be what we send back
        //$userlist = array('sending' => count($send_ids), 'all' => count($unique_users), 'ddd' => count($ddd_trained), 'graduate' => count($training_status['Graduate']), 'na' => count($training_status['N/A']), 'trainee' => count($training_status['Trainee']), 'unspecified' => count($training_status['unspecified']));
        $userlist = array('sending' => (count($send_ids) + count($location_send_ids)));
        $unique_send_ids = array_unique(array_merge($send_ids, $location_send_ids));
        $unique_userlist = array(('sending') => (count($unique_send_ids)));
        return array($userlist, $unique_send_ids, $unique_userlist);
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


    // Creating a Requester login - hm database hmu table
    // See readMe..for postman to create users
    static function createHmUser($email, $name, $title, $username, $password, $default_location, $default_locname,$createdate) {
        
        //defaults
        $email_active = 1;
        $affiliation = 'BCH-Requester';
        $confirmed = 1;
        $priv_level = 1;
        $date_created = date('Y-m-d H:i:s');
        $default_country = 106; 
        $default_radius = 50;
        $html_email = 1;
        
        $db = getDB('hm'); //do not remove hm here...default will go to epicore...this is a standalone script
        $hmuUser = $db->getRow("SELECT * FROM hm_hmu WHERE email='$email'");
        $messageResp = '';
        if ($hmuUser) {
          $messageResp = 'User exists.';
        } else {
            
            //echo 'create one hmuUser';
            if($password) {
                //STEP 1: Create user in hmu table in hm database
                $pword_hash = create_hash($password);
                    $res = $db->query("INSERT INTO hm_hmu (email, email_active, name, title, affiliation, username, pword_hash, confirmed, priv_level, date_created, default_location, default_locname, default_country,  default_radius, html_email) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                array($email,$email_active,$name,$title,$affiliation,$username,$pword_hash,$confirmed,$priv_level,$date_created,$default_location,$default_locname,$default_country,$default_radius, $html_email));
                        $db->commit();

                    // check result is not an error
                    if (PEAR::isError($res)) {
                        die($res->getMessage());
                        $status = 'failed to insert event';
                        $messageResp = 'Unable to create user.';
                    } else {
                        
                        $hmu_id = $db->getOne("SELECT LAST_INSERT_ID()");
                        $db->commit();
                        $messageResp = 'Created hmu user successfully with id:' . $hmu_id . '. Add this user through UI as a requester using email address:' .$email;
                        
                        return $messageResp;
                    }

            } else {
                return 'No password. Check request';
            }

        } //end if

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
        $userinfo = $db->getRow("SELECT * FROM epicore.maillist WHERE maillist_id='$uid'");
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
                    $db->query("INSERT INTO fetp (email, countrycode, active, status, maillist_id , roleId)
                        VALUES ('$approve_email', '$approve_countrycode', 'N','P', '$approve_id' ," .Role::responder .")");
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
                    $db->query("INSERT INTO fetp (email, countrycode, active, status, maillist_id , roleId)
                        VALUES ('$approve_email', '$approve_countrycode', 'N','A', '$approve_id' ," .Role::responder .")");
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
                //Cognito should send this email
                sendMail($approve_email, $approve_name, "We heartily welcome our new EpiCore Member!", $status, $fetp_id);

            }
            else if (($status == 'approved') || ($status == 'preapproved')) {
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
    static function getMembers($sdate, $edate){
        
        global $countries;
        
        //Improved query performance
        $start_date = $sdate ? $sdate: V1START_DATE; // V2START_DATE; 
        $end_date = $edate ? $edate: date("Y-m-d H:i:s");

        //modify date to datetime
        $start_time = '00:00:00';
        $end_time = '23:59:59';
        $sdatetimeStr = $start_date . ' ' . $start_time;
        $edatetimeStr = $end_date . ' ' . $end_time;

        $db = getDB();
        
        // get all applicants and fetps for date range
        $result = $db->query("SELECT maillist.*, fetp.fetp_id, fetp.active, fetp.status, fetp.pword_hash, fetp.locations
        FROM epicore.maillist maillist
        LEFT JOIN epicore.fetp fetp ON maillist.maillist_id = fetp.maillist_id
        WHERE maillist.apply_date >= ? AND maillist.apply_date <= ?
        order by maillist.apply_date DESC;", array($sdatetimeStr, $edatetimeStr));

        // set all applicants status based on applicant approvestatus and fetp active/status fields
        // approvestatus    fetp-active  fetp-status     app-status
        // 'N'              x               x             Denied          Application denied
        //  not N           null            null          Inactive        Applied
        //  Y              'N'              P            Pending         Pending training and needs to set password
        //  Y               'Y'              A            Approved        Finished training and set password
        //  Y               'N'              A            Pre-approved    Finished training and needs to set password
        
        $applicants = [];
        while($row = $result->fetchRow()) {       
           
            $applicant_row = $row;
            // echo'******ROW:';
            // print_r($row['maillist_id']);
            // echo'******ROW:';

            if ($row['approvestatus'] == 'N'){
                $row['status'] = 'Denied';
            }
            else {
                //check on fetp-flags
                if (($row['active'] == 'N') && ($row['status'] == "A")) {
                    $row['status'] = "Pre-approved";
                } else if (($row['active'] == 'N') && ($row['status'] == "P")) {
                    $row['status'] = "Pending";
                } else if (($row['active'] == 'Y') && ($row['status'] == "A")) {
                    $row['status'] = "Approved";
                } else {
                    //default
                    $row['status'] = 'Inactive';
                }
            }
            $applicant_row['status'] = $row['status'];
            $applicant_row['maillist_id'] = $row['maillist_id'];
            $applicant_row['pword'] = $row['pword_hash'] ? 'Yes' : null;
            $applicant_row['member_id'] = $row['fetp_id'];
            $applicant_row['locations'] = $row['locations'];
            $applicant_row['apply_date_iso'] = $row['apply_date'];
            $applicant_row['approve_date_iso'] = $row['approve_date'];
            $applicant_row['accept_date_iso'] = $row['accept_date'];
            $applicant_row['apply_date'] = date('d-M-Y', strtotime($row['apply_date']));
            $applicant_row['approve_date'] = $row['approve_date'] ?  date('d-M-Y', strtotime($row['approve_date'])) : $row['approve_date'];
            $applicant_row['accept_date'] = $row['accept_date'] ?  date('d-M-Y', strtotime($row['accept_date'])) : $row['accept_date'];
            $applicant_row['country_code'] = $row['country'];
            $applicant_row['country'] = $countries[$row['country']];
            
            //remove FETP data extrafields
            unset($applicant_row['fetp_id']);
            unset($applicant_row['locations']);
            unset($applicant_row['active']);
            unset($applicant_row['pword_hash']);

            $applicants[] = $applicant_row;
        }

        /*********************************************orig - do not remove*

        // get all applicants and fetps
        $db = getDB();
        // orig
        $applicants = $db->getAll("select * from epicore.maillist");
        $fetps = $db->getAll("select * from epicore.fetp");

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
                        $applicants[$n]['member_id'] = $fetp['fetp_id'];
                        $applicants[$n]['locations'] = $fetp['locations'];
                    }
                }
            }
            $applicants[$n]['apply_date_iso'] = $applicants[$n]['apply_date'];
            $applicants[$n]['approve_date_iso'] = $applicants[$n]['approve_date'];
            $applicants[$n]['accept_date_iso'] = $applicants[$n]['accept_date'];
            $applicants[$n]['apply_date'] = date('d-M-Y', strtotime($applicants[$n]['apply_date']));
            $applicants[$n]['approve_date'] = $applicants[$n]['approve_date'] ?  date('d-M-Y', strtotime($applicants[$n]['approve_date'])) : $applicants[$n]['approve_date'];
            $applicants[$n]['accept_date'] = $applicants[$n]['accept_date'] ?  date('d-M-Y', strtotime($applicants[$n]['accept_date'])) : $applicants[$n]['accept_date'];
            $applicants[$n]['country_code'] = $applicants[$n]['country'];
            $applicants[$n]['country'] = $countries[$applicants[$n]['country']];
            $n++;
        }
        *********************************************orig */



        return $applicants;

    }

    static function getMaillistDetails($id){
        $db = getDB();
        return $db->getRow("SELECT email FROM maillist WHERE maillist_id='$id'");
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

       // var_dump($mstatus);die();

        return $mstatus;
    }

    // returns location id if location is inserted and false if location already exists.
    static function addLocation($pvals)
    {
        $db = getDB();
        // check if locations exists
        $location_id = $db->getOne("SELECT location_id FROM member_location WHERE fetp_id = ? AND city = ? AND state = ? AND countrycode = ?", 
            array($pvals['fetp_id'], $pvals['city'], $pvals['state'], $pvals['countrycode']));
        if(!$location_id) { // insert if not
            //geocode location
            //Not using this API anymore - as of 2021-02-22
            // $address = $pvals['city'] . ', ' . $pvals['state'] . ', ' . $pvals['country'];
            // $position = Geocode::getLocationDetail('address', $address);
            // $pvals['lat'] = $position[0];
            // $pvals['lon'] = $position[1];

            //insert location
            $key_vals = join(",", array_keys($pvals));
            $qmarks = join(",", array_fill(0, count($pvals), '?'));
            $qvals = array_values($pvals);
            $db->query("INSERT IGNORE INTO member_location ($key_vals) VALUES ($qmarks)", $qvals);
            $location_id = $db->getOne("SELECT LAST_INSERT_ID()");
            $db->commit();
            return $location_id;
        }
        else
            return false;
       
    }

    // add/update mobile device
    // returns new or updated mobile_id if added/updated, or false if not
    static function addMobileDevice($pvals){

        if ($pvals['fetp_id'] && $pvals['reg_id']) {
            $db = getDB();
            $mobile_info = $db->getRow("SELECT mobile_id, reg_id FROM mobile where fetp_id = ?", array($pvals['fetp_id']));
            $pvals['reg_date'] = date('Y-m-d H:i:s', strtotime('now'));
            if (!$mobile_info['mobile_id']) { // add mobile device if none

                //insert mobile device
                $key_vals = join(",", array_keys($pvals));
                $qmarks = join(",", array_fill(0, count($pvals), '?'));
                $qvals = array_values($pvals);
                $db->query("INSERT INTO mobile ($key_vals) VALUES ($qmarks)", $qvals);
                $mobile_id = $db->getOne("SELECT LAST_INSERT_ID()");
                $db->commit();
                return $mobile_id;

            } else if (strcmp($mobile_info['reg_id'], $pvals['reg_id']) != 0) { // update reg id if different

                $db->query("UPDATE mobile SET reg_id=?, reg_date = ? WHERE fetp_id = ?", array($pvals['reg_id'], $pvals['reg_date'], $pvals['fetp_id']));
                $db->commit();
                return $mobile_info['mobile_id'];
            } else { // not added or updated
                return false;
            }
        }else // no fetp_id or reg_id
            return false;

    }

    // get member mobile id
    static  function getMemberMobileId($fetp_id){
        $db = getDB();
        $mobile_id = $db->getOne("SELECT reg_id FROM mobile WHERE fetp_id = ?", array($fetp_id));
        return $mobile_id;
    }

    // get member mobile info
    static  function getMemberMobileInfo($fetp_id){
        $db = getDB();
        $mobileinfo = $db->getRow("SELECT * FROM mobile WHERE fetp_id = ?", array($fetp_id));
        if ($mobileinfo) {
            return $mobileinfo;
        } else {
            return false;
        }
    }

    static function getLocations($fetp_id = '') {
        $db = getDB();
        $locations = $db->getAll("SELECT * FROM member_location WHERE fetp_id = ?", array($fetp_id));
        if ($locations){
            return $locations;
        } else {
            return false;
        }
    }

    static function getAllLocations() {
        $db = getDB();
        $locations = $db->getAll("SELECT * FROM member_location ");
        if ($locations){
            return $locations;
        } else {
            return false;
        }
    }

    static function deleteLocation($lid)
    {
        $db = getDB();
        $location_id = $db->getOne("SELECT location_id FROM member_location WHERE location_id = ?", array($lid));

        // delete
        $message = '';
        $status = '';
        if ($location_id) {
            $q = $db->query("DELETE FROM member_location WHERE location_id = ?", array($lid));

            // check that result is not an error
            if (PEAR::isError($q)) {
                //die($res->getMessage());
                $status = 'failed';
                $message = 'failed to delete location';
            } else {
                $message = 'Deleted location';
                $status = 'success';
                $db->commit();
            }
        }
        else{
            $status = 'failed';
            $message = 'location does not exist';
        }
        return array('status' => $status, 'message' =>$message);
    }

    static  function setLocationStatus($member_id, $action){
        $db = getDB();
        $mid = $db->getOne("SELECT maillist_id FROM fetp WEHRE maillist_id = ?", array($member_id));

        if ($mid) {
            if ($action == 'enable') {
                $db->query("update fetp set locations='1' where maillist_id = ?", array($member_id));
                $db->commit();
                return $member_id;
            } elseif ($action == 'disable') {
                $db->query("update fetp set locations='0' where maillist_id = ?", array($member_id));
                $db->commit();
                return $member_id;
            } else {
                return false;   // unrecognized action
            }
        } else {
            return false; // member does not exist
        }
    }

    // get all members for csv file
    function getMembersInfo($members) {
        $std_countries = unserialize(COUNTRIES);
        $std_who_map = unserialize(WHOMAP);


        // save all member info
        $user = array();
        $all_members = array();
        // echo '----------------Member count: '.  count($members) . '---------------------'. "\n";
        $counter =0;
        foreach($members as $applicant) {

            $user['Application Date'] = $applicant['apply_date'];
            $user['Approval Date'] = $applicant['approve_date'];
            $user['Acceptance Date'] = $applicant['accept_date'];
            $user['First name'] = $applicant['firstname'];
            $user['Last name'] = $applicant['lastname'];
            $user['email'] = $applicant['email'];
            $user['Member ID'] = $applicant['member_id'];
            $user['City'] = $applicant['city'];
            $user['State/Province'] = $applicant['state'];
            $user['Country'] = $std_countries[$applicant['country_code']];
            $user['Country Code'] = $applicant['country_code'];
            $user['WHO Region'] = $std_who_map[$applicant['country_code']];
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

            $applicant['other_degree1'] = $applicant['other_degree1']? $applicant['other_degree1']: '';
            $applicant['other_degree2'] = $applicant['other_degree2']? $applicant['other_degree2']: '';
            $applicant['other_degree3'] = $applicant['other_degree3'] ? $applicant['other_degree3']: '';
           

            
            $user['University1'] = $applicant['university1'] ? $applicant['university1']: '';
            $user['Country1'] = $applicant['school_country1'] ? $std_countries[$applicant['school_country1']]: '';
            $user['Major1'] = $applicant['major1'] ? $applicant['major1']: '';
            $user['Degree1'] = $applicant['degree1'] ? $applicant['degree1']: $applicant['other_degree1'];
            $user['University2'] = $applicant['university2'] ? $applicant['university2']: '';
            $user['Country2'] = $applicant['school_country2']? $std_countries[$applicant['school_country2']] : '';
            $user['Major2'] = $applicant['major2'] ? $applicant['major2']: '';
            $user['Degree2'] = $applicant['degree2'] ? $applicant['degree2']: $applicant['other_degree2'];
            $user['University3'] = $applicant['university3'] ? $applicant['university3']: '';
            $user['Country3'] = $applicant['school_country3'] ? $std_countries[$applicant['school_country3']] : '';
            $user['Major3'] = $applicant['major3'] ? $applicant['major3']: '';
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

            if($applicant['member_id']) {
                // get open and closed rfi's
                $open_rfis = $this->getFETPRequests('O', $applicant['member_id']);
                $closed_rfis = $this->getFETPRequests('C', $applicant['member_id']);
            } 
            // else {
            //     echo '----------------ApplicantID missing: '. $applicant['maillist_id'] . '---------------------'. "\n";
            // }
            // count rfi stats
            $user['# Responses'] = 0;
            $user['no contribution'] = 0;
            $user['not helpful'] = 0;
            $user['helpful-no promed'] = 0;
            $user['helpful-promed'] = 0;
            $user['# RFIs'] = 0;

           
            if($open_rfis && $closed_rfis) {
                $user['# RFIs'] = count($open_rfis) + count($closed_rfis);
            }

            if($open_rfis) {
                foreach ($open_rfis as $orfi) {
                    if ($orfi['response_dates']) {
                        $user['# Responses'] += count($orfi['response_dates']);
                        $user['no contribution'] += count(array_keys($orfi['response_use'], null));
                        $user['not helpful'] += count(array_keys($orfi['response_use'], '0'));
                        $user['helpful-no promed'] += count(array_keys($orfi['response_use'], '1'));
                        $user['helpful-promed'] += count(array_keys($orfi['response_use'], '2'));
                    }
                }
            }
            if($closed_rfis) {
                foreach ($closed_rfis as $crfi) {
                    if ($crfi['response_dates'])
                        $user['# Responses'] += count($crfi['response_dates']);
                    $user['no contribution'] += count(array_keys($crfi['response_use'], null));
                    $user['not helpful'] += count(array_keys($crfi['response_use'], '0'));
                    $user['helpful-no promed'] += count(array_keys($crfi['response_use'], '1'));
                    $user['helpful-promed'] += count(array_keys($crfi['response_use'], '2'));
                }
            }
            
            // echo '----------------Processing member '. $counter . ' completed-----------------------'. "\n";
            // save user in the array
            array_push($all_members, $user);
            $counter++;

        }

        return $all_members;
    }
}
?>