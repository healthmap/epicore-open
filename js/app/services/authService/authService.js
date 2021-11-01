const AuthService = ($rootScope, $cookieStore) => {
  return {
    isAuthenticated: function() {
      $rootScope.userinfo = $cookieStore.get('epiUserInfo');
      return $rootScope.userinfo ? 1 : 0;
    },
  };
};

AuthService.$inject = ['$rootScope', '$cookieStore'];

export default AuthService;
