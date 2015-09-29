<?php
/**
 * User: jeffandre
 * Date: 9/22/15
 *
 * Approves applicant and returns all applicants info
 *
 */
require_once 'db.function.php';
$db = getDB();
$approvedate = date('Y-m-d H:i:s', strtotime('now'));

// approve applicant
$approve_id = null;
if (isset($_GET['maillist_id'])) {
    $approve_id = strip_tags($_GET['maillist_id']);
    //todo - copy maillist entry into new fetp and set fetp status to 'P"
}

// get all applicants and fetps
$applicants = $db->getAll("select * from maillist");
$fetps = $db->getAll("select * from fetp");

// get applicant status based on fetp active/status fields
// fetp-active  fetp-status     app-status
// null         null            Not approved
// x            P               Pending         Pending training
// 1            A               Approved        finished training
// null         A               Unsubscribed    finished training but unsubscribed
$applicant_status = [];
$n = 0;
foreach ($applicants as $applicant){
    $applicants[$n]['status'] = 'Not Approved';
    foreach($fetps as $fetp){
        if (($fetp['email'] == $applicant['email']) && ($fetp['status'] == "P")){
            $applicants[$n]['status'] = "Pending";
        }
        else if (($fetp['email'] == $applicant['email']) && ($fetp['status'] == "A") && $fetp['active']){
            $applicants[$n]['status'] = "Approved";
        }
        else if (($fetp['email'] == $applicant['email']) && ($fetp['status'] == "A") && !$fetp['active']){
            $applicants[$n]['status'] = "Unsubscribed";
        }
    }
    $n++;
}

// return all applicants
print json_encode($applicants);