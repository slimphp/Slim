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
 * The last two routes demonstrate how to use named routes, the `urlFor` helper,
 * route parameter conditions, and optional route segments.
 *
 * The routes below work with PHP >= 5.3.
 */

//GET route
Slim::get('/', function () {
	Slim::render('index.php');
});

//POST route
Slim::post('/post', function () {
	echo '<p>Here are the details about your POST request:</p>';
	print_r(Slim::request());
});

//PUT route
Slim::put('/put', function () {
	echo '<p>Here are the details about your PUT request:</p>';
	print_r(Slim::request());
});

//DELETE route
Slim::delete('/delete', function () {
	echo '<p>Here are the details about your DELETE request:</p>';
	print_r(Slim::request());
});

//Named GET route with URL parameter conditions
Slim::get('/hello/:name', function ($name) {
	echo "<p>Hello, $name!</p>";
	echo "<p>This route using name \"Bob\" instead of \"$name\" would be: " . Slim::urlFor('hello', array('name' => 'Bob')) . '</p>';
})->name('hello')->conditions(array('name' => '\w+'));

//GET route with optional URL segments
Slim::get('/archive/:year(/:month(/:day))', function ( $year, $month = 5, $day = 20 ) {
	echo "<p>The date is: $month/$day/$year</p>";
});

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This is responsible for executing
 * the Slim application using the settings and/or routes defined above.
 */
Slim::run();

?>