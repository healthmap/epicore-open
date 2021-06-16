<?php

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Aws\Credentials\CredentialProvider;
use Aws\S3\S3Client;
 

require_once "db.function.php";
require_once 'const.inc.php';

if (file_exists("/usr/share/php/vendor/autoload.php")) {
    require_once '/usr/share/php/vendor/autoload.php';
}

// AWSMail::mailfunc('lyajurvedi@gmail.com','test subject','this is a test message','info@healthmap.org');


class AWSMail
{
    function mailfunc($to, $subject, $msg, $from, $extra_headers = array()) {
    	
        // Create an SesClient. Change the value of the region parameter .
        // Change the value of the profile parameter if you want to use a profile in your credentials file
        // other than the default.

        // Use the default credential provider
        $provider = CredentialProvider::defaultProvider();


        try {
            $SesClient = new SesClient([
                'profile' => 'default',
                'version' => '2010-12-01',
                'region'  => AWS_REGION,
                'credentials' =>  $provider
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

    }

}

?>