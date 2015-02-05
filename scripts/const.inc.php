<?
define('EMAIL_NOREPLY', 'mod@healthmap.org');
define('LAT_LON_PRECISION', 2);
define('EPICORE_URL', 'http://epicore.org');
define('DEFAULT_RADIUS', 400); // in km

define('EMAILPREVIEWS', 'emailtemplates/temp/');

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
