<?php
/**
 *
 * Add Moderator.
 *
 */
<<<<<<< HEAD
// validate user
=======
require_once (dirname(__FILE__) ."/Service/AuthService.php");
require_once (dirname(__FILE__) ."/Service/ValidationService.php");
require_once (dirname(__FILE__) ."/Exception/PasswordValidationException.php");
require_once (dirname(__FILE__) ."/Exception/EmailValidationException.php");
require_once "send_email.php";
require_once "const.inc.php";
>>>>>>> epicore-ng/main

// get data
$data = json_decode(file_get_contents("php://input"));
$mod_email = strip_tags((string)$data->mod_email);
$mod_org_id = strip_tags((string)$data->mod_org_id);
<<<<<<< HEAD

// add mod
if ($mod_email && $mod_org_id) {
    require_once 'UserInfo.class.php';
    $mod_status = UserInfo::addMod($mod_email, $mod_org_id);
    if (is_numeric($mod_status)){
        $status = 'success';
        $mod_id = $mod_status;
    } else {
        $status = 'failed';
=======
$mod_name = strip_tags((string)$data->mod_name);
$mod_req_status = NEW_EPICORE_REQUESTER_STATUS;

// add mod
if (!empty($mod_email) && !empty($mod_org_id) && !empty($mod_name)) {
    require_once 'UserInfo.class.php';
    $mod_status = UserInfo::addMod($mod_email, $mod_org_id, $mod_name , Role::requester);

    if (is_numeric($mod_status)){
        try{
            //send welcome mail to requesters(epciore-welcome-email)
            sendMail($mod_email, $mod_name, "We heartily welcome our new EpiCore Member!", $mod_req_status, $mod_org_id);

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
            $status = 'failed: Cannot add new requester at this time.';
            $mod_id = $exception->getMessage();
        }
    } else {
        $status = 'failed: Unable to add new requester at this time.';
>>>>>>> epicore-ng/main
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

