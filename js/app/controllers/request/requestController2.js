const RequestController2 = (
  $rootScope,
  $window,
  $scope,
  $routeParams,
  $cookieStore,
  $location,
  httpServiceInterceptor,
  urlBase,
  rfiForm,
  epicoreVersion,
) => {
  const http = httpServiceInterceptor.http;

  $scope.userInfo = $rootScope.userInfo = $cookieStore.get('epiUserInfo');

  $scope.epicore_version = epicoreVersion;

  // get persistant RFI form
  $scope.rfiData = rfiForm.get();

  // get event from database if event id is passed in and populate form
  // this is used to edit an RFI (not send a new RFI)
  if ($routeParams.id) {
    // save event id
    $scope.rfiData.event_id = $routeParams.id;

    // get event from database
    const eventData = {};
    eventData['event_id'] = $scope.rfiData.event_id;

    http({
      url: urlBase + 'scripts/getrequest2.php',
      method: 'POST',
      data: eventData,
    }).then(function successCallback(res) {
      const data = res.data;
      $scope.rfiData.location = {};
      $scope.rfiData.health_condition = {};
      $scope.rfiData.population = {};
      $scope.rfiData.source = {};

      $scope.rfiData.location.location = data.event.location;
      $scope.rfiData.location.event_date = data.event.event_date;
      $scope.rfiData.location.event_date_details =
        data.event.event_date_details;
      $scope.rfiData.location.latlon = data.event.latlon;
      $scope.rfiData.location.location_details = data.event.location_details;
      $scope.rfiData.additionalText = data.event.personalized_text;

      $scope.rfiData.population = data.population;
      $scope.rfiData.purpose = data.purpose;
      $scope.rfiData.health_condition = data.health_condition;
      $scope.rfiData.source = data.source;

      $scope.rfiData.place = data.event.location;

      $scope.isRequester = data.event.requester_id == $scope.userInfo.uid;
    });
  }

  // ///////////////////////////////////////////// Location ////////////////////////////////

  // autocomplete options for google api places
  $scope.autocompleteOptions = {
    types: ['(regions)'],
  };

  const getPlaceLatLon = function (place) {
    return place.geometry.location.lat() + ',' + place.geometry.location.lng();
  };

  $scope.location_error_message = '';
  $scope.saveLocation = function (direction) {
    // jquery hack to get the latlon hidden value and autocomplete for location (angular bug)
    // $scope.rfiData.location.latlon = $("#default_location").val();
    // $scope.rfiData.location.location = $("#searchTextField").val(); // format: "country" or "state, country" or "city, state, country"
    // only use google places for new events
    // if (!$scope.rfiData.event_id) {
    if (
      $('#autocompleteText').val() != undefined &&
      $('#autocompleteText').val() != ''
    ) {
      $scope.rfiData.location.latlon = getPlaceLatLon($scope.rfiData.place);
      $scope.rfiData.location.location = $('#autocompleteText').val();

      if (!$scope.rfiData.location.latlon) {
        $scope.rfiData.location.location_error_message =
          'Geolocation failed - please scroll down and select a location from the auto-suggester in the location field so that we have the coordinates of the event.';
        $scope.rfiData.location.location = '';
        return false;
      }
    }

    // get city, state, country from location string
    if ($scope.rfiData.location.location) {
      const mylocation = $scope.rfiData.location.location.split(',');
      if (mylocation.length == 4) {
        $scope.rfiData.default_city = mylocation[1];
        $scope.rfiData.default_state = mylocation[2];
        $scope.rfiData.default_country = mylocation[3];
        $scope.rfiData.location.location =
          mylocation[1] + ',' + mylocation[2] + ',' + mylocation[3]; // set location to only city,state,country
      } else if (mylocation.length == 3) {
        $scope.rfiData.default_city = mylocation[0];
        $scope.rfiData.default_state = mylocation[1];
        $scope.rfiData.default_country = mylocation[2];
      } else if (mylocation.length == 2) {
        $scope.rfiData.default_city = '';
        $scope.rfiData.default_state = mylocation[0];
        $scope.rfiData.default_country = mylocation[1];
      } else if (mylocation.length == 1) {
        $scope.rfiData.default_city = '';
        $scope.rfiData.default_state = '';
        $scope.rfiData.default_country = mylocation[0];
      }
    }

    // validate and go to next or back path
    if ($scope.rfiData.location.latlon && $scope.rfiData.location.location) {
      // next or back
      // if ((direction === 'next') && $scope.rfiData.event_id) {
      //     //   $location.path('/time'); // go to Time form if event id is passed in
      // } else if ((direction === 'next') && !$scope.rfiData.event_id) {
      if (direction === 'next' && !$scope.rfiData.event_id) {
        // Get filtered members at initial location or if the location has changed
        if (
          typeof $scope.rfiData.members == 'undefined' ||
          $scope.rfiData.members.location != $scope.rfiData.location.location
        ) {
          $scope.rfiData.members = {};
          $scope.rfiData.members.userIds = [];
          $scope.rfiData.members.numFetps = 0;
          $scope.rfiData.members.numUniqueFetps = 0;
          $scope.rfiData.members.searchBox = [];
          $scope.rfiData.members.searchType = 'none';
          // save location
          $scope.rfiData.members.location = $scope.rfiData.location.location;
          $scope.rfiData.members.display_location =
            '(' + $('#autocompleteText').val() + ')';
          $scope.rfiData.members.latlon = $scope.rfiData.location.latlon;
          // get filtered members at chosen location with default radius
          const fdata = {};
          fdata.location = $scope.rfiData.members.location;
          fdata.latlon = $scope.rfiData.members.latlon;
          http({
            url: urlBase + 'scripts/filter.php',
            method: 'POST',
            data: fdata,
          }).then(function successCallback(res) {
            const data = res.data;
            $scope.rfiData.members.userIds = data['userIds'];
            $scope.rfiData.members.numFetps = data['userList']['sending'];
            $scope.rfiData.members.numUniqueFetps =
              data['uniqueList']['sending'];
            $scope.rfiData.members.searchBox = data['bbox'];
            $scope.rfiData.members.searchType = 'radius';
            getMembers();
          });
        } else {
          // $location.path('/members');
          getMembers();
        }
      }
      $scope.location_error_message = '';
    } else {
      $scope.location_error_message = 'Missing parameters above.';
    }
  };

  function resetHealthConditionForPopType() {
    if (!$scope.rfiData.health_condition) {
      return;
    }

    // Type E and U do not require health_condition values. Reset here...
    if (
      $scope.rfiData.population.type == 'E' ||
      $scope.rfiData.population.type == 'U'
    ) {
      $scope.rfiData.health_condition.unknown = false;
      $scope.rfiData.health_condition.other = false;
      $scope.rfiData.health_condition.unknown_animal = false;
      $scope.rfiData.health_condition.other_animal = false;
      $scope.rfiData.health_condition.other_description = '';
      $scope.rfiData.health_condition.other_animal_description = '';
    }
  }
  // //////////////////////////////////////////// Members ////////////////////////////////////////

  /* select members  */
  // if ($location.path() == "/members") {
  function getMembers() {
    // initialize default radio buttons - radius select checked by default
    // $scope.radiussel = $scope.rfiData.members.searchType != "country";

    // bounding box around event location
    // show/hide the submit to next step only if there are FETPs to receive the email
    $scope.submitDisabled = $scope.rfiData.members.numFetps <= 0;

    $scope.bbox = $scope.rfiData.members.searchBox;
    const bounds = new google.maps.LatLngBounds(
      new google.maps.LatLng($scope.bbox[0], $scope.bbox[2]),
      new google.maps.LatLng($scope.bbox[1], $scope.bbox[3]),
    );
    $scope.rectangle = {
      bounds: bounds,
      stroke: { color: '#08B21F', weight: 2, opacity: 1 },
      fill: { color: '#08B21F', opacity: 0.5 },
      editable: true,
      visible: true,
    };
    const latlonarr = $scope.rfiData.members.latlon.split(',');

    /* get member markers for the map */
    $scope.map = {
      center: { latitude: latlonarr[0], longitude: latlonarr[1] },
      zoom: 5,
    };
    $scope.options = { scrollwheel: false };
    /* only show FETPs on a map to super-users */
    const query = {};
    query['centerlat'] = latlonarr[0];
    query['centerlon'] = latlonarr[1];
    http({
      url: urlBase + 'scripts/getmarkers.php',
      method: 'POST',
      data: query,
    }).then(function successCallback(res) {
      const data = res.data;
      if (data['status'] === 'success') {
        $scope.markers = data['markers'];
      }
    });

    /* rectangle change event */
    $scope.eventsRectangle = {
      bounds_changed: function (rectangle) {
        const filterData = {};
        const southwest = rectangle.bounds.getSouthWest();
        const northeast = rectangle.bounds.getNorthEast();
        $scope.radiussel = true; // if radius changes without changing radio button
        $scope.rfiData.members.searchType = 'radius';
        filterData['bbox'] = new Array(
          southwest.lat(),
          northeast.lat(),
          southwest.lng(),
          northeast.lng(),
        );
        http({
          url: urlBase + 'scripts/filter.php',
          method: 'POST',
          data: filterData,
        }).then(function successCallback(res) {
          const filtereddata = res.data;
          $scope.rfiData.members.searchBox = filtereddata['bbox'];
          $scope.rfiData.members.userIds = filtereddata['userIds'];
          $scope.rfiData.members.numFetps = $scope.numFetps =
            filtereddata['userList']['sending'];
          $scope.rfiData.members.numUniqueFetps = $scope.numUniqueFetps =
            filtereddata['uniqueList']['sending'];
          $scope.submitDisabled = $scope.rfiData.members.numFetps <= 0;
        });
      },
    };
  }

  /* get members based on selection type */
  $scope.recalcUsers = function (whichclicked) {
    $scope.saveLocation('next');

    $scope.rfiData.members.searchType = whichclicked;
    $scope.rfiData.members.displayCountries = whichclicked;
    $scope.rfiData.members.filtertype = whichclicked;

    $scope.radiussel = whichclicked != 'country';
    http({
      url: urlBase + 'scripts/filter.php',
      method: 'POST',
      data: $scope.rfiData.members, // filterData
    }).then(function successCallback(res) {
      const filtereddata = res.data;
      $scope.rfiData.members.userIds = filtereddata['userIds'];
      $scope.rfiData.members.numFetps = filtereddata['userList']['sending'];
      $scope.rfiData.members.numUniqueFetps =
        filtereddata['uniqueList']['sending'];
      $scope.submitDisabled = $scope.rfiData.members.numFetps <= 0;
      if (filtereddata['bbox']) {
        $scope.rfiData.members.searchBox = filtereddata['bbox'];
      }
    });
  };

  /* go next or back */
  $scope.saveMembers = function (direction) {
    // next or back
    if (direction === 'next') {
      $location.path('/time');
    } else if (direction === 'back') {
      $location.path('/location');
    }
  };

  $scope.sourceDetailsError = 'Please fill the details above';
  $scope.source_error_message =
    'How did you hear about this event is a required field.';
  $scope.isStep1Invalid = false;

  /* go next or back */
  $scope.saveStep1 = function (direction) {
    $scope.submitDisabled = false;
    $scope.isStep1Invalid = false;

    if (!$scope.rfiData.place) {
      $scope.location_error_message = 'Enter an event location';
      $scope.isStep1Invalid = true;
    }
    if (!$scope.rfiData.location || !$scope.rfiData.location.event_date) {
      $scope.time_error_message = 'Enter a valid date.';
      $scope.isStep1Invalid = true;
    }
    if (!$scope.rfiData.source) {
      $scope.isStep1Invalid = true;
    }
    if (
      !$scope.rfiData.event_id &&
      (!$scope.rfiData.members || !$scope.rfiData.members.filtertype)
    ) {
      // If editing RFI form - map responders are not editable. If new RFI only check for members
      $scope.submitDisabled = true;
    }

    if (
      $scope.rfiData.place &&
      $scope.rfiData.location &&
      $scope.rfiData.location.location !== $('#autocompleteText').val()
    ) {
      // editing location
      $scope.rfiData.location.latlon = getPlaceLatLon($scope.rfiData.place);
      $scope.rfiData.location.location = $('#autocompleteText').val();

      if (!$scope.rfiData.location.latlon) {
        $scope.rfiData.location.location_error_message =
          'Geolocation failed - please scroll down and select a location from the auto-suggester in the location field so that we have the coordinates of the event.';
        $scope.rfiData.location.location = '';
        $scope.isStep1Invalid = true;
      }
    }
    // get city, state, country from location string
    let mylocation;
    if ($scope.rfiData.location && $scope.rfiData.location.location) {
      mylocation = $scope.rfiData.location.location.split(',');

      if (mylocation.length == 4) {
        $scope.rfiData.default_city = mylocation[1];
        $scope.rfiData.default_state = mylocation[2];
        $scope.rfiData.default_country = mylocation[3];
        $scope.rfiData.location.location =
          mylocation[1] + ',' + mylocation[2] + ',' + mylocation[3]; // set location to only city,state,country
      } else if (mylocation.length == 3) {
        $scope.rfiData.default_city = mylocation[0];
        $scope.rfiData.default_state = mylocation[1];
        $scope.rfiData.default_country = mylocation[2];
      } else if (mylocation.length == 2) {
        $scope.rfiData.default_city = '';
        $scope.rfiData.default_state = mylocation[0];
        $scope.rfiData.default_country = mylocation[1];
      } else if (mylocation.length == 1) {
        $scope.rfiData.default_city = '';
        $scope.rfiData.default_state = '';
        $scope.rfiData.default_country = mylocation[0];
      }
    }

    if ($scope.filtertype === 'country' && !$scope.rfiData.members.countries) {
      $scope.isStep1Invalid = true;
    }

    if (
      $scope.rfiData.source &&
      $scope.rfiData.source.source &&
      !$scope.rfiData.source.details
    ) {
      $scope.isStep1Invalid = true;
    }
    if (!$scope.isStep1Invalid && direction === 'next') {
      $location.path('/rfi_step2');
      $scope.time_error_message = '';
    }
  };

  $scope.populationOtherError = '';
  $scope.affectedPopSelectionError = '';
  $scope.healthDetailsError = '';
  $scope.hc_error_message = '';
  $scope.hc_error_message1 = '';
  $scope.populationAnimalError = '';
  $scope.populationAnimalOtherError = '';
  $scope.saveStep2 = function (direction) {
    $scope.isStep2Invalid = false;
    $scope.populationOtherError = '';
    $scope.affectedPopSelectionError = '';
    $scope.healthDetailsError = '';
    $scope.hc_error_message = '';
    $scope.hc_error_message1 = '';

    // next or back
    if (direction === 'next') {
      if (!$scope.rfiData.population) {
        $scope.affectedPopSelectionError =
          'Please select the affected population from above';
        $scope.isStep2Invalid = true;
      }
      if ($scope.rfiData.population) {
        if (
          ($scope.rfiData.population.type == 'E' ||
            $scope.rfiData.population.type == 'U') &&
          !$scope.rfiData.population.other
        ) {
          $scope.populationOtherError = 'Please fill the details above';
          $scope.isStep2Invalid = true;
        }

        if (
          $scope.rfiData.population.type == 'A' &&
          !$scope.rfiData.population.animal_type
        ) {
          $scope.populationAnimalError = 'Please fill the details above';
          $scope.isStep2Invalid = true;
        }

        if (
          $scope.rfiData.population.animal_type === 'O' &&
          !$scope.rfiData.population.other_animal
        ) {
          $scope.populationAnimalOtherError = 'Please fill the details above';
          $scope.isStep2Invalid = true;
        }

        // Reset health_condition if type is E or U selected.
        // This occurs when users choose type A/H and then select E
      }
      if (!$scope.rfiData.health_condition && $scope.rfiData.population) {
        if (
          $scope.rfiData.population.type == 'E' ||
          $scope.rfiData.population.type == 'U'
        ) {
          resetHealthConditionForPopType();
        }
      }
      if (!$scope.rfiData.health_condition) {
        $scope.hc_error_message1 = 'Missing parameters above.';
        $scope.isStep2Invalid = true;
      }
      if (
        $scope.rfiData.health_condition &&
        !$scope.rfiData.health_condition.disease_details
      ) {
        $scope.healthDetailsError = 'Please fill the details above.';
        $scope.isStep2Invalid = true;
      }

      if (
        $scope.rfiData.health_condition &&
        $scope.rfiData.health_condition.other_animal &&
        !$scope.rfiData.health_condition.other_animal_description
      ) {
        $scope.healthDetailsOtherError = 'Please fill the details above.';
        $scope.isStep2Invalid = true;
      }

      if (
        $scope.rfiData.health_condition &&
        $scope.rfiData.health_condition.other &&
        !$scope.rfiData.health_condition.other_description
      ) {
        $scope.healthDetailsOtherError = 'Please fill the details above.';
        $scope.isStep2Invalid = true;
      }

      if (!$scope.isStep2Invalid) {
        //All type of requester submissions must be checked for Dupe RFI
        const bypass = false;
        checkDuplicateRFI2(bypass);
      }

    } else if (direction === 'back') {
      $location.path('/rfi_step1');
    }
  };

  // //////////////////////// Time //////////////////////////////////////////////
  // datepicker options
  $('#datepicker').datepicker({
    format: 'dd-MM-yyyy',
    endDate: 'now',
    startDate: '-1y',
  });

  $scope.time_error_message = '';
  $scope.saveTime = function (direction) {
    // validate and go to next or back path
    if (direction === 'back' || $scope.rfiData.location.event_date) {
      // next or back
      if (direction === 'next') {
        $location.path('/population');
      } else if (direction === 'back' && !$scope.rfiData.event_id) {
        $location.path('/members');
      } else if (direction === 'back' && $scope.rfiData.event_id) {
        $location.path('/location');
      }
      $scope.time_error_message = '';
    } else {
      $scope.time_error_message = 'Missing parameters above.';
    }
  };

  // ////////////////////////  Affected Population //////////////////////////////
  $scope.pop_error_message = '';
  $scope.savePopulation = function (direction) {
    $scope.goback = direction === 'back';

    if (direction === 'back') {
      if ($scope.rfiData.event_id) {
        $location.path('/members');
      } else {
        $location.path('/time');
      }
    } else {
      // validation
      const valid_other_animal =
        $scope.rfiData.population.animal_type != 'O' ||
        $scope.rfiData.population.other_animal;
      const valid_animal =
        $scope.rfiData.population.type != 'A' ||
        ($scope.rfiData.population.animal_type && valid_other_animal);
      // var valid_other = ($scope.rfiData.population.type != 'O') || $scope.rfiData.population.other;
      const valid_other =
        ($scope.rfiData.population.type != 'E' &&
          $scope.rfiData.population.type != 'U') ||
        $scope.rfiData.population.other;
      const valid_population = $scope.rfiData.population.type && valid_other;

      if (valid_population && valid_animal) {
        if (direction === 'next') {
          // Check for duplicate RFI only for original RFI requester
          const bypass = $scope.userInfo.superuser && !$scope.isRequester; // bypass for superusers that are not the original requester
          // bypass = false; // for testing
          checkDuplicateRFI(bypass);

          $location.path('/condition');
        }
        $scope.pop_error_message = '';
      } else {
        $scope.pop_error_message = 'Missing parameters above.';
      }
    }
  };

  // ////////////////////////////////////// Health Condition ////////////////////////////////
  $scope.hc_error_message = '';
  $scope.hc_error_message1 = '';
  $scope.saveCondition = function (direction) {
    $scope.goback = direction === 'back';

    if (direction === 'back') {
      $location.path('/population');
    } else {
      // checkbox validation
      const valid_other =
        !$scope.rfiData.health_condition.other ||
        $scope.rfiData.health_condition.other_description;
      const health_condition_human_valid =
        ($scope.rfiData.health_condition.respiratory ||
          $scope.rfiData.health_condition.gastrointestinal ||
          $scope.rfiData.health_condition.fever_rash ||
          $scope.rfiData.health_condition.jaundice ||
          $scope.rfiData.health_condition.h_fever ||
          $scope.rfiData.health_condition.paralysis ||
          $scope.rfiData.health_condition.other_neurological ||
          $scope.rfiData.health_condition.fever_unknown ||
          $scope.rfiData.health_condition.renal ||
          $scope.rfiData.health_condition.unknown ||
          $scope.rfiData.health_condition.other) &&
        valid_other;

      const valid_other_animal =
        !$scope.rfiData.health_condition.other_animal ||
        $scope.rfiData.health_condition.other_animal_description;
      const health_condition_animal_valid =
        ($scope.rfiData.health_condition.respiratory_animal ||
          $scope.rfiData.health_condition.neurological_animal ||
          $scope.rfiData.health_condition.hemorrhagic_animal ||
          $scope.rfiData.health_condition.vesicular_animal ||
          $scope.rfiData.health_condition.reproductive_animal ||
          $scope.rfiData.health_condition.gastrointestinal_animal ||
          $scope.rfiData.health_condition.multisystemic_animal ||
          $scope.rfiData.health_condition.unknown_animal ||
          $scope.rfiData.health_condition.other_animal) &&
        valid_other_animal;

      const health_condition_ok =
        !(
          $scope.rfiData.population.type == 'H' && !health_condition_human_valid
        ) &&
        !(
          $scope.rfiData.population.type == 'A' &&
          !health_condition_animal_valid
        );

      if (
        direction === 'back' ||
        (health_condition_ok && $scope.rfiData.health_condition.disease_details)
      ) {
        // validation

        // next or back
        if (direction === 'next') {
          // Check for duplicate RFI only for original RFI requester
          // var bypass = $scope.userInfo.superuser && !$scope.isRequester; // bypass for superusers that are not the original requester
          // checkDuplicateRFI( bypass );
          $location.path('/purpose');
        } else if (direction === 'back') {
          $location.path('/population');
        }
        // clear errors
        $scope.hc_error_message = '';
        $scope.hc_error_message1 = '';
      } else if (!health_condition_ok) {
        $scope.hc_error_message1 =
          valid_other && valid_other_animal ?
            'Must select one or more of the above options ' :
            '';
        $scope.hc_error_message = 'Missing parameters above.';
      } else {
        $scope.hc_error_message = 'Missing parameters above.';
        $scope.hc_error_message1 = '';
      }
    }
  };

  // clear health conditions
  $scope.clearCondition = function () {
    if ($scope.rfiData.health_condition) {
      $scope.rfiData.health_condition.respiratory = false;
      $scope.rfiData.health_condition.gastrointestinal = false;
      $scope.rfiData.health_condition.fever_rash = false;
      $scope.rfiData.health_condition.jaundice = false;
      $scope.rfiData.health_condition.h_fever = false;
      $scope.rfiData.health_condition.paralysis = false;
      $scope.rfiData.health_condition.other_neurological = false;
      $scope.rfiData.health_condition.fever_unknown = false;
      $scope.rfiData.health_condition.renal = false;
    }
  };
  $scope.clearCondition2 = function () {
    if ($scope.rfiData.health_condition) {
      $scope.rfiData.health_condition.respiratory_animal = false;
      $scope.rfiData.health_condition.neurological_animal = false;
      $scope.rfiData.health_condition.hemorrhagic_animal = false;
      $scope.rfiData.health_condition.vesicular_animal = false;
      $scope.rfiData.health_condition.reproductive_animal = false;
      $scope.rfiData.health_condition.gastrointestinal_animal = false;
      $scope.rfiData.health_condition.multisystemic_animal = false;
    }
  };
  // clear error
  $scope.clearhcError = function () {
    $scope.hc_error_message2 = '';
  };
  $scope.clearhcError2 = function () {
    $scope.hc_error_message2 = '';
  };

  // check for duplicate RFI
  function checkDuplicateRFI(bypass) {
    if (bypass) {
      $location.path('/condition');
    } else {
      const rfi_data = {};
      rfi_data['population_type'] = $scope.rfiData.population.type;
      // rfi_data['health_condition'] = $scope.rfiData.health_condition;
      rfi_data['location'] = $scope.rfiData.location.location;

      http({
        url: urlBase + 'scripts/checkDuplicateRFI2.php',
        method: 'POST',
        data: rfi_data,
      }).then(function successCallback(res) {
        const respdata = res.data;

        const dup_events = respdata['events'];

        if (respdata['status'] === 'success' && dup_events) {
          // got to duplicate page
          // var dup_event_id = respdata['event_id'];
          // var dup_event_status = respdata['event_status'];

          $scope.rfiData.duplicate_rfis = dup_events;
          $scope.rfiData.duplicate_rfi = {};

          // $scope.rfiData.duplicate_rfi.rfi_id = dup_event_id;
          // $scope.rfiData.duplicate_rfi.rfi_status = dup_event_status;
          $location.path('/duplicate');
        } else if (respdata['status'] == 'notfound') {
          // go to condition page
          $location.path('/condition');

        } else {
          alert(respdata['message']);
        }
      });
    }
  }

  // check for duplicate RFI
  function checkDuplicateRFI2(bypass) {
    $scope.hc_error_message = '';
    if (bypass) {
      $location.path('/rfi_step2');
    } else {
      const rfi_data = {};
      rfi_data['population_type'] = $scope.rfiData.population.type;
      rfi_data['health_condition'] = $scope.rfiData.health_condition;
      rfi_data['location'] = $scope.rfiData.location.location;

      http({
        url: urlBase + 'scripts/checkDuplicateRFI2.php',
        method: 'POST',
        data: rfi_data,
      })
        .then(function successCallback(res) {
          const respdata = res.data;
          const dupe_events = respdata['events'];

          if (respdata['status'] === 'success' && dupe_events) {
            $scope.rfiData.duplicate_rfis = dupe_events;
            $location.path('/duplicate');
          } else if (respdata['status'] == 'notfound') {
            // go to step-3
            $location.path('/rfi_step3');
          } else {
            alert(respdata['message']);
          }
        }, function errorCallback(errorMessage) {
          // called asynchronously if an error occurs
          // or server returns response with an error status.
          $scope.hc_error_message = errorMessage;
          console.error(erroMessage);

        });

    }
  }


  // ////////////////////////////////////// Duplicate RFI(s) Action ////////////////////////////////////////
  $scope.duplicateAction = function () {
    if ($scope.rfiData.duplicate_rfi.rfi_same == '1') {
      // same RFI, do not send

      if (confirm('Are sure? This will clear the RFI you have just entered.')) {
        // clear RFI and go to dashboard
        $window.sessionStorage.clear();
        rfiForm.clear();
        $location.path('/events2');
      }
    } else if ($scope.rfiData.duplicate_rfi.rfi_same == '2') {
      // same RFI, track existing RFI

      if (
        confirm(
          'Are you sure?  This will clear the RFI you have just entered and you will receive all emails regarding the existing RFIs',
        )
      ) {
        // Track RFI
        // var rfi_id = $scope.rfiData.duplicate_rfi.rfi_id;
        const dup_events = $scope.rfiData.duplicate_rfis;
        const event_ids = [];
        dup_events.forEach(function (event) {
          event_ids.push(event.event_id);
        });

        // http({ url: urlBase + 'scripts/trackDuplicateRFI.php', method: "POST", data: {'event_id' :rfi_id, 'user_id': user_id}

        http({
          url: urlBase + 'scripts/trackDuplicateRFI2.php',
          method: 'POST',
          data: { event_ids: event_ids, user_id: user_id },
        }).then(function successCallback(res) {
          const respdata = res.data;
          if (respdata['status'] === 'success') {
            // clear RFI and go to dashbaord
            $window.sessionStorage.clear();
            rfiForm.clear();
            $location.path('/events2');
          }
        });
      }
    } else if ($scope.rfiData.duplicate_rfi.rfi_same == '3') {
      // different RFI, so go ahead and send a new RFI

      // go to condition
      $location.path('/condition');
    } else {
    }
  };

  // //////////////////////////////////////////// Purpose ////////////////////////////////////////
  $scope.purpose_error_message = '';
  $scope.purpose_error_message1 = '';
  $scope.savePurpose = function (direction) {
    $scope.goback = direction === 'back';

    if (direction === 'back') {
      $location.path('/condition');
    } else {
      // checkbox validation
      const valid_purpose =
        $scope.rfiData.purpose.causal_agent ||
        $scope.rfiData.purpose.epidemiology ||
        $scope.rfiData.purpose.pop_affected ||
        $scope.rfiData.purpose.location ||
        $scope.rfiData.purpose.size ||
        $scope.rfiData.purpose.test ||
        $scope.rfiData.purpose.other_category;
      const valid_other_purpose =
        !$scope.rfiData.purpose.other_category || $scope.rfiData.purpose.other;

      // next
      if (
        direction === 'back' ||
        (valid_purpose && valid_other_purpose && $scope.rfiData.purpose.purpose)
      ) {
        // validation
        if (direction === 'next') {
          $location.path('/source');
        } else if (direction === 'back') {
          $location.path('/condition');
        }

        $scope.purpose_error_message = '';
        $scope.purpose_error_message1 = '';
      } else if (!valid_other_purpose) {
        $scope.purpose_error_message = 'Missing parameters above.';
        $scope.purpose_error_message1 = '';
      } else if (!valid_purpose) {
        $scope.purpose_error_message = 'Missing parameters above.';
        $scope.purpose_error_message1 =
          'Must select one or more of the above options.';
      } else {
        $scope.purpose_error_message = 'Missing parameters above.';
        $scope.purpose_error_message1 = '';
      }
    }
  };

  // /////////////////////////////////////////// Source /////////////////////////////////////////
  $scope.purpose_error_message1 = '';
  $scope.purpose_error_message = '';

  // $scope.saveSource = function (direction) {
  $scope.saveStep3 = function (direction) {
    $scope.purpose_error_message = '';
    $scope.purpose_error_message1 = '';

    //  if ((direction === 'back') || $scope.rfiData.source.source && $scope.rfiData.source.details) {
    if (direction === 'next') {
      // save RFI details for review
      // $scope.rfiData.event_location = getLocation();
      // $scope.rfiData.event_population = getPopulation();
      // $scope.rfiData.event_conditions = getConditions();
      // $scope.rfiData.event_title = $scope.rfiData.event_population + ' - ' + $scope.rfiData.event_conditions + ' - ' + $scope.rfiData.event_location + ' - ' + $scope.rfiData.location.event_date;
      // $scope.rfiData.event_purpose = getPurpose();
      // $scope.rfiData.event_source = getSource();

      // Editing location name requires google geolocation to autocomplete
      $scope.saveLocation('next');

      if (!$scope.rfiData.purpose || !$scope.rfiData.purpose.purpose) {
        $scope.purpose_error_message = 'Must select one of the above options.';
        return;
      }

      if (
        $scope.rfiData.purpose.causal_agent === undefined &&
        $scope.rfiData.purpose.epidemiology === undefined &&
        $scope.rfiData.purpose.pop_affected === undefined &&
        $scope.rfiData.purpose.location === undefined &&
        $scope.rfiData.purpose.size === undefined &&
        $scope.rfiData.purpose.test === undefined &&
        $scope.rfiData.purpose.other_category === undefined
      ) {
        $scope.purpose_error_message1 =
          'Must select atleast one of the above options.';
        return;
      }

      $scope.rfiData.event_location = $scope.getLocation_2();
      $scope.rfiData.event_population = $scope.getPopulation_2();
      $scope.rfiData.event_conditions = $scope.getConditions_2() ?
        $scope.getConditions_2().trim() + ' - ' :
        '';

      if ($scope.rfiData.event_conditions) {
        $scope.rfiData.event_title =
          $scope.rfiData.event_population +
          ' - ' +
          $scope.rfiData.event_conditions +
          ' - ' +
          $scope.rfiData.event_location +
          ' - ' +
          $scope.rfiData.location.event_date;
      } else {
        $scope.rfiData.event_title =
          $scope.rfiData.event_population +
          ' - ' +
          $scope.rfiData.event_location +
          ' - ' +
          $scope.rfiData.location.event_date;
      }

      $scope.rfiData.event_purpose = $scope.getPurpose_2();
      $scope.rfiData.event_source = $scope.getSource_2();

      $location.path('/sendrequest');

      // build and review request email - not used for now
      // buildEmailText();
    } else if (direction === 'back') {
      $location.path('/rfi_step2');
    }
    // $scope.source_error_message = '';
  };

  /*
      Adding the following functions (scopes) to retrieve information
      on the review page. Functions "getLocation", "getPopulation" etc are
      not being executed as they were not scoped in proper format
  */

  $scope.getLocation_2 = function () {
    let subnational = '';
    if ($scope.rfiData.default_city) {
      subnational =
        ' (' +
        $scope.rfiData.default_city +
        ',' +
        $scope.rfiData.default_state +
        ')';
    } else if ($scope.rfiData.default_state) {
      subnational = ' (' + $scope.rfiData.default_state + ')';
    }
    return $scope.rfiData.default_country + subnational;
  };
  $scope.getPopulation_2 = function () {
    let population = '';
    switch ($scope.rfiData.population.type) {
      case 'H':
        population = 'Human';
        break;
      case 'A':
        population = getAnimal();
        break;
      case 'E':
        population = 'Environmental, ' + $scope.rfiData.population.other;
        break;
      case 'U':
        population = $scope.rfiData.population.other ?
          $scope.rfiData.population.other :
          'Unknown';
        break;
      case 'O':
        population = $scope.rfiData.population.other;
        break;
    }
    return population;
  };
  $scope.getConditions_2 = function () {
    let species = '';

    if ($scope.rfiData.population.type == 'H') species = 'human';
    else if ($scope.rfiData.population.type == 'A') species = 'animal';
    else if ($scope.rfiData.population.type == 'E') species = 'environment';
    else if ($scope.rfiData.population.type == 'U') species = 'unknown';

    const condition = [];
    if (species == 'human') {
      if ($scope.rfiData.health_condition.respiratory) {
        condition.push('Acute Respiratory');
      }
      if ($scope.rfiData.health_condition.gastrointestinal) {
        condition.push('Gastrointestinal');
      }
      if ($scope.rfiData.health_condition.fever_rash) {
        condition.push('Fever & Rash');
      }
      if ($scope.rfiData.health_condition.jaundice) {
        condition.push('Acute Jaundice');
      }
      if ($scope.rfiData.health_condition.h_fever) {
        condition.push('Hemorrhagic Fever');
      }
      if ($scope.rfiData.health_condition.paralysis) {
        condition.push('Acute Flaccid paralysis');
      }
      if ($scope.rfiData.health_condition.other_neurological) {
        condition.push('Other neurological');
      }
      if ($scope.rfiData.health_condition.fever_unknown) {
        condition.push('Fever of unknown origin');
      }
      if ($scope.rfiData.health_condition.renal) {
        condition.push('Renal failure');
      }
      if ($scope.rfiData.health_condition.unknown) condition.push('Unknown');
      if ($scope.rfiData.health_condition.other) {
        condition.push($scope.rfiData.health_condition.other_description);
      }
    } else if (species == 'animal') {
      if ($scope.rfiData.health_condition.respiratory_animal) {
        condition.push('Respiratory');
      }
      if ($scope.rfiData.health_condition.neurological_animal) {
        condition.push('Neurological');
      }
      if ($scope.rfiData.health_condition.hemorrhagic_animal) {
        condition.push('Haemorrhagic');
      }
      if ($scope.rfiData.health_condition.vesicular_animal) {
        condition.push('Vesicular');
      }
      if ($scope.rfiData.health_condition.reproductive_animal) {
        condition.push('Reproductive');
      }
      if ($scope.rfiData.health_condition.gastrointestinal_animal) {
        condition.push('Gastrointestinal');
      }
      if ($scope.rfiData.health_condition.multisystemic_animal) {
        condition.push('Multisystemic');
      }
      if ($scope.rfiData.health_condition.unknown_animal) {
        condition.push('Unknown');
      }
      if ($scope.rfiData.health_condition.other_animal) {
        condition.push(
          $scope.rfiData.health_condition.other_animal_description,
        );
      }
    }
    // else if (species == 'environment') {
    //     condition.push($scope.rfiData.health_condition.disease_details);
    // }

    return condition.toString();
  };
  $scope.getPurpose_2 = function () {
    const purpose =
      $scope.rfiData.purpose.purpose == 'V' ? 'Verification' : 'Update';
    const type = [];

    const d1 = ' on identified/hypothetical causal agent';
    const d2 =
      ' on the epidemiology including patterns of disease transmission, incubation period';
    const d3 =
      ' on involved population (e.g. human/animal) or specific community';
    const d4 = ' on location of cases or locations at risk for disease spread';
    const d5 = ' on number of cases  (suspected, confirmed, fatalities etc)';
    const d6 = ' on test results';
    const d7 = ' on aspects not reflected in the other categories';

    if ($scope.rfiData.purpose.causal_agent) {
      type.push('PHE Causal Agent: ' + purpose + d1);
    }
    if ($scope.rfiData.purpose.epidemiology) {
      type.push('PHE Epidemiology: ' + purpose + d2);
    }
    if ($scope.rfiData.purpose.pop_affected) {
      type.push('PHE population affected: ' + purpose + d3);
    }
    if ($scope.rfiData.purpose.location) {
      type.push('PHE Location: ' + purpose + d4);
    }
    if ($scope.rfiData.purpose.size) type.push('PHE Size: ' + purpose + d5);
    if ($scope.rfiData.purpose.test) {
      type.push('PHE Test Results: ' + purpose + d6);
    }
    if ($scope.rfiData.purpose.other_category) {
      type.push(
        'Other: ' + purpose + d7 + ' - ' + $scope.rfiData.purpose.other,
      );
    }

    return type; // type.toString();
  };
  $scope.getSource_2 = function () {
    if ($scope.rfiData.source.source == 'MR') return 'Media Report';
    else if ($scope.rfiData.source.source == 'OR') return 'Official Report';
    else if ($scope.rfiData.source.source == 'OC') return 'Other Communication';
    else return 'none';
  };

  /*
   *************************    END    *************************
   */

  // buildEmailText
  $scope.filePreview = $window.sessionStorage.filePreview;
  const buildEmailText = function () {
    const formData = {};
    formData['title'] = $scope.rfiData.event_title;
    // overwrite the old file preview if it exists
    if (typeof $window.sessionStorage.filePreview != 'undefined') {
      formData['file_preview'] = $window.sessionStorage.filePreview;
    }
    http({
      url: urlBase + 'scripts/buildrequest2.php',
      method: 'POST',
      data: formData,
    }).then(function successCallback(res) {
      const respdata = res.data;
      $window.sessionStorage.filePreview = respdata['file_preview'];
      $location.path('/sendrequest');
    });
  };

  function getSource() {
    if ($scope.rfiData.source.source == 'MR') return 'Media Report';
    else if ($scope.rfiData.source.source == 'OR') return 'Official Report';
    else if ($scope.rfiData.source.source == 'OC') return 'Other Communication';
    else return 'none';
  }

  function getPurpose() {
    const purpose =
      $scope.rfiData.purpose.purpose == 'V' ? 'Verification' : 'Update';
    const type = [];

    const d1 = ' on identified/hypothetical causal agent';
    const d2 =
      ' on the epidemiology including patterns of disease transmission, incubation period';
    const d3 =
      ' on involved population (e.g. human/animal) or specific community';
    const d4 = ' on location of cases or locations at risk for disease spread';
    const d5 = ' on number of cases  (suspected, confirmed, fatalities etc)';
    const d6 = ' on test results';
    const d7 = ' on aspects not reflected in the other categories';

    if ($scope.rfiData.purpose.causal_agent) {
      type.push('PHE Causal Agent: ' + purpose + d1);
    }
    if ($scope.rfiData.purpose.epidemiology) {
      type.push('PHE Epidemiology: ' + purpose + d2);
    }
    if ($scope.rfiData.purpose.pop_affected) {
      type.push('PHE population affected: ' + purpose + d3);
    }
    if ($scope.rfiData.purpose.location) {
      type.push('PHE Location: ' + purpose + d4);
    }
    if ($scope.rfiData.purpose.size) type.push('PHE Size: ' + purpose + d5);
    if ($scope.rfiData.purpose.test) {
      type.push('PHE Test Results: ' + purpose + d6);
    }
    if ($scope.rfiData.purpose.other_category) {
      type.push(
        'Other: ' + purpose + d7 + ' - ' + $scope.rfiData.purpose.other,
      );
    }

    return type; // type.toString();
  }

  function getLocation() {
    let subnational = '';
    if ($scope.rfiData.default_city) {
      subnational =
        ' (' +
        $scope.rfiData.default_city +
        ',' +
        $scope.rfiData.default_state +
        ')';
    } else if ($scope.rfiData.default_state) {
      subnational = ' (' + $scope.rfiData.default_state + ')';
    }
    return $scope.rfiData.default_country + subnational;
  }

  function getPopulation() {
    let population = '';
    switch ($scope.rfiData.population.type) {
      case 'H':
        population = 'Human';
        break;
      case 'A':
        population = getAnimal();
        break;
      case 'E':
        population = 'Environmental, ' + $scope.rfiData.population.other;
        break;
      case 'U':
        population = 'Unknown, ' + $scope.rfiData.population.other;
        break;
      case 'O':
        population = $scope.rfiData.population.other;
        break;
    }
    return population;
  }

  function getAnimal() {
    let animal = '';
    switch ($scope.rfiData.population.animal_type) {
      case 'B':
        animal = 'Birds/Poultry';
        break;
      case 'P':
        animal = 'Pigs/Swine';
        break;
      case 'C':
        animal = 'Cattle';
        break;
      case 'G':
        animal = 'Goats/Sheep';
        break;
      case 'D':
        animal = 'Dogs/Cats';
        break;
      case 'H':
        animal = 'Horses/Equines';
        break;
      case 'O':
        animal = $scope.rfiData.population.other_animal;
        break;
      default:
        break;
    }
    return animal;
  }

  function getConditions() {
    let species = '';

    if ($scope.rfiData.population.type == 'H') species = 'human';
    else if ($scope.rfiData.population.type == 'A') species = 'animal';
    else if ($scope.rfiData.population.type == 'E') species = 'environment';
    else if ($scope.rfiData.population.type == 'U') species = 'unknown';

    const condition = [];
    if (species == 'human') {
      if ($scope.rfiData.health_condition.respiratory) {
        condition.push('Acute Respiratory');
      }
      if ($scope.rfiData.health_condition.gastrointestinal) {
        condition.push('Gastrointestinal');
      }
      if ($scope.rfiData.health_condition.fever_rash) {
        condition.push('Fever & Rash');
      }
      if ($scope.rfiData.health_condition.jaundice) {
        condition.push('Acute Jaundice');
      }
      if ($scope.rfiData.health_condition.h_fever) {
        condition.push('Hemorrhagic Fever');
      }
      if ($scope.rfiData.health_condition.paralysis) {
        condition.push('Acute Flaccid paralysis');
      }
      if ($scope.rfiData.health_condition.other_neurological) {
        condition.push('Other neurological');
      }
      if ($scope.rfiData.health_condition.fever_unknown) {
        condition.push('Fever of unknown origin');
      }
      if ($scope.rfiData.health_condition.renal) {
        condition.push('Renal failure');
      }
      if ($scope.rfiData.health_condition.unknown) condition.push('Unknown');
      if ($scope.rfiData.health_condition.other) {
        condition.push($scope.rfiData.health_condition.other_description);
      }
    } else if (species == 'animal') {
      if ($scope.rfiData.health_condition.respiratory_animal) {
        condition.push('Respiratory');
      }
      if ($scope.rfiData.health_condition.neurological_animal) {
        condition.push('Neurological');
      }
      if ($scope.rfiData.health_condition.hemorrhagic_animal) {
        condition.push('Haemorrhagic');
      }
      if ($scope.rfiData.health_condition.vesicular_animal) {
        condition.push('Vesicular');
      }
      if ($scope.rfiData.health_condition.reproductive_animal) {
        condition.push('Reproductive');
      }
      if ($scope.rfiData.health_condition.gastrointestinal_animal) {
        condition.push('Gastrointestinal');
      }
      if ($scope.rfiData.health_condition.multisystemic_animal) {
        condition.push('Multisystemic');
      }
      if ($scope.rfiData.health_condition.unknown_animal) {
        condition.push('Unknown');
      }
      if ($scope.rfiData.health_condition.other_animal) {
        condition.push(
          $scope.rfiData.health_condition.other_animal_description,
        );
      }
    }
    return condition.toString();
  }

  $scope.sendRFIButtonText = 'Send RFI';
  /* Send request */
  $scope.sendRequest2 = function (direction) {
    if (direction === 'next') {
      $scope.submitDisabled = true;
      $scope.sendRFIButtonText = 'Please wait ....';
      // go to success page for testing. Remove this when done testing.
      // $location.path('/sent');

      const formData = {};
      if ($scope.rfiData.members.searchType == 'radius') {
        formData['search_box'] = $scope.rfiData.members.searchBox.toString();
      } else {
        formData[
          'search_countries'
        ] = $scope.rfiData.members.countries.toString();
      }
      formData['fetp_ids'] = $scope.rfiData.members.userIds;
      formData['population'] = $scope.rfiData.population;
      formData['health_condition'] = $scope.rfiData.health_condition;
      formData['health_condition_details'] =
        $scope.rfiData.health_condition.disease_details;
      formData['location'] = $scope.rfiData.location;
      formData['purpose'] = $scope.rfiData.purpose;
      formData['source'] = $scope.rfiData.source;
      formData['title'] = $scope.rfiData.event_title;
      formData['additionalText'] = $scope.rfiData.additionalText;
      formData['duplicate_rfi_detected'] =
        $scope.rfiData.duplicate_rfi &&
          $scope.rfiData.duplicate_rfi.rfi_same == '3' ?
          1 :
          0; // possibile duplicate RFI

      if (
        formData['duplicate_rfi_detected'] &&
        typeof $scope.rfiData.duplicate_rfis != 'undefined'
      ) {
        const dup_events = [];
        $scope.rfiData.duplicate_rfis.forEach(function (event) {
          dup_events.push({ id: event.event_id, title: event.title });
        });
        formData['duplicate_events'] = dup_events;
      }

      // formData['duplicate_rfi_id'] = ($scope.rfiData.duplicate_rfi && $scope.rfiData.duplicate_rfi.rfi_id) ? $scope.rfiData.duplicate_rfi.rfi_id : 0;
      http({
        url: urlBase + 'scripts/sendrequest2.php',
        method: 'POST',
        data: formData,
      }).then(function successCallback() {
        // go to success page
        $location.path('/sent');
        $scope.submitDisabled = false;
      });
    } else if (direction === 'back') {
      $location.path('/rfi_step3');
    }
  };

  /* update request */
  $scope.updateRequest = function (direction) {
    if (direction === 'next') {
      $scope.submitDisabled = true;

      // update request
      const formData = {};
      formData['event_id'] = $scope.rfiData.event_id;
      formData['title'] = $scope.rfiData.event_title;
      formData['location'] = $scope.rfiData.location;
      formData['population'] = $scope.rfiData.population;
      formData['health_condition'] = $scope.rfiData.health_condition;
      formData['purpose'] = $scope.rfiData.purpose;
      formData['source'] = $scope.rfiData.source;

      http({
        url: urlBase + 'scripts/updaterequest2.php',
        method: 'POST',
        data: formData,
      }).then(
        function (response) {
          if (response.data) {
            const respdata = response.data;
            if (respdata['status'] === 'success') {
              // empty out the form values so they aren't pre-filled next time
              $window.sessionStorage.clear();
              rfiForm.clear();
              $location.path('/success/6');
            } else {
            }
          }
          $scope.submitDisabled = false;
        },
        function (erroMessage) {
          console.error(erroMessage);
        },
      );
    } else if (direction === 'back') {
      $location.path('/rfi_step3');
    }
  };

  /* Sent request */
  $scope.sentRFI = function () {
    // empty out the form values so they aren't pre-filled next time
    $window.sessionStorage.clear();
    rfiForm.clear();
    $location.path('/events2');
  };

  $scope.clearRequest = function () {
    if (confirm('Are you sure you want to clear this RFI?')) {
      $window.sessionStorage.clear();
      rfiForm.clear();
      $location.path('/rfi_step1');
    }
  };
};

RequestController2.$inject = [
  '$rootScope',
  '$window',
  '$scope',
  '$routeParams',
  '$cookieStore',
  '$location',
  'httpServiceInterceptor',
  'urlBase',
  'rfiForm',
  'epicoreVersion',
];

export default RequestController2;
