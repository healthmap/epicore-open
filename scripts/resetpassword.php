<?php
/* takes input from reset password form and sends an email to reset the fetp password */
$formvars = json_decode(file_get_contents("php://input"));
require_once "UserInfo.class.php";
require_once "send_email.php";
require_once (dirname(__FILE__) ."/Service/AuthService.php");

// get user email
$user_email = strip_tags($formvars->username);
$fetp_id = UserInfo::getFETPid($user_email);
$userinfo = UserInfo::getUserInfobyEmail($user_email);

// send email to reset password
if ($fetp_id) {
    $action = 'resetpassword';

    // TODO AuthService
    $authService = new AuthService();

    try
    {
        $authService->ForgotPassword($user_email);
        $status = 'success';
    }
    catch (\CognitoException $exception){
        if($exception->getMessage() === CognitoErrors::cantResetPassword){
            try
            {
                $authService->forceResetPassword($user_email);
                $authService->ForgotPassword($user_email);
            }
            catch (CognitoException | UserAccountNotExist | Exception $exception){
                $status = 'failed';
            }
        }
    }
    catch (\Exception $exception)
    {
        $status = 'failed';
    }
}
else{
    $status = 'failed';
}

print json_encode(array('status' => $status));

?>
