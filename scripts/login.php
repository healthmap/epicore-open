<?php
/* take the input from the Epicore login form and authenticate user */
$formvars = json_decode(file_get_contents("php://input"));

require_once "const.inc.php";
require_once "UserInfo.class.php";
require_once (dirname(__FILE__) ."/Service/AuthService.php");
require_once (dirname(__FILE__) ."/Service/ValidationService.php");
require_once (dirname(__FILE__) ."/Exception/PasswordValidationException.php");
require_once (dirname(__FILE__) ."/Exception/EmailValidationException.php");

$status = "incorrect password";
$path = "home";
$cognitoChangePassExceptionPath = false;
$cognitoAuthStatus = true;

$authService = new AuthService();
$env = ENVIRONMENT;

if(isset($formvars->ticket_id) && $formvars->usertype == "fetp") { // ticket system is for FETPs
    
    $uinfo = UserInfo::authenticateFetp(strip_tags($formvars->ticket_id));
    $user_id = $uinfo['fetp_id'];
    // for now set the fetp_id as the username
    $uinfo['username'] = "Member $user_id";
    $fetpinfo = UserInfo::getFETP($user_id);
    $uinfo['status'] = $fetpinfo['status'];
    $uinfo['locations'] = $fetpinfo['locations'];
} else {
    if(isset($formvars->ticket_id)) { // ticket system for mods coming from dashboard
        $uinfo = UserInfo::authenticateMod($formvars->ticket_id);
    } else { // login system is for mods and fetps

        $dbdata['email'] = strip_tags($formvars->username);
        $dbdata['password'] = strip_tags($formvars->password);

        $passwordWeekProcess = false;
        $passwordValidationProcess = false;
        $isSuperAdmin = false;

        $validationService = new ValidationService();

        $user = new User();
        $user->setEmail($dbdata['email']);
        $user->setPassword($dbdata['password']);

        try
        {
            $_isAdmin = false;
            $validationService->email($user);
            $validationService->password($user);
            $uinfo = UserInfo::authenticateUser($dbdata);
            if(!$uinfo){
                $status = "incorrect password";
                $cognitoAuthStatus = false;
            }
            if(!$uinfo && $uinfo['roleName'] === "admin"){
                $_isAdmin = true;
                $uinfo = UserInfo::authenticateUser($dbdata);
                $uinfo['superuser'] = (isset($uinfo['user_id']) && in_array($uinfo['user_id'], $super_users)) ? true: false;
            }
            if(!$_isAdmin && is_array($uinfo)) {
               $authResponse = $authService->LoginUser($user->getEmail(), $user->getPassword());
               if (!is_null($authResponse)) {
                   $uinfo['token']['accessToken'] = $authResponse->getAccessToken();
                   $uinfo['token']['refreshToken'] = $authResponse->getRefreshToken();
                   $uinfo['token']['expiresIn'] = $authResponse->getExpiresIn();
               }
           }
        }
        catch (EmailValidationException $exception)
        {
            $uinfo = UserInfo::authenticateUser($dbdata);
            if(!$uinfo) {
                $status = "incorrect password";
                $cognitoAuthStatus = false;
            }
            if($uinfo) {
                $uinfo['superuser'] = (isset($uinfo['user_id']) && in_array($uinfo['user_id'], $super_users)) ? true : false;
                if ($uinfo['superuser']) {
                    $isSuperAdmin = true;
                }
            }
        }
        catch (PasswordValidationException $exception)
        {
            $cognitoAuthStatus = true;
            $uinfo = UserInfo::authenticateUser($dbdata, false);
            if(!$uinfo)
            {
                $status = "incorrect password";
                $cognitoAuthStatus = false;
            }
            if($cognitoAuthStatus) {
                try {
                    $cognitoUser = $authService->User($user->getEmail());
                    $status = "incorrect password";
                    $cognitoAuthStatus = false;
                } catch (UserAccountNotExist $exception) {
                    $authService->SingUp($user->getEmail(), '', true, true);
                    $authService->ForgotPassword($dbdata['email']);
                    $cognitoChangePassExceptionPath = true;
                }
                catch (Exception | CognitoException $e) {
                }
            }
        }
        catch (\UserAccountNotExist $exception) {
            try {
                $authService->SingUp($user->getEmail(), $user->getPassword(), true, false);
                $authResponse = $authService->LoginUser($user->getEmail(), $user->getPassword());
                if (!is_null($authResponse)) {
                    $uinfo['token']['accessToken'] = $authResponse->getAccessToken();
                    $uinfo['token']['refreshToken'] = $authResponse->getRefreshToken();
                    $uinfo['token']['expiresIn'] = $authResponse->getExpiresIn();
                }
            }
            catch (\LoginException | CognitoException | Exception $exception){
                $status = "incorrect password";
                $cognitoAuthStatus = false;
            }
        }
        catch (\LoginException | CognitoException | Exception $exception){
            $status = "incorrect password";
            $cognitoAuthStatus = false;
        }
    }
    $user_id = isset($uinfo['fetp_id']) ? $uinfo['fetp_id'] : $uinfo['user_id'];
}

// make sure it's a valid user id (or fetp id)
if(is_numeric($user_id) && $user_id > 0) {
    // if it was a mod who successfully logged in, let's now repopulate the fetp table with latest eligible tephinet ids
    //if(isset($uinfo['organization_id']) && $uinfo['organization_id'] > 0) {
    //    $ui = new UserInfo($user_id);
    //    $ui->getFETPEligible();
    //}
    $status = "success";
        $mdata = array();
    // if mobile app, add/update info
    if ($formvars->app == 'mobile'){
  
        $mdata['reg_id'] = strip_tags($formvars->reg_id);
        $mdata['model'] = strip_tags($formvars->model);
        $mdata['platform'] = strip_tags($formvars->platform);
        $mdata['os_version'] = strip_tags($formvars->os_version);

        // mobile can be from fetp or mod
        if (isset($uinfo['fetp_id']))
            $mdata['fetp_id'] = $user_id;   // fetp (member)
        else
            $mdata['user_id'] = $user_id;   // mode

        // add mobile device
        $mobile_id = UserInfo::addMobileDevice($mdata);  // mobile_id or false if error

    }

    // if it was a mobile device with event id, go directly to the "respond" page
    // if it was a ticket with an event id, go directly to the "respond" page
    // if it was a ticket with an alert id, go directly to the "request" page (only version 1 for ProMED alerts)
    if (isset($formvars->epicore_version) && $formvars->epicore_version == '2') {  // app version 2
        if (isset($formvars->event_id) && is_numeric($formvars->event_id)) {
            $path = "events2/" . $formvars->event_id;
        } else {
            $path = "events2";
        }
    } else {
        if (isset($formvars->event_id) && is_numeric($formvars->event_id)) {    // app version 1
            $path = "events/" . $formvars->event_id;
        } elseif (isset($formvars->alert_id) && is_numeric($formvars->alert_id)) {
            $path = "request/" . $formvars->alert_id;
        } else {
            $path = "events";
        }
    }

    if($cognitoChangePassExceptionPath)
    {
        $status = "success";
        $path = 'setpassword';
    }
    $uinfo['superuser'] = (isset($uinfo['user_id']) && in_array($uinfo['user_id'], $super_users)) ? true: false;


}
$content = array('status' => $status, 'path' => $path, 'uinfo' => $uinfo , 'environment' => $env);
print json_encode($content);
?>
