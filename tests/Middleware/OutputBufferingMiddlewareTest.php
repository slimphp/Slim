<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Middleware;

use Exception;
use Slim\Middleware\ClosureMiddleware;
use Slim\Middleware\OutputBufferingMiddleware;
use Slim\MiddlewareRunner;
use Slim\Tests\TestCase;

class OutputBufferingMiddlewareTest extends TestCase
{
    public function testStyleDefaultValid()
    {
        $mw = new OutputBufferingMiddleware($this->getStreamFactory());
        $this->assertAttributeEquals('append', 'style', $mw);
    }

    public function testStyleCustomValid()
    {
        $mw = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');
        $this->assertAttributeEquals('prepend', 'style', $mw);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testStyleCustomInvalid()
    {
        new OutputBufferingMiddleware($this->getStreamFactory(), 'foo');
    }

    public function testAppend()
    {
        $responseFactory = $this->getResponseFactory();
        $mw = new ClosureMiddleware(function ($request, $handler) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        });
        $mw2 = new OutputBufferingMiddleware($this->getStreamFactory(), 'append');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->add($mw2);
        $response = $middlewareRunner->run($request);

        $this->assertEquals('BodyTest', $response->getBody());
    }

    public function testPrepend()
    {
        $responseFactory = $this->getResponseFactory();
        $mw = new ClosureMiddleware(function ($request, $handler) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        });
        $mw2 = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->add($mw2);
        $response = $middlewareRunner->run($request);

        $this->assertEquals('TestBody', $response->getBody());
    }

    public function testOutputBufferIsCleanedWhenThrowableIsCaught()
    {
        $responseFactory = $this->getResponseFactory();
        $mw = new ClosureMiddleware((function ($request, $handler) use ($responseFactory) {
            echo "Test";
            $this->assertEquals('Test', ob_get_contents());
            throw new Exception('Oops...');
        })->bindTo($this));
        $mw2 = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->add($mw2);

        try {
            $middlewareRunner->run($request);
        } catch (Exception $e) {
            $this->assertEquals('', ob_get_contents());
        }
    }
}
