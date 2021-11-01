/**
 * Created by jeffandre on 4/5/17.
 *
 * Epicore App Config
 */

const epicore_config = {};

// app version
// vers - 1 for version 1.0
// vers - 2 for version 2.0
epicore_config.vers = '2';

//As of 2021-03-02 using all data
epicore_config.V1START_DATE = '2015-01-01'; //this is to be used only for the member portal only
epicore_config.V2START_DATE = '2017-10-30';

epicore_config.EPICORE_START_DATE = epicore_config.V2START_DATE;

// app_mode settings to select web or mobile app
// mobile_prod - for mobile app with production backend
// mobile_dev - for mobile app with dev backend
// mobile_jandre - for mobile app with jandre's sandbox backend
// web - for web app (production, dev, and sandboxes)
epicore_config.app_mode = 'web';

// Push notifications Sender Id for Android devices
epicore_config.android_senderId = '67299923213';

epicore_config.API = {
  EVENTS_v3: 'scripts/EventsAPI3.php',
  EVENTS_v2: 'scripts/EventsAPI2.php',
  AUTH: 'scripts/auth.php'
};
