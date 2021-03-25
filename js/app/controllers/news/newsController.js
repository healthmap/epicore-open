controllers.controller(
  "newsController",
  function ($scope, $location, $window, newsService) {
    $scope.newsLinksJson = {};
    $scope.newsLinksLatest = {};
    newsService.getPdfURLS().then(function successCallback(res) {
      var response = res.data;
      $scope.newsLinksJson = response;
      $scope.newsLinksLatest = response[0];
    });
  }
);
