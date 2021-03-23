controllers.controller(
  "mapController",
  function ($scope, $http, $cookieStore, urlBase) {
    // only allow moderators
    $scope.userInfo = $cookieStore.get("epiUserInfo");
    //$scope.superuser = (typeof($scope.userInfo) != "undefined") ? $scope.userInfo.superuser: false;
    $scope.isOrganization = $scope.userInfo.isOrganization;
    $scope.showpage = false;

    // set map options
    $scope.map = { center: { latitude: 15, longitude: 18 }, zoom: 2 };
    $scope.options = { scrollwheel: true };

    // map height
    $scope.$on("$viewContentLoaded", function () {
      var mapHeight = 500; // or any other calculated value
      $("#member-map .angular-google-map-container").height(mapHeight);
    });

    $scope.markers = [];
    $scope.numMembers = "";
    $http({
      url: urlBase + "scripts/getallmarkers.php",
      method: "POST",
    }).success(function (data, status, headers, config) {
      if (data["status"] == "success") {
        $scope.markers = data["markers"];
        $scope.showpage = true;
        $scope.numMembers = $scope.markers.length;
        $scope.country_members = data["country_members"];
        $scope.numCountries = Object.keys($scope.country_members).length;
      }
    });

    /* FOR ADDING ACTIVE CLASS TO NAV */
  }
);
