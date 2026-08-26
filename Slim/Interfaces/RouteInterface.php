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
     * @return array<MiddlewareInterface|callable|string>
     */
    public function getMiddleware(): array;
}
