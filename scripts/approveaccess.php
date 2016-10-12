<?php
/**
 * User: jeffandre
 * Date: 10/7/16
 */
require_once 'const.inc.php';

// validate user
$valid_passwords = array (APPROVAL_USERNAME => APPROVAL_PASSWORD);
$valid_users = array_keys($valid_passwords);
$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];
$validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);
if (!$validated) {
    header('WWW-Authenticate: Basic realm="Epicore Admin"');
    header('HTTP/1.0 401 Unauthorized');
    die ("Not authorized");
}