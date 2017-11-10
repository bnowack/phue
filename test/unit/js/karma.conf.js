
module.exports = function(config) {
    config.set({
        basePath: '../../../',// path to repo root
        frameworks: ['jasmine'],
        files: [
            { pattern: 'test/**/*Spec.js', included: true, watched: true, served: true }
        ],
        client: {
            args: process.argv.slice(4),
            captureConsole: true
        },
        preprocessors: {
            'src/**/*.js': ['coverage']
        },
        reporters: ['junit', 'coverage'],
        junitReporter: {
            outputDir: 'test/reports/',
            outputFile: 'js-junit.xml',
            useBrowserName: false
        },
        coverageReporter: { 
            type : 'html',
            dir : 'test/reports/js-coverage/'
        },
        port: 9876,
        colors: true,
        browserConsoleLogOptions: {
            level: 'debug',
            format: '%T: %m',
            terminal: true
        },
        failOnEmptyTestSuite: false,
        logLevel: config.LOG_INFO, // config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
        urlRoot: "/__karma__/",
        proxies: {
            "/": "http://localhost:8888/"
        },
        browsers: ['PhantomJS'],
        autoWatch: true,
        singleRun: true
    });
};
