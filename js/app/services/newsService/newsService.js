const NewsService = ($http) => {
  const newsAPI = {};
  newsAPI.getPdfURLS = function() {
    return $http.get('/newsletter.json');
  };
  return newsAPI;
};

NewsService.$inject = ['$http'];

export default NewsService;
