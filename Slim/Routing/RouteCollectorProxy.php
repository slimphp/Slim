<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;

class RouteCollectorProxy implements RouteCollectorProxyInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var CallableResolverInterface
     */
    protected $callableResolver;

    /**
     * @var ContainerInterface|null
     */
    protected $container;

    /**
     * @var RouteCollectorInterface
     */
    protected $routeCollector;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @param ResponseFactoryInterface     $responseFactory
     * @param CallableResolverInterface    $callableResolver
     * @param RouteCollectorInterface|null $routeCollector
     * @param ContainerInterface|null      $container
     * @param string                       $basePath
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        CallableResolverInterface $callableResolver,
        ContainerInterface $container = null,
        RouteCollectorInterface $routeCollector = null,
        string $basePath = ''
    ) {
        $this->responseFactory = $responseFactory;
        $this->callableResolver = $callableResolver;
        $this->container = $container;
        $this->routeCollector = $routeCollector;
        $this->basePath = $basePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getCallableResolver(): CallableResolverInterface
    {
        return $this->callableResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollector(): RouteCollectorInterface
    {
        return $this->routeCollector;
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * {@inheritdoc}
     */
    public function setBasePath(string $basePath): RouteCollectorProxyInterface
    {
        $this->basePath = $basePath;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $pattern, $callable): RouteInterface
    {
        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function post(string $pattern, $callable): RouteInterface
    {
        return $this->map(['POST'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $pattern, $callable): RouteInterface
    {
        return $this->map(['PUT'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function patch(string $pattern, $callable): RouteInterface
    {
        return $this->map(['PATCH'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $pattern, $callable): RouteInterface
    {
        return $this->map(['DELETE'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function options(string $pattern, $callable): RouteInterface
    {
        return $this->map(['OPTIONS'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function any(string $pattern, $callable): RouteInterface
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $methods, string $pattern, $callable): RouteInterface
    {
        $pattern = $this->basePath . $pattern;

        if ($this->container && $callable instanceof Closure) {
            $callable = $callable->bindTo($this->container);
        }

        return $this->routeCollector->map($methods, $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function group(string $pattern, $callable): RouteGroupInterface
    {
        $pattern = $this->basePath . $pattern;
        return $this->routeCollector->group($pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function redirect(string $from, $to, int $status = 302): RouteInterface
    {
        $handler = function () use ($to, $status) {
            $response = $this->responseFactory->createResponse($status);
            return $response->withHeader('Location', (string) $to);
        };

        return $this->get($from, $handler);
    }
}
