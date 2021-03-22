controllers.controller("headerController", function ($scope, $location, $window) {
  $scope.isActive = function (viewLocation) {
    return viewLocation === $location.path();
  };
  /* FETP controller */
});
