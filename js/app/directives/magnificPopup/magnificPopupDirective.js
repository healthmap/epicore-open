const initMagnificPopupDirective = (app) => {
  app.directive('magnificPopup', function() {
    return {
      restrict: 'A',
      scope: {},
      link: function($scope, element, attr) {
        const isSmallDevice = $(window).width() <= 1024;
        const activeLink = attr.magnificPopup;
        (isThirdPartyUrl = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/.test(
          activeLink,
        )),
        (isTephinet = 'https://www.tephinet.org'),
        (targetType = function(curTarget) {
          return curTarget || isSmallDevice ? '_system' : '_self';
        });

        if (isSmallDevice && /iPad|iPhone|iPod/.test(navigator.userAgent)) {
          // if(isThirdPartyUrl || attr.third){
          //     element[0].addEventListener('click', function(e){
          //         e.preventDefault();
          //         window.open(activeLink, targetType(attr.target));
          //         return false;
          //     });
          // }else{
          element.magnificPopup({
            fixedContentPos: true,
            callbacks: {
              beforeOpen: function() {
                if (activeLink == isTephinet) {
                  $.magnificPopup.open({
                    fixedContentPos: true,
                    items: {
                      src:
                        '<div id="popup-preloader">You are leaving EpiCore to visit a third-party. <a href="https://www.tephinet.org">Click to proceed</a></div>',
                      type: 'inline',
                    },
                  });
                } else {
                  $.magnificPopup.open({
                    fixedContentPos: true,
                    items: {
                      src:
                        '<div id="popup-preloader">Accessing an external resource, if it does not load, please close this window and try again</div>',
                      type: 'inline',
                    },
                  });

                  setTimeout(function() {
                    $.magnificPopup.close();

                    $.magnificPopup.open(
                      {
                        items: {
                          src: activeLink,
                        },
                        type: 'iframe',
                        fixedContentPos: true,
                        removalDelay: 300,
                        mainClass: 'mfp-fade',
                      },
                      0,
                    );
                  }, 4000);
                }
              },
            },
            // type: 'iframe',
            removalDelay: 300,
            mainClass: 'mfp-fade',
          });
          // }
        } else {
          element[0].addEventListener('click', function(e) {
            e.preventDefault();
            window.open(activeLink, targetType(attr.target));
            return false;
          });
        }
      },
    };
  });
};

export default initMagnificPopupDirective;
