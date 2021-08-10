const ModaccessController = ($scope, $cookieStore, httpServiceInterceptor, urlBase) => {
  const http = httpServiceInterceptor.http;
  const data = {};
  $scope.showpage = false;
  $scope.isFetchingData = false;
  // only allow superusers
  $scope.userInfo = $cookieStore.get('epiUserInfo');
  $scope.superuser =
    typeof $scope.userInfo != 'undefined' ? $scope.userInfo.superuser : false;
  $scope.message = '';

  if ($scope.superuser != true) {
    http({
      url: urlBase + 'scripts/approveaccess.php',
      method: 'POST',
      data: data,
    }).then(function successCallback() {
      $scope.showpage = true;
    });
  } else {
    $scope.showpage = true;
  }

  $scope.addMod = function (mod_email, mod_org_id, mod_name) {
    const mod_data = { mod_email: mod_email, mod_org_id: mod_org_id, mod_name: mod_name };
    $scope.isFetchingData = true;
    http({
      url: urlBase + 'scripts/addmod.php',
      method: 'POST',
      data: mod_data,
    }).then(function successCallback(res) {
      $scope.isFetchingData = false;
      const respdata = res.data;
      if (respdata['status'] === 'success') {
        $scope.message = 'Successfully added new moderator';
      } else {
        $scope.message = respdata['message'];
      }
    }, function errorCallback(res) {
      $scope.isFetchingData = false;
      $scope.message = 'Unable to add requester.';
    });
  };

  $scope.mods = '';
  http({
    url: urlBase + 'scripts/getmods.php',
    method: 'POST',
  }).then(function successCallback(res) {
    const respdata = res.data;
    if (respdata['status'] === 'success') {
      $scope.mods = respdata['mods'];
    }
  });
};

ModaccessController.$inject = ['$scope', '$cookieStore', 'httpServiceInterceptor', 'urlBase'];

export default ModaccessController;
