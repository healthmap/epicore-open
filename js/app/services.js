angular.module('EpicoreApp.services', [])
   .run(['$rootScope', '$location', 'authService', 'epicoreVersion', function ($rootScope, $location, authService, epicoreVersion) {
        $rootScope.$on("$routeChangeStart", function (event, next, current) {
            var requesturl = $location.path();
            var urlarr = requesturl.split("/");
            var nonauthpages = new Array('fetp', 'about', 'terms', 'mod', 'application', 'application_confirm', 'login',
                'setpassword', 'resetpassword','who','how','educator','provider','professional','researcher', 'certificate', 'events_public','login_mobile');

            // if user is not authenticated, make them go to homepage if on an auth-only page
            if(!authService.isAuthenticated() && nonauthpages.indexOf(urlarr[1]) == -1) {
                if(urlarr[1] == "home") {
                    $location.path('/home');
                } else {
                    // add a query string so after login, user goes straight to where they wanted to go
                    $location.path('/home').search({redir: requesturl});
                }
            }

            // if user is authenticated and on homepage or fetp login page, go to events listing, or redirect location
            var redirloc = urlarr[1] == "fetp" && typeof(urlarr[3]) != "undefined" ? '/events/' + urlarr[3] : '/events';
            if (epicoreVersion == '2') {
                redirloc = urlarr[1] == "fetp" && typeof(urlarr[3]) != "undefined" ? '/events2/' + urlarr[3] : '/events2';
            }

            // redirloc = ($rootScope.userinfo['fetp_id'] && ($rootScope.userinfo['active'] == 'N')) ? "home" : redirloc; // go to home page if not active fetp
            if($rootScope.userinfo != undefined) {
                redirloc = ($rootScope.userinfo['fetp_id'] && ($rootScope.userinfo['active'] == 'N')) ? "home" : redirloc; // go to home page if not active fetp                
            }
            // console.log('redirloc-2:' + redirloc);
            
            if(authService.isAuthenticated() && ($location.path() == "/home" || urlarr[1] == "fetp")) $location.path(redirloc);
        });
    }])
    .factory('authService', function($rootScope, $cookies, $cookieStore, $http){
        return {
            isAuthenticated: function(user) {
                $rootScope.userinfo = $cookieStore.get('epiUserInfo');
                return $rootScope.userinfo ? 1 : 0;
                //return ($rootScope.userinfo['active'] == 'Y') ? 1: 0;
            }
        }
    })
    .factory('eventAPIservice', function($http, $rootScope, $location, urlBase) {
        var eventAPI = {};
        eventAPI.getEvents = function(event_id) {

            var qs = event_id ? '&event_id='+event_id : '';
            if(typeof($rootScope.userinfo['uid']) == "undefined") {
                qs += "&fetp_id="+$rootScope.userinfo['fetp_id'];
            } else {
                qs += "&uid="+$rootScope.userinfo['uid'];
            }
            var requesturl = $location.path();
            var urlarr = requesturl.split("/");
            qs += "&from="+urlarr[1]; // responses, followup, events
            if(typeof(urlarr[2]) != "undefined") {
                qs += "&detail="+urlarr[2]; // closed
            }
            return $http({
                method: 'JSONP',
                url: urlBase + 'scripts/EventsAPI.php?auth=true&callback=JSON_CALLBACK'+qs
            });
        }
        return eventAPI;
    })
    .factory('eventAPIservice2', function($http, $rootScope, $location, urlBase) {
        var eventAPI = {};
        eventAPI.getEvents = function(event_id, start_date, end_date) {
            var qs = event_id ? '&event_id='+event_id : '';
            /*if(typeof($rootScope.userinfo['uid']) == "undefined") {
                qs += "&fetp_id="+$rootScope.userinfo['fetp_id'];
            } else {
                qs += "&uid="+$rootScope.userinfo['uid'];
            }*/
            
            if((typeof($rootScope.userinfo) == "undefined") || $rootScope.dashboardType == 'PR') {
               // get events for public view
                qs +="&public=1"
            } else if(typeof($rootScope.userinfo['uid']) == "undefined") {
                qs += "&fetp_id="+$rootScope.userinfo['fetp_id'];
            } else {
                qs += "&uid="+$rootScope.userinfo['uid'];
            }

            qs += "&start_date="+start_date;
            qs += "&end_date="+end_date;

            var requesturl = $location.path();
            var urlarr = requesturl.split("/");
            qs += "&from="+urlarr[1]; // responses, followup, events
            if(typeof(urlarr[2]) != "undefined") {
                qs += "&detail="+urlarr[2]; // closed
            }
            // console.log("qs is:", qs);
            //scripts/EventsAPI2.php?auth=true&callback=JSON_CALLBACK&uid=135&start_date=2017-10-30&end_date=2020-10-02&from=events2
            return $http({
                method: 'JSONP',
                url: urlBase + 'scripts/EventsAPI2.php?auth=true&callback=JSON_CALLBACK'+qs
            });
            
        }
        return eventAPI;
    })
    .factory("rfiForm", function () { // questions form variables object (persistance)
        var questions = {};
        return{
            clear: function () {
                for (var member in questions) delete questions[member];
            },
            get: function () {
                return questions;
            }
        };

    });
