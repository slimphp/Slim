<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use FastRoute\Dispatcher\GroupCountBased;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use Slim\Routing\Router;
use Slim\Routing\RoutingResults;
use Slim\Routing\UrlGenerator;

/**
 * Middleware for resolving routes.
 *
 * This middleware handles the routing process by dispatching the request to the appropriate route
 * based on the HTTP method and URI. It then stores the routing results in the request attributes.
 */
final class RoutingMiddleware implements MiddlewareInterface
{
    private Router $router;

    private UrlGenerator $urlGenerator;

    public function __construct(Router $router, UrlGenerator $urlGenerator)
    {
        $this->router = $router;
        $this->urlGenerator = $urlGenerator;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Dispatch
        $dispatcher = new GroupCountBased($this->router->getRouteCollector()->getData());

        $httpMethod = $request->getMethod();
        $uri = $request->getUri()->getPath();

        // Determine base path
        $basePath = $request->getAttribute(RouteContext::BASE_PATH) ?? $this->router->getBasePath();

        $dispatcherUri = $uri;
        if ($basePath) {
            // Remove base path for the dispatcher
            $dispatcherUri = substr($dispatcherUri, strlen($basePath));
            $dispatcherUri = $this->normalizePath($dispatcherUri);
        }

        $dispatcherUri = rawurldecode($dispatcherUri);
        $routeInfo = $dispatcher->dispatch($httpMethod, $dispatcherUri);
        $routeStatus = (int)$routeInfo[0];
        $routingResults = null;

        if ($routeStatus === RoutingResults::FOUND) {
            $routingResults = new RoutingResults(
                $routeStatus,
                $routeInfo[1],
                $request->getMethod(),
                $uri,
                $routeInfo[2]
            );
        }

        if ($routeStatus === RoutingResults::METHOD_NOT_ALLOWED) {
            $routingResults = new RoutingResults(
                $routeStatus,
                null,
                $request->getMethod(),
                $uri,
                $routeInfo[1],
            );
        }

        if ($routeStatus === RoutingResults::NOT_FOUND) {
            $routingResults = new RoutingResults($routeStatus, null, $request->getMethod(), $uri);
        }

        if ($routingResults) {
            $request = $request
                ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults)
                ->withAttribute(RouteContext::URL_GENERATOR, $this->urlGenerator);
        }

        return $handler->handle($request);
    }

    private function normalizePath(string $path): string
    {
        // If path is empty or just a slash, return single slash
        if ($path === '' || $path === '/') {
            return '/';
        }

        // Ensure path starts with a slash
        $path = '/' . ltrim($path, '/');

        // Remove trailing slash unless it's the root path
        $path = rtrim($path, '/');

        // Replace multiple consecutive slashes with a single slash
        return preg_replace('#/+#', '/', $path);
    }
}
