<?php

require_once (dirname(__FILE__) ."/Service/AuthService.php");
require_once (dirname(__FILE__) ."/Model/UserCognitoType.php");
require_once (dirname(__FILE__) ."/Model/ApiResponseStatus.php");
require_once (dirname(__FILE__) ."/Exception/LoginException.php");
require_once (dirname(__FILE__) ."/UserInfo.class.php");

if (file_exists("/usr/share/php/vendor/autoload.php")) {
    require_once '/usr/share/php/vendor/autoload.php';
}
//require_once '../vendor/autoload.php';

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

$token = 'sampleToken';

$service = new AuthService();
if($_GET['type'] == "login")
{

    try {
        $ex = $service->LoginUser('test@test.com', 'Qwrtyfsfdsfsd1');
        var_dump($ex);
    } catch (\LoginException $e) {
        var_dump($e->getMessage());
    }
}
if($_GET['type'] == "confirm")
{
    try {
        $service->ConfirmAccount('test@test.com', 'Qwertyuiop2222@');
    } catch (Exception $e) {

    }
}
if($_GET['type'] == "signup")
{
    try {
        $service->singUp('test@test.com', 'Qwrtyfsfdsfsd1' , true);
    } catch (Exception $e) {
        var_dump($e->getMessage());
    }
}
if($_GET['type'] == "token")
{
   // try {
        $e = $service->ValidToken($token);
  //  } catch (Exception $e) {
        var_dump($e);
  //  }
}
if($_GET['type'] == "revoke")
{
    try {
    $token = 'sampleToken';
    $e = $service->RevokeToken('test@test.com');
    var_dump($e);
     } catch (Exception $e) {
     }
}