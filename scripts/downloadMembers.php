<?php
/**
 * User: jeffandre
 * Date: 3/25/16
 */

require_once "fileUtil.php";

saveMembersToCSV();

$status = 'success';

print json_encode($status);