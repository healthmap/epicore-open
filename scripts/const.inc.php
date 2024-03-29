<?php

require_once '/usr/share/php/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '../../');

$dotenv->load();
$environment = $_ENV['environment'];
$approval_username = $_ENV['approval_username'];
$$approval_username = $_ENV['approval_password'];
$key = $_ENV['googleapi_key'];
$clientid =  $_ENV['googleapi_clientid'];
$fcm_server_key = $_ENV['fcm_server_key'];
$fcm_send_url = $_ENV['fcm_send_url'];

$tephinet_baseurl = $_ENV['tephinet_baseurl'];
$consumerkey = $_ENV['tephinet_consumerkey'];
$consumersecret = $_ENV['tephinet_consumersecret'];

$userids = $_ENV['superusers_userids'];

#$epicore_info_baseurl = $_ENV['epicore_info_baseurl'];
$emailnoreply = $_ENV['emailnoreply'];
$emailinfo = $_ENV['emailinfo'];
$emailadmin = $_ENV['emailadmin'];
#$promed_info = $_ENV['promed_info'];
$emailproin = $_ENV['emailproin'];

$aws_region = $_ENV['epicore_aws_region'];
$aws_userPoolId = $_ENV['epicore_user_pool_id'];
$aws_appClientId = $_ENV['epicore_app_client_id'];
$aws_appClientIdSecret = $_ENV['epicore_app_client_id_secret'];
$aws_epicoreArn = $_ENV['epicore_aws_arn'];
$aws_epicoreIamRolename = $_ENV['epicore_aws_iam_rolename'];

// echo '>>>>';
// echo $environment;
// echo '<<<<:';

define('ENVIRONMENT', $environment);

define('NEW_EPICORE_REQUESTER_STATUS', 'NewRequester');

define('AWS_REGION', $aws_region);
define('AWS_USER_POOL_ID', $aws_userPoolId);
define('AWS_APP_CLIENT_ID', $aws_appClientId);
define('AWS_APP_CLIENT_ID_SECRET', $aws_appClientIdSecret);
define('AWS_EPICORE_ARN', $aws_epicoreArn);
define('AWS_EPICORE_IAM_ROLENAME', $aws_epicoreIamRolename);


// approval username and password
define('APPROVAL_USERNAME', $approval_username);
define('APPROVAL_PASSWORD', $approval_username);

// google api
define('CRYPTOKEY', $key );
define('CLIENTID', $clientid);
define('FCM_SERVER_KEY', $fcm_server_key);
define('FCM_SEND_URL', $fcm_send_url);

// Epicore
define('EPICORE_URL', $epicore_info_baseurl);
define('EMAIL_NOREPLY', $emailnoreply);
define('EMAIL_INFO_EPICORE', $emailinfo);
define('EMAIL_EPICORE_ADMIN', $emailadmin);

// Tephinet
define('TEPHINET_BASE', $tephinet_baseurl);
define('TEPHINET_CONSUMER_KEY', $consumerkey);
define('TEPHINET_CONSUMER_SECRET', $consumersecret);

// ProMed
define('EMAIL_PROIN', $emailproin);

//su
$super_users = explode(',', $userids);

define('EPICORE_ID', 0);
define('PROMED_ID', 4);
define('LAT_LON_PRECISION', 2);
define('DEFAULT_RADIUS', 400); // in km
define('EMAILPREVIEWS', 'emailtemplates/temp/');
define('V2START_DATE', '2017-10-30');
define('V1START_DATE', '2015-01-01'); //Year epicore starte with V1

define('RESPONSEFILE_DIR', 'responsefiles/');

$status_lu = array('O' => 're-opened', 'C' => 'closed');





