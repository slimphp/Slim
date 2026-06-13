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
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouterInterface;

final class Router implements RouterInterface, RequestHandlerInterface
{
    use RouteCollectionTrait;

    use MiddlewareCollectionTrait;

    private PipelineRunner $pipelineRunner;

    private RouteCollector $collector;

    private ?string $basePath = null;

    public function __construct(PipelineRunner $pipelineRunner)
    {
        $this->collector = new RouteCollector(new Std(), new GroupCountBased());
        $this->pipelineRunner = $pipelineRunner;
    }

    public function map(array $methods, string $path, callable|string $handler): RouteInterface
    {
        if (!$methods) {
            throw new InvalidArgumentException('HTTP methods array cannot be empty');
        }

        $route = new Route($methods, $path, $handler, null);

        $this->collector->addRoute($methods, $path, $route);

        return $route;
    }

    public function group(string $path, callable $handler): RouteGroup
    {
        $routeGroup = new RouteGroup($path, $handler, $this->getRouteCollector());
        $this->collector->addGroup($path, $routeGroup);

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

    public function getBasePath(): ?string
    {
        return $this->basePath;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->pipelineRunner
            ->withPipeline($this->getMiddleware())
            ->handle($request);
    }
}
