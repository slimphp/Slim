<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim;

use Slim\Interfaces\CallableResolverInterface;

class DeferredCallable
{
    /**
     * @var callable|string
     */
    protected $callable;

    /**
     * @var CallableResolverInterface|null
     */
    protected $callableResolver;

    /**
     * DeferredMiddleware constructor.
     *
     * @param callable|string $callable
     * @param CallableResolverInterface|null $resolver
     */
    public function __construct($callable, CallableResolverInterface $resolver = null)
    {
        $this->callable = $callable;
        $this->callableResolver = $resolver;
    }

    public function __invoke()
    {
        $callable = $this->callable;
        if ($this->callableResolver) {
            $callable = $this->callableResolver->resolve($callable);
        }
        $args = func_get_args();

        return call_user_func_array($callable, $args);
    }
}
