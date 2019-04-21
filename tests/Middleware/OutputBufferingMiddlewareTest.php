<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use Exception;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Middleware\OutputBufferingMiddleware;
use Slim\MiddlewareDispatcher;
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
        $mw = function ($request, $handler) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        };
        $mw2 = new OutputBufferingMiddleware($this->getStreamFactory(), 'append');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareDispatcher = new MiddlewareDispatcher($this->createMock(RequestHandlerInterface::class));
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $response = $middlewareDispatcher->handle($request);

        $this->assertEquals('BodyTest', $response->getBody());
    }

    public function testPrepend()
    {
        $responseFactory = $this->getResponseFactory();
        $mw = function ($request, $handler) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        };
        $mw2 = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareDispatcher = new MiddlewareDispatcher($this->createMock(RequestHandlerInterface::class));
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $response = $middlewareDispatcher->handle($request);

        $this->assertEquals('TestBody', $response->getBody());
    }

    public function testOutputBufferIsCleanedWhenThrowableIsCaught()
    {
        $responseFactory = $this->getResponseFactory();
        $mw = (function ($request, $handler) use ($responseFactory) {
            echo "Test";
            $this->assertEquals('Test', ob_get_contents());
            throw new Exception('Oops...');
        })->bindTo($this);
        $mw2 = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareDispatcher = new MiddlewareDispatcher($this->createMock(RequestHandlerInterface::class));
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);

        try {
            $middlewareDispatcher->handle($request);
        } catch (Exception $e) {
            $this->assertEquals('', ob_get_contents());
        }
    }
}
