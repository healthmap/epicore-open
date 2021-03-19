services.factory("rfiForm", function () {
  // questions form variables object (persistance)
  var questions = {};
  return {
    clear: function () {
      for (var member in questions) delete questions[member];
    },
    get: function () {
      return questions;
    },
  };
});
