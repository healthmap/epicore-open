controllers.controller("metricsController", function ($scope) {
  $scope.date_now = Date.now();
  var today = new Date();
  $scope.year = today.getFullYear();
  var last_month_num = today.getMonth() - 1;
  if (today.getMonth() == 0) {
    last_month_num = 11;
    $scope.year = $scope.year - 1;
  }

  var months = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];
  $scope.month = months[last_month_num];
  /*
     Following Controllers are added by Sam, CH157135.
     Usage: A call for openPortfolioURL is placed in events_public.html which brings in the data requried to display in the summary page
     Once the data is available, we pass in the datato the next page (publicrfi.html) with publicRFIChildController as child controller
   */
});
