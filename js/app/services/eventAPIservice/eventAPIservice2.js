const eventAPIservice2 = ($http, $rootScope, $location, urlBase) => {
  const eventAPI = {};
  eventAPI.getEvents = function(event_id, start_date, end_date) {
    let qs = event_id ? '&event_id=' + event_id : '';
    /* if(typeof($rootScope.userinfo['uid']) == "undefined") {
          qs += "&fetp_id="+$rootScope.userinfo['fetp_id'];
      } else {
          qs += "&uid="+$rootScope.userinfo['uid'];
      }*/

    if (
      typeof $rootScope.userinfo == 'undefined' ||
      $rootScope.dashboardType == 'PR'
    ) {
      // get events for public view
      qs += '&public=1';
    } else if (typeof $rootScope.userinfo['uid'] == 'undefined') {
      qs += '&fetp_id=' + $rootScope.userinfo['fetp_id'];
    } else {
      qs += '&uid=' + $rootScope.userinfo['uid'];
    }

    qs += '&start_date=' + start_date;
    qs += '&end_date=' + end_date;

    const requesturl = $location.path();
    const urlarr = requesturl.split('/');
    qs += '&from=' + urlarr[1]; // responses, followup, events
    if (typeof urlarr[2] != 'undefined') {
      qs += '&detail=' + urlarr[2]; // closed
    }
    // scripts/EventsAPI2.php?auth=true&callback=JSON_CALLBACK&uid=135&start_date=2017-10-30&end_date=2020-10-02&from=events2
    return $http({
      method: 'JSONP',
      url:
        urlBase +
        'scripts/EventsAPI2.php?auth=true&callback=JSON_CALLBACK' +
        qs,
    });
  };
  return eventAPI;
};

eventAPIservice2.$inject = ['$http', '$rootScope', '$location', 'urlBase'];

export default eventAPIservice2;
