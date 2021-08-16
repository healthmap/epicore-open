<?php
/* reply to an RFI */
$formvars = json_decode(file_get_contents("php://input"));
require_once "EventInfo.class.php";
require_once "const.inc.php";
require_once "UserInfo.class.php";
require_once "UserContoller3.class.php";

use UserController as userController;

$userData = userController::getUserData();

$status = "error";

$event_id = $formvars->event_id;

if(is_numeric($event_id)) {
    // clean data
    $dbdata['responder_id'] = isset($formvars->anonymous) ? 0 : (int)$userData['fetp_id'];
    $dbdata['response'] = strip_tags($formvars->reply);
    $dbdata['response_permission'] = (int)$formvars->response_permission;
    $response_member = strip_tags($formvars->response_member);
    $dbdata['response'] = $response_member ? $dbdata['response'] . "\n\n Responding Member: " . $response_member : $dbdata['response'];
    $dbdata['source'] = $formvars->source;
    $filenames = isset($formvars->files) ? $formvars->files : 0;

    // insert into response table
    $ei = new EventInfo($event_id);
    $event_info = $ei->getInfo();
    $response_id = $ei->insertResponse2($dbdata);

    // save response file names
    if ($filenames){
        foreach ($filenames as $fname) {
            $rfilnename_id = $ei->saveResponseFileNames($response_id,$fname->savefilename);
        }
    }

    $subject = "EPICORE RFI #" . $event_id .  " - Response: " . $event_info['Title'];

    // send response to moderators if member had something to contribute
    if (($dbdata['response_permission']  != 0) || ($dbdata['response_permission']  != 4)) {
        // do this so you get the permission and text formatted correctly for the email
        $response_info = EventInfo::getResponse($response_id);
        $custom_vars['RESPONSE_PERMISSION'] = $response_info['response_permission'];
        $custom_vars['RESPONSE_TEXT'] = $response_info['response'];
        $custom_vars['RESPONSE_ID'] = $response_id;

        // build email
        $msg = $ei->buildEmailForEvent($event_info, 'response2', $custom_vars, 'text');
        $emailtext = trim(str_replace("[EVENT_HISTORY]", $history, $msg));
        $extra_headers['text_or_html'] = "html";
        require_once "AWSMail.class.php";

        // get moderator who initiated the event request
        $initiator = $ei->getInitiatorEmail();

        // get all moderators that sent followups for the event
        $moderators = $ei->getFollowupEmail();

        // make email to: list, and id list
        $tolist[0] = $initiator['email'];
        $idlist[0] = $initiator['user_id'];
        $i = 1;
        if (is_array($moderators) || is_object($moderators)) {
            foreach ($moderators as $moderator) {
                if ($moderator['email'] != $initiator['email']) {
                    $tolist[$i] = $moderator['email'];
                    $idlist[$i++] = $moderator['user_id'];
                }
            }
        }

        //get all fetp messages
        $history = $ei->getEventHistoryAll($event_id);

        // get event moderator info
        $emoderator = $ei->getEventPerson($event_id);
        // send a modified copy to pro-in for ProMED event moderator only
        if ($emoderator['organization_id'] == PROMED_ID) {
            array_push($tolist, EMAIL_PROIN);
            array_push($idlist, PROMED_ID);

        }
        // send copy to epicore info
        array_push($tolist, EMAIL_INFO_EPICORE);
        array_push($idlist, EPICORE_ID);

        // send copy to mods following the Event
        $followers = EventInfo::getFollowers($event_id);
        if (is_array($followers) || is_object($followers)) {
            foreach ($followers as $follower){
                array_push($tolist, $follower['email']);
                array_push($idlist, $follower['user_id']);
            }
        }

        $extra_headers['user_ids'] = $idlist;
        try {
            AWSMail::mailfunc($tolist, $subject, $emailtext, EMAIL_NOREPLY, $extra_headers);
        } catch (Exception $e) {}
    }

    $status = "success";

}


print json_encode(array(
    'status' => $status,
    'dbdata' => $dbdata,
    'response_id' => $response_id
));

?>
