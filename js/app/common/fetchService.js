import { cacheService } from '@/common/cacheService';
import { Modal } from '@/common/modal';

const { setRequestCache, getRequestCache } = cacheService();
const { showModal } = Modal();

const fetchService = () => {

  const getErrorMesages = async () => {
    if (!epicore_config.errorMessages) {
      const errorMessages = await fetch('constants/error-messages.json');
      epicore_config.errorMessages = await errorMessages.json();
    } 
  };

  const fetchGet = async ({ url, params }) => {
    await getErrorMesages();
    
    if (params) {
      const urlParams = new URLSearchParams(params);
      url = `${url}?${urlParams}`;
    }

    try {
      const response =  await fetch(url, {
        method: 'GET'
      });

      const status = response.status;

      if (status !== 200) {
        throw new Error(`${response.status} ${response.statusText}`);
      }

      const data = await response.json();

      if (data.error) {
        showModal({
          id: 'error_message',
          header: epicore_config.errorMessages.header,
          message: data.error_message,
          details: data.error_details
        });
        return [];
      }

      setRequestCache({
        url: url,
        data: data
      });

      return data;

    } catch (error) {
      showModal({
        id: 'error_message',
        header: epicore_config.errorMessages.header,
        message: params && params.action ? epicore_config.errorMessages[params.action] : epicore_config.errorMessages['default'],
        details: error
      });
      return [];
    } 
  };

  return {
    fetchGet
  };
};

export { fetchService };
