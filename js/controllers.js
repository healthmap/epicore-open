angular.module('EpicoreApp.controllers', []).

/* User - includes Login & Logout */
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

    $scope.signup = function(uservals, isValid) {
        if (!isValid){
            $scope.signup_message = 'Form not complete. Please make sure all input boxes are filled out.';
            return false;
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
                $cookieStore.put('epiUserInfo', {'uid':data['uinfo']['user_id'], 'isPromed':isPromed, 'isOrganization':$rootScope.isOrganization, 'organization_id':data['uinfo']['organization_id'], 'organization':data['uinfo']['orgname'], 'fetp_id':data['uinfo']['fetp_id'], 'email':data['uinfo']['email'], 'uname':data['uinfo']['username'], 'active':isActive});
                $rootScope.error_message = 'false';
                // FETPs that aren't activated yet don't get review page
                if(data['uinfo']['fetp_id'] && data['uinfo']['active'] == 'N') {
                    var redirpath = '/welcome';
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
                            'active': isActive
                        });
                        $rootScope.error_message = 'false';
                        var redirpath = '/welcome';
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
            {name: 'Palestinian Territory, Occupied', code: 'PS'},
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
            {name: 'Serbia and Montenegro', code: 'CS'},
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
            {name: 'Taiwan, Province of China', code: 'TW'},
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

}).controller('mapController', function($scope, $http) {
        $scope.map = { center: { latitude: 15, longitude: 18 }, zoom: 3 }
        $scope.options = {scrollwheel: false};
        $scope.markers = [];
        // only show FETPs on a map to super-users
        var query = {};
        query['uid'] = $scope.userInfo.uid;
        $http({ url: 'scripts/getmarkers.php', method: "POST", data: query
        }).success(function (data, status, headers, config) {
            if(data['status'] == "success") {
                $scope.markers = data['markers'];
            }
        });

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

        eventAPIservice.getEvents($scope.id).success(function (response) {
            $scope.isOrganization = $scope.userInfo.fetp_id > 0 ? false : true;
            // if RFI requester is the logged in user or of same org, they get different action items
            if(response.EventsList != null) {
                $scope.isAuthorizedToFollowup = $scope.userInfo.organization_id == response.EventsList.org_requester_id ? true : false;
                $scope.changeStatusText = response.EventsList.estatus == "C" ? 'Reopen' : 'Close';
                $scope.changeStatusType = response.EventsList.estatus == "C" ? 'reopen' : 'close';
                $scope.isAuthorizedFETP = false;
                if (response.EventsList.fetp_ids != null && response.EventsList.fetp_ids.indexOf($scope.userInfo.fetp_id) != -1) {
                    $scope.isAuthorizedFETP = true;
                }
            }

            $scope.eventsList = response.EventsList;
            $scope.filePreview = response.EventsList.filePreview ? response.EventsList.filePreview : '';
        });

        $scope.sendFollowup = function(formData, isValid) {
            if(isValid) {
                formData['uid'] = $scope.userInfo.uid;
                formData['event_id'] = $routeParams.id;
                if($routeParams.response_id) {
                    formData['response_id'] = $routeParams.response_id;
                }
                $http({ url: 'scripts/sendfollowup.php', method: "POST", data: formData
                }).success(function (data, status, headers, config) {
                    $location.path('/success/3');
                });
            }
        }

        $scope.changeRequestStatus = function(formData, thestatus, isValid) {
            if(isValid) {
                formData['event_id'] = $routeParams.id
                formData['uid'] = $scope.userInfo.uid;
                formData['thestatus'] = thestatus;
                $http({ url: 'scripts/changestatus.php', method: "POST", data: formData
                }).success(function (data, status, headers, config) {
                    var pathid = thestatus == "Reopen" ? 5 : 4;
                    $location.path('/success/'+pathid);
                });
            }
        }

        $scope.sendResponse = function(formData, isValid) {
            if(formData['response_permission'] == 0 || isValid) {
                // if user has chosen "I have nothing to contribute" button, 
                // formData comes in as object response_permissions: 0 
                formData['event_id'] = $routeParams.id
                formData['fetp_id'] = $scope.userInfo.fetp_id;
                $http({ url: 'scripts/sendresponse.php', method: "POST", data: formData
                }).success(function (data, status, headers, config) {
                    $location.path('/'+data['path']);
                });
            }
        }


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
        $scope.formData.latlon = $window.sessionStorage.latlon;
    }

    // if there's an alertid passed in, get the info to prepopulate the fields
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
                $window.sessionStorage.additionalText = '';
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
            formData['alert_id'] = $window.sessionStorage.alertid;
            $http({ url: 'scripts/sendrequest.php', method: "POST", data: formData 
            }).success(function (respdata, status, headers, config) {
                // empty out the form values since you've submitted so they aren't prefilled next time
                $window.sessionStorage.clear();
                $location.path('/success/3');
            });
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
        messages[2] = "Your response has been sent to the moderator who initiated the request.";
        messages[3] = "Your request has been sent to the selected FETPs.";
        messages[4] = "Your request has been closed and an email has gone out to the original FETPs contacted.";
        messages[5] = "Your request has been reopened and an email has gone out to the original FETPs contacted.";
        $scope.id = $routeParams.id;
        $scope.messageResponse = {};
        $scope.messageResponse.text = messages[$scope.id];

    }).controller('approvalController', function($scope, $http) {
        var data = {};
            $http({ url: 'scripts/approval.php', method: "POST", data: data
            }).success(function (respdata, status, headers, config) {
                $scope.applicants = respdata;
            });

        $scope.approveApplicant = function(maillist_id, action){
            data = {maillist_id: maillist_id, action:action};
            $http({ url: 'scripts/approval.php', method: "POST", data: data
            }).success(function (respdata, status, headers, config) {
                $scope.applicants = respdata;
            });
        };

        /* filter for trusted HTML */
    }).filter('to_trusted', ['$sce', function($sce){
        return function(text) {
            return $sce.trustAsHtml(text);
        };

    }]);

