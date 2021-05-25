import { Modal } from '@/common/modal';

const { showModal } = Modal();

const fetchService = () => {

  const getErrorMesages = async () => {
    if (!epicore_config.errorMessages) {
      const errorMessages = await fetch('constants/error-messages.json');
      epicore_config.errorMessages = await errorMessages.json();
    } 
  };

  const fetchUrl = async ({ url, params, options }) => {    
    if (params && options.method === 'GET') {
      const urlParams = new URLSearchParams(params);
      url = `${url}?${urlParams}`;
    }

    await getErrorMesages();

    const fetchOptions = {
      headers: {
        'Content-Type': 'application/json'
      },
      method: options.method
    };

    if (options.method === 'POST' && params) {
      fetchOptions.body = JSON.stringify(params);
    }

    const response =  await fetch(url, fetchOptions);
    let responseClone = response.clone();

    try {
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
        return {
          error: true,
          message: data.error_message,
          details: data.error_details
        };
      }

      responseClone = null;
      return data;

    } catch (error) {
      const message = params && params.action && epicore_config.errorMessages[params.action] ? epicore_config.errorMessages[params.action] : epicore_config.errorMessages['default'];
      const details = await responseClone.text();
      showModal({
        id: 'error_message',
        header: epicore_config.errorMessages.header,
        message: message,
        details: details
      });
      return {
        error: true,
        message: data.error_message,
        details: data.error_details
      };
    } 
  };

  return {
    fetchUrl,
  };
};

export { fetchService };
