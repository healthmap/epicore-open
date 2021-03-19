controllers.controller(
  "requestController",
  function (
    $rootScope,
    $window,
    $scope,
    $routeParams,
    $cookieStore,
    $location,
    $http,
    urlBase
  ) {
    $scope.userInfo = $rootScope.userInfo = $cookieStore.get("epiUserInfo");

    // this will pre-fill the event form with session values if back button is used
    if ($window.sessionStorage.length > 0) {
      $scope.formData = {};
      $scope.formData.title = $window.sessionStorage.title;
      $scope.formData.additionalText = $window.sessionStorage.additionalText;
      $scope.formData.description = $window.sessionStorage.description;
      $scope.formData.location = $window.sessionStorage.location;
      $scope.formData.disease = $window.sessionStorage.disease;
      $scope.formData.latlon = $window.sessionStorage.latlon;
    }

    // if there's an alertid passed in from ProMED, get the info to prepopulate the fields
    $scope.alertid = $routeParams.alertid;
    if ($scope.alertid && $scope.alertid !== $window.sessionStorage.alertid) {
      $window.sessionStorage.alertid = $scope.alertid;
      var alertData = {};
      alertData["alert_id"] = $scope.alertid;
      $http({
        url: urlBase + "scripts/getalert.php",
        method: "POST",
        data: alertData,
      }).success(function (data, status, headers, config) {
        $scope.formData = data; // this pre-populates the values on the form
        $scope.formData.additionalText = "";
        $window.sessionStorage.title = data["title"];
        $window.sessionStorage.description = data["description"];
        $window.sessionStorage.location = data["location"];
        $window.sessionStorage.latlon = data["latlon"];
        $window.sessionStorage.disease = data["disease"];
        $window.sessionStorage.species = data["species"];
        $window.sessionStorage.additionalText = "";

        //insert arabic summary into description if available
        var a = data["arabic_text"];
        if (a != "") {
          var d = data["description"];
          d = d.replace(
            "<http://www.isid.org>",
            "<http://www.isid.org>" + "\n\n" + a + "\n"
          );
          $window.sessionStorage.description = d;
          $scope.formData.description = d;
        }
      });
    }

    /* step 1: save the event information in session variable, and
  filter FETPs for next screen based on location chosen */
    $scope.storeEvent = function (formData, isValid) {
      if (isValid) {
        // jquery hack to get the latlon hidden value and autocomplete for location (angular bug)
        formData["latlon"] = $("#default_location").val();
        formData["location"] = $("#searchTextField").val();

        if (!formData["latlon"]) {
          alert(
            "Geolocation failed - please scroll down and select a location from the auto-suggester in the location field so that we have the coordinates of the event."
          );
          $scope.formData.location = "";
          return false;
        }

        // otherwise save the session data, get FETPs near location and move on
        $window.sessionStorage.title = formData["title"];
        $window.sessionStorage.description = formData["description"];
        $window.sessionStorage.disease = formData["disease"];
        $window.sessionStorage.additionalText = formData["additionalText"]
          ? formData["additionalText"]
          : "";

        // if you're here from the back button and the location hasn't changed,
        // don't change the FETP filtering criteria

        if (
          !$window.sessionStorage.searchBox ||
          $window.sessionStorage.location != formData["location"]
        ) {
          $window.sessionStorage.location = formData["location"];
          $window.sessionStorage.latlon = formData["latlon"];

          $http({
            url: urlBase + "scripts/filter.php",
            method: "POST",
            data: formData,
          })
            .success(function (data, status, headers, config) {
              $window.sessionStorage.userIds = data["userIds"];
              $window.sessionStorage.numFetps = data["userList"]["sending"];
              $window.sessionStorage.numUniqueFetps =
                data["uniqueList"]["sending"];
              $window.sessionStorage.searchBox = data["bbox"];
              $window.sessionStorage.searchType = "radius";
              $location.path("/request2");
            })
            .error(function (data, status, headers, config) {
              console.log(status);
            });
        } else {
          $location.path("/request2");
        }
      }
    };

    $scope.numFetps = $window.sessionStorage.numFetps;
    $scope.numUniqueFetps = $window.sessionStorage.numUniqueFetps;
    $scope.filePreview = $window.sessionStorage.filePreview;

    /* step 2: Filter FETP: calculate the number of users based on check & uncheck */
    if ($location.path() == "/request2" || $location.path() == "/members") {
      // initialize default radio buttons - radius select checked by default
      // unless it's a back-button, then take from session
      if ($window.sessionStorage.searchType == "country") {
        $scope.radiussel = false;
        $scope.formData.countries = $window.sessionStorage.countries.split(",");
      } else {
        $scope.radiussel = true;
      }

      // bounding box around event location
      // show/hide the submit to next step only if there are FETPs to receive the email
      $scope.submitDisabled = $scope.numFetps > 0 ? false : true;

      $scope.bbox = $window.sessionStorage.searchBox.split(",");
      var bounds = new google.maps.LatLngBounds(
        new google.maps.LatLng($scope.bbox[0], $scope.bbox[2]),
        new google.maps.LatLng($scope.bbox[1], $scope.bbox[3])
      );
      $scope.rectangle = {
        bounds: bounds,
        stroke: { color: "#08B21F", weight: 2, opacity: 1 },
        fill: { color: "#08B21F", opacity: 0.5 },
        editable: true,
        visible: true,
      };
      var latlonarr = $window.sessionStorage.latlon.split(",");
      /* values for the map */
      $scope.map = {
        center: { latitude: latlonarr[0], longitude: latlonarr[1] },
        zoom: 5,
      };
      $scope.options = { scrollwheel: false };
      /* only show FETPs on a map to super-users */
      var query = {};
      query["uid"] = $scope.userInfo.uid;
      query["centerlat"] = latlonarr[0];
      query["centerlon"] = latlonarr[1];
      $http({
        url: urlBase + "scripts/getmarkers.php",
        method: "POST",
        data: query,
      }).success(function (data, status, headers, config) {
        if (data["status"] == "success") {
          $scope.markers = data["markers"];
        }
      });

      /* rectangle change event */
      $scope.eventsRectangle = {
        bounds_changed: function (rectangle) {
          var filterData = {};
          var southwest = rectangle.bounds.getSouthWest();
          var northeast = rectangle.bounds.getNorthEast();
          $scope.radiussel = true; // if radius changes without changing radio button
          $window.sessionStorage.searchType = "radius";
          filterData["bbox"] = new Array(
            southwest.lat(),
            northeast.lat(),
            southwest.lng(),
            northeast.lng()
          );
          $http({
            url: urlBase + "scripts/filter.php",
            method: "POST",
            data: filterData,
          }).success(function (filtereddata, status, headers, config) {
            $window.sessionStorage.searchBox = filtereddata["bbox"];
            $window.sessionStorage.userIds = filtereddata["userIds"];
            $window.sessionStorage.numFetps = $scope.numFetps =
              filtereddata["userList"]["sending"];
            $window.sessionStorage.numUniqueFetps = $scope.numUniqueFetps =
              filtereddata["uniqueList"]["sending"];
            $scope.submitDisabled = $scope.numFetps > 0 ? false : true;
          });
        },
      };
    }

    /* check and uncheck training type filters */
    $scope.recalcUsers = function (filterData, whichclicked) {
      $window.sessionStorage.searchType = filterData[
        "filtertype"
      ] = whichclicked;
      if (whichclicked == "country") {
        // select the right radio button if a country is selected without changing radio
        $scope.radiussel = false;
        $window.sessionStorage.countries = filterData["countries"];
      } else {
        filterData["bbox"] = $window.sessionStorage.searchBox.split(",");
        $scope.radiussel = true;
      }
      $http({
        url: urlBase + "scripts/filter.php",
        method: "POST",
        data: filterData,
      }).success(function (filtereddata, status, headers, config) {
        $window.sessionStorage.userIds = filtereddata["userIds"];
        $window.sessionStorage.numFetps = $scope.numFetps =
          filtereddata["userList"]["sending"];
        $window.sessionStorage.numUniqueFetps = $scope.numUniqueFetps =
          filtereddata["uniqueList"]["sending"];
        $scope.submitDisabled = $scope.numFetps > 0 ? false : true;
        if (filtereddata["bbox"]) {
          $window.sessionStorage.searchBox = filtereddata["bbox"];
        }
      });
    };

    /* step 2 submit button - build the email text and move on to step 3 */
    $scope.buildEmailText = function () {
      var formData = {};
      formData["additionalText"] = $window.sessionStorage.additionalText;
      formData["title"] = $window.sessionStorage.title;
      formData["location"] = $window.sessionStorage.location;
      formData["description"] = $window.sessionStorage.description;
      // overwrite the old file preview if it exists
      if (typeof $window.sessionStorage.filePreview != "undefined") {
        formData["file_preview"] = $window.sessionStorage.filePreview;
      }
      $http({
        url: urlBase + "scripts/buildrequest.php",
        method: "POST",
        data: formData,
      }).success(function (respdata, status, headers, config) {
        $window.sessionStorage.filePreview = respdata["file_preview"];
        $location.path("/request3");
      });
    };

    /* step 3 : save all event RFI data in database and send the request */
    $scope.sendRequest = function () {
      $scope.submitDisabled = true;
      var formData = {};
      if ($window.sessionStorage.searchType == "radius") {
        formData["search_box"] = $window.sessionStorage.searchBox;
      } else {
        formData["search_countries"] = $window.sessionStorage.countries;
      }
      formData["uid"] = $scope.userInfo.uid; //requester of RFI
      formData["fetp_ids"] = $window.sessionStorage.userIds;
      formData["latlon"] = $window.sessionStorage.latlon;
      formData["location"] = $window.sessionStorage.location;
      formData["title"] = $window.sessionStorage.title;
      formData["description"] = $window.sessionStorage.description;
      formData["additionalText"] = $window.sessionStorage.additionalText;
      formData["disease"] = $window.sessionStorage.disease;
      formData["alert_id"] = $window.sessionStorage.alertid;
      $http({
        url: urlBase + "scripts/sendrequest.php",
        method: "POST",
        data: formData,
      }).success(function (respdata, status, headers, config) {
        // empty out the form values since you've submitted so they aren't prefilled next time
        $window.sessionStorage.clear();
        $location.path("/success/3");
        $scope.submitDisabled = false;
      });
    };

    /* clear request form */
    $scope.clearRequest = function () {
      if (confirm("Are you sure you want to clear this request form?")) {
        $scope.formData = {};
        $window.sessionStorage.title = "";
        $window.sessionStorage.description = "";
        $window.sessionStorage.location = "";
        $window.sessionStorage.latlon = "";
        $window.sessionStorage.additionalText = "";
        $window.sessionStorage.disease = "";
        $window.sessionStorage.species = "";
        $window.sessionStorage.alertid = "";
      } else {
      }
    };

    /* edit request by owner or superuser */
  }
);
