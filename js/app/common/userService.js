import Cookies from 'js-cookie';

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

  return {
    getUser,
    isAuthenticated
  };
};

export { userService };
