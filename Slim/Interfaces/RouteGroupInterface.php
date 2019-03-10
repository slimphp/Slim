<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use Psr\Http\Server\MiddlewareInterface;

interface RouteGroupInterface
{
    /**
     * @return RouteGroupInterface
     */
    public function collectRoutes(): RouteGroupInterface;

    /**
     * @param MiddlewareInterface|string|callable $middleware
     * @return RouteGroupInterface
     */
    public function add($middleware): RouteGroupInterface;

    /**
     * @param MiddlewareInterface $middleware
     * @return RouteGroupInterface
     */
    public function addMiddleware(MiddlewareInterface $middleware): RouteGroupInterface;

    /**
     * @return array
     */
    public function getMiddleware(): array;

    /**
     * @return string
     */
    public function getPattern(): string;
}
