app.config(function ($routeProvider) {
    
  $routeProvider.
      when("/events", { templateUrl: "templates/events/events.html?cb=" + cacheBustSuffix, controller: "eventsController" }).
      when("/events2", { templateUrl: "templates/events/events2.html?cb=" + cacheBustSuffix, controller: "eventsController2" }).
      when("/events_public", { templateUrl: "templates/events/events_public.html?cb=" + cacheBustSuffix, controller: "eventsController2" }).
      when("/events_public/articles/:articleID", { templateUrl: "templates/events/publicEventsRFI.html?cb=" + cacheBustSuffix, controller: "eventsController2" }).
      when("/events/closed", { templateUrl: "templates/events/events.html?cb=" + cacheBustSuffix, controller: "eventsController" }).
      when("/events2/closed", { templateUrl: "templates/events/events2.html?cb=" + cacheBustSuffix, controller: "eventsController2" }).
      when("/events/:id", { templateUrl: "templates/events/event.html?cb=" + cacheBustSuffix, controller: "eventsController" }).
      when("/events2/:id", { templateUrl: "templates/events/event2.html?cb=" + cacheBustSuffix, controller: "eventsController2" }).
      when("/reply/:id", { templateUrl: "templates/events/reply.html?cb=" + cacheBustSuffix, controller: "eventsController" }).
      when("/reply2/:id", { templateUrl: "templates/events/reply2.html?cb=" + cacheBustSuffix, controller: "eventsController2" }).
      when("/close/:id", { templateUrl: "templates/events/close.html?cb=" + cacheBustSuffix, controller: "eventsController" }).
      when("/close2/:id", { templateUrl: "templates/events/close2.html?cb=" + cacheBustSuffix, controller: "eventsController2" }).
      when("/reopen/:id", { templateUrl: "templates/events/reopen.html?cb=" + cacheBustSuffix, controller: "eventsController" }).
      when("/reopen2/:id", { templateUrl: "templates/events/reopen2.html?cb=" + cacheBustSuffix, controller: "eventsController2" }).
      when("/summary/:id", { templateUrl: "templates/events/summary.html?cb=" + cacheBustSuffix, controller: "eventsController2" }).
      when("/followup/:id", { templateUrl: "templates/events/followup.html?cb=" + cacheBustSuffix, controller: "eventsController" }).
      when("/followup2/:id", { templateUrl: "templates/events/followup2.html?cb=" + cacheBustSuffix, controller: "eventsController2" }).
      when("/followup/:id/:response_id", { templateUrl: "templates/events/followup.html?cb=" + cacheBustSuffix, controller: "eventsController" }).
      when("/followup2/:id/:response_id", { templateUrl: "templates/events/followup2.html?cb=" + cacheBustSuffix, controller: "eventsController2" }).
      when("/rfi_dashboard", { templateUrl: "templates/events/events_metrics.html?cb=" + cacheBustSuffix, controller: "eventsController2" }).
      when("/rfi_dashboard/closed", { templateUrl: "templates/events/events_metrics.html?cb=" + cacheBustSuffix, controller: "eventsController2" }).
      
      when("/events_public/articles/:articleID", { templateUrl: "templates/events/publicrfi.html?cb=" + cacheBustSuffix, controller: "publicRFIController" }).

      when("/map", { templateUrl: "templates/map/map.html?cb=" + cacheBustSuffix, controller: "mapController" }).
      
      when("/rfi_step3", { templateUrl: "templates/request/rfi_step3.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/rfi_step2", { templateUrl: "templates/request/rfi_step2.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/rfi_step1", { templateUrl: "templates/request/rfi_step1.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/condition", { templateUrl: "templates/request/rfi_condition.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/duplicate", { templateUrl: "templates/request/rfi_duplicate2.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/population", { templateUrl: "templates/request/rfi_population.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/location", { templateUrl: "templates/request/rfi_location.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/location/:id", { templateUrl: "templates/request/rfi_location.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/rfi_step1/:id", { templateUrl: "templates/request/rfi_step1.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/time", { templateUrl: "templates/request/rfi_time.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/purpose", { templateUrl: "templates/request/rfi_purpose.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/source", { templateUrl: "templates/request/rfi_source.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/members", { templateUrl: "templates/request/rfi_members.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/sendrequest", { templateUrl: "templates/request/rfi_sendrequest.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/sent", { templateUrl: "templates/request/rfi_sent.html?cb=" + cacheBustSuffix, controller: "requestController2" }).
      when("/request", { templateUrl: "templates/request/request.html?cb=" + cacheBustSuffix, controller: "requestController" }).
      when("/request/:alertid", { templateUrl: "templates/request/request.html?cb=" + cacheBustSuffix, controller: "requestController" }).
      when("/request2", { templateUrl: "templates/request/request2.html?cb=" + cacheBustSuffix, controller: "requestController" }).
      when("/request3", { templateUrl: "templates/request/request3.html?cb=" + cacheBustSuffix, controller: "requestController" }).
      
      when("/request_edit/:id", { templateUrl: "templates/request_edit/request_edit.html?cb=" + cacheBustSuffix, controller: "editRequestController" }).
      
      when("/success/:id", { templateUrl: "templates/success/success.html?cb=" + cacheBustSuffix, controller: "successController" }).
      when("/success/:id/:eid", { templateUrl: "templates/success/success.html?cb=" + cacheBustSuffix, controller: "successController" }).
      
      when("/about", { templateUrl: "templates/about/about.html?cb=" + cacheBustSuffix, }).
      when("/how", { templateUrl: "templates/how/howitworks.html?cb=" + cacheBustSuffix }).
      when("/who", { templateUrl: "templates/who/whocanapply.html?cb=" + cacheBustSuffix }).
      
      when("/news", { templateUrl: "templates/news/newsletter.html?cb=" + cacheBustSuffix, controller: "newsController"}).
      when("/educator", { templateUrl: "templates/educator/lpeducator.html?cb=" + cacheBustSuffix }).
      when("/provider", { templateUrl: "templates/provider/lpprovider.html?cb=" + cacheBustSuffix }).
      when("/researcher", { templateUrl: "templates/researcher/lpresearcher.html?cb=" + cacheBustSuffix }).
      when("/professional", { templateUrl: "templates/professional/lpprofessional.html?cb=" + cacheBustSuffix }).
      when("/terms", { templateUrl: "templates/terms/terms.html?cb=" + cacheBustSuffix }).
      when("/fetp", { templateUrl: "templates/fetp/fetp.html?cb=" + cacheBustSuffix }).
      when("/fetp/:eid", { templateUrl: "templates/fetp/fetp.html?cb=" + cacheBustSuffix }).
      when("/mod/:tid/:aid", { templateUrl: "templates/mod/mod.html?cb=" + cacheBustSuffix }).
      
      when("/application", { templateUrl: "templates/application/application_new.html?cb=" + cacheBustSuffix }).
      when("/application_confirm", { templateUrl: "templates/application/application_confirm.html?cb=" + cacheBustSuffix }).
      when("/application/:id/:action/:idtype", { templateUrl: "templates/application/application_new.html?cb=" + cacheBustSuffix, controller: "userController" }).
      
      when("/trainingvideos", { templateUrl: "templates/trainingvideos/trainingvideos.html?cb=" + cacheBustSuffix, controller: "userController" }).
      when("/resources", { templateUrl: "templates/resources/resources.html?cb=" + cacheBustSuffix, controller: "userController" }).

      when("/approval", { templateUrl: "templates/approval/approval.html?cb=" + cacheBustSuffix, controller: "approvalController" }).
      when("/approval/accepted", { templateUrl: "templates/approval/approval.html?cb=" + cacheBustSuffix,  }).
      when("/approval/pre_approved", { templateUrl: "templates/approval/approval.html?cb=" + cacheBustSuffix,  }).
      when("/approval/members", { templateUrl: "templates/approval/approval.html?cb=" + cacheBustSuffix, }).
      when("/approval/denied", { templateUrl: "templates/approval/approval.html?cb=" + cacheBustSuffix, }).

      when("/login", { templateUrl: "templates/login/login.html?cb=" + cacheBustSuffix }).
      when("/login_mobile", { templateUrl: "templates/login/login_mobile.html?cb=" + cacheBustSuffix }).
      when("/setpassword", { templateUrl: "templates/setpassword/setpassword.html?cb=" + cacheBustSuffix }).
      when("/resetpassword", { templateUrl: "templates/resetpassword/resetpassword.html?cb=" + cacheBustSuffix }).
      when("/home", { templateUrl: home_page + cacheBustSuffix }).

      when("/training", { templateUrl: "templates/training/test.html?cb=" + cacheBustSuffix, controller: "testController" }).
      when("/certificate", { templateUrl: "templates/certificate/certificate.html?cb=" + cacheBustSuffix, controller: "certController" }).
      when("/modaccess", { templateUrl: "templates/modaccess/modaccess.html?cb=" + cacheBustSuffix, controller: "modaccessController" }).
      when("/requesters_dashboard", { templateUrl: "templates/modaccess/requesters_dashboard.html?cb=" + cacheBustSuffix, controller: "modaccessController" }).
      when("/member_locations", { templateUrl: "templates/member_locations/member_locations.html?cb=" + cacheBustSuffix, controller: "memberLocationsController" }).
      when("/metrics", { templateUrl: "templates/responders_metrics.html?cb=" + cacheBustSuffix, controller: "metricsController" }).

      otherwise({ redirectTo: '/home' });
});