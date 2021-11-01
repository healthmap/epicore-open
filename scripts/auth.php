<?php
$formvars = json_decode(file_get_contents("php://input"));

require_once "UserInfo.class.php";
require_once (dirname(__FILE__) ."/Service/AuthService.php");

$status = false;
$message = '';
$token = strip_tags($formvars->token);

$authService = new AuthService();

try
{
    $status = $authService->ValidToken($token);
}
catch (CognitoException $exception)
{
    error_log($exception->getMessage());
    $message = $exception->getMessage();
    $status = false;
}

print json_encode(array('authorization' => $status, 'message' => $message));


