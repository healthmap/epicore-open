<?php

/**
 * API endpoint for confirm Cognito account and start process for update new update for AWS Cognito
 */
require_once (dirname(__FILE__) ."/Service/AuthService.php");
require_once (dirname(__FILE__) ."/Model/ApiResponseStatus.php");
require_once "UserInfo.class.php";

$formvars = json_decode(file_get_contents("php://input"));
$status = ApiResponseStatus::success;
$fetpinfo = null;

$username = strip_tags($formvars->username);
$verifycode = strip_tags($formvars->verifycode);
$newpassword = strip_tags($formvars->newpassword);

$message = null;
$fetpStatus = null;

if(!empty($username) && !empty($verifycode))
{
    $authService = new AuthService();
    try
    {
        $message = $authService->ConfirmAccount($username , $verifycode , $newpassword);
        
        //POST Cognito confirm account - Update fetp for Pre-Approved responders to active status
        $fetpStatus = 'failed';
    
        $authfetp = UserInfo::getFETPid($username); //get fetpID
        $fetpinfo = UserInfo::getFETP($authfetp); //get * from fetp
        $fetpinfo['username'] = "MEMBER ". $authfetp;
        
        // check if username matches authenticated email
        $emailmatch = (strcasecmp($fetpinfo['email'], $username) == 0) ? true: false;
    
        if(is_numeric($authfetp) && ($authfetp > 0) && $emailmatch) {
            // $password_set = UserInfo::setFETPpassword($authfetp['fetp_id'],$password); //not reqd as password is stored in cognito
            // if ($password_set){
                $fetpStatus = 'success';
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
            //}
        }


    }
    catch (\UserIsConfirmed $exception)
    {
        $status = ApiResponseStatus::goToLogin;
    }
    catch (\PasswordValidationException | Exception $exception)
    {
        $message = $exception->getMessage();
        $status = ApiResponseStatus::failed;
    }

}
  
print json_encode(array('status' => $status, 'uinfo' => $fetpinfo, 'message' => $message, 'fetpUpdateStatus' => $fetpStatus));
