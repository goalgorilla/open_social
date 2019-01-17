const path = require('path');

module.exports = {
  entry: './js/src/index.jsx',
  output: {
    filename: 'index.js',
    path: path.resolve(__dirname, 'js/dist'),
  },
  devtool: 'source-map',
  module: {
    rules: [
      {
        test: /\.m?jsx?$/,
        exclude: /node_modules\/(?!yoastseo\/)/,
        use: {
          loader: 'babel-loader',
        }
      }
    ]
  },
  resolve: {
    extensions: ['.wasm', '.mjs', '.js', '.json', '.jsx'],
  }
};
