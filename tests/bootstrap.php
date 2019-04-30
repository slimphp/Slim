<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

// Set timezone
date_default_timezone_set('America/New_York');

// Prevent session cookies
ini_set('session.use_cookies', 0);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/getallheaders.php';
require __DIR__ . '/Assets/PhpFunctionOverrides.php';
require __DIR__ . '/Assets/PhpHttpFunctionOverrides.php';
