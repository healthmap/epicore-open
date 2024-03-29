<?php
/*
 * Jeff Andre
 * June 7, 2017
 *
 * Saves RFI info in the database and sends email to selected members
*/

require_once "const.inc.php";
require_once "EventInfo.class.php";
require_once "UserInfo.class.php";
require_once "AWSMail.class.php";
// require_once 'ePush.class.php';
require_once "UserContoller3.class.php";

use UserController as userController;

$userData = userController::getUserData();

$formvars = json_decode(file_get_contents("php://input"));

// Save RFI in database and send to selected members
if ($formvars->fetp_ids && $formvars->population && $formvars->health_condition && $formvars->location && $formvars->purpose && $formvars->source) {

    // event info
    $event_info['latlon'] = (string)$formvars->location->latlon;
    $event_info['location'] = (string)$formvars->location->location;
    $event_info['location_details'] = (string)$formvars->location->location_details;
    $event_info['requester_id'] = (int)$userData["uid"];
    $event_info['search_countries'] = $formvars->search_countries ? $formvars->search_countries : '';
    $event_info['search_box'] = $formvars->search_box ? $formvars->search_box : '';
    $event_info['create_date'] = date('Y-m-d H:i:s');
    $event_info['personalized_text'] = $formvars->additionalText ? (string)$formvars->additionalText : '';
    $event_info['event_date'] = date_format(date_create($formvars->location->event_date), "Y-m-d");
    $event_info['event_date_details'] = (string)$formvars->location->event_date_details;
    $event_info['title'] = (string)$formvars->title;
    $fetp_ids = $formvars->fetp_ids;
    $duplicate_rfi_detected = (int)$formvars->duplicate_rfi_detected == 1;
    $duplicate_events = $formvars->duplicate_events ? $formvars->duplicate_events : false; // array of objects


    // related tables
    $event_table['health_condition'] = $formvars->health_condition;
    $event_table['population'] = $formvars->population;
    $event_table['purpose'] = $formvars->purpose;
    $event_table['source'] = $formvars->source;

    // insert event into database
    $event_result = EventInfo::insertEvent2($event_info, $event_table);
    $event_status = $event_result['status'];
    $event_id = $event_result['event_id'];
    $ei = new EventInfo($event_id);

    if ($event_status == 'success') {
        $subject = "EPICORE RFI #" . $event_id . " : " . $event_info['title'];

        // now send it to each FETP individually as they each need unique login token id
        $tokens = $ei->insertFetpsReceivingEmail($fetp_ids, 0);
        $fetp_emails = UserInfo::getFETPEmails($fetp_ids);
        $extra_headers['text_or_html'] = "html";

        $custom_vars['CONDITION_DETAILS'] = $formvars->health_condition_details;
        $emailtext = $ei->buildEmailForEvent($event_info, 'rfi2', $custom_vars, 'text');

        // set up push notification
        // $push = new ePush();
        // $pushevent['id'] = $event_id;
        // $pushevent['title'] = $event_info['title'];
        // $pushevent['type'] = 'RFI';

        foreach ($fetp_emails as $fetp_id => $recipient) {
            // send email
            $idlist[0] = $fetp_id;
            $extra_headers['user_ids'] = $idlist;
            $recipient = trim($recipient);
            $custom_emailtext = trim(str_replace("[TOKEN]", $tokens[$fetp_id], $emailtext));
            try {
                $aws_resp = AWSMail::mailfunc($recipient, $subject, $custom_emailtext, EMAIL_NOREPLY, $extra_headers);
            } catch (Exception $e) {}

            // send push notification
            //$push->sendPush($pushevent, $fetp_id);

        }

        // build copy email
        $proin_emailtext = $ei->buildEmailForEvent($event_info, 'rfi_proin2', $custom_vars, 'text');
        $moderator = $ei->getEventPerson($event_id); // get event moderator name
        $name = $moderator['name'];
        $email = $moderator['email'];
        $modfetp = "EpiCore asks for your assistance on the following RFI"; //"Requester: $name sent the following RFI";
        $custom_emailtext_proin = trim(str_replace("[PRO_IN]", $modfetp, $proin_emailtext));


        // send copy to pro-in for ProMED moderators only
        if ($moderator['organization_id'] == PROMED_ID) {
            $idlist[0] = PROMED_ID;
            $extra_headers['user_ids'] = $idlist;
            try {
                $aws_resp = AWSMail::mailfunc(EMAIL_PROIN, $subject, $custom_emailtext_proin, EMAIL_NOREPLY, $extra_headers);
            } catch (Exception $e) {}
        }

        // send copy to epicore info
        $idlist[0] = EPICORE_ID;
        $extra_headers['user_ids'] = $idlist;

        try {
            $aws_resp = AWSMail::mailfunc(EMAIL_INFO_EPICORE, $subject, $custom_emailtext_proin, EMAIL_NOREPLY, $extra_headers);
        } catch (Exception $e) {}
        
        // send copy to Epicore Admin if duplicate RFI detected
        if ($duplicate_rfi_detected) {
            $this_event_info = $ei->getInfo();
            $this_location = $this_event_info['location'];
            $this_population = $this_event_info['population'];
            $subject2 = "EPICORE RFI: Similar RFI(s) Detected";
            $admin_emailtext = $ei->buildEmailForEvent($event_info, 'rfi2_admin', $custom_vars, 'text');
            $match_location = "Matching country in location: " . $this_location;
            $match_population = "Matching population: " . $this_population;
            $custom_emailtext1 = trim(str_replace("[MATCHING_LOCATION]", $match_location, $admin_emailtext));
            $custom_emailtext2 = trim(str_replace("[MATCHING_POPULATION]", $match_population, $custom_emailtext1));
            $similar_message = "Similar RFI ID: $event_id.  Requester: $name sent the following RFI >";
            $custom_emailtext3 = trim(str_replace("[SIMILAR_RFI]", $similar_message, $custom_emailtext2));
            $duplicate_list = '';
            foreach($duplicate_events as $dup_event){
                $dup_title = $dup_event->title;
                $dup_id = $dup_event->id;
                $dup_url = EPICORE_URL . "/#/events2/" . $dup_id;
                $duplicate_list .= '<p><a style="margin:12px 0;" href= '.$dup_url.'>' . $dup_title . '</a></p>';
            }
            $custom_emailtext_last = trim(str_replace("[SIMILAR_RFI_LIST]", $duplicate_list, $custom_emailtext3));
            $idlist[0] = EPICORE_ID;
            $extra_headers['user_ids'] = $idlist;
            try {
                $aws_resp = AWSMail::mailfunc(EMAIL_EPICORE_ADMIN, $subject2, $custom_emailtext_last, EMAIL_NOREPLY, $extra_headers);
            } catch (Exception $e) {}
        }

        $status = 'success';
    } else {
        $status = $event_status;
        $fetp_ids = false;
    }

} else {
    $status = 'Missing parameters.';
    $fetp_ids = false;
}

print json_encode(array(
    'status' => $status,
    'fetps' => $fetp_ids,
    'event_id' => $event_id
));

?>
