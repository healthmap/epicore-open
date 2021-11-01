<?php
$formvars = json_decode(file_get_contents("php://input"));

require_once "UserInfo.class.php";
require_once (dirname(__FILE__) ."/Service/AuthService.php");

$status = false;
$message = '';
$username = strip_tags($formvars->username);

$authService = new AuthService();

try
{
    $status = $authService->RevokeToken($username);
}
catch (CognitoException $exception)
{
    error_log($exception->getMessage());
    $message = $exception->getMessage();
    $status = false;
}
echo json_encode(array('authorization' => $status, 'message' => $message));