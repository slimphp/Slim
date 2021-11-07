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
     * Get route invocation strategy
     *
     * @return InvocationStrategyInterface
     */
    public function getInvocationStrategy(): InvocationStrategyInterface;

    /**
     * Set route invocation strategy
     *
     * @param InvocationStrategyInterface $invocationStrategy
     * @return RouteInterface
     */
    public function setInvocationStrategy(InvocationStrategyInterface $invocationStrategy): RouteInterface;

    /**
     * Get route methods
     *
     * @return string[]
     */
    public function getMethods(): array;

    /**
     * Get route pattern
     *
     * @return string
     */
    public function getPattern(): string;

    /**
     * Set route pattern
     *
     * @param string $pattern
     * @return RouteInterface
     */
    public function setPattern(string $pattern): RouteInterface;

    /**
     * Get route callable
     *
     * @return callable|string
     */
    public function getCallable();

    /**
     * Set route callable
     *
     * @param callable|string $callable
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
     * Set route name
     *
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): RouteInterface;

    /**
     * Get the route's unique identifier
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Retrieve a specific route argument
     *
     * @param string      $name
     * @param string|null $default
     *
     * @return string|null
     */
    public function getArgument(string $name, ?string $default = null): ?string;

    /**
     * Get route arguments
     *
     * @return array<string, string>
     */
    public function getArguments(): array;

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
     * @param array<string, string> $arguments
     *
     * @return self
     */
    public function setArguments(array $arguments): RouteInterface;

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
     * @param array<string, string> $arguments
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
