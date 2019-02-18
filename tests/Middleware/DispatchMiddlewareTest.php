<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\CallableResolver;
use Slim\Middleware\DispatchMiddleware;
use Slim\MiddlewareRunner;
use Slim\Router;
use Slim\RoutingResults;
use Slim\Tests\TestCase;

class DispatchMiddlewareTest extends TestCase
{
    public function testRoutingIsPerformedIfRoutingResultsAreUnavailable()
    {
        $handler = (function (ServerRequestInterface $request, ResponseInterface $response) {
            $routingResults = $request->getAttribute('routingResults');
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            return $response;
        })->bindTo($this);

        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $router = new Router($responseFactory, $callableResolver);
        $router->map(['GET'], '/hello/{name}', $handler);

        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'GET');
        $mw = new DispatchMiddleware($router);

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->run($request);
    }
}
