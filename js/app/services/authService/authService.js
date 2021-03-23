services.factory(
  "authService",
  function ($rootScope, $cookies, $cookieStore, $http) {
    return {
      isAuthenticated: function (user) {
        $rootScope.userinfo = $cookieStore.get("epiUserInfo");
        return $rootScope.userinfo ? 1 : 0;
      },
    };
  }
);
