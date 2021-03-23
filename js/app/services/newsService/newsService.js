services.factory(
  "newsService",
  function ($http, $rootScope, $location, urlBase) {
    var newsAPI = {};
    newsAPI.getPdfURLS = function () {
      return $http.get("/newsletter.json");
    };
    return newsAPI;
  }
);
