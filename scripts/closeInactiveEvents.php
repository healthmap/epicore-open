<?php
/**
 * User: jeffandre
 * Date: 8/31/17
 *
 * Closes Events with no responses or no contribution, and no active searches after 5 days.
 *
 * Sends warning email to responders for events with active searches (no content) after 5 days.
 *
 * Runs every day.
 */

require_once "EventInfo.class.php";

// set date to 5 days ago
$date = date("Y-m-d", strtotime("-5 days"));

// get mods with inactive events for closing
$mods = EventInfo::getModsWithInactiveEvents2($date);

// close inactive events and send warning email to responders with active searches
foreach ($mods as $mod){

    foreach ($mod['events'] as $event) {

        if ($event['responses'] == 0 && $event['active_search'] == 0) {  // close events with responses with no content and no active search

            //print_r($event); // test

            echo date("Y-m-d H:i:s") . ': Sent email to ' .$mod['email']. ' for auto-closed event id: ' . $event['event_id'] . "\n";

            // send email to mod
            sendMail($mod['email'], $mod['name'], "Epicore RFI " . $event['event_id'] . " has been closed", 'warning2', $mod['user_id'], $event['title'], $event['date'], $event['event_id']);
            // get event
            $ei = new EventInfo($event['event_id']);
            $event_info = $ei->getInfo();
            // close event
            $notes = 'Auto Closed';
            $reason = '';
            $status = 'C';
            $return_val = $ei->changeStatus($status, $mod['user_id'], $notes, $reason);
            if ($return_val == 1) {
                echo date("Y-m-d H:i:s") . ': Auto-closed event id: ' . $event['event_id'] . "\n";
            }
            else {
                echo date("Y-m-d H:i:s") . ': Error auto-closing event id: ' . $event['event_id'] . "\n";
            }
            // change outcome to Unverified
            $outcome = array();
            $outcome['outcome'] = 'UV'; // Unverified
            $outcome['event_id'] = $event['event_id'];
            EventInfo::updateOutcome($outcome);

        } else if ($event['responses'] == 0 && $event['active_search'] > 0) {  // send warning email for only active search responses and no content

            //print_r($event); // test

            // get active search responders
            $responders = EventInfo::getActiveSearchResponders($event['event_id']);

            // send warning email to responders with active searches.
            foreach ($responders as $responder){
                echo date("Y-m-d H:i:s") . ': Sent email to ' .$responder['email']. ' for Active Search Warning, event id: ' . $event['event_id'] . "\n";

                // send email
                sendMail($responder['email'], '', "Epicore RFI " . $event['event_id'] . " Active Search Reminder", 'active_search_warning', $responder['responder_id'], $event['title'], '', '');
            }

        }
    }

}

