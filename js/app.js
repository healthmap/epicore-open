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
        when("/terms", {templateUrl: "partials/terms.html"}).
        when("/fetp", {templateUrl: "partials/fetp.html"}).
        when("/fetp/:eid", {templateUrl: "partials/fetp.html"}).
        when("/mod/:tid/:aid", {templateUrl: "partials/mod.html"}).
        when("/application", {templateUrl: "partials/application_new.html"}).
        when("/application_confirm", {templateUrl: "partials/application_confirm.html"}).
        when("/application/:id/:action", {templateUrl: "partials/application_new.html", controller: "userController"}).
        when("/approval", {templateUrl: "partials/approval.html", controller: "approvalController"}).
        when("/login", {templateUrl: "partials/login.html"}).
        when("/welcome", {templateUrl: "partials/welcome.html", controller: "fetpController"}).
        when("/setpassword", {templateUrl: "partials/setpassword.html"}).
        when("/resetpassword", {templateUrl: "partials/resetpassword.html"}).
        when("/home", {templateUrl: "partials/home.html"}).
        otherwise({redirectTo: '/home'});
    });

app.run(['$rootScope', '$location', '$window', function($rootScope, $location, $window){    //google analytics
        $rootScope.$on('$routeChangeSuccess', function(event){
                $window.ga('send', 'pageview', { page: $location.path() });
            });
    }]);

app.directive('siteHeader', function () {
    return {
        restrict: 'E',
        template: '<i class="fa fa-arrow-circle-left"></i> <button class="btn">{{back}} to RFI list</button>',
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
