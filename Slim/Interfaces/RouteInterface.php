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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Route Interface
 *
 * @package Slim
 * @since   3.0.0
 */
interface RouteInterface
{

    /**
     * Retrieve a specific route argument
     *
     * @param string $name
     * @param string|null $default
     *
     * @return string|null
     */
    public function getArgument(string $name, $default = null);

    /**
     * Get route arguments
     *
     * @return string[]
     */
    public function getArguments(): array;

    /**
     * Get route name
     *
     * @return null|string
     */
    public function getName();

    /**
     * Get route pattern
     *
     * @return string
     */
    public function getPattern(): string;

    /**
     * Set a route argument
     *
     * @param string $name
     * @param string $value
     *
     * @return self
     */
    public function setArgument(string $name, string $value): self;

    /**
     * Replace route arguments
     *
     * @param string[] $arguments
     *
     * @return self
     */
    public function setArguments(array $arguments): self;

    /**
     * Set route name
     *
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): self;

    /**
     * @param MiddlewareInterface|string $middleware
     */
    public function add($middleware);

    /**
     * Add middleware
     *
     * This method prepends new middleware to the route's middleware stack.
     *
     * @param callable|string $callable The callback routine
     *
     * @return RouteInterface
     */
    public function addLegacy($callable);

    /**
     * Prepare the route for use
     *
     * @param ServerRequestInterface $request
     * @param array $arguments
     */
    public function prepare(ServerRequestInterface $request, array $arguments);

    /**
     * Run route
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function run(ServerRequestInterface $request): ResponseInterface;
}
