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
use Slim\Tests\TestCase;

class OutputBufferingMiddlewareTest extends TestCase
{
    /**
     * Provide a boolean flag to indicate whether the test case should use the
     * advanced callable resolver or the non-advanced callable resolver
     *
     * @return array
     */
    public function useAdvancedCallableResolverDataProvider(): array
    {
        return [[true], [false]];
    }

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

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testAppend(bool $useAdvancedCallableResolver)
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

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandlerInterface::class),
            null,
            $useAdvancedCallableResolver
        );
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $response = $middlewareDispatcher->handle($request);

        $this->assertEquals('BodyTest', $response->getBody());
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testPrepend(bool $useAdvancedCallableResolver)
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

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandlerInterface::class),
            null,
            $useAdvancedCallableResolver
        );
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $response = $middlewareDispatcher->handle($request);

        $this->assertEquals('TestBody', $response->getBody());
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testOutputBufferIsCleanedWhenThrowableIsCaught(bool $useAdvancedCallableResolver)
    {
        $responseFactory = $this->getResponseFactory();
        $mw = (function ($request, $handler) use ($responseFactory) {
            echo "Test";
            $this->assertEquals('Test', ob_get_contents());
            throw new Exception('Oops...');
        })->bindTo($this);
        $mw2 = new OutputBufferingMiddleware($this->getStreamFactory(), 'prepend');

        $request = $this->createServerRequest('/', 'GET');

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandlerInterface::class),
            null,
            $useAdvancedCallableResolver
        );
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);

        try {
            $middlewareDispatcher->handle($request);
        } catch (Exception $e) {
            $this->assertEquals('', ob_get_contents());
        }
    }
}
