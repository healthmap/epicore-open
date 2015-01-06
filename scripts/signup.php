<?php 
// clearn variables
foreach($_POST as $pkey => $pval) {
    if(!is_array($pval)) {
        $pkey = strip_tags($pkey);
        $pvals[$pkey] = strip_tags($pval);
    }
}
// exit if no email
if(!$pvals['email']) {
    print "No email specified";
    exit;
}
require_once "../db.function.php";
$db = getDB();
// check if user already in db
$user_id = $db->getOne("SELECT user_id FROM user WHERE email = ?", array($pvals['email']));
if(!$user_id) { // insert if not
    $key_vals = join(",", array_keys($pvals));
    $qmarks = join(",", array_fill(0, count($pvals), '?'));
    $qvals = array_values($pvals);
    $db->query("INSERT INTO user ($key_vals) VALUES ($qmarks)", $qvals);
    $user_id = $db->getOne("SELECT LAST_INSERT_ID()");
}
// at this point, should have a user_id
if(!$user_id) {
    print "No user_id";
    exit;
}
// loop through expertise and insert in table
foreach($_POST['expertisearr'] as $eid) {
    if(is_numeric($eid)) {
        $ueid = $db->getOne("SELECT user_expertise_id FROM user_expertise WHERE user_id = ? AND expertise_id = ?", array($user_id, $eid));
        if(!$ueid) { 
            $db->query("INSERT INTO user_expertise (user_id, expertise_id) VALUES (?, ?)", array($user_id, $eid));
        }
    }
}
$db->commit();
header("Location: ../success");
?>
