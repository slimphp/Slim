<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteMatch;

final class RouteMatchTest extends TestCase
{
    public function testFoundRouteMatch(): void
    {
        $route = $this->createMock(RouteInterface::class);

        $arguments = ['id' => 42, 'slug' => 'test'];
        $basePath = '/api';

        $routeMatch = RouteMatch::found($route, $arguments);

        $this->assertTrue($routeMatch->isFound());
        $this->assertFalse($routeMatch->isNotFound());
        $this->assertFalse($routeMatch->isMethodNotAllowed());

        $this->assertSame($route, $routeMatch->getRoute());
        $this->assertSame($arguments, $routeMatch->getArguments());
        $this->assertSame(42, $routeMatch->getArgument('id'));
        $this->assertSame('test', $routeMatch->getArgument('slug'));
        $this->assertNull($routeMatch->getArgument('missing'));

        $this->assertSame([], $routeMatch->getAllowedMethods());
    }

    public function testNotFoundRouteMatch(): void
    {
        $basePath = '/api';

        $routeMatch = RouteMatch::notFound($basePath);

        $this->assertFalse($routeMatch->isFound());
        $this->assertTrue($routeMatch->isNotFound());
        $this->assertFalse($routeMatch->isMethodNotAllowed());

        $this->assertNull($routeMatch->getRoute());
        $this->assertSame([], $routeMatch->getArguments());
        $this->assertSame([], $routeMatch->getAllowedMethods());
    }

    public function testMethodNotAllowedRouteMatch(): void
    {
        $allowedMethods = ['GET', 'POST'];
        $basePath = '/api';

        $routeMatch = RouteMatch::methodNotAllowed($allowedMethods, $basePath);

        $this->assertFalse($routeMatch->isFound());
        $this->assertFalse($routeMatch->isNotFound());
        $this->assertTrue($routeMatch->isMethodNotAllowed());

        $this->assertNull($routeMatch->getRoute());
        $this->assertSame([], $routeMatch->getArguments());
        $this->assertSame($allowedMethods, $routeMatch->getAllowedMethods());
    }

    public function testGetArgumentWithDefaultValue(): void
    {
        $route = $this->createMock(RouteInterface::class);

        $routeMatch = RouteMatch::found($route, ['id' => 123]);

        $this->assertSame(123, $routeMatch->getArgument('id'));
        $this->assertSame('default', $routeMatch->getArgument('missing', 'default'));
    }

    public function testEmptyArguments(): void
    {
        $route = $this->createMock(RouteInterface::class);

        $routeMatch = RouteMatch::found($route);

        $this->assertSame([], $routeMatch->getArguments());
    }

    public function testStatusConsistency(): void
    {
        $route = $this->createMock(RouteInterface::class);

        $found = RouteMatch::found($route);
        $notFound = RouteMatch::notFound();
        $methodNotAllowed = RouteMatch::methodNotAllowed(['GET']);

        $this->assertSame(DispatcherInterface::FOUND, $found->getStatus());
        $this->assertSame(DispatcherInterface::NOT_FOUND, $notFound->getStatus());
        $this->assertSame(DispatcherInterface::METHOD_NOT_ALLOWED, $methodNotAllowed->getStatus());
    }
}
