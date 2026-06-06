<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouterInterface;
use Slim\Routing\RouteMatch;

/**
 * Resolves the current request against the registered routes and stores
 * the immutable RouteMatch on the request attributes.
 *
 * This middleware should run before the endpoint runner middleware.
 */
final class RoutingMiddleware implements MiddlewareInterface
{
    private DispatcherInterface $dispatcher;

    private RouterInterface $router;

    private bool $decodePath;

    public function __construct(
        DispatcherInterface $dispatcher,
        RouterInterface $router,
        bool $decodePath = true
    ) {
        $this->dispatcher = $dispatcher;
        $this->router = $router;
        $this->decodePath = $decodePath;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestPath = $request->getUri()->getPath();
        $basePath = $this->router->getBasePath();
        $dispatchPath = $this->stripBasePath($requestPath, $basePath);

        $routingResult = $this->dispatcher->dispatch(
            $request->getMethod(),
            $this->decodePath ? rawurldecode($dispatchPath) : $dispatchPath
        );

        $routeMatch = $this->createRouteMatch($routingResult);
        $request = $request->withAttribute(RouteMatch::class, $routeMatch);

        return $handler->handle($request);
    }

    /**
     * @param array<int, mixed> $routingResult
     */
    private function createRouteMatch(array $routingResult): RouteMatch
    {
        $status = $routingResult[0] ?? null;

        return match ($status) {
            DispatcherInterface::FOUND => RouteMatch::found(
                $this->assertRoute($routingResult[1] ?? null),
                $this->extractArguments($routingResult[2] ?? null),
            ),
            DispatcherInterface::METHOD_NOT_ALLOWED => RouteMatch::methodNotAllowed(
                $this->extractAllowedMethods($routingResult[1] ?? null),
            ),
            DispatcherInterface::NOT_FOUND => RouteMatch::notFound(),
            default => throw new RuntimeException('Invalid routing result status returned by dispatcher.'),
        };
    }

    private function assertRoute(mixed $route): RouteInterface
    {
        if (!$route instanceof RouteInterface) {
            throw new RuntimeException('Dispatcher returned an invalid route for FOUND status.');
        }

        return $route;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractArguments(mixed $arguments): array
    {
        if ($arguments === null) {
            return [];
        }

        if (!is_array($arguments)) {
            throw new RuntimeException('Dispatcher returned invalid route arguments.');
        }

        return $arguments;
    }

    /**
     * @return list<string>
     */
    private function extractAllowedMethods(mixed $allowedMethods): array
    {
        if ($allowedMethods === null) {
            return [];
        }

        if (!is_array($allowedMethods)) {
            throw new RuntimeException('Dispatcher returned invalid allowed methods.');
        }

        return array_values($allowedMethods);
    }

    private function stripBasePath(string $uri, string $basePath): string
    {
        // No base path configured
        if ($basePath === '' || $basePath === '/') {
            return $uri;
        }

        return '/' . ltrim(rtrim(substr($uri, strlen($basePath)), '/'), '/');
    }
}
