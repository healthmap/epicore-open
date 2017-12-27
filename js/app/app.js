var app = angular.module('EpicoreApp', [
    'EpicoreApp.services',
    'EpicoreApp.controllers',
    'EpicoreApp.controllers2',
    'ngCookies',
    'ngRoute',
    'ngSanitize',
    'uiGmapgoogle-maps',
    'angular-google-analytics',
    'ngCordova',
    'ngStorage'
]);

// set app version
app.value('epicoreVersion', epicore_config.vers);

// select web or mobile app
var app_mode = epicore_config.app_mode;
if (app_mode == 'mobile_prod') {
    app.value('urlBase', 'https://epicore.org/'); // use full url for mobile api calls
    app.value('epicoreMode', 'mobile');
  }
else if ( app_mode == 'mobile_dev') { // use full url for mobile api calls
    app.value('urlBase', 'https://epicore.org/dev/');
    app.value('epicoreMode', 'mobile');
  }
else if ( app_mode == 'mobile_jandre') { // use full url for mobile api calls
    app.value('urlBase', 'https://epicore.org/~jandre/epicore/');
    app.value('epicoreMode', 'mobile');
}
else { // use relative url for web app
    app.value('urlBase', '');
    app.value('epicoreMode', 'web');
  }

var cacheBustSuffix = Date.now();

