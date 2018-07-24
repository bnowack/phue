const path = require('path');
const fs = require('fs-extra');
const VueLoaderPlugin = require('vue-loader/lib/plugin');

fs.emptyDir('./dist');

module.exports = {
    entry: {
        app: ['babel-regenerator-runtime', './src/Phue/Application/app.js']
    },
    output: {
        path: path.resolve(__dirname, './dist'),
        publicPath: 'dist/',
        filename: '[name]-bundle.js?[chunkhash:4]',
        chunkFilename: '[name]-bundle.js?[chunkhash:4]'
    },
    plugins: [
        new VueLoaderPlugin()
    ],
    module: {
        noParse: [
            /moment.js/ // avoid locales getting included
        ],
        rules: [
            {
                test: /\.vue/,
                use: 'vue-loader'
            },
            {
                test: /\.js$/,
                use: 'babel-loader',
                exclude: /node_modules/
            },
            {
                test: /\.css$/,
                use: [
                    'vue-style-loader',
                    'css-loader'
                ]
            },
            {
                test: /\.scss$/,
                use: [
                    'vue-style-loader',
                    'css-loader',
                    'sass-loader'
                ]
            },
            {
                test: /\.(png|jpg|gif|svg|woff)$/,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: '[path][name].[ext]?[hash:4]',
                            publicPath: './',
                            emitFile: false,
                            useRelativePath: false
                        }
                    }
                ]
            }
        ]
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.js'
        },
        extensions: ['.vue', '.js']
    }
};
