const EpicoreCacheService = () => {
  let sharedScopes = {};
  sharedScopes.memberPortalInfoAll = [];
  sharedScopes.memberPortalInfoPastYear = [];
  sharedScopes.memberPortalInfoPastQuarter = [];

  return {
    clear: function() {
      sharedScopes = {};
    },
    // Setters
    setMemberPortalInfoAll: function(memInfo) {
      return (sharedScopes.memberPortalInfoAll = memInfo);
    },
    setMemberPortalInfoPastYear: function(memInfo) {
      return (sharedScopes.memberPortalInfoPastYear = memInfo);
    },
    setMemberPortalInfoPastQuarter: function(memInfo) {
      return (sharedScopes.memberPortalInfoPastQuarter = memInfo);
    },

    // Getters
    getMemberPortalInfoAll: function() {
      return sharedScopes.memberPortalInfoAll;
    },
    getMemberPortalInfoPastYear: function() {
      return sharedScopes.memberPortalInfoPastYear;
    },
    getMemberPortalInfoPastQuarter: function() {
      return sharedScopes.memberPortalInfoPastQuarter;
    },
  };
};

export default EpicoreCacheService;
