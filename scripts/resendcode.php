<?php
/* takes input from reset password form and sends an email to reset the fetp password */
$formvars = json_decode(file_get_contents("php://input"));

require_once (dirname(__FILE__) ."/Service/AuthService.php");
// get user email
$user_email = strip_tags($formvars->username);
// TODO AuthService
$authService = new AuthService();
$message = '';

try
{
    $authService->ForgotPassword($user_email);
    $status = 'success';
}
catch (UserAccountNotExist | \Exception $exception)
{
    $message = $exception->getMessage();
    $status = 'failed';
}

print json_encode(array('status' => $status , 'message' => $message));