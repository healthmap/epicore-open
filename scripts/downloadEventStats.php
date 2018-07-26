<?php
/**
 * User: jeffandre
 * Date: 3/25/16
 */

require_once "fileUtil.php";

saveEventStatsToCSV();

$status = 'success';

print json_encode($status);