<?php
/**
 * Load all classes at once in order to improve performance.
 */

$slim_root = dirname(__FILE__) . '/';

require $slim_root . 'Exception/Pass.php';
require $slim_root . 'Exception/RequestSlash.php';
require $slim_root . 'Exception/Stop.php';
require $slim_root . 'Http/Cookie.php';
require $slim_root . 'Http/CookieJar.php';
require $slim_root . 'Http/Request.php';
require $slim_root . 'Http/Response.php';
require $slim_root . 'Http/Uri.php';
require $slim_root . 'Log.php';
require $slim_root . 'Logger.php';
require $slim_root . 'Route.php';
require $slim_root . 'Router.php';
require $slim_root . 'Session/Flash.php';
require $slim_root . 'Session/Handler/Cookies.php';
require $slim_root . 'Session/Handler.php';
require $slim_root . 'Slim.php';
require $slim_root . 'View.php';
