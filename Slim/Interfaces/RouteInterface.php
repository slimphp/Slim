<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

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
     * Get route callable
     *
     * @return callable|string
     */
    public function getCallable();

    /**
     * Set route callable
     *
     * @param $callable
     * @return RouteInterface
     */
    public function setCallable($callable): RouteInterface;

    /**
     * Get route name
     *
     * @return null|string
     */
    public function getName(): ?string;

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
    public function setArgument(string $name, string $value): RouteInterface;

    /**
     * Replace route arguments
     *
     * @param string[] $arguments
     *
     * @return self
     */
    public function setArguments(array $arguments): RouteInterface;

    /**
     * Set route name
     *
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): RouteInterface;

    /**
     * @param MiddlewareInterface|string|callable $middleware
     * @return RouteInterface
     */
    public function add($middleware): RouteInterface;

    /**
     * @param MiddlewareInterface $middleware
     * @return RouteInterface
     */
    public function addMiddleware(MiddlewareInterface $middleware): RouteInterface;

    /**
     * Prepare the route for use
     *
     * @param array $arguments
     * @return RouteInterface
     */
    public function prepare(array $arguments): RouteInterface;

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
