<?
/*
* Sue Aman 13 June, 2014
* get alert by alert id
* this is for requests that come directly from the ProMED dashboard
*/
require_once "const.inc.php";
$formvars = json_decode(file_get_contents("php://input"));

if(!isset($formvars->alert_id) || !is_numeric($formvars->alert_id)) {
    print json_encode(array('status' => 'failed', 'reason' => 'missing required fields'));
    exit;
} 

require_once "AlertInfo.class.php";
$ai = new AlertInfo($formvars->alert_id);
$alert_info = $ai->getInfo();
print json_encode($alert_info);
exit;

?>
