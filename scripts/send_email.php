<?php
/**
 * User: jeffandre
 * Date: 10/16/15
 */
require_once "const.inc.php";
require_once "AWSMail.class.php";
require_once "db.function.php";

function sendMail($email, $name, $subject, $status, $user_id){

    $idlist[0] = $user_id;
    $extra_headers['user_ids'] = $idlist;

    if($status == 'apply') {
        $emailtemplate = file_get_contents("../emailtemplates/application.html");
    }
    else if($status =='pending'){
        // create ticket for fetp
        $fetp_id = UserInfo::getFETPid($email);
        $db = getDB();
        $ticket = md5(uniqid(rand(), true));
        $db->query("INSERT INTO ticket (fetp_id, val, exp) VALUES (?, ?, ?)", array($fetp_id, $ticket, date('Y-m-d H:i:s', strtotime("+30 days"))));
        $db->commit();
        //get email template and set link
        $link = 'https://www.epicore.org/#/setpassword?t=' . $ticket;
        $emailtemplate = file_get_contents("../emailtemplates/pending.html");
    }
    else if($status =='preapproved'){
        // create ticket for fetp
        $fetp_id = UserInfo::getFETPid($email);
        $db = getDB();
        $ticket = md5(uniqid(rand(), true));
        $db->query("INSERT INTO ticket (fetp_id, val, exp) VALUES (?, ?, ?)", array($fetp_id, $ticket, date('Y-m-d H:i:s', strtotime("+30 days"))));
        $db->commit();
        //get email template and set link
        $link = 'https://www.epicore.org/#/setpassword?t=' . $ticket;
        $emailtemplate = file_get_contents("../emailtemplates/preapprove.html");
    }
    else if ($status == 'approved'){
        //get email template and set link
        $link = 'https://www.epicore.org/#/login';
        $emailtemplate = file_get_contents("../emailtemplates/approve.html");
    }
    else if ($status == 'declined'){
        //get email template
        $emailtemplate = file_get_contents("../emailtemplates/decline.html");
    }
    else if($status =='resetpassword'){
        // create ticket for fetp
        $fetp_id = UserInfo::getFETPid($email);
        $db = getDB();
        $ticket = md5(uniqid(rand(), true));
        $db->query("INSERT INTO ticket (fetp_id, val, exp) VALUES (?, ?, ?)", array($fetp_id, $ticket, date('Y-m-d H:i:s', strtotime("+30 days"))));
        $db->commit();
        //get email template and set link
        $link = 'https://www.epicore.org/#/setpassword?t=' . $ticket;
        $emailtemplate = file_get_contents("../emailtemplates/resetpassword.html");
    }
    else if($status =='preapprove_reminder'){
        // create ticket for fetp
        $fetp_id = UserInfo::getFETPid($email);
        $db = getDB();
        $ticket = md5(uniqid(rand(), true));
        $db->query("INSERT INTO ticket (fetp_id, val, exp) VALUES (?, ?, ?)", array($fetp_id, $ticket, date('Y-m-d H:i:s', strtotime("+30 days"))));
        $db->commit();
        //get email template and set link
        $link = 'https://www.epicore.org/#/setpassword?t=' . $ticket;
        $emailtemplate = file_get_contents("../emailtemplates/preapprove_reminder.html");
        $subject = 'EpiCore misses you! Set your password now!';
    }
    else if($status =='setpassword_reminder'){
        // create ticket for fetp
        $fetp_id = UserInfo::getFETPid($email);
        $db = getDB();
        $ticket = md5(uniqid(rand(), true));
        $db->query("INSERT INTO ticket (fetp_id, val, exp) VALUES (?, ?, ?)", array($fetp_id, $ticket, date('Y-m-d H:i:s', strtotime("+30 days"))));
        $db->commit();
        //get email template and set link
        $link = 'https://www.epicore.org/#/setpassword?t=' . $ticket;
        $emailtemplate = file_get_contents("../emailtemplates/setpassword_reminder.html");
        $subject = 'EpiCore misses you! Set your password now!';
    }
    else if($status =='training_reminder'){
        //get email template and set link
        $link = 'https://www.epicore.org/#/login';
        $emailtemplate = file_get_contents("../emailtemplates/training_reminder.html");
        $subject = '80% of success is showing up!';
    }
    else if($status =='launch_reminder'){
        $emailtemplate = file_get_contents("../emailtemplates/launch_reminder.html");
        $subject = 'Epicore Project Officially Launched!';
    }
    else{
        return false;
    }

    // send email
    $extra_headers['text_or_html'] = "html";
    $emailtext = str_replace("[NAME]", $name, $emailtemplate);
    $emailtext = str_replace("[SUBJECT]", $subject, $emailtext);
    if ($link)
        $emailtext = str_replace("[LINK]", $link, $emailtext);
    $aws_resp = AWSMail::mailfunc($email, $subject, $emailtext, EMAIL_INFO_EPICORE, $extra_headers);

    return true;
}