<?php
/**
 * User: jeffandre
 * Date: 9/22/15
 *
 * Returns all members.
 *
 */
require_once 'const.inc.php';
<<<<<<< HEAD
require_once 'approveaccess.php';
require_once 'UserInfo.class.php';

=======
// require_once 'approveaccess.php';
require_once 'UserInfo.class.php';

$formvars = json_decode(file_get_contents("php://input"));

$startDt = strip_tags($formvars->startDate);
$endDt = strip_tags($formvars->endDate);
    
>>>>>>> epicore-ng/main
// connect to memcache
//$mem = new Memcache();
//$mem->connect("127.0.0.1", 11211) or die ("Could not connect");

// set memcache key and expire time (in seconds)
//$cachekey = "KEY". md5('memberdata');
//$expire = 60*60*1; // 1 hour

//use results from cache if available, else use from database
//$members = $mem->get($cachekey);  // disable cache for now
//if ($members) { // from cache
//    print json_encode($members->members);
//} else{ // from db
<<<<<<< HEAD
    $members = UserInfo::getMembers();
=======
    $members = UserInfo::getMembers($startDt, $endDt);
>>>>>>> epicore-ng/main
    $tmp_object = new stdClass;
    $tmp_object->members = $members;
    //$status = $mem->set($cachekey, $tmp_object,false, $expire); // save members in cache
    print json_encode($members);
//}