app.config(function($routeProvider) {
  $routeProvider.
        when("/events", {templateUrl: "partials/events.html?cb=" + cacheBustSuffix, controller: "eventsController"}).
        when("/events2", {templateUrl: "partials/events2.html?cb=" + cacheBustSuffix, controller: "eventsController2"}).
        when("/events_public", {templateUrl: "partials/events_public.html?cb=" + cacheBustSuffix, controller: "eventsController2"}).
        when("/map", {templateUrl: "partials/map.html?cb=" + cacheBustSuffix, controller: "mapController"}).
        when("/events/closed", {templateUrl: "partials/events.html?cb=" + cacheBustSuffix, controller: "eventsController"}).
        when("/events2/closed", {templateUrl: "partials/events2.html?cb=" + cacheBustSuffix, controller: "eventsController2"}).
        when("/events/:id", {templateUrl: "partials/event.html?cb=" + cacheBustSuffix, controller: "eventsController"}).
        when("/events2/:id", {templateUrl: "partials/event2.html?cb=" + cacheBustSuffix, controller: "eventsController2"}).
        when("/reply/:id", {templateUrl: "partials/reply.html?cb=" + cacheBustSuffix, controller: "eventsController"}).
        when("/reply2/:id", {templateUrl: "partials/reply2.html?cb=" + cacheBustSuffix, controller: "eventsController2"}).
        when("/close/:id", {templateUrl: "partials/close.html?cb=" + cacheBustSuffix, controller: "eventsController"}).
        when("/close2/:id", {templateUrl: "partials/close2.html?cb=" + cacheBustSuffix, controller: "eventsController2"}).
        when("/reopen/:id", {templateUrl: "partials/reopen.html?cb=" + cacheBustSuffix, controller: "eventsController"}).
        when("/reopen2/:id", {templateUrl: "partials/reopen2.html?cb=" + cacheBustSuffix, controller: "eventsController2"}).
        when("/followup/:id", {templateUrl: "partials/followup.html?cb=" + cacheBustSuffix, controller: "eventsController"}).
        when("/followup2/:id", {templateUrl: "partials/followup2.html?cb=" + cacheBustSuffix, controller: "eventsController2"}).
        when("/followup/:id/:response_id", {templateUrl: "partials/followup.html?cb=" + cacheBustSuffix, controller: "eventsController"}).
        when("/followup2/:id/:response_id", {templateUrl: "partials/followup2.html?cb=" + cacheBustSuffix, controller: "eventsController2"}).
        when("/condition", {templateUrl: "partials/rfi_condition.html?cb=" + cacheBustSuffix, controller: "requestController2"}).
        when("/duplicate", {templateUrl: "partials/rfi_duplicate.html?cb=" + cacheBustSuffix, controller: "requestController2"}).
        when("/population", {templateUrl: "partials/rfi_population.html?cb=" + cacheBustSuffix, controller: "requestController2"}).
        when("/location", {templateUrl: "partials/rfi_location.html?cb=" + cacheBustSuffix, controller: "requestController2"}).
        when("/location/:id", {templateUrl: "partials/rfi_location.html?cb=" + cacheBustSuffix, controller: "requestController2"}).
        when("/time", {templateUrl: "partials/rfi_time.html?cb=" + cacheBustSuffix, controller: "requestController2"}).
        when("/purpose", {templateUrl: "partials/rfi_purpose.html?cb=" + cacheBustSuffix, controller: "requestController2"}).
        when("/source", {templateUrl: "partials/rfi_source.html?cb=" + cacheBustSuffix, controller: "requestController2"}).
        when("/members", {templateUrl: "partials/rfi_members.html?cb=" + cacheBustSuffix, controller: "requestController2"}).
        when("/sendrequest", {templateUrl: "partials/rfi_sendrequest.html?cb=" + cacheBustSuffix, controller: "requestController2"}).
        when("/sent", {templateUrl: "partials/rfi_sent.html?cb=" + cacheBustSuffix, controller: "requestController2"}).
        when("/request", {templateUrl: "partials/request.html?cb=" + cacheBustSuffix, controller: "requestController"}).
        when("/request/:alertid", {templateUrl: "partials/request.html?cb=" + cacheBustSuffix, controller: "requestController"}).
        when("/request2", {templateUrl: "partials/request2.html?cb=" + cacheBustSuffix, controller: "requestController"}).
        when("/request3", {templateUrl: "partials/request3.html?cb=" + cacheBustSuffix, controller: "requestController"}).
        when("/request_edit/:id", {templateUrl: "partials/request_edit.html?cb=" + cacheBustSuffix, controller: "editRequestController"}).
        when("/success/:id", {templateUrl: "partials/success.html?cb=" + cacheBustSuffix, controller: "successController"}).
        when("/success/:id/:eid", {templateUrl: "partials/success.html?cb=" + cacheBustSuffix, controller: "successController"}).
        when("/about", {templateUrl: "partials/about.html?cb=" + cacheBustSuffix}).
        when("/how", {templateUrl: "partials/howitworks.html?cb=" + cacheBustSuffix}).
        when("/who", {templateUrl: "partials/whocanapply.html?cb=" + cacheBustSuffix}).
        when("/educator", {templateUrl: "partials/lpeducator.html?cb=" + cacheBustSuffix}).
        when("/provider", {templateUrl: "partials/lpprovider.html?cb=" + cacheBustSuffix}).
        when("/researcher", {templateUrl: "partials/lpresearcher.html?cb=" + cacheBustSuffix}).
        when("/professional", {templateUrl: "partials/lpprofessional.html?cb=" + cacheBustSuffix}).
        when("/terms", {templateUrl: "partials/terms.html?cb=" + cacheBustSuffix}).
        when("/fetp", {templateUrl: "partials/fetp.html?cb=" + cacheBustSuffix}).
        when("/fetp/:eid", {templateUrl: "partials/fetp.html?cb=" + cacheBustSuffix}).
        when("/mod/:tid/:aid", {templateUrl: "partials/mod.html?cb=" + cacheBustSuffix}).
        when("/application", {templateUrl: "partials/application_new.html?cb=" + cacheBustSuffix}).
        when("/application_confirm", {templateUrl: "partials/application_confirm.html?cb=" + cacheBustSuffix}).
        when("/application/:id/:action/:idtype", {templateUrl: "partials/application_new.html?cb=" + cacheBustSuffix, controller: "userController"}).
        when("/approval", {templateUrl: "partials/approval.html?cb=" + cacheBustSuffix, controller: "approvalController"}).
        when("/login", {templateUrl: "partials/login.html?cb=" + cacheBustSuffix}).
        when("/setpassword", {templateUrl: "partials/setpassword.html?cb=" + cacheBustSuffix}).
        when("/resetpassword", {templateUrl: "partials/resetpassword.html?cb=" + cacheBustSuffix}).
        when("/home", {templateUrl: "partials/home.html?cb=" + cacheBustSuffix}).
        when("/trainingvideos", {templateUrl: "partials/trainingvideos.html?cb=" + cacheBustSuffix, controller: "userController"}).
        when("/resources", {templateUrl: "partials/resources.html?cb=" + cacheBustSuffix, controller: "userController"}).
        when("/training", {templateUrl: "partials/test.html?cb=" + cacheBustSuffix, controller: "testController"}).
        when("/certificate", {templateUrl: "partials/certificate.html?cb=" + cacheBustSuffix, controller: "certController"}).
        when("/modaccess", {templateUrl: "partials/modaccess.html?cb=" + cacheBustSuffix, controller: "modaccessController"}).
        when("/member_locations", {templateUrl: "partials/member_locations.html?cb=" + cacheBustSuffix, controller: "memberLocationsController"}).
  otherwise({redirectTo: '/home'});
    });

