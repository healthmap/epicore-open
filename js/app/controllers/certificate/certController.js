const CertController = ($scope, $cookieStore, $http, urlBase) => {
  // get member info
  $scope.userInfo = $cookieStore.get('epiUserInfo');
  const data = {};
  data['uid'] = $scope.userInfo.fetp_id;
  data['idtype'] = 'fetp';
  $http({
    url: urlBase + 'scripts/getapplicant.php',
    method: 'POST',
    data: data,
  }).then(function successCallback(res) {
    const data = res.data;
    $scope.member_name = data.firstname + ' ' + data.lastname;
    $scope.approve_date = data.approve_date;
    const month = new Array(
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    );
    if (!data.approve_date) {
      return;
    }
    let d = data.approve_date.split(' ');
    d = d[0].split('-');
    let dayof = 'th Day of ';
    if (d[2] == 1 || d[2] == 21 || d[2] == 31) {
      dayof = 'st Day of ';
    } else if (d[2] == 2 || d[2] == 22) {
      dayof = 'nd Day of ';
    } else if (d[2] == 3 || d[2] == 23) {
      dayof = 'rd Day of ';
    }
    $scope.approve_date =
      Number(d[2]) + dayof + month[Number(d[1]) - 1] + ', ' + d[0];
  });

  $scope.printPage = function(divName) {
    window.print();
  };
};

CertController.$inject = ['$scope', '$cookieStore', '$http', 'urlBase'];

export default CertController;
