import { fetchService } from '@/common/fetchService';

const { fetchUrl } = fetchService();

const HttpServiceInterceptor = ($rootScope, $timeout) => {
  return {
    http: (attrs) => {
      return new Promise(async (resolve, reject) => {
        const options = {
          method: attrs.method,
          cache: false
        };
        const response = await fetchUrl({
          url: attrs.url,
          params: attrs.data,
          options
        });

        const output = {
          data: response
        };

        if (!response || response.error) {
          reject(output);
        } else {
          resolve(output);
        }

        $timeout(() => {
          $rootScope.$digest();
        });
      });
    }
  };
};

HttpServiceInterceptor.$inject = ['$rootScope', '$timeout'];

export default HttpServiceInterceptor;
