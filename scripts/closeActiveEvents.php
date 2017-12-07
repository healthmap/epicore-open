<?php
/**
 * User: jeffandre
 * Date: 8/31/17
 *
 * Closes Events that have only Active search responses after a set date.
 *
 * Run every day.
 */

require_once "EventInfo.class.php";

// set date to 5 days ago
$date = date("Y-m-d", strtotime("-5 days"));

// get mods with inactive events for closing
$mods = EventInfo::getModsWithInactiveEvents2($date);

// close inactive events
foreach ($mods as $mod){

    foreach ($mod['events'] as $event) {

        if ($event['responses'] == 0 && $event['active_search'] > 0) {  // responses with active search only

            //print_r($event); // test

            echo date("Y-m-d H:i:s") . ': Sent email to ' .$mod['email']. ' for auto-closed Active Search event id: ' . $event['event_id'] . "\n";


            // send email to mod
            sendMail($mod['email'], $mod['name'], "Epicore RFI has been closed", 'warning2', $mod['user_id'], $event['title'], $event['date'], $event['event_id']);
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

        }
    }

}

