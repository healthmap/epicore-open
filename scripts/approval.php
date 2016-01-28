<?php
/**
 * User: jeffandre
 * Date: 9/22/15
 *
 * Sets user status and sends email.
 * Returns all users info.
 * Also saves data in a csv file.
 *
 */
require_once "const.inc.php";
require_once "AWSMail.class.php";
require_once 'db.function.php';
require_once 'UserInfo.class.php';
require_once "send_email.php";

$db = getDB();

// get applicant and set status
$data = json_decode(file_get_contents("php://input"));
$approve_id = (string)$data->maillist_id;
$approve_status = (string)$data->action;
UserInfo::setUserStatus($approve_id, $approve_status);


// get all applicants and fetps
$applicants = $db->getAll("select * from maillist");
$fetps = $db->getAll("select * from fetp");

// set all applicants status based on applicant approvestatus and fetp active/status fields
// approvestatus    fetp-active  fetp-status     app-status
// 'N'              x               x             Declined
//  not N           null            null          Inactive
//  not N           'N'              P            Pending         Pending training
//  not N           'Y'              A            Approved        Finished training
//  not N           'N'              A            Unsubscribed    Unsubscribed
$applicant_status = [];
$n = 0;
foreach ($applicants as $applicant){
    $applicants[$n]['status'] = 'Inactive';
    if ($applicants[$n]['approvestatus'] == 'N'){
        $applicants[$n]['status'] = 'Denied';
    }
    else {
        foreach ($fetps as $fetp) {
            $emailmatch = (strcasecmp($fetp['email'], $applicant['email']) == 0);
            if ($emailmatch && ($fetp['active'] == 'N') && ($fetp['status'] == "A")) {
                $applicants[$n]['status'] = "Unsubscribed";
            } else if ($emailmatch && ($fetp['active'] == 'N') && ($fetp['status'] == "P")) {
                $applicants[$n]['status'] = "Pending";
            } else if ($emailmatch && ($fetp['active'] == 'Y') && ($fetp['status'] == "A")) {
                $applicants[$n]['status'] = "Approved";
            }
            if ($emailmatch) {
                $applicants[$n]['pword'] = $fetp['pword_hash'] ? 'Yes' : null;
                $applicants[$n]['member_id'] = $fetp['fetp_id'];;

            }
        }
    }
    $applicants[$n]['apply_date_iso'] = $applicants[$n]['apply_date'];
    $applicants[$n]['approve_date_iso'] = $applicants[$n]['approve_date'];
    $applicants[$n]['accept_date_iso'] = $applicants[$n]['accept_date'];
    $applicants[$n]['apply_date'] = date('j-M-Y', strtotime($applicants[$n]['apply_date']));
    $applicants[$n]['approve_date'] = $applicants[$n]['approve_date'] ?  date('j-M-Y', strtotime($applicants[$n]['approve_date'])) : $applicants[$n]['approve_date'];
    $applicants[$n]['accept_date'] = $applicants[$n]['accept_date'] ?  date('j-M-Y', strtotime($applicants[$n]['accept_date'])) : $applicants[$n]['accept_date'];
    $applicants[$n]['country'] = $countries[$applicants[$n]['country']];
    $n++;
}

// return all applicants
print json_encode($applicants);


