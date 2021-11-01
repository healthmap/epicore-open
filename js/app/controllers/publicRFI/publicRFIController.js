const PublicRFIController = ($scope, $window) => {
  $scope.articleTitle = '';
  $scope.articleBody = '';
  $scope.openPortfolioURL = function(
    eventID,
    summary,
    more_info,
    event_title,
    event_source,
    event_source_details,
    event_outcome,
    event_action_date,
  ) {
    let source = '';
    if (event_source == 'MR') {
      source = 'Media Report';
    } else if (event_source == 'OR') {
      source = 'Official Report';
    } else if (event_source == 'OC') {
      source = 'Other communication';
    }

    let outcome = 'Pending';
    if (event_outcome == 'VP') {
      outcome = 'Verified (positive)';
    } else if (event_outcome == 'VN') {
      outcome = 'Verified (negative)';
    } else if (event_outcome == 'UV') {
      outcome = 'Unverified';
    } else if (event_outcome == 'UP') {
      outcome = 'Updated (positive)';
    } else if (event_outcome == 'NU') {
      outcome = 'Updated (negative)';
    }

    $scope.eventTitle = event_title;
    $scope.initialSource = source;
    $scope.articleSourceDetails = event_source_details;
    $scope.rfiOutcome = outcome;
    $scope.eventInfo = event_info;
    $scope.closureDate = event_action_date;

    var event_info =
      'Title: ' +
      event_title +
      '\r\n\r\n' +
      'Initial Source: ' +
      source +
      ':' +
      event_source_details +
      '\r\n\r\n' +
      'RFI Outcome: ' +
      outcome +
      '\r\n\r\n';

    $scope.articleTitle = 'Summary';
    $scope.articleBody = '';
    $scope.additionalInfo = more_info;
    if (more_info) $scope.articleBody = 'PHE Description:\r\n' + summary;
    else if (summary) $scope.articleBody = 'PHE Description:\r\n' + summary;

    const $newArticleWindow = $window.open(
      '#/events_public/articles/' + eventID,
      '_self',
    );

    $newArticleWindow.eventTitle = $scope.eventTitle;
    $newArticleWindow.initialSource = $scope.initialSource;
    $newArticleWindow.articleSourceDetails = $scope.articleSourceDetails;
    $newArticleWindow.rfiOutcome = $scope.rfiOutcome;
    $newArticleWindow.eventInfo = $scope.eventInfo;
    $newArticleWindow.closureDate = $scope.closureDate;
    $newArticleWindow.articleOutput = $scope.articleBody;

    localStorage.clear();

    localStorage.setItem('local_eventTitle', $scope.eventTitle);
    localStorage.setItem('local_initialSource', $scope.initialSource);
    localStorage.setItem(
      'local_articleSourceDetails',
      $scope.articleSourceDetails,
    );
    localStorage.setItem('local_rfiOutcome', $scope.rfiOutcome);
    localStorage.setItem('local_eventInfo', $scope.eventInfo);
    localStorage.setItem('local_closureDate', $scope.closureDate);
    localStorage.setItem('local_articleOutput', $scope.articleBody);
    localStorage.setItem('local_additionalInfo', $scope.additionalInfo);
  };
};

PublicRFIController.$inject = ['$scope', '$window'];

export default PublicRFIController;


