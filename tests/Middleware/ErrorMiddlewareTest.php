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
use Error;
use Throwable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler;
use Slim\Middleware\ErrorMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Mocks\MockCustomException;
use Slim\Tests\TestCase;

/**
 * Class ErrorMiddlewareTest
 * @package Slim\Tests\Middleware
 */
class ErrorMiddlewareTest extends TestCase
{
    public function testSetErrorHandler()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
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

        $mw2 = new ErrorMiddleware($callableResolver, $this->getResponseFactory(), false, false, false);
        $mw2->setErrorHandler($exception, $handler);
        $app->add($mw2);

        $request = $this->createServerRequest('/foo/baz/');
        $app->run($request);

        $this->expectOutputString('Oops..');
    }

    public function testSetDefaultErrorHandler()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $callableResolver = $app->getCallableResolver();

        $mw = new RoutingMiddleware($app->getRouter());
        $app->add($mw);

        $handler = function () {
            $response = $this->createResponse();
            $response->getBody()->write('Oops..');
            return $response;
        };
        Closure::bind($handler, $this);

        $mw2 = new ErrorMiddleware($callableResolver, $this->getResponseFactory(), false, false, false);
        $mw2->setDefaultErrorHandler($handler);
        $app->add($mw2);

        $request = $this->createServerRequest('/foo/baz/');
        $app->run($request);

        $this->expectOutputString('Oops..');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSetDefaultErrorHandlerThrowsException()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $callableResolver = $app->getCallableResolver();

        $mw = new ErrorMiddleware($callableResolver, $this->getResponseFactory(), false, false, false);
        $mw->setDefaultErrorHandler('Uncallable');
        $mw->getDefaultErrorHandler();
    }

    public function testGetErrorHandlerWillReturnDefaultErrorHandlerForUnhandledExceptions()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $callableResolver = $app->getCallableResolver();

        $middleware = new ErrorMiddleware($callableResolver, $this->getResponseFactory(), false, false, false);
        $exception = MockCustomException::class;
        $handler = $middleware->getErrorHandler($exception);
        $this->assertInstanceOf(ErrorHandler::class, $handler);
    }

    public function testErrorHandlerHandlesThrowables()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $callableResolver = $app->getCallableResolver();

        $mw2 = function () {
            throw new Error('Oops..');
        };
        $app->add($mw2);

        $handler = function (ServerRequestInterface $request, ResponseInterface $response, Throwable $exception) {
            $response = $this->createResponse();
            $response->getBody()->write($exception->getMessage());
            return $response;
        };
        Closure::bind($handler, $this);

        $mw = new ErrorMiddleware($callableResolver, $this->getResponseFactory(), false, false, false);
        $mw->setDefaultErrorHandler($handler);
        $app->add($mw);

        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('...');
            return $response;
        });

        $request = $this->createServerRequest('/foo');
        $app->run($request);

        $this->expectOutputString('Oops..');
    }
}
