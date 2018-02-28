<?php
namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler;
use Slim\Http\Body;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Middleware\ErrorMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Mocks\MockCustomException;
use Error;

/**
 * Class ErrorMiddlewareTest
 * @package Slim\Tests\Middleware
 */
class ErrorMiddlewareTest extends TestCase
{
    public function testSetErrorHandler()
    {
        $app = new App();
        $callableResolver = $app->getCallableResolver();

        $mw = new RoutingMiddleware($app->getRouter());
        $app->add($mw);

        $exception = HttpNotFoundException::class;
        $handler = function () {
            return (new Response())->withJson('Oops..');
        };
        $mw2 = new ErrorMiddleware($callableResolver, false, false, false);
        $mw2->setErrorHandler($exception, $handler);
        $app->add($mw2);

        $request = $this->requestFactory('/foo/baz/');
        $app->run($request);

        $expectedOutput = json_encode('Oops..');
        $this->expectOutputString($expectedOutput);
    }

    public function testSetDefaultErrorHandler()
    {
        $app = new App();
        $callableResolver = $app->getCallableResolver();

        $mw = new RoutingMiddleware($app->getRouter());
        $app->add($mw);

        $handler = function () {
            return (new Response())->withJson('Oops..');
        };
        $mw2 = new ErrorMiddleware($callableResolver, false, false, false);
        $mw2->setDefaultErrorHandler($handler);
        $app->add($mw2);

        $request = $this->requestFactory('/foo/baz/');
        $app->run($request);

        $expectedOutput = json_encode('Oops..');
        $this->expectOutputString($expectedOutput);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSetDefaultErrorHandlerThrowsException()
    {
        $app = new App();
        $callableResolver = $app->getCallableResolver();

        $mw = new ErrorMiddleware($callableResolver, false, false, false);
        $mw->setDefaultErrorHandler('Uncallable');
        $mw->getDefaultErrorHandler();
    }

    public function testGetErrorHandlerWillReturnDefaultErrorHandlerForUnhandledExceptions()
    {
        $app = new App();
        $callableResolver = $app->getCallableResolver();

        $middleware = new ErrorMiddleware($callableResolver, false, false, false);
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

        $handler = function ($req, $exception) {
            return (new Response())->withJson($exception->getMessage());
        };
        $mw = new ErrorMiddleware($callableResolver, false, false, false);
        $mw->setDefaultErrorHandler($handler);
        $app->add($mw);

        $app->get('/foo', function () {
            return (new Response())->withJson('...');
        });

        $request = $this->requestFactory('/foo');
        $app->run($request);

        $expectedOutput = json_encode('Oops..');
        $this->expectOutputString($expectedOutput);
    }

    /**
     * helper to create a request object
     * @return Request
     */
    private function requestFactory($requestUri, $method = 'GET', $data = [])
    {
        $defaults = [
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => $requestUri,
            'REQUEST_METHOD' => $method,
        ];

        $data = array_merge($defaults, $data);

        $env = Environment::mock($data);
        $uri = Uri::createFromGlobals($env);
        $headers = Headers::createFromGlobals($env);
        $cookies = [];
        $serverParams = $env;
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request($method, $uri, $headers, $cookies, $serverParams, $body);

        return $request;
    }
}
