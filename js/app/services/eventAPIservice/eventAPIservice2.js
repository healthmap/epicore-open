const eventAPIservice2 = ($rootScope, $location, urlBase, httpServiceInterceptor) => {
  const eventAPI = {};
  const http = httpServiceInterceptor.http;
  eventAPI.getEvents = async (event_id, start_date, end_date) => {
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
    }

    qs += '&start_date=' + start_date;
    qs += '&end_date=' + end_date;

    const requesturl = $location.path();
    const urlarr = requesturl.split('/');
    qs += '&from=' + urlarr[1]; // responses, followup, events
    if (typeof urlarr[2] != 'undefined') {
      qs += '&detail=' + urlarr[2]; // closed
    }

    const url = urlBase + 'scripts/EventsAPI2.php?auth=true' + qs;
    
    return http({
      url: url,
      method: 'GET'
    });
  };

  return eventAPI;
};

eventAPIservice2.$inject = ['$rootScope', '$location', 'urlBase', 'httpServiceInterceptor'];

export default eventAPIservice2;
