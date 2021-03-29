const initSiteHeaderDirective = (app) => {
  app.directive('siteHeader', function() {
    return {
      restrict: 'E',
      template:
        '<button class="btn btn-default"><i class="fa fa-arrow-circle-left"></i> {{back}} to Your EpiCore Dashboard</button>',
      scope: {
        back: '@back',
        icons: '@icons',
      },
      link: function(scope, element) {
        $(element[0]).on('click', function() {
          history.back();
          scope.$apply();
        });
      },
    };
  });
};

export default initSiteHeaderDirective;
