import HttpServiceInterceptor from './httpServiceInterceptor/httpServiceInterceptor.js';
import AuthService from './authService/authService.js';
import EventAPIservice from './eventAPIservice/eventAPIservice.js';
import EventAPIservice2 from './eventAPIservice/eventAPIservice2.js';
import NewsService from './newsService/newsService.js';
import RfiForm from './rfiForm/rfiForm.js';

const Services = angular
  .module('EpicoreApp.services', [])
  .factory('httpServiceInterceptor', HttpServiceInterceptor)
  .factory('authService', AuthService)
  .factory('eventAPIservice', EventAPIservice)
  .factory('eventAPIservice2', EventAPIservice2)
  .factory('newsService', NewsService)
  .factory('rfiForm', RfiForm)

  .run([
    '$rootScope',
    '$location',
    'authService',
    'epicoreVersion',
    function($rootScope, $location, authService, epicoreVersion) {
      $rootScope.$on('$routeChangeStart', function(event, next, current) {
        const requesturl = $location.path();
        const urlarr = requesturl.split('/');
        const nonauthpages = new Array(
          'fetp',
          'about',
          'terms',
          'mod',
          'application',
          'news',
          'application_confirm',
          'login',
          'setpassword',
          'resetpassword',
          'confirm',
          'who',
          'how',
          'educator',
          'provider',
          'professional',
          'researcher',
          'certificate',
          'events_public',
          'login_mobile',
          'resendVerify'
        );

        // if user is not authenticated, make them go to homepage if on an auth-only page
        if (
          !authService.isAuthenticated() &&
          nonauthpages.indexOf(urlarr[1]) == -1
        ) {
          if (urlarr[1] == 'home') {
            $location.path('/home');
          } else {
            // add a query string so after login, user goes straight to where they wanted to go
            $location.path('/home').search({redir: requesturl});
          }
        }

        // if user is authenticated and on homepage or fetp login page, go to events listing, or redirect location
        let redirloc =
          urlarr[1] == 'fetp' && typeof urlarr[3] != 'undefined' ?
            '/events/' + urlarr[3] :
            '/events';
        if (epicoreVersion == '2') {
          redirloc =
            urlarr[1] == 'fetp' && typeof urlarr[3] != 'undefined' ?
              '/events2/' + urlarr[3] :
              '/events2';
        }

        // redirloc = ($rootScope.userinfo['fetp_id'] && ($rootScope.userinfo['active'] == 'N')) ? "home" : redirloc; // go to home page if not active fetp
        if ($rootScope.userinfo != undefined) {
          redirloc =
            $rootScope.userinfo['fetp_id'] &&
            $rootScope.userinfo['active'] == 'N' ?
              'home' :
              redirloc; // go to home page if not active fetp
        }
        if (
          authService.isAuthenticated() &&
          ($location.path() == '/home' || urlarr[1] == 'fetp')
        ) {
          $location.path(redirloc);
        }
      });
    },
  ]);

export default Services;
