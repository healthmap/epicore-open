import initChosenDirective from './chosen/chosenDirective.js';
import initMagnificPopupDirective from './magnificPopup/magnificPopupDirective.js';
import initModalDirective from './modal/modalDirective.js';
import initMyYoutubeDirective from './myYoutube/myYoutubeDirective.js';
import initSiteHeaderDirective from './siteHeader/siteHeaderDirective.js';

const initDirectives = (app) => {
  initChosenDirective(app);
  initMagnificPopupDirective(app);
  initModalDirective(app);
  initMyYoutubeDirective(app);
  initSiteHeaderDirective(app);
};

export default initDirectives;

