<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Routing\Route;
use Slim\Routing\RouteGroup;
use Slim\Routing\Router;

class RouteGroupTest extends TestCase
{
    public function testConstructorInitializesPropertiesCorrectly(): void
    {
        $router = $this->createRouter();
        $callback = function () {
        };
        $prefix = '/test';
        $routeGroup = new RouteGroup($prefix, $callback, $router->getRouteCollector());

        $this->assertSame('/test', $routeGroup->getPrefix());
        $this->assertSame($callback, $routeGroup->getRouteGroup() === null ? $callback : null);
        $this->assertSame($router, $routeGroup->getRouteGroup() === null ? $router : null);
        $this->assertNull($routeGroup->getRouteGroup());
    }

    public function testConstructorWithParentGroup(): void
    {
        $router = $this->createRouter();
        $parentGroupCallback = function () {
        };
        $childGroupCallback = function () {
        };
        $parentGroup = new RouteGroup('/parent', $parentGroupCallback, $router->getRouteCollector());
        $childGroup = new RouteGroup('/child', $childGroupCallback, $router->getRouteCollector(), $parentGroup);

        $this->assertSame('/child', $childGroup->getPrefix());
        $this->assertSame($parentGroup, $childGroup->getRouteGroup());
    }

    public function testInvokeExecutesCallback(): void
    {
        $router = $this->createRouter();
        $called = false;
        $callback = function () use (&$called) {
            $called = true;
        };
        $routeGroup = new RouteGroup('/test', $callback, $router->getRouteCollector());

        $routeGroup();
        $this->assertTrue($called);
    }

    public function testMapCreatesAndRegistersRoute(): void
    {
        $router = $this->createRouter();
        $callback = function () {
        };
        $routeGroup = new RouteGroup('/test', $callback, $router->getRouteCollector());

        $route = $routeGroup->map(['GET'], '/foo', 'handler');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test/foo', $route->getPattern());
    }

    public function testMapCreatesAndRegistersRouteWithEmptyRoute(): void
    {
        $router = $this->createRouter();
        $callback = function () {
        };
        $routeGroup = new RouteGroup('/test', $callback, $router->getRouteCollector());

        $route = $routeGroup->map(['GET'], '', 'handler');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test', $route->getPattern());
    }

    public function testMapCreatesAndRegistersRouteWithSlashRoute(): void
    {
        $router = $this->createRouter();
        $callback = function () {
        };
        $routeGroup = new RouteGroup('/test', $callback, $router->getRouteCollector());

        $route = $routeGroup->map(['GET'], '', 'handler');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test', $route->getPattern());
    }

    public function testGroupCreatesAndRegistersNestedRouteGroup(): void
    {
        $router = $this->createRouter();
        $callback = function () {
        };
        $routeGroup = new RouteGroup('/test', $callback, $router->getRouteCollector());

        $nestedGroup = $routeGroup->group('/nested', function () {
        });

        $this->assertInstanceOf(RouteGroup::class, $nestedGroup);
        $this->assertSame('/test/nested', $nestedGroup->getPrefix());
        $this->assertSame($routeGroup, $nestedGroup->getRouteGroup());
    }

    private function createRouter(): Router
    {
        $app = AppFactory::create();

        return $app->getContainer()->get(Router::class);
    }
}
