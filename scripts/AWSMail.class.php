<?php
<<<<<<< HEAD
/*
example usage:
require_once "AWSMail.class.php";
AWSMail::mailfunc('susan.aman@childrens.harvard.edu','test subject','this is a test message','info@healthmap.org');
*/

require_once "/usr/share/php/AWSSDKforPHP/sdk.class.php";
require_once "db.function.php";
=======

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Aws\Credentials\CredentialProvider;

require_once (dirname(__FILE__) ."/common/AWSCredentialsProvider.php");

require_once "db.function.php";
require_once 'const.inc.php';

if (file_exists("/usr/share/php/vendor/autoload.php")) {
    require_once '/usr/share/php/vendor/autoload.php';
}

// AWSMail::mailfunc('lyajurvedi@gmail.com','test subject','this is a test message','info@healthmap.org');

>>>>>>> epicore-ng/main

class AWSMail
{
    function mailfunc($to, $subject, $msg, $from, $extra_headers = array()) {
<<<<<<< HEAD
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
=======
    	
        // Create an SesClient. Change the value of the region parameter .
        // Change the value of the profile parameter if you want to use a profile in your credentials file
        // other than the default.

        $AWSCredentialsProviderInstance = AWSCredentialsProvider::getInstance();
    
        try {
            $SesClient = new SesClient([
                // 'profile' => 'default',
                'version' => '2010-12-01',
                'region'  => AWS_REGION,
                'credentials' => $AWSCredentialsProviderInstance->fetchAWSCredentialsFromRole()
            ]);

            $to = is_array($to) ? $to : explode(",", $to);
            $ccAdrs = (array) null;
            $bccAdrs = (array) null;
            if(isset($extra_headers['cc'])) {
                $ccAdrs = is_array($extra_headers['cc']) ? $extra_headers['cc'] : explode(",", $extra_headers['cc']);
            } else {
                
            }
            if(isset($extra_headers['bcc'])) {
                $bccAdrs = is_array($extra_headers['bcc']) ? $extra_headers['bcc'] : explode(",", $extra_headers['bcc']);
            }
            $char_set = 'UTF-8';


            // Specify a configuration set. If you do not want to use a configuration
            // set, comment the following variable, and the
            // 'ConfigurationSetName' => $configuration_set argument below.
            // $configuration_set = 'ConfigSet';

        
            $result = $SesClient->sendEmail([
                'Destination' => [
                    'ToAddresses' => $to,
                    'CcAddresses' => $ccAdrs,
                    'BccAddresses' => $bccAdrs,
                ],
                'ReplyToAddresses' => [$from],
                'Source' => $from,
                'Message' => [
                  'Body' => [
                      'Html' => [
                          'Charset' => $char_set,
                          'Data' => $msg,
                      ],
                      'Text' => [
                          'Charset' => $char_set,
                          'Data' => $msg,
                      ],
                  ],
                  'Subject' => [
                      'Charset' => $char_set,
                      'Data' => $subject,
                  ],
                ],
                // If you aren't using a configuration set, comment or delete the
                // following line
                // 'ConfigurationSetName' => $configuration_set,
            ]);
            if($result['MessageId']) {
                $messageId = $result['MessageId'];
                // echo("Email sent! Message ID: $messageId"."\n"); //Email sent! Message ID: 010001783cabfadd-b1d2c851-61b5-47f6-b30c-54e1b17f7529-000000

                // log the request in the email log
                $db = getDB();
                $subject = 'Message ID:' . $messageId . ':::' . $subject;
                foreach($extra_headers['user_ids'] as $uid) {
                    $db->query("INSERT INTO emaillog (user_id, send_date, subject, content) VALUES (?, ?, ?, ?)", array($uid, date('Y-m-d H:i:s'), $subject, $msg));
                    $db->commit();
                }

            } else {
                // echo("Email sent! Message ID: $messageId"."\n");
                $result['ErrorMsg'] = 'Something went wrong while sending emails on' . date('Y-m-d H:i:s');
            }
            

        } catch (AwsException $e) {
            // output error message if fails
            //echo $e->getMessage();
            //echo("The email was not sent. Error message: ".$e->getAwsErrorMessage()."\n");
            $result['Error'] = 'Something went wrong while sending email';
            $result['ErrorMsg'] = $e->getMessage();
        }

        return $result;

>>>>>>> epicore-ng/main
    }

}

<<<<<<< HEAD
?>
=======
?>
>>>>>>> epicore-ng/main
