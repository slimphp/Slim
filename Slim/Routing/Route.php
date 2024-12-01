<?php

namespace Slim\Routing;

use Slim\Interfaces\MiddlewareCollectionInterface;

final class Route implements MiddlewareCollectionInterface
{
    use MiddlewareAwareTrait;

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

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getRouteGroup(): ?RouteGroup
    {
        return $this->group;
    }
}
