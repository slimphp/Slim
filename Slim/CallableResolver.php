<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim;

use RuntimeException;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\CallableResolverInterface;

/**
 * This class resolves a string of the format 'class:method' into a closure
 * that can be dispatched.
 */
final class CallableResolver implements CallableResolverInterface
{
    const CALLABLE_PATTERN = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Resolve toResolve into a closure so that the router can dispatch.
     *
     * If toResolve is of the format 'class:method', then try to extract 'class'
     * from the container otherwise instantiate it and then dispatch 'method'.
     *
     * @param mixed $toResolve
     *
     * @return callable
     *
     * @throws RuntimeException if the callable does not exist
     * @throws RuntimeException if the callable is not resolvable
     */
    public function resolve($toResolve)
    {
        if (is_callable($toResolve)) {
            return $toResolve;
        }

        if (!is_string($toResolve)) {
            $this->assertCallable($toResolve);
        }

        // check for slim callable as "class:method"
        if (preg_match(self::CALLABLE_PATTERN, $toResolve, $matches)) {
            $resolved = $this->resolveCallable($matches[1], $matches[2]);
            $this->assertCallable($resolved);

            return $resolved;
        }

        $resolved = $this->resolveCallable($toResolve);
        $this->assertCallable($resolved);

        return $resolved;
    }

    /**
     * Check if string is something in the DIC
     * that's callable or is a class name which has an __invoke() method.
     *
     * @param string $class
     * @param string $method
     * @return callable
     */
    protected function resolveCallable($class, $method = '__invoke')
    {
        if ($this->container->has($class)) {
            return [$this->container->get($class), $method];
        }

        return [$this->constructCallable($class), $method];
    }

    /**
     * Check if string is a class that exists
     * and if so resolve the constructor arguments from the DiC
     *
     * @param string $class
     * @return object
     *
     * @throws \RuntimeException if the callable does not exist
     */
    protected function constructCallable($class)
    {
        if (!class_exists($class)) {
            throw new RuntimeException(sprintf('Callable %s does not exist', $class));
        }

        $reflection = new \ReflectionClass($class);

        $constructor = $reflection->getConstructor();
        $args = [];
        if ($constructor !== null) {
            $args = $this->resolveMethodArguments($constructor);
        }

        $callable = $reflection->newInstanceArgs($args);
        return $callable;
    }

    /**
     * Attempt to resolve method arguments from the DiC or pass in the container itself
     *
     * @param \ReflectionMethod $method
     * @return array
     *
     * @throws \RuntimeException if an argument is not type hinted or a dependency cannot be resolved
     */
    protected function resolveMethodArguments(\ReflectionMethod $method)
    {
        $args = [];

        $parameters = $method->getParameters();
        if (count($parameters) === 1 && $parameters[0]->getClass() === null) {
            // If there is a single un-type hinted argument then inject the container.
            // This is for backwards compatibility.
            $args[] = $this->container;
        } else {
            foreach ($method->getParameters() as $parameter) {
                $class = $parameter->getClass();
                if (!is_object($class)) {
                    throw new RuntimeException(sprintf('Argument %d for %s::%s must be type hinted',
                        $parameter->getPosition(), $method->getDeclaringClass()->getName(), $method->getName()));
                }
                if ($class->getName() == ContainerInterface::class) {
                    $args[] = $this->container;
                    continue;
                }
                if (!$this->container->has($class->getName())) {
                    throw new RuntimeException(sprintf('%s is not resolvable', $class->getName()));
                }

                $args[] = $this->container->get($class->getName());
            }
        }

        return $args;
    }

    /**
     * @param Callable $callable
     *
     * @throws \RuntimeException if the callable is not resolvable
     */
    protected function assertCallable($callable)
    {
        if (!is_callable($callable)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_array($callable) || is_object($callable) ? json_encode($callable) : $callable
            ));
        }
    }
}
