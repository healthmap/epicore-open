const HeaderController = ($scope, $location) => {
  $scope.isActive = function(viewLocation) {
    return viewLocation === $location.path();
  };
  /* FETP controller */
};

HeaderController.$inject = ['$scope', '$location'];

export default HeaderController;
