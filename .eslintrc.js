module.exports = {
  'env': {
    'browser': true,
    'es2021': true,
  },
  'extends': [
    // 'plugin:react/recommended',
  ],
  'parserOptions': {
    'ecmaFeatures': {
      'jsx': true,
    },
    'ecmaVersion': 12,
    'sourceType': 'module',
  },
  'plugins': [
    // 'react',
  ],
  'rules': {
    'semi': ['error', 'always'],
    'quotes': ['error', 'single'],
    'comma-style': [2, 'last'],
    'indent': [2, 2]
  },
};
