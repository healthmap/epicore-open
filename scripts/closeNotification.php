<?php
/**
 * User: jeffandre
 * Date: 2/17/17
 *
 * Sends notification emails to moderators that have RFIs past due closing.
 * Also closes past due RFI(s) with no responses.
 */

require_once "EventInfo.class.php";

// set due data to two weeks ago
$due_date = date("Y-m-d", strtotime("-2 weeks"));

// get mods with past due RFIs (events)
$mods = EventInfo::getModsWithInactiveEvents($due_date);

// send warning emails and close events
foreach ($mods as $mod){

        foreach ($mod['events'] as $event) {

            if ($event['responses'] == 0) {  // events with no responses
                sendMail($mod['email'], $mod['name'], "Epicore RFI has been closed", 'warning', $mod['user_id'], $event['title'], $event['date'], $event['event_id']);
                echo date("Y-m-d H:i:s") . ': Sent email to ' .$mod['email']. ' for auto-closed event id: ' . $event['event_id'] . "\n";
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
            } else { // events with repsonses
                sendMail($mod['email'], $mod['name'], "Epicore RFI past due closing", 'warning_responses', $mod['user_id'], $event['title'], $event['date'], $event['event_id']);
                echo date("Y-m-d H:i:s") . ': Sent email to ' .$mod['email']. ' to close past due event id: ' . $event['event_id'] . "\n";
            }
        }

}
