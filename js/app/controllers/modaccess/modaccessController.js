const ModaccessController = ($scope, $cookieStore, httpServiceInterceptor, urlBase, $window) => {
  const http = httpServiceInterceptor.http;
  const data = {};
  $scope.showpage = false;
  $scope.isFetchingData = false;
  $scope.mods = [];
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

  //Soft inActivate Requester. Set column to Inactive// All data associated remains
  //Only remove from Cognito and set flag to false
  $scope.inActivateRequester = function (mod_user_id, mod_email) {
    $scope.message = '';
    $scope.isFetchingData = true;
    let data = {};
    data.userId = mod_user_id;
    data.userEmail = mod_email;
    $window.scrollTo(0, 0);
    http({
      url: urlBase + 'scripts/deactivateMod.php',
      method: 'POST',
      data: data,
    }).then(function successCallback(res) {
      const respdata = res.data;
      if (respdata['status'] === 'success') {
        $scope.mods = respdata['mods'];
        $scope.message = 'Successfully deactivated  moderator: ' + mod_email;
      } else {
        $scope.message = 'Unable to deactivated requester: ' + mod_email;
      }
      $scope.isFetchingData = false;
    }, function errorCallback(res) {
      $scope.isFetchingData = false;
      $scope.message = 'Unable to deactivated requester: ' + mod_email;
    });
  };

  $scope.activateRequester = function (mod_user_id, mod_email) {
    $scope.message = '';
    $scope.isFetchingData = true;
    let data = {};
    data.userId = mod_user_id;
    data.userEmail = mod_email;
    $window.scrollTo(0, 0);
    http({
      url: urlBase + 'scripts/activateMod.php',
      method: 'POST',
      data: data,
    }).then(function successCallback(res) {
      const respdata = res.data;
      if (respdata['status'] === 'success') {
        $scope.mods = respdata['mods'];
        $scope.message = 'Successfully activated moderator: ' + mod_email;
      } else {
        $scope.message = 'Unable to deactivated requester: ' + mod_email;
      }
      $scope.isFetchingData = false;
    }, function errorCallback(res) {
      $scope.isFetchingData = false;
      $scope.message = 'Unable to activate requester: ' + mod_email;
    });
  };

  $scope.getAllMods = function () {
    $scope.isFetchingData = true;
    $scope.message = '';

    http({
      url: urlBase + 'scripts/getmods.php',
      method: 'POST',
    }).then(function successCallback(res) {
      const respdata = res.data;
      if (respdata['status'] === 'success') {
        $scope.mods = respdata['mods'];
        $scope.message = '';
        $scope.isFetchingData = false;
      }
    }, function errorCallback(res) {
      $scope.isFetchingData = false;
      $scope.message = 'Unable to delete requester: ' + mod_email;
    });
  };

  //On init call
  $scope.getAllMods();

};

ModaccessController.$inject = ['$scope', '$cookieStore', 'httpServiceInterceptor', 'urlBase', '$window'];

export default ModaccessController;
