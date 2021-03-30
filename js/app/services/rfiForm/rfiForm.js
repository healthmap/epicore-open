const RfiForm = () => {
  // questions form variables object (persistance)
  const questions = {};
  return {
    clear: function() {
      for (const member in questions) delete questions[member];
    },
    get: function() {
      return questions;
    },
  };
};

export default RfiForm;
