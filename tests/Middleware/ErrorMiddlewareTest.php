<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use Error;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler;
use Slim\Middleware\ErrorMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Mocks\MockCustomException;
use Slim\Tests\TestCase;

class ErrorMiddlewareTest extends TestCase
{
    private function getMockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    public function testSetErrorHandler()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $callableResolver = $app->getCallableResolver();
        $logger = $this->getMockLogger();

        $routingMiddleware = new RoutingMiddleware(
            $app->getRouteResolver(),
            $app->getRouteCollector()->getRouteParser()
        );
        $app->add($routingMiddleware);

        $exception = HttpNotFoundException::class;
        $handler = (function () {
            $response = $this->createResponse(500);
            $response->getBody()->write('Oops..');
            return $response;
        })->bindTo($this);

        $errorMiddleware = new ErrorMiddleware(
            $callableResolver,
            $this->getResponseFactory(),
            false,
            false,
            false,
            $logger
        );
        $errorMiddleware->setErrorHandler($exception, $handler);
        $app->add($errorMiddleware);

        $request = $this->createServerRequest('/foo/baz/');
        $app->run($request);

        $this->expectOutputString('Oops..');
    }

    public function testSetDefaultErrorHandler()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $callableResolver = $app->getCallableResolver();
        $logger = $this->getMockLogger();

        $routingMiddleware = new RoutingMiddleware(
            $app->getRouteResolver(),
            $app->getRouteCollector()->getRouteParser()
        );
        $app->add($routingMiddleware);

        $handler = (function () {
            $response = $this->createResponse();
            $response->getBody()->write('Oops..');
            return $response;
        })->bindTo($this);

        $errorMiddleware = new ErrorMiddleware(
            $callableResolver,
            $this->getResponseFactory(),
            false,
            false,
            false,
            $logger
        );
        $errorMiddleware->setDefaultErrorHandler($handler);
        $app->add($errorMiddleware);

        $request = $this->createServerRequest('/foo/baz/');
        $app->run($request);

        $this->expectOutputString('Oops..');
    }

    public function testSetDefaultErrorHandlerThrowsException()
    {
        $this->expectException(RuntimeException::class);

        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $callableResolver = $app->getCallableResolver();
        $logger = $this->getMockLogger();

        $errorMiddleware = new ErrorMiddleware(
            $callableResolver,
            $this->getResponseFactory(),
            false,
            false,
            false,
            $logger
        );
        $errorMiddleware->setDefaultErrorHandler('Uncallable');
        $errorMiddleware->getDefaultErrorHandler();
    }

    public function testGetErrorHandlerWillReturnDefaultErrorHandlerForUnhandledExceptions()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $callableResolver = $app->getCallableResolver();
        $logger = $this->getMockLogger();

        $middleware = new ErrorMiddleware($callableResolver, $this->getResponseFactory(), false, false, false, $logger);
        $exception = MockCustomException::class;
        $handler = $middleware->getErrorHandler($exception);
        $this->assertInstanceOf(ErrorHandler::class, $handler);
    }

    public function testSuperclassExceptionHandlerHandlesExceptionWithSubclassExactMatch()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $callableResolver = $app->getCallableResolver();
        $logger = $this->getMockLogger();
        $middleware = new ErrorMiddleware($callableResolver, $this->getResponseFactory(), false, false, false, $logger);
        $app->add(function ($request, $handler) {
            throw new LogicException('This is a LogicException...');
        });
        $middleware->setErrorHandler(LogicException::class, (function (ServerRequestInterface $request, $exception) {
            $response = $this->createResponse();
            $response->getBody()->write($exception->getMessage());
            return $response;
        })->bindTo($this), true); // - true; handle subclass but also LogicException explicitly
        $middleware->setDefaultErrorHandler((function () {
            $response = $this->createResponse();
            $response->getBody()->write('Oops..');
            return $response;
        })->bindTo($this));
        $app->add($middleware);
        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('...');
            return $response;
        });
        $request = $this->createServerRequest('/foo');
        $app->run($request);
        $this->expectOutputString('This is a LogicException...');
    }

    public function testSuperclassExceptionHandlerHandlesSubclassException()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $callableResolver = $app->getCallableResolver();
        $logger = $this->getMockLogger();

        $middleware = new ErrorMiddleware($callableResolver, $this->getResponseFactory(), false, false, false, $logger);

        $app->add(function ($request, $handler) {
            throw new InvalidArgumentException('This is a subclass of LogicException...');
        });

        $middleware->setErrorHandler(LogicException::class, (function (ServerRequestInterface $request, $exception) {
            $response = $this->createResponse();
            $response->getBody()->write($exception->getMessage());
            return $response;
        })->bindTo($this), true); // - true; handle subclass

        $middleware->setDefaultErrorHandler((function () {
            $response = $this->createResponse();
            $response->getBody()->write('Oops..');
            return $response;
        })->bindTo($this));

        $app->add($middleware);

        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('...');
            return $response;
        });

        $request = $this->createServerRequest('/foo');
        $app->run($request);

        $this->expectOutputString('This is a subclass of LogicException...');
    }

    public function testSuperclassExceptionHandlerDoesNotHandleSubclassException()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $callableResolver = $app->getCallableResolver();
        $logger = $this->getMockLogger();

        $middleware = new ErrorMiddleware($callableResolver, $this->getResponseFactory(), false, false, false, $logger);

        $app->add(function ($request, $handler) {
            throw new InvalidArgumentException('This is a subclass of LogicException...');
        });

        $middleware->setErrorHandler(LogicException::class, (function (ServerRequestInterface $request, $exception) {
            $response = $this->createResponse();
            $response->getBody()->write($exception->getMessage());
            return $response;
        })->bindTo($this), false); // - false; don't handle subclass

        $middleware->setDefaultErrorHandler((function () {
            $response = $this->createResponse();
            $response->getBody()->write('Oops..');
            return $response;
        })->bindTo($this));

        $app->add($middleware);

        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('...');
            return $response;
        });

        $request = $this->createServerRequest('/foo');
        $app->run($request);

        $this->expectOutputString('Oops..');
    }

    public function testHandleMultipleExceptionsAddedAsArray()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $callableResolver = $app->getCallableResolver();
        $logger = $this->getMockLogger();

        $middleware = new ErrorMiddleware($callableResolver, $this->getResponseFactory(), false, false, false, $logger);

        $app->add(function ($request, $handler) {
            throw new InvalidArgumentException('This is an invalid argument exception...');
        });

        $handler = (function (ServerRequestInterface $request, $exception) {
            $response = $this->createResponse();
            $response->getBody()->write($exception->getMessage());
            return $response;
        });

        $middleware->setErrorHandler([LogicException::class, InvalidArgumentException::class], $handler->bindTo($this));

        $middleware->setDefaultErrorHandler((function () {
            $response = $this->createResponse();
            $response->getBody()->write('Oops..');
            return $response;
        })->bindTo($this));

        $app->add($middleware);

        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('...');
            return $response;
        });

        $request = $this->createServerRequest('/foo');
        $app->run($request);

        $this->expectOutputString('This is an invalid argument exception...');
    }

    public function testErrorHandlerHandlesThrowables()
    {
        $responseFactory = $this->getResponseFactory();
        $app = new App($responseFactory);
        $callableResolver = $app->getCallableResolver();
        $logger = $this->getMockLogger();

        $middleware = new ErrorMiddleware($callableResolver, $this->getResponseFactory(), false, false, false, $logger);

        $app->add(function ($request, $handler) {
            throw new Error('Oops..');
        });

        $middleware->setDefaultErrorHandler((function (ServerRequestInterface $request, $exception) {
            $response = $this->createResponse();
            $response->getBody()->write($exception->getMessage());
            return $response;
        })->bindTo($this));

        $app->add($middleware);

        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('...');
            return $response;
        });

        $request = $this->createServerRequest('/foo');
        $app->run($request);

        $this->expectOutputString('Oops..');
    }
}