// old permission text
/*$response_permission_lu = array(
    "0" => " Indicated nothing to contribute to the outbreak",
    "1" => " Paraphrase / do not use direct quotes from this response, mask any identifying information referenced in the response text and do not provide any details on the responding member identity",
    "2" => " Quote this response but mask any identifying information referenced in the response text and do not provide any details on the responding member identity",
    "3" => " Quote this response and attribute it to the responding member [name and title in response text]",
    "4" => " Active Search"
);*/
// permission text
$response_permission_lu = array(
    "0" => " Indicated nothing to contribute to the outbreak",
    "1" => " Don't quote, don't attribute",
    "2" => " Can quote, don't attribute",
    "3" => " Can quote, can attribute",
    "4" => " Active Search"
);

// permission traffic light icons
$permission_img = array(
    "0"  => '',
    "1" => '<img src="'. EPICORE_URL. '/img/redlightnew.png" height="48" alt="traffic light" style="vertical-align:middle;height:48px;margin-left: 3px;">',
    "2" => '<img src="'. EPICORE_URL. '/img/yellowlightnew.png" height="48" alt="traffic light" style="vertical-align:middle;height:48px;margin-left: 3px;">',
    "3" => '<img src="'. EPICORE_URL. '/img/greenlightnew.png" height="48" alt="traffic light" style="vertical-align:middle;height:48px;margin-left: 3px;">',
    "4" => ''
);

$reason_lu = array(
    "1" => "Outbreak has been confirmed from other sources",
    "2" => "Sufficient Member responses",
    "3" => "Insufficient Member responses"
);

