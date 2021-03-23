var controllers = angular.module('EpicoreApp.controllers', []).filter('to_trusted', ['$sce', function ($sce) {
  return function (text) {
      return $sce.trustAsHtml(text);
  };
}]);