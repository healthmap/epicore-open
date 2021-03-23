controllers.controller(
  "responseController",
  function ($scope, $location, $routeParams, $cookieStore, $http, urlBase) {
    $scope.userInfo = $cookieStore.get("epiUserInfo");
    var formData = {};
    formData["uid"] = $scope.userInfo.uid;
    formData["org_id"] = $scope.userInfo.organization_id;
    formData["fetp_id"] = $scope.userInfo.fetp_id;
    formData["response_id"] = $routeParams.response_id;
    $http({
      url: urlBase + "scripts/getresponse.php",
      method: "POST",
      data: formData,
    }).success(function (respdata, status, headers, config) {
      $scope.isAuthorizedToSee = respdata["status"] == "failed" ? false : true;
      $scope.isAuthorizedToFollowup = respdata["authorized_to_followup"]
        ? true
        : false;
      $scope.filePreview = respdata["filePreview"]
        ? respdata["filePreview"]
        : "";
      $scope.responseObj = respdata;
    });
  }
);
