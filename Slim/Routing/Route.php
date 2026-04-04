<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

namespace Slim\Routing;

use Slim\Interfaces\MiddlewareCollectionInterface;
use Slim\Interfaces\RouteInterface;

use function array_key_exists;

final class Route implements RouteInterface, MiddlewareCollectionInterface
{
    use MiddlewareCollectionTrait;

    /**
     * @var array<string>
     */
    private array $methods;

    /**
     * The route matching pattern
     */
    private string $pattern;

    /**
     * @var callable|string
     */
    private $handler;

    /**
     * Route name
     */
    private ?string $name = null;

    /**
     * Parent route group
     */
    private ?RouteGroup $group;

    /**
     * Route parameters
     *
     * @var array<string, string>
     */
    private array $arguments = [];

    /**
     * @param array<string> $methods
     * @param string $pattern
     * @param callable|string $handler
     * @param ?RouteGroup $group
     */
    public function __construct(array $methods, string $pattern, callable|string $handler, ?RouteGroup $group = null)
    {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->group = $group;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler(): callable|string
    {
        return $this->handler;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteGroup(): ?RouteGroup
    {
        return $this->group;
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument(string $name, ?string $default = null): ?string
    {
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }
        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments): RouteInterface
    {
        $this->arguments = $arguments;

        return $this;
    }
}
