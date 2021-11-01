<?php
/* takes input from reset password form and sends an email to reset the fetp password */
$formvars = json_decode(file_get_contents("php://input"));
require_once "UserInfo.class.php";
require_once "send_email.php";
<<<<<<< HEAD

// get user email
$user_email = strip_tags($formvars->username);
$fetp_id = UserInfo::getFETPid($user_email);
$userinfo = UserInfo::getUserInfobyEmail($user_email);

// send email to reset password
if ($fetp_id) {
    $action = 'resetpassword';
    sendMail($user_email, $userinfo['firstname'], "EpiCore Reset Password", $action, $fetp_id);
    $status = 'success';
=======
require_once (dirname(__FILE__) ."/Service/AuthService.php");

// get user email
$user_email = strip_tags($formvars->username);
$fetp_id = UserInfo::getFETPid($user_email);//checking if reponder
$user_id = UserInfo::authenticateUserByEmail($user_email); //checking if requester
$status = null;

// send email to reset password
if (!is_null($fetp_id) || !is_null($user_id)) {
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
                $status = 'success';
                
            }
            catch (CognitoException | UserAccountNotExist | Exception $exception){
                $status = 'failed:'.$exception.getMessage();
            }
        }
    }
    catch (\Exception $exception) {
        $status = 'failed:'.$exception.getMessage();
    }
>>>>>>> epicore-ng/main
}
else{
    $status = 'failed';
}

print json_encode(array('status' => $status));

?>
