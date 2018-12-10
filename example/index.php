<?php

/**
 * Require the Slim Framework using Composer's autoloader
 * This example uses: composer require slim/slim:4.x-dev slim/psr7:dev-master slim/http
 */
require __DIR__ . '/vendor/autoload.php';


/**
 * Import relevant classes
 */
use Slim\App;
use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Http\ServerRequest;
use Slim\Middleware\ErrorMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

/**
 * Create a PSR-17 response factory and instantiate our Slim appliciation ($app)
 */
$responseFactory = new DecoratedResponseFactory(new ResponseFactory(), new StreamFactory());

/**
 * Instantiate a Slim application
 *
 * This example instantiates a Slim application using its default settings. However, you may choose to configure
 * your Slim application now by passing an associative array of settings into the application constructor.
 */
$app = new App($responseFactory);


/**
 * Add middleware as required. This is a LIFO stack.
 * We recommend adding ErrorMiddleware at least
 */
$app->add(new ErrorMiddleware($app->getCallableResolver(), $responseFactory, true, true, true));


$app = new App();

/**
 * Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, `Slim::patch`, and `Slim::delete`
 * is an anonymous function.
 */
$app->get('/', function ($request, $response, $args) {
    $response->write("Welcome to Slim!");
    return $response;
});

$app->get('/hello[/{name}]', function ($request, $response, $args) {
    $response->write("Hello, " . $args['name']);
    return $response;
})->setArgument('name', 'World!');

/**
 * Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$request = new ServerRequest(ServerRequestFactory::createFromGlobals());
$app->run($request);
