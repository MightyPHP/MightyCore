<?php

use MightyCore\Vault\SessionManager;
use MightyCore\Http\Request;

define("DOC_ROOT", __DIR__ . "/../../../../../");
require __DIR__ . '/../../../../autoload.php';

// Utilities Path
define("UTILITY_PATH", DOC_ROOT . 'Utilities');
// Database Path
define("DATABASE_PATH", DOC_ROOT . 'Database');

/**
 * Require the helpers
 */
require "helpers.php";

/**
 * This part loads the .env values into putenv
 * Critical to run first, else env() method will not return any value
 */
if (file_exists(DOC_ROOT . ".env")) {
    $envFile = fopen(DOC_ROOT . ".env", "r");
    $contents = fread($envFile, filesize(DOC_ROOT . ".env"));
    $contents = explode("\n", $contents);
    foreach ($contents as $key => $value) {
        if (!empty($value)) {
            putenv($value);
        }
    }
    fclose($envFile);
}

define('SECURITY_SESSION_TIMEOUT', env('SECURITY_SESSION_TIMEOUT', 3600));

/**
 * To set default time zone
 */
if (env('DEFAULT_TIMEZONE') !== false) {
    date_default_timezone_set(env('DEFAULT_TIMEZONE'));
}

if (env('APP_ENV') == 'develop') {
    ini_set('display_errors', true);
    ini_set('html_errors', true);
    error_reporting(E_ALL);
} else if (env('APP_ENV') == 'production') {
    //Do not display error for security reasons
    ini_set('display_errors', false);
}

// Requests from the same server don't have a HTTP_ORIGIN header
if (!array_key_exists('HTTP_ORIGIN', $_SERVER) && !empty($_SERVER['SERVER_NAME'])) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}