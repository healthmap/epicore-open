<?php

/**
 * Do not delete this as we may use it once our mobile rollout is active
 * 
 *
 * Push class for iOS and Android push notifications.
 *
 */
// require_once '/usr/share/php/vendor/autoload.php';
// require_once '../scripts/db.function.php';
// require_once '../scripts/UserInfo.class.php';

// use Sly\NotificationPusher\PushManager,
//     Sly\NotificationPusher\Adapter\Apns as ApnsAdapter,
//     Sly\NotificationPusher\Collection\DeviceCollection,
//     Sly\NotificationPusher\Model\Device,
//     Sly\NotificationPusher\Model\Message,
//     Sly\NotificationPusher\Model\Push
//     ;


// class ePush
// {

//     function __construct()
//     {

//         // iOS
//         // First, instantiate the apns manager for production environment:
//         $this->ios_PushManager = new PushManager(PushManager::ENVIRONMENT_PROD);

//         // Then declare an adapter with pem file generated with Apple Dev console and Keychain Access
//         $this->ios_apnsAdapter = new ApnsAdapter(array(
//             'certificate' => '../scripts/conf/push-epicore.pem',
//         ));

//     }

//     // send push notification if user has a mobile device
//     // $user_id: fetp_id
//     // $type: 'RFI', 'FOLLOWUP', 'CLOSED', 'REOPEN'
//     // Returns collection of notified devices for iOS or false if not iOS or Android
//     public function sendPush($event, $user_id) {

//         // get user's mobile registration id and platform
//         $mobileinfo = UserInfo::getMemberMobileInfo($user_id);
//         $reg_id = $mobileinfo['reg_id'];
//         $platform = $mobileinfo['platform'];

//         if ($reg_id && $platform) {
//             $status = false;
//             switch ($platform) {
//                 case "iOS":
//                     $status = $this->sendiOSPush($event, $reg_id);
//                     break;
//                 case "Android":
//                     $status = $this->sendAndroidPush($event, $reg_id);
//                     break;
//             }
//             return $status;
//         } else
//             return false;
//     }

//     // send push notification for iOS using APNS
//     // Returns collection of notified devices
//     protected function sendiOSPush($event, $reg_id){

//         // Set the device(s) to push the notification to.
//         $devices = new DeviceCollection(array(
//             new Device($reg_id)
//         ));

//         // Then, create the push skel.
//         switch ($event['type']) {
//             case "RFI":
//                 $message = new Message("RFI #". $event['id'] . ': '. $event['title'], array(
//                     'badge' => 1
//                 ));
//                 break;
//             case "FOLLOWUP":
//                 $message = new Message("Followup for RFI #" . $event['id'] . ': ' . $event['title'], array(
//                     'badge' => 1
//                 ));
//                 break;
//             case "CLOSED":
//                 $message = new Message("Closed RFI #" . $event['id'] . ': ' . $event['title'], array(
//                     'badge' => 1
//                 ));
//                 break;
//             case "REOPENED":
//                 $message = new Message("Re-Opened RFI #" . $event['id'] . ': ' . $event['title'], array(
//                     'badge' => 1
//                 ));
//                 break;
//         }

//         // Finally, create and add the push to the manager, and push it!
//         $push = new Push($this->ios_apnsAdapter, $devices, $message);
//         $this->ios_PushManager->add($push);
//         return $this->ios_PushManager->push();  // returns collection of notified devices

//     }

//     // send push notification for Android using Google Firebase Cloud Messaging
//     // Returns status of push notification (string value)
//     protected function sendAndroidPush($event, $reg_id) {

//         // Create event message
//         $message = '';
//         switch ($event['type']) {
//             case "RFI":
//                 $message = "RFI #". $event['id'] . ': '. $event['title'];
//                 break;
//             case "FOLLOWUP":
//                 $message = "Followup for RFI #" . $event['id'] . ': ' . $event['title'];
//                 break;
//             case "CLOSED":
//                 $message = "Closed RFI #" . $event['id'] . ': ' . $event['title'];
//                 break;
//             case "REOPEN":
//                 $message = "Re-Opened RFI #" . $event['id'] . ': ' . $event['title'];
//                 break;
//         }

//         // Android message format
//         $android_msg = array(
//             'title' => 'Epicore',
//             'body' => $message,
//             'sound' => 1
//         );

//         // notification message
//         $fields = array(
//             'to' => $reg_id,
//             'notification'  => $android_msg
//         );

//         // header
//         $headers = array(
//             'Authorization: key=' .FCM_SERVER_KEY,
//             'Content-Type: application/json'
//         );

//         // send push notification with Google Firbebase Cloud Messaging (FCM)
//         $ch = curl_init();
//         curl_setopt($ch, CURLOPT_URL, FCM_SEND_URL);
//         curl_setopt($ch, CURLOPT_POST, true);
//         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
//         $result = curl_exec($ch);
//         curl_close($ch);
//         return $result;

//     }


// }
?>
