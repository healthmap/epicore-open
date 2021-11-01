<?php
require_once "UserInfo.class.php";
require_once "send_email.php";
<<<<<<< HEAD
=======
require_once (dirname(__FILE__) ."/Service/AuthService.php");
require_once (dirname(__FILE__) ."/Model/CognitoErrors.php");
>>>>>>> epicore-ng/main

// clean variables
$formvars = json_decode(file_get_contents("php://input"));
$uid = strip_tags($formvars->uid);

// exit if no user id
if(!$uid) {
    print json_encode(array('status' => 'failed', 'reason' => 'missing required parameters uid:' . $uid));
    exit;
}
<<<<<<< HEAD

require_once "UserInfo.class.php";
$uinfo = UserInfo::getUserInfo($uid);
$action = 'delete';
sendMail($uinfo['email'], $uinfo['firstname'], "EpiCore Unsubscription Notification", $action, $uinfo['maillist_id']);
=======
$uinfo = UserInfo::getUserInfo($uid);
$authService = new AuthService();
$validationService = new ValidationService();

$user = new User();
$user->setEmail($uinfo['email']);

try
{
    $validationService->email($user);
}
catch (EmailValidationException $exception) // user email/pwd validation errors thrown by validationService
{
    echo json_encode(array('status' => 'failed', 'reason' => $exception->getMessage()));
    exit();
}


$action = 'delete';
>>>>>>> epicore-ng/main

$result = UserInfo::deleteMaillist($uid);
$status = $result[0];
$message = $result[1];

if($status == 'success') {
<<<<<<< HEAD
    print json_encode(array('status' => 'success'));
=======

    sendMail($uinfo['email'], $uinfo['firstname'], "EpiCore Unsubscription Notification", $action, $uinfo['maillist_id']);
    //delete on Cognito
    try
    {
        if($message === 'deletion of maillist & fetp success.') {
            $authService->User($user->getEmail()); 
            $authService->DeleteUser($uinfo['email']);
        } 
        print json_encode(array('status' => 'success'));
    }
    catch (\CognitoException | UserAccountNotExist $exception)
    {
        echo json_encode(array('status' => 'failed', 'reason' => $exception->getMessage()));
        exit();
    }

>>>>>>> epicore-ng/main
} else {
    print json_encode(array('status' => 'failed', 'reason' => $message));
}

?>
