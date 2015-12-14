<?php
/*
 * Sends reminder emails to applicants.
 */

require_once "send_email.php";
require_once 'UserInfo.class.php';
require_once "db.function.php";

$db = getDB();

$data = json_decode(file_get_contents("php://input"));
$action = (string)$data->action;

$maillist = '';
// unsubscribed/pre-approved members with no password
if ($action == 'preapprove_reminder') {
    $maillist = $db->getAll("select fetp_id, f.email,firstname from fetp as f, maillist as m  where f.email=m.email and active='N' and status='A' and pword_hash is null");
    foreach ($maillist as $member) {
        sendMail($member['email'], $member['firstname'], "Reminder | EpiCore misses you! Set your password now!", $action, $member['fetp_id']);
    }
}

// get accepted members with no password
if ($action == 'setpassword_reminder') {
    $maillist = $db->getAll("select fetp_id, f.email,firstname from fetp as f, maillist as m  where f.email=m.email and active='N'
                            and status='P' and pword_hash is null");
    foreach ($maillist as $member) {
        sendMail($member['email'], $member['firstname'], "Reminder | EpiCore misses you! Set your password now!", $action, $member['fetp_id']);
    }
}

// accepted members (with password set) that did not finish training
if ($action == 'training_reminder') {
    $maillist =  $db->getAll("select fetp_id, f.email,firstname from fetp as f,maillist as m where f.email=m.email and active='N'
                            and status='P' and pword_hash is not null and f.email
                            in (select email from maillist where online_course is null)");
    foreach ($maillist as $member) {
        sendMail($member['email'], $member['firstname'], "Reminder | 80% of success is showing up!", $action, $member['fetp_id']);
    }
}

// original launch members that did not apply
if ($action == 'launch_reminder') {
    $maillist = $db->getAll("select maillist_id,email,firstname from contactlist where email not in (select email from maillist)");
    foreach ($maillist as $member) {
        sendMail($member['email'], $member['firstname'], "Reminder | EpiCore Project Official Launch!", $action, $member['maillist_id']);
    }
}

// return all applicants
print json_encode($maillist);
?>