/* google analytics */
app.run(['$rootScope', '$location', '$window', function($rootScope, $location, $window){
        $rootScope.$on('$routeChangeSuccess', function(event){
                $window.ga('send', 'pageview', { page: $location.path() });
            });
    }]);

/* push notifications listener */
if ((app_mode == 'mobile_dev') || (app_mode == 'mobile_prod') || (app_mode == 'mobile_jandre')) {
    app.run(function ($http, $cordovaPushV5, $rootScope, $cordovaDevice, $localStorage) {

        document.addEventListener("deviceready", function () {

            var options = {
                android: {
                    senderID: epicore_config.android_senderId
                },
                ios: {
                    alert: "true",
                    badge: "true",
                    sound: "true",
                    clearBadge: "true"  // clears badge on app startup
                },
                browser: {
                    pushServiceURL: 'http://push.api.phonegap.com/v1/push'
                },
                windows: {}
            };

            // initialize
            $cordovaPushV5.initialize(options).then(function () {
                // start listening for new notifications
                $cordovaPushV5.onNotification();
                // start listening for errors
                $cordovaPushV5.onError();

                // register to get registrationId
                $cordovaPushV5.register().then(function (registrationId) {
                    // save `registrationId` somewhere;
                    //alert("RegId: " +registrationId);
                    $localStorage.registrationId = registrationId;

                })
            });

            // triggered every time notification received
            $rootScope.$on('$cordovaPushV5:notificationReceived', function (event, data) {
                // data.message,
                // data.title,
                // data.count,
                // data.sound,
                // data.image,
                // data.additionalData

                $localStorage.pushMessage = data.message;

                // parse event id from message
                var msg = data.message.split(':');
                var emsg = msg[0].split('#');
                $localStorage.event_id = emsg[1];
                //alert("Event id: " +$localStorage.event_id);
                //alert("Notification: " +data.message);

            });

            // triggered every time error occurs
            $rootScope.$on('$cordovaPushV5:errorOcurred', function (event, e) {
                // e.message
                //alert(e.message);
                $localStorage.pushError = e.message;
            });

            // get device info
            $localStorage.mobile_model = $cordovaDevice.getModel(); // eg. iPhone 6
            $localStorage.mobile_platform = $cordovaDevice.getPlatform(); // eg. iOS, Android
            $localStorage.mobile_os_version = $cordovaDevice.getVersion(); // eg iOS 10.2

        }, false);

    });
}
app.config(function (AnalyticsProvider) {
    // Add configuration code as desired - see below
    // Set a single account
    AnalyticsProvider.setAccount('UA-72336136-1');

    // Use ga.js (classic) instead of analytics.js (universal)
    // By default, universal analytics is used, unless this is called with a falsey value.
    AnalyticsProvider.useAnalytics(false);

    // track all routes/states (or not)
    //AnalyticsProvider.trackPages(true);
});

