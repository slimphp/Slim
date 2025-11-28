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
    private ContainerResolverInterface $containerResolver;
    private ResponseFactoryInterface $responseFactory;
    private RequestHandlerInvocationStrategyInterface $invocationStrategy;

    public function __construct(
        ContainerResolverInterface $containerResolver,
        ResponseFactoryInterface $responseFactory,
        RequestHandlerInvocationStrategyInterface $invocationStrategy,
    ) {
        $this->containerResolver = $containerResolver;
        $this->responseFactory = $responseFactory;
        $this->invocationStrategy = $invocationStrategy;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /* @var RoutingResults $routingResults */
        $routingResults = $request->getAttribute(RouteContext::ROUTING_RESULTS);

        if (!$routingResults instanceof RoutingResults) {
            throw new RuntimeException(
                'An unexpected error occurred while handling routing results. Routing results are not available.'
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
        $response = $this->responseFactory->createResponse();

        // Collect middleware
        $middlewares = $this->getRouteMiddleware($route);

        $middlewares[] = $this->createRouteHandlerMiddleware($request, $response, $routingResults);

        // Tunnel the response object through the route/group specific middleware stack
        $middlewares[] = $this->createResponseMiddleware($response);

        return (new PipelineRunner($middlewares))->handle($request);
    }

    private function getRouteMiddleware(Route $route): array
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
        return array_merge($middlewares, $route->getMiddleware());
    }

    private function createRouteHandlerMiddleware(
        ServerRequestInterface $request,
        ResponseInterface $response,
        RoutingResults $routingResults
    ): callable {
        $containerResolver = $this->containerResolver;
        $invocationStrategy = $this->invocationStrategy;

        return function () use (
            $request,
            $response,
            $routingResults,
            $containerResolver,
            $invocationStrategy
        ) {
            // Get handler
            $actionHandler = $routingResults->getRoute()->getHandler();
            $vars = $routingResults->getRouteArguments();
            $actionHandler = $containerResolver->resolveRoute($actionHandler);

            // Invoke action handler
            return call_user_func($invocationStrategy, $actionHandler, $request, $response, $vars);
        };
    }

    private function createResponseMiddleware(ResponseInterface $response): MiddlewareInterface
    {
        return new class ($response) implements MiddlewareInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                return $this->response;
            }
        };
    }
}
