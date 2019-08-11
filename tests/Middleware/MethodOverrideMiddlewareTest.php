<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Tests\TestCase;

class MethodOverrideMiddlewareTest extends TestCase
{
    public function testHeader()
    {
        $responseFactory = $this->getResponseFactory();
        $mw = (function (Request $request, RequestHandler $handler) use ($responseFactory) {
            $this->assertEquals('PUT', $request->getMethod());
            return $responseFactory->createResponse();
        })->bindTo($this);
        $mw2 = new MethodOverrideMiddleware();

        $request = $this
            ->createServerRequest('/', 'POST')
            ->withHeader('X-Http-Method-Override', 'PUT');

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandler::class),
            null
        );
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $middlewareDispatcher->handle($request);
    }

    public function testBodyParam()
    {
        $responseFactory = $this->getResponseFactory();
        $mw = (function (Request $request, RequestHandler $handler) use ($responseFactory) {
            $this->assertEquals('PUT', $request->getMethod());
            return $responseFactory->createResponse();
        })->bindTo($this);

        $mw2 = new MethodOverrideMiddleware();

        $request = $this
            ->createServerRequest('/', 'POST')
            ->withParsedBody(['_METHOD' => 'PUT']);

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandler::class),
            null
        );
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $middlewareDispatcher->handle($request);
    }

    public function testHeaderPreferred()
    {
        $responseFactory = $this->getResponseFactory();
        $mw = (function (Request $request, RequestHandler $handler) use ($responseFactory) {
            $this->assertEquals('DELETE', $request->getMethod());
            return $responseFactory->createResponse();
        })->bindTo($this);

        $mw2 = new MethodOverrideMiddleware();

        $request = $this
            ->createServerRequest('/', 'POST')
            ->withHeader('X-Http-Method-Override', 'DELETE')
            ->withParsedBody((object) ['_METHOD' => 'PUT']);

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandler::class),
            null
        );
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $middlewareDispatcher->handle($request);
    }

    public function testNoOverride()
    {
        $responseFactory = $this->getResponseFactory();
        $mw = (function (Request $request, RequestHandler $handler) use ($responseFactory) {
            $this->assertEquals('POST', $request->getMethod());
            return $responseFactory->createResponse();
        })->bindTo($this);

        $mw2 = new MethodOverrideMiddleware();

        $request = $this->createServerRequest('/', 'POST');

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandler::class),
            null
        );
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $middlewareDispatcher->handle($request);
    }

    public function testNoOverrideRewindEofBodyStream()
    {
        $responseFactory = $this->getResponseFactory();
        $mw = (function (Request $request, RequestHandler $handler) use ($responseFactory) {
            $this->assertEquals('POST', $request->getMethod());
            return $responseFactory->createResponse();
        })->bindTo($this);

        $mw2 = new MethodOverrideMiddleware();

        $request = $this->createServerRequest('/', 'POST');

        // Prophesize the body stream for which `eof()` returns `true` and the
        // `rewind()` has to be called.
        $bodyProphecy = $this->prophesize(StreamInterface::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $bodyProphecy->eof()
            ->willReturn(true)
            ->shouldBeCalled();
        /** @noinspection PhpUndefinedMethodInspection */
        $bodyProphecy->rewind()
            ->shouldBeCalled();
        /** @var StreamInterface $body */
        $body = $bodyProphecy->reveal();
        $request = $request->withBody($body);

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandler::class),
            null
        );
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $middlewareDispatcher->handle($request);
    }
}
