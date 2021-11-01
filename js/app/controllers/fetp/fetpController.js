const FetpController = ($scope, $cookieStore) => {
  $scope.userInfo = $cookieStore.get('epiUserInfo');
  /* Event(s) controller */
};

FetpController.$inject = ['$scope', '$cookieStore'];

export default FetpController;

