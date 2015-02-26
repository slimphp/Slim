<?php
set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());

// Set timezone
date_default_timezone_set('America/New_York');

// Enable Composer autoloader
require dirname(__DIR__) . '/vendor/autoload.php';

//Register non-Slim autoloader
function customAutoLoader($class)
{
    $file = dirname(__FILE__) . '/' . $class . '.php';
    if (file_exists($file)) {
        require $file;
    } else {
        return;
    }
}
spl_autoload_register('customAutoLoader');

// Prevent session cookies
ini_set('session.use_cookies', 0);
