const PublicRFIChildController = ($scope, $window) => {
  $scope.articleOutput = $window.articleOutput;

  $scope.eventTitle = $window.eventTitle;
  $scope.initialSource = $window.initialSource;
  $scope.articleSourceDetails = $window.articleSourceDetails;
  $scope.rfiOutcome = $window.rfiOutcome;
  $scope.eventInfo = $window.eventInfo;
  $scope.closureDate = $window.closureDate;
};

PublicRFIChildController.$inject = ['$scope', '$window'];

export default PublicRFIChildController;
