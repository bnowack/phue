
let config = require('./webpack.config.base.js');
let webpack = require('webpack');

process.env.NODE_ENV = 'production';

config.plugins.push(
    new webpack.DefinePlugin({
        'process.env': {
            NODE_ENV: '"production"'
        }
    })
);
config.plugins.push(
    new webpack.optimize.UglifyJsPlugin({
        compress: {
            warnings: false
        }
    })
);

module.exports = config;
