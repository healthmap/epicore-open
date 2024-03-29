angular.module('EpicoreApp.controllers', []).

/* User - includes signup, Reset password, Login & Logout */
controller('userController', function($rootScope, $routeParams, $scope, $route, $cookies, $cookieStore, $location, $http, $window, urlBase , epicoreMode, $localStorage, epicoreCountries, epicoreVersion, $cordovaTouchID) {


    $scope.mobile = (epicoreMode == 'mobile') ? true: false;
    $scope.epicore_version = epicoreVersion;

    $scope.isRouteLoading = false;
    $scope.autologin = true;
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

    /* pre-populate application form */
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
        $http({ url: urlBase + 'scripts/getapplicant.php', method: "POST", data: data
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

    /* get user cookie info */
    $scope.userInfo = $rootScope.userInfo = $cookieStore.get('epiUserInfo');

    /* countries and codes */
    $scope.countries = epicoreCountries;

    // pre-populate saved username and password for mobile app
    if ($scope.mobile && (typeof($localStorage.username) != 'undefined') && (typeof($localStorage.password) != "undefined") ){
        $scope.formData = {};
        $scope.formData.username = $localStorage.username;
        $scope.formData.password = $localStorage.password;
    }

    /* set some global variables for Tephinet integration */
    /*$http({ url: urlBase + 'scripts/getvars.php', method: "POST"
     }).success(function (data, status, headers, config) {
     $rootScope.tephinetBase = data['tephinet_base'];
     });*/

    $scope.signup = function(uservals, isValid) {

        $scope.attempted = true;

        // validate checkboxes
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
                    url: urlBase + 'scripts/updateuser.php', method: "POST", data: uservals
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
                    url: urlBase + 'scripts/signup.php', method: "POST", data: uservals
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

    // Sign in with Touch id for iOS or login with username & password
    $scope.mobile_message = "";
    $scope.signIn = function(){
      $scope.autologin = true;
      if ($scope.mobile && (typeof($localStorage.mobile_platform) != 'undefined') && ($localStorage.mobile_platform == 'iOS')){
        // check touch id support of iOS
        $cordovaTouchID.checkSupport().then(function() {
          // success, TouchID supported
          // iOS touch id authentication
          $cordovaTouchID.authenticate("Use touch id to login or cancel to login with password.").then(function() {
              // success
              // username and password must be set first time to use touch id
              if ((typeof($localStorage.username) !='undefined') && (typeof($localStorage.password) !='undefined')
                  && $localStorage.username && $localStorage.password){
                    $scope.formData.username = $localStorage.username;
                    $scope.formData.password = $localStorage.password;
                    $scope.userLogin($scope.formData);
              } else {
                $scope.autologin = false;
                $scope.formData.password = "";
                $scope.mobile_message = "Please enter Epicore email (username) and password to use touch id";
                //alert('Please enter Epicore email (username) and password to use touch id')
              }
            }, function () {
              $scope.autologin = false;
              $scope.formData.password = "";
              $scope.mobile_message = "Please enter email (username) and password to login.";
              //alert('Please enter email (username) and passord to login.');// cancel touch id authentication
          });

        }, function (error) {
          $scope.autologin = false;
          $scope.formData.password = "";
          //alert('Touch id not supported or you have not enabled touch id on your device.');
          //alert(error); // TouchID not supported
        });
      } else {  // login no touch ID
        $scope.autologin = false;
        $scope.formData.password = "";
      }
    }

    /* log in */
    $scope.userLogin = function(formData) {
        $scope.isRouteLoading = true;
        // no formdata passed, get ticket id and (optional) event_id from URL
        if(typeof(formData) == "undefined") {
            formData = {};
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
            formData['app'] = 'web';
            formData['epicore_version'] = epicoreVersion;

        } else { // from login page
            // save mobile or web platform info
            if ($scope.mobile) {
                formData['reg_id'] = $localStorage.registrationId; // regstration id for push notifications
                formData['model'] = $localStorage.mobile_model; // eg. iPhone 6
                formData['platform'] = $localStorage.mobile_platform; // eg. iOS, Android
                formData['os_version'] = $localStorage.mobile_os_version; // eg iOS 10.2
                formData['app'] = 'mobile';
                formData['event_id'] = $localStorage.event_id; // event_id from push notification
                $localStorage.event_id = null; // clear event_id for next login
            }
            formData['epicore_version'] = epicoreVersion;
        }
        if(!formData['ticket_id'] && !formData['alert_id'] && !formData['event_id'] && !$scope.loginForm.$valid) {
            $scope.isRouteLoading = false;
            return;
        }

        $http({ url: urlBase + 'scripts/login.php', method: "POST", data: formData
        }).success(function (data, status, headers, config) {
            if(data['status'] == "success") {
                // determines if user is an organization or FETP
                $rootScope.isOrganization = data['uinfo']['organization_id'] > 0 ? true : false;
                var isPromed = data['uinfo']['organization_id'] == 4 ? true : false;
                var isActive = typeof(data['uinfo']['active']) != "undefined" ? data['uinfo']['active'] : 'Y';
                var memberLocations = typeof(data['uinfo']['active']) != "undefined" ? data['uinfo']['locations'] : false;
                var newUserInfo = {'uid':data['uinfo']['user_id'], 'isPromed':isPromed, 'isOrganization':$rootScope.isOrganization,
                    'organization_id':data['uinfo']['organization_id'], 'organization':data['uinfo']['orgname'], 'fetp_id':data['uinfo']['fetp_id'],
                    'email':data['uinfo']['email'], 'uname':data['uinfo']['username'], 'active':isActive, 'status':data['uinfo']['status'],
                    'superuser':data['uinfo']['superuser'], 'locations':memberLocations };


                // save username and password
                $localStorage.username = formData['username'];
                $localStorage.password = formData['password'];

                // save user in cookie
                $cookieStore.put('epiUserInfo', newUserInfo);

                // save user in local storage for mobile app
                $localStorage.user = newUserInfo;

                $rootScope.error_message = false;
                // FETPs that aren't activated yet don't get review page
                if(data['uinfo']['fetp_id'] && data['uinfo']['active'] == 'N') {
                    var redirpath = '/training';
                } else {
                    var redirpath = typeof(querystr['redir']) != "undefined" ? querystr['redir'] : '/'+data['path'];
                }

                $scope.isRouteLoading = false;
                $scope.autologin = false;
                $location.path(redirpath);

            } else {
                $scope.isRouteLoading = false;
                $rootScope.error_message = true;
                $scope.autologin = false;
                $route.reload();

            }
        }).error(function (data, status, headers, config) {
            $scope.isRouteLoading = false;
            $scope.autologin = false;
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
                    url: urlBase + 'scripts/setpassword.php', method: "POST", data: formData
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
                        $rootScope.error_message = false;
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
                $rootScope.error_message_pw = 'Invalid email address';
                return false;
            }
            else {
                $http({
                    url: urlBase + 'scripts/resetpassword.php', method: "POST", data: formData
                }).success(function (data, status, headers, config) {
                    if (data['status'] == "success") {
                        $scope.isRouteLoading = false;
                        $rootScope.error_message_pw = 'Please check your email for instructions to reset your password.';
                        $route.reload();
                    } else {
                        $scope.isRouteLoading = false;
                        $rootScope.error_message_pw = 'Invalid email address';
                        $route.reload();
                    }
                }).error(function (data, status, headers, config) {
                    $rootScope.error_message_pw = 'Invalid email address';
                    $scope.isRouteLoading = false;
                    console.log(status);
                });
            }
    }


    // auto-login with mobile push notification
    // This needs to be last in the controller
     if ($scope.mobile && ($localStorage.event_id !== null) && (typeof $localStorage.event_id !== 'undefined') && (parseInt($localStorage.event_id) > 0)) {
         $scope.autologin = true;
         $scope.formData.username = $localStorage.username;
         $scope.formData.password = $localStorage.password;
         $scope.userLogin($scope.formData);
     }

}).controller('mapController', function($scope, $http, $cookieStore, urlBase) {
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
    $http({ url: urlBase + 'scripts/getallmarkers.php', method: "POST"
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
}).controller('eventsController', function($scope, $routeParams, $cookieStore, $location, $http, eventAPIservice, urlBase, epicoreMode) {

        $scope.mobile = (epicoreMode == 'mobile') ? true: false;
        $scope.isRouteLoading = true;
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
                $scope.isRequester = response.EventsList.requester_id == $scope.userInfo.uid ? true:false;
                if (response.EventsList.fetp_ids != null && response.EventsList.fetp_ids.indexOf($scope.userInfo.fetp_id) != -1) {
                    $scope.isAuthorizedFETP = true;
                }
                if (response.EventsList.fetp_ids){
                    $scope.num_fetp = response.EventsList.fetp_ids.length;
                }

                $scope.eventsList = response.EventsList;
                $scope.filePreview = response.EventsList.filePreview ? response.EventsList.filePreview : '';
            }

            //$scope.closedEvents = response.closedEvents;

            // get response
            $scope.response_text = '';
            if ($routeParams.response_id){
                var formData = {};
                formData['uid'] = $scope.userInfo.uid;
                formData['org_id'] = $scope.userInfo.organization_id;
                formData['fetp_id'] = $scope.userInfo.fetp_id;
                formData['response_id'] = $routeParams.response_id;
                $http({ url: urlBase + 'scripts/getresponse.php', method: "POST", data: formData
                }).success(function (respdata, status, headers, config) {
                    $scope.response_text = respdata['response'];
                    $scope.responder_id = respdata['responder_id'];
                    $scope.permission_id = respdata['response_permission_id'];
                });
            }

            // count unrated responses in closed events
            $scope.num_notrated_responses = 0;
            if ($scope.onOpen) {
                $scope.num_notrated_responses = response.numNotRatedResponses;
            } else if ($scope.eventsList){
                for (var n in $scope.eventsList.yours) {
                    $scope.num_notrated_responses += parseInt($scope.eventsList.yours[n].num_notrated_responses);
                }
            }

            // count responses with content
            for (var h in $scope.eventsList.history) {
                if (($scope.eventsList.history[h].permission !== '0') && ($scope.eventsList.history[h].type == 'Member Response')
                    && ($scope.userInfo.uid)){
                    $scope.validResponses++;
                }
            }

            // check unclosed RFIs with no activity in the last two weeks
            Date.prototype.yyyymmdd = function() {
                var yyyy = this.getFullYear().toString();
                var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
                var dd  = this.getDate().toString();
                return yyyy + "-" + (mm[1]?mm:"0"+mm[0]) + "-" + (dd[1]?dd:"0"+dd[0]); // padding
            };
            var d = new Date();
            $scope.date = d.setDate(d.getDate() - 14); // now minus 14 days
            $scope.unclosed = 0;
            for (var n in $scope.eventsList.yours){
                newdate = $scope.eventsList.yours[n].num_followups[0].iso_date;
                if (newdate < d.yyyymmdd()) {
                    $scope.unclosed++;
                }
            }
            $scope.isRouteLoading = false;
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
                $http({ url: urlBase + 'scripts/sendfollowup.php', method: "POST", data: formData
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
                $http({ url: urlBase + 'scripts/changestatus.php', method: "POST", data: formData
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
                $http({ url: urlBase + 'scripts/sendresponse.php', method: "POST", data: formData
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
                    url: urlBase + 'scripts/deleteEvent.php', method: "POST", data: data
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
}).controller('requestController', function($rootScope, $window, $scope, $routeParams, $cookieStore, $location, $http, urlBase) {

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
        $http({ url: urlBase + 'scripts/getalert.php', method: "POST", data: alertData
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

                $http({ url: urlBase + 'scripts/filter.php', method: "POST", data: formData
                    }).success(function (data, status, headers, config) {
                        $window.sessionStorage.userIds = data['userIds'];
                        $window.sessionStorage.numFetps = data['userList']['sending'];
                        $window.sessionStorage.numUniqueFetps = data['uniqueList']['sending'];
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
    $scope.numUniqueFetps = $window.sessionStorage.numUniqueFetps;
    $scope.filePreview = $window.sessionStorage.filePreview;

    /* step 2: Filter FETP: calculate the number of users based on check & uncheck */
    if($location.path() == "/request2" || $location.path() == "/members") {

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
        $http({ url: urlBase + 'scripts/getmarkers.php', method: "POST", data: query
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
                $http({ url: urlBase + 'scripts/filter.php', method: "POST", data: filterData
                      }).success(function (filtereddata, status, headers, config) {
                         $window.sessionStorage.searchBox = filtereddata['bbox'];
                         $window.sessionStorage.userIds = filtereddata['userIds'];
                         $window.sessionStorage.numFetps = $scope.numFetps = filtereddata['userList']['sending'];
                         $window.sessionStorage.numUniqueFetps = $scope.numUniqueFetps = filtereddata['uniqueList']['sending'];
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
        $http({ url: urlBase + 'scripts/filter.php', method: "POST", data: filterData
            }).success(function (filtereddata, status, headers, config) {
                $window.sessionStorage.userIds = filtereddata['userIds'];
                $window.sessionStorage.numFetps = $scope.numFetps = filtereddata['userList']['sending'];
                $window.sessionStorage.numUniqueFetps = $scope.numUniqueFetps = filtereddata['uniqueList']['sending'];
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
        $http({ url: urlBase + 'scripts/buildrequest.php', method: "POST", data: formData
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
            $http({ url: urlBase + 'scripts/sendrequest.php', method: "POST", data: formData
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
    }).controller('editRequestController', function($rootScope, $window, $scope, $routeParams, $cookieStore, $location, $http, urlBase) {

        $scope.userInfo = $rootScope.userInfo = $cookieStore.get('epiUserInfo');

        // prepopulate edit request form
        $scope.eventid = $routeParams.id;
        if($scope.eventid) {
            var eventData = {};
            eventData['event_id'] = $scope.eventid;
            $http({ url: urlBase + 'scripts/getrequest.php', method: "POST", data: eventData
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
                $http({ url: urlBase + 'scripts/updaterequest.php', method: "POST", data: formData
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

    }).controller('responseController', function($scope, $location, $routeParams, $cookieStore, $http, urlBase) {
        $scope.userInfo = $cookieStore.get('epiUserInfo');
        var formData = {};
        formData['uid'] = $scope.userInfo.uid;
        formData['org_id'] = $scope.userInfo.organization_id;
        formData['fetp_id'] = $scope.userInfo.fetp_id;
        formData['response_id'] = $routeParams.response_id;
        $http({ url: urlBase + 'scripts/getresponse.php', method: "POST", data: formData
            }).success(function (respdata, status, headers, config) {
                $scope.isAuthorizedToSee = respdata['status'] == "failed" ? false : true;
                $scope.isAuthorizedToFollowup = respdata['authorized_to_followup'] ? true : false;
                $scope.filePreview = respdata['filePreview'] ? respdata['filePreview'] : '';
                $scope.responseObj = respdata;
            });


/* Success controller - for the success page */
}).controller('successController', function($scope, $routeParams, $cookieStore, epicoreVersion) {
        $scope.userInfo = $cookieStore.get('epiUserInfo');
        $scope.epicore_version = epicoreVersion;

        var messages = {};
        messages[1] = "You have been signed up.";
        messages[2] = 'The moderator who initiated the request has been notified. If you get any information on this RFI in the future, please come back to this RFI and click on "Yes, respond to this RFI"';
        messages[3] = "Your RFI has been sent to the selected members.";
        messages[4] = "Your RFI has been closed and an email has gone out to the original members contacted.";
        messages[5] = "Your RFI has been reopened and an email has gone out to the original members contacted.";
        messages[6] = "Your RFI has been updated.";
        messages[7] = "Your RFI has been deleted.";
        messages[8] = "Your RFI responses have been updated.";
        messages[9] = "RFI summary has been updated.";
        $scope.id = $routeParams.id;
        $scope.eid = $routeParams.eid;
        $scope.messageResponse = {};
        $scope.messageResponse.text = messages[$scope.id];

    }).controller('approvalController', function($scope, $http, $location, $route, $cookieStore, urlBase) {

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
        $scope.allapp = false;

        var data = {};
            $http({ url: urlBase + 'scripts/approval.php', method: "POST", data: data
            }).success(function (respdata, status, headers, config) {
                var inactive_applicants = [];
                for (var n in respdata){
                    respdata[n]['member_id'] = parseInt(respdata[n]['member_id']);  // use int so orberby works
                    if (respdata[n]['status'] == 'Pending'){
                        $scope.num_accepted++;
                    }
                    if (respdata[n]['status'] == 'Approved'){
                        $scope.num_approved++;
                    }
                    if (respdata[n]['status'] == 'Inactive'){
                        inactive_applicants.push(respdata[n]);
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
                $scope.allapp = false;
                $scope.inactive_applicants = inactive_applicants;
                $scope.applicants = inactive_applicants;
                $scope.all_applicants = respdata;
                $scope.inactive_applicants = inactive_applicants;
                $scope.num_applicants = $scope.applicants.length;
                $scope.showpage = true;
            });

        $scope.setLocationStatus = function (maillist_id, action) {
            data = {maillist_id: maillist_id, action:action};
            $http({ url: urlBase + 'scripts/setLocationStatus.php', method: "POST", data: data
            }).success(function (respdata, status, headers, config) {
                if (respdata['status'] == 'success'){
                    for (var n in $scope.applicants){
                        if ($scope.applicants[n].maillist_id == maillist_id){
                            $scope.applicants[n].locations = (action == 'enable') ? '1':'0';
                        }
                    }
                } else {
                    alert(respdata['message']);
                }
            });
        };

        $scope.selectMembers = function (status) {

            var r = confirm("Please wait a little while if you select OK");
            if (r == true) {
                if (status){
                    $scope.allapp = true;
                    $scope.applicants = $scope.all_applicants;
                } else {
                    $scope.allapp = false;
                    $scope.applicants = $scope.inactive_applicants;
                }
            }

        };

        $scope.approveApplicant = function(maillist_id, action){
            data = {maillist_id: maillist_id, action:action};
            $http({ url: urlBase  + 'scripts/setMemberStatus.php', method: "POST", data: data
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
            $http({ url: urlBase + 'scripts/downloadMembers.php', method: "POST"
            }).success(function (respdata, status, headers, config) {
                $scope.membersavailable = true;
                $scope.isRouteLoading = false;
            });
        };

        $scope.downloadEvents = function(){
            $scope.isRouteLoading = true;
            $http({ url: urlBase + 'scripts/downloadEventStats.php', method: "POST"
            }).success(function (respdata, status, headers, config) {
                $scope.eventsavailable = true;
                $scope.isRouteLoading = false;
            });
        };

        $scope.sendReminder = function(action){
            if (confirm('Are you sure you want to send reminder emails?')) {
                data = {action: action};
                $http({
                    url: urlBase + 'scripts/sendreminder.php', method: "POST", data: data
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
                    url: urlBase + 'scripts/deleteuser.php', method: "POST", data: data
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

    }).controller('testController', function($scope, $cookieStore, $http, $location, urlBase) {

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
                        url: urlBase + 'scripts/approveUser.php', method: "POST", data: data
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

    }).controller('certController', function($scope, $cookieStore, $http, urlBase) {

    //get member info
    $scope.userInfo = $cookieStore.get('epiUserInfo');
    var data = {};
    data['uid'] = $scope.userInfo.fetp_id;
    data['idtype'] = 'fetp';
    $http({ url: urlBase + 'scripts/getapplicant.php', method: "POST", data: data
    }).success(function (data, status, headers, config) {

        $scope.member_name = data.firstname + ' ' + data.lastname;
        $scope.approve_date = data.approve_date;
        var month = new Array("January", "February", "March",
            "April", "May", "June", "July", "August", "September",
            "October", "November", "December");
        var d = data.approve_date.split(" ");
        d = d[0].split("-");
        var dayof  = 'th Day of ';
        if (d[2] == 1 || d[2] == 21 || d[2] == 31){
            dayof = 'st Day of ';
        } else if (d[2] == 2 || d[2] == 22){
            dayof = 'nd Day of ';
        } else if (d[2] == 3 || d[2] == 23) {
            dayof = 'rd Day of ';
        }
        $scope.approve_date = Number(d[2]) + dayof + month[(Number(d[1]))-1] + ", " + d[0];
    });

    $scope.printPage = function (divName) {
        window.print();
    };


    }). controller('modaccessController', function($scope, $cookieStore, $http, urlBase) {

    var data = {};
    $scope.showpage = false;
    $http({ url: urlBase + 'scripts/approveaccess.php', method: "POST", data: data
    }).success(function (respdata, status, headers, config) {
        $scope.showpage = true;
    });

    // only allow superusers
    $scope.userInfo = $cookieStore.get('epiUserInfo');
    $scope.superuser = (typeof($scope.userInfo) != "undefined") ? $scope.userInfo.superuser: false;
    $scope.message = '';

    $scope.addMod = function(mod_email, mod_org_id){

        var mod_data = {mod_email: mod_email, mod_org_id:mod_org_id};
        console.log(mod_data);
        $http({ url: urlBase + 'scripts/addmod.php', method: "POST", data: mod_data
        }).success(function (respdata, status, headers, config) {
            if (respdata['status'] == 'success'){
                $scope.message = "Successfully added new moderator"

            } else {
                $scope.message = respdata['message'];
            }
        });
    };

    $scope.mods = '';
    $http({ url: urlBase + 'scripts/getmods.php', method: "POST"
    }).success(function (respdata, status, headers, config) {
        if (respdata['status'] == 'success'){
            $scope.mods = respdata['mods'];
        } else {

        }
    });

    }). controller('memberLocationsController', function($scope, $cookieStore, $http, urlBase) {


    $scope.userInfo = $cookieStore.get('epiUserInfo');
    $scope.locationaccess = (typeof($scope.userInfo.locations) != "undefined") ? $scope.userInfo.locations: false;
    $scope.fetp_id = (typeof($scope.userInfo.fetp_id) != "undefined") ? $scope.userInfo.fetp_id: false;
    $scope.showpage = true;
    $scope.message = '';
    $scope.error_message = '';

    $scope.addLocation = function(member){

        if (typeof(member.countrycode) == "undefined"){
            $scope.error_message = 'Please select a country.';
            return false;
        }

        var location = {city: member.city, state:member.state, countrycode:member.countrycode, fetp_id:$scope.fetp_id};
        $http({ url: urlBase + 'scripts/addlocation.php', method: "POST", data: location
        }).success(function (respdata, status, headers, config) {
            if (respdata['status'] == 'success'){
                $scope.message = "Successfully added new location";
                $scope.error_message = '';
                $scope.locations = getLocations($scope.fetp_id);

            } else {
                $scope.message = '';
                $scope.error_message = respdata['message'];
            }
        });
    };

    $scope.locations = getLocations($scope.fetp_id);

    function getLocations(fetp_id){
        var member = {fetp_id:fetp_id};
        $http({ url: urlBase + 'scripts/getlocations.php', method: "POST", data: member
        }).success(function (respdata, status, headers, config) {
            if (respdata['status'] == 'success'){
                $scope.locations = respdata['locations'];
            } else {
                $scope.message = '';
                $scope.error_message = respdata['message'];
            }
        });
    }
    $scope.deleteLocation = function (location_id){
        var location = {location_id:location_id};
        $http({ url: urlBase + 'scripts/deletelocation.php', method: "POST", data: location
        }).success(function (respdata, status, headers, config) {
            if (respdata['status'] == 'success'){
                $scope.message = respdata['message'];
                $scope.locations = getLocations($scope.fetp_id);
            } else {
                $scope.message = '';
                $scope.error_message = respdata['message'];
            }
        });
    };

    /* filter for trusted HTML */
    }).filter('to_trusted', ['$sce', function($sce){
        return function(text) {
            return $sce.trustAsHtml(text);
        };

    }]);
