<?php
/* takes input from the Epicore set password form, authenticates user, and sets password */
$formvars = json_decode(file_get_contents("php://input"));
require_once "UserInfo.class.php";
require_once (dirname(__FILE__) ."/Service/AuthService.php");
require_once (dirname(__FILE__) ."/Exception/InvalidCodeException.php");
require_once (dirname(__FILE__) ."/Model/UserCognitoType.php");
require_once (dirname(__FILE__) ."/Model/ApiResponseStatus.php");
$status = ApiResponseStatus::failed;

// get username/email and password
$username = strip_tags($formvars->username);
$password = strip_tags($formvars->password);
$verifycode = strip_tags($formvars->verifycode);
$message = '';

if(!empty($verifycode))
{
    $fetchObj = UserInfo::authenticateFetpByEmail($formvars->username);
    if(!empty($fetchObj))
    {
        $fetpinfo['username'] = "MEMBER ". $fetchObj['fetp_id'];
        try
        {
            $validationService = new ValidationService();

            $user = new User();
            $user->setEmail($username);
            $user->setPassword($password);

            $validationService->email($user);
            $validationService->password($user);

            $authService = new AuthService();
            $authService->UpdatePassword($username, $password, $verifycode);
            $status = ApiResponseStatus::success;
        }
        catch (PasswordValidationException | InvalidCodeException | Exception $exception)
        {
            error_log($exception->getMessage());
            $message = $exception->getMessage();
            $status = ApiResponseStatus::failed;
        }

        print json_encode(array('status' => $status, 'uinfo' => $fetpinfo , 'message' => $message));
        die();
    }
    else
    {
        $message = 'Username not found.';
        print json_encode(array('status' => $status, 'uinfo' => null , 'message' => $message));
        die();
    }

}

// authenticate fetp and get info
$ticket = strip_tags($formvars->ticket_id);
$authfetp = UserInfo::authenticateFetp($ticket);
$fetpinfo = UserInfo::getFETP($authfetp['fetp_id']);
$fetpinfo['username'] = "MEMBER ". $authfetp['fetp_id'];


// set password if username matches authenticated email
$emailmatch = (strcasecmp($fetpinfo['email'], $username) == 0) ? true: false;
if(is_numeric($authfetp['fetp_id']) && ($authfetp['fetp_id'] > 0) && $emailmatch) {
    $password_set = UserInfo::setFETPpassword($authfetp['fetp_id'],$password);
    if ($password_set){
        $status = 'success';

        // geocode fetp if not already done
        if (!$fetpinfo['lat']){
            UserInfo::geocodeFETP($fetpinfo['email']);
        }

        // set user active for pending_preapproved users (active = N, status = A)
        if (($fetpinfo['active'] == 'N') && ($fetpinfo['status'] == 'A')){
            $uinfo = UserInfo::getUserInfobyEmail($fetpinfo['email']);
            UserInfo::setUserStatus($uinfo['maillist_id'], 'preapproved');
            $fetpinfo['active'] = 'Y';
        }
    }
}

print json_encode(array('status' => $status, 'uinfo' => $fetpinfo));

?>
