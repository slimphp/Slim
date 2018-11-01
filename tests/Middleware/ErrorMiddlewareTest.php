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
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler;
use Slim\Middleware\ErrorMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Mocks\MockCustomException;
use Slim\Tests\Test;
use Closure;
use Error;

/**
 * Class ErrorMiddlewareTest
 * @package Slim\Tests\Middleware
 */
class ErrorMiddlewareTest extends Test
{
    public function testSetErrorHandler()
    {
        $app = new App();
        $callableResolver = $app->getCallableResolver();

        $mw = new RoutingMiddleware($app->getRouter());
        $app->add($mw);

        $exception = HttpNotFoundException::class;
        $handler = function () {
            $response = $this->createResponse(500);
            $response->getBody()->write('Oops..');
            return $response;
        };
        Closure::bind($handler, $this);

        $mw2 = new ErrorMiddleware($callableResolver, $this->responseFactory(), false, false, false);
        $mw2->setErrorHandler($exception, $handler);
        $app->add($mw2);

        $request = $this->createServerRequest('/foo/baz/');
        $response = $app->run($request, $this->responseFactory());

        $this->assertEquals('Oops..', (string) $response->getBody());
    }

    public function testSetDefaultErrorHandler()
    {
        $app = new App();
        $callableResolver = $app->getCallableResolver();

        $mw = new RoutingMiddleware($app->getRouter());
        $app->add($mw);

        $handler = function () {
            $response = $this->createResponse();
            $response->getBody()->write('Oops..');
            return $response;
        };
        Closure::bind($handler, $this);

        $mw2 = new ErrorMiddleware($callableResolver, $this->responseFactory(), false, false, false);
        $mw2->setDefaultErrorHandler($handler);
        $app->add($mw2);

        $request = $this->createServerRequest('/foo/baz/');
        $response = $app->run($request, $this->responseFactory());

        $this->assertEquals('Oops..', (string) $response->getBody());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSetDefaultErrorHandlerThrowsException()
    {
        $app = new App();
        $callableResolver = $app->getCallableResolver();

        $mw = new ErrorMiddleware($callableResolver, $this->responseFactory(), false, false, false);
        $mw->setDefaultErrorHandler('Uncallable');
        $mw->getDefaultErrorHandler();
    }

    public function testGetErrorHandlerWillReturnDefaultErrorHandlerForUnhandledExceptions()
    {
        $app = new App();
        $callableResolver = $app->getCallableResolver();

        $middleware = new ErrorMiddleware($callableResolver, $this->responseFactory(), false, false, false);
        $exception = MockCustomException::class;
        $handler = $middleware->getErrorHandler($exception);
        $this->assertInstanceOf(ErrorHandler::class, $handler);
    }

    /**
     * @requires PHP 7.0
     */
    public function testErrorHandlerHandlesThrowables()
    {
        $app = new App();
        $callableResolver = $app->getCallableResolver();

        $mw2 = function () {
            throw new Error('Oops..');
        };
        $app->add($mw2);

        $handler = function (ServerRequestInterface $request, $exception) {
            $response = $this->createResponse();
            $response->getBody()->write($exception->getMessage());
            return $response;
        };
        Closure::bind($handler, $this);

        $mw = new ErrorMiddleware($callableResolver, $this->responseFactory(), false, false, false);
        $mw->setDefaultErrorHandler($handler);
        $app->add($mw);

        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('...');
            return $response;
        });

        $request = $this->createServerRequest('/foo');
        $response = $app->run($request, $this->responseFactory());

        $this->assertEquals('Oops..', (string) $response->getBody());
    }
}
