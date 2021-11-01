const NewsController = ($scope, newsService) => {
  $scope.newsLinksJson = {};
  $scope.newsLinksLatest = {};
  newsService.getPdfURLS().then(function successCallback(res) {
    const response = res.data;
    $scope.newsLinksJson = response;
    $scope.newsLinksLatest = response[0];
  });
};

NewsController.$inject = ['$scope', 'newsService'];

export default NewsController;
