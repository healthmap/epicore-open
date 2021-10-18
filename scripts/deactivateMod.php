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

// get data
$data = json_decode(file_get_contents("php://input"));
$mod_user_id = strip_tags((string)$data->userId);
$mod_email = strip_tags((string)$data->userEmail);

// print_r(mod_user_id);
// print_r(mod_email);
// deactivate mod
if (!empty($mod_user_id) && !empty($mod_email)) {
    require_once 'UserInfo.class.php';
    $mod_status = UserInfo::deactivateMod($mod_user_id, Role::requester);

    if (is_numeric($mod_status)){
        $mods = UserInfo::getMods();
        try{
           
            $authService = new AuthService();
            $validationService = new ValidationService();

            $user = new User();
            $user->setEmail($mod_email);

            $validationService->email($user);
            $authService->DeleteUser($user->getEmail());

            $status = 'success';
            $mod_id = $mod_status;
        }
        catch (PasswordValidationException | UserAccountExistException | NoEmailProvidedException | Exception $exception) {
            $status = 'failed: Cannot deactivate requester at this time.';
            $message = $exception->getMessage();
        }
    } else {
        $status = 'failed: Unable to add new requester at this time.';
        $message = 'failed: Unable to add new requester at this time. ReqId:' . $mod_status;
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

