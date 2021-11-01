<?php
/**
 * User: jeffandre
 * Date: 4/4/18
 *
 * upload response files.
 */

require_once 'const.inc.php';

$filename = $_FILES['file']['name'];
$savefilename = $_POST['event_id'] . "_" . $_POST['fetp_id'] . "." . $_FILES['file']['name'];

$destination = "../" . RESPONSEFILE_DIR . $savefilename;
$status = move_uploaded_file( $_FILES['file']['tmp_name'] , $destination );
<<<<<<< HEAD

=======
>>>>>>> epicore-ng/main
if ($status) {
$response=array("status"=>1,"message"=>"File Uploaded: " . $filename, "filename"=>$filename, "savefilename"=>$savefilename);
} else {
    $response=array("status"=>0,"message"=>"File Not Uploaded: " . $filename);
}
print json_encode($response);
exit;
