<?php

// Ensure time() is E_STRICT-compliant
date_default_timezone_set(@date_default_timezone_get());

// Define app path constant (root is 2 hops up from `test/phpspec/bootstrap.php`)
define("PHUE_APP_DIR", dirname(dirname(__DIR__)) . '/');

// Define source path constant (directly in app dir during development)
define("PHUE_SRC_DIR", PHUE_APP_DIR . 'src/');

// Include autoloader
require_once PHUE_APP_DIR . 'vendor/autoload.php';
