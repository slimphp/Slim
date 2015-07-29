<?php

// Set timezone
date_default_timezone_set('America/New_York');

// Prevent session cookies
ini_set('session.use_cookies', 0);



// Enable Composer autoloader
/** @var \Composer\Autoload\ClassLoader $autoloader */
$autoloader = require dirname(__DIR__) . '/vendor/autoload.php';

// Register test classes
$autoloader->addPsr4('Slim\Tests\\', __DIR__);
