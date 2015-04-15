<?php
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader with:
 *
 *     require 'Slim/Autoloader.php';
 *     \Slim\Autoloader::register();
 */
require 'vendor/autoload.php';

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new \Slim\App();

$app->map(['GET', 'POST'], '/hello/{first}/{last}', function ($req, $res, $first, $last) {
    echo $this['router']->urlFor('testGet', ['first' => 'Josh', 'last' => 'Lockhart']);
    var_dump($first);
    var_dump($last);
})->setName('testGet');

$app->get('/etaghit', function ($request, $response, $args) {
    $response->withEtag('abc123');
    $response->write("Test body");

    return $response;
});

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, `Slim::patch`, and `Slim::delete`
 * is an anonymous function.
 */
// $app->get('/hello/:first/:last', function ($req, $res) {
//     $first = $req->getAttribute('first');
//     $last = $req->getAttribute('last');
//
//     echo "Hello, $first $last";
// });

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
