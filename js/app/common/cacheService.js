const cacheService = () => {
  const cache = {};
  cache.data = {};
  cache.data.memberPortalInfoAll = [];
  cache.data.memberPortalInfoPastYear = [];
  cache.data.memberPortalInfoPastQuarter = [];

  cache.data.requests = [];

  const clearMemPortalCache = () => {
    cache.data = {};
    cache.data.memberPortalInfoAll = [];
    cache.data.memberPortalInfoPastYear = [];
    cache.data.memberPortalInfoPastQuarter = [];
  };

  // Setters
  const setMemberPortalInfoAll = (memInfo) => {
    return (cache.data.memberPortalInfoAll = memInfo);
  };
  const setMemberPortalInfoPastYear = (memInfo) => {
    return (cache.data.memberPortalInfoPastYear = memInfo);
  };
  const setMemberPortalInfoPastQuarter = (memInfo) => {
    return (cache.data.memberPortalInfoPastQuarter = memInfo);
  };

  // setRequestCache - params are for POST request only.
  const setRequestCache = ({ url, params, data }) => {
    if (cache.data.requests.findIndex(item => {
      return item.url === url && item.params === params;
    }) !== -1) {
      return;
    }

    cache.data.requests.push({
      url: url,
      params: params,
      data: data
    });
  };

  // Getters
  const getMemberPortalInfoAll = () => {
    return [...cache.data.memberPortalInfoAll];
  };
  const getMemberPortalInfoPastYear = () => {
    return [...cache.data.memberPortalInfoPastYear];
  };
  const getMemberPortalInfoPastQuarter = () => {
    return [...cache.data.memberPortalInfoPastQuarter];
  };

  // getRequestCache - params are for POST request only.
  const getRequestCache = ({ url, params }) => {
    const cachedRequest = cache.data.requests.find(item => {
      return item.url === url && item.params === params;
    });
    if (cachedRequest) {
      return cachedRequest.data;
    }
    return null;
  };

  return {
    clearMemPortalCache,
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

export { cacheService };
