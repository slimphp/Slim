<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Psr\Http\Server\MiddlewareInterface;
use Slim\Interfaces\CallableResolverInterface;

class DeferredCallable
{
    /**
     * @var MiddlewareInterface|callable|string
     */
    protected $callable;

    /**
     * @var CallableResolverInterface|null
     */
    protected $callableResolver;

    /**
     * @var bool
     */
    protected $resolveMiddleware;

    /**
     * DeferredMiddleware constructor.
     *
     * @param MiddlewareInterface|callable|string $callable
     * @param CallableResolverInterface|null $resolver
     * @param bool $resolveMiddleware
     */
    public function __construct($callable, CallableResolverInterface $resolver = null, $resolveMiddleware = false)
    {
        $this->callable = $callable;
        $this->callableResolver = $resolver;
        $this->resolveMiddleware = $resolveMiddleware;
    }

    public function __invoke(...$args)
    {
        /** @var callable $callable */
        $callable = $this->callable;
        if ($this->callableResolver) {
            $callable = $this->callableResolver->resolve($callable, $this->resolveMiddleware);
        }

        return $callable(...$args);
    }
}
