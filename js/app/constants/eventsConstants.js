export const EVENT_TYPES = {
  MY_RFIS : {
    CODE: 'MR',
    TEXT: 'My RFIs'
  },
  MY_ORGANIZATION: {
    CODE: 'MO',
    TEXT: 'My Organization'
  },
  ALL_ORGANIZATIONS: {
    CODE: 'AO',
    TEXT: 'All Organizations'
  }
};

export const EVENT_SOURCE = {
  MEDIA_REPORT: {
    CODE: 'MR',
    TEXT: 'Media Report'
  },
  OFFICIAL_REPORT: {
    CODE: 'OR',
    TEXT: 'Official Report'
  },
  OTHER: {
    CODE: 'OC',
    TEXT: 'Other communication'
  }
};

export const EVENT_OUTCOME = {
  PENDING: {
    CODE: 'PE',
    TEXT: 'Pending'
  },
  VERIFIED_POSITIVE: {
    CODE: 'VP',
    TEXT: 'Verified (positive)',
    TEXT_SHORT: 'Verified (+)'
  },
  VERIFIED_NEGATIVE: {
    CODE: 'VN',
    TEXT: 'Verified (negative)',
    TEXT_SHORT: 'Verified (-)'
  },
  UNVERIFIED: {
    CODE: 'UV',
    TEXT: 'Unverified'
  },
  UPDATED_POSITIVE: {
    CODE: 'UP',
    TEXT: 'Updated (positive)',
    TEXT_SHORT: 'Updated (+)'
  },
  UPDATED_NEGATIVE: {
    CODE: 'NU',
    TEXT: 'Updated (negative)',
    TEXT_SHORT: 'Updated (-)'
  }
};
