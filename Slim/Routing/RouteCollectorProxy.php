<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;

/**
 * @template TContainerInterface of (ContainerInterface|null)
 * @template-implements RouteCollectorProxyInterface<TContainerInterface>
 */
class RouteCollectorProxy implements RouteCollectorProxyInterface
{
    protected ResponseFactoryInterface $responseFactory;

    protected CallableResolverInterface $callableResolver;

    /** @var TContainerInterface */
    protected ?ContainerInterface $container = null;

    protected RouteCollectorInterface $routeCollector;

    protected string $groupPattern;

    /**
     * @param TContainerInterface $container
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        CallableResolverInterface $callableResolver,
        ?ContainerInterface $container = null,
        ?RouteCollectorInterface $routeCollector = null,
        string $groupPattern = ''
    ) {
        $this->responseFactory = $responseFactory;
        $this->callableResolver = $callableResolver;
        $this->container = $container;
        $this->routeCollector = $routeCollector ?? new RouteCollector($responseFactory, $callableResolver, $container);
        $this->groupPattern = $groupPattern;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getCallableResolver(): CallableResolverInterface
    {
        return $this->callableResolver;
    }

    /**
     * {@inheritdoc}
     * @return TContainerInterface
     */
    #[\Override]
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getRouteCollector(): RouteCollectorInterface
    {
        return $this->routeCollector;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getBasePath(): string
    {
        return $this->routeCollector->getBasePath();
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function setBasePath(string $basePath): RouteCollectorProxyInterface
    {
        $this->routeCollector->setBasePath($basePath);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function get(string $pattern, $callable): RouteInterface
    {
        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function post(string $pattern, $callable): RouteInterface
    {
        return $this->map(['POST'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function put(string $pattern, $callable): RouteInterface
    {
        return $this->map(['PUT'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function patch(string $pattern, $callable): RouteInterface
    {
        return $this->map(['PATCH'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function delete(string $pattern, $callable): RouteInterface
    {
        return $this->map(['DELETE'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function options(string $pattern, $callable): RouteInterface
    {
        return $this->map(['OPTIONS'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function any(string $pattern, $callable): RouteInterface
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function map(array $methods, string $pattern, $callable): RouteInterface
    {
        $pattern = $this->groupPattern . $pattern;

        return $this->routeCollector->map($methods, $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function group(string $pattern, $callable): RouteGroupInterface
    {
        $pattern = $this->groupPattern . $pattern;

        return $this->routeCollector->group($pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function redirect(string $from, $to, int $status = 302): RouteInterface
    {
        $responseFactory = $this->responseFactory;

        $handler = function () use ($to, $status, $responseFactory) {
            $response = $responseFactory->createResponse($status);
            return $response->withHeader('Location', (string) $to);
        };

        return $this->get($from, $handler);
    }
}
