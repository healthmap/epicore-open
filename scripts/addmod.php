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

// get data
$data = json_decode(file_get_contents("php://input"));
$mod_email = strip_tags((string)$data->mod_email);
$mod_org_id = strip_tags((string)$data->mod_org_id);
$mod_name = strip_tags((string)$data->mod_name);
// add mod
if ($mod_email && $mod_org_id && $mod_name) {
    require_once 'UserInfo.class.php';
    $mod_status = UserInfo::addMod($mod_email, $mod_org_id, $mod_name , Role::requester);
    if (is_numeric($mod_status)){
        try{
            $authService = new AuthService();
            $validationService = new ValidationService();

            $user = new User();
            $user->setEmail($mod_email);

            $validationService->email($user);
            $authService->SingUp($user->getEmail());

            $status = 'success';
            $mod_id = $mod_status;
        }
        catch (PasswordValidationException | UserAccountExistException | NoEmailProvidedException | Exception $exception) {
            $status = 'failed';
            $mod_id = $exception->getMessage();
        }
    } else {
        $status = 'failed';
        $message = $mod_status;
    }
} else{
    $status = 'failed';
    $message = 'invalid parameters';
}

// return mod id or error status
if($status == 'success') {
    print json_encode(array('status' => 'success', 'mod_id' =>$mod_id));
} else {
    print json_encode(array('status' => 'failed', 'message' => $message));
}

