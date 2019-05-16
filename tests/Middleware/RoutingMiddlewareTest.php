<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use FastRoute\Dispatcher;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\CallableResolver;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Middleware\RoutingMiddleware;
use Slim\MiddlewareDispatcher;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteResolver;
use Slim\Routing\RoutingResults;
use Slim\Tests\TestCase;

class RoutingMiddlewareTest extends TestCase
{
    protected function getRouteCollector()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $routeCollector = new RouteCollector($responseFactory, $callableResolver);
        $routeCollector->map(['GET'], '/hello/{name}', null);
        return $routeCollector;
    }

    public function testRouteIsStoredOnSuccessfulMatch()
    {
        $responseFactory = $this->getResponseFactory();
        $mw = (function (ServerRequestInterface $request) use ($responseFactory) {
            // route is available
            $route = $request->getAttribute('route');
            $this->assertNotNull($route);
            $this->assertEquals('foo', $route->getArgument('name'));

            // routingResults is available
            $routingResults = $request->getAttribute('routingResults');
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            return $responseFactory->createResponse();
        })->bindTo($this);

        $routeCollector = $this->getRouteCollector();
        $routeResolver = new RouteResolver($routeCollector);
        $mw2 = new RoutingMiddleware($routeResolver);

        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'GET');

        $middlewareDispatcher = new MiddlewareDispatcher($this->createMock(RequestHandlerInterface::class));
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $middlewareDispatcher->handle($request);
    }

    /**
     * @expectedException \Slim\Exception\HttpMethodNotAllowedException
     */
    public function testRouteIsNotStoredOnMethodNotAllowed()
    {

        $responseFactory = $this->getResponseFactory();
        $mw = (function (ServerRequestInterface $request) use ($responseFactory) {
            // route is not available
            $route = $request->getAttribute('route');
            $this->assertNull($route);

            // routingResults is available
            $routingResults = $request->getAttribute('routingResults');
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            $this->assertEquals(Dispatcher::METHOD_NOT_ALLOWED, $routingResults->getRouteStatus());

            return $responseFactory->createResponse();
        })->bindTo($this);

        $routeCollector = $this->getRouteCollector();
        $routeResolver = new RouteResolver($routeCollector);
        $mw2 = new RoutingMiddleware($routeResolver);

        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'POST');
        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);

        $middlewareDispatcher = new MiddlewareDispatcher($requestHandlerProphecy->reveal());
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $middlewareDispatcher->handle($request);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testPerformRoutingThrowsExceptionOnInvalidRoutingResultsRouteStatus()
    {
        // Prophesize the `RoutingResults` instance that would return an invalid route
        // status when the method `getRouteStatus()` gets called.
        $routingResultsProphecy = $this->prophesize(RoutingResults::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $routingResultsProphecy->getRouteStatus()
            ->willReturn(-1)
            ->shouldBeCalledOnce();
        /** @var RoutingResults $routingResults */
        $routingResults = $routingResultsProphecy->reveal();

        // Prophesize the `RouteResolverInterface` that would return the `RoutingResults`
        // defined above, when the method `computeRoutingResults()` gets called.
        $routeResolverProphecy = $this->prophesize(RouteResolverInterface::class);
        /** @noinspection PhpUndefinedMethodInspection */
        $routeResolverProphecy->computeRoutingResults(Argument::any(), Argument::any())
            ->willReturn($routingResults)
            ->shouldBeCalled();
        /** @var RouteResolverInterface $routeResolver */
        $routeResolver = $routeResolverProphecy->reveal();

        // Create the server request.
        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'GET');

        // Create the routing middleware with the `RouteResolverInterface` defined
        // above. Perform the routing, which should throw the RuntimeException.
        $m = new RoutingMiddleware($routeResolver);
        /** @noinspection PhpUnhandledExceptionInspection */
        $m->performRouting($request);
    }
}
