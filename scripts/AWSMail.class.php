<?php
/*
example usage:
require_once "AWSMail.class.php";
AWSMail::mailfunc('susan.aman@childrens.harvard.edu','test subject','this is a test message','info@healthmap.org');
*/
 
require_once "db.function.php";
// require_once "/usr/share/php/AWSSDKforPHP/sdk.class.php";

//LOCAL-SETUP ONLY-Uncomment following
require_once 'const.inc.php';
// if(ENVIRONMENT == 'Local'){
// require_once "../AWSSDKforPHP/sdk.class.php";
// } else {
// require_once "/usr/share/php/AWSSDKforPHP/sdk.class.php";
// }


class AWSMail
{
    function mailfunc($to, $subject, $msg, $from, $extra_headers = array()) {
    	$email = new AmazonSES();
	    $text_or_html = isset($extra_headers['text_or_html']) ? ucfirst(strtolower($extra_headers['text_or_html'])) : 'Text';
	    $email_type = 'Body.' . $text_or_html . '.Data';
        $to = is_array($to) ? $to : explode(",", $to);
        $toaddresses = array('ToAddresses' => $to);
	    if(isset($extra_headers['cc'])) {
	        $toaddresses['CcAddresses'] = is_array($extra_headers['cc']) ? $extra_headers['cc'] : explode(",", $extra_headers['cc']);
	    }
	    if(isset($extra_headers['bcc'])) {
	        $toaddresses['BccAddresses'] = is_array($extra_headers['bcc']) ? $extra_headers['bcc'] : explode(",", $extra_headers['bcc']);
	    }
    	$response = $email->send_email(
	    $from,
	    $toaddresses,
            array(
                'Subject.Data' => $subject,
                $email_type => $msg
            )
        );
   
        // log the request in the email log
        $db = getDB();
        foreach($extra_headers['user_ids'] as $uid) {
            $db->query("INSERT INTO emaillog (user_id, send_date, subject, content) VALUES (?, ?, ?, ?)", array($uid, date('Y-m-d H:i:s'), $subject, $msg));
            $db->commit();
        }

 
        return $response;
    }

}

?>
