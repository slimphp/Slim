<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

namespace Slim\Routing;

use Slim\Interfaces\MiddlewareCollectionInterface;

final class Route implements MiddlewareCollectionInterface
{
    use MiddlewareCollectionTrait;

    /**
     * @var array<string>
     */
    private array $methods;

    private string $pattern;

    /**
     * @var callable|string
     */
    private $handler;

    private ?string $name = null;

    private ?RouteGroup $group;

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

    public function getHandler(): callable|string
    {
        return $this->handler;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return array<string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getRouteGroup(): ?RouteGroup
    {
        return $this->group;
    }
}
