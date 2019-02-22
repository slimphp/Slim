<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Class DeferredResolutionMiddleware
 * @package Slim\Middleware
 */
class DeferredResolutionMiddleware implements MiddlewareInterface
{
    /**
     * @var string
     */
    private $resolvable;

    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * DeferredResolutionMiddleware constructor.
     * @param string                    $resolvable
     * @param ContainerInterface|null   $container
     */
    public function __construct(string $resolvable, ContainerInterface $container = null)
    {
        $this->resolvable = $resolvable;
        $this->container = $container;
    }

    /**
     * @return MiddlewareInterface
     */
    protected function resolve(): MiddlewareInterface
    {
        $resolved = $this->resolvable;

        if ($this->container && $this->container->has($this->resolvable)) {
            $resolved = $this->container->get($this->resolvable);

            if ($resolved instanceof MiddlewareInterface) {
                return $resolved;
            }
        }

        if (is_subclass_of($resolved, MiddlewareInterface::class)) {
            return new $resolved();
        }

        if (is_callable($resolved)) {
            $closure = ($resolved instanceof Closure) ? $resolved : Closure::fromCallable($resolved);
            return new ClosureMiddleware($closure);
        }

        throw new RuntimeException(sprintf(
            '%s is not resolvable',
            $this->resolvable
        ));
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->resolve()->process($request, $handler);
    }
}
