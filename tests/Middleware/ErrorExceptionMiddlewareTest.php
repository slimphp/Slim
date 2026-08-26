<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use ErrorException;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorExceptionMiddleware;

final class ErrorExceptionMiddlewareTest extends TestCase
{
    public function testProcessHandlesError(): void
    {
        // Assert that an ErrorException is thrown
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Test error');

        $request = $this->createMock(ServerRequestInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function () {
                trigger_error('Test error', E_USER_WARNING);
            });

        error_reporting(E_USER_WARNING);

        // Instantiate the middleware with a custom error level
        $app = AppFactory::create();
        $middleware = $app
            ->getContainer()
            ->get(ErrorExceptionMiddleware::class);

        // Invoke the middleware process method
        $middleware->process($request, $handler);
    }

    public function testProcessHandlesErrorSilent(): void
    {
        error_reporting(E_USER_ERROR);

        $app = AppFactory::create();
        $middleware = $app
            ->getContainer()
            ->get(ErrorExceptionMiddleware::class);

        $app->add($middleware);
        $app->addRoutingMiddleware();

        $app->get('/', function ($request, $response) {
            trigger_error('Test warning', E_USER_WARNING);

            return $response->withHeader('X-Test', 'silent');
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('silent', $response->getHeaderLine('X-Test'));
    }

    public function testProcessHandlesException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        $request = $this->createMock(ServerRequestInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function () {
                throw new Exception('Test exception');
            });

        // Instantiate the middleware
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(ErrorExceptionMiddleware::class);

        // Invoke the middleware process method
        $middleware->process($request, $handler);
    }

    public function testProcessReturnsResponse(): void
    {
        // Mock the ServerRequestInterface and RequestHandlerInterface
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(ErrorExceptionMiddleware::class);

        // Invoke the middleware process method and assert the response is returned
        $result = $middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testErrorHandlerIsRestoredWhenNoPreviousHandlerExists(): void
    {
        $previous = set_error_handler(static fn() => false);
        restore_error_handler();

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(ErrorExceptionMiddleware::class);

        $middleware->process($request, $handler);

        $current = set_error_handler(static fn() => false);
        restore_error_handler();

        $this->assertSame($previous, $current);
    }

    public function testErrorHandlerIsRestoredWhenAPreviousHandlerExists(): void
    {
        $custom = static fn() => false;
        set_error_handler($custom);

        try {
            $request = $this->createMock(ServerRequestInterface::class);
            $response = $this->createMock(ResponseInterface::class);

            $handler = $this->createMock(RequestHandlerInterface::class);
            $handler->expects($this->once())
                ->method('handle')
                ->willReturn($response);

            $app = AppFactory::create();
            $middleware = $app->getContainer()->get(ErrorExceptionMiddleware::class);

            $middleware->process($request, $handler);

            $current = set_error_handler(static fn() => false);
            restore_error_handler();

            $this->assertSame($custom, $current);
        } finally {
            restore_error_handler();
        }
    }
}
