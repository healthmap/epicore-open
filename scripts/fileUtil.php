<?php
/**
 * User: jeffandre
 * Date: 9/22/15
 *
 * file utilities for members and event stats
 *
 */

function saveMembersToCSV(){
<<<<<<< HEAD
    // get all members
    require_once 'UserInfo.class.php';
    $members = UserInfo::getMembers();

    // save all members in a csv file
=======
    
    echo '----------------getMembers()-----------------------'. "\n";
    echo 'startTime:' . date("Y-m-d H:i:s"). "\n";
    // get all members
    require_once 'UserInfo.class.php';
    $members = UserInfo::getMembers(null, null);
    echo 'endTime:' . date("Y-m-d H:i:s"). "\n";
    echo '----------------getMembersInfo()-----------------------'. "\n";
    echo 'startTime:' . date("Y-m-d H:i:s"). "\n";
    // // save all members in a csv file
>>>>>>> epicore-ng/main
    $uinfo = new UserInfo('1', null);
    $membersInfo = $uinfo->getMembersInfo($members);
    $file = "../data/approval.csv";
    saveToCSV($membersInfo, $file);
<<<<<<< HEAD
=======
    echo 'endTime:' . date("Y-m-d H:i:s"). "\n";
>>>>>>> epicore-ng/main
}

function saveEventStatsToCSV() {
    // save event stats in a csv file
    require_once 'EventInfo.class.php';
    $filename = '../data/rfistats.csv';
    $uid = '91'; // Jeff, uid doesn't matter, any uid will work
<<<<<<< HEAD
    $close_stats = EventInfo::getEventStats2($uid, 'C');
    $open_stats = EventInfo::getEventStats2($uid, 'O');
    $stats = array_merge($close_stats, $open_stats);
    saveToCSV($stats, $filename);
=======
    echo '----------------getEventStats2 closed()-----------------------'. "\n";
    echo 'startTime:' . date("Y-m-d H:i:s"). "\n";
    $close_stats = EventInfo::getEventStats2($uid, 'C');
    echo '----------------getEventStats2 open()-----------------------'. "\n";
    $open_stats = EventInfo::getEventStats2($uid, 'O');
    $stats = array_merge($close_stats, $open_stats);
    saveToCSV($stats, $filename);
    echo 'endTime:' . date("Y-m-d H:i:s"). "\n";
>>>>>>> epicore-ng/main

}

function saveResponsesToCSV() {
    require_once 'EventInfo.class.php';
    $filename = '../data/responses.csv';
    $responses = EventInfo::getAllResponses();
    saveToCSV($responses, $filename);
}

function saveFollowupsToCSV() {
    require_once 'EventInfo.class.php';
    $filename = '../data/followups.csv';
    $followups = EventInfo::getAllFollowups();
    saveToCSV($followups, $filename);
}

// save data to a csv file using keys as header values
function saveToCSV($data, $filename){

    // save applicants to a csv
    $fp = fopen($filename, 'w');
    //$fp = fopen($filename, 'w');
    if ($fp) {

        // save keys as header values
        fputcsv($fp, array_keys($data[0]));

        // save data
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);
    }else{
        echo 'failed to open';
    }
}
