angular.module('EpicoreApp', [
    'EpicoreApp.services',
    'EpicoreApp.controllers',
    'ngCookies',
    'ngRoute',
//    'ngSlider',
    'uiGmapgoogle-maps'
]).  
config(function($routeProvider) {
  $routeProvider.
        when("/events", {templateUrl: "partials/events.html", controller: "eventsController"}).
        when("/map", {templateUrl: "partials/map.html", controller: "mapController"}).
        when("/events/closed", {templateUrl: "partials/events.html", controller: "eventsController"}).
        when("/events/:id", {templateUrl: "partials/event.html", controller: "eventsController"}).
        when("/reply/:id", {templateUrl: "partials/reply.html", controller: "eventsController"}).
        when("/close/:id", {templateUrl: "partials/close.html", controller: "eventsController"}).
        when("/reopen/:id", {templateUrl: "partials/reopen.html", controller: "eventsController"}).
        when("/followup/:id", {templateUrl: "partials/followup.html", controller: "eventsController"}).
        when("/followup/:id/:responder_id", {templateUrl: "partials/followup.html", controller: "eventsController"}).
        when("/request", {templateUrl: "partials/request.html"}).
        when("/request/:alertid", {templateUrl: "partials/request.html"}).
        when("/request2", {templateUrl: "partials/request2.html"}).
        when("/request3", {templateUrl: "partials/request3.html"}).
        when("/response/:id", {templateUrl: "partials/response.html", controller: "fetpController"}).
        when("/responses/:id", {templateUrl: "partials/responses.html", controller: "eventsController"}).
        when("/success/:id", {templateUrl: "partials/success.html", controller: "successController"}).
        when("/about", {templateUrl: "partials/about.html"}).
        when("/terms", {templateUrl: "partials/terms.html"}).
        when("/fetp/:tid", {templateUrl: "partials/fetp.html"}).
        when("/fetp/:tid/:eid", {templateUrl: "partials/fetp.html"}).
        when("/mod/:tid/:aid", {templateUrl: "partials/mod.html"}).
        when("/home", {templateUrl: "partials/home.html"}).
        otherwise({redirectTo: '/home'});
    });
