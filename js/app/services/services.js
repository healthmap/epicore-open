var services = angular.module("EpicoreApp.services", []);

services.run([
  "$rootScope",
  "$location",
  "authService",
  "epicoreVersion",
  function ($rootScope, $location, authService, epicoreVersion) {
    $rootScope.$on("$routeChangeStart", function (event, next, current) {
      var requesturl = $location.path();
      var urlarr = requesturl.split("/");
      var nonauthpages = new Array(
        "fetp",
        "about",
        "terms",
        "mod",
        "application",
        "news",
        "application_confirm",
        "login",
        "setpassword",
        "resetpassword",
        "who",
        "how",
        "educator",
        "provider",
        "professional",
        "researcher",
        "certificate",
        "events_public",
        "login_mobile"
      );

      // if user is not authenticated, make them go to homepage if on an auth-only page
      if (
        !authService.isAuthenticated() &&
        nonauthpages.indexOf(urlarr[1]) == -1
      ) {
        if (urlarr[1] == "home") {
          $location.path("/home");
        } else {
          // add a query string so after login, user goes straight to where they wanted to go
          $location.path("/home").search({ redir: requesturl });
        }
      }

      // if user is authenticated and on homepage or fetp login page, go to events listing, or redirect location
      var redirloc =
        urlarr[1] == "fetp" && typeof urlarr[3] != "undefined"
          ? "/events/" + urlarr[3]
          : "/events";
      if (epicoreVersion == "2") {
        redirloc =
          urlarr[1] == "fetp" && typeof urlarr[3] != "undefined"
            ? "/events2/" + urlarr[3]
            : "/events2";
      }

      // redirloc = ($rootScope.userinfo['fetp_id'] && ($rootScope.userinfo['active'] == 'N')) ? "home" : redirloc; // go to home page if not active fetp
      if ($rootScope.userinfo != undefined) {
        redirloc =
          $rootScope.userinfo["fetp_id"] && $rootScope.userinfo["active"] == "N"
            ? "home"
            : redirloc; // go to home page if not active fetp
      }
      // console.log('redirloc-2:' + redirloc);

      if (
        authService.isAuthenticated() &&
        ($location.path() == "/home" || urlarr[1] == "fetp")
      )
        $location.path(redirloc);
    });
  },
]);
