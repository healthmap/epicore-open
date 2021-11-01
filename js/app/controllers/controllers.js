import ApprovalController from './approval/approvalController.js';
import CertController from './certificate/certController.js';
import EditRequestController from './editRequest/editRequestController.js';
import EventsController from './events/eventsController.js';
import EventsController2 from './events/eventsController2.js';
import FetpController from './fetp/fetpController.js';
import HeaderController from './header/headerController.js';
import MapController from './map/mapController.js';
import MemberLocationsController from './memberLocations/memberLocationsController.js';
import MetricsController from './metrics/metricsController.js';
import ModaccessController from './modaccess/modaccessController.js';
import NewsController from './news/newsController.js';
import PublicRFIChildController from './publicRFI/publicRFIChildController.js';
import PublicRFIController from './publicRFI/publicRFIController.js';
import RequestController from './request/requestController.js';
import RequestController2 from './request/requestController2.js';
import ResponseController from './response/responseController.js';
import SuccessController from './success/successController.js';
import TestController from './training/testController.js';
import UserController from './user/userController.js';

import EventsController3 from './events/eventsController3.js';
import EventsPublicController3 from './events/eventsPublicController3.js';

const Controllers = angular
  .module('EpicoreApp.controllers', [])
  .controller('approvalController', ApprovalController)
  .controller('certController', CertController)
  .controller('editRequestController', EditRequestController)
  .controller('eventsController', EventsController)
  .controller('fetpController', FetpController)
  .controller('headerController', HeaderController)
  .controller('mapController', MapController)
  .controller('memberLocationsController', MemberLocationsController)
  .controller('metricsController', MetricsController)
  .controller('modaccessController', ModaccessController)
  .controller('newsController', NewsController)
  .controller('publicRFIChildController', PublicRFIChildController)
  .controller('publicRFIController', PublicRFIController)
  .controller('requestController', RequestController)
  .controller('requestController2', RequestController2)
  .controller('responseController', ResponseController)
  .controller('successController', SuccessController)
  .controller('testController', TestController)
  .controller('userController', UserController)
  .controller('eventsController2', EventsController2)
  
  .controller('eventsController3', EventsController3)
  .controller('eventsPublicController3', EventsPublicController3)

  .filter('to_trusted', [
    '$sce',
    function($sce) {
      return function(text) {
        return $sce.trustAsHtml(text);
      };
    },
  ]);

export default Controllers;
