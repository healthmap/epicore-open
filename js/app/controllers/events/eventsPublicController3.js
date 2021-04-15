import { eventsService } from '@/common/eventsService';
import {EVENT_OUTCOME } from '@/constants/eventsConstants';

const { getPublicEvents, getTimeFilterSelectValues } = eventsService();

const EventsPublicController3 = ($scope, $window, epicoreMode, epicoreStartDate) => {
  $scope.mobile = epicoreMode == 'mobile' ? true : false;
  $scope.events = [];
  $scope.eventsOrderBy = 'iso_create_date';
  $scope.isRouteLoading = true;
  $scope.EVENT_OUTCOME = EVENT_OUTCOME;

  const getEventsData = async () => {
    $scope.isRouteLoading = true;

    const month = $scope.timeFilterSelectedValue;
    let start_date = '';
    let end_date = '';
    let num_events = 'all';
    if (month.value == 'all') {
      start_date = moment(epicoreStartDate).format('YYYY-MM-DD');
      end_date = moment().format('YYYY-MM-DD'); // now
    } else if (month.value == 'recent') {
      start_date = moment().subtract(3, 'months').format('YYYY-MM-DD'); // one month ago
      end_date = moment().format('YYYY-MM-DD'); // now
      num_events = 10;
    } else {
      start_date = moment(month.value + '-01').format('YYYY-MM-DD'); // selected month
      end_date = moment(month.value + '-01')
        .add(1, 'month')
        .format('YYYY-MM-DD'); // next month
    }

    $scope.events = await getPublicEvents({
      start_date: start_date || epicoreStartDate,
      end_date: end_date || moment().add(1, 'days').format('YYYY-MM-DD'),
    });

    $scope.isRouteLoading = false;
    $scope.$digest();
  };

  const setTimeFilter = () => {
    $scope.timeFilterValues = getTimeFilterSelectValues(epicoreStartDate);
    $scope.timeFilterSelectedValue = $scope.timeFilterValues[0];
  };

  $scope.selectEventsMonth = async (month) => {
    $scope.timeFilterSelectedValue = month;
    await getEventsData();
  };

  $scope.openPublicArticle = function(eventID) {
    $window.open('#/events_public/articles/' + eventID, '_self');
  };

  const init = () => {
    setTimeFilter();
    getEventsData();
  };

  init();

};

EventsPublicController3.$inject = ['$scope', '$window', 'epicoreMode' , 'epicoreStartDate'];

export default EventsPublicController3;