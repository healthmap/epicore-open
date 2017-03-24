var app = angular.module('EpicoreApp', [
    'EpicoreApp.services',
    'EpicoreApp.controllers',
    'ngCookies',
    'ngRoute',
    'ngSanitize',
    'uiGmapgoogle-maps',
    'angular-google-analytics',
    'ngCordova',
    'ngStorage'
]);

// app_mode settings to select web or mobile app
// mobile_prod - for mobile app with production backend
// mobile_dev - for mobile app with dev backend
// web - for web app (production and dev)
var app_mode = 'web';
var homeUrl = "partials/home.html";
if (app_mode == 'mobile_prod') {
    app.value('urlBase', 'https://epicore.org/'); // use full url for mobile api calls
    app.value('epicoreMode', 'mobile');
    homeUrl = "partials/home_mobile.html";
  }
else if ( app_mode == 'mobile_dev') { // use full url for mobile api calls
    app.value('urlBase', 'https://epicore.org/dev/');
    app.value('epicoreMode', 'mobile');
    homeUrl = "partials/home_mobile.html";
  }
else { // use relative url for web app
    app.value('urlBase', '');
    app.value('epicoreMode', 'web');
    homeUrl = "partials/home.html";
  }


app.config(function($routeProvider) {
  $routeProvider.
        when("/events", {templateUrl: "partials/events.html", controller: "eventsController"}).
        when("/map", {templateUrl: "partials/map.html", controller: "mapController"}).
        when("/events/closed", {templateUrl: "partials/events.html", controller: "eventsController"}).
        when("/events/:id", {templateUrl: "partials/event.html", controller: "eventsController"}).
        when("/reply/:id", {templateUrl: "partials/reply.html", controller: "eventsController"}).
        when("/close/:id", {templateUrl: "partials/close.html", controller: "eventsController"}).
        when("/reopen/:id", {templateUrl: "partials/reopen.html", controller: "eventsController"}).
        when("/followup/:id", {templateUrl: "partials/followup.html", controller: "eventsController"}).
        when("/followup/:id/:response_id", {templateUrl: "partials/followup.html", controller: "eventsController"}).
        when("/request", {templateUrl: "partials/request.html", controller: "requestController"}).
        when("/request/:alertid", {templateUrl: "partials/request.html", controller: "requestController"}).
        when("/request2", {templateUrl: "partials/request2.html", controller: "requestController"}).
        when("/request3", {templateUrl: "partials/request3.html", controller: "requestController"}).
        when("/request_edit/:id", {templateUrl: "partials/request_edit.html", controller: "editRequestController"}).
        when("/success/:id", {templateUrl: "partials/success.html", controller: "successController"}).
        when("/success/:id/:eid", {templateUrl: "partials/success.html", controller: "successController"}).
        when("/about", {templateUrl: "partials/about.html"}).
        when("/how", {templateUrl: "partials/howitworks.html"}).
        when("/who", {templateUrl: "partials/whocanapply.html"}).
        when("/educator", {templateUrl: "partials/lpeducator.html"}).
        when("/provider", {templateUrl: "partials/lpprovider.html"}).
        when("/researcher", {templateUrl: "partials/lpresearcher.html"}).
        when("/professional", {templateUrl: "partials/lpprofessional.html"}).
        when("/terms", {templateUrl: "partials/terms.html"}).
        when("/fetp", {templateUrl: "partials/fetp.html"}).
        when("/fetp/:eid", {templateUrl: "partials/fetp.html"}).
        when("/mod/:tid/:aid", {templateUrl: "partials/mod.html"}).
        when("/application", {templateUrl: "partials/application_new.html"}).
        when("/application_confirm", {templateUrl: "partials/application_confirm.html"}).
        when("/application/:id/:action/:idtype", {templateUrl: "partials/application_new.html", controller: "userController"}).
        when("/approval", {templateUrl: "partials/approval.html", controller: "approvalController"}).
        when("/login", {templateUrl: "partials/login.html"}).
        when("/setpassword", {templateUrl: "partials/setpassword.html"}).
        when("/resetpassword", {templateUrl: "partials/resetpassword.html"}).
        when("/home", {templateUrl: homeUrl}).
        when("/trainingvideos", {templateUrl: "partials/trainingvideos.html"}).
        when("/training", {templateUrl: "partials/test.html", controller: "testController"}).
        when("/certificate", {templateUrl: "partials/certificate.html", controller: "certController"}).
        when("/modaccess", {templateUrl: "partials/modaccess.html", controller: "modaccessController"}).
        when("/member_locations", {templateUrl: "partials/member_locations.html", controller: "memberLocationsController"}).
  otherwise({redirectTo: '/home'});
    });

