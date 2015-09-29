<?php 
// clearn variables
$formvars = json_decode(file_get_contents("php://input"));
$pvals['email'] = strip_tags($formvars->email);
$pvals['firstname'] = strip_tags($formvars->firstname);
$pvals['lastname'] = strip_tags($formvars->lastname);
$pvals['country'] = strip_tags($formvars->country);
$pvals['city'] = strip_tags($formvars->city);
$pvals['bachelors'] = strip_tags(isset($formvars->bachelors));
$pvals['gradstudent'] = strip_tags(isset($formvars->gradstudent));
$pvals['masters'] = strip_tags(isset($formvars->masters));
$pvals['medical'] = strip_tags(isset($formvars->medical));
$pvals['doctorate'] = strip_tags(isset($formvars->doctorate));
$pvals['otherdegree'] = strip_tags(isset($formvars->otherdegree));
$pvals['bachelors_type'] = strip_tags($formvars->bachelors_type);
$pvals['gradstudent_type'] = strip_tags($formvars->gradstudent_type);
$pvals['masters_type'] = strip_tags($formvars->masters_type);
$pvals['medical_type'] = strip_tags($formvars->medical_type);
$pvals['otherdegree_type'] = strip_tags($formvars->otherdegree_type);
$pvals['doctorate_type'] = strip_tags($formvars->doctorate_type);
$pvals['universities'] = strip_tags($formvars->universities);
$pvals['clinical_med_adult'] = strip_tags(isset($formvars->clinical_med_adult));
$pvals['clinical_med_pediatric'] = strip_tags(isset($formvars->clinical_med_pediatric));
$pvals['clinical_med_vet'] = strip_tags(isset($formvars->clinical_med_vet));
$pvals['research'] = strip_tags(isset($formvars->research));
$pvals['microbiology'] = strip_tags(isset($formvars->microbiology));
$pvals['virology'] = strip_tags(isset($formvars->virology));
$pvals['parasitology'] = strip_tags(isset($formvars->parasitology));
$pvals['vaccinology'] = strip_tags(isset($formvars->vaccinology));
$pvals['epidemiology'] = strip_tags(isset($formvars->epidemiology));
$pvals['biotechnology'] = strip_tags(isset($formvars->biotechnology));
$pvals['pharmacy'] = strip_tags(isset($formvars->pharmacy));
$pvals['publichealth'] = strip_tags(isset($formvars->publichealth));
$pvals['disease_surv'] = strip_tags(isset($formvars->disease_surv));
$pvals['informatics'] = strip_tags(isset($formvars->informatics));
$pvals['biostatistics'] = strip_tags(isset($formvars->biostatistics));
$pvals['other_knowledge'] = strip_tags(isset($formvars->other_knowledge));
$pvals['other_knowledge_type'] = strip_tags($formvars->other_knowledge_type);
$pvals['training'] = strip_tags(isset($formvars->training)) == 'yes' ? true: false;
$pvals['fetp_training'] = strip_tags($formvars->fetp_training);
$pvals['tephinet_news'] = strip_tags($formvars->tephinet_news) == 'yes' ? true: false;
$pvals['health_exp'] = strip_tags($formvars->health_exp);
$pvals['job_title'] = strip_tags($formvars->job_title);
$pvals['organization'] = strip_tags($formvars->organization);
$pvals['sector'] = strip_tags($formvars->sector);
$pvals['health_org'] = strip_tags($formvars->health_org) == 'yes' ? true: false;
$pvals['health_org_university'] = strip_tags(isset($formvars->health_org_university));
$pvals['health_org_doh'] = strip_tags(isset($formvars->health_org_doh));
$pvals['health_org_clinic'] = strip_tags(isset($formvars->health_org_clinic));
$pvals['health_org_other'] = strip_tags(isset($formvars->health_org_other));
$pvals['info_accurate'] = strip_tags(isset($formvars->info_accurate));
$pvals['rfi_agreement'] = strip_tags(isset($formvars->rfi_agreement));


// exit if no email
if(!$pvals['email']) {
    print json_encode(array('status' => 'failed', 'reason' => 'No email specified'));
    exit;
}

require_once "UserInfo.class.php";
//$user_id = UserInfo::joinMaillist($pvals);
$user_id = UserInfo::applyMaillist($pvals);

if(!$user_id) {
    print json_encode(array('status' => 'failed', 'reason' => 'User could not be inserted'));
} else {
    print json_encode(array('status' => 'success', 'uid' => $user_id));
}

?>
