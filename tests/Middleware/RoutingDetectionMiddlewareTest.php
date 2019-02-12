<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Middleware;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Middleware\RoutingDetectionMiddleware;
use Slim\MiddlewareRunner;
use Slim\Router;
use Slim\RoutingResults;
use Slim\Tests\TestCase;

class RoutingDetectionMiddlewareTest extends TestCase
{
    public function testRoutingIsPerformedIfRoutingResultsAreUnavailable()
    {
        $handler = function (ServerRequestInterface $request, ResponseInterface $response) {
            $routingResults = $request->getAttribute('routingResults');
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            return $response;
        };
        Closure::bind($handler, $this);

        $responseFactory = $this->getResponseFactory();
        $router = new Router($responseFactory);
        $router->map(['GET'], '/hello/{name}', $handler);

        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'GET');

        $deferredRouterResolver = function () use ($router) {
            return $router;
        };
        $mw = new RoutingDetectionMiddleware($deferredRouterResolver);

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->run($request);
    }
}
