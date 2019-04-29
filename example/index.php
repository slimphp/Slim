<?php

/**
 * Require the Slim Framework using Composer's autoloader
 * This example uses: composer require slim/slim:4.x-dev slim/psr7:dev-master slim/http
 */
require __DIR__ . '/../vendor/autoload.php';


/**
 * Import relevant classes
 */

use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Factory\BuildContainer;
use Slim\Factory\Builder;
use Slim\Factory\ServiceProvider;
use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Middleware\ErrorMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;

$provider = new ServiceProvider();
$container = new BuildContainer($provider);
$container->set(
    ResponseFactoryInterface::class,
    new DecoratedResponseFactory(new ResponseFactory(), new StreamFactory())
);
$builder = new Builder($container);
$app = $builder->getApp();

/**
 * Add middleware as required. This is a LIFO stack.
 * We recommend adding ErrorMiddleware at least
 */
$app->add(
    new ErrorMiddleware(
        $app->getCallableResolver(),
        $app->getResponseFactory(),
        true,
        true,
        true
    )
);

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
$app->run();
