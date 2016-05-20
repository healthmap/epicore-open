<?php
/**
 * User: jeffandre
 * Date: 9/22/15
 *
 * Returns all members.
 *
 */

// validate user
$valid_passwords = array ("superuser" => "approval");
$valid_users = array_keys($valid_passwords);
$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];
$validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);
if (!$validated) {
    header('WWW-Authenticate: Basic realm="Epicore Admin"');
    header('HTTP/1.0 401 Unauthorized');
    die ("Not authorized");
}
require_once 'UserInfo.class.php';

// connect to memcache
$mem = new Memcache();
$mem->connect("127.0.0.1", 11211) or die ("Could not connect");

// sete memcache key and expire time (in seconds)
$cachekey = "KEY". md5('memberdata');
$expire = 60*60*2; // 2 hours

//use results from cache if available, else use from database
$members = $mem->get($cachekey);
if ($members) { // from cache
    print json_encode($members->members);
} else{ // from db
    $members = UserInfo::getMembers();
    $tmp_object = new stdClass;
    $tmp_object->members = $members;
    $status = $mem->set($cachekey, $tmp_object,false, $expire); // save members in cache
    print json_encode($members);
}


