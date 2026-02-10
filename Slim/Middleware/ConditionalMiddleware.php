<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Conditional Middleware
 *
 * This middleware allows you to conditionally execute another middleware based on
 * a given condition (callable). If the condition returns true, the wrapped middleware
 * is executed. If it returns false, the request is passed directly to the next handler.
 *
 * This is useful for applying middleware only to certain routes, methods, or based
 * on request attributes without having to duplicate middleware logic.
 *
 * @api
 */
class ConditionalMiddleware implements MiddlewareInterface
{
    /**
     * The middleware to conditionally execute
     *
     * @var MiddlewareInterface
     */
    protected MiddlewareInterface $middleware;

    /**
     * The condition callable
     *
     * @var callable
     */
    protected $condition;

    /**
     * Constructor
     *
     * @param MiddlewareInterface $middleware The middleware to wrap
     * @param callable $condition A callable that receives ServerRequestInterface and returns bool.
     *                            Return true to execute middleware, false to skip it.
     */
    public function __construct(MiddlewareInterface $middleware, callable $condition)
    {
        $this->middleware = $middleware;
        $this->condition = $condition;
    }

    /**
     * Process the request through conditional middleware
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Call the condition with the request
        if (call_user_func($this->condition, $request)) {
            // Condition returned true, execute the wrapped middleware
            return $this->middleware->process($request, $handler);
        }

        // Condition returned false, skip the middleware and pass to next handler
        return $handler->handle($request);
    }

    /**
     * Create a conditional middleware that only executes for specific paths
     *
     * @param MiddlewareInterface $middleware
     * @param string $pathPrefix Path prefix to match (e.g., '/admin')
     * @return self
     */
    public static function forPath(MiddlewareInterface $middleware, string $pathPrefix): self
    {
        return new self(
            $middleware,
            fn(ServerRequestInterface $request) => str_starts_with($request->getUri()->getPath(), $pathPrefix)
        );
    }

    /**
     * Create a conditional middleware that only executes for specific HTTP methods
     *
     * @param MiddlewareInterface $middleware
     * @param string[] $methods HTTP methods to match (e.g., ['POST', 'PUT', 'DELETE'])
     * @return self
     */
    public static function forMethods(MiddlewareInterface $middleware, array $methods): self
    {
        $methods = array_map('strtoupper', $methods);
        return new self(
            $middleware,
            fn(ServerRequestInterface $request) => in_array($request->getMethod(), $methods, true)
        );
    }

    /**
     * Create a conditional middleware that only executes for specific paths AND methods
     *
     * @param MiddlewareInterface $middleware
     * @param string $pathPrefix Path prefix to match (e.g., '/api')
     * @param string[] $methods HTTP methods to match (e.g., ['POST', 'PUT'])
     * @return self
     */
    public static function forPathAndMethods(
        MiddlewareInterface $middleware,
        string $pathPrefix,
        array $methods
    ): self {
        $methods = array_map('strtoupper', $methods);
        return new self(
            $middleware,
            fn(ServerRequestInterface $request) =>
            str_starts_with($request->getUri()->getPath(), $pathPrefix) &&
                in_array($request->getMethod(), $methods, true)
        );
    }
}
