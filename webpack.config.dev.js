
let config = require('./webpack.config.base.js');

process.env.NODE_ENV = 'development';

config.devtool = '#source-map';

module.exports = config;
