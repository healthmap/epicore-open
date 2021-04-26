import { userService } from '@/common/userService';
import { eventsService } from '@/common/eventsService';
import { EVENT_TYPES, EVENT_SOURCE, EVENT_OUTCOME } from '@/constants/eventsConstants';


const { getUser } = userService();
const { getEvents, getEventSummary, getTimeFilterSelectValues } = eventsService();

const EventsController3 = ($scope, $location, epicoreMode, epicoreStartDate) => {
  $scope.userInfo  = getUser();
  $scope.mobile = epicoreMode == 'mobile' ? true : false;
  $scope.events = [];
  $scope.isOrganization = $scope.userInfo .fetp_id > 0 ? false : true;
  $scope.onOpen = $location.path().indexOf('/closed') > 0 ? false : true;
  $scope.changeStatusText = !$scope.onOpen ? 'Re open' : 'Close';
  $scope.changeStatusType = !$scope.onOpen ? 'reopen' : 'close';
  $scope.EVENT_TYPES = EVENT_TYPES;
  $scope.EVENT_OUTCOME = EVENT_OUTCOME;
  $scope.eventType = EVENT_TYPES.ALL_ORGANIZATIONS.CODE;
  $scope.eventsOrderBy = 'iso_create_date';
  $scope.isRouteLoading = true;
  $scope.summaryLoading = null;

  const d = new Date();
  $scope.date = d.setDate(d.getDate() - 14);
 
  const getEventsData = async ({
    start_date = null,
    end_date = null
  }) => {
    $scope.isRouteLoading = true;

    $scope.events = await getEvents({
      uid: $scope.eventType === EVENT_TYPES.MY_RFIS.CODE ? true : false,
      organization_id: $scope.eventType === EVENT_TYPES.MY_ORGANIZATION.CODE ? $scope.userInfo .organization_id : null,
      start_date: start_date || epicoreStartDate,
      end_date: end_date || moment().add(1, 'days').format('YYYY-MM-DD'),
      is_open: $scope.onOpen,
    });

    $scope.isRouteLoading = false;
    $scope.$digest();
  };

  $scope.getClosedEventsData = async () => {
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

    await getEventsData({
      start_date: start_date,
      end_date: end_date
    });
  };

  const checkEventsWithNoActivity = async () => {
    $scope.unclosed = $scope.events.filter(event => {
      return event.requester_id === $scope.userInfo .uid && parseInt(event.no_active_14_days) > 0;
    }).length;

    $scope.$digest();
  };

  const setTimeFilter = () => {
    $scope.timeFilterValues = getTimeFilterSelectValues(epicoreStartDate);
    $scope.timeFilterSelectedValue = $scope.timeFilterValues[0];
  };

  $scope.selectClosedEventsMonth = (month) => {
    $scope.timeFilterSelectedValue = month;
    $scope.getClosedEventsData();
  };

  $scope.getEvents = async () => {
    if (!$scope.onOpen) {
      $scope.getClosedEventsData();
    } else {
      await getEventsData({});
      checkEventsWithNoActivity();
    }
  };

  $scope.showEventSummary = async (event_id) => {
    $scope.summaryLoading = event_id;
    const event = $scope.events.find(event => event.event_id === event_id);
    
    const {
      phe_description: summary,
      title: event_title,
      outcome: event_outcome,
      action_date: event_action_date
    } = event;

    const eventEventSummaryData = await getEventSummary({
      event_id: event_id
    });

    const {
      phe_additional: more_info,
      source: event_source,
      details: event_source_details
    } = eventEventSummaryData;

    let source = '';
    if (event_source === EVENT_SOURCE.MEDIA_REPORT.CODE) {
      source = EVENT_SOURCE.MEDIA_REPORT.TEXT;
    } else if (event_source === EVENT_SOURCE.OFFICIAL_REPORT.CODE) {
      source = EVENT_SOURCE.OFFICIAL_REPORT.TEXT;
    } else if (event_source === EVENT_SOURCE.OTHER.CODE) {
      source = EVENT_SOURCE.OTHER.TEXT;
    }

    let outcome = EVENT_OUTCOME.PENDING.TEXT;
    if (event_outcome === EVENT_OUTCOME.VERIFIED_POSITIVE.CODE) {
      outcome = EVENT_OUTCOME.VERIFIED_POSITIVE.TEXT;
    } else if (event_outcome === EVENT_OUTCOME.VERIFIED_NEGATIVE.CODE) {
      outcome = EVENT_OUTCOME.VERIFIED_NEGATIVE.TEXT;
    } else if (event_outcome == EVENT_OUTCOME.UNVERIFIED.CODE) {
      outcome = EVENT_OUTCOME.UNVERIFIED.TEXT;
    } else if (event_outcome === EVENT_OUTCOME.UPDATED_POSITIVE.CODE) {
      outcome = EVENT_OUTCOME.UPDATED_POSITIVE.TEXT;
    } else if (event_outcome === EVENT_OUTCOME.UPDATED_NEGATIVE.CODE) {
      outcome = EVENT_OUTCOME.UPDATED_NEGATIVE.TEXT;
    }

    const event_info =
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

    $scope.modalTitle = 'Summary';
    $scope.modalBody = '';
    if (more_info) {
      $scope.modalBody =
        event_info +
        'RFI Closure Date: ' +
        event_action_date +
        '\r\n\r\n' +
        'PHE Description:\r\n' +
        summary +
        '\r\n\r\n' +
        'Additional Info:\r\n' +
        more_info;
    } else if (summary) {
      $scope.modalBody =
        event_info +
        'RFI Closure Date: ' +
        event_action_date +
        '\r\n\r\n' +
        'PHE Description:\r\n' +
        summary;
    }

    $scope.showSummaryModal = true;
    $scope.summaryLoading = null;
    $scope.$digest();
  };

  const init = () => {
    if (!$scope.onOpen) {
      setTimeFilter();
    }
    $scope.getEvents();
  };

  init();
 
};

EventsController3.$inject = ['$scope', '$location', 'epicoreMode' , 'epicoreStartDate'];

export default EventsController3;