/* google analytics */
app.run(['$rootScope', '$location', '$window', function($rootScope, $location, $window){
        $rootScope.$on('$routeChangeSuccess', function(event){
                $window.ga('send', 'pageview', { page: $location.path() });
            });
    }]);

/* push notifications listener */
if ((app_mode == 'mobile_dev') || (app_mode == 'mobile_prod')) {
    app.run(function ($http, $cordovaPushV5, $rootScope, $cordovaDevice, $localStorage) {

        document.addEventListener("deviceready", function () {

            var options = {
                android: {
                    senderID: "808458117906"
                },
                ios: {
                    alert: "true",
                    badge: "true",
                    sound: "true",
                    clearBadge: "true"  // clears badge on app startup
                },
                browser: {
                    pushServiceURL: 'http://push.api.phonegap.com/v1/push'
                },
                windows: {}
            };

            // initialize
            $cordovaPushV5.initialize(options).then(function () {
                // start listening for new notifications
                $cordovaPushV5.onNotification();
                // start listening for errors
                $cordovaPushV5.onError();

                // register to get registrationId
                $cordovaPushV5.register().then(function (registrationId) {
                    // save `registrationId` somewhere;
                    //alert("RegId: " +registrationId);
                    $localStorage.registrationId = registrationId;

                })
            });

            // triggered every time notification received
            $rootScope.$on('$cordovaPushV5:notificationReceived', function (event, data) {
                // data.message,
                // data.title,
                // data.count,
                // data.sound,
                // data.image,
                // data.additionalData

                $localStorage.pushMessage = data.message;
                //alert("Notification: " +data.message);

            });

            // triggered every time error occurs
            $rootScope.$on('$cordovaPushV5:errorOcurred', function (event, e) {
                // e.message
                //alert(e.message);
                $localStorage.pushError = e.message;
            });

            // get device info
            $localStorage.mobile_model = $cordovaDevice.getModel(); // eg. iPhone 6
            $localStorage.mobile_platform = $cordovaDevice.getPlatform(); // eg. iOS, Android
            $localStorage.mobile_os_version = $cordovaDevice.getVersion(); // eg iOS 10.2

        }, false);

    });
}
app.config(function (AnalyticsProvider) {
    // Add configuration code as desired - see below
    // Set a single account
    AnalyticsProvider.setAccount('UA-72336136-1');

    // Use ga.js (classic) instead of analytics.js (universal)
    // By default, universal analytics is used, unless this is called with a falsey value.
    AnalyticsProvider.useAnalytics(false);

    // track all routes/states (or not)
    //AnalyticsProvider.trackPages(true);
});

//app.run(function(Analytics){});

/* back button directive used on Event.html*/
app.directive('siteHeader', function () {
    return {
        restrict: 'E',
        template: '<button class="btn btn-default"><i class="fa fa-arrow-circle-left"></i> {{back}} to Your EpiCore Dashboard</button>',
        scope: {
            back: '@back',
            icons: '@icons'
        },
        link: function(scope, element, attrs) {
            $(element[0]).on('click', function() {
                history.back();
                scope.$apply();
            });
        }
    };
});

/* youtube directive */
app.directive('myYoutube', function($sce) {
    return {
        restrict: 'EA',
        scope: { code:'=' },
        replace: true,
        template: '<div style="height:550px; width: 980px;"><iframe style="overflow:hidden;height:100%;width:100%" width="100%" height="100%" src="{{url}}" frameborder="0" allowfullscreen></iframe></div>',
        link: function (scope) {
            scope.$watch('code', function (newVal) {
                if (newVal) {
                    scope.url = $sce.trustAsResourceUrl("https://www.youtube.com/embed/" + newVal + "?vq=hd720");
                }
            });
        }
    };
});

/* chosen directive */
app.directive('chosen', function($timeout) {

    var linker = function(scope, element, attr) {

        $timeout(function () {
            element.chosen();
        }, 0, false);
    };

    return {
        restrict: 'A',
        link: linker
    };
});
