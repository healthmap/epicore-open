angular.module('EpicoreApp.services', [])
   .run(['$rootScope', '$location', 'authService', function ($rootScope, $location, authService) {
        $rootScope.$on("$routeChangeStart", function (event, next, current) {
            var requesturl = $location.path();
            var urlarr = requesturl.split("/");
            var nonauthpages = new Array('fetp', 'about', 'terms', 'mod');
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
            var redirloc = urlarr[1] == "fetp" && typeof(urlarr[3]) != "undefined" ? '/events/'+urlarr[3] : '/events';
            if(authService.isAuthenticated() && ($location.path() == "/home" || urlarr[1] == "fetp")) $location.path(redirloc);
        });
    }])
    .factory('authService', function($rootScope, $cookies, $cookieStore, $http){
        return {
            isAuthenticated: function(user) {
                $rootScope.userinfo = $cookieStore.get('epiUserInfo');
                return $rootScope.userinfo ? 1 : 0;
            }
        }
    })
    .factory('eventAPIservice', function($http, $rootScope, $location) {
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
                url: 'scripts/EventsAPI.php?auth=true&callback=JSON_CALLBACK'+qs
            });
        }
        return eventAPI;
    });
