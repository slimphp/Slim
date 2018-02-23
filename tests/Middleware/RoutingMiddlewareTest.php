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
use FastRoute\Dispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\DispatcherResults;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Middleware\RoutingMiddleware;
use Slim\Router;

class RoutingMiddlewareTest extends TestCase
{
    protected function getRouter()
    {
        $router = new Router();
        $router->map(['GET'], '/hello/{name}', null);

        return $router;
    }

    public function testRouteIsStoredOnSuccessfulMatch()
    {
        $router = $this->getRouter();
        $mw = new RoutingMiddleware($router);

        $uri = Uri::createFromString('https://example.com:443/hello/foo');
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, new Headers(), [], [], $body);
        $response = new Response();

        $next = function (ServerRequestInterface $req, ResponseInterface $res) {
            // route is available
            $route = $req->getAttribute('route');
            $this->assertNotNull($route);
            $this->assertEquals('foo', $route->getArgument('name'));

            // dispatcherResults is available
            $dispatcherResults = $req->getAttribute('dispatcherResults');
            $this->assertInstanceOf(DispatcherResults::class, $dispatcherResults);
            return $res;
        };
        Closure::bind($next, $this); // bind test class so we can test request object

        $result = $mw($request, $response, $next);
    }

    /**
     * @expectedException \Slim\Exception\HttpMethodNotAllowedException
     */
    public function testRouteIsNotStoredOnMethodNotAllowed()
    {
        $router = $this->getRouter();
        $mw = new RoutingMiddleware($router);

        $uri = Uri::createFromString('https://example.com:443/hello/foo');
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('POST', $uri, new Headers(), [], [], $body);
        $response = new Response();

        $next = function (ServerRequestInterface $req, ResponseInterface $res) {
            // route is not available
            $route = $req->getAttribute('route');
            $this->assertNull($route);

            // dispatcherResults is available
            $dispatcherResults = $req->getAttribute('dispatcherResults');
            $this->assertInstanceOf(DispatcherResults::class, $dispatcherResults);
            $this->assertEquals(Dispatcher::METHOD_NOT_ALLOWED, $dispatcherResults->getRouteStatus());

            return $res;
        };
        Closure::bind($next, $this); // bind test class so we can test request object
        $result = $mw($request, $response, $next);
    }
}
