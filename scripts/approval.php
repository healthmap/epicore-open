<?php
/**
 * User: jeffandre
 * Date: 9/22/15
 *
 * Approves applicant and returns all applicants info
 *
 */
require_once "const.inc.php";
require_once "AWSMail.class.php";
require_once 'db.function.php';
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
                $db->query("INSERT INTO fetp (email, countrycode, status)
                            VALUES ('$approve_email', '$approve_countrycode','P')");  // need to add country code
                $lastId = $db->getOne('SELECT LAST_INSERT_ID()');
                $db->commit();
            }
            else{
                $db->query("update fetp set status='P', countrycode= '$approve_countrycode', active=0 where email='$approve_email'");
                $db->commit();
            }

            // send email
            $pending_msg = '<p style="margin:12px 0;"> Dear [NAME], <br><br>

You have been approved to access the Epicore site at https://epicore.org. <br><br>

If you have any questions, please do not hesitate to contact us at info@epicore.org. <br><br>

Best,<br>
Zeenah Haddad  and Emily Cohn</p>';
            $message = str_replace("[NAME]", $approve_name, $pending_msg);
            sendMail($approve_email, "Epicore Approval", $message);
        }
        else if ($action == 'unsubscribe'){
            $db->query("update fetp set active='N' where email='$approve_email'");
            $db->commit();
        }
        else if ($action == 'approve'){
            $db->query("update fetp set active='Y', status='A' where email='$approve_email'");
            $db->commit();
            $approve_date = date('Y-m-d H:i:s', strtotime('now'));
            $db->query("update maillist set approve_date='$approve_date' where maillist_id=$approve_id");
            $db->commit();
        }
}

// get all applicants and fetps
$applicants = $db->getAll("select * from maillist");
$fetps = $db->getAll("select * from fetp");

// get applicant status based on fetp active/status fields
// fetp-active  fetp-status     app-status
// null         null            Not approved
// null         P               Pending         Pending training
// 'Y'          A               Approved        finished training
// 'N'          x               Unsubscribed    Unsubscribed
$applicant_status = [];
$n = 0;
foreach ($applicants as $applicant){
    $applicants[$n]['status'] = 'Not Approved';
    foreach($fetps as $fetp){
        if (($fetp['email'] == $applicant['email']) && ($fetp['active'] == 'N')){
            $applicants[$n]['status'] = "Unsubscribed";
        }
        else if (($fetp['email'] == $applicant['email']) && ($fetp['status'] == "P") && ($fetp['active'] != 'N')){
            $applicants[$n]['status'] = "Pending";
        }
        else if (($fetp['email'] == $applicant['email']) && ($fetp['status'] == "A") && ($fetp['active'] == 'Y')){
            $applicants[$n]['status'] = "Approved";
        }
    }
    $n++;
}

// return all applicants
print json_encode($applicants);


function sendMail($email, $subject, $msg){

    $extra_headers['text_or_html'] = "html";
    //get template
    $emailtext = file_get_contents("../emailtemplates/signup.html");
    $description = $msg;
    $emailtext = str_replace("[DESCRIPTION]", $description, $emailtext);
    $emailtext = str_replace("[SUBJECT]", $subject, $emailtext);
    $aws_resp = AWSMail::mailfunc($email, $subject, $emailtext, EMAIL_INFO_EPICORE, $extra_headers);
}