<?php


namespace Slim;

use Closure;
use Interop\Container\ContainerInterface;

class DeferredMiddleware
{
    use CallableResolverAwareTrait;

    private $callable;
    /** @var  ContainerInterface */
    private $container;

    /**
     * DeferredMiddleware constructor.
     * @param $callable
     * @param $container
     */
    public function __construct($callable, $container)
    {
        $this->callable = $callable;
        $this->container = $container;
    }

    public function __invoke()
    {
        $callable = $this->resolveCallable($this->callable);
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this->container);
        }

        $args = func_get_args();

        return call_user_func_array($callable, $args);
    }
}
