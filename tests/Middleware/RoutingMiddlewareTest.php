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
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\RouteParserInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Middleware\RoutingMiddleware;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteParser;
use Slim\Routing\RouteResolver;
use Slim\Routing\RoutingResults;
use Slim\Tests\TestCase;

class RoutingMiddlewareTest extends TestCase
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

    protected function getRouteCollector()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $routeCollector = new RouteCollector($responseFactory, $callableResolver);
        $routeCollector->map(['GET'], '/hello/{name}', null);
        return $routeCollector;
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testRouteIsStoredOnSuccessfulMatch(bool $useAdvancedCallableResolver)
    {
        $responseFactory = $this->getResponseFactory();
        $mw = (function (ServerRequestInterface $request) use ($responseFactory) {
            // route is available
            $route = $request->getAttribute('route');
            $this->assertNotNull($route);
            $this->assertEquals('foo', $route->getArgument('name'));

            // routeParser is available
            $routeParser = $request->getAttribute('routeParser');
            $this->assertNotNull($routeParser);
            $this->assertInstanceOf(RouteParserInterface::class, $routeParser);

            // routingResults is available
            $routingResults = $request->getAttribute('routingResults');
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            return $responseFactory->createResponse();
        })->bindTo($this);

        $routeCollector = $this->getRouteCollector();
        $routeParser = new RouteParser($routeCollector);
        $routeResolver = new RouteResolver($routeCollector);
        $mw2 = new RoutingMiddleware($routeResolver, $routeParser);

        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'GET');

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandlerInterface::class),
            null,
            $useAdvancedCallableResolver
        );
        $middlewareDispatcher->addCallable($mw);
        $middlewareDispatcher->addMiddleware($mw2);
        $middlewareDispatcher->handle($request);
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testRouteIsNotStoredOnMethodNotAllowed(bool $useAdvancedCallableResolver)
    {
        $routeCollector = $this->getRouteCollector();
        $routeParser = new RouteParser($routeCollector);
        $routeResolver = new RouteResolver($routeCollector);
        $routingMiddleware = new RoutingMiddleware($routeResolver, $routeParser);

        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'POST');
        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        /** @var RequestHandlerInterface $requestHandler */
        $requestHandler = $requestHandlerProphecy->reveal();

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $requestHandler,
            null,
            $useAdvancedCallableResolver
        );
        $middlewareDispatcher->addMiddleware($routingMiddleware);

        try {
            $middlewareDispatcher->handle($request);
            $this->fail('HTTP method should not have been allowed');
        } catch (HttpMethodNotAllowedException $exception) {
            $request = $exception->getRequest();

            // route is not available
            $route = $request->getAttribute('route');
            $this->assertNull($route);

            // routeParser is available
            $routeParser = $request->getAttribute('routeParser');
            $this->assertNotNull($routeParser);
            $this->assertInstanceOf(RouteParserInterface::class, $routeParser);

            // routingResults is available
            $routingResults = $request->getAttribute('routingResults');
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            $this->assertEquals(Dispatcher::METHOD_NOT_ALLOWED, $routingResults->getRouteStatus());
        }
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testRouteIsNotStoredOnNotFound(bool $useAdvancedCallableResolver)
    {
        $routeCollector = $this->getRouteCollector();
        $routeParser = new RouteParser($routeCollector);
        $routeResolver = new RouteResolver($routeCollector);
        $routingMiddleware = new RoutingMiddleware($routeResolver, $routeParser);

        $request = $this->createServerRequest('https://example.com:443/goodbye', 'GET');
        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        /** @var RequestHandlerInterface $requestHandler */
        $requestHandler = $requestHandlerProphecy->reveal();

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $requestHandler,
            null,
            $useAdvancedCallableResolver
        );
        $middlewareDispatcher->addMiddleware($routingMiddleware);

        try {
            $middlewareDispatcher->handle($request);
            $this->fail('HTTP route should not have been found');
        } catch (HttpNotFoundException $exception) {
            $request = $exception->getRequest();

            // route is not available
            $route = $request->getAttribute('route');
            $this->assertNull($route);

            // routeParser is available
            $routeParser = $request->getAttribute('routeParser');
            $this->assertNotNull($routeParser);
            $this->assertInstanceOf(RouteParserInterface::class, $routeParser);

            // routingResults is available
            $routingResults = $request->getAttribute('routingResults');
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            $this->assertEquals(Dispatcher::NOT_FOUND, $routingResults->getRouteStatus());
        }
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage An unexpected error occurred while performing routing.
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

        // Prophesize the `RouteParserInterface` instance will be created.
        $routeParserProphecy = $this->prophesize(RouteParser::class);
        /** @var RouteParserInterface $routeParser */
        $routeParser = $routeParserProphecy->reveal();

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
        $m = new RoutingMiddleware($routeResolver, $routeParser);
        /** @noinspection PhpUnhandledExceptionInspection */
        $m->performRouting($request);
    }
}
