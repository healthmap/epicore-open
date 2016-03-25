<?php
/**
 * User: jeffandre
 * Date: 9/22/15
 *
 * Sets user status and sends email.
 * Returns all users info.
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

// set member status
require_once 'UserInfo.class.php';
$data = json_decode(file_get_contents("php://input"));
$approve_id = (string)$data->maillist_id;
$approve_status = (string)$data->action;
UserInfo::setUserStatus($approve_id, $approve_status);


// get all members
$members = UserInfo::getMembers();
// return all applicants
print json_encode($members);
