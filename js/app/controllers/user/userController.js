import { cacheService } from '@/common/cacheService';

const { clear } = cacheService();

const UserController = (
  $rootScope,
  $routeParams,
  $scope,
  $route,
  $cookies,
  $cookieStore,
  $location,
  $window,
  urlBase,
  epicoreMode,
  $localStorage,
  epicoreCountries,
  epicoreVersion,
  $cordovaTouchID,
  httpServiceInterceptor
) => {
  const http = httpServiceInterceptor.http;
  $scope.mobile = epicoreMode == 'mobile' ? true : false;
  $scope.epicore_version = epicoreVersion;

  $scope.isRouteLoading = false;
  $scope.autologin = true;
  const querystr = $location.search() ? $location.search() : '';

  /* get the active state of page you're on */
  $scope.getClass = function(path) {
    if (path == $location.path()) {
      return 'active';
    } else {
      return '';
    }
  };

  $scope.go = function(path) {
    $location.path(path);
  };

  /* pre-populate application form */
  $scope.uid = $routeParams.id;
  $scope.action = $routeParams.action;
  $scope.idtype = $routeParams.idtype;
  if ($scope.uid && $scope.action == 'edit') {
    $scope.more_schools1 = true;
    $scope.more_schools2 = true;
    const data = {};
    data['action'] = $scope.action;
    data['idtype'] = $scope.idtype;

    http({
      url: urlBase + 'scripts/getapplicant.php',
      method: 'POST',
      data: data,
    }).then(function successCallback(res) {
      const data = res.data;
      $scope.uservals = data; // this pre-populates the values on the form
      if ($scope.uservals.university2) {
        $scope.more_schools1 = true;
        $scope.uservals.school_country2 = data['school_country2'];
      } else {
        $scope.more_schools1 = false;
      }
      if ($scope.uservals.university3) {
        $scope.more_schools2 = true;
        $scope.uservals.school_country3 = data['school_country3'];
      } else {
        $scope.more_schools2 = false;
      }

      const addrCompTuple = {};
      $scope.userLocationPlace = {
        address_components: [],
        formatted_address: '',
      };

      if (data['city']) {
        addrCompTuple['long_name'] = data['city'];
        addrCompTuple['short_name'] = data['city'];
        $scope.userLocationPlace['address_components'].push(addrCompTuple);
      }

      if (data['state']) {
        addrCompTuple['long_name'] = data['state'];
        addrCompTuple['short_name'] = data['state'];
        $scope.userLocationPlace['address_components'].push(addrCompTuple);
      }

      if (data['country']) {
        addrCompTuple['long_name'] = data['country'];
        addrCompTuple['short_name'] = data['country'];
        $scope.userLocationPlace['address_components'].push(addrCompTuple);
      }

      let formatAddr =
        data['city'] + ', ' + data['state'] + ' ' + data['country'];

      if (formatAddr) {
        formatAddr = formatAddr.replace(/null/g, '');
        formatAddr = formatAddr.replace(/undefined/g, '');
        $scope.userLocationPlace['formatted_address'] = formatAddr.replace(
          /^,/g,
          '',
        );
      }
    });
  }

  /* get user cookie info */
  $scope.userInfo = $rootScope.userInfo = $cookieStore.get('epiUserInfo');

  /* countries and codes */
  $scope.countries = epicoreCountries;

  $scope.locationOptions = {
    types: ['(regions)'],
  };

  $scope.uservals = {};

  $scope.userLocationChange = function(userLocation) {
    const administrative_areas = [];
    userLocation.address_components.forEach(function(item) {
      if (item.types.indexOf('country') !== -1) {
        $scope.uservals.country = item.short_name;
      }

      item.types.filter(function(type) {
        if (type.indexOf('administrative_area') !== -1) {
          administrative_areas.push(item.short_name);
        }
      });

      if (item.types.indexOf('locality') !== -1) {
        $scope.uservals.city = item.short_name;
      }
    });

    $scope.uservals.state = administrative_areas.toString().replace(/,/g, ', ');
  };

  // pre-populate saved username and password for mobile app
  if (
    $scope.mobile &&
    typeof $localStorage.username != 'undefined' &&
    typeof $localStorage.password != 'undefined'
  ) {
    $scope.formData = {};
    $scope.formData.username = $localStorage.username;
    $scope.formData.password = $localStorage.password;
  }

  $scope.signup = function(uservals, isValid) {
    $scope.attempted = true;
    $scope.signup_message = '';
    // validate checkboxes
    $scope.no_health_exp =
      !uservals.human_health &&
      !uservals.animal_health &&
      !uservals.env_health &&
      !uservals.health_exp_none;

    $scope.no_category =
      !uservals.health_org_university &&
      !uservals.health_org_doh &&
      !uservals.health_org_clinic &&
      !uservals.health_org_other &&
      !uservals.health_org_none;

    $scope.no_notification =
      !uservals.epicoreworkshop &&
      !uservals.conference &&
      !uservals.promoemail &&
      !uservals.othercontact;
    // check email
    const regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    const isemail = regex.test(uservals.email);

    if (
      !isValid ||
      !isemail ||
      $scope.no_health_exp ||
      $scope.no_category ||
      $scope.no_notification ||
      !uservals.training ||
      !uservals.other_training ||
      !uservals.health_exp ||
      !uservals.sector ||
      !$scope.uservals.country ||
      !$scope.uservals.state ||
      !$scope.uservals.city
    ) {
      $scope.signup_message =
        'Form not complete. Please correct the errors above in red, and then submit again.';
      return false;
    } else {
      if ($scope.action == 'edit') {
        http({
          url: urlBase + 'scripts/updateuser.php',
          method: 'POST',
          data: uservals,
        }).then(function successCallback(res) {
          const data = res.data;
          if (data['status'] === 'success') {
            if ($scope.idtype == 'fetp') {
              $location.path('/application/' + $scope.uid + '/edit/fetp');
              $scope.signup_message = 'Successfully Updated profile';
            } else $location.path('/approval');
          } else {
            $scope.signup_message = data['message'];
          }
        });
      } else {
        http({
          url: urlBase + 'scripts/signup.php',
          method: 'POST',
          data: uservals,
        }).then(function successCallback(res) {
          const data = res.data;
          if (data['status'] === 'success') {
            if (data['exists'] == 1) {
              $scope.signup_message =
                'Your email address is already in the applicant system.';
            } else {
              $location.path('/application_confirm');
            }
          } else {
            $scope.signup_message =
              'Your email address is already in the applicant system.';
          }
        });
      }
    }
  };

  // Sign in with Touch id for iOS or login with username & password
  $scope.mobile_message = '';
  $scope.signIn = function() {
    $scope.formData.password = '';
    $scope.autologin = true;
    if (
      $scope.mobile &&
      typeof $localStorage.mobile_platform != 'undefined' &&
      $localStorage.mobile_platform == 'iOS'
    ) {
      // check touch id support of iOS
      $cordovaTouchID.checkSupport().then(
        function() {
          // success, TouchID supported
          // iOS touch id authentication
          $cordovaTouchID
            .authenticate(
              'Use touch id to login or cancel to login with password.',
            )
            .then(
              function() {
                // success
                // username and password must be set first time to use touch id
                if (
                  typeof $localStorage.username != 'undefined' &&
                  typeof $localStorage.password != 'undefined' &&
                  $localStorage.username &&
                  $localStorage.password
                ) {
                  $scope.formData.username = $localStorage.username;
                  $scope.formData.password = $localStorage.password;
                  $scope.userLogin($scope.formData);
                } else {
                  $scope.autologin = false;
                  $scope.formData.password = '';
                  $scope.mobile_message =
                    'Please enter Epicore email (username) and password to use touch id';
                  // alert('Please enter Epicore email (username) and password to use touch id')
                }
              },
              function() {
                $scope.autologin = false;
                $scope.formData.password = '';
                $scope.mobile_message =
                  'Please enter email (username) and password to login.';
                // alert('Please enter email (username) and passord to login.');// cancel touch id authentication
              },
            );
        },
        function(error) {
          $scope.autologin = false;
          $scope.formData.password = '';
          // alert('Touch id not supported or you have not enabled touch id on your device.');
          // alert(error); // TouchID not supported
        },
      );
    } else {
      // login no touch ID
      $scope.autologin = false;
      $scope.formData.password = '';
    }
  };

  /* log in */
  $scope.userLogin = function(formData) {
    $scope.isRouteLoading = true;

    // no formdata passed, get ticket id and (optional) event_id from URL
    if (typeof formData == 'undefined') {
      formData = {};
      if (typeof querystr['t'] != 'undefined') {
        formData['ticket_id'] = querystr['t'];
      } else if ($routeParams.tid) {
        formData['ticket_id'] = $routeParams.tid;
      }
      // if it's a mod, may be coming in with ticket and alert id to auto-fill a request
      formData['alert_id'] = $routeParams.aid ? $routeParams.aid : null;
      // if it's an fetp, may be coming in with ticket and event id for which they will respond
      formData['event_id'] = $routeParams.eid ? $routeParams.eid : null;
      formData['usertype'] =
        $location.path().indexOf('/fetp') == 0 ? 'fetp' : '';
      formData['app'] = 'web';
      formData['epicore_version'] = epicoreVersion;
    } else {
      // from login page
      // save mobile or web platform info
      if ($scope.mobile) {
        formData['reg_id'] = $localStorage.registrationId; // regstration id for push notifications
        formData['model'] = $localStorage.mobile_model; // eg. iPhone 6
        formData['platform'] = $localStorage.mobile_platform; // eg. iOS, Android
        formData['os_version'] = $localStorage.mobile_os_version; // eg iOS 10.2
        formData['app'] = 'mobile';
        formData['event_id'] = $localStorage.event_id; // event_id from push notification
        $localStorage.event_id = null; // clear event_id for next login
      }
      formData['epicore_version'] = epicoreVersion;
    }
    if (
      !formData['ticket_id'] &&
      !formData['alert_id'] &&
      !formData['event_id'] &&
      !$scope.loginForm.$valid
    ) {
      $scope.isRouteLoading = false;
      return;
    }
    http({
      url: urlBase + 'scripts/login.php',
      method: 'POST',
      data: formData,
    }).then(
      function successCallback(res) {
        const data = res.data;
        if (data['status'] === 'success') {
          
          // determines if user is an organization or FETP
          $rootScope.isOrganization =
            data['uinfo']['organization_id'] > 0 ? true : false;
          const isPromed = data['uinfo']['organization_id'] == 4 ? true : false;
          const isActive =
            typeof data['uinfo']['active'] != 'undefined' ?
              data['uinfo']['active'] :
              'Y';
          const memberLocations =
            typeof data['uinfo']['active'] != 'undefined' ?
              data['uinfo']['locations'] :
              false;
          const newUserInfo = {
            uid: data['uinfo']['user_id'],
            isPromed: isPromed,
            isOrganization: $rootScope.isOrganization,
            organization_id: data['uinfo']['organization_id'],
            organization: data['uinfo']['orgname'],
            fetp_id: data['uinfo']['fetp_id'] ? data['uinfo']['fetp_id'] : null,
            email: data['uinfo']['email'],
            uname: data['uinfo']['username'],
            active: isActive,
            status: data['uinfo']['status'],
            superuser: data['uinfo']['superuser'],
            locations: memberLocations,
            environment: data['environment']
          };

          // ticket ref: #245
         /* if(data['uinfo']['token'] != null)
          {
            const token = {
                accessToken : data['uinfo']['token']['accessToken'],
                refreshToken : data['uinfo']['token']['refreshToken'],
                expiresIn : data['uinfo']['token']['expiresIn']
              }
              //save token in localStorage
            $localStorage.token = token;
          }*/

          
          // save username and password
          $localStorage.username = formData['username'];
          // $localStorage.password = formData['password'];
          // save user in cookie
          $cookieStore.put('epiUserInfo', newUserInfo);

          // save user in local storage for mobile app
          $localStorage.user = newUserInfo;

          $rootScope.error_message = false;

          // FETPs that aren't activated yet don't get review page
          if (data['uinfo']['fetp_id'] && data['uinfo']['active'] == 'N') {
            var redirpath = '/training';
          } else {
            var redirpath =
              typeof querystr['redir'] != 'undefined' ?
                querystr['redir'] :
                '/' + data['path'];
          }

          $scope.isRouteLoading = false;
          $scope.autologin = false;
          $location.path(redirpath);
        } else {
          $scope.isRouteLoading = false;
          $rootScope.error_message = true;
          $scope.autologin = false;
          $route.reload();
        }
      },
      function errorCallback() {
        $scope.isRouteLoading = false;
        $scope.autologin = false;
      },
    );
  };

  /* log out */
  $scope.userLogout = function() {
    $cookieStore.remove('epiUserInfo');
    $window.sessionStorage.clear();
    clear();
  };

  /* set password */
  $scope.setPassword = function(formData) {
    $scope.isRouteLoading = true;
    if (typeof querystr['t'] != 'undefined') {
      formData['ticket_id'] = querystr['t'];
    }
    if (!$scope.setpwForm.$valid) {
      $scope.isRouteLoading = false;
      $rootScope.error_message = 'Invalid email or password';
      return false;
    } else {
      http({
        url: urlBase + 'scripts/setpassword.php',
        method: 'POST',
        data: formData,
      }).then(
        function successCallback(res) {
          const data = res.data;
          if (data['status'] === 'success') {

            $location.path('/login');

            // old to delete , should be confirm if needed
            /*
            const isActive =
              typeof data['uinfo']['active'] != 'undefined' ?
                data['uinfo']['active'] :
                'Y';
            $cookieStore.put('epiUserInfo', {
              uid: data['uinfo']['user_id'],
              isPromed: false,
              isOrganization: false,
              organization_id: data['uinfo']['organization_id'],
              organization: data['uinfo']['orgname'],
              fetp_id: data['uinfo']['fetp_id'],
              email: data['uinfo']['email'],
              uname: data['uinfo']['username'],
              active: isActive,
              status: data['uinfo']['status'],
            });
            $rootScope.error_message = false;
            let redirpath = '/training';
            // FETPs that are activated and approved status get to review page
            if (data['uinfo']['fetp_id'] && data['uinfo']['active'] == 'Y') {
              redirpath =
                typeof querystr['redir'] != 'undefined' ?
                  querystr['redir'] :
                  '/' + data['path'];
            }
            $scope.isRouteLoading = false;
            $location.path(redirpath);*/
          } else {
            $scope.isRouteLoading = false;
            if(data['message'] == '') {
              $rootScope.error_message = 'Invalid email address or password';
            }
            if(data['message'] != '') {
              $rootScope.error_message = data['message'];
            }
            $route.reload();
          }
        },
        function errorCallback() {
          $scope.isRouteLoading = false;
        },
      );
    }
  };

  $scope.confirm = function (formData)
  {
    if (!$scope.setpwForm.$valid) {
      $scope.isRouteLoading = false;
      $rootScope.error_message_pw = 'Invalid email address';
      return false;
    } else {
      http({
        url: urlBase + 'scripts/confirm.php',
        method: 'POST',
        data: formData,
      }).then(
        function successCallback(res) {
            const data = res.data;
            if (data['status'] === 'success') {
              $scope.isRouteLoading = false;
              $rootScope.error_message_pw =
                  'Please check your email or temporary password.';
            $location.path('/login');
            } else {
              $scope.isRouteLoading = false;
              $rootScope.error_message_pw = 'Invalid email address or temporary password';
              $route.reload();
            }
          },
        function errorCallback() {
            $rootScope.error_message_pw = 'Invalid email address';
            $scope.isRouteLoading = false;
          },
      );
    }
  };

  /* Reset password */
  $scope.resetPassword = function(formData) {
    if (!$scope.setpwForm.$valid) {
      $scope.isRouteLoading = false;
      $rootScope.error_message_pw = 'Invalid email address';
      return false;
    } else {
      http({
        url: urlBase + 'scripts/resetpassword.php',
        method: 'POST',
        data: formData,
      }).then(
        function successCallback(res) {
          const data = res.data;
          if (data['status'] === 'success') {
            $scope.isRouteLoading = false;
            $rootScope.error_message_pw =
              'Please check your email for instructions to reset your password.';
            $route.reload();
          } else {
            $scope.isRouteLoading = false;
            $rootScope.error_message_pw = 'Invalid email address';
            $route.reload();
          }
        },
        function errorCallback() {
          $rootScope.error_message_pw = 'Invalid email address';
          $scope.isRouteLoading = false;
        },
      );
    }
  };

  // auto-login with mobile push notification
  // This needs to be last in the controller
  if (
    $scope.mobile &&
    $localStorage.event_id !== null &&
    typeof $localStorage.event_id !== 'undefined' &&
    parseInt($localStorage.event_id) > 0
  ) {
    $scope.autologin = true;
    $scope.formData.username = $localStorage.username;
    $scope.formData.password = $localStorage.password;
    $scope.userLogin($scope.formData);
  }
};

UserController.$inject = [
  '$rootScope',
  '$routeParams',
  '$scope',
  '$route',
  '$cookies',
  '$cookieStore',
  '$location',
  '$window',
  'urlBase',
  'epicoreMode',
  '$localStorage',
  'epicoreCountries',
  'epicoreVersion',
  '$cordovaTouchID',
  'httpServiceInterceptor'
];

export default UserController;
