angular.module('EpicoreApp.controllers2', []).

/* Request (RFI): this is the controller that sends an RFI. */
controller('requestController2', function($rootScope, $window, $scope, $routeParams, $cookieStore, $location, $http, urlBase, rfiForm, epicoreVersion) {

    $scope.userInfo = $rootScope.userInfo = $cookieStore.get('epiUserInfo');

    $scope.epicore_version = epicoreVersion;

    // get persistant RFI form
    $scope.rfiData = rfiForm.get();

    // get event from database if event id is passed in and populate forM
    // this is used to edit an RFI (not send a new RFI)
    if ($routeParams.id) {
        // save event id
        $scope.rfiData.event_id = $routeParams.id;

        // get event from database
        var eventData = {};
        eventData['event_id'] = $scope.rfiData.event_id;
        $http({ url: urlBase + 'scripts/getrequest2.php', method: "POST", data: eventData
        }).success(function (data, status, headers, config) {

            //console.log(data);

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

            $scope.rfiData.population = data.population;
            $scope.rfiData.purpose = data.purpose;
            $scope.rfiData.health_condition = data.health_condition;
            $scope.rfiData.source = data.source;
        });

    }

    /////////////////////////////////////////////// Location ////////////////////////////////
    $scope.location_error_message = '';
    $scope.saveLocation = function (direction) {

        // jquery hack to get the latlon hidden value and autocomplete for location (angular bug)
        $scope.rfiData.location.latlon = $("#default_location").val();
        $scope.rfiData.location.location = $("#searchTextField").val(); // format: "country" or "state, country" or "city, state, country"
        //$scope.rfiData.default_city = $("#default_city").val();
        //$scope.rfiData.default_state = $("#default_state").val();
        //$scope.rfiData.default_country = $("#default_country").val();

        // get city, state, country from location string
        var mylocation = $scope.rfiData.location.location.split(",");
        if (mylocation.length == 3){
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


        if(!$scope.rfiData.location.latlon) {
            $scope.rfiData.location.location_error_message = 'Geolocation failed - please scroll down and select a location from the auto-suggester in the location field so that we have the coordinates of the event.';
            $scope.rfiData.location.location = '';
            return false;
        }

        // validate and go to next or back path
        if ($scope.rfiData.location.latlon && $scope.rfiData.location.location){

            // next or back
            if ((direction === 'next') && $scope.rfiData.event_id) {
                $location.path('/time'); // go to Time form if event id is passed in
            } else if ((direction === 'next') && !$scope.rfiData.event_id) {

                // Get filtered members at initial location or if the location has changed
                if((typeof ($scope.rfiData.members) == 'undefined') || ($scope.rfiData.members.location != $scope.rfiData.location.location)) {

                    $scope.rfiData.members = {};
                    $scope.rfiData.members.userIds = [];
                    $scope.rfiData.members.numFetps = 0;
                    $scope.rfiData.members.numUniqueFetps = 0;
                    $scope.rfiData.members.searchBox = [];
                    $scope.rfiData.members.searchType = 'none';
                    // save location
                    $scope.rfiData.members.location = $scope.rfiData.location.location;
                    $scope.rfiData.members.latlon = $scope.rfiData.location.latlon;

                    // get filtered members at chosen location with default radius
                    fdata = {};
                    fdata.location = $scope.rfiData.members.location;
                    fdata.latlon = $scope.rfiData.members.latlon;
                    $http({ url: urlBase + 'scripts/filter.php', method: "POST", data: fdata
                    }).success(function (data, status, headers, config) {
                        $scope.rfiData.members.userIds = data['userIds'];
                        $scope.rfiData.members.numFetps = data['userList']['sending'];
                        $scope.rfiData.members.numUniqueFetps = data['uniqueList']['sending'];
                        $scope.rfiData.members.searchBox = data['bbox'];
                        $scope.rfiData.members.searchType = 'radius';
                        $location.path('/members');
                    }).error(function (data, status, headers, config) {
                        console.log(status);
                    });
                } else {
                    $location.path('/members');
                }
            }
            $scope.location_error_message = '';

        } else {
            $scope.location_error_message = 'Missing parameters above.';
        }
    };

    ////////////////////////////////////////////// Members ////////////////////////////////////////

    /* select members  */
    if($location.path() == "/members") {

        // initialize default radio buttons - radius select checked by default
        $scope.radiussel = $scope.rfiData.members.searchType != "country";

        // bounding box around event location
        // show/hide the submit to next step only if there are FETPs to receive the email
        $scope.submitDisabled = $scope.rfiData.members.numFetps <= 0;

        $scope.bbox = $scope.rfiData.members.searchBox;
        var bounds = new google.maps.LatLngBounds(new google.maps.LatLng($scope.bbox[0], $scope.bbox[2]), new google.maps.LatLng($scope.bbox[1], $scope.bbox[3]));
        $scope.rectangle = {bounds: bounds, stroke: { color: '#08B21F', weight: 2, opacity: 1 }, fill: { color: '#08B21F', opacity: 0.5 }, editable: true, visible: true };
        var latlonarr = $scope.rfiData.members.latlon.split(",");

        /* get member markers for the map */
        $scope.map = { center: { latitude: latlonarr[0], longitude: latlonarr[1] }, zoom: 5 }
        $scope.options = {scrollwheel: false};
        /* only show FETPs on a map to super-users */
        var query = {};
        query['uid'] = $scope.userInfo.uid;
        query['centerlat'] = latlonarr[0];
        query['centerlon'] = latlonarr[1];
        $http({ url: urlBase + 'scripts/getmarkers.php', method: "POST", data: query
        }).success(function (data, status, headers, config) {
            if(data['status'] == "success") {
                $scope.markers = data['markers'];
            }
        });

        /* rectangle change event */
        $scope.eventsRectangle = {
            bounds_changed: function(rectangle) {
                var filterData = {};
                var southwest = rectangle.bounds.getSouthWest();
                var northeast = rectangle.bounds.getNorthEast();
                $scope.radiussel = true; // if radius changes without changing radio button
                $scope.rfiData.members.searchType = 'radius';
                filterData['bbox'] = new Array(southwest.lat(), northeast.lat(), southwest.lng(), northeast.lng());
                $http({ url: urlBase + 'scripts/filter.php', method: "POST", data: filterData
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
    $scope.recalcUsers = function(whichclicked) {
        $scope.rfiData.members.searchType = whichclicked;
        $scope.rfiData.members.filtertype = whichclicked;
        $scope.radiussel = whichclicked != "country";
        $http({ url: urlBase + 'scripts/filter.php', method: "POST", data: $scope.rfiData.members //filterData
        }).success(function (filtereddata, status, headers, config) {
            $scope.rfiData.members.userIds = filtereddata['userIds'];
            $scope.rfiData.members.numFetps = filtereddata['userList']['sending'];
            $scope.rfiData.members.numUniqueFetps = filtereddata['uniqueList']['sending'];
            $scope.submitDisabled = $scope.rfiData.members.numFetps <= 0;
            if(filtereddata['bbox']) {
                $scope.rfiData.members.searchBox = filtereddata['bbox'];
            }
        });
    };

    /* go next or back */
    $scope.saveMembers = function (direction) {

        // next or back
        if (direction === 'next') {
            $location.path('/time');
        } else if (direction === 'back'){
            $location.path('/location');
        }
    };

    ////////////////////////// Time //////////////////////////////////////////////
    // datepicker options
    $("#datepicker").datepicker({
        format: "dd-MM-yyyy",
        endDate : 'now'
    });

    $scope.time_error_message = '';
    $scope.saveTime = function (direction) {

        // validate and go to next or back path
        if ($scope.rfiData.location.event_date){

            // next or back
            if (direction === 'next') {
                $location.path('/population');
            } else if ((direction === 'back') && !$scope.rfiData.event_id){
                    $location.path('/members');
            } else if ((direction === 'back') && $scope.rfiData.event_id){
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

        // validation
        var valid_other_animal = ($scope.rfiData.population.animal_type != 'O') || $scope.rfiData.population.other_animal;
        var valid_animal = ($scope.rfiData.population.type != 'A') || ($scope.rfiData.population.animal_type && valid_other_animal);
        var valid_other = ($scope.rfiData.population.type != 'O') || $scope.rfiData.population.other;
        var valid_population = $scope.rfiData.population.type && valid_other;

        if (valid_population && valid_animal){

            if (direction === 'next') {
                $location.path('/condition');
            } else if ((direction === 'back') && !$scope.rfiData.event_id){
                $location.path('/members');
            } else if ((direction === 'back') && $scope.rfiData.event_id){
                $location.path('/time');
            }
            $scope.pop_error_message = '';
        } else {
            $scope.pop_error_message = 'Missing parameters above.';
        }
    };

    //////////////////////////////////////// Health Condition ////////////////////////////////
    $scope.hc_error_message = '';
    $scope.hc_error_message1 = '';
    $scope.saveCondition = function (direction) {

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

        if (health_condition_ok && $scope.rfiData.health_condition.disease_details ) { // validation

            // next or back
            if (direction === 'next') {
                $location.path('/purpose');
            } else if (direction === 'back'){
                $location.path('/population');
            }
            // clear errors
            $scope.hc_error_message = '';
            $scope.hc_error_message1 = '';
        } else if (!health_condition_ok){
            $scope.hc_error_message1 =  (valid_other && valid_other_animal) ? 'Must select one or more of the above options ' : '' ;
            $scope.hc_error_message = 'Missing parameters above.';
        } else {
            $scope.hc_error_message = 'Missing parameters above.';
            $scope.hc_error_message1 = '';
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


    ////////////////////////////////////////////// Purpose ////////////////////////////////////////
    $scope.purpose_error_message = '';
    $scope.purpose_error_message1 = '';
    $scope.savePurpose = function (direction) {

        // checkbox validation
        var valid_purpose = $scope.rfiData.purpose.causal_agent || $scope.rfiData.purpose.epidemiology || $scope.rfiData.purpose.pop_affected || $scope.rfiData.purpose.location
            || $scope.rfiData.purpose.size || $scope.rfiData.purpose.test || $scope.rfiData.purpose.other_category;
        var valid_other_purpose = !$scope.rfiData.purpose.other_category || $scope.rfiData.purpose.other;

        // next
        if (valid_purpose && valid_other_purpose && $scope.rfiData.purpose.purpose) { // validation
            if (direction === 'next') {
                $location.path('/source');
            } else if (direction === 'back'){
                $location.path('/condition');
            }

            $scope.purpose_error_message = '';
            $scope.purpose_error_message1 = '';
        } else if (!valid_other_purpose) {
            $scope.purpose_error_message = 'Missing parameters above.';
            $scope.purpose_error_message1 = '';
        }
        else if (!valid_purpose){
                $scope.purpose_error_message = 'Missing parameters above.';
                $scope.purpose_error_message1 = 'Must select one or more of the above options.';
        } else {
            $scope.purpose_error_message = 'Missing parameters above.';
            $scope.purpose_error_message1 = '';
        }
    };

    ///////////////////////////////////////////// Source /////////////////////////////////////////
    $scope.source_error_message = '';
    $scope.saveSource = function (direction) {

        if ($scope.rfiData.source.source && $scope.rfiData.source.details){
            if (direction === 'next') {

                // save RFI details for review
                $scope.rfiData.event_location = getLocation();
                $scope.rfiData.event_population = getPopulation();
                $scope.rfiData.event_conditions = getConditions();
                $scope.rfiData.event_title = $scope.rfiData.event_population + ', ' + $scope.rfiData.event_conditions + ' - ' + $scope.rfiData.event_location + $scope.rfiData.location.event_date;
                $scope.rfiData.event_purpose = getPurpose();
                $scope.rfiData.event_source = getSource();
                $location.path('/sendrequest');

                // build and review request email - not used for now
                //buildEmailText();

            } else if (direction === 'back'){
                $location.path('/purpose');
            }
            $scope.source_error_message = '';
        } else {
            $scope.source_error_message = 'Missing parameters above.';
        }

    };

    // buildEmailText
    $scope.filePreview = $window.sessionStorage.filePreview;
    buildEmailText = function() {

        var formData = {};
        formData['title'] =  $scope.rfiData.event_title;
        // overwrite the old file preview if it exists
        if(typeof($window.sessionStorage.filePreview) != "undefined") {
            formData['file_preview'] = $window.sessionStorage.filePreview;
        }
        $http({ url: urlBase + 'scripts/buildrequest2.php', method: "POST", data: formData
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

        return type.toString();
    }

    function getLocation() {
        var subnational = '';
        if ($scope.rfiData.default_city){
            subnational = $scope.rfiData.default_city + ',' +$scope.rfiData.default_state;
        } else if ($scope.rfiData.default_state) {
            subnational = $scope.rfiData.default_state;
        }
        return $scope.rfiData.default_country + ' (' + subnational + '), ';
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
                population = 'Environmental';
                break;
            case "U":
                population = 'Unknown';
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
        else if ($scope.rfiData.population.type =="A")
            species = 'animal';

        var condition = [];
        if (species == 'human'){

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
                condition.push("Other neorological");
            if ($scope.rfiData.health_condition.fever_unknown)
                condition.push("Fever of uknown origin");
            if ($scope.rfiData.health_condition.renal)
                condition.push("Renal failure");
            if ($scope.rfiData.health_condition.unknown)
                condition.push("Unknown");
            if ($scope.rfiData.health_condition.other)
                condition.push($scope.rfiData.health_condition.other_description);

        } else if (species == 'animal'){

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

        } else {
            condition.push($scope.rfiData.health_condition.disease_details);
        }
        return condition.toString();
    }

    /* Send request */
    $scope.sendRequest2 = function (direction) {

        if (direction === 'next') {
            $scope.submitDisabled = true;

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
            formData['location'] = $scope.rfiData.location;
            formData['purpose'] = $scope.rfiData.purpose;
            formData['source'] = $scope.rfiData.source;
            formData['title'] =  $scope.rfiData.event_title;

            $http({
                url: urlBase + 'scripts/sendrequest2.php', method: "POST", data: formData
            }).success(function (respdata, status, headers, config) {

                // go to success page
                $location.path('/sent');
                $scope.submitDisabled = false;

            });

        } else if ( direction === 'back'){
            $location.path('/source');
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
            formData['title'] =  $scope.rfiData.event_title;
            formData['location'] = $scope.rfiData.location;
            formData['population'] = $scope.rfiData.population;
            formData['health_condition'] = $scope.rfiData.health_condition;
            formData['purpose'] = $scope.rfiData.purpose;
            formData['source'] = $scope.rfiData.source;
            $http({
             url: urlBase + 'scripts/updaterequest2.php', method: "POST", data: formData
             }).success(function (respdata, status, headers, config) {

                 if (respdata['status'] == 'success'){
                     // empty out the form values so they aren't pre-filled next time
                     $window.sessionStorage.clear();
                     rfiForm.clear();
                     $location.path('/success/6');
                 }
                 else{
                     console.log(respdata['reason']);
                 }
                 $scope.submitDisabled = false;

             });

        } else if ( direction === 'back'){
            $location.path('/source');
        }

    };

    /* Sent request */
    $scope.sentRFI = function () {

        // empty out the form values so they aren't pre-filled next time
        $window.sessionStorage.clear();
        rfiForm.clear();
        $location.path('/events2');
    }

    /* Requester (moderator) & Responder (member) dashboard controller */
}).controller('eventsController2', function($scope, $routeParams, $cookieStore, $location, $http, eventAPIservice2, urlBase, epicoreMode, epicoreVersion) {

    $scope.mobile = (epicoreMode == 'mobile') ? true: false;
    $scope.epicore_version = epicoreVersion;
    $scope.isRouteLoading = true;
    $scope.eventsList = [];
    $scope.userInfo = $cookieStore.get('epiUserInfo');
    $scope.id = $routeParams.id ? $routeParams.id : null;
    $scope.allFETPs = $routeParams.response_id ? false : true;
    // if we're on the closed requests page
    $scope.onOpen = $location.path().indexOf("/closed") > 0 ? false : true;
    $scope.anonymous_disabled = false;
    if(!$scope.formData) {
        $scope.formData = {};
    }
    $scope.validResponses = 0;

    $scope.eventType = "MR";

    eventAPIservice2.getEvents($scope.id).success(function (response) {
        $scope.isOrganization = $scope.userInfo.fetp_id > 0 ? false : true;
        // if RFI requester is the logged in user or of same org, they get different action items
        if(response.EventsList != null) {
            $scope.isAuthorizedToFollowup = $scope.userInfo.organization_id == response.EventsList.org_requester_id ? true : false;
            $scope.changeStatusText = response.EventsList.estatus == "C" ? 'Re open' : 'Close';
            $scope.changeStatusType = response.EventsList.estatus == "C" ? 'reopen' : 'close';
            $scope.isAuthorizedFETP = false;
            $scope.isRequester = response.EventsList.requester_id == $scope.userInfo.uid ? true:false;
            if (response.EventsList.fetp_ids != null && response.EventsList.fetp_ids.indexOf($scope.userInfo.fetp_id) != -1) {
                $scope.isAuthorizedFETP = true;
            }
            if (response.EventsList.fetp_ids){
                $scope.num_fetp = response.EventsList.fetp_ids.length;
            }

            $scope.eventsList = response.EventsList;
            $scope.filePreview = response.EventsList.filePreview ? response.EventsList.filePreview : '';



            if($scope.eventsList.purpose) {
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

        //console.log($scope.eventsList);

        // today's date
        var today = new Date();
        var dd = today.getDate();
        var mm = today.getMonth(); //January is 0!
        var yyyy = today.getFullYear();
        var month = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        $scope.today_date = dd + '-' + month[mm] + '-' + yyyy;

        // get response
        $scope.response_text = '';
        if ($routeParams.response_id){
            var formData = {};
            formData['uid'] = $scope.userInfo.uid;
            formData['org_id'] = $scope.userInfo.organization_id;
            formData['fetp_id'] = $scope.userInfo.fetp_id;
            formData['response_id'] = $routeParams.response_id;
            $http({ url: urlBase + 'scripts/getresponse.php', method: "POST", data: formData
            }).success(function (respdata, status, headers, config) {
                $scope.response_text = respdata['response'];
                $scope.responder_id = respdata['responder_id'];
                $scope.permission_id = respdata['response_permission_id'];
            });
        }

        // count unrated responses in closed events
        $scope.num_notrated_responses = 0;
        if ($scope.onOpen) {
            $scope.num_notrated_responses = response.numNotRatedResponses;
        } else if ($scope.eventsList){
            for (var n in $scope.eventsList.yours) {
                $scope.num_notrated_responses += parseInt($scope.eventsList.yours[n].num_notrated_responses);
            }
        }

        // count responses with content
        for (var h in $scope.eventsList.history) {
            if (($scope.eventsList.history[h].permission !== '0') && ($scope.eventsList.history[h].type == 'Member Response')
                && ($scope.userInfo.uid)){
                $scope.validResponses++;
            }
        }

        // check unclosed RFIs with no activity in the last two weeks
        Date.prototype.yyyymmdd = function() {
            var yyyy = this.getFullYear().toString();
            var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
            var dd  = this.getDate().toString();
            return yyyy + "-" + (mm[1]?mm:"0"+mm[0]) + "-" + (dd[1]?dd:"0"+dd[0]); // padding
        };
        var d = new Date();
        $scope.date = d.setDate(d.getDate() - 14); // now minus 14 days
        $scope.unclosed = 0;
        for (var n in $scope.eventsList.yours){
            newdate = $scope.eventsList.yours[n].num_followups[0].iso_date;
            if (newdate < d.yyyymmdd()) {
                $scope.unclosed++;
            }
        }
        $scope.isRouteLoading = false;

    });

    $scope.sendFollowup = function(formData, isValid) {
        if(isValid) {
            $scope.submitDisabled = true;
            formData['uid'] = $scope.userInfo.uid;
            formData['event_id'] = $routeParams.id;
            if ($routeParams.id){
                var eid = $routeParams.id;
            }
            if($routeParams.response_id) {
                formData['response_id'] = $routeParams.response_id;
            }
            $http({ url: urlBase + 'scripts/sendfollowup2.php', method: "POST", data: formData
            }).success(function (data, status, headers, config) {
                $scope.submitDisabled = false;
                $location.path('/success/3/' + eid);
            });
        }
    }

    $scope.changeRequestStatus = function(formData, thestatus, isValid) {
        // count responses assessed as useful,used in promed, or not useful when closing an RFI
        // only for responses with content
        var useful_rids = [];
        var usefulpromed_rids = [];
        var notuseful_rids = [];
        if(isValid && (thestatus == 'Close' || thestatus == 'Update') && ($scope.validResponses > 0)) {
            for (var h in $scope.eventsList.history) {
                var h_rid = $scope.eventsList.history[h].response_id;
                var h_type = $scope.eventsList.history[h].type;
                var h_fetp_id = $scope.eventsList.history[h].fetp_id;
                var h_orgid = $scope.eventsList.history[h].organization_id;
                var h_useful = $scope.eventsList.history[h].useful;
                var h_perm = $scope.eventsList.history[h].permission;
                if ((h_type == 'Member Response' && h_perm !=='0')
                    && ($scope.userInfo.uid || (h_fetp_id == $scope.userInfo.fetp_id)) && (h_orgid == $scope.userInfo.organization_id)) {
                    if (h_useful === null ) {
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
        if(isValid) {
            $scope.submitDisabled = true;
            formData['event_id'] = $routeParams.id;
            formData['uid'] = $scope.userInfo.uid;
            formData['thestatus'] = thestatus;
            formData['useful_rids'] = useful_rids.toString();
            formData['usefulpromed_rids'] = usefulpromed_rids.toString();
            formData['notuseful_rids'] = notuseful_rids.toString();

            if (thestatus == 'Close') {
                formData['phe_outcome'] = $scope.outcome.answer;
                formData['phe_title'] = $scope.summary.phe_title;
                formData['phe_description'] = $scope.summary.phe_description;
                formData['phe_additional'] = $scope.summary.phe_additional;
            }
            formData['condition_details'] = $scope.eventsList.condition_details;
            $http({ url: urlBase + 'scripts/changestatus2.php', method: "POST", data: formData
            }).success(function (data, status, headers, config) {
                if (data['status'] == 'success') {
                    $scope.submitDisabled = false;
                    var pathid = 4;
                    if (thestatus == "Update") {
                        pathid = 8;
                    } else if (thestatus == "Reopen") {
                        pathid = 5;
                    } else { // closed
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

    $scope.sendResponse = function(formData, isValid) {
        if((formData['response_permission'] == 0) || (formData['response_permission'] == 4) || isValid) {
            $scope.submitDisabled = true;
            // if user has chosen "I have nothing to contribute" button,
            // formData comes in as object response_permissions: 0
            // if user chooses "Active Search", object is response_permissions: 4
            formData['event_id'] = $routeParams.id
            formData['fetp_id'] = $scope.userInfo.fetp_id;
            if ($routeParams.id){
                var eid = $routeParams.id;
            }
            $http({ url: urlBase + 'scripts/sendresponse2.php', method: "POST", data: formData
            }).success(function (data, status, headers, config) {
                if (data['status'] == 'success') {
                    $location.path('/success/2/' + eid);
                } else{
                    alert('response failed!');
                    console.log('invalid event id.')
                }
                $scope.submitDisabled = false;
            });
        }
    };

    $scope.deleteEvent = function(eid){
        if (confirm('Are you sure you want to delete this event?')) {
            data = {eid: eid, superuser: $scope.userInfo.superuser};
            $http({
                url: urlBase + 'scripts/deleteEvent2.php', method: "POST", data: data
            }).success(function (data, status, headers, config) {
                if (data['status'] == 'success') {
                    $location.path('/success/7');
                }
                else{
                    alert(data['reason']);
                    console.log(data['reason']);
                }
            }).error(function (data, status, headers, config) {
                console.log(status);
            });
        }
    };

});