//app.run(function(Analytics){});

/* back button directive used on Event.html*/
app.directive('siteHeader', function () {
    return {
        restrict: 'E',
        template: '<button class="btn btn-default"><i class="fa fa-arrow-circle-left"></i> {{back}} to Your EpiCore Dashboard</button>',
        scope: {
            back: '@back',
            icons: '@icons'
        },
        link: function(scope, element, attrs) {
            $(element[0]).on('click', function() {
                history.back();
                scope.$apply();
            });
        }
    };
});

/* youtube directive */
app.directive('myYoutube', function($sce) {
    return {
        restrict: 'EA',
        scope: { code:'=' },
        replace: true,
        template: '<div style="height:550px; width: 980px;"><iframe style="overflow:hidden;height:100%;width:100%" width="100%" height="100%" src="{{url}}" frameborder="0" allowfullscreen></iframe></div>',
        link: function (scope) {
            scope.$watch('code', function (newVal) {
                if (newVal) {
                    scope.url = $sce.trustAsResourceUrl("https://www.youtube.com/embed/" + newVal + "?vq=hd720");
                }
            });
        }
    };
});

/* chosen directive */
app.directive('chosen', function($timeout) {

    var linker = function(scope, element, attr) {

        $timeout(function () {
            element.chosen();
        }, 0, false);
    };

    return {
        restrict: 'A',
        link: linker
    };
});

app.directive('modal', function () {
    return {
        template: '<div class="modal fade">' +
        '<div class="modal-dialog">' +
        '<div class="modal-content">' +
        '<div class="modal-header">' +
        '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' +
        '<h4 class="modal-title">{{ modalTitle }}</h4>' +
        '</div>' +
        '<div class="modal-body" ng-transclude>{{modalBody}}</div>' +
        '</div>' +
        '</div>' +
        '</div>',
        restrict: 'E',
        transclude: true,
        replace:true,
        scope:true,
        link: function postLink(scope, element, attrs) {
            scope.$watch(attrs.visible, function(value){
                if(value == true)
                    $(element).modal('show');
                else
                    $(element).modal('hide');
            });

            $(element).on('shown.bs.modal', function(){
                scope.$apply(function(){
                    scope.$parent[attrs.visible] = true;
                });
            });

            $(element).on('hidden.bs.modal', function(){
                scope.$apply(function(){
                    scope.$parent[attrs.visible] = false;
                });
            });
        }
    };
});

