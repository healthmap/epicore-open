controllers.controller(
  "modaccessController",
  function ($scope, $cookieStore, $http, urlBase) {
    var data = {};
    $scope.showpage = false;

    // only allow superusers
    $scope.userInfo = $cookieStore.get("epiUserInfo");
    $scope.superuser =
      typeof $scope.userInfo != "undefined" ? $scope.userInfo.superuser : false;
    $scope.message = "";

    if ($scope.superuser != true) {
      $http({
        url: urlBase + "scripts/approveaccess.php",
        method: "POST",
        data: data,
      }).success(function (respdata, status, headers, config) {
        $scope.showpage = true;
      });
    } else {
      $scope.showpage = true;
    }

    $scope.addMod = function (mod_email, mod_org_id) {
      var mod_data = { mod_email: mod_email, mod_org_id: mod_org_id };
      console.log(mod_data);
      $http({
        url: urlBase + "scripts/addmod.php",
        method: "POST",
        data: mod_data,
      }).success(function (respdata, status, headers, config) {
        if (respdata["status"] == "success") {
          $scope.message = "Successfully added new moderator";
        } else {
          $scope.message = respdata["message"];
        }
      });
    };

    $scope.mods = "";
    $http({
      url: urlBase + "scripts/getmods.php",
      method: "POST",
    }).success(function (respdata, status, headers, config) {
      if (respdata["status"] == "success") {
        $scope.mods = respdata["mods"];
      } else {
      }
    });
  }
);
