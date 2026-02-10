<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Middleware\ConditionalMiddleware;

final class ConditionalMiddlewareTest extends TestCase
{
    /**
     * Test that middleware executes when condition returns true
     */
    public function testMiddlewareExecutesWhenConditionTrue(): void
    {
        $app = AppFactory::create();

        $middlewareCalled = false;

        // Add conditional middleware that always executes (condition = true)
        $app->add(new ConditionalMiddleware(
            new class($middlewareCalled) implements \Psr\Http\Server\MiddlewareInterface {
                public function __construct(private bool &$called) {}

                public function process(
                    ServerRequestInterface $request,
                    \Psr\Http\Server\RequestHandlerInterface $handler
                ): ResponseInterface {
                    $this->called = true;
                    $response = $handler->handle($request);
                    $response->getBody()->write('|middleware');
                    return $response;
                }
            },
            fn(ServerRequestInterface $request): bool => true
        ));

        $app->addRoutingMiddleware();

        $app->get('/test', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Route');
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test');

        $response = $app->handle($request);

        $this->assertTrue($middlewareCalled);
        $this->assertSame('Route|middleware', (string)$response->getBody());
    }

    /**
     * Test that middleware is skipped when condition returns false
     */
    public function testMiddlewareSkippedWhenConditionFalse(): void
    {
        $app = AppFactory::create();

        $middlewareCalled = false;

        // Add conditional middleware that never executes (condition = false)
        $app->add(new ConditionalMiddleware(
            new class($middlewareCalled) implements \Psr\Http\Server\MiddlewareInterface {
                public function __construct(private bool &$called) {}

                public function process(
                    ServerRequestInterface $request,
                    \Psr\Http\Server\RequestHandlerInterface $handler
                ): ResponseInterface {
                    $this->called = true;
                    $response = $handler->handle($request);
                    $response->getBody()->write('|middleware');
                    return $response;
                }
            },
            fn(ServerRequestInterface $request): bool => false
        ));

        $app->addRoutingMiddleware();

        $app->get('/test', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Route');
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test');

        $response = $app->handle($request);

        $this->assertFalse($middlewareCalled);
        $this->assertSame('Route', (string)$response->getBody());
    }

    /**
     * Test path-based conditional middleware with matching path
     */
    public function testForPathConditionMatches(): void
    {
        $app = AppFactory::create();

        $middlewareCalled = false;

        // Add conditional middleware that executes only for /admin paths
        $app->add(ConditionalMiddleware::forPath(
            new class($middlewareCalled) implements \Psr\Http\Server\MiddlewareInterface {
                public function __construct(private bool &$called) {}

                public function process(
                    ServerRequestInterface $request,
                    \Psr\Http\Server\RequestHandlerInterface $handler
                ): ResponseInterface {
                    $this->called = true;
                    $response = $handler->handle($request);
                    $response->getBody()->write('|admin-middleware');
                    return $response;
                }
            },
            '/admin'
        ));

        $app->addRoutingMiddleware();

        $app->get('/admin/dashboard', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Admin Dashboard');
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/admin/dashboard');

        $response = $app->handle($request);

        $this->assertTrue($middlewareCalled);
        $this->assertSame('Admin Dashboard|admin-middleware', (string)$response->getBody());
    }

    /**
     * Test path-based conditional middleware with non-matching path
     */
    public function testForPathConditionDoesNotMatch(): void
    {
        $app = AppFactory::create();

        $middlewareCalled = false;

        // Add conditional middleware that executes only for /admin paths
        $app->add(ConditionalMiddleware::forPath(
            new class($middlewareCalled) implements \Psr\Http\Server\MiddlewareInterface {
                public function __construct(private bool &$called) {}

                public function process(
                    ServerRequestInterface $request,
                    \Psr\Http\Server\RequestHandlerInterface $handler
                ): ResponseInterface {
                    $this->called = true;
                    $response = $handler->handle($request);
                    $response->getBody()->write('|admin-middleware');
                    return $response;
                }
            },
            '/admin'
        ));

        $app->addRoutingMiddleware();

        $app->get('/public/page', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Public Page');
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/public/page');

        $response = $app->handle($request);

        $this->assertFalse($middlewareCalled);
        $this->assertSame('Public Page', (string)$response->getBody());
    }

    /**
     * Test HTTP method-based conditional middleware with matching method
     */
    public function testForMethodsConditionMatches(): void
    {
        $app = AppFactory::create();

        $middlewareCalled = false;

        // Add conditional middleware that executes only for POST/PUT/DELETE
        $app->add(ConditionalMiddleware::forMethods(
            new class($middlewareCalled) implements \Psr\Http\Server\MiddlewareInterface {
                public function __construct(private bool &$called) {}

                public function process(
                    ServerRequestInterface $request,
                    \Psr\Http\Server\RequestHandlerInterface $handler
                ): ResponseInterface {
                    $this->called = true;
                    $response = $handler->handle($request);
                    $response->getBody()->write('|csrf-middleware');
                    return $response;
                }
            },
            ['POST', 'PUT', 'DELETE']
        ));

        $app->addRoutingMiddleware();

        $app->post('/api/users', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('User Created');
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/api/users');

        $response = $app->handle($request);

        $this->assertTrue($middlewareCalled);
        $this->assertSame('User Created|csrf-middleware', (string)$response->getBody());
    }

    /**
     * Test HTTP method-based conditional middleware with non-matching method
     */
    public function testForMethodsConditionDoesNotMatch(): void
    {
        $app = AppFactory::create();

        $middlewareCalled = false;

        // Add conditional middleware that executes only for POST/PUT/DELETE
        $app->add(ConditionalMiddleware::forMethods(
            new class($middlewareCalled) implements \Psr\Http\Server\MiddlewareInterface {
                public function __construct(private bool &$called) {}

                public function process(
                    ServerRequestInterface $request,
                    \Psr\Http\Server\RequestHandlerInterface $handler
                ): ResponseInterface {
                    $this->called = true;
                    $response = $handler->handle($request);
                    $response->getBody()->write('|csrf-middleware');
                    return $response;
                }
            },
            ['POST', 'PUT', 'DELETE']
        ));

        $app->addRoutingMiddleware();

        $app->get('/api/users', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Users List');
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/api/users');

        $response = $app->handle($request);

        $this->assertFalse($middlewareCalled);
        $this->assertSame('Users List', (string)$response->getBody());
    }

    /**
     * Test path and method-based conditional middleware with matching conditions
     */
    public function testForPathAndMethodsConditionMatches(): void
    {
        $app = AppFactory::create();

        $middlewareCalled = false;

        // Add conditional middleware that executes only for POST/PUT to /api paths
        $app->add(ConditionalMiddleware::forPathAndMethods(
            new class($middlewareCalled) implements \Psr\Http\Server\MiddlewareInterface {
                public function __construct(private bool &$called) {}

                public function process(
                    ServerRequestInterface $request,
                    \Psr\Http\Server\RequestHandlerInterface $handler
                ): ResponseInterface {
                    $this->called = true;
                    $response = $handler->handle($request);
                    $response->getBody()->write('|validation-middleware');
                    return $response;
                }
            },
            '/api',
            ['POST', 'PUT']
        ));

        $app->addRoutingMiddleware();

        $app->post('/api/users', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('User Created');
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/api/users');

        $response = $app->handle($request);

        $this->assertTrue($middlewareCalled);
        $this->assertSame('User Created|validation-middleware', (string)$response->getBody());
    }

    /**
     * Test path and method-based conditional middleware with non-matching method
     */
    public function testForPathAndMethodsConditionFailsWithNonMatchingMethod(): void
    {
        $app = AppFactory::create();

        $middlewareCalled = false;

        // Add conditional middleware that executes only for POST/PUT to /api paths
        $app->add(ConditionalMiddleware::forPathAndMethods(
            new class($middlewareCalled) implements \Psr\Http\Server\MiddlewareInterface {
                public function __construct(private bool &$called) {}

                public function process(
                    ServerRequestInterface $request,
                    \Psr\Http\Server\RequestHandlerInterface $handler
                ): ResponseInterface {
                    $this->called = true;
                    $response = $handler->handle($request);
                    $response->getBody()->write('|validation-middleware');
                    return $response;
                }
            },
            '/api',
            ['POST', 'PUT']
        ));

        $app->addRoutingMiddleware();

        $app->get('/api/users', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Users List');
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/api/users');

        $response = $app->handle($request);

        $this->assertFalse($middlewareCalled);
        $this->assertSame('Users List', (string)$response->getBody());
    }

    /**
     * Test path and method-based conditional middleware with non-matching path
     */
    public function testForPathAndMethodsConditionFailsWithNonMatchingPath(): void
    {
        $app = AppFactory::create();

        $middlewareCalled = false;

        // Add conditional middleware that executes only for POST/PUT to /api paths
        $app->add(ConditionalMiddleware::forPathAndMethods(
            new class($middlewareCalled) implements \Psr\Http\Server\MiddlewareInterface {
                public function __construct(private bool &$called) {}

                public function process(
                    ServerRequestInterface $request,
                    \Psr\Http\Server\RequestHandlerInterface $handler
                ): ResponseInterface {
                    $this->called = true;
                    $response = $handler->handle($request);
                    $response->getBody()->write('|validation-middleware');
                    return $response;
                }
            },
            '/api',
            ['POST', 'PUT']
        ));

        $app->addRoutingMiddleware();

        $app->post('/public/users', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Public Creation');
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/public/users');

        $response = $app->handle($request);

        $this->assertFalse($middlewareCalled);
        $this->assertSame('Public Creation', (string)$response->getBody());
    }

    /**
     * Test custom condition with complex logic
     */
    public function testCustomConditionWithComplexLogic(): void
    {
        $app = AppFactory::create();

        $middlewareCalled = false;

        // Add conditional middleware with complex condition logic
        $app->add(new ConditionalMiddleware(
            new class($middlewareCalled) implements \Psr\Http\Server\MiddlewareInterface {
                public function __construct(private bool &$called) {}

                public function process(
                    ServerRequestInterface $request,
                    \Psr\Http\Server\RequestHandlerInterface $handler
                ): ResponseInterface {
                    $this->called = true;
                    $response = $handler->handle($request);
                    $response->getBody()->write('|auth-middleware');
                    return $response;
                }
            },
            function (ServerRequestInterface $request): bool {
                // Only execute for POST/PUT requests to /api with JSON content-type
                return in_array($request->getMethod(), ['POST', 'PUT'], true)
                    && str_starts_with($request->getUri()->getPath(), '/api')
                    && $request->getHeaderLine('Content-Type') === 'application/json';
            }
        ));

        $app->addRoutingMiddleware();

        $app->post('/api/users', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('User Created');
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/api/users')
            ->withHeader('Content-Type', 'application/json');

        $response = $app->handle($request);

        $this->assertTrue($middlewareCalled);
        $this->assertSame('User Created|auth-middleware', (string)$response->getBody());
    }

    /**
     * Test that middleware response headers are preserved
     */
    public function testMiddlewareCanAddHeaders(): void
    {
        $app = AppFactory::create();

        // Add conditional middleware that adds headers
        $app->add(new ConditionalMiddleware(
            new class implements \Psr\Http\Server\MiddlewareInterface {
                public function process(
                    ServerRequestInterface $request,
                    \Psr\Http\Server\RequestHandlerInterface $handler
                ): ResponseInterface {
                    $response = $handler->handle($request);
                    return $response->withHeader('X-Middleware', 'Conditional');
                }
            },
            fn(ServerRequestInterface $request): bool => true
        ));

        $app->addRoutingMiddleware();

        $app->get('/test', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Test');
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test');

        $response = $app->handle($request);

        $this->assertSame('Conditional', $response->getHeaderLine('X-Middleware'));
        $this->assertSame('Test', (string)$response->getBody());
    }
}