app.value('epicoreCountries', [
    {name: 'Afghanistan', code: 'AF'},
    {name: 'Åland Islands', code: 'AX'},
    {name: 'Albania', code: 'AL'},
    {name: 'Algeria', code: 'DZ'},
    {name: 'American Samoa', code: 'AS'},
    {name: 'Andorra', code: 'AD'},
    {name: 'Angola', code: 'AO'},
    {name: 'Anguilla', code: 'AI'},
    {name: 'Antarctica', code: 'AQ'},
    {name: 'Antigua and Barbuda', code: 'AG'},
    {name: 'Argentina', code: 'AR'},
    {name: 'Armenia', code: 'AM'},
    {name: 'Aruba', code: 'AW'},
    {name: 'Australia', code: 'AU'},
    {name: 'Austria', code: 'AT'},
    {name: 'Azerbaijan', code: 'AZ'},
    {name: 'Bahamas', code: 'BS'},
    {name: 'Bahrain', code: 'BH'},
    {name: 'Bangladesh', code: 'BD'},
    {name: 'Barbados', code: 'BB'},
    {name: 'Belarus', code: 'BY'},
    {name: 'Belgium', code: 'BE'},
    {name: 'Belize', code: 'BZ'},
    {name: 'Benin', code: 'BJ'},
    {name: 'Bermuda', code: 'BM'},
    {name: 'Bhutan', code: 'BT'},
    {name: 'Bolivia', code: 'BO'},
    {name: 'Bosnia and Herzegovina', code: 'BA'},
    {name: 'Botswana', code: 'BW'},
    {name: 'Bouvet Island', code: 'BV'},
    {name: 'Brazil', code: 'BR'},
    {name: 'British Indian Ocean Territory', code: 'IO'},
    {name: 'Brunei Darussalam', code: 'BN'},
    {name: 'Bulgaria', code: 'BG'},
    {name: 'Burkina Faso', code: 'BF'},
    {name: 'Burundi', code: 'BI'},
    {name: 'Cambodia', code: 'KH'},
    {name: 'Cameroon', code: 'CM'},
    {name: 'Canada', code: 'CA'},
    {name: 'Cape Verde', code: 'CV'},
    {name: 'Cayman Islands', code: 'KY'},
    {name: 'Central African Republic', code: 'CF'},
    {name: 'Chad', code: 'TD'},
    {name: 'Chile', code: 'CL'},
    {name: 'China', code: 'CN'},
    {name: 'Christmas Island', code: 'CX'},
    {name: 'Cocos (Keeling) Islands', code: 'CC'},
    {name: 'Colombia', code: 'CO'},
    {name: 'Comoros', code: 'KM'},
    {name: 'Congo, Republic of the', code: 'CG'},
    {name: 'Congo, The Democratic Republic of the', code: 'CD'},
    {name: 'Cook Islands', code: 'CK'},
    {name: 'Costa Rica', code: 'CR'},
    {name: 'Cote D\'Ivoire', code: 'CI'},
    {name: 'Croatia', code: 'HR'},
    {name: 'Cuba', code: 'CU'},
    {name: 'Cyprus', code: 'CY'},
    {name: 'Czech Republic', code: 'CZ'},
    {name: 'Denmark', code: 'DK'},
    {name: 'Djibouti', code: 'DJ'},
    {name: 'Dominica', code: 'DM'},
    {name: 'Dominican Republic', code: 'DO'},
    {name: 'Ecuador', code: 'EC'},
    {name: 'Egypt', code: 'EG'},
    {name: 'El Salvador', code: 'SV'},
    {name: 'Equatorial Guinea', code: 'GQ'},
    {name: 'Eritrea', code: 'ER'},
    {name: 'Estonia', code: 'EE'},
    {name: 'Ethiopia', code: 'ET'},
    {name: 'Falkland Islands (Malvinas)', code: 'FK'},
    {name: 'Faroe Islands', code: 'FO'},
    {name: 'Fiji', code: 'FJ'},
    {name: 'Finland', code: 'FI'},
    {name: 'France', code: 'FR'},
    {name: 'French Guiana', code: 'GF'},
    {name: 'French Polynesia', code: 'PF'},
    {name: 'French Southern Territories', code: 'TF'},
    {name: 'Gabon', code: 'GA'},
    {name: 'Gambia', code: 'GM'},
    {name: 'Georgia', code: 'GE'},
    {name: 'Germany', code: 'DE'},
    {name: 'Ghana', code: 'GH'},
    {name: 'Gibraltar', code: 'GI'},
    {name: 'Greece', code: 'GR'},
    {name: 'Greenland', code: 'GL'},
    {name: 'Grenada', code: 'GD'},
    {name: 'Guadeloupe', code: 'GP'},
    {name: 'Guam', code: 'GU'},
    {name: 'Guatemala', code: 'GT'},
    {name: 'Guernsey', code: 'GG'},
    {name: 'Guinea', code: 'GN'},
    {name: 'Guinea-Bissau', code: 'GW'},
    {name: 'Guyana', code: 'GY'},
    {name: 'Haiti', code: 'HT'},
    {name: 'Heard Island and Mcdonald Islands', code: 'HM'},
    {name: 'Holy See (Vatican City State)', code: 'VA'},
    {name: 'Honduras', code: 'HN'},
    {name: 'Hong Kong', code: 'HK'},
    {name: 'Hungary', code: 'HU'},
    {name: 'Iceland', code: 'IS'},
    {name: 'India', code: 'IN'},
    {name: 'Indonesia', code: 'ID'},
    {name: 'Iran, Islamic Republic Of', code: 'IR'},
    {name: 'Iraq', code: 'IQ'},
    {name: 'Ireland', code: 'IE'},
    {name: 'Isle of Man', code: 'IM'},
    {name: 'Israel', code: 'IL'},
    {name: 'Italy', code: 'IT'},
    {name: 'Jamaica', code: 'JM'},
    {name: 'Japan', code: 'JP'},
    {name: 'Jersey', code: 'JE'},
    {name: 'Jordan', code: 'JO'},
    {name: 'Kazakhstan', code: 'KZ'},
    {name: 'Kenya', code: 'KE'},
    {name: 'Kiribati', code: 'KI'},
    {name: 'Korea, Democratic People\'s Republic of', code: 'KP'},
    {name: 'Korea, Republic of', code: 'KR'},
    {name: 'Kuwait', code: 'KW'},
    {name: 'Kyrgyzstan', code: 'KG'},
    {name: 'Lao People\'s Democratic Republic', code: 'LA'},
    {name: 'Latvia', code: 'LV'},
    {name: 'Lebanon', code: 'LB'},
    {name: 'Lesotho', code: 'LS'},
    {name: 'Liberia', code: 'LR'},
    {name: 'Libyan Arab Jamahiriya', code: 'LY'},
    {name: 'Liechtenstein', code: 'LI'},
    {name: 'Lithuania', code: 'LT'},
    {name: 'Luxembourg', code: 'LU'},
    {name: 'Macao', code: 'MO'},
    {name: 'Macedonia, The Former Yugoslav Republic of', code: 'MK'},
    {name: 'Madagascar', code: 'MG'},
    {name: 'Malawi', code: 'MW'},
    {name: 'Malaysia', code: 'MY'},
    {name: 'Maldives', code: 'MV'},
    {name: 'Mali', code: 'ML'},
    {name: 'Malta', code: 'MT'},
    {name: 'Marshall Islands', code: 'MH'},
    {name: 'Martinique', code: 'MQ'},
    {name: 'Mauritania', code: 'MR'},
    {name: 'Mauritius', code: 'MU'},
    {name: 'Mayotte', code: 'YT'},
    {name: 'Mexico', code: 'MX'},
    {name: 'Micronesia, Federated States of', code: 'FM'},
    {name: 'Moldova, Republic of', code: 'MD'},
    {name: 'Monaco', code: 'MC'},
    {name: 'Mongolia', code: 'MN'},
    {name: 'Montenegro', code: 'ME'},
    {name: 'Montserrat', code: 'MS'},
    {name: 'Morocco', code: 'MA'},
    {name: 'Mozambique', code: 'MZ'},
    {name: 'Myanmar', code: 'MM'},
    {name: 'Namibia', code: 'NA'},
    {name: 'Nauru', code: 'NR'},
    {name: 'Nepal', code: 'NP'},
    {name: 'Netherlands', code: 'NL'},
    {name: 'Netherlands Antilles', code: 'AN'},
    {name: 'New Caledonia', code: 'NC'},
    {name: 'New Zealand', code: 'NZ'},
    {name: 'Nicaragua', code: 'NI'},
    {name: 'Niger', code: 'NE'},
    {name: 'Nigeria', code: 'NG'},
    {name: 'Niue', code: 'NU'},
    {name: 'Norfolk Island', code: 'NF'},
    {name: 'Northern Mariana Islands', code: 'MP'},
    {name: 'Norway', code: 'NO'},
    {name: 'Oman', code: 'OM'},
    {name: 'Pakistan', code: 'PK'},
    {name: 'Palau', code: 'PW'},
    {name: 'Palestinian Territory', code: 'PS'},
    {name: 'Panama', code: 'PA'},
    {name: 'Papua New Guinea', code: 'PG'},
    {name: 'Paraguay', code: 'PY'},
    {name: 'Peru', code: 'PE'},
    {name: 'Philippines', code: 'PH'},
    {name: 'Pitcairn', code: 'PN'},
    {name: 'Poland', code: 'PL'},
    {name: 'Portugal', code: 'PT'},
    {name: 'Puerto Rico', code: 'PR'},
    {name: 'Qatar', code: 'QA'},
    {name: 'Reunion', code: 'RE'},
    {name: 'Romania', code: 'RO'},
    {name: 'Russian Federation', code: 'RU'},
    {name: 'Rwanda', code: 'RW'},
    {name: 'Saint Helena', code: 'SH'},
    {name: 'Saint Kitts and Nevis', code: 'KN'},
    {name: 'Saint Lucia', code: 'LC'},
    {name: 'Saint Pierre and Miquelon', code: 'PM'},
    {name: 'Saint Vincent and the Grenadines', code: 'VC'},
    {name: 'Samoa', code: 'WS'},
    {name: 'San Marino', code: 'SM'},
    {name: 'Sao Tome and Principe', code: 'ST'},
    {name: 'Saudi Arabia', code: 'SA'},
    {name: 'Senegal', code: 'SN'},
    {name: 'Serbia', code: 'RS'},
    {name: 'Seychelles', code: 'SC'},
    {name: 'Sierra Leone', code: 'SL'},
    {name: 'Singapore', code: 'SG'},
    {name: 'Slovakia', code: 'SK'},
    {name: 'Slovenia', code: 'SI'},
    {name: 'Solomon Islands', code: 'SB'},
    {name: 'Somalia', code: 'SO'},
    {name: 'South Africa', code: 'ZA'},
    {name: 'South Georgia and the South Sandwich Islands', code: 'GS'},
    {name: 'Spain', code: 'ES'},
    {name: 'Sri Lanka', code: 'LK'},
    {name: 'Sudan', code: 'SD'},
    {name: 'Suriname', code: 'SR'},
    {name: 'Svalbard and Jan Mayen', code: 'SJ'},
    {name: 'Swaziland', code: 'SZ'},
    {name: 'Sweden', code: 'SE'},
    {name: 'Switzerland', code: 'CH'},
    {name: 'Syrian Arab Republic', code: 'SY'},
    {name: 'Taiwan', code: 'TW'},
    {name: 'Tajikistan', code: 'TJ'},
    {name: 'Tanzania, United Republic of', code: 'TZ'},
    {name: 'Thailand', code: 'TH'},
    {name: 'Timor-Leste', code: 'TL'},
    {name: 'Togo', code: 'TG'},
    {name: 'Tokelau', code: 'TK'},
    {name: 'Tonga', code: 'TO'},
    {name: 'Trinidad and Tobago', code: 'TT'},
    {name: 'Tunisia', code: 'TN'},
    {name: 'Turkey', code: 'TR'},
    {name: 'Turkmenistan', code: 'TM'},
    {name: 'Turks and Caicos Islands', code: 'TC'},
    {name: 'Tuvalu', code: 'TV'},
    {name: 'Uganda', code: 'UG'},
    {name: 'Ukraine', code: 'UA'},
    {name: 'United Arab Emirates', code: 'AE'},
    {name: 'United Kingdom', code: 'GB'},
    {name: 'United States', code: 'US'},
    {name: 'United States Minor Outlying Islands', code: 'UM'},
    {name: 'Uruguay', code: 'UY'},
    {name: 'Uzbekistan', code: 'UZ'},
    {name: 'Vanuatu', code: 'VU'},
    {name: 'Venezuela', code: 'VE'},
    {name: 'Vietnam', code: 'VN'},
    {name: 'Virgin Islands, British', code: 'VG'},
    {name: 'Virgin Islands, U.S.', code: 'VI'},
    {name: 'Wallis and Futuna', code: 'WF'},
    {name: 'Western Sahara', code: 'EH'},
    {name: 'Yemen', code: 'YE'},
    {name: 'Zambia', code: 'ZM'},
    {name: 'Zimbabwe', code: 'ZW'}
]);
