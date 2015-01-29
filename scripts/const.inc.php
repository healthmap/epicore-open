<?
define('EMAIL_NOREPLY', 'Epicore Message Bot <mod@healthmap.org>');
define('LAT_LON_PRECISION', 2);
define('EPICORE_URL', 'http://epicore.org');
define('DEFAULT_RADIUS', 400); // in km

define('EVENT_DETAILS', "\n\n[PERSONALIZED_TEXT]Date: [EVENT_DATE]\nTitle: [TITLE]\nLocation: [LOCATION]\n\n[DESCRIPTION]");

define('EMAIL_TEXT_RFI', "The following RFI was sent from the Epicore system:".EVENT_DETAILS);
define('EMAIL_TEXT_FOLLOWUP', "The following follow-up RFI was sent from the Epicore system.[CUSTOM_TEXT] The original event details are:".EVENT_DETAILS);
define('EMAIL_TEXT_FOLLOWUP_SPECIFIC', "The following follow-up RFI was sent from the Epicore system.[CUSTOM_TEXT] It is in response to your reply from [RESPONSE_DATE]:\n\n[RESPONSE_TEXT]\n\n\nThe original event details are:".EVENT_DETAILS);
define('EMAIL_TEXT_CLOSED', "The following Epicore RFI has been closed:".EVENT_DETAILS."[CUSTOM_TEXT]");
define('EMAIL_TEXT_REOPENED', "The following Epicore RFI has been re-opened:".EVENT_DETAILS."[CUSTOM_TEXT]");
define('EMAIL_TEXT_RESPONSE', "The following is a response to your Epicore RFI:".EVENT_DETAILS);
define('EMAIL_TEXT_SHOW_RESPONSE', "The following is a response to your Epicore RFI:\n\n[RESPONSE_INFO]\n\nThe original event details are:".EVENT_DETAILS);

define('EMAIL_TEXT_RESPONSE_FOOTER', "To send a follow-up to this response, please visit:\n".EPICORE_URL."/#/followup/[EVENT_ID]/[RESPONSE_ID].\n\nTo view all responses to this RFI, please visit:\n".EPICORE_URL."/#/responses/[EVENT_ID].");
define('RESPONSE_LINK', "To respond to this message, please visit:\n".EPICORE_URL."/#/fetp/[TOKEN]/[EVENT_ID]");

//define('TEPHINET_BASE', 'http://tftephinetdev.devcloud.acquia-sites.com/');
//define('TEPHINET_BASE', 'http://tftephinettest.devcloud.acquia-sites.com/');
define('TEPHINET_BASE', 'http://www.tephinet.org/');
define('TEPHINET_CONSUMER_KEY', 'EJL7rbQR3YTQXPb8ku6zEQbmRSXzLRtd');
define('TEPHINET_CONSUMER_SECRET', 'Nr8TTVMoAvm4X86jjzbp65aE7kTmR2WN');

$status_lu = array('O' => 're-opened', 'C' => 'closed');

$super_users = array(1,11,79,13); // Sue, Anika, Zeenah, Sumi

$response_permission_lu = array(
    "0" => "indicated nothing to contribute to the outbreak",
    "1" => "paraphrase this response when reporting on the event in other forums.",
    "2" => "quote this response but may not provide any details on my identity (location, position, etc.)",
    "3" => "quote this response and attribute it to me [name and title in response text]"
);

$reason_lu = array(
    "1" => "Outbreak has been confirmed from other sources",
    "2" => "Sufficient FETP responses",
    "3" => "Insufficient FETP responses"
);
?>
