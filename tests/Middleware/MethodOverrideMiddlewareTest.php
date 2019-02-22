<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Middleware;

use Slim\Middleware\ClosureMiddleware;
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
        $responseFactory = $this->getResponseFactory();
        $callable = (function ($request, $handler) use ($responseFactory) {
            $this->assertEquals('PUT', $request->getMethod());
            return $responseFactory->createResponse();
        })->bindTo($this);

        $mw = new ClosureMiddleware($callable);
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
        $responseFactory = $this->getResponseFactory();
        $callable = (function ($request, $handler) use ($responseFactory) {
            $this->assertEquals('PUT', $request->getMethod());
            return $responseFactory->createResponse();
        })->bindTo($this);

        $mw = new ClosureMiddleware($callable);
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
        $responseFactory = $this->getResponseFactory();
        $callable = (function ($request, $handler) use ($responseFactory) {
            $this->assertEquals('DELETE', $request->getMethod());
            return $responseFactory->createResponse();
        })->bindTo($this);

        $mw = new ClosureMiddleware($callable);
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
        $responseFactory = $this->getResponseFactory();
        $callable = (function ($request, $handler) use ($responseFactory) {
            $this->assertEquals('POST', $request->getMethod());
            return $responseFactory->createResponse();
        })->bindTo($this);

        $mw = new ClosureMiddleware($callable);
        $mw2 = new MethodOverrideMiddleware();

        $request = $this->createServerRequest('/', 'POST');

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->add($mw2);
        $middlewareRunner->run($request);
    }
}
