<?php
/**
 * User: jeffandre
 * Date: 10/22/15
 *
 * Approves users that have completed the training course at NextGenU.
 */
require_once 'UserInfo.class.php';

// get approved user email list from NextGenU api
$url = 'http://www.nextgenu.org/rest/v1/WwKiILITUxQuFHHDLXTtGXUBCMmMZARr/DIBqOsOupBDUxCDZRyxrhYsJKpvIkqta/course/193/certificates';
$curl_handle = curl_init();
curl_setopt($curl_handle,CURLOPT_URL,$url);
curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,2);
curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
$nextgenu = curl_exec($curl_handle);
curl_close($curl_handle);
$nextgenu_list = json_decode($nextgenu)->data;

// put nextgenu list into an array
$email_list = array();
$i=0;
foreach ($nextgenu_list as $email){
    $email_list[$i++] = $email->email;
}

// approve users in email list from nextGenU
foreach ($email_list as $approve_email) {
    print("$approve_email\n");

    // get user info
    $fetp_id = UserInfo::getFETPid($approve_email);
    $fetpinfo = UserInfo::getFETP($fetp_id);
    $userinfo = UserInfo::getUserInfobyEmail($approve_email);
    $approve_id = $userinfo['maillist_id'];

    // set user status approved if user exists and status is pending/accepted
    if ($approve_id && ($fetpinfo['status'] == 'P')) {
        UserInfo::setUserStatus($approve_id, 'approved');
    }

    // set user has taken online course
    if ($approve_id){
        $online = true;
        $inperson = false;
        UserInfo::setCourseType($approve_id, $online, $inperson);
    }
}
