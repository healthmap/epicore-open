const ResponseController = (
  $scope,
  $routeParams,
  $cookieStore,
  $http,
  urlBase,
) => {
  $scope.userInfo = $cookieStore.get('epiUserInfo');
  const formData = {};
  formData['uid'] = $scope.userInfo.uid;
  formData['org_id'] = $scope.userInfo.organization_id;
  formData['fetp_id'] = $scope.userInfo.fetp_id;
  formData['response_id'] = $routeParams.response_id;
  $http({
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
  '$cookieStore',
  '$http',
  'urlBase',
];

export default ResponseController;
