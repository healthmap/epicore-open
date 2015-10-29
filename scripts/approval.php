<?php
/**
 * User: jeffandre
 * Date: 9/22/15
 *
 * Sets user status and sends email.
 * Returns all users info.
 *
 */
require_once "const.inc.php";
require_once "AWSMail.class.php";
require_once 'db.function.php';
require_once 'UserInfo.class.php';
require_once "send_email.php";

$db = getDB();

// get applicant and set status
$data = json_decode(file_get_contents("php://input"));
$approve_id = (string)$data->maillist_id;
$approve_status = (string)$data->action;
UserInfo::setUserStatus($approve_id, $approve_status);


// get all applicants and fetps
$applicants = $db->getAll("select * from maillist");
$fetps = $db->getAll("select * from fetp");

// set all applicants status based on applicant approvestatus and fetp active/status fields
// approvestatus    fetp-active  fetp-status     app-status
// 'N'              x               x             Declined
//  not N           null            null          Inactive
//  not N           'N'              P            Pending         Pending training
//  not N           'Y'              A            Approved        Finished training
//  not N           'N'              A            Unsubscribed    Unsubscribed
$applicant_status = [];
$n = 0;
foreach ($applicants as $applicant){
    $applicants[$n]['status'] = 'Inactive';
    if ($applicants[$n]['approvestatus'] == 'N'){
        $applicants[$n]['status'] = 'Declined';
    }
    else {
        foreach ($fetps as $fetp) {
            if (($fetp['email'] == $applicant['email']) && ($fetp['active'] == 'N') && ($fetp['status'] == "A")) {
                $applicants[$n]['status'] = "Unsubscribed";
            } else if (($fetp['email'] == $applicant['email']) && ($fetp['active'] == 'N') && ($fetp['status'] == "P")) {
                $applicants[$n]['status'] = "Pending";
            } else if (($fetp['email'] == $applicant['email']) && ($fetp['active'] == 'Y') && ($fetp['status'] == "A")) {
                $applicants[$n]['status'] = "Approved";
            }
        }
    }
    $applicants[$n]['apply_date'] = date('j-M-Y', strtotime($applicants[$n]['apply_date']));
    $applicants[$n]['approve_date'] = date('j-M-Y', strtotime($applicants[$n]['approve_date']));
    $n++;
}

// return all applicants
print json_encode($applicants);
