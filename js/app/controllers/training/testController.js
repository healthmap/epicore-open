const TestController = ($scope, $cookieStore, $http, $location, urlBase) => {
  // youtube codes
  $scope.code1 = 'LYgaHDL00x0'; // Introduction to Innovative Disease Surveillance Course
  $scope.code2 = '0ZVnTS7Bo3A'; // The EpiCore Training Course

  $scope.passed = false;

  // grade the test and approve member after they pass the test
  $scope.grade = function(test) {
    $scope.attempted = true;

    const missed = [];
    if (test.q1 != 'E') missed.push('1');
    if (test.q2 != 'C') missed.push('2');
    if (test.q3 != 'B') missed.push('3');
    if (test.q4 != 'B') missed.push('4');
    if (test.q5 != 'E') missed.push('5');

    if (missed != '') {
      $scope.test_message =
        'Missed question(s): ' + missed + '.  Please take the test again.';
    } else {
      // approve member
      $scope.passed = true;
      // get member info
      $scope.userInfo = $cookieStore.get('epiUserInfo');

      // check member status add set to approved if status is accepted ('P')
      if ($scope.userInfo.status == 'P') {
        const status = 'approved';
        const data = {fetp_id: $scope.userInfo.fetp_id, status: status};
        $http({
          url: urlBase + 'scripts/approveUser.php',
          method: 'POST',
          data: data,
        }).then(function successCallback(res) {
          const respdata = res.data;
          if (respdata['status'] === 'success') {
            $scope.test_message =
              'You passed the test! <br><br> You can now login to the Epicore platform using your email and password.  Your certificate of recognition is available on the training page after you login.';
            $scope.passed = true;
            // update cookie
            $scope.userInfo.status = 'A';
            $cookieStore.put('epiUserInfo', $scope.userInfo);
          } else {
          }
        });
      } else {
        // member already approved
        $scope.test_message =
          'You passed the test! <br><br> You can now login to the Epicore platform using your email and password. Your certificate of recognition is available on the training page after you login.';
        $scope.passed = true;
      }
    }
  };
};

TestController.$inject = [
  '$scope',
  '$cookieStore',
  '$http',
  '$location',
  'urlBase',
];

export default TestController;
