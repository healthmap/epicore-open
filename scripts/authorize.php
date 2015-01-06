<?
/* 
Epicore authorize with Tephinet 
This page gets hit by Tephinet when the user authorizes the request
takes the key/secret sent by tephinet and sends it back to get the user associated with that token
*/
require_once "const.inc.php";

// make sure request came from Tephinet
$base_url = TEPHINET_BASE;
if(strpos($_SERVER['HTTP_REFERER'], "$base_url") === FALSE) {
    print "Sorry!";
    exit;
}

$rt = strip_tags($_GET['rt']);
$rts = strip_tags($_GET['rts']);
if(!$rt || !$rts) {
    print "no request token";
    exit;
}
require_once "GetURL.class.php";
require_once "db.function.php";
$ck = TEPHINET_CONSUMER_KEY;
$cks = TEPHINET_CONSUMER_SECRET;
$webservice = TEPHINET_BASE . 'epicore/oauth_final/' . $ck . '/' . $cks . '/' . $rt . '/' . $rts;
$gurl = GetURL::getInstance();
$returnval = json_decode($gurl->get($webservice));
print_r($returnval);

// turn the user id into a ticket id and redirect to fetp/:tid
if($returnval->user) {
    $db = getDB();
    $ticket = md5(uniqid(rand(), true));
    $db->query("INSERT INTO ticket (fetp_id, val, exp) VALUES (?, ?, ?)", array($returnval->user, $ticket, date('Y-m-d H:i:s', strtotime("+10 days"))));
    $db->commit();
    header("Location: ../#/fetp/$ticket");
} else {
    header("Location: ../#/home");
}
?>
