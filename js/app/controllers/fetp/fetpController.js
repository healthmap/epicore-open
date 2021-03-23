controllers.controller("fetpController", function ($scope, $cookieStore) {
  $scope.userInfo = $cookieStore.get("epiUserInfo");
  /* Event(s) controller */
});
