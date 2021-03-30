const initChosenDirective = (app) => {
  app.directive('chosen', function($timeout) {
    const linker = function(scope, element, attr) {
      $timeout(
        function() {
          element.chosen();
        },
        0,
        false,
      );
    };

    return {
      restrict: 'A',
      link: linker,
    };
  });
};

export default initChosenDirective;
