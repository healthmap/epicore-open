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
$members = UserInfo::getMembers();

// return all members
print json_encode($members);
