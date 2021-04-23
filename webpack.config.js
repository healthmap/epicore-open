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
    filename: '[name].bundle.js',
    clean: true
  },
  devtool: 'source-map',
  resolve: {
    extensions: ['.js'],
    alias: {
      '@': path.resolve(__dirname, 'js/app')
    }
  }
};