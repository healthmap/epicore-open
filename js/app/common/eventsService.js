import { fetchService } from '@/common/fetchService';

const { fetchUrl } = fetchService();

const eventsService = () => {

  const getEvents = async ({
    uid,
    organization_id,
    start_date,
    end_date,
    is_open
  }) => { 
    const params = {
      action: 'get_events',
      cache: false
    };

    if (uid){
      params.uid = uid;
    }
    if (organization_id){
      params.organization_id = organization_id;
    }
    if (start_date){
      params.start_date = start_date;
    }
    if (end_date){
      params.end_date = end_date;
    }
    if (is_open){
      params.is_open = is_open;
    }

    const url = epicore_config.urlBase + epicore_config.API.EVENTS_v3;
    const options = {
      method: 'GET',
      cache: false
    };
    const response = await fetchUrl({ url, params, options });
    return response;
  };

  const getPublicEvents = async ({
    start_date,
    end_date
  }) => {
    const params = {
      action: 'get_public_events',
      cache: false
    };

    if (start_date){
      params.start_date = start_date;
    }
    if (end_date){
      params.end_date = end_date;
    }

    const url = epicore_config.urlBase + epicore_config.API.EVENTS_v3;
    const options = {
      method: 'GET',
      cache: false
    };
    const response = await fetchUrl({ url, params, options });
    return response;
  };

  const getEventSummary = async ({event_id}) => {
    const params = {
      action: 'get_event_summary',
      event_id: event_id,
      cache: false
    };

    const url = epicore_config.urlBase + epicore_config.API.EVENTS_v3;
    const options = {
      method: 'GET',
      cache: false
    };
    const response = await fetchUrl({ url, params, options });
    return response;
  };

  const getTimeFilterSelectValues = (epicoreStartDate) => {
    // get list of months for selecting event month
    const dateStart = moment(epicoreStartDate);
    const dateEnd = moment(); // now
    const timeValues = [];
    let i = 0;
    while (dateEnd > dateStart || dateStart.format('M') === dateEnd.format('M')) {
      timeValues.push({
        name: dateStart.format('YYYY-MMMM'),
        value: dateStart.format('YYYY-MM'),
      });
      dateStart.add(1, 'month');
      i++;
    }

    timeValues.push({name: 'All', value: 'all'});
    timeValues.push({name: 'Most Recent', value: 'recent'});
    return timeValues.reverse();
  };

  return {
    getEvents,
    getPublicEvents,
    getEventSummary,
    getTimeFilterSelectValues
  };
};

export { eventsService };