$countries = array("AF" => "Afghanistan",
    "AX" => "Åland Islands",
    "AL" => "Albania",
    "DZ" => "Algeria",
    "AS" => "American Samoa",
    "AD" => "Andorra",
    "AO" => "Angola",
    "AI" => "Anguilla",
    "AQ" => "Antarctica",
    "AG" => "Antigua and Barbuda",
    "AR" => "Argentina",
    "AM" => "Armenia",
    "AW" => "Aruba",
    "AU" => "Australia",
    "AT" => "Austria",
    "AZ" => "Azerbaijan",
    "BS" => "Bahamas",
    "BH" => "Bahrain",
    "BD" => "Bangladesh",
    "BB" => "Barbados",
    "BY" => "Belarus",
    "BE" => "Belgium",
    "BZ" => "Belize",
    "BJ" => "Benin",
    "BM" => "Bermuda",
    "BT" => "Bhutan",
    "BO" => "Bolivia",
    "BA" => "Bosnia and Herzegovina",
    "BW" => "Botswana",
    "BV" => "Bouvet Island",
    "BR" => "Brazil",
    "IO" => "British Indian Ocean Territory",
    "BN" => "Brunei Darussalam",
    "BG" => "Bulgaria",
    "BF" => "Burkina Faso",
    "BI" => "Burundi",
    "KH" => "Cambodia",
    "CM" => "Cameroon",
    "CA" => "Canada",
    "CV" => "Cape Verde",
    "KY" => "Cayman Islands",
    "CF" => "Central African Republic",
    "TD" => "Chad",
    "CL" => "Chile",
    "CN" => "China",
    "CX" => "Christmas Island",
    "CC" => "Cocos (Keeling) Islands",
    "CO" => "Colombia",
    "KM" => "Comoros",
    "CG" => "Congo",
    "CD" => "Congo, The Democratic Republic of The",
    "CK" => "Cook Islands",
    "CR" => "Costa Rica",
    "CI" => "Cote D'ivoire",
    "HR" => "Croatia",
    "CU" => "Cuba",
    "CY" => "Cyprus",
    "CZ" => "Czech Republic",
    "DK" => "Denmark",
    "DJ" => "Djibouti",
    "DM" => "Dominica",
    "DO" => "Dominican Republic",
    "EC" => "Ecuador",
    "EG" => "Egypt",
    "SV" => "El Salvador",
    "GQ" => "Equatorial Guinea",
    "ER" => "Eritrea",
    "EE" => "Estonia",
    "ET" => "Ethiopia",
    "FK" => "Falkland Islands (Malvinas)",
    "FO" => "Faroe Islands",
    "FJ" => "Fiji",
    "FI" => "Finland",
    "FR" => "France",
    "GF" => "French Guiana",
    "PF" => "French Polynesia",
    "TF" => "French Southern Territories",
    "GA" => "Gabon",
    "GM" => "Gambia",
    "GE" => "Georgia",
    "DE" => "Germany",
    "GH" => "Ghana",
    "GI" => "Gibraltar",
    "GR" => "Greece",
    "GL" => "Greenland",
    "GD" => "Grenada",
    "GP" => "Guadeloupe",
    "GU" => "Guam",
    "GT" => "Guatemala",
    "GG" => "Guernsey",
    "GN" => "Guinea",
    "GW" => "Guinea-bissau",
    "GY" => "Guyana",
    "HT" => "Haiti",
    "HM" => "Heard Island and Mcdonald Islands",
    "VA" => "Holy See (Vatican City State)",
    "HN" => "Honduras",
    "HK" => "Hong Kong",
    "HU" => "Hungary",
    "IS" => "Iceland",
    "IN" => "India",
    "ID" => "Indonesia",
    "IR" => "Iran, Islamic Republic of",
    "IQ" => "Iraq",
    "IE" => "Ireland",
    "IM" => "Isle of Man",
    "IL" => "Israel",
    "IT" => "Italy",
    "JM" => "Jamaica",
    "JP" => "Japan",
    "JE" => "Jersey",
    "JO" => "Jordan",
    "KZ" => "Kazakhstan",
    "KE" => "Kenya",
    "KI" => "Kiribati",
    "KP" => "Korea, Democratic People's Republic of",
    "XK" => "Kosovo",
    "KR" => "Korea, Republic of",
    "KW" => "Kuwait",
    "KG" => "Kyrgyzstan",
    "LA" => "Lao People's Democratic Republic",
    "LV" => "Latvia",
    "LB" => "Lebanon",
    "LS" => "Lesotho",
    "LR" => "Liberia",
    "LY" => "Libyan Arab Jamahiriya",
    "LI" => "Liechtenstein",
    "LT" => "Lithuania",
    "LU" => "Luxembourg",
    "MO" => "Macao",
    "MK" => "Macedonia, The Former Yugoslav Republic of",
    "MG" => "Madagascar",
    "MW" => "Malawi",
    "MY" => "Malaysia",
    "MV" => "Maldives",
    "ML" => "Mali",
    "MT" => "Malta",
    "MH" => "Marshall Islands",
    "MQ" => "Martinique",
    "MR" => "Mauritania",
    "MU" => "Mauritius",
    "YT" => "Mayotte",
    "MX" => "Mexico",
    "FM" => "Micronesia, Federated States of",
    "MD" => "Moldova, Republic of",
    "MC" => "Monaco",
    "MN" => "Mongolia",
    "ME" => "Montenegro",
    "MS" => "Montserrat",
    "MA" => "Morocco",
    "MZ" => "Mozambique",
    "MM" => "Myanmar",
    "NA" => "Namibia",
    "NR" => "Nauru",
    "NP" => "Nepal",
    "NL" => "Netherlands",
    "AN" => "Netherlands Antilles",
    "NC" => "New Caledonia",
    "NZ" => "New Zealand",
    "NI" => "Nicaragua",
    "NE" => "Niger",
    "NG" => "Nigeria",
    "NU" => "Niue",
    "NF" => "Norfolk Island",
    "MP" => "Northern Mariana Islands",
    "NO" => "Norway",
    "OM" => "Oman",
    "PK" => "Pakistan",
    "PW" => "Palau",
    "PS" => "Palestinian Territory",
    "PA" => "Panama",
    "PG" => "Papua New Guinea",
    "PY" => "Paraguay",
    "PE" => "Peru",
    "PH" => "Philippines",
    "PN" => "Pitcairn",
    "PL" => "Poland",
    "PT" => "Portugal",
    "PR" => "Puerto Rico",
    "QA" => "Qatar",
    "RE" => "Reunion",
    "RO" => "Romania",
    "RU" => "Russian Federation",
    "RW" => "Rwanda",
    "SH" => "Saint Helena",
    "KN" => "Saint Kitts and Nevis",
    "LC" => "Saint Lucia",
    "PM" => "Saint Pierre and Miquelon",
    "VC" => "Saint Vincent and The Grenadines",
    "WS" => "Samoa",
    "SM" => "San Marino",
    "ST" => "Sao Tome and Principe",
    "SA" => "Saudi Arabia",
    "SN" => "Senegal",
    "RS" => "Serbia",
    "SC" => "Seychelles",
    "SL" => "Sierra Leone",
    "SG" => "Singapore",
    "SK" => "Slovakia",
    "SI" => "Slovenia",
    "SB" => "Solomon Islands",
    "SO" => "Somalia",
    "ZA" => "South Africa",
    "GS" => "South Georgia and The South Sandwich Islands",
    "SS" => "South Sudan",
    "ES" => "Spain",
    "LK" => "Sri Lanka",
    "SD" => "Sudan",
    "SR" => "Suriname",
    "SJ" => "Svalbard and Jan Mayen",
    "SZ" => "Swaziland",
    "SE" => "Sweden",
    "CH" => "Switzerland",
    "SY" => "Syrian Arab Republic",
    "TW" => "Taiwan",
    "TJ" => "Tajikistan",
    "TZ" => "Tanzania",
    "TH" => "Thailand",
    "TL" => "East Timor",
    "TG" => "Togo",
    "TK" => "Tokelau",
    "TO" => "Tonga",
    "TT" => "Trinidad and Tobago",
    "TN" => "Tunisia",
    "TR" => "Turkey",
    "TM" => "Turkmenistan",
    "TC" => "Turks and Caicos Islands",
    "TV" => "Tuvalu",
    "UG" => "Uganda",
    "UA" => "Ukraine",
    "AE" => "United Arab Emirates",
    "GB" => "United Kingdom",
    "US" => "United States",
    "UM" => "United States Minor Outlying Islands",
    "UY" => "Uruguay",
    "UZ" => "Uzbekistan",
    "VU" => "Vanuatu",
    "VE" => "Venezuela",
    "VN" => "Viet Nam",
    "VG" => "Virgin Islands, British",
    "VI" => "Virgin Islands, U.S.",
    "WF" => "Wallis and Futuna",
    "EH" => "Western Sahara",
    "YE" => "Yemen",
    "ZM" => "Zambia",
    "ZW" => "Zimbabwe"
);

