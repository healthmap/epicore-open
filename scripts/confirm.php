<?php

/**
 * API endpoint for confirm Cognito account and start process for update new update for AWS Cognito
 */
require_once (dirname(__FILE__) ."/Service/AuthService.php");
require_once (dirname(__FILE__) ."/Model/ApiResponseStatus.php");

$formvars = json_decode(file_get_contents("php://input"));
$status = ApiResponseStatus::success;
$fetpinfo = null;

$username = strip_tags($formvars->username);
$verifycode = strip_tags($formvars->verifycode);
$newpassword = strip_tags($formvars->newpassword);

$message = null;

if(!empty($username) && !empty($verifycode))
{
    $authService = new AuthService();
    try
    {
        $authService->ConfirmAccount($username , $verifycode , $newpassword);
    }
    catch (\UserIsConfirmed $exception)
    {
        $status = ApiResponseStatus::goToLogin;
    }
    catch (\PasswordValidationException $exception)
    {
        $message = $exception->getMessage();
        $status = ApiResponseStatus::failed;
    }

}
print json_encode(array('status' => $status, 'uinfo' => $fetpinfo , 'message' => $message));
