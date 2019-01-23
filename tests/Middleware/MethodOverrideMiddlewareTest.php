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
use Slim\Middleware\Psr7MiddlewareWrapper;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\MiddlewareRunner;
use Slim\Tests\TestCase;

/**
 * @covers \Slim\Middleware\MethodOverrideMiddleware
 */
class MethodOverrideMiddlewareTest extends TestCase
{
    public function testHeader()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            $this->assertEquals('PUT', $request->getMethod());
            return $response;
        };
        Closure::bind($callable, $this);

        $responseFactory = $this->getResponseFactory();
        $mw = new Psr7MiddlewareWrapper($callable, $responseFactory);
        $mw2 = new MethodOverrideMiddleware();

        $request = $this
            ->createServerRequest('/', 'POST')
            ->withHeader('X-Http-Method-Override', 'PUT');

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->add($mw2);
        $middlewareRunner->run($request);
    }

    public function testBodyParam()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            $this->assertEquals('PUT', $request->getMethod());
            return $response;
        };
        Closure::bind($callable, $this);

        $responseFactory = $this->getResponseFactory();
        $mw = new Psr7MiddlewareWrapper($callable, $responseFactory);
        $mw2 = new MethodOverrideMiddleware();

        $request = $this
            ->createServerRequest('/', 'POST')
            ->withParsedBody(['_METHOD' => 'PUT']);

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->add($mw2);
        $middlewareRunner->run($request);
    }

    public function testHeaderPreferred()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            $this->assertEquals('DELETE', $request->getMethod());
            return $response;
        };
        Closure::bind($callable, $this);

        $responseFactory = $this->getResponseFactory();
        $mw = new Psr7MiddlewareWrapper($callable, $responseFactory);
        $mw2 = new MethodOverrideMiddleware();

        $request = $this
            ->createServerRequest('/', 'POST')
            ->withHeader('X-Http-Method-Override', 'DELETE')
            ->withParsedBody((object) ['_METHOD' => 'PUT']);

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->add($mw2);
        $middlewareRunner->run($request);
    }

    public function testNoOverride()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            $this->assertEquals('POST', $request->getMethod());
            return $response;
        };
        Closure::bind($callable, $this);

        $responseFactory = $this->getResponseFactory();
        $mw = new Psr7MiddlewareWrapper($callable, $responseFactory);
        $mw2 = new MethodOverrideMiddleware();

        $request = $this->createServerRequest('/', 'POST');

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->add($mw2);
        $middlewareRunner->run($request);
    }
}
