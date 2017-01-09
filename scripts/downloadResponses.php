<?php
/**
 * User: jeffandre
 * Date: 1/9/17
 */

require_once "fileUtil.php";

saveResponsesToCSV();

$status = 'success';

print json_encode($status);