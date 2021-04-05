const path = require('path');

module.exports = {
  entry: './js/app/app.js',
  module: {
    rules: [
      {
        test: /\.(js)$/,
        exclude: /node_modules/,
        use: ['babel-loader']
      }
    ]
  },
  output: {
    path: path.resolve(__dirname, 'js/dist'),
    filename: 'app.bundle.js',
  },
};