<?php
/**
 * User: jeffandre
 * Date: 10/16/15
 */

/*
    Commenting out this page just for DEV version
    We don't want emails going out on every test
    *********** IMP to enable this on PROD ***************
*/


// require_once "const.inc.php";
// require_once "AWSMail.class.php";
// require_once "db.function.php";

// function sendMail($email, $name, $subject, $status, $user_id, $event_title = '', $event_date = '', $event_id = ''){

//     $idlist[0] = $user_id;
//     $extra_headers['user_ids'] = $idlist;

//     if($status == 'apply') {
//         $emailtemplate = file_get_contents("../emailtemplates/application.html");
//     }
//     else if($status =='pending'){
//         // create ticket for fetp
//         $fetp_id = UserInfo::getFETPid($email);
//         $db = getDB();
//         $ticket = md5(uniqid(rand(), true));
//         $db->query("INSERT INTO ticket (fetp_id, val, exp) VALUES (?, ?, ?)", array($fetp_id, $ticket, date('Y-m-d H:i:s', strtotime("+30 days"))));
//         $db->commit();
//         //get email template and set link
//         $link = EPICORE_URL .'/#/setpassword?t=' . $ticket;
//         $emailtemplate = file_get_contents("../emailtemplates/pending.html");
//     }
//     else if($status =='preapproved'){
//         // create ticket for fetp
//         $fetp_id = UserInfo::getFETPid($email);
//         $db = getDB();
//         $ticket = md5(uniqid(rand(), true));
//         $db->query("INSERT INTO ticket (fetp_id, val, exp) VALUES (?, ?, ?)", array($fetp_id, $ticket, date('Y-m-d H:i:s', strtotime("+30 days"))));
//         $db->commit();
//         //get email template and set link
//         $link = EPICORE_URL .'/#/setpassword?t=' . $ticket;
//         $emailtemplate = file_get_contents("../emailtemplates/preapprove.html");
//     }
//     else if ($status == 'approved'){
//         //get email template and set link
//         $link = EPICORE_URL .'/#/login';
//         $emailtemplate = file_get_contents("../emailtemplates/approve.html");
//     }
//     else if ($status == 'declined'){
//         //get email template
//         $emailtemplate = file_get_contents("../emailtemplates/decline.html");
//     }
//     else if($status =='resetpassword'){
//         // create ticket for fetp
//         $fetp_id = UserInfo::getFETPid($email);
//         $db = getDB();
//         $ticket = md5(uniqid(rand(), true));
//         $db->query("INSERT INTO ticket (fetp_id, val, exp) VALUES (?, ?, ?)", array($fetp_id, $ticket, date('Y-m-d H:i:s', strtotime("+30 days"))));
//         $db->commit();
//         //get email template and set link
//         $link = EPICORE_URL .'/#/setpassword?t=' . $ticket;
//         $emailtemplate = file_get_contents("../emailtemplates/resetpassword.html");
//     }
//     else if($status =='preapprove_reminder'){
//         // create ticket for fetp
//         $fetp_id = UserInfo::getFETPid($email);
//         $db = getDB();
//         $ticket = md5(uniqid(rand(), true));
//         $db->query("INSERT INTO ticket (fetp_id, val, exp) VALUES (?, ?, ?)", array($fetp_id, $ticket, date('Y-m-d H:i:s', strtotime("+30 days"))));
//         $db->commit();
//         //get email template and set link
//         $link = EPICORE_URL .'/#/setpassword?t=' . $ticket;
//         $emailtemplate = file_get_contents("../emailtemplates/preapprove_reminder.html");
//     }
//     else if($status =='setpassword_reminder'){
//         // create ticket for fetp
//         $fetp_id = UserInfo::getFETPid($email);
//         $db = getDB();
//         $ticket = md5(uniqid(rand(), true));
//         $db->query("INSERT INTO ticket (fetp_id, val, exp) VALUES (?, ?, ?)", array($fetp_id, $ticket, date('Y-m-d H:i:s', strtotime("+30 days"))));
//         $db->commit();
//         //get email template and set link
//         $link = EPICORE_URL .'/#/setpassword?t=' . $ticket;
//         $emailtemplate = file_get_contents("../emailtemplates/setpassword_reminder.html");
//     }
//     else if($status =='training_reminder'){
//         //get email template and set link
//         $link = EPICORE_URL .'/#/login';
//         $emailtemplate = file_get_contents("../emailtemplates/training_reminder.html");
//     }
//     else if($status =='launch_reminder'){
//         $emailtemplate = file_get_contents("../emailtemplates/launch_reminder.html");
//     }
//     else if($status =='delete'){
//         $emailtemplate = file_get_contents("../emailtemplates/delete.html");
//     }
//     else if($status =='warning'){
//         $link = EPICORE_URL .'/#/events';
//         $extra_headers['bcc'] = EMAIL_INFO_EPICORE;
//         $emailtemplate = file_get_contents("../emailtemplates/warning.html");
//     }
//     else if($status =='warning_responses'){
//         $link = EPICORE_URL .'/#/events';
//         $extra_headers['bcc'] = EMAIL_INFO_EPICORE;
//         $emailtemplate = file_get_contents("../emailtemplates/warning_responses.html");
//     }
//     else if($status =='warning2'){
//         $link = EPICORE_URL .'/#/events2';
//         $extra_headers['bcc'] = EMAIL_INFO_EPICORE;
//         $emailtemplate = file_get_contents("../emailtemplates/warning2.html");
//     }
//     else if($status =='warning_responses2'){
//         $link = EPICORE_URL .'/#/events2';
//         $extra_headers['bcc'] = EMAIL_INFO_EPICORE;
//         $emailtemplate = file_get_contents("../emailtemplates/warning_responses2.html");
//     }
//     else if($status =='active_search_warning'){
//         $link = EPICORE_URL .'/#/events2';
//         $extra_headers['bcc'] = EMAIL_INFO_EPICORE;
//         $emailtemplate = file_get_contents("../emailtemplates/warning_active_search.html");
//     }
//     else{
//         return false;
//     }

//     // send email
//     $extra_headers['text_or_html'] = "html";
//     $emailtext = str_replace("[NAME]", $name, $emailtemplate);
//     $emailtext = str_replace("[SUBJECT]", $subject, $emailtext);
//     $emailtext = str_replace("[TITLE]", $event_title, $emailtext);
//     $emailtext = str_replace("[EVENT_DATE]", $event_date, $emailtext);
//     $emailtext = str_replace("[EVENT_ID]", $event_id, $emailtext);
//     if ($link)
//         $emailtext = str_replace("[LINK]", $link, $emailtext);
//     $aws_resp = AWSMail::mailfunc($email, $subject, $emailtext, EMAIL_NOREPLY, $extra_headers);

//     return true;
// }