<?
define('EMAIL_NOREPLY', 'Epicore Message Bot <mod@healthmap.org>');
define('LAT_LON_PRECISION', 2);
define('EPICORE_URL', 'http://epicore.org');
define('DEFAULT_RADIUS', 250);

define('RESPONSE_LINK', "To respond to this message, please visit:\n[EPICORE_URL]/#/fetp/[TOKEN]/[ID]");
define('EMAIL_TEXT_RFI', "We are writing to request additional information on the event:\n\nTitle: [TITLE]\nLocation: [LOCATION]\n\n[DESCRIPTION]\n\nTo respond to this message, please visit:\n[EPICORE_URL]/#/fetp/[TOKEN]/[ID]");
define('EMAIL_TEXT_FOLLOWUP', "The following are the details of the original event RFI:\n\nTitle: [TITLE]\nLocation: [LOCATION]\n\n[DESCRIPTION]\n\nTo respond to this message, please visit:\n[EPICORE_URL]/#/fetp/[TOKEN]/[ID]");
define('EMAIL_TEXT_CLOSED', "The following event RFI has been closed:\n\nTitle: [TITLE]\nLocation: [LOCATION]\n\n[DESCRIPTION]\n\n[CUSTOM_TEXT]\n\nTo view your RFIs, visit:\n[EPICORE_URL]");
define('EMAIL_TEXT_REOPENED', "The following event RFI has been re-opened:\n\nTitle: [TITLE]\nLocation: [LOCATION]\n\n[DESCRIPTION]\n\n[CUSTOM_TEXT]\n\n".RESPONSE_LINK);
define('EMAIL_TEXT_RESPONSE', "The following is a response to your request:\n\nTitle: [TITLE]\nLocation: [LOCATION]");

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
    "2" => "Sufficient number of FETP responses",
    "3" => "Insufficient number of FETP responses"
);
?>
