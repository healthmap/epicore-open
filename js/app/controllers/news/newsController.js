controllers.controller(
  "newsController",
  function ($scope, $location, $window, newsService) {
    $scope.newsLinksJson = {};
    $scope.newsLinksLatest = {};
    newsService.getPdfURLS().success(function (response) {
      $scope.newsLinksJson = response;
      $scope.newsLinksLatest = response[0];
    });
  }
);