// save applicants to a csv
$fp = fopen('../data/approval.csv', 'w');
$user = array();
//fputcsv($fp, array_keys($applicants[0]));   // save keys as header values
$n=0;
foreach($applicants as $applicant){
    $user['Application Date'] = $applicant['apply_date'];
    $user['Approval Date'] = $applicant['approve_date'];
    $user['Acceptance Date'] = $applicant['accept_date'];
    $user['Name'] = $applicant['firstname'] . ' ' . $applicant['lastname'];
    $user['email'] = $applicant['email'];
    $user['Member ID'] = $applicant['member_id'];
    $user['City'] = $applicant['city'];
    $user['State/Province'] = $applicant['state'];
    $user['Country'] = $applicant['country'];
    $user['Job Title'] = $applicant['job_title'];
    $user['Organization'] = $applicant['organization'];
    //Sector
    $user['Sector'] = '';
    if ($applicant['sector'] == 'G')
        $user['Sector'] = 'Governmental';
    elseif ($applicant['sector'] == 'N')
        $user['Sector'] = 'Non-governmental/Nonprofit';
    elseif ($applicant['sector'] == 'P')
        $user['Sector'] = 'Private';
    // Organization Category
    $user['Organization Category'] = '';
    if ($applicant['health_org_university'])
        $user['Organization Category'] = 'University or any academic or research institution';
    elseif ($applicant['health_org_doh'])
        $user['Organization Category'] = 'Ministry / Department of Health';
    elseif ($applicant['health_org_clinic'])
        $user['Organization Category'] = 'Medical clinic';
    elseif ($applicant['health_org_other'])
        $user['Organization Category'] = 'Other health-related organizations';
    elseif ($applicant['health_org_none'])
        $user['Organization Category'] = 'No category';


    //Degrees
    $user['Degrees'] = '';
    if ($applicant['bachelors_type'])
        $user['Degrees'] = $applicant['bachelors_type'] . ', ';
    if ($applicant['gradstudent_type'])
        $user['Degrees'] .= $applicant['gradstudent_type'] . ', ';
    if ($applicant['masters_type'])
        $user['Degrees'] .= $applicant['masters_type'] . ', ';
    if ($applicant['medical_type'])
        $user['Degrees'] .= $applicant['medical_type'] . ', ';
    if ($applicant['doctorate_type'])
        $user['Degrees'] .= $applicant['doctorate_type'] . ', ';
    if ($applicant['otherdegree_type'])
        $user['Degrees'] .= $applicant['otherdegree_type'] . ', ';

    // Universities
    $user['Universities'] = $applicant['universities'];

    // Universities 1-3
    $user['University1'] = $applicant['university1'];
    $user['Country1'] = $applicant['school_country1'];
    $user['Major1'] = $applicant['major1'];
    $user['Degree1'] = $applicant['degree1'] ? $applicant['degree1']: $applicant['other_degree1'];
    $user['University2'] = $applicant['university2'];
    $user['Country2'] = $applicant['school_country2'];
    $user['Major2'] = $applicant['major2'];
    $user['Degree2'] = $applicant['degree2'] ? $applicant['degree2']: $applicant['other_degree2'];
    $user['University3'] = $applicant['university3'];
    $user['Country3'] = $applicant['school_country3'];
    $user['Major3'] = $applicant['major3'];
    $user['Degree3'] = $applicant['degree3'] ? $applicant['degree3']: $applicant['other_degree3'];


    // Basic Knowledge
    $user['Basic Knowledge'] = '';
    if ($applicant['clinical_med_adult'])
        $user['Basic Knowledge'] = 'Clinical Medicine – Adult, ';
    if ($applicant['clinical_med_pediatric'])
        $user['Basic Knowledge'] .= 'Clinical Medicine – Pediatric, ';
    if ($applicant['clinical_med_vet'])
        $user['Basic Knowledge'] .= 'Clinical Medicine – Vet, ';
    if ($applicant['research'])
        $user['Basic Knowledge'] .= '>Research, ';
    if ($applicant['microbiology'])
        $user['Basic Knowledge'] .= 'microbiology, ';
    if ($applicant['virology'])
        $user['Basic Knowledge'] .= 'virology, ';
    if ($applicant['parasitology'])
        $user['Basic Knowledge'] .= 'parasitology, ';
    if ($applicant['vaccinology'])
        $user['Basic Knowledge'] .= 'vaccinology, ';
    if ($applicant['epidemiology'])
        $user['Basic Knowledge'] .= 'epidemiology, ';
    if ($applicant['biotechnology'])
        $user['Basic Knowledge'] .= 'biotechnology, ';
    if ($applicant['pharmacy'])
        $user['Basic Knowledge'] .= 'pharmacy, ';
    if ($applicant['publichealth'])
        $user['Basic Knowledge'] .= 'public health, ';
    if ($applicant['disease_surv'])
        $user['Basic Knowledge'] .= 'disease surveillance, ';
    if ($applicant['informatics'])
        $user['Basic Knowledge'] .= 'informatics, ';
    if ($applicant['biostatistics'])
        $user['Basic Knowledge'] .= 'biostatistics, ';
    if ($applicant['other_knowledge'])
        $user['Basic Knowledge'] .= $applicant['other_knowledge_type'];
    // fetp training
    $user['FETP Training'] = $applicant['fetp_training'] ? $applicant['fetp_training']: 'none';
    // years experience
    $user['Years of Experience'] = '';
    if ($applicant['health_exp'] == 'A')
        $user['Years of Experience'] = 'none';
    elseif ($applicant['health_exp'] == 'B')
        $user['Years of Experience'] = 'less than 3 years';
    elseif ($applicant['health_exp'] == 'C')
        $user['Years of Experience'] = '3-5 years';
    elseif ($applicant['health_exp'] == 'D')
        $user['Years of Experience'] = '5-10 years';
    elseif ($applicant['health_exp'] == 'E')
        $user['Years of Experience'] = 'More than 10 years';
    //Heard about by
    $user['Heard about Epicore by'] = '';
    if ($applicant['googlesearch'])
        $user['Heard about Epicore by'] = 'Googlesearch, ';
    if ($applicant['conference'])
        $user['Heard about Epicore by'] .= 'Conference: ' . $applicant['conference_type']. ', ';
    if ($applicant['nextgenu'])
        $user['Heard about Epicore by'] .= 'NextGenU, ';
    if ($applicant['epicoreworkshop'])
        $user['Heard about Epicore by'] .= 'Workshop: ' . $applicant['epicoreworkshop_type']. ', ';
    if ($applicant['promoemail'])
        $user['Heard about Epicore by'] .= 'Email: ' . $applicant['promoemail_type']. ', ';
    if ($applicant['othercontact'])
        $user['Heard about Epicore by'] .= 'Other: ' . $applicant['othercontact_type']. ', ';
    // user course type
    $user['Course Type'] = '';
    if ($applicant['online_course'])
        $user['Course Type'] = 'online';
    elseif ($applicant['inperson_course'])
        $user['Course Type'] = 'inperson';
    //user status
    $user['User Status'] = $applicant['status'] == 'Pending' ? 'Accepted' : $applicant['status'] ;
    // user set password
    $user['pword'] = $applicant['pword'];

    // save keys as header values
    if ($n == 0)
        fputcsv($fp, array_keys($user));

    $n=1;

    // save user
    fputcsv($fp, $user);
}
