<?php

namespace Slim\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\Routing\PipelineRunner;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

/**
 * This middleware processes the routing results to determine if a route was found,
 * if the HTTP method is allowed, or if the route was not found. Based on these results,
 * it either executes the found route's handler with its associated middleware stack or
 * throws appropriate exceptions for 404 Not Found or 405 Method Not Allowed.
 */
final class EndpointMiddleware implements MiddlewareInterface
{
    private ContainerResolverInterface $resolver;

    private RouteInvokerMiddleware $routeInvokerMiddleware;

    public function __construct(
        ContainerResolverInterface $containerResolver,
        RouteInvokerMiddleware $routeInvokerMiddleware,
    ) {
        $this->resolver = $containerResolver;
        $this->routeInvokerMiddleware = $routeInvokerMiddleware;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /* @var RoutingResults $routingResults */
        $routingResults = $request->getAttribute(RouteContext::ROUTING_RESULTS);

        if (!$routingResults instanceof RoutingResults) {
            throw new RuntimeException(
                'An unexpected error occurred while handling routing results. Routing results are not available.',
            );
        }

        $routeStatus = $routingResults->getRouteStatus();
        if ($routeStatus === RoutingResults::FOUND) {
            return $this->handleFound($request, $routingResults);
        }

        if ($routeStatus === RoutingResults::NOT_FOUND) {
            // 404 Not Found
            throw new HttpNotFoundException($request);
        }

        if ($routeStatus === RoutingResults::METHOD_NOT_ALLOWED) {
            // 405 Method Not Allowed
            $exception = new HttpMethodNotAllowedException($request);
            $exception->setAllowedMethods($routingResults->getAllowedMethods());

            throw $exception;
        }

        throw new RuntimeException('An unexpected error occurred while endpoint handling.');
    }

    private function handleFound(
        ServerRequestInterface $request,
        RoutingResults $routingResults,
    ): ResponseInterface {
        $route = $routingResults->getRoute() ?? throw new RuntimeException('Route not found.');

        // Collect route specific middleware
        $middlewares = $this->collectRouteMiddleware($route);

        // Invoke the route/group specific middleware stack
        $middlewares[] = $this->routeInvokerMiddleware->withHandler(
            $this->resolver->resolveRoute($route->getHandler()),
            $routingResults->getRouteArguments(),
        );

        return (new PipelineRunner($middlewares))->handle($request);
    }

    /**
     * @param Route $route
     * @return array<MiddlewareInterface|callable> List of middleware
     */
    private function collectRouteMiddleware(Route $route): array
    {
        $middlewares = [];

        // Append group specific middleware from all parent route groups
        $group = $route->getRouteGroup();

        while ($group) {
            // Prepend group middleware so outer groups come first
            $middlewares = array_merge($group->getMiddleware(), $middlewares);
            $group = $group->getRouteGroup();
        }

        // Append endpoint-specific middleware
        $middlewares = array_merge($middlewares, $route->getMiddleware());

        // Resolve middleware
        foreach ($middlewares as $key => $value) {
            $middlewares[$key] = $this->resolver->resolveMiddleware($value);
        }

        return $middlewares;
    }

}
