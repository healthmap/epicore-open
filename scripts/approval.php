<?php
/**
 * User: jeffandre
 * Date: 9/22/15
 *
 * Sets user status and sends email.
 * Returns all users info.
 * Also saves member info and event stats in csv files.
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

// save all members in a csv file
$membersInfo = UserInfo::getMembersInfo($members);
$file = "../data/approval.csv";
saveToCSV($membersInfo, $file);


// save event stats in a csv file
require_once 'EventInfo.class.php';
$filename = '../data/rfistats.csv';
$uid = '91'; // Jeff, uid doesn't matter, any uid will work
$close_stats = EventInfo::getEventStats($uid, 'C');
$open_stats =EventInfo::getEventStats($uid, 'O');
$stats = array_merge($close_stats, $open_stats);
saveToCSV($stats, $filename);


// save data to a csv file using keys as header values
function saveToCSV($data, $filename){

    // save applicants to a csv
    $fp = fopen($filename, 'w');
    //$fp = fopen($filename, 'w');
    if ($fp) {

        // save keys as header values
        fputcsv($fp, array_keys($data[0]));

        // save data
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);
    }else{
        echo 'failed to open';
    }
}
