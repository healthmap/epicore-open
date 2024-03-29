const MemberLocationsController = (
  $scope,
  $cookieStore,
  httpServiceInterceptor,
  urlBase,
  $timeout,
) => {
  const http = httpServiceInterceptor.http;
  $scope.userInfo = $cookieStore.get('epiUserInfo');
  $scope.locationaccess =
    typeof $scope.userInfo.locations != 'undefined' ?
      $scope.userInfo.locations :
      false;
  $scope.showpage = true;
  $scope.message = '';
  $scope.error_message = '';
  $scope.locationOptions = {
    types: ['(regions)'],
  };
  $scope.memLocationPlace = {
    address_components: [],
    formatted_address: '',
    geometry: [],
  };
  $scope.member = {
    city: '',
    state: '',
    countrycode: '',
    lat: '',
    long: '',
  };

  $scope.memLocationChange = function(memLocation) {
    const administrative_areas = [];
    memLocation.address_components.forEach(function(item) {
      if (item.types.indexOf('country') !== -1) {
        $scope.member.countrycode = item.short_name;
      }

      item.types.filter(function(type) {
        if (type.indexOf('administrative_area') !== -1) {
          administrative_areas.push(item.short_name);
        }
      });

      if (item.types.indexOf('locality') !== -1) {
        $scope.member.city = item.short_name;
      }
    });

    $scope.member.state = administrative_areas.toString().replace(/,/g, ', ');

    if (memLocation.geometry && memLocation.geometry.location) {
      $scope.member.long = memLocation.geometry.location.lng();
      $scope.member.lat = memLocation.geometry.location.lat();
    }
  };

  $scope.addLocation = function(memLocation) {
    if (typeof $scope.member.countrycode == 'undefined') {
      $scope.error_message = 'Please select a country.';
      return false;
    } else if (typeof $scope.member.city == 'undefined') {
      $scope.error_message = 'Please select a city.';
      return false;
    } else if (typeof $scope.member.state == 'undefined') {
      $scope.error_message = 'Please select a state.';
      return false;
    }

    const location = {
      city: $scope.member.city,
      state: $scope.member.state,
      countrycode: $scope.member.countrycode,
      latitude: $scope.member.lat,
      longitude: $scope.member.long,
    };

    http({
      url: urlBase + 'scripts/addlocation.php',
      method: 'POST',
      data: location,
    }).then(function successCallback(res) {
      const respdata = res.data;
      const message_debounce = 2000;
      if (respdata['status'] === 'success') {
        $scope.message = 'Successfully added new location';
        $scope.error_message = '';
        $timeout(function() {
          $scope.message = '';
        }, message_debounce);
        $scope.locations = getLocations();
      } else {
        $scope.message = '';
        $scope.error_message = respdata['message'];
        $timeout(function() {
          $scope.error_message = '';
        }, message_debounce);
      }
    });
  };

  $scope.locations = getLocations();

  function getLocations() {
    http({
      url: urlBase + 'scripts/getlocations.php',
      method: 'GET'
    }).then(function successCallback(res) {
      const respdata = res.data;
      if (respdata['status'] === 'success') {
        $scope.locations = respdata['locations'];
      } else {
        $scope.message = '';
        $scope.error_message = respdata['message'];
        const message_debounce = 2000;
        $timeout(function() {
          $scope.error_message = '';
        }, message_debounce);
      }
    });
  }
  $scope.deleteLocation = function(location_id) {
    const location = {location_id: location_id};
    http({
      url: urlBase + 'scripts/deletelocation.php',
      method: 'POST',
      data: location,
    }).then(function successCallback(res) {
      const respdata = res.data;
      const message_debounce = 2000;
      if (respdata['status'] === 'success') {
        $scope.message = respdata['message'];
        $scope.locations = getLocations();
        $timeout(function() {
          $scope.message = '';
        }, message_debounce);
      } else {
        $scope.message = '';
        $scope.error_message = respdata['message'];
        $timeout(function() {
          $scope.error_message = '';
        }, message_debounce);
      }
    });
  };
};

MemberLocationsController.$inject = [
  '$scope',
  '$cookieStore',
  'httpServiceInterceptor',
  'urlBase',
  '$timeout',
];

export default MemberLocationsController;
