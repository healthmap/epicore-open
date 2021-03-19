services.factory(
  "eventAPIservice",
  function ($http, $rootScope, $location, urlBase) {
    var eventAPI = {};
    eventAPI.getEvents = function (event_id) {
      var qs = event_id ? "&event_id=" + event_id : "";
      if (typeof $rootScope.userinfo["uid"] == "undefined") {
        qs += "&fetp_id=" + $rootScope.userinfo["fetp_id"];
      } else {
        qs += "&uid=" + $rootScope.userinfo["uid"];
      }
      var requesturl = $location.path();
      var urlarr = requesturl.split("/");
      qs += "&from=" + urlarr[1]; // responses, followup, events
      if (typeof urlarr[2] != "undefined") {
        qs += "&detail=" + urlarr[2]; // closed
      }
      return $http({
        method: "JSONP",
        url:
          urlBase +
          "scripts/EventsAPI.php?auth=true&callback=JSON_CALLBACK" +
          qs,
      });
    };
    return eventAPI;
  }
);
