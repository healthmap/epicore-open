<?php
/**
 * User: jeffandre
 * Date: 1/9/17
 */

require_once "fileUtil.php";

saveFollowupsToCSV();

$status = 'success';

print json_encode($status);