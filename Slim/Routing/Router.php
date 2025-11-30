<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

namespace Slim\Routing;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouterInterface;

final class Router implements RouterInterface, RequestHandlerInterface
{
    use RouteCollectionTrait;

    use MiddlewareCollectionTrait;

    private PipelineRunner $pipelineRunner;

    private RouteCollector $collector;

    private string $basePath = '';

    public function __construct(PipelineRunner $pipelineRunner)
    {
        $this->collector = new RouteCollector(new Std(), new GroupCountBased());
        $this->pipelineRunner = $pipelineRunner;
    }

    /**
     * @param array<string> $methods
     * @param string $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function map(array $methods, string $path, callable|string $handler): Route
    {
        if (!$methods) {
            throw new InvalidArgumentException('HTTP methods array cannot be empty');
        }

        $routePattern = $this->normalizePath($path);
        $route = new Route($methods, $routePattern, $handler, null);

        $this->collector->addRoute($methods, $routePattern, $route);

        return $route;
    }

    public function group(string $path, callable $handler): RouteGroup
    {
        $routePattern = $this->normalizePath($path);
        $routeGroup = new RouteGroup($routePattern, $handler, $this->getRouteCollector());
        $this->collector->addGroup($routePattern, $routeGroup);

        return $routeGroup;
    }

    public function getRouteCollector(): RouteCollector
    {
        return $this->collector;
    }

    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->pipelineRunner
            ->withPipeline($this->getMiddleware())
            ->handle($request);
    }

    /**
     * Normalizes a path by ensuring:
     * - Starts with a forward slash
     * - No trailing slash (unless root path)
     * - No double slashes
     * @param string $path
     */
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
        return preg_replace('#/+#', '/', $path) ?? '';
    }
}
