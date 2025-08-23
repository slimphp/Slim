<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use Exception;
use InvalidArgumentException;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;
use Slim\Middleware\OutputBufferingMiddleware;
use Slim\Tests\TestCase;

use function ob_get_contents;

class OutputBufferingMiddlewareTest extends TestCase
{
    public function testStyleDefaultValid()
    {
        $middleware = new OutputBufferingMiddleware($this->getStreamFactory());

        $reflectionProperty = new ReflectionProperty($middleware, 'style');
        $this->setAccessible($reflectionProperty);
        $value = $reflectionProperty->getValue($middleware);

        $this->assertSame('append', $value);
    }

    public function testStyleCustomValid()
    {
        $middleware = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');

        $reflectionProperty = new ReflectionProperty($middleware, 'style');
        $this->setAccessible($reflectionProperty);
        $value = $reflectionProperty->getValue($middleware);

        $this->assertSame('prepend', $value);
    }

    public function testStyleCustomInvalid()
    {
        $this->expectException(InvalidArgumentException::class);

        new OutputBufferingMiddleware($this->getStreamFactory(), 'foo');
    }

    public function testAppend()
    {
        $responseFactory = $this->getResponseFactory();
        $middleware = function ($request, $handler) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        };
        $outputBufferingMiddleware = new OutputBufferingMiddleware($this->getStreamFactory(), 'append');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandlerInterface::class),
            null
        );
        $middlewareDispatcher->addCallable($middleware);
        $middlewareDispatcher->addMiddleware($outputBufferingMiddleware);
        $response = $middlewareDispatcher->handle($request);

        $this->assertSame('BodyTest', (string) $response->getBody());
    }

    public function testPrepend()
    {
        $responseFactory = $this->getResponseFactory();
        $middleware = function ($request, $handler) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        };
        $outputBufferingMiddleware = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandlerInterface::class),
            null
        );
        $middlewareDispatcher->addCallable($middleware);
        $middlewareDispatcher->addMiddleware($outputBufferingMiddleware);
        $response = $middlewareDispatcher->handle($request);

        $this->assertSame('TestBody', (string) $response->getBody());
    }

    public function testOutputBufferIsCleanedWhenThrowableIsCaught()
    {
        $this->getResponseFactory();
        $middleware = (function ($request, $handler) {
            echo "Test";
            $this->assertSame('Test', ob_get_contents());
            throw new Exception('Oops...');
        })->bindTo($this);
        $outputBufferingMiddleware = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandlerInterface::class),
            null
        );
        $middlewareDispatcher->addCallable($middleware);
        $middlewareDispatcher->addMiddleware($outputBufferingMiddleware);

        try {
            $middlewareDispatcher->handle($request);
        } catch (Exception $e) {
            $this->assertSame('', ob_get_contents());
        }
    }
}
