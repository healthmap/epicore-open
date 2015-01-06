angular.module('EpicoreApp.controllers', []).

    /* User - includes Login & Logout */
    controller('userController', function($rootScope, $routeParams, $scope, $route, $cookies, $cookieStore, $location, $http) {
        /* get the active state of page you're on */
        $scope.getClass = function(path) {
            if(path == $location.path()) {
                return "active";
            } else {
                return "";
            }
        }

        /* set some global variables for Tephinet integration */
        $http({ url: 'scripts/getvars.php', method: "POST"
            }).success(function (data, status, headers, config) {
            $rootScope.tephinetBase = data['tephinet_base'];
        });

        /* log in */
        $scope.userLogin = function(formData) {
            // came in from fetp log in, no formdata passed, get ticket id and (optional) event_id from URL
            if(typeof(formData) == "undefined") {
                var formData = {};
                formData['ticket_id'] = $routeParams.tid ? $routeParams.tid : null;
                // if it's a mod, may be coming in with ticket and alert id to auto-fill a request
                formData['alert_id'] = $routeParams.aid ? $routeParams.aid : null;
                // if it's an fetp, may be coming in with ticket and event id for which they will respond
                formData['event_id'] = $routeParams.eid ? $routeParams.eid : null;
                formData['usertype'] = $location.path().indexOf("/fetp") == 0 ? 'fetp' : '';
            } 
            $http({ url: 'scripts/login.php', method: "POST", data: formData
            }).success(function (data, status, headers, config) {
                if(data['status'] == "success") {
                    // determines if user is an organization or FETP
                    $rootScope.isOrganization = data['uinfo']['organization_id'] > 0 ? true : false;
                    var isPromed = data['uinfo']['organization_id'] == 4 ? true : false;
                    $cookieStore.put('epiUserInfo', {'uid':data['uinfo']['user_id'], 'isPromed':isPromed, 'isOrganization':$rootScope.isOrganization, 'organization_id':data['uinfo']['organization_id'], 'organization':data['uinfo']['orgname'], 'fetp_id':data['uinfo']['fetp_id'], 'email':data['uinfo']['email'], 'uname':data['uinfo']['username']});
                    $rootScope.error_message = 'false';
                    $location.path('/'+data['path']);
                } else {
                    $rootScope.error_message = 'true';
                    $route.reload();
                }
            }).error(function (data, status, headers, config) {
                console.log(status);
            });
        }
        /* log out */
        $scope.userLogout = function() {
            $cookieStore.remove('epiUserInfo');
        }
        /* get user cookie info */
        $rootScope.userInfo = $cookieStore.get('epiUserInfo');
    }).

    controller('mapController', function($scope, $http) {
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
    }).

    /* Event(s) controller */
    controller('eventsController', function($scope, $routeParams, $cookieStore, $location, $http, eventAPIservice) {
        //$scope.nameFilter = null;
        $scope.eventsList = [];
        $scope.userInfo = $cookieStore.get('epiUserInfo');
        $scope.id = $routeParams.id ? $routeParams.id : null;
        $scope.responder_id = $routeParams.responder_id ? $routeParams.responder_id : null;
        $scope.allFETPs = $routeParams.responder_id ? false : true;
        // if we're on the closed requests page
        $scope.onOpen = $location.path().indexOf("/closed") > 0 ? false : true;
        $scope.anonymous_disabled = false;

        eventAPIservice.getEvents($scope.id).success(function (response) {
            $scope.isOrganization = $scope.userInfo.fetp_id > 0 ? false : true;
            // if the requester of event is the logged in user, or of the same organization, they get different action items
            if(response.EventsList != null) {
                $scope.requestOwner = $scope.userInfo.organization_id == response.EventsList.org_requester_id ? true : false;
                $scope.changeStatusText = response.EventsList.estatus == "C" ? 'Reopen' : 'Close';
                $scope.changeStatusType = response.EventsList.estatus == "C" ? 'reopen' : 'close';
            }
            $scope.eventsList = response.EventsList;
        });

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

        if(!$scope.formData) {
            $scope.formData = {};
        }

        $scope.sendResponse = function(formData) {
            // if user has chosen "I have nothing to contribute" button, 
            // formData comes in as object response_permissions: 0 
            formData['event_id'] = $routeParams.id
            formData['fetp_id'] = $scope.userInfo.fetp_id;
            $http({ url: 'scripts/respond.php', method: "POST", data: formData
            }).success(function (data, status, headers, config) {
                $location.path('/'+data['path']);
            });
        }

        $scope.sendFollowup = function(formData, isValid) {
            if(isValid) {
                // if responder id, send to that person only, otherwise to all in orig request
                formData['fetp_ids'] = $scope.responder_id;
                formData['event_id'] = $routeParams.id;
                formData['uid'] = $scope.userInfo.uid;
                formData['recipient'] = $scope.userInfo.email;
                formData['followup'] = 1;
                $http({ url: 'scripts/sendrequest.php', method: "POST", data: formData
                }).success(function (data, status, headers, config) {
                    $location.path('/success/3');
                });
            }
        }
    }).

    /* Request (RFI) */
    controller('requestController', function($rootScope, $scope, $routeParams, $cookieStore, $location, $http) {

        $rootScope.userInfo = $cookieStore.get('epiUserInfo');
        if(!$rootScope.formData) {
            $rootScope.formData = {};
        }
        if(!$rootScope.userIds) {
            $rootScope.userIds = [];
            $rootScope.userList = {};
            $rootScope.searchBox = [];
        }
        if(!$rootScope.eventId) {
            $rootScope.eventId = 0;
            $rootScope.formData.radiusValue = "250";
            $rootScope.emailText = '';
        }

        // if there's an alertid passed in, get the info to prepopulate the fields
        $scope.alertid = $routeParams.alertid;
        if($scope.alertid) {
            var alertData = {};
            alertData['alert_id'] = $scope.alertid;
            $http({ url: 'scripts/getalert.php', method: "POST", data: alertData
            }).success(function (data, status, headers, config) {
                $rootScope.formData = data;
            });
        }

        // step 1: store the event information
        $scope.storeEvent = function(formData, isValid) {
            if(isValid) {
                // jquery hack to get the hidden value of the latlon 
                formData['latlon'] = $("#default_location").val();
                if(!formData['latlon']) {
                    alert("Geolocation failed - please scroll down and select a location from the auto-suggester in the location field so that we have the coordinates of the event.");
                    $scope.formData.location = '';
                    return false;
                }
                // jquery hack to update the autocomplete value for location (angular bug)
                $rootScope.locname = formData['location'] = $("#searchTextField").val();
                formData['uid'] = $scope.userInfo.uid;
                formData['ddd_only'] = 1; // for now, hard-code this
                formData['event_id'] = $rootScope.eventId;
                $http({ url: 'scripts/request.php', method: "POST", data: formData 
                }).success(function (data, status, headers, config) {
                    $rootScope.userList = data['userList'];
                    $rootScope.userIds = data['userIds'];
                    $rootScope.eventId = data['eventId'];
                    $rootScope.emailText = data['emailText'];
                    $location.path('/'+data['path']);
                }).error(function (data, status, headers, config) {
                    console.log(status);
                });
            }
        };

        // step 2: calculate the number of users based on check & uncheck

        // show/hide the submit to next step only if there are FETPs to receive the email
        $scope.submitDisabled = $rootScope.userList.sending > 0 ? false : true;

        // only change the userList if on the second page
        if($location.path() == "/request2") {
            /* values for the radius slider */
            $scope.options = { from: 25, to: 1000, step: 25, dimension: " miles" };
            var filterData = {};
            $scope.$watch('formData.radiusValue', function(value) {
                filterData['latlon'] = $rootScope.formData.latlon;
                filterData['radius'] = value;
                filterData['ddd_only'] = 1; // for now, hard-code this
                filterData['filtertype'] = 'radius';
                $scope.radiussel = true; // if radius changes without changing radio button
                $http({ url: 'scripts/filter.php', method: "POST", data: filterData 
                }).success(function (filtereddata, status, headers, config) {
                    $rootScope.userList = filtereddata['userList'];
                    $rootScope.userIds = filtereddata['userIds'];
                    $rootScope.searchBox = filtereddata['bbox'];
                    $scope.submitDisabled = $rootScope.userList.sending > 0 ? false : true;
                });
            }); 
        }

        // initialize default radio buttons - radius select checked by default
        $scope.radiussel = true;
        /* check and uncheck training type filters */
        $scope.recalcUsers = function(filterData, whichclicked) {
            filterData['latlon'] = $rootScope.formData.latlon;
            filterData['filtertype'] = whichclicked;
            // select the right radio button if a country is selected without changing radio 
            $scope.radiussel = whichclicked == "country" ? false : true;
/*
            filterData['trainingchoices'] = new Array();
            // FIXME the trainingarr model vars are 1 behind so getting via jquery
            $('input[name="trainingarr"]').not(':checked').each(function() {
                filterData['trainingchoices'].push($(this).val());
            });
            filterData['ddd_only'] = $('input[name="filter1"]:checked').val() == "ddd" ? 1 : 0;
*/
            $http({ url: 'scripts/filter.php', method: "POST", data: filterData 
            }).success(function (filtereddata, status, headers, config) {
                $rootScope.userList = filtereddata['userList'];
                $rootScope.userIds = filtereddata['userIds'];
                $rootScope.searchBox = filtereddata['bbox'];
                $scope.submitDisabled = $rootScope.userList.sending > 0 ? false : true;
            });
        };

        // step 2: move on to step 3
        $scope.saveFETPs = function(filterData) {
            var saveFilterData = {};
            saveFilterData['event_id'] = $rootScope.eventId;
            // searchBox is set to nothing in filter.php if country is the filter
            if($rootScope.searchBox) {
                saveFilterData['search_box'] = $rootScope.searchBox;
                saveFilterData['search_radius'] = filterData['radiusValue'];
            } else {
                saveFilterData['search_countries'] = filterData['countries'];
            }
console.log(saveFilterData);
            $http({ url: 'scripts/saveFilter.php', method: "POST", data: saveFilterData
            }).success(function (returndata, status, headers, config) {
                $location.path('/request3');
            });
        }

        // step 3 : send the request
        $scope.sendRequest = function() {
            var formData = {};
            formData['fetp_ids'] = $rootScope.userIds;
            formData['event_id'] = $rootScope.eventId;
            formData['uid'] = $scope.userInfo.uid;
            formData['recipient'] = $scope.userInfo.email;
            $http({ url: 'scripts/sendrequest.php', method: "POST", data: formData 
            }).success(function (respdata, status, headers, config) {
                // empty out the form values since you've submitted so they aren't prefilled next time
                $rootScope.formData = {};
                $rootScope.eventId = 0;
                $rootScope.userList = {};
                $rootScope.userIds = [];
                $rootScope.emailText = '';
                $location.path('/success/3');
            });
        };
    }).

    controller('fetpController', function($scope, $routeParams, $cookieStore, $http) {
        $scope.userInfo = $cookieStore.get('epiUserInfo');
        $scope.formData = {};
        $scope.formData.response_id = $routeParams.id;
        $scope.formData['uid'] = $scope.userInfo.uid;
        $scope.formData['org_id'] = $scope.userInfo.organization_id;
        $scope.formData['fetp_id'] = $scope.userInfo.fetp_id;
        $http({ url: 'scripts/getresponse.php', method: "POST", data: $scope.formData 
        }).success(function (respdata, status, headers, config) {
            $scope.isAuthorizedToSee = respdata['status'] == "failed" ? false : true;
            $scope.isAuthorizedToFollowup = respdata['authorized_to_followup'] ? true : false;
            $scope.responseObj = respdata;
        });
    }).

    /* Success controller - for the success page */
    controller('successController', function($scope, $routeParams, $cookieStore) {
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
    });
