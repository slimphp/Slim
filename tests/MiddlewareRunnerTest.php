<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;
use RuntimeException;
use Slim\Middleware\ClosureMiddleware;
use Slim\MiddlewareRunner;
use SplObjectStorage;

/**
 * Class MiddlewareRunnerTest
 * @package Slim\Tests
 */
class MiddlewareRunnerTest extends TestCase
{
    public function testGetMiddleware()
    {
        $responseFactory = $this->getResponseFactory();
        $callable = function ($request, $handler) use ($responseFactory) {
            return $responseFactory->createResponse();
        };

        $mw = new ClosureMiddleware($callable);

        $middleware = [$mw];
        $middlewareRunner = new MiddlewareRunner($middleware);

        $this->assertEquals($middleware, $middlewareRunner->getMiddleware());
    }

    public function testSetMiddleware()
    {
        $responseFactory = $this->getResponseFactory();
        $callable = function ($request, $handler) use ($responseFactory) {
            return $responseFactory->createResponse();
        };

        $mw = new ClosureMiddleware($callable);

        $middleware = [$mw];
        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->setMiddleware($middleware);

        $this->assertEquals($middleware, $middlewareRunner->getMiddleware());
    }

    public function testSetStages()
    {
        $stages = new SplObjectStorage();

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->setStages($stages);

        $reflectionProperty = new ReflectionProperty(MiddlewareRunner::class, 'stages');
        $reflectionProperty->setAccessible(true);

        $this->assertEquals($stages, $reflectionProperty->getValue($middlewareRunner));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Middleware queue should not be empty.
     */
    public function testEmptyMiddlewarePipelineThrowsException()
    {
        $request = $this->createServerRequest('/');
        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->run($request);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage
     * All middleware should implement `MiddlewareInterface`.
     * For PSR-7 middleware use the `Psr7MiddlewareAdapter` class.
     */
    public function testMiddlewareNotImplementingInterfaceThrowsException()
    {
        $mw = function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };

        $request = $this->createServerRequest('/');
        $middlewareRunner = new MiddlewareRunner([$mw]);
        $middlewareRunner->run($request);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage
     * Middleware queue stages have not been set yet.
     * Please use the `MiddlewareRunner::run()` method.
     */
    public function testMiddlewareWithoutStagesBuiltThrowsException()
    {
        $request = $this->createServerRequest('/');
        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->handle($request);
    }
}
