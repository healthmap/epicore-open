const ResponseController = (
  $scope,
  $routeParams,
  httpServiceInterceptor,
  urlBase,
) => {
  const http = httpServiceInterceptor.http;
  const formData = {};
  formData['response_id'] = $routeParams.response_id;
  http({
    url: urlBase + 'scripts/getresponse.php',
    method: 'POST',
    data: formData,
  }).then(function successCallback(res) {
    const respdata = res.data;
    $scope.isAuthorizedToSee = respdata['status'] == 'failed' ? false : true;
    $scope.isAuthorizedToFollowup = respdata['authorized_to_followup'] ?
      true :
      false;
    $scope.filePreview = respdata['filePreview'] ? respdata['filePreview'] : '';
    $scope.responseObj = respdata;
  });
};

ResponseController.$inject = [
  '$scope',
  '$routeParams',
  'httpServiceInterceptor',
  'urlBase',
];

export default ResponseController;
