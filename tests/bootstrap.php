<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

use Composer\Autoload\ClassLoader;

// Set timezone
date_default_timezone_set('America/New_York');

// Prevent session cookies
ini_set('session.use_cookies', 0);

/** @var ClassLoader $autoloader */
$autoloader = require dirname(__DIR__) . '/vendor/autoload.php';
$autoloader->addPsr4('Slim\Tests\\', __DIR__);

require dirname(__FILE__) . '/getallheaders.php';
