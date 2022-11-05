<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\CallableResolver;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteCollectorProxy;
use Slim\Tests\TestCase;

class RouteCollectorProxyTest extends TestCase
{
    public function testGetResponseFactory()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal()
        );

        $this->assertSame(
            $responseFactoryProphecy->reveal(),
            $routeCollectorProxy->getResponseFactory()
        );
    }

    public function testGetCallableResolver()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal()
        );

        $this->assertSame(
            $callableResolverProphecy->reveal(),
            $routeCollectorProxy->getCallableResolver()
        );
    }

    public function testGetContainerReturnsInjectedInstance()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            $containerProphecy->reveal()
        );

        $this->assertSame(
            $containerProphecy->reveal(),
            $routeCollectorProxy->getContainer()
        );
    }

    public function testGetRouteCollectorReturnsInjectedInstance()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            $containerProphecy->reveal(),
            $routeCollectorProphecy->reveal()
        );

        $this->assertSame(
            $routeCollectorProphecy->reveal(),
            $routeCollectorProxy->getRouteCollector()
        );
    }

    public function testGetSetBasePath()
    {
        $basePath = '/base/path';

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            $containerProphecy->reveal()
        );

        $routeCollectorProxy->setBasePath($basePath);

        $this->assertSame($basePath, $routeCollectorProxy->getBasePath());

        $newBasePath = '/new/base/path';
        $routeCollectorProxy->setBasePath('/new/base/path');

        $this->assertSame($newBasePath, $routeCollectorProxy->getBasePath());
    }

    public function testGet()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $pattern = '/';
        $callable = function () {
        };

        $routeProphecy = $this->prophesize(RouteInterface::class);
        $routeProphecy
            ->getPattern()
            ->willReturn($pattern)
            ->shouldBeCalledOnce();

        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeCollectorProphecy
            ->map(['GET'], $pattern, Argument::is($callable))
            ->willReturn($routeProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            null,
            $routeCollectorProphecy->reveal()
        );

        $route = $routeCollectorProxy->get($pattern, $callable);

        $this->assertSame($pattern, $route->getPattern());
    }

    public function testPost()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $pattern = '/';
        $callable = function () {
        };

        $routeProphecy = $this->prophesize(RouteInterface::class);
        $routeProphecy
            ->getPattern()
            ->willReturn($pattern)
            ->shouldBeCalledOnce();

        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeCollectorProphecy
            ->map(['POST'], $pattern, Argument::is($callable))
            ->willReturn($routeProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            null,
            $routeCollectorProphecy->reveal()
        );

        $route = $routeCollectorProxy->post($pattern, $callable);

        $this->assertSame($pattern, $route->getPattern());
    }

    public function testPut()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $pattern = '/';
        $callable = function () {
        };

        $routeProphecy = $this->prophesize(RouteInterface::class);
        $routeProphecy
            ->getPattern()
            ->willReturn($pattern)
            ->shouldBeCalledOnce();

        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeCollectorProphecy
            ->map(['PUT'], $pattern, Argument::is($callable))
            ->willReturn($routeProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            null,
            $routeCollectorProphecy->reveal()
        );

        $route = $routeCollectorProxy->put($pattern, $callable);

        $this->assertSame($pattern, $route->getPattern());
    }

    public function testPatch()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $pattern = '/';
        $callable = function () {
        };

        $routeProphecy = $this->prophesize(RouteInterface::class);
        $routeProphecy
            ->getPattern()
            ->willReturn($pattern)
            ->shouldBeCalledOnce();

        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeCollectorProphecy
            ->map(['PATCH'], $pattern, Argument::is($callable))
            ->willReturn($routeProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            null,
            $routeCollectorProphecy->reveal()
        );

        $route = $routeCollectorProxy->patch($pattern, $callable);

        $this->assertSame($pattern, $route->getPattern());
    }

    public function testDelete()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $pattern = '/';
        $callable = function () {
        };

        $routeProphecy = $this->prophesize(RouteInterface::class);
        $routeProphecy
            ->getPattern()
            ->willReturn($pattern)
            ->shouldBeCalledOnce();

        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeCollectorProphecy
            ->map(['DELETE'], $pattern, Argument::is($callable))
            ->willReturn($routeProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            null,
            $routeCollectorProphecy->reveal()
        );

        $route = $routeCollectorProxy->delete($pattern, $callable);

        $this->assertSame($pattern, $route->getPattern());
    }

    public function testOptions()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $pattern = '/';
        $callable = function () {
        };

        $routeProphecy = $this->prophesize(RouteInterface::class);
        $routeProphecy
            ->getPattern()
            ->willReturn($pattern)
            ->shouldBeCalledOnce();

        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeCollectorProphecy
            ->map(['OPTIONS'], $pattern, Argument::is($callable))
            ->willReturn($routeProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            null,
            $routeCollectorProphecy->reveal()
        );

        $route = $routeCollectorProxy->options($pattern, $callable);

        $this->assertSame($pattern, $route->getPattern());
    }

    public function testAny()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $pattern = '/';
        $callable = function () {
        };

        $routeProphecy = $this->prophesize(RouteInterface::class);
        $routeProphecy
            ->getPattern()
            ->willReturn($pattern)
            ->shouldBeCalledOnce();

        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeCollectorProphecy
            ->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, Argument::is($callable))
            ->willReturn($routeProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            null,
            $routeCollectorProphecy->reveal()
        );

        $route = $routeCollectorProxy->any($pattern, $callable);

        $this->assertSame($pattern, $route->getPattern());
    }

    public function testMap()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $pattern = '/';
        $methods = ['GET', 'POST'];
        $callable = function () {
        };

        $routeProphecy = $this->prophesize(RouteInterface::class);
        $routeProphecy
            ->getPattern()
            ->willReturn($pattern)
            ->shouldBeCalledOnce();

        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeCollectorProphecy
            ->map($methods, $pattern, Argument::is($callable))
            ->willReturn($routeProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            null,
            $routeCollectorProphecy->reveal()
        );

        $route = $routeCollectorProxy->map($methods, $pattern, $callable);

        $this->assertSame($pattern, $route->getPattern());
    }

    public function testRedirect()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $callableResolver = new CallableResolver($containerProphecy->reveal());

        $from = '/from';
        $to = '/to';

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy
            ->withHeader('Location', $to)
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledOnce();

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse()
            ->will(function () use ($responseProphecy) {
                $this
                    ->createResponse(302)
                    ->willReturn($responseProphecy)
                    ->shouldBeCalledOnce();
                return $responseProphecy->reveal();
            })
            ->shouldBeCalledOnce();

        $routeCollector = new RouteCollector(
            $responseFactoryProphecy->reveal(),
            $callableResolver
        );

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolver,
            $containerProphecy->reveal(),
            $routeCollector
        );

        $route = $routeCollectorProxy->redirect($from, $to);
        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertSame($responseProphecy->reveal(), $response);
    }

    public function testGroup()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $pattern = '/group';
        $callable = function () {
        };

        $routeGroupProphecy = $this->prophesize(RouteGroupInterface::class);

        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeCollectorProphecy
            ->group($pattern, Argument::is($callable))
            ->willReturn($routeGroupProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeCollectorProxy = new RouteCollectorProxy(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            null,
            $routeCollectorProphecy->reveal()
        );

        $routeCollectorProxy->group($pattern, $callable);
    }
}
