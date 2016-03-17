var app = angular.module('EpicoreApp', [
    'EpicoreApp.services',
    'EpicoreApp.controllers',
    'ngCookies',
    'ngRoute',
    'ngSanitize',
    'uiGmapgoogle-maps'
]);

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
        when("/welcome", {templateUrl: "partials/welcome.html", controller: "fetpController"}).
        //when("/welcome", {templateUrl: "partials/welcome_new.html", controller: "fetpController"}).
        when("/setpassword", {templateUrl: "partials/setpassword.html"}).
        when("/resetpassword", {templateUrl: "partials/resetpassword.html"}).
        when("/home", {templateUrl: "partials/home.html"}).
        when("/training", {templateUrl: "partials/test.html", controller: "testController"}).
        otherwise({redirectTo: '/home'});
    });

/* google analytics */
app.run(['$rootScope', '$location', '$window', function($rootScope, $location, $window){
        $rootScope.$on('$routeChangeSuccess', function(event){
                $window.ga('send', 'pageview', { page: $location.path() });
            });
    }]);

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
        template: '<div style="height:400px;"><iframe style="overflow:hidden;height:100%;width:100%" width="100%" height="100%" src="{{url}}" frameborder="0" allowfullscreen></iframe></div>',
        link: function (scope) {
            scope.$watch('code', function (newVal) {
                if (newVal) {
                    scope.url = $sce.trustAsResourceUrl("https://www.youtube.com/embed/" + newVal);
                }
            });
        }
    };
});
