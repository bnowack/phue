<?php

// Ensure time() is E_STRICT-compliant
date_default_timezone_set(@date_default_timezone_get());

// Define app path constant (root is 3 hops up from `/vendor/bnowack/phue/index.php`)
if (!defined("PHUE_APP_DIR")) {
    define("PHUE_APP_DIR", dirname(dirname(dirname(__DIR__))) . '/');
}

// Define source path constant (`./src/` relative to this file)
if (!defined("PHUE_SRC_DIR")) {
    define("PHUE_SRC_DIR", __DIR__ . '/src/');
}

// Include autoloader
require_once PHUE_APP_DIR . 'vendor/autoload.php';

// Create and start the application
$app = new Phue\Application\Application();
$app['debug'] = false;
$app['config.files'] = [
    PHUE_SRC_DIR . 'base-config.json', // phue base config
    PHUE_SRC_DIR . 'base-routes.json', // phue base routes
    PHUE_APP_DIR . 'config/app-config.json', // app config
    PHUE_APP_DIR . 'config/app-routes.json' // app routes
];
$app->run();
