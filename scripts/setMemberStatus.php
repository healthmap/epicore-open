<?php
/**
 * User: jeffandre
 * Date: 3/28/16
 *
 * Sets and returns member status.
 *
 */
$status = 'success';
$message = '';
$member_status = '';
require_once (dirname(__FILE__) ."/Service/AuthService.php");
require_once (dirname(__FILE__) ."/Exception/UserAccountExistException.php");

// set member status
$data = json_decode(file_get_contents("php://input"));
$approve_id = strip_tags((string)$data->maillist_id);
$approve_status = strip_tags((string)$data->action);
if (!empty($approve_id) && !empty($approve_status)) {
    require_once 'UserInfo.class.php';
    UserInfo::setUserStatus($approve_id, $approve_status);
    $member_status = UserInfo::getMemberStatus($approve_id);
    if (!$member_status){
        $status = 'failed';
        $message = 'member not found';
    }
    $cognitoCommand = false;
    switch ($member_status)
    {
        case 'Pending':
        case 'Approved':
        case 'Pre-approved':
            $cognitoCommand = true;
            break;
    }
    if($cognitoCommand)
    {
        $maillist = UserInfo::getMaillistDetails($approve_id);
        $authService = new AuthService();
        $validationService = new ValidationService();

        $user = new User();
        $user->setEmail($maillist['email']);

        try {
            // TODO valid email
            $validationService->email($user);

            // TODO send user to AWS Cognito
            $authService->singUp($user->getEmail());
        }
        catch (\UserAccountExistException $exception)
        {
            try {
                // TODO delete user on AWS Cognito
                $authService->DeleteUser($user->getEmail());
                // TODO send user to AWS Cognito
                $authService->singUp($user->getEmail());
            }catch (Exception $exception){
                $status = 'failed';
                $message = $exception->getMessage();
                error_log($exception->getMessage());
            }
        }
        catch (\NoEmailProvidedException $exception)
        {
            $status = 'failed';
            error_log($exception->getMessage());
            $message = $exception->getMessage();
        }
        catch (\Exception $exception)
        {
            $status = 'failed';
            $message = 'invalid paramters';
            error_log($exception->getMessage());
        }
    }

} else{
    $status = 'failed';
    $message = 'invalid paramters';
}

// return member status or error message
if($status == 'success') {
    print json_encode(array('status' => 'success', 'member_status' =>$member_status));
} else {
    print json_encode(array('status' => 'failed', 'message' => $message));
}

