const SuccessController = (
  $scope,
  $routeParams,
  $cookieStore,
  epicoreVersion,
) => {
  $scope.userInfo = $cookieStore.get('epiUserInfo');
  $scope.epicore_version = epicoreVersion;

  const messages = {};
  messages[1] = 'You have been signed up.';
  messages[2] =
    'The moderator who initiated the request has been notified. If you get any information on this RFI in the future, please come back to this RFI and click on "Yes, respond to this RFI"';
  messages[3] = 'Your RFI has been sent to the selected members.';
  messages[4] =
    'Your RFI has been closed and an email has gone out to the original members contacted.';
  messages[5] =
    'Your RFI has been reopened and an email has gone out to the original members contacted.';
  messages[6] = 'Your RFI has been updated.';
  messages[7] = 'Your RFI has been deleted.';
  messages[8] = 'Your RFI responses have been updated.';
  messages[9] = 'RFI summary has been updated.';
  $scope.id = $routeParams.id;
  $scope.eid = $routeParams.eid;
  $scope.messageResponse = {};
  $scope.messageResponse.text = messages[$scope.id];
};

SuccessController.$inject = [
  '$scope',
  '$routeParams',
  '$cookieStore',
  'epicoreVersion',
];

export default SuccessController;
