<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

namespace Slim\Interfaces;

use Psr\Http\Server\MiddlewareInterface;
use Slim\Routing\RouteGroup;

interface RouteInterface
{
    /**
     * Get route callable.
     *
     * @return callable|string
     */
    public function getHandler(): callable|string;

    /**
     * Set route name.
     *
     * @param string $name The route name
     *
     * @return RouteInterface
     */
    public function setName(string $name): RouteInterface;

    /**
     * Get route name.
     */
    public function getName(): ?string;

    /**
     * Get route pattern.
     *
     * @return string
     */
    public function getPattern(): string;

    /**
     * Get route HTTP methods
     *
     * @return array<string>
     */
    public function getMethods(): array;

    /**
     * Get route group.
     *
     * @return RouteGroup|null
     */
    public function getRouteGroup(): ?RouteGroup;

    /**
     * Retrieve a specific route argument.
     */
    public function getArgument(string $name, ?string $default = null): ?string;

    /**
     * Get route arguments.
     *
     * @return array<string, string>
     */
    public function getArguments(): array;

    /**
     * Set route arguments.
     *
     * @param array<string,string> $arguments The arguments.
     *
     * @return RouteInterface
     */
    public function setArguments(array $arguments): RouteInterface;

    /**
     * @return array<MiddlewareInterface|callable|string>
     */
    public function getMiddleware(): array;
}
