<?php
/*
 * Sends reminder emails to applicants.
 */

require_once "send_email.php";
require_once 'UserInfo.class.php';
require_once "db.function.php";
require_once (dirname(__FILE__) ."/Service/AuthService.php");
require_once (dirname(__FILE__) ."/Model/CognitoErrors.php");

$db = getDB();

$data = json_decode(file_get_contents("php://input"));
$action = (string)$data->action;
$memberid = (int)$data->memberid;

$maillist = '';
$message = '';
$status = 'success';

// unsubscribed/pre-approved members with no password and accepted at least one week ago
if ($action == 'preapprove_reminder') {
    $maillist = $db->getAll("select fetp_id, f.email,firstname from fetp as f, maillist as m  where f.email=m.email 
                            and active='N' and status='A' and pword_hash is null AND accept_date <= NOW() - INTERVAL 1 WEEK");
    foreach ($maillist as $member) {
        sendMail($member['email'], $member['firstname'], "Reminder | You're almost there!", $action, $member['fetp_id']);
    }
    sendMail('info@epicore.org', 'Info', "Reminder | You're almost there!", $action, '0');
}

if ($action == 'applicant_setpassword_reminder') {
    $applicant_info = $db->getRow("select fetp_id, f.email,firstname from epicore.fetp as f, epicore.maillist as m  where f.maillist_id=m.maillist_id  AND m.maillist_id='$memberid'");

    if(!empty($applicant_info))
    {
        $authService = new AuthService();
        $cognitoForgotPassword = false;
        $cognitoAddNewAccount = false;

        try {
            $user = $authService->User($applicant_info['email']);
            if(is_array($user) && isset($user['UserStatus']) && $user['UserStatus'] === 'FORCE_CHANGE_PASSWORD')
            {
               $authService->forceResetPassword($applicant_info['email'] , $authService->generatePassword());
            }
            $cognitoForgotPassword = true;
        }
        catch (\CognitoException | UserAccountNotExist $exception)
        {
            $cognitoAddNewAccount = true;
        }
        if($cognitoAddNewAccount) {
            try
            {
                $authService->SingUp($applicant_info['email'], $authService->generatePassword(), true);
            }
            catch (NoEmailProvidedException | PasswordValidationException | UserAccountExistException | \CognitoException $e)
            {
                error_log($e->getMessage());
                $message = $e->getMessage();
                $status = 'failure';
            }
        }
        if($cognitoForgotPassword) {
            try
            {
                $authService->ForgotPassword($applicant_info['email']);
            } catch (\Exception $exception) {
                error_log($exception->getMessage());
                $message = $exception->getMessage();
                $status = 'failure';
            }
        }
    }

    echo json_encode(['status' => $status , 'message' => $message]);
    exit(0);
}

if ($action == 'applicant_finishtraining_reminder') {
    
    $applicant_training_info = $db->getRow("select fetp_id, f.email,firstname from epicore.fetp as f, epicore.maillist as m  where f.maillist_id=m.maillist_id  AND m.maillist_id='$memberid'");
  
    $maillist = $applicant_training_info;

    $result = sendMail($applicant_training_info['email'], $applicant_training_info['firstname'], "Reminder | You're almost there!", 'training_reminder', $applicant_training_info['fetp_id']);

    // sendMail('info@epicore.org', 'Info', "Reminder | You're almost there!", $action, '0');
}

// get accepted members with no password and accepted at least one week ago
if ($action == 'setpassword_reminder') {
    $maillist = $db->getAll("select fetp_id, f.email,firstname from fetp as f, maillist as m  where f.email=m.email and active='N'
                            and status='P' and pword_hash is null AND accept_date <= NOW() - INTERVAL 1 WEEK");
    
    foreach ($maillist as $member) {
        sendMail($member['email'], $member['firstname'], "Reminder | You're almost there!", $action, $member['fetp_id']);
    }
    sendMail('info@epicore.org', 'Info', "Reminder | You're almost there!", $action, '0');
}

// accepted members (with password set) that did not finish training
if ($action == 'training_reminder') {
    $maillist =  $db->getAll("select fetp_id, f.email,firstname from fetp as f,maillist as m where f.email=m.email and active='N'
                            and status='P' and pword_hash is not null and f.email
                            in (select email from maillist where online_course is null)");
    foreach ($maillist as $member) {
        sendMail($member['email'], $member['firstname'], "80% of success is showing up!", $action, $member['fetp_id']);
    }
    sendMail('info@epicore.org', 'Info', "80% of success is showing up!", $action, '0');
}

// original launch members that did not apply
if ($action == 'launch_reminder') {
    $maillist = $db->getAll("select maillist_id,email,firstname from contactlist where email not in (select email from maillist)");
    foreach ($maillist as $member) {
        sendMail($member['email'], $member['firstname'], "Reminder | We miss you!", $action, $member['maillist_id']);
    }
    sendMail('info@epicore.org', 'Info', "Reminder | We miss you!", $action, '0');
}

// return all applicants
print json_encode($maillist);
?>
