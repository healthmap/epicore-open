<?php

require_once "const.inc.php";
require_once "EventInfo.class.php";

$formvars = json_decode(file_get_contents("php://input"));

$event_metrics_id = (int)$formvars->event_metrics_id;
$event_id = (int)$formvars->event_id;
$score = (int)$formvars->score;
$creation = $formvars->creation;
$notes = $formvars->notes;
$action = $formvars->action;

$event_metrics = array();
$event_metrics['event_metrics_id'] = $event_metrics_id;
$event_metrics['event_id'] = $event_id;
$event_metrics['score'] = $score;
$event_metrics['creation'] = $creation;
$event_metrics['notes'] = $notes;
$event_metrics['action'] = $action;

if (is_numeric($event_metrics['event_id']) && $event_metrics['event_id'] > 0) {

    $table_id = EventInfo::updateEventMetrics($event_metrics);

    if (is_numeric($table_id))
        print json_encode(array('status' => 'success'));
    else
        print json_encode(array('status' => 'failed', 'reason' => $table_id));

} else
    print json_encode(array('status' => 'failed', 'reason' => 'Invalid parameters', 'parameters' => $score));

?>
