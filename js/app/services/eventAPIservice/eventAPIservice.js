const EventAPIservice = ($http, $rootScope, $location, urlBase) => {
  const eventAPI = {};
  eventAPI.getEvents = function(event_id) {
    let qs = event_id ? '&event_id=' + event_id : '';
    if (typeof $rootScope.userinfo['uid'] == 'undefined') {
      qs += '&fetp_id=' + $rootScope.userinfo['fetp_id'];
    } else {
      qs += '&uid=' + $rootScope.userinfo['uid'];
    }
    const requesturl = $location.path();
    const urlarr = requesturl.split('/');
    qs += '&from=' + urlarr[1]; // responses, followup, events
    if (typeof urlarr[2] != 'undefined') {
      qs += '&detail=' + urlarr[2]; // closed
    }
    return $http({
      method: 'JSONP',
      url:
        urlBase + 'scripts/EventsAPI.php?auth=true&callback=JSON_CALLBACK' + qs,
    });
  };
  return eventAPI;
};

EventAPIservice.$inject = ['$http', '$rootScope', '$location', 'urlBase'];

export default EventAPIservice;
