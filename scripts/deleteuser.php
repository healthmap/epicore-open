<?php
require_once "UserInfo.class.php";
require_once "send_email.php";
require_once (dirname(__FILE__) ."/Service/AuthService.php");
require_once (dirname(__FILE__) ."/Model/CognitoErrors.php");

// clean variables
$formvars = json_decode(file_get_contents("php://input"));
$uid = strip_tags($formvars->uid);

// exit if no user id
if(!$uid) {
    print json_encode(array('status' => 'failed', 'reason' => 'missing required parameters uid:' . $uid));
    exit;
}
$uinfo = UserInfo::getUserInfo($uid);
$authService = new AuthService();
$validationService = new ValidationService();

$user = new User();
$user->setEmail($uinfo['email']);

try
{
    $validationService->email($user);
    $authService->User($user->getEmail());
}
catch (\CognitoException | UserAccountNotExist | EmailValidationException $exception)
{
    echo json_encode(array('status' => 'failed', 'reason' => $exception->getMessage()));
    exit();
}

try
{
    $authService->DeleteUser($uinfo['email']);
}
catch (CognitoException $exception)
{
    echo json_encode(array('status' => 'failed', 'reason' => $exception->getMessage()));
    exit();
}

$action = 'delete';
sendMail($uinfo['email'], $uinfo['firstname'], "EpiCore Unsubscription Notification", $action, $uinfo['maillist_id']);

$result = UserInfo::deleteMaillist($uid);
$status = $result[0];
$message = $result[1];

if($status == 'success') {
    print json_encode(array('status' => 'success'));
} else {
    print json_encode(array('status' => 'failed', 'reason' => $message));
}

?>
