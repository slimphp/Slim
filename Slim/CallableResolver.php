<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use RuntimeException;
use Interop\Container\ContainerInterface;
use Slim\Interfaces\CallableResolverInterface;

/**
 * This class resolves a string of the format 'class:method' into a closure
 * that can be dispatched. It is itself invokable as it lazily resolves the string
 * when it is invoked.
 */
final class CallableResolver implements CallableResolverInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $toResolve;

    /**
     * @var callable
     */
    private $resolved;

    /**
     * @param ContainerInterface $container
     * @param string             $toResolve
     */
    public function __construct(ContainerInterface $container, $toResolve = null)
    {
        $this->toResolve = $toResolve;
        $this->container = $container;
    }


    /**
     * Receive a string that is to be resolved to a callable
     *
     * @param  string $toResolve
     */
    public function setToResolve($toResolve)
    {
        $this->toResolve = $toResolve;
    }

    /**
     * Resolve toResolve into a closure that that the router can dispatch.
     *
     * If toResolve is of the format 'class:method', then try to extract 'class'
     * from the container otherwise instantiate it and then dispatch 'method'.
     *
     * @return \Closure
     *
     * @throws RuntimeException if the callable does not exist
     * @throws RuntimeException if the callable is not resolvable
     */
    private function resolve()
    {
        // if it's callable, then it's already resolved
        if (is_callable($this->toResolve)) {
            $this->resolved = $this->toResolve;

        // check for slim callable as "class:method"
        } elseif (is_string($this->toResolve)) {
            $callable_pattern = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';
            if (preg_match($callable_pattern, $this->toResolve, $matches)) {
                $class = $matches[1];
                $method = $matches[2];

                if ($this->container->has($class)) {
                    $this->resolved = [$this->container->get($class), $method];
                } else {
                    if (!class_exists($class)) {
                        throw new RuntimeException(sprintf('Callable %s does not exist', $class));
                    }
                    $this->resolved = [new $class, $method];
                }
                if (!is_callable($this->resolved)) {
                    throw new RuntimeException(sprintf('%s is not resolvable', $this->toResolve));
                }
            } else {
                throw new RuntimeException(sprintf('%s is not resolvable', $this->toResolve));
            }
        }
    }

    /**
     * Invoke the resolved callable.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke()
    {
        if (!isset($this->resolved)) {
            $this->resolve();
        }
        return call_user_func_array($this->resolved, func_get_args());
    }
}
