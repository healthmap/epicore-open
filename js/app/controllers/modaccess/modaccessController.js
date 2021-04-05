const ModaccessController = ($scope, $cookieStore, $http, urlBase) => {
  const data = {};
  $scope.showpage = false;

  // only allow superusers
  $scope.userInfo = $cookieStore.get('epiUserInfo');
  $scope.superuser =
    typeof $scope.userInfo != 'undefined' ? $scope.userInfo.superuser : false;
  $scope.message = '';

  if ($scope.superuser != true) {
    $http({
      url: urlBase + 'scripts/approveaccess.php',
      method: 'POST',
      data: data,
    }).then(function successCallback() {
      $scope.showpage = true;
    });
  } else {
    $scope.showpage = true;
  }

  $scope.addMod = function(mod_email, mod_org_id) {
    const mod_data = {mod_email: mod_email, mod_org_id: mod_org_id};
    $http({
      url: urlBase + 'scripts/addmod.php',
      method: 'POST',
      data: mod_data,
    }).then(function successCallback(res) {
      const respdata = res.data;
      if (respdata['status'] === 'success') {
        $scope.message = 'Successfully added new moderator';
      } else {
        $scope.message = respdata['message'];
      }
    });
  };

  $scope.mods = '';
  $http({
    url: urlBase + 'scripts/getmods.php',
    method: 'POST',
  }).then(function successCallback(res) {
    const respdata = res.data;
    if (respdata['status'] === 'success') {
      $scope.mods = respdata['mods'];
    }
  });
};

ModaccessController.$inject = ['$scope', '$cookieStore', '$http', 'urlBase'];

export default ModaccessController;
