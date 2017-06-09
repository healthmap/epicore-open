angular.module('EpicoreApp.controllers2', []).

/* Request (RFI): this is the controller that sends an RFI. */
controller('requestController2', function($rootScope, $window, $scope, $routeParams, $cookieStore, $location, $http, urlBase, rfiForm) {

    $scope.userInfo = $rootScope.userInfo = $cookieStore.get('epiUserInfo');

    // get persistant RFI form
    $scope.rfiData = rfiForm.get();

    //////////////////////////  Affected Population //////////////////////////////
    $scope.pop_attempted = false;
    $scope.pop_error_message = '';
    $scope.pop_error_message1 = '';
    $scope.savePopulation = function (direction) {
        // checkbox validation
        $scope.pop_valid = $scope.rfiData.population.human || $scope.rfiData.population.animal || $scope.rfiData.population.environment || $scope.rfiData.population.unknown|| $scope.rfiData.population.other;
        $scope.pop_attempted = true;

        // next
        if ($scope.pop_valid && $scope.rfiData.population.description) { // validation
            if (direction === 'next') {
                $location.path('/condition');
            }
            $scope.clearPopError();
        } else {
            $scope.pop_error_message = 'Missing parameters above.';
            $scope.pop_error_message1 = $scope.pop_valid ? '' : 'Must select one or more of the above options.';
        }
    };

    // clear population
    $scope.clearPopulation = function () {
        $scope.rfiData.population.human = false;
        $scope.rfiData.population.animal = false;
        $scope.rfiData.population.environment = false;
    };
    // clear error
    $scope.clearPopError = function () {
        $scope.pop_error_message = '';
        $scope.pop_error_message1 = '';
    };

    //////////////////////////////////////// Health Condition ////////////////////////////////
    $scope.hc_attempted = false;
    $scope.hc_error_message = '';
    $scope.hc_error_message1 = '';
    $scope.saveCondition = function (direction) {

        // checkbox validation
        $scope.health_condition_valid = $scope.rfiData.health_condition.respiratory || $scope.rfiData.health_condition.gastrointestinal || $scope.rfiData.health_condition.fever_rash || $scope.rfiData.health_condition.jaundice
            || $scope.rfiData.health_condition.h_fever || $scope.rfiData.health_condition.paralysis || $scope.rfiData.health_condition.other_neurological || $scope.rfiData.health_condition.fever_unknown
            || $scope.rfiData.health_condition.unknown || $scope.rfiData.health_condition.other;
        $scope.hc_attempted = true;


        if ($scope.health_condition_valid && $scope.rfiData.health_condition.disease_details ) { // validation

            // next or back
            if (direction === 'next') {
                $location.path('/location');
            } else if (direction === 'back'){
                $location.path('/population');
            }
            $scope.clearhcError();
        } else {
            $scope.hc_error_message = 'Missing parameters above.';
            $scope.hc_error_message1 = $scope.health_condition_valid ? '' : 'Must select one or more of the above options.';
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
    };
    // clear error
    $scope.clearhcError = function () {
        $scope.hc_error_message = '';
        $scope.hc_error_message1 = '';
    };

    /////////////////////////////////////////////// Location & Time ////////////////////////////////
    $scope.location_error_message = '';
    $scope.saveLocation = function (direction) {

        // jquery hack to get the latlon hidden value and autocomplete for location (angular bug)
        $scope.rfiData.location.latlon = $("#default_location").val();
        $scope.rfiData.location.location = $("#searchTextField").val();
        if(!$scope.rfiData.location.latlon) {
            $scope.rfiData.location.location_error_message = 'Geolocation failed - please scroll down and select a location from the auto-suggester in the location field so that we have the coordinates of the event.';
            $scope.rfiData.location.location = '';
            return false;
        }

        // validate and go to next or back path
        if ($scope.rfiData.location.latlon && $scope.rfiData.location.location && $scope.rfiData.location.location_details && $scope.rfiData.location.event_date){

            // next or back
            if (direction === 'next') {

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

            } else if (direction === 'back'){
                $location.path('/condition');
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
        //$scope.submitDisabled = $scope.rfiData.members.numFetps > 0 ? false : true;
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
            $location.path('/purpose');
        } else if (direction === 'back'){
            $location.path('/location');
        }
    };

    ////////////////////////////////////////////// Purpose ////////////////////////////////////////
    $scope.purpose_error_message = '';
    $scope.purpose_error_message1 = '';
    $scope.savePurpose = function (direction) {

        // checkbox validation
        $scope.purpose_valid = $scope.rfiData.purpose.causal_agent || $scope.rfiData.purpose.epidemiology || $scope.rfiData.purpose.pop_affected || $scope.rfiData.purpose.location
            || $scope.rfiData.purpose.size || $scope.rfiData.purpose.test || $scope.rfiData.purpose.other_category;

        // next
        if ($scope.purpose_valid && $scope.rfiData.purpose.purpose && $scope.rfiData.purpose.relevance && $scope.rfiData.purpose.relevance_details) { // validation
            if (direction === 'next') {
                $location.path('/source');
            } else if (direction === 'back'){
                $location.path('/members');
            }

            $scope.purpose_error_message = '';
            $scope.purpose_error_message1 = '';
        } else {
            $scope.purpose_error_message = 'Missing parameters above.';
            $scope.purpose_error_message1 = $scope.purpose_valid ? '' : 'Must select one or more of the above options.';
        }
    };

    ///////////////////////////////////////////// Source /////////////////////////////////////////
    $scope.source_error_message = '';
    $scope.saveSource = function (direction) {

        if ($scope.rfiData.source.source && $scope.rfiData.source.details){
            if (direction === 'next') {

                // build and review request email
                buildEmailText();

            } else if (direction === 'back'){
                $location.path('/purpose');
            }
            $scope.source_error_message = '';
        } else {
            $scope.source_error_message = 'Missing parameters above.';
        }

    };

    /* build and review request email*/
    $scope.filePreview = $window.sessionStorage.filePreview;
    buildEmailText = function() {
        var formData = {};
        formData['additionalText'] =  $scope.rfiData.population.description;
        formData['title'] =  $scope.rfiData.health_condition.disease_details;
        formData['location'] = $scope.rfiData.location.location;
        formData['description'] = $scope.rfiData.health_condition.disease_details;
        // overwrite the old file preview if it exists
        if(typeof($window.sessionStorage.filePreview) != "undefined") {
            formData['file_preview'] = $window.sessionStorage.filePreview;
        }
        $http({ url: urlBase + 'scripts/buildrequest2.php', method: "POST", data: formData
        }).success(function (respdata, status, headers, config) {
            $window.sessionStorage.filePreview = respdata['file_preview'];
            $location.path('/sendrequest');
        });
    }

    /* Send request */
    $scope.sendRequest2 = function () {

        //console.log($scope.rfiData);

        $scope.submitDisabled = true;
        var formData = {};
        if($scope.rfiData.members.searchType == "radius") {
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

        console.log(formData);
        $http({ url: urlBase + 'scripts/sendrequest2.php', method: "POST", data: formData
        }).success(function (respdata, status, headers, config) {
            // empty out the form values since you've submitted so they aren't pre-filled next time
            console.log(respdata);
            $window.sessionStorage.clear();
            $location.path('/success/3');
            $scope.submitDisabled = false;
        });

    }

    });
