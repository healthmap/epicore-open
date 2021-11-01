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

$token = 'eyJraWQiOiJjYWkrVmdrdnFmbnpKXC83M0YyRDNWUFwveEx6Q09sVFh0cmdPVW9ESTMrMnc9IiwiYWxnIjoiUlMyNTYifQ.eyJvcmlnaW5fanRpIjoiYTBkYTA0OGEtM2RmOS00NDMxLWJiMjgtYWU2N2FlZTZhOWNhIiwic3ViIjoiZDg2ZjQwZTgtMzE0OS00ZjQ4LWFjZjctNTdiMWJiNDI4ZTcyIiwiZXZlbnRfaWQiOiI5MGJmZTg2My01ZmE1LTQwZTktYmVkNC0wZmMyNDlhOTEzMDciLCJ0b2tlbl91c2UiOiJhY2Nlc3MiLCJzY29wZSI6ImF3cy5jb2duaXRvLnNpZ25pbi51c2VyLmFkbWluIiwiYXV0aF90aW1lIjoxNjI0MjE4NDM0LCJpc3MiOiJodHRwczpcL1wvY29nbml0by1pZHAudXMtZWFzdC0yLmFtYXpvbmF3cy5jb21cL3VzLWVhc3QtMl8xRGVTbDY0MnkiLCJleHAiOjE2MjQyMjIwMzQsImlhdCI6MTYyNDIxODQzNCwianRpIjoiYzkyNWEzMTctMDcyZi00MDlkLWIwMGYtMDQxYzVmZmU2YjZiIiwiY2xpZW50X2lkIjoiNGVuZ3VtYzVxMjZvNGFmcDNja2drb2dwZnMiLCJ1c2VybmFtZSI6ImJhcnRraWV3aWN6akBnbWFpbC5jb20ifQ.SBhmY2MA3qs_srss_bN582gNPJtn1IcZc1YsG8SsnZDdnB4D96MpmveYyFXeT86VXchVIfDHCBljPqhRqfVbeuYpdhELpWQAOuo8rT4T_fj1pZhqTR47QDUbDWN8uO0lCAPflljFnsRz48c21XoDAo2g4x6Cubl2tCb-9JMyrrupGDdp9T-GAwjiqcBPKdN3FaCDu1NxdHxGUNVOcqfa2eHjQMS5TVqnrIAAwBaI8qqx03fMaEOBDjeQDkVACW4KCV706Ja5-tV1pXctKmcd9fDo0L5B7fEqc2976-8hz12CFnlAKk2U_ygfHYp45TTk6r49ym-8PlGdVu6f9gjuIA';


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
        $e = $service->ValidToken($token);
  //  } catch (Exception $e) {
        var_dump($e);
  //  }
}
if($_GET['type'] == "revoke")
{
    // try {
    $token = 'eyJraWQiOiJpQ3RIV0JhSkZBc2w5bTFISEFzRFhldW5cL2hhdVl5UmhGZ3VFazB5d2dZWT0iLCJhbGciOiJSUzI1NiJ9.eyJvcmlnaW5fanRpIjoiNjYwYTE4ZDktYTdlZS00OWI4LWEwODktMzY2NzljMGEyMDI2Iiwic3ViIjoiOTVkODAzOWItMjFhNS00NmM0LTg2NjYtNzdkMjI4YjM3MDY4IiwiZXZlbnRfaWQiOiIzOTc5ZTRlYS04MjMzLTRkMjktYTQ5OS1hOGJmM2FlZDZiYjQiLCJ0b2tlbl91c2UiOiJhY2Nlc3MiLCJzY29wZSI6ImF3cy5jb2duaXRvLnNpZ25pbi51c2VyLmFkbWluIiwiYXV0aF90aW1lIjoxNjIzNzg5NTE2LCJpc3MiOiJodHRwczpcL1wvY29nbml0by1pZHAudXMtZWFzdC0xLmFtYXpvbmF3cy5jb21cL3VzLWVhc3QtMV9qZGJMdXh2TUUiLCJleHAiOjE2MjM3OTMxMTYsImlhdCI6MTYyMzc4OTUxNywianRpIjoiNDcxMDRlMzQtNTk3Ny00ZDY2LWI5ODUtNmJlZDdhNzEyNmYxIiwiY2xpZW50X2lkIjoiMmc0bjY4MGVwZ3N1aHEwYWRpc3Ywa3A0YmoiLCJ1c2VybmFtZSI6ImJhcnRraWV3aWN6akBnbWFpbC5jb20ifQ.XBbze08X36-FoR5sDIjy41fhDb-eMlVnIZXvgkzsXP808-E_0OFBcfaRkWuksQwTZJ7p65WcMpZqUN0j8AbiMSmXPYizK2dmwYYxmrIlEhyehIKGMb66TG64CUtMbnRrCtkpN_AI2zxS2ra-STqtofmFyfVvw9vQZismflZ4r6v2QMag_mEEQO2fenhsDO-yH6IL9mYcuwXF0Dd75XAJtUvNpVXaSkL2IV6sqDxhtCws6mkThDXQUnM9s6iUM19JLcqEAeK0WXc0QaZLm_zQ2NBM0lIii5oCwggXe15zYDzetExzVDkHUgSOpr70Kpxmd8oR8l6DaunAZQNt3TbyEw!';
    $e = $service->RevokeToken('bartkiewiczj@gmail.com');
    var_dump($e);
    //  } catch (Exception $e) {
    //  }
}