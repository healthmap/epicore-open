<?php
/**
 * User: jeffandre
 * Date: 9/22/15
 *
 * Approves applicant and sends email.
 * Returns all applicants info.
 *
 */
require_once "const.inc.php";
require_once "AWSMail.class.php";
require_once 'db.function.php';
require_once 'UserInfo.class.php';
require_once "send_email.php";

$db = getDB();

// approve applicant
$data = json_decode(file_get_contents("php://input"));
$approve_id = (string)$data->maillist_id;
$action = (string)$data->action;
if ($approve_id) {
        $approve_email = $db->getOne("select email from maillist where maillist_id=$approve_id");
        $approve_countrycode = $db->getOne("select country from maillist where maillist_id=$approve_id");
        $approve_name = $db->getOne("select firstname from maillist where maillist_id=$approve_id");

        if ($action == 'pending'){
            // copy maillist to new fetp if it does not exist and set fetp status to 'P'
            $fetpemail = $db->getOne("select email from fetp where email='$approve_email'");
            if (!$fetpemail){
                $db->query("INSERT INTO fetp (email, countrycode, active, status)
                            VALUES ('$approve_email', '$approve_countrycode', 'N','P')");
                $lastId = $db->getOne('SELECT LAST_INSERT_ID()');
                $db->commit();
            }
            else{
                $db->query("update fetp set status='P', countrycode= '$approve_countrycode', active='N' where email='$approve_email'");
                $db->commit();
            }

            $fetp_id = UserInfo::getFETPid($approve_email);
            // send email
            sendMail($approve_email, $approve_name, "EpiCore Application Decision", $action, $fetp_id);
        }
        else if ($action == 'unsubscribed'){
            $db->query("update fetp set active='N' where email='$approve_email'");
            $db->commit();
        }
        else if ($action == 'approved'){
            $db->query("update fetp set active='Y', status='A' where email='$approve_email'");
            $db->commit();
            $approve_date = date('Y-m-d H:i:s', strtotime('now'));
            $db->query("update maillist set approve_date='$approve_date' where maillist_id=$approve_id");
            $db->commit();

            $fetp_id = UserInfo::getFETPid($approve_email);
            // send mail
            sendMail($approve_email, $approve_name, "EpiCore Course Completed", $action, $fetp_id);

        }else if ($action == 'inactive'){
            $db->query("update fetp set active=0 where email='$approve_email'");
            $db->commit();
        }
}

// get all applicants and fetps
$applicants = $db->getAll("select * from maillist");
$fetps = $db->getAll("select * from fetp");

// set all applicants status based on fetp active/status fields
// fetp-active  fetp-status     app-status
// null         null            Inactive
// 'N'          P               Pending         Pending training
// 'Y'          A               Approved        Finished training
// 'N'          A               Unsubscribed    Unsubscribed
$applicant_status = [];
$n = 0;
foreach ($applicants as $applicant){
    $applicants[$n]['status'] = 'Inactive';
    foreach($fetps as $fetp){
        if (($fetp['email'] == $applicant['email']) && ($fetp['active'] == 'N') && ($fetp['status'] == "A")){
            $applicants[$n]['status'] = "Unsubscribed";
        }
        else if (($fetp['email'] == $applicant['email']) && ($fetp['active'] == 'N') && ($fetp['status'] == "P")){
            $applicants[$n]['status'] = "Pending";
        }
        else if (($fetp['email'] == $applicant['email']) && ($fetp['active'] == 'Y') && ($fetp['status'] == "A")){
            $applicants[$n]['status'] = "Approved";
        }
    }
    $n++;
}

// return all applicants
print json_encode($applicants);
