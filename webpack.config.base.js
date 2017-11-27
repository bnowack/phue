let fs = require('fs-extra');
let path = require('path');
let CleanObsoleteChunks = require('webpack-clean-obsolete-chunks');

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
        new CleanObsoleteChunks({verbose: false})
    ],
    module: {
        noParse: [
            /moment.js/ // avoid locales getting included
        ],
        loaders: [
            {
                test: /\.vue/,
                loader: 'vue-loader',
                options: {
                    loaders: {
                        scss: 'vue-style-loader!css-loader!sass-loader'
                    }
                }
            },
            {
                test: /\.js$/,
                loader: 'babel-loader',
                exclude: /node_modules/
            },
            {
                test: /\.css$/,
                loader: 'css-loader'
            },
            {
                test: /\.(png|jpg|gif|svg|woff)$/,
                loader: 'file-loader',
                options: {
                    name: '[path][name].[ext]?[hash:4]',
                    publicPath: './',
                    useRelativePath: false,
                    emitFile: false
                }
            }
        ]
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.js'
        }
    }
};
