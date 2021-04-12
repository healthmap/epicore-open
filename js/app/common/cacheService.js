const cacheService = () => {
  const sharedScopes = {};
  sharedScopes.memberPortalInfoAll = [];
  sharedScopes.memberPortalInfoPastYear = [];
  sharedScopes.memberPortalInfoPastQuarter = [];

  sharedScopes.requests = [];

  const clear = () => {
    sharedScopes = {};
  };

  // Setters
  const setMemberPortalInfoAll = (memInfo) => {
    return (sharedScopes.memberPortalInfoAll = memInfo);
  };
  const setMemberPortalInfoPastYear = (memInfo) => {
    return (sharedScopes.memberPortalInfoPastYear = memInfo);
  };
  const setMemberPortalInfoPastQuarter = (memInfo) => {
    return (sharedScopes.memberPortalInfoPastQuarter = memInfo);
  };

  // setRequestCache - params are for POST request only.
  const setRequestCache = ({url, params = null, data}) => {
    if (sharedScopes.requests.findIndex(item => {
      return item.url === url && item.params === params;
    }) !== -1) {
      return;
    }
    sharedScopes.requests.push({
      url: url,
      params: params,
      data: data
    });
  };

  // Getters
  const getMemberPortalInfoAll = () => {
    return [...sharedScopes.memberPortalInfoAll];
  };
  const getMemberPortalInfoPastYear = () => {
    return [...sharedScopes.memberPortalInfoPastYear];
  };
  const getMemberPortalInfoPastQuarter = () => {
    return [...sharedScopes.memberPortalInfoPastQuarter];
  };

  // getRequestCache - params are for POST request only.
  const getRequestCache = ({url, params = null}) => {
    const cachedRequest = sharedScopes.requests.find(item => {
      return item.url === url && item.params === params;
    });
    if (cachedRequest) {
      return cachedRequest.data;
    }
    return null;
  };

  return {
    clear,
    setMemberPortalInfoAll,
    setMemberPortalInfoPastYear,
    setMemberPortalInfoPastQuarter,
    setRequestCache,
    getMemberPortalInfoAll,
    getMemberPortalInfoPastYear,
    getMemberPortalInfoPastQuarter,
    getRequestCache
  };
};

export  { cacheService };
