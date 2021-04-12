import { cacheService } from '@/common/cacheService';

const { setRequestCache, getRequestCache } = cacheService();

const fetchService = () => {

  const fetchGet = async (url) => {
    const cachedRequest = getRequestCache({
      url: url
    });

    if (cachedRequest) {
      return cachedRequest;
    }

    const response =  await fetch(url, {
      method: 'GET'
    }).catch(err => console.log(err));

    try {
      const data = await response.json();

      if (data.error) {
        const errorMessage = `${data.error_message} Details: ${data.error_details}`;
        alert(errorMessage);
        return [];
      }

      setRequestCache({
        url: url,
        data: data
      });

      return data;

    } catch (error) {
      alert('error', error);
      return [];
    }
  };

  return {
    fetchGet
  };
};

export { fetchService };
