<?php
/* takes input from the Epicore set password form, authenticates user, and sets password */

require_once "UserInfo.class.php";
$status = 'failed';

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

$superuser = $rvars['superuser'];
if(!$superuser) {
    print json_encode(array('status' => 'failed', 'reason' => 'Unauthorized. Not able to proceed as you are not a superuser'));
    exit;
}

// get form values
$email = $rvars['email'];
$name = $rvars['name'];
$title = $rvars['title'];
$username = $rvars['username'];
$password = $rvars['password'];
$default_location = $rvars['defLoc'];
$default_locname = $rvars['defLocname'];
$createdate = $rvars['defDateCreated'];

// 
if($email && $username && $password) {
    $status = 'success';
    $hmuser_info = UserInfo::createHmUser($email, $name, $title, $username, $password, $default_location, $default_locname,$createdate);
}

print json_encode(array('status' => $status, 'uinfo' => $hmuser_info));

?>
