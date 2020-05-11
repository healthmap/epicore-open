angular.module('EpicoreApp.controllers2', []).

/* Request (RFI): this is the controller that sends an RFI. */
controller('requestController2', function ($rootScope, $window, $scope, $routeParams, $cookieStore, $location, $http, urlBase, rfiForm, epicoreVersion) {

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
        var eventData = {};
        eventData['event_id'] = $scope.rfiData.event_id;
        $http({
            url: urlBase + 'scripts/getrequest2.php', method: "POST", data: eventData
        }).success(function (data, status, headers, config) {

            console.log(data);
            // populate form
            $scope.rfiData.location = {};
            $scope.rfiData.health_condition = {};
            $scope.rfiData.population = {};
            $scope.rfiData.source = {};

            $scope.rfiData.location.location = data.event.location;
            $scope.rfiData.location.event_date = data.event.event_date;
            $scope.rfiData.location.event_date_details = data.event.event_date_details;
            $scope.rfiData.location.latlon = data.event.latlon;
            $scope.rfiData.location.location_details = data.event.location_details;
            $scope.rfiData.additionalText = data.event.personalized_text;

            $scope.rfiData.population = data.population;
            $scope.rfiData.purpose = data.purpose;
            $scope.rfiData.health_condition = data.health_condition;
            $scope.rfiData.source = data.source;

            $scope.rfiData.place = data.event.location;

            $scope.isRequester = (data.event.requester_id == $scope.userInfo.uid);

        });

    }

    /////////////////////////////////////////////// Location ////////////////////////////////

    // autocomplete options for google api places
    $scope.autocompleteOptions = {
        types: ['(regions)']
    }

    getPlaceLatLon = function (place) {
        return place.geometry.location.lat() + ',' + place.geometry.location.lng();
    };

    $scope.location_error_message = '';
    $scope.saveLocation = function (direction) {

        // jquery hack to get the latlon hidden value and autocomplete for location (angular bug)
        //$scope.rfiData.location.latlon = $("#default_location").val();
        //$scope.rfiData.location.location = $("#searchTextField").val(); // format: "country" or "state, country" or "city, state, country"

        // only use google places for new events
        if (!$scope.rfiData.event_id) {
            $scope.rfiData.location.latlon = getPlaceLatLon($scope.rfiData.place);
            $scope.rfiData.location.location = $("#autocompleteText").val();

            

            if (!$scope.rfiData.location.latlon) {
                $scope.rfiData.location.location_error_message = 'Geolocation failed - please scroll down and select a location from the auto-suggester in the location field so that we have the coordinates of the event.';
                $scope.rfiData.location.location = '';
                return false;
            }
        }

        // get city, state, country from location string
        var mylocation = $scope.rfiData.location.location.split(",");
        if (mylocation.length == 4) {
            $scope.rfiData.default_city = mylocation[1];
            $scope.rfiData.default_state = mylocation[2];
            $scope.rfiData.default_country = mylocation[3];
            $scope.rfiData.location.location = mylocation[1] + ',' + mylocation[2] + ',' + mylocation[3]; // set location to only city,state,country
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

        // validate and go to next or back path
        if ($scope.rfiData.location.latlon && $scope.rfiData.location.location) {

            // next or back
            if ((direction === 'next') && $scope.rfiData.event_id) {
                //   $location.path('/time'); // go to Time form if event id is passed in
            } else if ((direction === 'next') && !$scope.rfiData.event_id) {

                // Get filtered members at initial location or if the location has changed
                if ((typeof ($scope.rfiData.members) == 'undefined') || ($scope.rfiData.members.location != $scope.rfiData.location.location)) {

                    $scope.rfiData.members = {};
                    $scope.rfiData.members.userIds = [];
                    $scope.rfiData.members.numFetps = 0;
                    $scope.rfiData.members.numUniqueFetps = 0;
                    $scope.rfiData.members.searchBox = [];
                    $scope.rfiData.members.searchType = 'none';
                    // save location
                    $scope.rfiData.members.location = $scope.rfiData.location.location;
                    $scope.rfiData.members.display_location = "(" + $("#autocompleteText").val() + ")";
                    $scope.rfiData.members.latlon = $scope.rfiData.location.latlon;

                    // get filtered members at chosen location with default radius
                    fdata = {};
                    fdata.location = $scope.rfiData.members.location;
                    fdata.latlon = $scope.rfiData.members.latlon;
                    $http({
                        url: urlBase + 'scripts/filter.php', method: "POST", data: fdata
                    }).success(function (data, status, headers, config) {
                        $scope.rfiData.members.userIds = data['userIds'];
                        $scope.rfiData.members.numFetps = data['userList']['sending'];
                        $scope.rfiData.members.numUniqueFetps = data['uniqueList']['sending'];
                        $scope.rfiData.members.searchBox = data['bbox'];
                        $scope.rfiData.members.searchType = 'radius';
                        getMembers();
                    }).error(function (data, status, headers, config) {
                        console.log(status);
                    });
                } else {
                    //$location.path('/members');
                    getMembers();
                }
            }
            $scope.location_error_message = '';

        } else {
            $scope.location_error_message = 'Missing parameters above.';
        }
    };

    ////////////////////////////////////////////// Members ////////////////////////////////////////

    /* select members  */
    // if ($location.path() == "/members") {
    function getMembers() {     
        // initialize default radio buttons - radius select checked by default
        //$scope.radiussel = $scope.rfiData.members.searchType != "country";

        // bounding box around event location
        // show/hide the submit to next step only if there are FETPs to receive the email
        $scope.submitDisabled = $scope.rfiData.members.numFetps <= 0;

        $scope.bbox = $scope.rfiData.members.searchBox;
        var bounds = new google.maps.LatLngBounds(new google.maps.LatLng($scope.bbox[0], $scope.bbox[2]), new google.maps.LatLng($scope.bbox[1], $scope.bbox[3]));
        $scope.rectangle = { bounds: bounds, stroke: { color: '#08B21F', weight: 2, opacity: 1 }, fill: { color: '#08B21F', opacity: 0.5 }, editable: true, visible: true };
        var latlonarr = $scope.rfiData.members.latlon.split(",");

        /* get member markers for the map */
        $scope.map = { center: { latitude: latlonarr[0], longitude: latlonarr[1] }, zoom: 5 }
        $scope.options = { scrollwheel: false };
        /* only show FETPs on a map to super-users */
        var query = {};
        query['uid'] = $scope.userInfo.uid;
        query['centerlat'] = latlonarr[0];
        query['centerlon'] = latlonarr[1];
        $http({
            url: urlBase + 'scripts/getmarkers.php', method: "POST", data: query
        }).success(function (data, status, headers, config) {
            if (data['status'] == "success") {
                $scope.markers = data['markers'];
            }
        });

        /* rectangle change event */
        $scope.eventsRectangle = {
            bounds_changed: function (rectangle) {
                var filterData = {};
                var southwest = rectangle.bounds.getSouthWest();
                var northeast = rectangle.bounds.getNorthEast();
                $scope.radiussel = true; // if radius changes without changing radio button
                $scope.rfiData.members.searchType = 'radius';
                filterData['bbox'] = new Array(southwest.lat(), northeast.lat(), southwest.lng(), northeast.lng());
                $http({
                    url: urlBase + 'scripts/filter.php', method: "POST", data: filterData
                }).success(function (filtereddata, status, headers, config) {
                    $scope.rfiData.members.searchBox = filtereddata['bbox'];
                    $scope.rfiData.members.userIds = filtereddata['userIds'];
                    $scope.rfiData.members.numFetps = $scope.numFetps = filtereddata['userList']['sending'];
                    $scope.rfiData.members.numUniqueFetps = $scope.numUniqueFetps = filtereddata['uniqueList']['sending'];
                    $scope.submitDisabled = $scope.rfiData.members.numFetps <= 0;
                });
            }
        }
    }

    /* get members based on selection type */
    $scope.recalcUsers = function (whichclicked) {
        $scope.saveLocation('next');
        $scope.rfiData.members.searchType = whichclicked;
        $scope.rfiData.members.displayCountries = whichclicked;
        $scope.rfiData.members.filtertype = whichclicked;
        $scope.radiussel = whichclicked != "country";
        $http({
            url: urlBase + 'scripts/filter.php', method: "POST", data: $scope.rfiData.members //filterData
        }).success(function (filtereddata, status, headers, config) {
            console.log("Filtered Data ====> ", filtereddata);
            $scope.rfiData.members.userIds = filtereddata['userIds'];
            $scope.rfiData.members.numFetps = filtereddata['userList']['sending'];
            $scope.rfiData.members.numUniqueFetps = filtereddata['uniqueList']['sending'];
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

    $scope.sourceDetailsError = "";

    /* go next or back */
    $scope.saveStep1 = function (direction) {
        if ((direction === 'back') || $scope.rfiData.location.event_date && $scope.rfiData.source.source ) {
            // next or back
            if (direction === 'next') {
                if($scope.rfiData.source && !($scope.rfiData.source.details)){
                    $scope.sourceDetailsError = "Please fill the details above";
                    return;
                }
                $location.path('/rfi_step2');
            }
            $scope.time_error_message = '';
        } else if (!$scope.rfiData.location.event_date ){

            $scope.time_error_message = 'Enter a valid date.';
        } else if (!$scope.rfiData.source.source){      $scope.source_error_message = 'How did you hear about this event is a required field.';
        }
    };

    $scope.populationOtherError = "";
    $scope.saveStep2 = function (direction) {

        // next or back
        if (direction === 'next') {
            if($scope.rfiData.population.type && !($scope.rfiData.population.other)){
                $scope.populationOtherError = "Please fill the details above";
                return;
            }
            console.log("RFI Data Step 3 --> ", $scope.rfiData)
            $location.path('/rfi_step3');
        } else if (direction === 'back') {
            $location.path('/rfi_step1');
        }
    };

    ////////////////////////// Time //////////////////////////////////////////////
    // datepicker options
    $("#datepicker").datepicker({
        format: "dd-MM-yyyy",
        endDate: 'now',
        startDate: '-1y'
    });

    $scope.time_error_message = '';
    $scope.saveTime = function (direction) {

        // validate and go to next or back path
        if ((direction === 'back') || $scope.rfiData.location.event_date) {

            // next or back
            if (direction === 'next') {
                $location.path('/population');
            } else if ((direction === 'back') && !$scope.rfiData.event_id) {
                $location.path('/members');
            } else if ((direction === 'back') && $scope.rfiData.event_id) {
                $location.path('/location');
            }
            $scope.time_error_message = '';

        } else {
            $scope.time_error_message = 'Missing parameters above.';
        }
    }


    //////////////////////////  Affected Population //////////////////////////////
    $scope.pop_error_message = '';
    $scope.savePopulation = function (direction) {

        $scope.goback = (direction === 'back');

        if (direction === 'back') {
            if ($scope.rfiData.event_id) {
                $location.path('/members');
            } else {
                $location.path('/time');
            }

        } else {
            // validation
            var valid_other_animal = ($scope.rfiData.population.animal_type != 'O') || $scope.rfiData.population.other_animal;
            var valid_animal = ($scope.rfiData.population.type != 'A') || ($scope.rfiData.population.animal_type && valid_other_animal);
            //var valid_other = ($scope.rfiData.population.type != 'O') || $scope.rfiData.population.other;
            var valid_other = (($scope.rfiData.population.type != 'E') && ($scope.rfiData.population.type != 'U')) || $scope.rfiData.population.other;
            var valid_population = $scope.rfiData.population.type && valid_other;

            if (valid_population && valid_animal) {

                if (direction === 'next') {

                    // Check for duplicate RFI only for original RFI requester
                    var bypass = $scope.userInfo.superuser && !$scope.isRequester; // bypass for superusers that are not the original requester
                    //bypass = false; // for testing
                    checkDuplicateRFI(bypass);

                    $location.path('/condition');
                }
                $scope.pop_error_message = '';
            } else {
                $scope.pop_error_message = 'Missing parameters above.';
            }
        }
    };

    //////////////////////////////////////// Health Condition ////////////////////////////////
    $scope.hc_error_message = '';
    $scope.hc_error_message1 = '';
    $scope.saveCondition = function (direction) {
        $scope.goback = (direction === 'back');

        if (direction === 'back') {
            $location.path('/population');
        } else {
            // checkbox validation
            var valid_other = (!$scope.rfiData.health_condition.other || $scope.rfiData.health_condition.other_description);
            var health_condition_human_valid = ($scope.rfiData.health_condition.respiratory || $scope.rfiData.health_condition.gastrointestinal || $scope.rfiData.health_condition.fever_rash || $scope.rfiData.health_condition.jaundice
                    || $scope.rfiData.health_condition.h_fever || $scope.rfiData.health_condition.paralysis || $scope.rfiData.health_condition.other_neurological || $scope.rfiData.health_condition.fever_unknown || $scope.rfiData.health_condition.renal
                    || $scope.rfiData.health_condition.unknown || $scope.rfiData.health_condition.other) && valid_other;

            var valid_other_animal = (!$scope.rfiData.health_condition.other_animal || $scope.rfiData.health_condition.other_animal_description);
            var health_condition_animal_valid = ($scope.rfiData.health_condition.respiratory_animal || $scope.rfiData.health_condition.neurological_animal || $scope.rfiData.health_condition.hemorrhagic_animal
                    || $scope.rfiData.health_condition.vesicular_animal || $scope.rfiData.health_condition.reproductive_animal || $scope.rfiData.health_condition.gastrointestinal_animal || $scope.rfiData.health_condition.multisystemic_animal
                    || $scope.rfiData.health_condition.unknown_animal || $scope.rfiData.health_condition.other_animal) && valid_other_animal;

            var health_condition_ok = !(($scope.rfiData.population.type == 'H') && !health_condition_human_valid) && !(($scope.rfiData.population.type == 'A') && !health_condition_animal_valid);

            if ((direction === 'back') || health_condition_ok && $scope.rfiData.health_condition.disease_details) { // validation

                // next or back
                if (direction === 'next') {

                    // Check for duplicate RFI only for original RFI requester
                    //var bypass = $scope.userInfo.superuser && !$scope.isRequester; // bypass for superusers that are not the original requester
                    //checkDuplicateRFI( bypass );
                    $location.path('/purpose');

                } else if (direction === 'back') {
                    $location.path('/population');
                }
                // clear errors
                $scope.hc_error_message = '';
                $scope.hc_error_message1 = '';
            } else if (!health_condition_ok) {
                $scope.hc_error_message1 = (valid_other && valid_other_animal) ? 'Must select one or more of the above options ' : '';
                $scope.hc_error_message = 'Missing parameters above.';
            } else {
                $scope.hc_error_message = 'Missing parameters above.';
                $scope.hc_error_message1 = '';
            }
        }
    };

    // clear health conditions
    $scope.clearCondition = function () {
        $scope.rfiData.health_condition.respiratory = false;
        $scope.rfiData.health_condition.gastrointestinal = false;
        $scope.rfiData.health_condition.fever_rash = false;
        $scope.rfiData.health_condition.jaundice = false;
        $scope.rfiData.health_condition.h_fever = false;
        $scope.rfiData.health_condition.paralysis = false;
        $scope.rfiData.health_condition.other_neurological = false;
        $scope.rfiData.health_condition.fever_unknown = false;
        $scope.rfiData.health_condition.renal = false;

    };
    $scope.clearCondition2 = function () {
        $scope.rfiData.health_condition.respiratory_animal = false;
        $scope.rfiData.health_condition.neurological_animal = false;
        $scope.rfiData.health_condition.hemorrhagic_animal = false;
        $scope.rfiData.health_condition.vesicular_animal = false;
        $scope.rfiData.health_condition.reproductive_animal = false;
        $scope.rfiData.health_condition.gastrointestinal_animal = false;
        $scope.rfiData.health_condition.multisystemic_animal = false;
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
            var rfi_data = {};
            rfi_data['population_type'] = $scope.rfiData.population.type;
            //rfi_data['health_condition'] = $scope.rfiData.health_condition;
            rfi_data['location'] = $scope.rfiData.location.location;

            $http({
                url: urlBase + 'scripts/checkDuplicateRFI2.php', method: "POST", data: rfi_data
            }).success(function (respdata, status, headers, config) {

                //console.log(respdata);

                var dup_events = respdata['events'];

                if (respdata['status'] == 'success' && dup_events) { // got to duplicate page
                    //var dup_event_id = respdata['event_id'];
                    //var dup_event_status = respdata['event_status'];

                    $scope.rfiData.duplicate_rfis = dup_events;
                    $scope.rfiData.duplicate_rfi = {};

                    //$scope.rfiData.duplicate_rfi.rfi_id = dup_event_id;
                    //$scope.rfiData.duplicate_rfi.rfi_status = dup_event_status;
                    $location.path('/duplicate');

                } else if (respdata['status'] == 'notfound') { // go to condition page
                    $location.path('/condition');
                } else {
                    alert(respdata['message']);
                }

            });
        }

    }

    //////////////////////////////////////// Duplicate RFI(s) Action ////////////////////////////////////////
    $scope.duplicateAction = function () {

        if ($scope.rfiData.duplicate_rfi.rfi_same == '1') {  // same RFI, do not send

            if (confirm('Are sure? This will clear the RFI you have just entered.')) {

                // clear RFI and go to dashboard
                $window.sessionStorage.clear();
                rfiForm.clear();
                $location.path('/events2')
            }

        } else if ($scope.rfiData.duplicate_rfi.rfi_same == '2') { // same RFI, track existing RFI

            if (confirm('Are you sure?  This will clear the RFI you have just entered and you will receive all emails regarding the existing RFIs')) {

                // Track RFI
                //var rfi_id = $scope.rfiData.duplicate_rfi.rfi_id;
                var user_id = $scope.userInfo.uid;
                var dup_events = $scope.rfiData.duplicate_rfis;
                var event_ids = [];
                dup_events.forEach(function (event) {
                    event_ids.push(event.event_id);
                });

                //$http({ url: urlBase + 'scripts/trackDuplicateRFI.php', method: "POST", data: {'event_id' :rfi_id, 'user_id': user_id}

                $http({
                    url: urlBase + 'scripts/trackDuplicateRFI2.php', method: "POST", data: { 'event_ids': event_ids, 'user_id': user_id }
                }).success(function (respdata, status, headers, config) {

                    if (respdata['status'] == 'success') {

                        // clear RFI and go to dashbaord
                        $window.sessionStorage.clear();
                        rfiForm.clear();
                        $location.path('/events2');

                    } else {
                        console.log(respdata['message']);
                    }

                });
            }

            } else if ($scope.rfiData.duplicate_rfi.rfi_same == '3') { // different RFI, so go ahead and send a new RFI

                // go to condition
                $location.path('/condition');

            } else {
                console.log('error');
            }
        };


        ////////////////////////////////////////////// Purpose ////////////////////////////////////////
        $scope.purpose_error_message = '';
        $scope.purpose_error_message1 = '';
        $scope.savePurpose = function (direction) {

            $scope.goback = (direction === 'back');


            if (direction === 'back') {
                $location.path('/condition');
            } else {
                // checkbox validation
                var valid_purpose = $scope.rfiData.purpose.causal_agent || $scope.rfiData.purpose.epidemiology || $scope.rfiData.purpose.pop_affected || $scope.rfiData.purpose.location
                    || $scope.rfiData.purpose.size || $scope.rfiData.purpose.test || $scope.rfiData.purpose.other_category;
                var valid_other_purpose = !$scope.rfiData.purpose.other_category || $scope.rfiData.purpose.other;

                // next
                if ((direction === 'back') || valid_purpose && valid_other_purpose && $scope.rfiData.purpose.purpose) { // validation
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
                }
                else if (!valid_purpose) {
                    $scope.purpose_error_message = 'Missing parameters above.';
                    $scope.purpose_error_message1 = 'Must select one or more of the above options.';
                } else {
                    $scope.purpose_error_message = 'Missing parameters above.';
                    $scope.purpose_error_message1 = '';
                }

            }
        };

        ///////////////////////////////////////////// Source /////////////////////////////////////////
        $scope.source_error_message = '';
        // $scope.saveSource = function (direction) {
        $scope.saveStep3 = function (direction) {

            //  if ((direction === 'back') || $scope.rfiData.source.source && $scope.rfiData.source.details) {
            if (direction === 'next') {

                // save RFI details for review
                $scope.rfiData.event_location = getLocation();
                $scope.rfiData.event_population = getPopulation();
                $scope.rfiData.event_conditions = getConditions();
                $scope.rfiData.event_title = $scope.rfiData.event_population + ' - ' + $scope.rfiData.event_conditions + ' - ' + $scope.rfiData.event_location + ' - ' + $scope.rfiData.location.event_date;
                $scope.rfiData.event_purpose = getPurpose();
                $scope.rfiData.event_source = getSource();
                $location.path('/sendrequest');

                // build and review request email - not used for now
                //buildEmailText();

            } else if (direction === 'back') {
                $location.path('/rfi_step2');
            }
            // $scope.source_error_message = '';

        };

        // buildEmailText
        $scope.filePreview = $window.sessionStorage.filePreview;
        buildEmailText = function () {

            var formData = {};
            formData['title'] = $scope.rfiData.event_title;
            // overwrite the old file preview if it exists
            if (typeof ($window.sessionStorage.filePreview) != "undefined") {
                formData['file_preview'] = $window.sessionStorage.filePreview;
            }
            $http({
                url: urlBase + 'scripts/buildrequest2.php', method: "POST", data: formData
            }).success(function (respdata, status, headers, config) {
                $window.sessionStorage.filePreview = respdata['file_preview'];
                $location.path('/sendrequest');
            });
        };

        function getSource() {
            if ($scope.rfiData.source.source == "MR")
                return "Media Report";
            else if ($scope.rfiData.source.source == "OR")
                return "Official Report";
            else if ($scope.rfiData.source.source == "OC")
                return "Other Communication";
            else
                return "none";
        }

        function getPurpose() {

            var purpose = $scope.rfiData.purpose.purpose == "V" ? "Verification" : "Update";
            var type = [];

            var d1 = ' on identified/hypothetical causal agent';
            var d2 = ' on the epidemiology including patterns of disease transmission, incubation period';
            var d3 = ' on involved population (e.g. human/animal) or specific community';
            var d4 = ' on location of cases or locations at risk for disease spread';
            var d5 = ' on number of cases  (suspected, confirmed, fatalities etc)';
            var d6 = ' on test results';
            var d7 = ' on aspects not reflected in the other categories';

            if ($scope.rfiData.purpose.causal_agent)
                type.push("PHE Causal Agent: " + purpose + d1);
            if ($scope.rfiData.purpose.epidemiology)
                type.push("PHE Epidemiology: " + purpose + d2);
            if ($scope.rfiData.purpose.pop_affected)
                type.push("PHE population affected: " + purpose + d3);
            if ($scope.rfiData.purpose.location)
                type.push("PHE Location: " + purpose + d4);
            if ($scope.rfiData.purpose.size)
                type.push("PHE Size: " + purpose + d5);
            if ($scope.rfiData.purpose.test)
                type.push("PHE Test Results: " + purpose + d6);
            if ($scope.rfiData.purpose.other_category)
                type.push("Other: " + purpose + d7 + " - " + $scope.rfiData.purpose.other);

            return type; //type.toString();
        }

        function getLocation() {
            var subnational = '';
            if ($scope.rfiData.default_city) {
                subnational = ' (' + $scope.rfiData.default_city + ',' + $scope.rfiData.default_state + ')';
                        } else if ($scope.rfiData.default_state) {
                            subnational = ' (' + $scope.rfiData.default_state + ')';
                                }
                                return $scope.rfiData.default_country + subnational;
                                }

                                function getPopulation() {
                                    var population = '';
                                    switch ($scope.rfiData.population.type) {
                                        case "H":
                                            population = 'Human';
                                            break;
                                        case "A":
                                            population = getAnimal();
                                            break;
                                        case "E":
                                            population = 'Environmental, ' + $scope.rfiData.population.other;
                                            break;
                                        case "U":
                                            population = 'Unknown, ' + $scope.rfiData.population.other;
                                            break;
                                        case "O":
                                            population = $scope.rfiData.population.other;
                                            break;
                                    }
                                    return population;
                                }

                                function getAnimal() {

                                    var animal = '';
                                    switch ($scope.rfiData.population.animal_type) {
                                        case "B":
                                            animal = "Birds/Poultry";
                                            break;
                                        case "P":
                                            animal = "Pigs/Swine";
                                            break;
                                        case "C":
                                            animal = "Cattle";
                                            break;
                                        case "G":
                                            animal = "Goats/Sheep";
                                            break;
                                        case "D":
                                            animal = "Dogs/Cats";
                                            break;
                                        case "H":
                                            animal = "Horses/Equines";
                                            break;
                                        case "O":
                                            animal = $scope.rfiData.population.other_animal;
                                            break;
                                        default:
                                            break;

                                    }
                                    return animal;
                                }

                                function getConditions() {

                                    var species = '';

                                    if ($scope.rfiData.population.type == "H")
                                        species = 'human';
                                    else if ($scope.rfiData.population.type == "A")
                                        species = 'animal';
                                    else if ($scope.rfiData.population.type == "E")
                                        species = 'environment';
                                    else if ($scope.rfiData.population.type == "U")
                                        species = 'unknown';


                                    var condition = [];
                                    if (species == 'human') {

                                        if ($scope.rfiData.health_condition.respiratory)
                                            condition.push("Acute Respiratory");
                                        if ($scope.rfiData.health_condition.gastrointestinal)
                                            condition.push("Gastrointestinal");
                                        if ($scope.rfiData.health_condition.fever_rash)
                                            condition.push("Fever & Rash");
                                        if ($scope.rfiData.health_condition.jaundice)
                                            condition.push("Acute Jaundice");
                                        if ($scope.rfiData.health_condition.h_fever)
                                            condition.push("Hemorrhagic Fever");
                                        if ($scope.rfiData.health_condition.paralysis)
                                            condition.push("Acute Flaccid paralysis");
                                        if ($scope.rfiData.health_condition.other_neurological)
                                            condition.push("Other neurological");
                                        if ($scope.rfiData.health_condition.fever_unknown)
                                            condition.push("Fever of unknown origin");
                                        if ($scope.rfiData.health_condition.renal)
                                            condition.push("Renal failure");
                                        if ($scope.rfiData.health_condition.unknown)
                                            condition.push("Unknown");
                                        if ($scope.rfiData.health_condition.other)
                                            condition.push($scope.rfiData.health_condition.other_description);

                                    } else if (species == 'animal') {

                                        if ($scope.rfiData.health_condition.respiratory_animal) {
                                            condition.push("Respiratory");
                                        }
                                        if ($scope.rfiData.health_condition.neurological_animal) {
                                            condition.push("Neurological");
                                        }
                                        if ($scope.rfiData.health_condition.hemorrhagic_animal) {
                                            condition.push("Haemorrhagic");
                                        }
                                        if ($scope.rfiData.health_condition.vesicular_animal) {
                                            condition.push("Vesicular");
                                        }
                                        if ($scope.rfiData.health_condition.reproductive_animal) {
                                            condition.push("Reproductive");
                                        }
                                        if ($scope.rfiData.health_condition.gastrointestinal_animal) {
                                            condition.push("Gastrointestinal");
                                        }
                                        if ($scope.rfiData.health_condition.multisystemic_animal) {
                                            condition.push("Multisystemic");
                                        }
                                        if ($scope.rfiData.health_condition.unknown_animal) {
                                            condition.push("Unknown");
                                        }
                                        if ($scope.rfiData.health_condition.other_animal) {
                                            condition.push($scope.rfiData.health_condition.other_animal_description);
                                        }

                                    } else if (species == 'environment') {
                                        condition.push($scope.rfiData.health_condition.disease_details);
                                    }
                                    return condition.toString();
                                }

                                $scope.sendRFIButtonText = "Send RFI";
                                /* Send request */
                                $scope.sendRequest2 = function (direction) {

                                    if (direction === 'next') {
                                        $scope.submitDisabled = true;
                                        $scope.sendRFIButtonText = "Please wait ....";
                                        // go to success page for testing. Remove this when done testing.
                                        //$location.path('/sent');

                                        var formData = {};
                                        if ($scope.rfiData.members.searchType == "radius") {
                                            formData['search_box'] = $scope.rfiData.members.searchBox.toString();
                                        } else {
                                            formData['search_countries'] = $scope.rfiData.members.countries.toString();
                                        }
                                        formData['uid'] = $scope.userInfo.uid; //requester of RFI
                                        formData['fetp_ids'] = $scope.rfiData.members.userIds;
                                        formData['population'] = $scope.rfiData.population;
                                        formData['health_condition'] = $scope.rfiData.health_condition;
                                        formData['health_condition_details'] = $scope.rfiData.health_condition.disease_details;
                                        formData['location'] = $scope.rfiData.location;
                                        formData['purpose'] = $scope.rfiData.purpose;
                                        formData['source'] = $scope.rfiData.source;
                                        formData['title'] = $scope.rfiData.event_title;
                                        formData['additionalText'] = $scope.rfiData.additionalText;
                                        formData['duplicate_rfi_detected'] = ($scope.rfiData.duplicate_rfi && ($scope.rfiData.duplicate_rfi.rfi_same == '3')) ? 1 : 0; // possibile duplicate RFI

                                        if (formData['duplicate_rfi_detected'] && typeof ($scope.rfiData.duplicate_rfis) != 'undefined') {
                                            var dup_events = [];
                                            $scope.rfiData.duplicate_rfis.forEach(function (event) {
                                                dup_events.push({ id: event.event_id, title: event.title });
                                            });
                                            formData['duplicate_events'] = dup_events;
                                        }


                                        //formData['duplicate_rfi_id'] = ($scope.rfiData.duplicate_rfi && $scope.rfiData.duplicate_rfi.rfi_id) ? $scope.rfiData.duplicate_rfi.rfi_id : 0;
                                        // console.log("Form data before posting to SendReq2 ---> ", formData);
                                        $http({
                                            url: urlBase + 'scripts/sendrequest2.php', method: "POST", data: formData
                                        }).success(function (respdata, status, headers, config) {
                                            console.log("Resp Data after Send Req ===> ", respdata);
                                            console.log("Status ===> ", status);
                                            // go to success page
                                            $location.path('/sent');
                                            $scope.submitDisabled = false;

                                        });

                                    } else if (direction === 'back') {
                                        $location.path('/rfi_step3');
                                    }

                                }

                                /* update request */
                                $scope.updateRequest = function (direction) {

                                    if (direction === 'next') {
                                        $scope.submitDisabled = true;

                                        // update request
                                        var formData = {};
                                        formData['event_id'] = $scope.rfiData.event_id;
                                        formData['uid'] = $scope.userInfo.uid; //requester of RFI
                                        formData['title'] = $scope.rfiData.event_title;
                                        formData['location'] = $scope.rfiData.location;
                                        formData['population'] = $scope.rfiData.population;
                                        formData['health_condition'] = $scope.rfiData.health_condition;
                                        formData['purpose'] = $scope.rfiData.purpose;
                                        formData['source'] = $scope.rfiData.source;
                                        $http({
                                            url: urlBase + 'scripts/updaterequest2.php', method: "POST", data: formData
                                        }).success(function (respdata, status, headers, config) {

                                            if (respdata['status'] == 'success') {
                                                // empty out the form values so they aren't pre-filled next time
                                                $window.sessionStorage.clear();
                                                rfiForm.clear();
                                                $location.path('/success/6');
                                            }
                                            else {
                                                console.log(respdata['reason']);
                                            }
                                        $scope.submitDisabled = false;

                                        });

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

                                /* Requester (moderator) & Responder (member) dashboard controller */
        }).controller('eventsController2', function ($scope, $window, $rootScope, $routeParams, $cookieStore, $location, $http, eventAPIservice2, urlBase, epicoreMode, epicoreVersion, Upload, $timeout) {

            $scope.mobile = (epicoreMode == 'mobile') ? true : false;
            $scope.epicore_version = epicoreVersion;
            $scope.isRouteLoading = true;
            $scope.eventsList = [];
            $scope.userInfo = $cookieStore.get('epiUserInfo');
            $scope.id = $routeParams.id ? $routeParams.id : null;
            $scope.allFETPs = $routeParams.response_id ? false : true;
            // if we're on the closed requests page
            $scope.onOpen = $location.path().indexOf("/closed") > 0 ? false : true;
            // check if public dashboard
            $scope.publicDashboard = $location.path().indexOf("/events_public") >= 0 ? true : false;
            $scope.anonymous_disabled = false;
            if (!$scope.formData) {
                $scope.formData = {};
            }
            $scope.validResponses = 0;

            $scope.eventType = "AO";

            $rootScope.dashboardType = "MR";

            $scope.cbsuffix = Date.now();

            // get list of months for selecting event month
            var dateStart = moment('2017-10-30'); // starting date of EpiCore v2.0
            var dateEnd = moment(); // now
            var timeValues = [];
            var i = 0;
            while (dateEnd > dateStart || dateStart.format('M') === dateEnd.format('M')) {
                timeValues.push({ name: dateStart.format('YYYY-MMMM'), value: dateStart.format('YYYY-MM') });
                dateStart.add(1, 'month');
                i++;
            }

            timeValues.push({ name: 'All', value: 'all' });
            timeValues.push({ name: 'Most Recent', value: 'recent' });
            $scope.event_months = timeValues.reverse();
            $scope.selected_month = timeValues[0];

            // get events for selected month
            $scope.getEventMonth = function (month) {

                var start_date = '';
                var end_date = '';
                var num_events = 'all';
                if (month.value == 'all') {
                    start_date = moment('2017-10-30').format('YYYY-MM-DD'); // starting date of EpiCore v2.0
                    end_date = moment().format('YYYY-MM-DD'); // now
                } else if (month.value == 'recent') {
                    start_date = moment().subtract(3, 'months').format('YYYY-MM-DD'); // one month ago
                    end_date = moment().format('YYYY-MM-DD'); // now
                    num_events = 10;
                } else {
                    start_date = moment(month.value + '-01').format('YYYY-MM-DD'); // selected month
                    end_date = moment(month.value + '-01').add(1, 'month').format('YYYY-MM-DD'); // next month
                }

                getAllEvents(start_date, end_date, num_events);
            };

            // upload response files
            $scope.ufiles = [];
            $scope.uploadFiles = function (files, errFiles) {
                $scope.files = files;
                $scope.errFiles = errFiles;

                var i = 1;
                angular.forEach(files, function (file) {
                    $scope.isRouteLoading = true;
                    file.upload = Upload.upload({
                        url: 'scripts/uploadfile.php',
                        data: { file: file, event_id: $scope.id, fetp_id: $scope.userInfo.fetp_id },
                        method: 'POST'
                    });

                    file.upload.then(function (response) {
                        // save uploaded file names
                        $scope.ufiles.push({ filename: response.data.filename, savefilename: response.data.savefilename });
                        // turn off spinner
                        if (i++ >= files.length) {
                            $scope.isRouteLoading = false;
                        }

                        $timeout(function () {
                            file.result = response.data;
                        });
                    }, function (response) {

                        if (response.status > 0) {
                            $scope.errorMsg = response.status + ': ' + response.data;
                        }
                    }, function (evt) {
                        file.progress = Math.min(100, parseInt(100.0 *
                                evt.loaded / evt.total));
                    });

                });
            };

            removeItem = function (file) {
                var index = $scope.ufiles.map(function (item) { return item.savefilename; }).indexOf(file);
                $scope.ufiles.splice(index, 1);
            };

            $scope.removeFile = function (file) {

                $http({
                    url: urlBase + 'scripts/removefile.php', method: "POST", data: { filename: file }
                }).success(function (respdata, status) {
                    removeItem(file);
                });

            };

            $scope.publicEvents = function (event) {
                return event.outcome === 'VP' ||
                    event.outcome === 'VN' ||
                    event.outcome === 'UP';
            };

            $scope.publicArticle = function (eventID) {
                $window.open("#/events_public/articles/" + eventID, "_self");
            }

            $scope.getPublicEventsByID = function () {
                var article_id = localStorage.getItem('articleID');
                // alert("ID ==> " + article_id);
                eventAPIservice2.getEvents(article_id).success(function (response) {
                    // console.log(response)
                    $scope.isRouteLoading = false;
                    $scope.eventsListPublic = response.EventsList;
                    var outcome = 'Pending';

                    if ($scope.eventsListPublic.outcome == 'VP') {
                        outcome = 'Verified (positive)';
                    } else if ($scope.eventsListPublic.outcome == 'VN') {
                        outcome = 'Verified (negative)';
                    } else if ($scope.eventsListPublic.outcome == 'UV') {
                        outcome = 'Unverified';
                    } else if ($scope.eventsListPublic.outcome == 'UP') {
                        outcome = 'Updated (positive)';
                    } else if ($scope.eventsListPublic.outcome == 'NU') {
                        outcome = 'Updated (negative)';
                    }


                    //console.log($scope.eventsListPublic);
                    //$scope.modifiedEventTitle = $scope.eventsListPublic.title.replace(",", "&#183;");
                    //$scope.closureDate = $scope.eventsListPublic.history[0].date;
                    $scope.cd = $scope.eventsListPublic.history[0].date;
                    $scope.closureDate = $scope.cd.split(' ')[0];
                    $scope.event_outcome = outcome;
                    //$scope.eventTitle = $scope.modifiedEventTitle
                    $scope.eventTitle = $scope.eventsListPublic.title;
                    $scope.phe_description = $scope.eventsListPublic.phe_description;
                    $scope.phe_additional = $scope.eventsListPublic.phe_additional;
                    $scope.initialSource = $scope.eventsListPublic.source + " : " + $scope.eventsListPublic.source_details;
                });
            }

            // get events for public dashboard for Responders view
            $scope.getEvents2 = function (dbtype) {
                // console.log("Scope inside GetEvents 2 ----> ", $scope)
                $scope.isRouteLoading = false;
                $rootScope.dashboardType = dbtype;
                if (dbtype == "PR" && !$scope.eventsListPublic) {
                    $scope.isRouteLoading = true;
                    var end_date = moment().format('YYYY-MM-DD'); // now
                    var start_date = moment().subtract(2, 'months').format('YYYY-MM-DD'); // 2 months ago
                    eventAPIservice2.getEvents($scope.id, start_date, end_date).success(function (response) {
                        // console.log("Success Function output getEvents2 -> ", response)
                        $scope.isRouteLoading = false;
                        $scope.eventsListPublic = response.EventsList;
                        if ($scope.eventsListPublic.purpose) {
                            $scope.outcomePublic = {};
                            $scope.outcomePublic.phe_purpose = 'N';
                            if ($scope.eventsListPublic.purpose.indexOf("Verification") >= 0) {
                                $scope.outcomePublic.phe_purpose = 'V';
                            } else if ($scope.eventsListPublic.purpose.indexOf("Update") >= 0) {
                                $scope.outcomePublic.phe_purpose = 'U';
                            }
                            $scope.summaryPublic = {};
                            $scope.summaryPublic.phe_title = $scope.eventsListPublic.title;
                        }
                    });
                }

            };

            getAllEvents = function (start_date, end_date, num_events = 'all') {

                $scope.isRouteLoading = true;

                $scope.eventsList = [];
                eventAPIservice2.getEvents($scope.id, start_date, end_date).success(function (response) {
                    $scope.isRouteLoading = false;
                    console.log("Response output -> ", response)
                    if (typeof ($scope.userinfo) != "undefined") {
                        $scope.isOrganization = $scope.userInfo.fetp_id > 0 ? false : true;
                        // if RFI requester is the logged in user or of same org, they get different action items
                        if (response.EventsList != null) {

                            //$scope.isAuthorizedToFollowup = $scope.userInfo.organization_id == response.EventsList.org_requester_id ? true : false;
                            $scope.isAuthorizedToFollowup = (($scope.userInfo.organization_id == response.EventsList.org_requester_id) || ($scope.userInfo.superuser)) ? true : false;
                            $scope.changeStatusText = response.EventsList.estatus == "C" ? 'Re open' : 'Close';
                            $scope.changeStatusType = response.EventsList.estatus == "C" ? 'reopen' : 'close';
                            $scope.isAuthorizedFETP = false;
                            $scope.isRequester = response.EventsList.requester_id == $scope.userInfo.uid ? true : false;
                            if (response.EventsList.fetp_ids != null && response.EventsList.fetp_ids.indexOf($scope.userInfo.fetp_id) != -1) {
                                $scope.isAuthorizedFETP = true;
                            }
                            if (response.EventsList.fetp_ids) {
                                $scope.num_fetp = response.EventsList.fetp_ids.length;
                            }

                            $scope.eventsList = response.EventsList;
                            $scope.filePreview = response.EventsList.filePreview ? response.EventsList.filePreview : '';

                            if (num_events == 'all') {
                                $scope.eventsList.all = response.EventsList.all;
                                $scope.eventsList.other = response.EventsList.other;

                            } else {
                                $scope.eventsList.all = response.EventsList.all.slice(0, num_events);
                                $scope.eventsList.other = response.EventsList.other.slice(0, num_events);
                            }

                            if ($scope.eventsList.purpose) {
                                $scope.outcome = {};
                                $scope.outcome.phe_purpose = 'N';
                                if ($scope.eventsList.purpose.indexOf("Verification") >= 0) {
                                    $scope.outcome.phe_purpose = 'V';
                                } else if ($scope.eventsList.purpose.indexOf("Update") >= 0) {
                                    $scope.outcome.phe_purpose = 'U';
                                }
                                $scope.summary = {};
                                $scope.summary.phe_title = $scope.eventsList.title;
                                $scope.summary.phe_description = $scope.eventsList.phe_description;
                                $scope.summary.phe_additional = $scope.eventsList.phe_additional;
                                $scope.summary.outcome = $scope.eventsList.outcome;

                            }
                        }
                        //////// public events
                    } else if (typeof ($scope.userinfo) == "undefined") {
                        $scope.eventsList = response.EventsList;

                        if (num_events != 'all') {

                            var all_events = response.EventsList.all;
                            var public_events = [];
                            all_events.forEach(function (event) {
                                if ($scope.publicEvents(event)) {
                                    public_events.push(event);
                                }
                            })
                            $scope.eventsList.all = public_events.splice(0, num_events);
                        }

                        if ($scope.eventsList.purpose) {
                            $scope.outcome = {};
                            $scope.outcome.phe_purpose = 'N';
                            if ($scope.eventsList.purpose.indexOf("Verification") >= 0) {
                                $scope.outcome.phe_purpose = 'V';
                            } else if ($scope.eventsList.purpose.indexOf("Update") >= 0) {
                                $scope.outcome.phe_purpose = 'U';
                            }
                            $scope.summary = {};
                            $scope.summary.phe_title = $scope.eventsList.title;
                        }
                    }

                // today's date
                var today = new Date();
                var dd = today.getDate();
                var mm = today.getMonth(); //January is 0!
                var yyyy = today.getFullYear();
                var month = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                $scope.today_date = dd + '-' + month[mm] + '-' + yyyy;

                // get response
                $scope.response_text = '';
                if ($routeParams.response_id) {
                    var formData = {};
                    formData['uid'] = $scope.userInfo.uid;
                    formData['org_id'] = $scope.userInfo.organization_id;
                    formData['fetp_id'] = $scope.userInfo.fetp_id;
                    formData['response_id'] = $routeParams.response_id;
                    $http({
                        url: urlBase + 'scripts/getresponse.php', method: "POST", data: formData
                    }).success(function (respdata, status, headers, config) {
                        $scope.response_text = respdata['response'];
                        $scope.responder_id = respdata['responder_id'];
                        $scope.permission_id = respdata['response_permission_id'];
                    });
                }

                // count unrated responses in closed events
                if ($scope.onOpen) {
                    // console.log("Response output ====> ", response)
                    $scope.listofEventIdsToDisplay = response.numNotRatedResponses[1][0];
                    // console.log("scope --====> ", $scope)
                    $scope.num_notrated_responses = response.numNotRatedResponses[0];
                } else if ($scope.eventsList) {
                    for (var n in $scope.eventsList.yours) {
                        $scope.num_notrated_responses += parseInt($scope.eventsList.yours[n].num_notrated_responses);
                    }
                }

                // count responses with content
                for (var h in $scope.eventsList.history) {
                    if (($scope.eventsList.history[h].permission !== '0') && ($scope.eventsList.history[h].type == 'Member Response')
                            && ($scope.userInfo.uid)) {
                                $scope.validResponses++;
                            }
                }

                // check for active search
                $scope.activeSearch = false;
                for (var h in $scope.eventsList.history) {
                    if (($scope.eventsList.history[h].permission == '4') && ($scope.eventsList.history[h].type == 'Member Response')
                            && ($scope.eventsList.history[h].fetp_id == $scope.userInfo.fetp_id)) {
                                $scope.activeSearch = true;
                            }
                }

                // check unclosed RFIs with no activity in the last two weeks
                Date.prototype.yyyymmdd = function () {
                    var yyyy = this.getFullYear().toString();
                    var mm = (this.getMonth() + 1).toString(); // getMonth() is zero-based
                    var dd = this.getDate().toString();
                    return yyyy + "-" + (mm[1] ? mm : "0" + mm[0]) + "-" + (dd[1] ? dd : "0" + dd[0]); // padding
                };
                var d = new Date();
                $scope.date = d.setDate(d.getDate() - 14); // now minus 14 days
                $scope.unclosed = 0;
                for (var n in $scope.eventsList.yours) {
                    newdate = $scope.eventsList.yours[n].num_followups[0].iso_date;
                    if (newdate < d.yyyymmdd()) {
                        $scope.unclosed++;
                    }
                }
                $scope.isRouteLoading = false;

                });

            }

            // get most recent events for public dashboard
            // get all events on load for open events
            // get events for current month for closed events
            if ($scope.publicDashboard) {
                var end_date = moment().format('YYYY-MM-DD'); // now
                var start_date = moment().subtract(3, 'months').format('YYYY-MM-DD'); // 3 month ago
                getAllEvents(start_date, end_date, 10);
            } else if ($scope.onOpen) {
                getAllEvents('2017-10-30', moment().add(1, 'days').format('YYYY-MM-DD'));
            } else {
                end_date = moment().format('YYYY-MM-DD'); // now
                start_date = moment().subtract(1, 'months').format('YYYY-MM-DD'); // one month ago
                getAllEvents(start_date, end_date, 10);
            }

            $scope.displaySavingText = false;

            if(!($scope.newMetricsId)){
                $scope.newMetricsId = 0;
            } 

            $scope.updateRFIMetrics = function (event,field_to_update) {
                
                // console.log("Event incoming -> ", event);

                if(event.event_metrics_id){
                    $scope.newMetricsId = event.event_metrics_id;
                }
                // console.log("New Metrics event ID ====> ", $scope.newMetricsId);

                var currentFieldValue = eval("event." + field_to_update);

                //Avoid DB call if nothing is entered in the fields
                if(!(currentFieldValue) || currentFieldValue == '' || currentFieldValue == undefined){
                    return
                }

                var metric_data = {
                    event_metrics_id: $scope.newMetricsId,
                    score: event.metric_score,
                    creation: event.metric_creation,
                    notes: event.metric_notes,
                    action: event.metric_action,
                    event_id: event.event_id
                };

                if(event.metric_score != '' || event.metric_creation != '' || event.metric_notes != '' || event.metric_action != ''){
                    $scope.displaySavingText = true;
                    $http({
                        url: urlBase + 'scripts/updatemetrics.php', method: "POST", data: metric_data
                    }).success(function (data, status, headers, config) {
                        // console.log("Success after updating Metrics Data -> ", data.tableID);
                        $scope.displaySavingText = false;
                        $scope.newMetricsId = data.tableID;
                    }).error(function (data, status, headers, config) {
                        // console.log(status);
                        $scope.displaySavingText = false;
                    });
                }
                
            };

            $scope.sendFollowup = function (formData, isValid) {
                if (isValid) {
                    $scope.submitDisabled = true;
                    formData['uid'] = $scope.userInfo.uid;
                    formData['event_id'] = $routeParams.id;
                    formData['superuser'] = $scope.userInfo.superuser ? 1 : 0;
                    if ($routeParams.id) {
                        var eid = $routeParams.id;
                    }
                    if ($routeParams.response_id) {
                        formData['response_id'] = $routeParams.response_id;
                    }
                    $http({
                        url: urlBase + 'scripts/sendfollowup2.php', method: "POST", data: formData
                    }).success(function (data, status, headers, config) {
                        $scope.submitDisabled = false;
                        $location.path('/success/3/' + eid);
                    });
                }
            }

            $scope.changeRequestStatus = function (formData, thestatus, isValid) {
                // count responses assessed as useful,used in promed, or not useful when closing an RFI
                // only for responses with content
                var useful_rids = [];
                var usefulpromed_rids = [];
                var notuseful_rids = [];

                if (isValid && (thestatus == 'Close' || thestatus == 'Update') && ($scope.validResponses > 0)) {
                    for (var h in $scope.eventsList.history) {
                        var h_rid = $scope.eventsList.history[h].response_id;
                        var h_type = $scope.eventsList.history[h].type;
                        var h_fetp_id = $scope.eventsList.history[h].fetp_id;
                        var h_orgid = $scope.eventsList.history[h].organization_id;
                        var h_useful = $scope.eventsList.history[h].useful;
                        var h_perm = $scope.eventsList.history[h].permission;
                        if ((h_type == 'Member Response' && h_perm !== '0' && h_perm !== '4')
                                && ($scope.userInfo.uid || (h_fetp_id == $scope.userInfo.fetp_id)) && ((h_orgid == $scope.userInfo.organization_id) || $scope.userInfo.superuser)) {
                                    if (h_useful === null) {
                                        alert('Please assess all member responses.');
                                        $scope.close_message = 'Please assess all member responses.';
                                        return false;
                                    } else if (h_useful === '1') {
                                        useful_rids.push(h_rid);   // save useful response_ids
                                    } else if (h_useful === '2') {
                                        usefulpromed_rids.push(h_rid);   // save useful promed response_ids
                                    } else {
                                        notuseful_rids.push(h_rid);   // save not useful response_ids
                                    }
                                }
                    }
                }
                if (isValid) {
                    $scope.submitDisabled = true;
                    formData['event_id'] = $routeParams.id;
                    formData['uid'] = $scope.userInfo.uid;
                    formData['superuser'] = $scope.userInfo.superuser;
                    formData['thestatus'] = thestatus;
                    formData['useful_rids'] = useful_rids.toString();
                    formData['usefulpromed_rids'] = usefulpromed_rids.toString();
                    formData['notuseful_rids'] = notuseful_rids.toString();

                    if (thestatus == 'Close') {
                        formData['phe_outcome'] = $scope.outcome.answer;
                        formData['phe_title'] = $scope.summary.phe_title;
                        formData['phe_description'] = $scope.summary.phe_description;
                        formData['phe_additional'] = $scope.summary.phe_additional;
                    } else if (thestatus == 'Summary') {
                        formData['phe_outcome'] = $scope.summary.outcome;
                        formData['phe_title'] = $scope.summary.phe_title;
                        formData['phe_description'] = $scope.summary.phe_description;
                        formData['phe_additional'] = $scope.summary.phe_additional;
                    }

                    formData['condition_details'] = $scope.eventsList.condition_details;

                    $http({
                        url: urlBase + 'scripts/changestatus2.php', method: "POST", data: formData
                    }).success(function (data, status, headers, config) {
                        if (data['status'] == 'success') {
                            $scope.submitDisabled = false;
                            var pathid = 4;
                            if (thestatus == "Update") {
                                pathid = 8;
                            } else if (thestatus == "Reopen") {
                                pathid = 5;
                            } else if (thestatus == "Summary") {
                                pathid = 9;
                            }
                            else { // closed
                                pathid = 4;
                            }
                    $location.path('/success/' + pathid);
                        } else {
                            console.log(data['reason']);
                            alert(data['reason']);
                        }
                    });
                }
            };

            $scope.sendResponse = function (formData, isValid) {

                var source_valid = typeof (formData.source) != "undefined";

                $scope.response_error_message = '';
                if ((formData['response_permission'] == 0) || (formData['response_permission'] == 4) || (isValid && source_valid)) {
                    $scope.submitDisabled = true;
                    // if user has chosen "I have nothing to contribute" button,
                    // formData comes in as object response_permissions: 0
                    // if user chooses "Active Search", object is response_permissions: 4
                    formData['event_id'] = $routeParams.id
                        formData['fetp_id'] = $scope.userInfo.fetp_id;
                    if ($routeParams.id) {
                        var eid = $routeParams.id;
                    }
                    formData['files'] = $scope.ufiles;
                    $http({
                        url: urlBase + 'scripts/sendresponse2.php', method: "POST", data: formData
                    }).success(function (data, status, headers, config) {
                        if (data['status'] == 'success') {
                            $location.path('/success/2/' + eid);
                        } else {
                            alert('response failed!');
                            console.log('invalid event id.')
                        }
                        $scope.submitDisabled = false;
                    });
                } else {
                    if (isValid && !source_valid) {
                        $scope.response_error_message = "missing verification sources";
                    }
                }
            };

            $scope.deleteEvent = function (eid) {
                if (confirm('Are you sure you want to delete this event?')) {
                    data = { eid: eid, superuser: $scope.userInfo.superuser };
                    $http({
                        url: urlBase + 'scripts/deleteEvent2.php', method: "POST", data: data
                    }).success(function (data, status, headers, config) {
                        if (data['status'] == 'success') {
                            $location.path('/success/7');
                        }
                        else {
                            alert(data['reason']);
                            console.log(data['reason']);
                        }
                    }).error(function (data, status, headers, config) {
                        console.log(status);
                    });
                }
            };

            // Show summary modal
            $scope.showModal = false;
            $scope.modalTitle = "";
            $scope.modalBody = "";
            $scope.showSummary = function (summary, more_info, event_title, event_source, event_source_details, event_outcome, event_action_date) {

                var source = '';
                if (event_source == 'MR') {
                    source = "Media Report";
                } else if (event_source == 'OR') {
                    source = "Official Report";
                } else if (event_source == 'OC') {
                    source = "Other communication";
                }

                var outcome = 'Pending';
                if (event_outcome == 'VP') {
                    outcome = 'Verified (positive)';
                } else if (event_outcome == 'VN') {
                    outcome = 'Verified (negative)';
                } else if (event_outcome == 'UV') {
                    outcome = 'Unverified';
                } else if (event_outcome == 'UP') {
                    outcome = 'Updated (positive)';
                } else if (event_outcome == 'NU') {
                    outcome = 'Updated (negative)';
                }


                var event_info = "Title: " + event_title + "\r\n\r\n" + "Initial Source: " + source + ":" + event_source_details + "\r\n\r\n" + "RFI Outcome: " + outcome + "\r\n\r\n";

                $scope.modalTitle = "Summary";
                $scope.modalBody = '';
                if (more_info)
                    $scope.modalBody = event_info + "RFI Closure Date: " + event_action_date + "\r\n\r\n" + "PHE Description:\r\n" + summary + "\r\n\r\n" + "Additional Info:\r\n" + more_info;
                else if (summary)
                    $scope.modalBody = event_info + "RFI Closure Date: " + event_action_date + "\r\n\r\n" + "PHE Description:\r\n" + summary;

                $scope.showModal = !$scope.showModal;
            };

        }).controller('metricsController', function ($scope) {

            $scope.date_now = Date.now();
            var today = new Date();
            $scope.year = today.getFullYear();
            var last_month_num = today.getMonth() - 1;
            if (today.getMonth() == 0) {
                last_month_num = 11;
                $scope.year = $scope.year - 1;
            }

            var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            $scope.month = months[last_month_num];
            /*
               Following Controllers are added by Sam, CH157135.
               Usage: A call for openPortfolioURL is placed in events_public.html which brings in the data requried to display in the summary page
               Once the data is available, we pass in the datato the next page (publicrfi.html) with publicRFIChildController as child controller
             */
        }).controller('publicRFIController', function ($scope, $window) {

            $scope.articleTitle = "";
            $scope.articleBody = "";
            $scope.openPortfolioURL = function (eventID, summary, more_info, event_title, event_source, event_source_details, event_outcome, event_action_date) {


                var source = '';
                if (event_source == 'MR') {
                    source = "Media Report";
                } else if (event_source == 'OR') {
                    source = "Official Report";
                } else if (event_source == 'OC') {
                    source = "Other communication";
                }

                var outcome = 'Pending';
                if (event_outcome == 'VP') {
                    outcome = 'Verified (positive)';
                } else if (event_outcome == 'VN') {
                    outcome = 'Verified (negative)';
                } else if (event_outcome == 'UV') {
                    outcome = 'Unverified';
                } else if (event_outcome == 'UP') {
                    outcome = 'Updated (positive)';
                } else if (event_outcome == 'NU') {
                    outcome = 'Updated (negative)';
                }

                $scope.eventTitle = event_title;
                $scope.initialSource = source;
                $scope.articleSourceDetails = event_source_details;
                $scope.rfiOutcome = outcome;
                $scope.eventInfo = event_info;
                $scope.closureDate = event_action_date;

                var event_info = "Title: " + event_title + "\r\n\r\n" + "Initial Source: " + source + ":" + event_source_details + "\r\n\r\n" + "RFI Outcome: " + outcome + "\r\n\r\n";

                $scope.articleTitle = "Summary";
                $scope.articleBody = '';
                $scope.additionalInfo = more_info;
                if (more_info)
                    $scope.articleBody = "PHE Description:\r\n" + summary;
                else if (summary)
                    $scope.articleBody = "PHE Description:\r\n" + summary;

                var $newArticleWindow = $window.open("#/events_public/articles/" + eventID, "_self");

                $newArticleWindow.eventTitle = $scope.eventTitle;
                $newArticleWindow.initialSource = $scope.initialSource;
                $newArticleWindow.articleSourceDetails = $scope.articleSourceDetails;
                $newArticleWindow.rfiOutcome = $scope.rfiOutcome;
                $newArticleWindow.eventInfo = $scope.eventInfo;
                $newArticleWindow.closureDate = $scope.closureDate;
                $newArticleWindow.articleOutput = $scope.articleBody;

                localStorage.clear();

                localStorage.setItem('local_eventTitle', $scope.eventTitle);
                localStorage.setItem('local_initialSource', $scope.initialSource);
                localStorage.setItem('local_articleSourceDetails', $scope.articleSourceDetails);
                localStorage.setItem('local_rfiOutcome', $scope.rfiOutcome);
                localStorage.setItem('local_eventInfo', $scope.eventInfo);
                localStorage.setItem('local_closureDate', $scope.closureDate);
                localStorage.setItem('local_articleOutput', $scope.articleBody);
                localStorage.setItem('local_additionalInfo', $scope.additionalInfo);
            }
        }).controller('publicRFIChildController', function ($scope, $window) {
            $scope.articleOutput = $window.articleOutput;

            $scope.eventTitle = $window.eventTitle;
            $scope.initialSource = $window.initialSource;
            $scope.articleSourceDetails = $window.articleSourceDetails;
            $scope.rfiOutcome = $window.rfiOutcome;
            $scope.eventInfo = $window.eventInfo;
            $scope.closureDate = $window.closureDate;
        });
