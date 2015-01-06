<?
/*
example usage:
require_once "AWSMail.class.php";
AWSMail::mailfunc('susan.aman@childrens.harvard.edu','test subject','this is a test message','info@healthmap.org');
*/

require_once "/usr/share/php/AWSSDKforPHP/sdk.class.php";

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
    }
}

?>
