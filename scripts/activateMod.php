<?php
/**
 *
 * Add Moderator.
 *
 */
require_once (dirname(__FILE__) ."/Service/AuthService.php");
require_once (dirname(__FILE__) ."/Service/ValidationService.php");
require_once (dirname(__FILE__) ."/Exception/PasswordValidationException.php");
require_once (dirname(__FILE__) ."/Exception/EmailValidationException.php");
require_once "const.inc.php";
require_once "send_email.php";

// get data
$data = json_decode(file_get_contents("php://input"));
$mod_user_id = strip_tags((string)$data->userId);
$mod_email = strip_tags((string)$data->userEmail);
$mod_req_status = NEW_EPICORE_REQUESTER_STATUS;


// print_r(mod_user_id);
// print_r(mod_email);
// activate mod
if (!empty($mod_user_id) && !empty($mod_email)) {
    require_once 'UserInfo.class.php';
    $mod_status = UserInfo::activateMod($mod_user_id, Role::requester);

    $modStatus_uId = $mod_status[0];
    $modStatus_orgId = $mod_status[1];

    if (is_numeric($modStatus_uId )){
        $mods = UserInfo::getMods();
        try{
            //send welcome mail to requesters(epciore-welcome-email)
            sendMail($mod_email, $mod_name, "We heartily welcome our new EpiCore Member!", $mod_req_status, $modStatus_orgId);

            $authService = new AuthService();
            $validationService = new ValidationService();

            $user = new User();
            $user->setEmail($mod_email);

            $validationService->email($user);
            $authService->SingUp($user->getEmail());

            $status = 'success';
            $mod_id = $modStatus_uId;
        }
        catch (PasswordValidationException | UserAccountExistException | NoEmailProvidedException | Exception $exception) {
            $status = 'failed: Cannot add new requester at this time.';
            $mod_id = $exception->getMessage();
        }
    } else {
        $status = 'failed: Unable to add new requester at this time.';
        $message = $mod_status;
    }

} else{
    $status = 'failed';
    $message = 'invalid parameters';
}

// return mod id or error status
if($status == 'success') {
    print json_encode(array('status' => 'success', 'mod_id' =>$mod_id , 'mods' =>$mods));
} else {
    print json_encode(array('status' => 'failed', 'message' => $message));
}


