controllers.controller(
  "editRequestController",
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

    // prepopulate edit request form
    $scope.eventid = $routeParams.id;
    if ($scope.eventid) {
      var eventData = {};
      eventData["event_id"] = $scope.eventid;
      $http({
        url: urlBase + "scripts/getrequest.php",
        method: "POST",
        data: eventData,
      }).success(function (data, status, headers, config) {
        $scope.formData = data; // this pre-populates the values on the form
        $scope.formData.additionalText = data["personalized_text"];
      });
    }

    $scope.updateEvent = function (formData, isValid) {
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

        // update event
        $http({
          url: urlBase + "scripts/updaterequest.php",
          method: "POST",
          data: formData,
        })
          .success(function (data, status, headers, config) {
            if (data["status"] == "success") {
              $location.path("/success/6");
            } else {
            }
          })
          .error(function (data, status, headers, config) {
          });
      }
    };
  }
);
