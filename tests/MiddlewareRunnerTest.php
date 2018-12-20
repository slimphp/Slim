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
use RuntimeException;
use Slim\Middleware\LegacyMiddlewareWrapper;
use Slim\MiddlewareRunner;

/**
 * Class MiddlewareRunnerTest
 * @package Slim\Tests
 */
class MiddlewareRunnerTest extends TestCase
{
    public function testGetMiddleware()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };

        $responseFactory = $this->getResponseFactory();
        $mw = new LegacyMiddlewareWrapper($callable, $responseFactory);

        $middleware = [$mw];
        $middlewareRunner = new MiddlewareRunner($middleware);

        $this->assertEquals($middleware, $middlewareRunner->getMiddleware());
    }

    public function testSetMiddleware()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };

        $responseFactory = $this->getResponseFactory();
        $mw = new LegacyMiddlewareWrapper($callable, $responseFactory);

        $middleware = [$mw];
        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->setMiddleware($middleware);

        $this->assertEquals($middleware, $middlewareRunner->getMiddleware());
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
     * For PSR-7 middleware use the `LegacyMiddlewareWrapper` class.
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
}
