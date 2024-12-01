<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use ErrorException;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Slim\Builder\AppBuilder;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\ErrorHandlingMiddleware;
use Slim\Middleware\ExceptionLoggingMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Logging\TestLogger;

class ExceptionLoggingMiddlewareTest extends TestCase
{
    public function testErrorExceptionIsLogged(): void
    {
        $this->expectException(ErrorException::class);

        $app = (new AppBuilder())->build();

        $logger = new TestLogger();

        $middleware = new ExceptionLoggingMiddleware($logger);
        $app->add($middleware->withLogErrorDetails(true));

        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Set up a route that throws an ErrorException
        $app->get('/error', function (ServerRequestInterface $request, ResponseInterface $response) {
            throw new ErrorException('This is an error', 0, E_ERROR);
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/error');

        try {
            $app->handle($request);
        } finally {
            // Assert the logger captured the error
            $logs = $logger->getLogs();
            $this->assertCount(1, $logs);
            $log = $logs[0];
            $this->assertSame(LogLevel::ERROR, $log['level']);
            $this->assertSame('This is an error', $log['message']);
            $this->assertInstanceOf(ErrorException::class, $log['context']['exception']);
        }
    }

    public function testThrowableIsLogged(): void
    {
        // Expect the RuntimeException to be thrown
        $this->expectException(RuntimeException::class);

        $app = (new AppBuilder())->build();

        $logger = new TestLogger();

        $middleware = new ExceptionLoggingMiddleware($logger);
        $app->add($middleware->withLogErrorDetails(true));

        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Set up a route that throws a generic Throwable
        $app->get('/throwable', function (ServerRequestInterface $request, ResponseInterface $response) {
            throw new RuntimeException('This is a runtime exception');
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/throwable');

        try {
            $app->handle($request);
        } finally {
            // Assert the logger captured the error
            $logs = $logger->getLogs();
            $this->assertCount(1, $logs);
            $log = $logs[0];
            $this->assertSame(LogLevel::ERROR, $log['level']);
            $this->assertSame('This is a runtime exception', $log['message']);
            $this->assertInstanceOf(RuntimeException::class, $log['context']['exception']);
        }
    }

    /**
     * Passing E_USER_ERROR to trigger_error() is now deprecated.
     * RFC: https://wiki.php.net/rfc/deprecations_php_8_4#deprecate_passing_e_user_error_to_trigger_error
     */
    #[RequiresPhp('< 8.4.0')]
    public function testUserLevelErrorIsLogged(): void
    {
        $this->expectException(ErrorException::class);

        $app = (new AppBuilder())->build();
        error_reporting(E_ALL);

        $logger = new TestLogger();
        $app->add(ErrorHandlingMiddleware::class);

        $middleware = new ExceptionLoggingMiddleware($logger);

        $app->add($middleware->withLogErrorDetails(true));
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get('/error', function () {
            trigger_error('This is an error', E_USER_ERROR);
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/error');

        try {
            $app->handle($request);
        } finally {
            // Assert the logger captured the error
            $logs = $logger->getLogs();
            $this->assertCount(1, $logs);
            $log = $logs[0];
            $this->assertSame(LogLevel::ERROR, $log['level']);
            $this->assertSame('This is an error', $log['message']);
            $this->assertInstanceOf(ErrorException::class, $log['context']['exception']);
        }
    }
}
