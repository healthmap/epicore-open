const initMyYoutubeDirective = (app) => {
  app.directive('myYoutube', function($sce) {
    return {
      restrict: 'EA',
      scope: {code: '='},
      replace: true,
      template:
        '<div style="height:550px; width: 980px;"><iframe style="overflow:hidden;height:100%;width:100%" width="100%" height="100%" src="{{url}}" frameborder="0" allowfullscreen></iframe></div>',
      link: function(scope) {
        scope.$watch('code', function(newVal) {
          if (newVal) {
            scope.url = $sce.trustAsResourceUrl(
              'https://www.youtube.com/embed/' + newVal + '?vq=hd720',
            );
          }
        });
      },
    };
  });
};

export default initMyYoutubeDirective;
