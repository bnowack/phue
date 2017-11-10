<?php

/**
 * Server script for development and system tests
 */

// don't allow access to the router script during production
if (php_sapi_name() !== 'cli-server') {
    die('The router script is only available during development');
}

// Ensure time() is E_STRICT-compliant
date_default_timezone_set(@date_default_timezone_get());

// Define app path constant (root is 1 hop up from `dev/index.php`)
define("PHUE_APP_DIR", dirname(__DIR__) . '/');

// Define source path constant (directly in app dir during development)
define("PHUE_SRC_DIR", PHUE_APP_DIR . 'src/');

// Include autoloader
require_once PHUE_APP_DIR . 'vendor/autoload.php';

$asset = PHUE_APP_DIR . ltrim(preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']), '/');

// Serve static assets
if (is_file($asset)) {
    return false;
}

// Serve dynamic contents
$app = new Phue\Application\Application();
$app['debug'] = true;
$app['config.files'] = [
    PHUE_SRC_DIR . 'base-config.json',// phue base config
    PHUE_SRC_DIR . 'base-routes.json',// phue base routes
    PHUE_APP_DIR . 'dev/app-config.json',// app config during dev
    PHUE_APP_DIR . 'dev/app-routes.json'// app routes during dev
];
$app->run();
