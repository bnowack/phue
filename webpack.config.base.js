let fs = require('fs-extra');
let path = require('path');
let CleanObsoleteChunks = require('webpack-clean-obsolete-chunks');

fs.emptyDir('./dist');

module.exports = {
    entry: {
        app: './src/Phue/Application/app.js'
    },
    output: {
        path: path.resolve(__dirname, './dist'),
        publicPath: 'dist/',
        filename: '[name]-bundle.js',
        chunkFilename: '[name]-bundle.[chunkhash:4].js'
    },
    plugins: [
        new CleanObsoleteChunks({verbose: false})
    ],
    module: {
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
                test: /\.(png|jpg|gif|svg)$/,
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
