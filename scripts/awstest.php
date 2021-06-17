<?php

require_once (dirname(__FILE__) ."/Service/AuthService.php");
require_once (dirname(__FILE__) ."/Model/UserCognitoType.php");
require_once (dirname(__FILE__) ."/Model/ApiResponseStatus.php");
require_once (dirname(__FILE__) ."/Exception/LoginException.php");
require_once (dirname(__FILE__) ."/UserInfo.class.php");

if (file_exists("/usr/share/php/vendor/autoload.php")) {

}
require_once '../vendor/autoload.php';

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

$service = new AuthService();
if($_GET['type'] == "login")
{

    try {
        $ex = $service->LoginUser('bartkiewiczj@gmail.com', 'Qwrtyfsfdsfsd1');
        var_dump($ex);
    } catch (\LoginException $e) {
        var_dump($e->getMessage());
    }
}
if($_GET['type'] == "confirm")
{
    try {
        $service->ConfirmAccount('bartkiewiczj@gmail.com', 'Qwertyuiop2222@');
    } catch (Exception $e) {

    }
}
if($_GET['type'] == "signup")
{
    try {
        $service->singUp('bartkiewiczj@gmail.com', 'Qwrtyfsfdsfsd1' , true);
    } catch (Exception $e) {
        var_dump($e->getMessage());
    }
}
if($_GET['type'] == "token")
{
   // try {
        $token = '';
        $e = $service->ValidToken($token);
  //  } catch (Exception $e) {
        var_dump($e);
  //  }
}
if($_GET['type'] == "revoke")
{
    // try {
    $token = 'eyJraWQiOiJpQ3RIV0JhSkZBc2w5bTFISEFzRFhldW5cL2hhdVl5UmhGZ3VFazB5d2dZWT0iLCJhbGciOiJSUzI1NiJ9.eyJvcmlnaW5fanRpIjoiNjYwYTE4ZDktYTdlZS00OWI4LWEwODktMzY2NzljMGEyMDI2Iiwic3ViIjoiOTVkODAzOWItMjFhNS00NmM0LTg2NjYtNzdkMjI4YjM3MDY4IiwiZXZlbnRfaWQiOiIzOTc5ZTRlYS04MjMzLTRkMjktYTQ5OS1hOGJmM2FlZDZiYjQiLCJ0b2tlbl91c2UiOiJhY2Nlc3MiLCJzY29wZSI6ImF3cy5jb2duaXRvLnNpZ25pbi51c2VyLmFkbWluIiwiYXV0aF90aW1lIjoxNjIzNzg5NTE2LCJpc3MiOiJodHRwczpcL1wvY29nbml0by1pZHAudXMtZWFzdC0xLmFtYXpvbmF3cy5jb21cL3VzLWVhc3QtMV9qZGJMdXh2TUUiLCJleHAiOjE2MjM3OTMxMTYsImlhdCI6MTYyMzc4OTUxNywianRpIjoiNDcxMDRlMzQtNTk3Ny00ZDY2LWI5ODUtNmJlZDdhNzEyNmYxIiwiY2xpZW50X2lkIjoiMmc0bjY4MGVwZ3N1aHEwYWRpc3Ywa3A0YmoiLCJ1c2VybmFtZSI6ImJhcnRraWV3aWN6akBnbWFpbC5jb20ifQ.XBbze08X36-FoR5sDIjy41fhDb-eMlVnIZXvgkzsXP808-E_0OFBcfaRkWuksQwTZJ7p65WcMpZqUN0j8AbiMSmXPYizK2dmwYYxmrIlEhyehIKGMb66TG64CUtMbnRrCtkpN_AI2zxS2ra-STqtofmFyfVvw9vQZismflZ4r6v2QMag_mEEQO2fenhsDO-yH6IL9mYcuwXF0Dd75XAJtUvNpVXaSkL2IV6sqDxhtCws6mkThDXQUnM9s6iUM19JLcqEAeK0WXc0QaZLm_zQ2NBM0lIii5oCwggXe15zYDzetExzVDkHUgSOpr70Kpxmd8oR8l6DaunAZQNt3TbyEw!';
    $e = $service->RevokeToken($token);
    //  } catch (Exception $e) {
    //  }
}