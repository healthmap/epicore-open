import Cookies from 'js-cookie';
import { fetchService } from '@/common/fetchService';
const { fetchUrl } = fetchService();

const userService = () => {
  const getUser = () => {

    if (Cookies.get('epiUserInfo')) {
      return JSON.parse(Cookies.get('epiUserInfo'));
    }
    return null;
  };

  const isAuthenticated = () => {
    return getUser() ? true : false;
  };

  const hasToken = async (user) => {
    if (user.token != undefined && user.token != null) {
      const url = epicore_config.urlBase + epicore_config.API.AUTH;
      const params = {
        token: user.token,
      };

      const options = {
        method: 'POST',
        cache: false
      };
      const response = await fetchUrl({ url, params, options });
      if (response) {
        return true;
      }
      return false;
    }
    else {
      //check if user is requester and has a ticketID
      if (user && user.role['roleName'] === 'requester' && user.ticket_id) {
        return true;
      }
    }
    return false;
  }

  return {
    getUser,
    isAuthenticated,
    hasToken
  };
};

export { userService };
