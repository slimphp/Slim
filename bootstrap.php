<?php

/**
 * Step 1: Require the Slim PHP 5 Framework
 *
 * If using the default file layout, the `slim/` directory
 * will already be on your include path. If you move the `slim/`
 * directory elsewhere, ensure that it is added to your include path
 * or update this file path as needed.
 */
require 'slim/Slim.php';


/**
 * Step 2: Initialize the Slim application
 *
 * Here we initialize the Slim application with its default settings.
 * However, we could also pass a key-value array of settings.
 * Refer to the online documentation for available settings.
 */
Slim::init();


/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, and `Slim::delete`
 * is an anonymous function. If you are using PHP < 5.3, the
 * second argument should be any variable that returns `true` for
 * `is_callable()`. An example GET route for PHP < 5.3 is:
 *
 * Slim::get('/hello/:name', 'myFunction');
 * function myFunction($name) { echo "Hello, $name"; }
 *
 * The routes below work with PHP >= 5.3.
 */

//GET route
Slim::get('/', function () {
    Slim::render('index.php');
});

//POST route
Slim::post('/post', function () {
    echo 'This is a POST route';
});

//PUT route
Slim::put('/put', function () {
    echo 'This is a PUT route';
});

//DELETE route
Slim::delete('/delete', function () {
    echo 'This is a DELETE route';
});

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This is responsible for executing
 * the Slim application using the settings and routes defined above.
 */
Slim::run();

?>