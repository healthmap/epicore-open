angular.module('EpicoreApp.controllers', []).

/* User - includes signup, Reset password, Login & Logout */
controller('userController', function($rootScope, $routeParams, $scope, $route, $cookies, $cookieStore, $location, $http, $window) {

    $scope.isRouteLoading = false;
    var querystr = $location.search() ? $location.search() : '';

    /* get the active state of page you're on */
    $scope.getClass = function(path) {
        if(path == $location.path()) {
            return "active";
        } else {
            return "";
        }
    }

    $scope.go = function(path) {
        $location.path(path);
    }

        // prepopulate application form
        $scope.uid = $routeParams.id;
        $scope.action = $routeParams.action;
        $scope.idtype = $routeParams.idtype;
        if($scope.uid && ($scope.action == 'edit')) {
            $scope.more_schools1 = true;
            $scope.more_schools2 = true;
            var data = {};
            data['uid'] = $scope.uid;
            data['action'] = $scope.action;
            data['idtype'] = $scope.idtype;
            $http({ url: 'scripts/getapplicant.php', method: "POST", data: data
            }).success(function (data, status, headers, config) {
                $scope.uservals = data; // this pre-populates the values on the form
                if ($scope.uservals.university2) {
                    $scope.more_schools1 = true;
                    $scope.uservals.school_country2 = data['school_country2'];
                }
                else{
                    $scope.more_schools1 = false;
                }
                if ($scope.uservals.university3) {
                    $scope.more_schools2 = true;
                    $scope.uservals.school_country3 = data['school_country3'];
                }
                else{
                    $scope.more_schools2 = false;
                }
            });
        }

    $scope.signup = function(uservals, isValid) {

        $scope.attempted = true;

        // validate checkboxes
        /*$scope.no_knowledge = !uservals.clinical_med_adult && !uservals.clinical_med_pediatric && !uservals.clinical_med_vet && !uservals.research &&
                                !uservals.microbiology && !uservals.virology && !uservals.parasitology && !uservals.vaccinology && !uservals.epidemiology &&
                                !uservals.biotechnology && !uservals.pharmacy && !uservals.publichealth && !uservals.disease_surv && !uservals.informatics &&
                                !uservals.biostatistics && !uservals.other_knowledge;
         */
        $scope.no_health_exp = !uservals.human_health && !uservals.animal_health && !uservals.env_health && !uservals.health_exp_none;

        $scope.no_category = !uservals.health_org_university  && !uservals.health_org_doh && !uservals.health_org_clinic
                                && !uservals.health_org_other && !uservals.health_org_none;

        $scope.no_notification = !uservals.epicoreworkshop && !uservals.conference && !uservals.promoemail && !uservals.othercontact;


        // check email
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        var isemail = regex.test(uservals.email);

        if (!isValid || !isemail || $scope.no_health_exp || $scope.no_category || $scope.no_notification || !uservals.training || !uservals.other_training
            || !uservals.health_exp || !uservals.sector){

            $scope.signup_message = 'Form not complete. Please correct the errors above in red, and then submit again.';
            return false;
        }
        else {
            if($scope.action == 'edit'){
                $http({
                    url: 'scripts/updateuser.php', method: "POST", data: uservals
                }).success(function (data, status, headers, config) {
                    if (data['status'] == "success") {
                        if($scope.idtype == 'fetp'){
                            $location.path('/application/' + $scope.uid + '/edit/fetp');
                            $scope.signup_message = 'Successfully Updated profile';
                        }
                        else
                            $location.path('/approval');
                    } else {
                        $scope.signup_message = data['message'];
                    }
                });

            }
            else {
                $http({
                    url: 'scripts/signup.php', method: "POST", data: uservals
                }).success(function (data, status, headers, config) {
                    if (data['status'] == "success") {
                        if (data['exists'] == 1) {
                            $scope.signup_message = 'Your email address is already in the applicant system.';
                        } else {
                            $location.path('/application_confirm');
                        }
                    } else {
                        $scope.signup_message = 'Your email address is already in the applicant system.';
                    }
                });
            }
        }
    };

    /* set some global variables for Tephinet integration */
    $http({ url: 'scripts/getvars.php', method: "POST"
        }).success(function (data, status, headers, config) {
        $rootScope.tephinetBase = data['tephinet_base'];
    });

    /* log in */
    $scope.userLogin = function(formData) {
        $scope.isRouteLoading = true;
        // came in from fetp log in, no formdata passed, get ticket id and (optional) event_id from URL
        if(typeof(formData) == "undefined") {
            var formData = {};
            if(typeof(querystr['t']) != "undefined") {
                formData['ticket_id'] = querystr['t'];
            } else if ($routeParams.tid) {
                formData['ticket_id'] = $routeParams.tid;
            }
            // if it's a mod, may be coming in with ticket and alert id to auto-fill a request
            formData['alert_id'] = $routeParams.aid ? $routeParams.aid : null;
            // if it's an fetp, may be coming in with ticket and event id for which they will respond
            formData['event_id'] = $routeParams.eid ? $routeParams.eid : null;
            formData['usertype'] = $location.path().indexOf("/fetp") == 0 ? 'fetp' : '';
        } 
        if(!formData['ticket_id'] && !formData['alert_id'] && !formData['event_id'] && !$scope.loginForm.$valid) {
            $scope.isRouteLoading = false;
            return;
        }
        $http({ url: 'scripts/login.php', method: "POST", data: formData
        }).success(function (data, status, headers, config) {
            if(data['status'] == "success") {
                // determines if user is an organization or FETP
                $rootScope.isOrganization = data['uinfo']['organization_id'] > 0 ? true : false;
                var isPromed = data['uinfo']['organization_id'] == 4 ? true : false;
                var isActive = typeof(data['uinfo']['active']) != "undefined" ? data['uinfo']['active'] : 'Y';
                $cookieStore.put('epiUserInfo', {'uid':data['uinfo']['user_id'], 'isPromed':isPromed, 'isOrganization':$rootScope.isOrganization,
                    'organization_id':data['uinfo']['organization_id'], 'organization':data['uinfo']['orgname'], 'fetp_id':data['uinfo']['fetp_id'],
                    'email':data['uinfo']['email'], 'uname':data['uinfo']['username'], 'active':isActive, 'status':data['uinfo']['status'], 'superuser':data['uinfo']['superuser']});
                $rootScope.error_message = 'false';
                // FETPs that aren't activated yet don't get review page
                if(data['uinfo']['fetp_id'] && data['uinfo']['active'] == 'N') {
                    var redirpath = '/training';
                } else {
                    var redirpath = typeof(querystr['redir']) != "undefined" ? querystr['redir'] : '/'+data['path'];
                }
                /*
                var redirpath = '/welcome';
                // FETPs that are activated and approved status get to review page
                if(data['uinfo']['fetp_id'] && data['uinfo']['active'] == 'Y') {
                    redirpath = typeof(querystr['redir']) != "undefined" ? querystr['redir'] : '/'+data['path'];
                }
                */
                $scope.isRouteLoading = false;
                $location.path(redirpath);
            } else {
                $scope.isRouteLoading = false;
                $rootScope.error_message = 'true';
                $route.reload();
            }
        }).error(function (data, status, headers, config) {
            $scope.isRouteLoading = false;
            console.log(status);
        });
    }
    /* log out */
    $scope.userLogout = function() {
        $cookieStore.remove('epiUserInfo');
        $window.sessionStorage.clear();
    }

        /* set password */
        $scope.setPassword = function(formData) {
            $scope.isRouteLoading = true;
            if(typeof(querystr['t']) != "undefined") {
                formData['ticket_id'] = querystr['t'];
            }
            if (!$scope.setpwForm.$valid){
                $scope.isRouteLoading = false;
                $rootScope.error_message = 'Invalid email or password';
                return false;
            }
            else {
                $http({
                    url: 'scripts/setpassword.php', method: "POST", data: formData
                }).success(function (data, status, headers, config) {
                    if (data['status'] == "success") {
                        var isActive = typeof(data['uinfo']['active']) != "undefined" ? data['uinfo']['active'] : 'Y';
                        $cookieStore.put('epiUserInfo', {
                            'uid': data['uinfo']['user_id'],
                            'isPromed': false,
                            'isOrganization': false,
                            'organization_id': data['uinfo']['organization_id'],
                            'organization': data['uinfo']['orgname'],
                            'fetp_id': data['uinfo']['fetp_id'],
                            'email': data['uinfo']['email'],
                            'uname': data['uinfo']['username'],
                            'active': isActive,
                            'status': data['uinfo']['status']
                        });
                        $rootScope.error_message = 'false';
                        var redirpath = '/training';
                        // FETPs that are activated and approved status get to review page
                        if (data['uinfo']['fetp_id'] && data['uinfo']['active'] == 'Y') {
                            redirpath = typeof(querystr['redir']) != "undefined" ? querystr['redir'] : '/' + data['path'];
                        }
                        $scope.isRouteLoading = false;
                        $location.path(redirpath);
                    } else {
                        $scope.isRouteLoading = false;
                        $rootScope.error_message = 'Invalid email address';
                        $route.reload();
                    }
                }).error(function (data, status, headers, config) {
                    $scope.isRouteLoading = false;
                    console.log(status);
                });


            }
        }

        /* Reset password */
        $scope.resetPassword = function(formData) {
            if (!$scope.setpwForm.$valid){
                $scope.isRouteLoading = false;
                $rootScope.error_message = 'Invalid email address';
                return false;
            }
            else {
                $http({
                    url: 'scripts/resetpassword.php', method: "POST", data: formData
                }).success(function (data, status, headers, config) {
                    if (data['status'] == "success") {
                        $scope.isRouteLoading = false;
                        $rootScope.error_message = 'Please check your email for instructions to reset your password.';
                        $route.reload();
                    } else {
                        $scope.isRouteLoading = false;
                        $rootScope.error_message = 'Invalid email address';
                        $route.reload();
                    }
                }).error(function (data, status, headers, config) {
                    $scope.isRouteLoading = false;
                    console.log(status);
                });
            }
        }

    /* get user cookie info */
    $scope.userInfo = $rootScope.userInfo = $cookieStore.get('epiUserInfo');

        /* countries and codes */
        $scope.countries = [
            {name: 'Afghanistan', code: 'AF'},
            {name: 'Åland Islands', code: 'AX'},
            {name: 'Albania', code: 'AL'},
            {name: 'Algeria', code: 'DZ'},
            {name: 'American Samoa', code: 'AS'},
            {name: 'Andorra', code: 'AD'},
            {name: 'Angola', code: 'AO'},
            {name: 'Anguilla', code: 'AI'},
            {name: 'Antarctica', code: 'AQ'},
            {name: 'Antigua and Barbuda', code: 'AG'},
            {name: 'Argentina', code: 'AR'},
            {name: 'Armenia', code: 'AM'},
            {name: 'Aruba', code: 'AW'},
            {name: 'Australia', code: 'AU'},
            {name: 'Austria', code: 'AT'},
            {name: 'Azerbaijan', code: 'AZ'},
            {name: 'Bahamas', code: 'BS'},
            {name: 'Bahrain', code: 'BH'},
            {name: 'Bangladesh', code: 'BD'},
            {name: 'Barbados', code: 'BB'},
            {name: 'Belarus', code: 'BY'},
            {name: 'Belgium', code: 'BE'},
            {name: 'Belize', code: 'BZ'},
            {name: 'Benin', code: 'BJ'},
            {name: 'Bermuda', code: 'BM'},
            {name: 'Bhutan', code: 'BT'},
            {name: 'Bolivia', code: 'BO'},
            {name: 'Bosnia and Herzegovina', code: 'BA'},
            {name: 'Botswana', code: 'BW'},
            {name: 'Bouvet Island', code: 'BV'},
            {name: 'Brazil', code: 'BR'},
            {name: 'British Indian Ocean Territory', code: 'IO'},
            {name: 'Brunei Darussalam', code: 'BN'},
            {name: 'Bulgaria', code: 'BG'},
            {name: 'Burkina Faso', code: 'BF'},
            {name: 'Burundi', code: 'BI'},
            {name: 'Cambodia', code: 'KH'},
            {name: 'Cameroon', code: 'CM'},
            {name: 'Canada', code: 'CA'},
            {name: 'Cape Verde', code: 'CV'},
            {name: 'Cayman Islands', code: 'KY'},
            {name: 'Central African Republic', code: 'CF'},
            {name: 'Chad', code: 'TD'},
            {name: 'Chile', code: 'CL'},
            {name: 'China', code: 'CN'},
            {name: 'Christmas Island', code: 'CX'},
            {name: 'Cocos (Keeling) Islands', code: 'CC'},
            {name: 'Colombia', code: 'CO'},
            {name: 'Comoros', code: 'KM'},
            {name: 'Congo', code: 'CG'},
            {name: 'Congo, The Democratic Republic of the', code: 'CD'},
            {name: 'Cook Islands', code: 'CK'},
            {name: 'Costa Rica', code: 'CR'},
            {name: 'Cote D\'Ivoire', code: 'CI'},
            {name: 'Croatia', code: 'HR'},
            {name: 'Cuba', code: 'CU'},
            {name: 'Cyprus', code: 'CY'},
            {name: 'Czech Republic', code: 'CZ'},
            {name: 'Denmark', code: 'DK'},
            {name: 'Djibouti', code: 'DJ'},
            {name: 'Dominica', code: 'DM'},
            {name: 'Dominican Republic', code: 'DO'},
            {name: 'Ecuador', code: 'EC'},
            {name: 'Egypt', code: 'EG'},
            {name: 'El Salvador', code: 'SV'},
            {name: 'Equatorial Guinea', code: 'GQ'},
            {name: 'Eritrea', code: 'ER'},
            {name: 'Estonia', code: 'EE'},
            {name: 'Ethiopia', code: 'ET'},
            {name: 'Falkland Islands (Malvinas)', code: 'FK'},
            {name: 'Faroe Islands', code: 'FO'},
            {name: 'Fiji', code: 'FJ'},
            {name: 'Finland', code: 'FI'},
            {name: 'France', code: 'FR'},
            {name: 'French Guiana', code: 'GF'},
            {name: 'French Polynesia', code: 'PF'},
            {name: 'French Southern Territories', code: 'TF'},
            {name: 'Gabon', code: 'GA'},
            {name: 'Gambia', code: 'GM'},
            {name: 'Georgia', code: 'GE'},
            {name: 'Germany', code: 'DE'},
            {name: 'Ghana', code: 'GH'},
            {name: 'Gibraltar', code: 'GI'},
            {name: 'Greece', code: 'GR'},
            {name: 'Greenland', code: 'GL'},
            {name: 'Grenada', code: 'GD'},
            {name: 'Guadeloupe', code: 'GP'},
            {name: 'Guam', code: 'GU'},
            {name: 'Guatemala', code: 'GT'},
            {name: 'Guernsey', code: 'GG'},
            {name: 'Guinea', code: 'GN'},
            {name: 'Guinea-Bissau', code: 'GW'},
            {name: 'Guyana', code: 'GY'},
            {name: 'Haiti', code: 'HT'},
            {name: 'Heard Island and Mcdonald Islands', code: 'HM'},
            {name: 'Holy See (Vatican City State)', code: 'VA'},
            {name: 'Honduras', code: 'HN'},
            {name: 'Hong Kong', code: 'HK'},
            {name: 'Hungary', code: 'HU'},
            {name: 'Iceland', code: 'IS'},
            {name: 'India', code: 'IN'},
            {name: 'Indonesia', code: 'ID'},
            {name: 'Iran, Islamic Republic Of', code: 'IR'},
            {name: 'Iraq', code: 'IQ'},
            {name: 'Ireland', code: 'IE'},
            {name: 'Isle of Man', code: 'IM'},
            {name: 'Israel', code: 'IL'},
            {name: 'Italy', code: 'IT'},
            {name: 'Jamaica', code: 'JM'},
            {name: 'Japan', code: 'JP'},
            {name: 'Jersey', code: 'JE'},
            {name: 'Jordan', code: 'JO'},
            {name: 'Kazakhstan', code: 'KZ'},
            {name: 'Kenya', code: 'KE'},
            {name: 'Kiribati', code: 'KI'},
            {name: 'Korea, Democratic People\'s Republic of', code: 'KP'},
            {name: 'Korea, Republic of', code: 'KR'},
            {name: 'Kuwait', code: 'KW'},
            {name: 'Kyrgyzstan', code: 'KG'},
            {name: 'Lao People\'s Democratic Republic', code: 'LA'},
            {name: 'Latvia', code: 'LV'},
            {name: 'Lebanon', code: 'LB'},
            {name: 'Lesotho', code: 'LS'},
            {name: 'Liberia', code: 'LR'},
            {name: 'Libyan Arab Jamahiriya', code: 'LY'},
            {name: 'Liechtenstein', code: 'LI'},
            {name: 'Lithuania', code: 'LT'},
            {name: 'Luxembourg', code: 'LU'},
            {name: 'Macao', code: 'MO'},
            {name: 'Macedonia, The Former Yugoslav Republic of', code: 'MK'},
            {name: 'Madagascar', code: 'MG'},
            {name: 'Malawi', code: 'MW'},
            {name: 'Malaysia', code: 'MY'},
            {name: 'Maldives', code: 'MV'},
            {name: 'Mali', code: 'ML'},
            {name: 'Malta', code: 'MT'},
            {name: 'Marshall Islands', code: 'MH'},
            {name: 'Martinique', code: 'MQ'},
            {name: 'Mauritania', code: 'MR'},
            {name: 'Mauritius', code: 'MU'},
            {name: 'Mayotte', code: 'YT'},
            {name: 'Mexico', code: 'MX'},
            {name: 'Micronesia, Federated States of', code: 'FM'},
            {name: 'Moldova, Republic of', code: 'MD'},
            {name: 'Monaco', code: 'MC'},
            {name: 'Mongolia', code: 'MN'},
            {name: 'Montenegro', code: 'ME'},
            {name: 'Montserrat', code: 'MS'},
            {name: 'Morocco', code: 'MA'},
            {name: 'Mozambique', code: 'MZ'},
            {name: 'Myanmar', code: 'MM'},
            {name: 'Namibia', code: 'NA'},
            {name: 'Nauru', code: 'NR'},
            {name: 'Nepal', code: 'NP'},
            {name: 'Netherlands', code: 'NL'},
            {name: 'Netherlands Antilles', code: 'AN'},
            {name: 'New Caledonia', code: 'NC'},
            {name: 'New Zealand', code: 'NZ'},
            {name: 'Nicaragua', code: 'NI'},
            {name: 'Niger', code: 'NE'},
            {name: 'Nigeria', code: 'NG'},
            {name: 'Niue', code: 'NU'},
            {name: 'Norfolk Island', code: 'NF'},
            {name: 'Northern Mariana Islands', code: 'MP'},
            {name: 'Norway', code: 'NO'},
            {name: 'Oman', code: 'OM'},
            {name: 'Pakistan', code: 'PK'},
            {name: 'Palau', code: 'PW'},
            {name: 'Palestinian Territory', code: 'PS'},
            {name: 'Panama', code: 'PA'},
            {name: 'Papua New Guinea', code: 'PG'},
            {name: 'Paraguay', code: 'PY'},
            {name: 'Peru', code: 'PE'},
            {name: 'Philippines', code: 'PH'},
            {name: 'Pitcairn', code: 'PN'},
            {name: 'Poland', code: 'PL'},
            {name: 'Portugal', code: 'PT'},
            {name: 'Puerto Rico', code: 'PR'},
            {name: 'Qatar', code: 'QA'},
            {name: 'Reunion', code: 'RE'},
            {name: 'Romania', code: 'RO'},
            {name: 'Russian Federation', code: 'RU'},
            {name: 'Rwanda', code: 'RW'},
            {name: 'Saint Helena', code: 'SH'},
            {name: 'Saint Kitts and Nevis', code: 'KN'},
            {name: 'Saint Lucia', code: 'LC'},
            {name: 'Saint Pierre and Miquelon', code: 'PM'},
            {name: 'Saint Vincent and the Grenadines', code: 'VC'},
            {name: 'Samoa', code: 'WS'},
            {name: 'San Marino', code: 'SM'},
            {name: 'Sao Tome and Principe', code: 'ST'},
            {name: 'Saudi Arabia', code: 'SA'},
            {name: 'Senegal', code: 'SN'},
            {name: 'Serbia', code: 'RS'},
            {name: 'Seychelles', code: 'SC'},
            {name: 'Sierra Leone', code: 'SL'},
            {name: 'Singapore', code: 'SG'},
            {name: 'Slovakia', code: 'SK'},
            {name: 'Slovenia', code: 'SI'},
            {name: 'Solomon Islands', code: 'SB'},
            {name: 'Somalia', code: 'SO'},
            {name: 'South Africa', code: 'ZA'},
            {name: 'South Georgia and the South Sandwich Islands', code: 'GS'},
            {name: 'Spain', code: 'ES'},
            {name: 'Sri Lanka', code: 'LK'},
            {name: 'Sudan', code: 'SD'},
            {name: 'Suriname', code: 'SR'},
            {name: 'Svalbard and Jan Mayen', code: 'SJ'},
            {name: 'Swaziland', code: 'SZ'},
            {name: 'Sweden', code: 'SE'},
            {name: 'Switzerland', code: 'CH'},
            {name: 'Syrian Arab Republic', code: 'SY'},
            {name: 'Taiwan', code: 'TW'},
            {name: 'Tajikistan', code: 'TJ'},
            {name: 'Tanzania, United Republic of', code: 'TZ'},
            {name: 'Thailand', code: 'TH'},
            {name: 'Timor-Leste', code: 'TL'},
            {name: 'Togo', code: 'TG'},
            {name: 'Tokelau', code: 'TK'},
            {name: 'Tonga', code: 'TO'},
            {name: 'Trinidad and Tobago', code: 'TT'},
            {name: 'Tunisia', code: 'TN'},
            {name: 'Turkey', code: 'TR'},
            {name: 'Turkmenistan', code: 'TM'},
            {name: 'Turks and Caicos Islands', code: 'TC'},
            {name: 'Tuvalu', code: 'TV'},
            {name: 'Uganda', code: 'UG'},
            {name: 'Ukraine', code: 'UA'},
            {name: 'United Arab Emirates', code: 'AE'},
            {name: 'United Kingdom', code: 'GB'},
            {name: 'United States', code: 'US'},
            {name: 'United States Minor Outlying Islands', code: 'UM'},
            {name: 'Uruguay', code: 'UY'},
            {name: 'Uzbekistan', code: 'UZ'},
            {name: 'Vanuatu', code: 'VU'},
            {name: 'Venezuela', code: 'VE'},
            {name: 'Vietnam', code: 'VN'},
            {name: 'Virgin Islands, British', code: 'VG'},
            {name: 'Virgin Islands, U.S.', code: 'VI'},
            {name: 'Wallis and Futuna', code: 'WF'},
            {name: 'Western Sahara', code: 'EH'},
            {name: 'Yemen', code: 'YE'},
            {name: 'Zambia', code: 'ZM'},
            {name: 'Zimbabwe', code: 'ZW'}
        ];

}).controller('mapController', function($scope, $http, $cookieStore) {
    // only allow moderators
    $scope.userInfo = $cookieStore.get('epiUserInfo');
    //$scope.superuser = (typeof($scope.userInfo) != "undefined") ? $scope.userInfo.superuser: false;
    $scope.isOrganization = $scope.userInfo.isOrganization;
    $scope.showpage = false;

    // set map options
    $scope.map = { center: { latitude: 15, longitude: 18 }, zoom: 2 };
    $scope.options = {scrollwheel: true};

    // map height
    $scope.$on('$viewContentLoaded', function () {
        var mapHeight = 500; // or any other calculated value
        $("#member-map .angular-google-map-container").height(mapHeight);
    });

    $scope.markers = [];
    $scope.numMembers = '';
    $http({ url: 'scripts/getallmarkers.php', method: "POST"
    }).success(function (data, status, headers, config) {
            if(data['status'] == "success") {
                $scope.markers = data['markers'];
                $scope.showpage = true;
                $scope.numMembers = $scope.markers.length;
                $scope.country_members = data['country_members'];
                $scope.numCountries = Object.keys($scope.country_members).length;
            }
    });

/* FOR ADDING ACTIVE CLASS TO NAV */
}).controller('headerController', function($scope, $location) {
     $scope.isActive = function (viewLocation) { 
        return viewLocation === $location.path();
    };

/* FETP controller */
}).controller('fetpController', function($scope, $cookieStore) {
        $scope.userInfo = $cookieStore.get('epiUserInfo');
/* Event(s) controller */
}).controller('eventsController', function($scope, $routeParams, $cookieStore, $location, $http, eventAPIservice) {
        $scope.eventsList = [];
        $scope.userInfo = $cookieStore.get('epiUserInfo');
        $scope.id = $routeParams.id ? $routeParams.id : null;
        $scope.allFETPs = $routeParams.response_id ? false : true;
        // if we're on the closed requests page
        $scope.onOpen = $location.path().indexOf("/closed") > 0 ? false : true;
        $scope.anonymous_disabled = false;
        if(!$scope.formData) {
            $scope.formData = {};
        }
        $scope.validResponses = 0;

        eventAPIservice.getEvents($scope.id).success(function (response) {
            $scope.isOrganization = $scope.userInfo.fetp_id > 0 ? false : true;
            // if RFI requester is the logged in user or of same org, they get different action items
            if(response.EventsList != null) {
                $scope.isAuthorizedToFollowup = $scope.userInfo.organization_id == response.EventsList.org_requester_id ? true : false;
                $scope.changeStatusText = response.EventsList.estatus == "C" ? 'Re open' : 'Close';
                $scope.changeStatusType = response.EventsList.estatus == "C" ? 'reopen' : 'close';
                $scope.isAuthorizedFETP = false;
                if (response.EventsList.fetp_ids != null && response.EventsList.fetp_ids.indexOf($scope.userInfo.fetp_id) != -1) {
                    $scope.isAuthorizedFETP = true;
                }
                if (response.EventsList.fetp_ids){
                    $scope.num_fetp = response.EventsList.fetp_ids.length;
                }
            }

            $scope.eventsList = response.EventsList;
            $scope.filePreview = response.EventsList.filePreview ? response.EventsList.filePreview : '';

            // count responses with content
            for (var h in $scope.eventsList.history) {
                if (($scope.eventsList.history[h].permission !== '0') && ($scope.eventsList.history[h].type == 'Member Response')
                    && ($scope.userInfo.uid)){
                    $scope.validResponses++;
                }
            }
        });

        $scope.sendFollowup = function(formData, isValid) {
            if(isValid) {
                $scope.submitDisabled = true;
                formData['uid'] = $scope.userInfo.uid;
                formData['event_id'] = $routeParams.id;
                if ($routeParams.id){
                    var eid = $routeParams.id;
                }
                if($routeParams.response_id) {
                    formData['response_id'] = $routeParams.response_id;
                }
                $http({ url: 'scripts/sendfollowup.php', method: "POST", data: formData
                }).success(function (data, status, headers, config) {
                    $scope.submitDisabled = false;
                    $location.path('/success/3/' + eid);
                });
            }
        }

        $scope.changeRequestStatus = function(formData, thestatus, isValid) {
            // count responses assessed as useful,used in promed, or not useful when closing an RFI
            // only for responses with content
            var useful_rids = [];
            var usefulpromed_rids = [];
            var notuseful_rids = [];
            if(isValid && (thestatus == 'Close' || thestatus == 'Update') && ($scope.validResponses > 0)) {
                for (var h in $scope.eventsList.history) {
                    var h_rid = $scope.eventsList.history[h].response_id;
                    var h_type = $scope.eventsList.history[h].type;
                    var h_fetp_id = $scope.eventsList.history[h].fetp_id;
                    var h_orgid = $scope.eventsList.history[h].organization_id;
                    var h_useful = $scope.eventsList.history[h].useful;
                    var h_perm = $scope.eventsList.history[h].permission;
                    if ((h_type == 'Member Response' && h_perm !=='0')
                        && ($scope.userInfo.uid || (h_fetp_id == $scope.userInfo.fetp_id)) && (h_orgid == $scope.userInfo.organization_id)) {
                        if (h_useful === null ) {
                            alert('Please assess all member responses.');
                            $scope.close_message = 'Please assess all member responses.';
                            return false;
                        } else if (h_useful === '1') {
                            useful_rids.push(h_rid);   // save useful response_ids
                        } else if (h_useful === '2') {
                            usefulpromed_rids.push(h_rid);   // save useful promed response_ids
                        } else {
                            notuseful_rids.push(h_rid);   // save not useful response_ids
                        }
                    }
                }
            }
            if(isValid) {
                $scope.submitDisabled = true;
                formData['event_id'] = $routeParams.id;
                formData['uid'] = $scope.userInfo.uid;
                formData['thestatus'] = thestatus;
                formData['useful_rids'] = useful_rids.toString();
                formData['usefulpromed_rids'] = usefulpromed_rids.toString();
                formData['notuseful_rids'] = notuseful_rids.toString();
                $http({ url: 'scripts/changestatus.php', method: "POST", data: formData
                }).success(function (data, status, headers, config) {
                    if (data['status'] == 'success') {
                        $scope.submitDisabled = false;
                        var pathid = 4;
                        if (thestatus == "Update") {
                            pathid = 8;
                        } else if (thestatus == "Reopen") {
                            pathid = 5;
                        } else { // closed
                            pathid = 4;
                        }
                        $location.path('/success/' + pathid);
                    } else {
                        console.log(data['reason']);
                        alert(data['reason']);
                    }
                });
            }
        };

        $scope.sendResponse = function(formData, isValid) {
            if(formData['response_permission'] == 0 || isValid) {
                $scope.submitDisabled = true;
                // if user has chosen "I have nothing to contribute" button, 
                // formData comes in as object response_permissions: 0 
                formData['event_id'] = $routeParams.id
                formData['fetp_id'] = $scope.userInfo.fetp_id;
                if ($routeParams.id){
                    var eid = $routeParams.id;
                }
                $http({ url: 'scripts/sendresponse.php', method: "POST", data: formData
                }).success(function (data, status, headers, config) {
                    if (data['status'] == 'success') {
                        $location.path('/success/2/' + eid);
                    } else{
                        alert('response failed!');
                        console.log('invalid event id.')
                    }
                    $scope.submitDisabled = false;
                });
            }
        };

        $scope.deleteEvent = function(eid){
            if (confirm('Are you sure you want to delete this event?')) {
                data = {eid: eid, superuser: $scope.userInfo.superuser};
                $http({
                    url: 'scripts/deleteEvent.php', method: "POST", data: data
                }).success(function (data, status, headers, config) {
                    if (data['status'] == 'success') {
                        $location.path('/success/7');
                    }
                    else{
                        alert(data['reason']);
                        console.log(data['reason']);
                    }
                }).error(function (data, status, headers, config) {
                    console.log(status);
                });
            }
        };


/* Request (RFI)
  this is the process to send an RFI. Store all values in window session
  and wipe session after added to db */
}).controller('requestController', function($rootScope, $window, $scope, $routeParams, $cookieStore, $location, $http) {

            $scope.userInfo = $rootScope.userInfo = $cookieStore.get('epiUserInfo');

        // this will pre-fill the event form with session values if back button is used
        if($window.sessionStorage.length > 0) {
            $scope.formData = {};
            $scope.formData.title = $window.sessionStorage.title;
            $scope.formData.additionalText = $window.sessionStorage.additionalText;
            $scope.formData.description = $window.sessionStorage.description;
            $scope.formData.location = $window.sessionStorage.location;
            $scope.formData.disease = $window.sessionStorage.disease;
            $scope.formData.latlon = $window.sessionStorage.latlon;
        }

    // if there's an alertid passed in from ProMED, get the info to prepopulate the fields
    $scope.alertid = $routeParams.alertid;
    if($scope.alertid && ($scope.alertid !== $window.sessionStorage.alertid)) {
        $window.sessionStorage.alertid = $scope.alertid;
        var alertData = {};
        alertData['alert_id'] = $scope.alertid;
        $http({ url: 'scripts/getalert.php', method: "POST", data: alertData
            }).success(function (data, status, headers, config) {
                $scope.formData = data; // this pre-populates the values on the form
                $scope.formData.additionalText = '';
                $window.sessionStorage.title = data['title'];
                $window.sessionStorage.description = data['description'];
                $window.sessionStorage.location = data['location'];
                $window.sessionStorage.latlon = data['latlon'];
                $window.sessionStorage.disease = data['disease'];
                $window.sessionStorage.species = data['species'];
                $window.sessionStorage.additionalText = '';

            //insert arabic summary into description if available
            var a = data['arabic_text'];
            if (a != ''){
                var d = data['description'];
                d = d.replace("<http://www.isid.org>", "<http://www.isid.org>" +'\n\n' + a + '\n');
                $window.sessionStorage.description = d;
                $scope.formData.description = d;
            }

        });
    }


    /* step 1: save the event information in session variable, and 
    filter FETPs for next screen based on location chosen */
    $scope.storeEvent = function(formData, isValid) {
        if(isValid) {
            // jquery hack to get the latlon hidden value and autocomplete for location (angular bug)
            formData['latlon'] = $("#default_location").val();
            formData['location'] = $("#searchTextField").val();

            if(!formData['latlon']) {
                alert("Geolocation failed - please scroll down and select a location from the auto-suggester in the location field so that we have the coordinates of the event.");
                $scope.formData.location = '';
                return false;
            }

            // otherwise save the session data, get FETPs near location and move on
            $window.sessionStorage.title = formData['title'];
            $window.sessionStorage.description = formData['description'];
            $window.sessionStorage.disease = formData['disease'];
            $window.sessionStorage.additionalText = formData['additionalText'] ? formData['additionalText'] : '';

            // if you're here from the back button and the location hasn't changed,
            // don't change the FETP filtering criteria

            if(!$window.sessionStorage.searchBox || ($window.sessionStorage.location != formData['location'])) {
                $window.sessionStorage.location = formData['location'];
                $window.sessionStorage.latlon = formData['latlon'];

                $http({ url: 'scripts/filter.php', method: "POST", data: formData 
                    }).success(function (data, status, headers, config) {
                        $window.sessionStorage.userIds = data['userIds'];
                        $window.sessionStorage.numFetps = data['userList']['sending'];
                        $window.sessionStorage.searchBox = data['bbox'];
                        $window.sessionStorage.searchType = 'radius';
                        $location.path('/request2');
                    }).error(function (data, status, headers, config) {
                        console.log(status);
                    });
            } else {
                $location.path('/request2');
            }
        }
    };

    $scope.numFetps = $window.sessionStorage.numFetps;
    $scope.filePreview = $window.sessionStorage.filePreview;

    /* step 2: Filter FETP: calculate the number of users based on check & uncheck */
    if($location.path() == "/request2") {

        // initialize default radio buttons - radius select checked by default
        // unless it's a back-button, then take from session
        if($window.sessionStorage.searchType == "country") {
            $scope.radiussel = false;
            $scope.formData.countries = $window.sessionStorage.countries.split(",");
        } else {
             $scope.radiussel = true;
        }

        // bounding box around event location
        // show/hide the submit to next step only if there are FETPs to receive the email
        $scope.submitDisabled = $scope.numFetps > 0 ? false : true;

        $scope.bbox = $window.sessionStorage.searchBox.split(",");
        var bounds = new google.maps.LatLngBounds(new google.maps.LatLng($scope.bbox[0], $scope.bbox[2]), new google.maps.LatLng($scope.bbox[1], $scope.bbox[3]));
        $scope.rectangle = {bounds: bounds, stroke: { color: '#08B21F', weight: 2, opacity: 1 }, fill: { color: '#08B21F', opacity: 0.5 }, editable: true, visible: true };
        var latlonarr = $window.sessionStorage.latlon.split(",");
        /* values for the map */
        $scope.map = { center: { latitude: latlonarr[0], longitude: latlonarr[1] }, zoom: 5 }
        $scope.options = {scrollwheel: false};
        /* only show FETPs on a map to super-users */
        var query = {};
        query['uid'] = $scope.userInfo.uid;
        query['centerlat'] = latlonarr[0];
        query['centerlon'] = latlonarr[1];
        $http({ url: 'scripts/getmarkers.php', method: "POST", data: query
                 }).success(function (data, status, headers, config) {
                 if(data['status'] == "success") {
                    $scope.markers = data['markers'];
                 }
             });

        /* rectangle change event */
        $scope.eventsRectangle = {
            bounds_changed: function(rectangle) {
                var filterData = {};
                var southwest = rectangle.bounds.getSouthWest();
                var northeast = rectangle.bounds.getNorthEast();
                $scope.radiussel = true; // if radius changes without changing radio button
                $window.sessionStorage.searchType = 'radius';
                filterData['bbox'] = new Array(southwest.lat(), northeast.lat(), southwest.lng(), northeast.lng());
                $http({ url: 'scripts/filter.php', method: "POST", data: filterData 
                      }).success(function (filtereddata, status, headers, config) {
                         $window.sessionStorage.searchBox = filtereddata['bbox'];
                         $window.sessionStorage.userIds = filtereddata['userIds'];
                         $window.sessionStorage.numFetps = $scope.numFetps = filtereddata['userList']['sending'];
                         $scope.submitDisabled = $scope.numFetps > 0 ? false : true;
                      });
            }
        }
    }

    /* check and uncheck training type filters */
    $scope.recalcUsers = function(filterData, whichclicked) {
        $window.sessionStorage.searchType = filterData['filtertype'] = whichclicked;
        if(whichclicked == "country") {
            // select the right radio button if a country is selected without changing radio 
            $scope.radiussel = false;
            $window.sessionStorage.countries = filterData['countries'];
        } else {
            filterData['bbox'] = $window.sessionStorage.searchBox.split(",");
            $scope.radiussel = true;
        }
        $http({ url: 'scripts/filter.php', method: "POST", data: filterData 
            }).success(function (filtereddata, status, headers, config) {
                $window.sessionStorage.userIds = filtereddata['userIds'];
                $window.sessionStorage.numFetps = $scope.numFetps = filtereddata['userList']['sending'];
                $scope.submitDisabled = $scope.numFetps > 0 ? false : true;
                if(filtereddata['bbox']) {
                    $window.sessionStorage.searchBox = filtereddata['bbox'];
                }
            });
    }

    /* step 2 submit button - build the email text and move on to step 3 */
    $scope.buildEmailText = function() {
        var formData = {};
        formData['additionalText'] = $window.sessionStorage.additionalText;
        formData['title'] = $window.sessionStorage.title;
        formData['location'] = $window.sessionStorage.location;
        formData['description'] = $window.sessionStorage.description;
        // overwrite the old file preview if it exists
        if(typeof($window.sessionStorage.filePreview) != "undefined") {
            formData['file_preview'] = $window.sessionStorage.filePreview;
        }
        $http({ url: 'scripts/buildrequest.php', method: "POST", data: formData 
        }).success(function (respdata, status, headers, config) {
            $window.sessionStorage.filePreview = respdata['file_preview'];
            $location.path('/request3');
        });
    }

    /* step 3 : save all event RFI data in database and send the request */
    $scope.sendRequest = function() {
            $scope.submitDisabled = true;
            var formData = {};
            if($window.sessionStorage.searchType == "radius") {
                formData['search_box'] = $window.sessionStorage.searchBox;
            } else {
                formData['search_countries'] = $window.sessionStorage.countries;
            }
            formData['uid'] = $scope.userInfo.uid; //requester of RFI
            formData['fetp_ids'] = $window.sessionStorage.userIds;
            formData['latlon'] = $window.sessionStorage.latlon;
            formData['location'] = $window.sessionStorage.location;
            formData['title'] = $window.sessionStorage.title;
            formData['description'] = $window.sessionStorage.description;
            formData['additionalText'] = $window.sessionStorage.additionalText;
            formData['disease'] = $window.sessionStorage.disease;
            formData['alert_id'] = $window.sessionStorage.alertid;
            $http({ url: 'scripts/sendrequest.php', method: "POST", data: formData 
            }).success(function (respdata, status, headers, config) {
                // empty out the form values since you've submitted so they aren't prefilled next time
                $window.sessionStorage.clear();
                $location.path('/success/3');
                $scope.submitDisabled = false;
            });
        };

        /* clear request form */
        $scope.clearRequest = function() {
            if (confirm('Are you sure you want to clear this request form?')) {
                $scope.formData = {};
                $window.sessionStorage.title = '';
                $window.sessionStorage.description = '';
                $window.sessionStorage.location = '';
                $window.sessionStorage.latlon = '';
                $window.sessionStorage.additionalText = '';
                $window.sessionStorage.disease = '';
                $window.sessionStorage.species = '';
                $window.sessionStorage.alertid = '';
            } else {

            }

        }

        /* edit request by owner or superuser */
    }).controller('editRequestController', function($rootScope, $window, $scope, $routeParams, $cookieStore, $location, $http) {

        $scope.userInfo = $rootScope.userInfo = $cookieStore.get('epiUserInfo');

        // prepopulate edit request form
        $scope.eventid = $routeParams.id;
        if($scope.eventid) {
            var eventData = {};
            eventData['event_id'] = $scope.eventid;
            $http({ url: 'scripts/getrequest.php', method: "POST", data: eventData
            }).success(function (data, status, headers, config) {
                $scope.formData = data; // this pre-populates the values on the form
                $scope.formData.additionalText = data['personalized_text'];
            });
        }

        $scope.updateEvent = function(formData, isValid) {
            if (isValid) {
                // jquery hack to get the latlon hidden value and autocomplete for location (angular bug)
                formData['latlon'] = $("#default_location").val();
                formData['location'] = $("#searchTextField").val();

                if(!formData['latlon']) {
                    alert("Geolocation failed - please scroll down and select a location from the auto-suggester in the location field so that we have the coordinates of the event.");
                    $scope.formData.location = '';
                    return false;
                }

                // update event
                $http({ url: 'scripts/updaterequest.php', method: "POST", data: formData
                }).success(function (data, status, headers, config) {
                    if (data['status'] == 'success'){
                        $location.path('/success/6');
                    }
                    else{
                        console.log(data['reason']);
                    }
                }).error(function (data, status, headers, config) {
                    console.log(status);
                });

            }
        };

    }).controller('responseController', function($scope, $location, $routeParams, $cookieStore, $http) {
        $scope.userInfo = $cookieStore.get('epiUserInfo');
        var formData = {};
        formData['uid'] = $scope.userInfo.uid;
        formData['org_id'] = $scope.userInfo.organization_id;
        formData['fetp_id'] = $scope.userInfo.fetp_id;
        formData['response_id'] = $routeParams.response_id;
        $http({ url: 'scripts/getresponse.php', method: "POST", data: formData 
            }).success(function (respdata, status, headers, config) {
                $scope.isAuthorizedToSee = respdata['status'] == "failed" ? false : true;
                $scope.isAuthorizedToFollowup = respdata['authorized_to_followup'] ? true : false;
                $scope.filePreview = respdata['filePreview'] ? respdata['filePreview'] : '';
                $scope.responseObj = respdata;
            });


/* Success controller - for the success page */
}).controller('successController', function($scope, $routeParams, $cookieStore) {
        $scope.userInfo = $cookieStore.get('epiUserInfo');
        var messages = {};
        messages[1] = "You have been signed up.";
        messages[2] = 'The moderator who initiated the request has been notified. If you get any information on this RFI in the future, please come back to this RFI and click on "Yes, respond to this RFI"';
        messages[3] = "Your RFI has been sent to the selected members.";
        messages[4] = "Your RFI has been closed and an email has gone out to the original members contacted.";
        messages[5] = "Your RFI has been reopened and an email has gone out to the original members contacted.";
        messages[6] = "Your RFI has been updated.";
        messages[7] = "Your RFI has been deleted.";
        messages[8] = "Your RFI responses have been updated.";
        $scope.id = $routeParams.id;
        $scope.eid = $routeParams.eid;
        $scope.messageResponse = {};
        $scope.messageResponse.text = messages[$scope.id];

    }).controller('approvalController', function($scope, $http, $location, $route, $cookieStore) {

        // only allow superusers for admin
        $scope.userInfo = $cookieStore.get('epiUserInfo');
        $scope.superuser = (typeof($scope.userInfo) != "undefined") ? $scope.userInfo.superuser: false;
        $scope.showpage = false;
        $scope.membersavailable = false;
        $scope.eventsavailable = false;
        $scope.num_applicants = 0;
        $scope.num_accepted = 0;
        $scope.num_approved = 0;
        $scope.num_inactive = 0;
        $scope.num_denied = 0;
        $scope.num_preapproved = 0;
        $scope.num_setpassword = 0;

        var data = {};
            $http({ url: 'scripts/approval.php', method: "POST", data: data
            }).success(function (respdata, status, headers, config) {
                for (var n in respdata){
                    respdata[n]['member_id'] = parseInt(respdata[n]['member_id']);  // use int so orberby works
                    if (respdata[n]['status'] == 'Pending'){
                        $scope.num_accepted++;
                    }
                    if (respdata[n]['status'] == 'Approved'){
                        $scope.num_approved++;
                    }
                    if (respdata[n]['status'] == 'Inactive'){
                        $scope.num_inactive++;
                    }
                    if (respdata[n]['status'] == 'Denied'){
                        $scope.num_denied++;
                    }
                    if (respdata[n]['status'] == 'Pre-approved'){
                        $scope.num_preapproved++;
                    }
                    if (respdata[n]['pword'] == 'Yes'){
                        $scope.num_setpassword++;
                    }
                }
                $scope.applicants = respdata;
                $scope.num_applicants = $scope.applicants.length;
                $scope.showpage = true;
            });

        $scope.approveApplicant = function(maillist_id, action){
            data = {maillist_id: maillist_id, action:action};
            $http({ url: 'scripts/setMemberStatus.php', method: "POST", data: data
            }).success(function (respdata, status, headers, config) {
                if (respdata['status'] == 'success'){
                    for (var n in $scope.applicants){
                        if ($scope.applicants[n].maillist_id == maillist_id){
                            $scope.applicants[n].status = respdata['member_status'];
                        }
                    }
                } else {
                    alert(respdata['message']);
                }
            });
        };

        $scope.downloadMembers = function(){
            $scope.isRouteLoading = true;
            $http({ url: 'scripts/downloadMembers.php', method: "POST"
            }).success(function (respdata, status, headers, config) {
                $scope.membersavailable = true;
                $scope.isRouteLoading = false;
            });
        };

        $scope.downloadEvents = function(){
            $scope.isRouteLoading = true;
            $http({ url: 'scripts/downloadEventStats.php', method: "POST"
            }).success(function (respdata, status, headers, config) {
                $scope.eventsavailable = true;
                $scope.isRouteLoading = false;
            });
        };

        $scope.sendReminder = function(action){
            if (confirm('Are you sure you want to send reminder emails?')) {
                data = {action: action};
                $http({
                    url: 'scripts/sendreminder.php', method: "POST", data: data
                }).success(function (respdata, status, headers, config) {
                    alert(respdata.length + ' emails sent.');
                });
            } else {

            }
        };

        $scope.editApplicant = function(uid, action){
            $location.path('/application/' + uid + '/' +action + '/member');
        };

        $scope.deleteApplicant = function(uid){
            if (confirm('Are you sure you want to delete this user?')) {
                data = {uid: uid};
                $http({
                    url: 'scripts/deleteuser.php', method: "POST", data: data
                }).success(function (data, status, headers, config) {
                    if (data['status'] == 'success')
                        $route.reload();
                    else{
                        alert(data['message']);
                    }
                }).error(function (data, status, headers, config) {
                console.log(status);
            });

            } else {
            }
        };

    }).controller('testController', function($scope, $cookieStore, $http, $location) {

        // youtube codes
        $scope.code1 = 'LYgaHDL00x0'; // Introduction to Innovative Disease Surveillance Course
        $scope.code2 = '0ZVnTS7Bo3A'; // The EpiCore Training Course

        $scope.passed = false;

        // grade the test and approve member after they pass the test
        $scope.grade = function(test) {
            $scope.attempted = true;

            var missed = [];
            if (test.q1 != 'E')
                missed.push('1');
            if (test.q2 != 'C')
                missed.push('2');
            if (test.q3 != 'B')
                missed.push('3');
            if (test.q4 != 'B')
                missed.push('4');
            if (test.q5 != 'E')
                missed.push('5');

            if (missed != ''){
                $scope.test_message = "Missed question(s): " + missed + ".  Please take the test again.";
            }else{  // approve member
                $scope.passed = true;
                //get member info
                $scope.userInfo = $cookieStore.get('epiUserInfo');

                // check member status add set to approved if status is accepted ('P')
                if ($scope.userInfo.status == 'P') {
                    var status = 'approved';
                    var data = {fetp_id: $scope.userInfo.fetp_id, status: status};
                    $http({
                        url: 'scripts/approveUser.php', method: "POST", data: data
                    }).success(function (respdata, status, headers, config) {
                        if (respdata['status'] == 'success') {
                            $scope.test_message = "You passed the test! <br><br> You can now login to the Epicore platform using your email and password.  Your certificate of recognition is available on the training page after you login.";
                            $scope.passed = true;
                            // update cookie
                            $scope.userInfo.status = 'A';
                            $cookieStore.put('epiUserInfo',$scope.userInfo);
                        }
                        else {
                            console.log(respdata['message']);
                        }
                    });
                }else{ // member already approved
                    $scope.test_message = "You passed the test! <br><br> You can now login to the Epicore platform using your email and password. Your certificate of recognition is available on the training page after you login.";
                    $scope.passed = true;
                }
            }

        };

    }).controller('certController', function($scope, $cookieStore, $http) {

    //get member info
    $scope.userInfo = $cookieStore.get('epiUserInfo');
    var data = {};
    data['uid'] = $scope.userInfo.fetp_id;
    data['idtype'] = 'fetp';
    $http({ url: 'scripts/getapplicant.php', method: "POST", data: data
    }).success(function (data, status, headers, config) {

        $scope.member_name = data.firstname + ' ' + data.lastname;
        $scope.approve_date = data.approve_date;
        var month = new Array("January", "February", "March",
            "April", "May", "June", "July", "August", "September",
            "October", "November", "December");
        var d = data.approve_date.split(" ");
        d = d[0].split("-");
        $scope.approve_date = d[2] + "th Day of " + month[(Number(d[1]))-1] + ", " + d[0];
    });

        /* filter for trusted HTML */
    }).filter('to_trusted', ['$sce', function($sce){
        return function(text) {
            return $sce.trustAsHtml(text);
        };

    }]);

