<?php
/**
 * User: jeffandre
 * Date: 4/6/18
 *
 * remove response files.
 */
require_once 'const.inc.php';

$formvars = json_decode(file_get_contents("php://input"));
$filename = isset($formvars->filename) ? $formvars->filename : '';

$destination = "../" . RESPONSEFILE_DIR . $filename;

$status = unlink($destination);

if ($status) {
$response=array("status"=>1,"message"=>"File removed: " . $filename);
} else {
    $response=array("status"=>0,"message"=>"File not removed: " . $filename);
}
print json_encode($response);
exit;