define ('COUNTRIES', serialize($countries));

$whomap = array("AF" => "Eastern Mediterranean",
    "AX" => "Europe",
    "AL" => "Europe",
    "DZ" => "Africa",
    "AS" => "North America",
    "AD" => "Europe",
    "AO" => "Africa",
    "AI" => "South and Central America",
    "AQ" => "Antarctica",
    "AG" => "South and Central America",
    "AR" => "South and Central America",
    "AM" => "Europe",
    "AW" => "North America",
    "AU" => "Western Pacific",
    "AT" => "Europe",
    "AZ" => "Europe",
    "BS" => "South and Central America",
    "BH" => "Eastern Mediterranean",
    "BD" => "South East Asia",
    "BB" => "South and Central America",
    "BY" => "Europe",
    "BE" => "Europe",
    "BZ" => "South and Central America",
    "BJ" => "Africa",
    "BM" => "South and Central America",
    "BT" => "South East Asia",
    "BO" => "South and Central America",
    "BA" => "Europe",
    "BW" => "Africa",
    "BV" => "Bouvet Island",
    "BR" => "South and Central America",
    "IO" => "Europe",
    "BN" => "Western Pacific",
    "BG" => "Europe",
    "BF" => "Africa",
    "BI" => "Africa",
    "KH" => "Western Pacific",
    "CM" => "Africa",
    "CA" => "North America",
    "CV" => "Africa",
    "KY" => "South and Central America",
    "CF" => "Africa",
    "TD" => "Africa",
    "CL" => "South and Central America",
    "CN" => "Western Pacific",
    "CX" => "Western Pacific",
    "CC" => "Western Pacific",
    "CO" => "South and Central America",
    "KM" => "Africa",
    "CG" => "Africa",
    "CD" => "Africa",
    "CK" => "Western Pacific",
    "CR" => "South and Central America",
    "CI" => "Africa",
    "HR" => "Europe",
    "CU" => "South and Central America",
    "CY" => "Europe",
    "CZ" => "Europe",
    "DK" => "Europe",
    "DJ" => "Eastern Mediterranean",
    "DM" => "South and Central America",
    "DO" => "South and Central America",
    "EC" => "South and Central America",
    "EG" => "Eastern Mediterranean",
    "SV" => "South and Central America",
    "GQ" => "Africa",
    "ER" => "Africa",
    "EE" => "Europe",
    "ET" => "Africa",
    "FK" => "South and Central America",
    "FO" => "Europe",
    "FJ" => "Western Pacific",
    "FI" => "Europe",
    "FR" => "Europe",
    "GF" => "South and Central America",
    "PF" => "Western Pacific",
    "TF" => "French Southern Territories",
    "GA" => "Africa",
    "GM" => "Africa",
    "GE" => "Europe",
    "DE" => "Europe",
    "GH" => "Africa",
    "GI" => "Europe",
    "GR" => "Europe",
    "GL" => "North America",
    "GD" => "South and Central America",
    "GP" => "South and Central America",
    "GU" => "North America",
    "GT" => "South and Central America",
    "GG" => "Europe",
    "GN" => "Africa",
    "GW" => "Africa",
    "GY" => "South and Central America",
    "HT" => "South and Central America",
    "HM" => "Western Pacific",
    "VA" => "Europe",
    "HN" => "South and Central America",
    "HK" => "Western Pacific",
    "HU" => "Europe",
    "IS" => "Europe",
    "IN" => "South East Asia",
    "ID" => "South East Asia",
    "IR" => "Eastern Mediterranean",
    "IQ" => "Eastern Mediterranean",
    "IE" => "Europe",
    "IM" => "Europe",
    "IL" => "Europe",
    "IT" => "Europe",
    "JM" => "South and Central America",
    "JP" => "Western Pacific",
    "JE" => "Europe",
    "JO" => "Eastern Mediterranean",
    "KZ" => "Europe",
    "KE" => "Africa",
    "KI" => "Western Pacific",
    "KP" => "South East Asia",
    "KR" => "Western Pacific",
    "KW" => "Eastern Mediterranean",
    "KG" => "Europe",
    "LA" => "Western Pacific",
    "LV" => "Europe",
    "LB" => "Eastern Mediterranean",
    "LS" => "Africa",
    "LR" => "Africa",
    "LY" => "Africa",
    "LI" => "Europe",
    "LT" => "Europe",
    "LU" => "Europe",
    "MO" => "Western Pacific",
    "MK" => "Europe",
    "MG" => "Africa",
    "MW" => "Africa",
    "MY" => "Western Pacific",
    "MV" => "South East Asia",
    "ML" => "Africa",
    "MT" => "Europe",
    "MH" => "Western Pacific",
    "MQ" => "South and Central America",
    "MR" => "Africa",
    "MU" => "Africa",
    "YT" => "Africa",
    "MX" => "South and Central America",
    "FM" => "Western Pacific",
    "MD" => "Europe",
    "MC" => "Europe",
    "MN" => "Mongolia",
    "ME" => "Europe",
    "MS" => "Europe",
    "MA" => "Eastern Mediterranean",
    "MZ" => "Africa",
    "MM" => "South East Asia",
    "NA" => "Africa",
    "NR" => "Western Pacific",
    "NP" => "South East Asia",
    "NL" => "Europe",
    "AN" => "Europe",
    "NC" => "Western Pacific",
    "NZ" => "Western Pacific",
    "NI" => "South and Central America",
    "NE" => "Africa",
    "NG" => "Africa",
    "NU" => "Western Pacific",
    "NF" => "Western Pacific",
    "MP" => "North America",
    "NO" => "Europe",
    "OM" => "Eastern Mediterranean",
    "PK" => "Eastern Mediterranean",
    "PW" => "Western Pacific",
    "PS" => "Eastern Mediterranean",
    "PA" => "South and Central America",
    "PG" => "Western Pacific",
    "PY" => "South and Central America",
    "PE" => "South and Central America",
    "PH" => "Western Pacific",
    "PN" => "Europe",
    "PL" => "Europe",
    "PT" => "Europe",
    "PR" => "North and Central America",
    "QA" => "Eastern Mediterranean",
    "RE" => "Europe",
    "RO" => "Europe",
    "RU" => "Europe",
    "RW" => "Africa",
    "SH" => "Europe",
    "KN" => "South and Central America",
    "LC" => "South and Central America",
    "PM" => "Europe",
    "VC" => "South and Central America",
    "WS" => "Western Pacific",
    "SM" => "Europe",
    "ST" => "Africa",
    "SA" => "Eastern Mediterranean",
    "SN" => "Africa",
    "RS" => "Europe",
    "SC" => "Africa",
    "SL" => "Africa",
    "SG" => "Western Pacific",
    "SK" => "Europe",
    "SI" => "Europe",
    "SB" => "Western Pacific",
    "SO" => "Eastern Mediterranean",
    "ZA" => "Africa",
    "GS" => "Europe",
    "ES" => "Europe",
    "LK" => "South East Asia",
    "SD" => "Eastern Mediterranean",
    "SS" => "Africa",
    "SR" => "South and Central America",
    "SJ" => "Europe",
    "SZ" => "Africa",
    "SE" => "Europe",
    "CH" => "Europe",
    "SY" => "Eastern Mediterranean",
    "TW" => "Western Pacific",
    "TJ" => "Europe",
    "TZ" => "Africa",
    "TH" => "South East Asia",
    "TL" => "South East Asia",
    "TG" => "Africa",
    "TK" => "Western Pacific",
    "TO" => "Western Pacific",
    "TT" => "South and Central America",
    "TN" => "Eastern Mediterranean",
    "TR" => "Europe",
    "TM" => "Europe",
    "TC" => "South and Central America",
    "TV" => "Western Pacific",
    "UG" => "Africa",
    "UA" => "Europe",
    "AE" => "Eastern Mediterranean",
    "GB" => "Europe",
    "US" => "North America",
    "UM" => "North America",
    "UY" => "South and Central America",
    "UZ" => "Europe",
    "VU" => "Western Pacific",
    "VE" => "South and Central America",
    "VN" => "Western Pacific",
    "VG" => "Europe",
    "VI" => "North America",
    "WF" => "Western Pacific",
    "EH" => "Africa",
    "XK" => "Europe",
    "YE" => "Eastern Mediterranean",
    "ZM" => "Africa",
    "ZW" => "Africa"
);

define ('WHOMAP', serialize($whomap));

$check_conditions = array("respiratory","gastrointestinal","other_neurological", "fever_rash", "jaundice", "h_fever", "paralysis", "other_neurological",
                        "fever_unknown", "renal", "respiratory_animal", "neurological_animal", "hemorrhagic_animal", "vesicular_animal",
                        "reproductive_animal", "gastrointestinal_animal", "multisystemic_animal");
define('CHECK_CONDITIONS', serialize($check_conditions));

?>
