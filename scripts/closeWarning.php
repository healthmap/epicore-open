<?php
/**
 * User: jeffandre
 * Date: 8/31/2017
 *
 * Sends notification emails to Requester's with Events past due closing that have responses with content.
 *
 * Run once a week.
 *
 */

require_once "EventInfo.class.php";

// set due date to 14 days ago
$due_date = date("Y-m-d", strtotime("-14 days"));

// get mods with inactive events for
$mods = EventInfo::getModsWithInactiveEvents2($due_date);

// send warning emails to close events
foreach ($mods as $mod){

        foreach ($mod['events'] as $event) {

            if ($event['responses'] > 0) {  // events with responses with content.

                //print_r($event);

                echo date("Y-m-d H:i:s") . ': Sent email to ' .$mod['email']. ' to close past due event id: ' . $event['event_id'] . "\n";

                // comment out for testing
                sendMail($mod['email'], $mod['name'], "Epicore RFI past due closing", 'warning_responses2', $mod['user_id'], $event['title'], $event['date'], $event['event_id']);

            }
        }

}
