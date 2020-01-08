<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Closure;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Slim\Interfaces\AdvancedCallableResolverInterface;

use function class_exists;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function json_encode;
use function preg_match;
use function sprintf;

final class CallableResolver implements AdvancedCallableResolverInterface
{
    /**
     * @var string
     */
    public static $callablePattern = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';

    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @param ContainerInterface|null $container
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($toResolve): callable
    {
        return $this->resolveSlimNotation($toResolve);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveRoute($toResolve): callable
    {
        return $this->resolveSlimNotation($toResolve, 'handle');
    }

    /**
     * {@inheritdoc}
     */
    public function resolveMiddleware($toResolve): callable
    {
        return $this->resolveSlimNotation($toResolve, 'process');
    }

    /**
     * @param callable|string $toResolve
     * @param string          $defaultMethod
     *
     * @throws RuntimeException
     *
     * @return callable
     */
    private function resolveSlimNotation($toResolve, string $defaultMethod = '__invoke'): callable
    {
        if (is_callable($toResolve)) {
            $resolved = $toResolve;
        } elseif (is_string($toResolve)) {
            if (preg_match(CallableResolver::$callablePattern, $toResolve, $matches)) {
                $callable = [$this->resolveClass($matches[1]), $matches[2]];
            } else {
                $instance = $this->resolveClass($toResolve);
                if (is_callable($instance)) {
                    $callable = $instance;
                } else {
                    $callable = [$instance, $defaultMethod];
                }
            }

            $resolved = $this->assertCallable($callable, $toResolve);
        } else {
            $callable = [$toResolve, $defaultMethod];
            $resolved = $this->assertCallable($callable, $toResolve);
        }

        return $this->bindToContainer($resolved);
    }

    /**
     * @param string $class
     *
     * @throws RuntimeException
     *
     * @return mixed
     */
    private function resolveClass(string $class)
    {
        if ($this->container && $this->container->has($class)) {
            $instance = $this->container->get($class);
        } elseif (class_exists($class)) {
            $instance = new $class($this->container);
        } else {
            throw new RuntimeException(sprintf('Callable %s does not exist', $class));
        }

        return $instance;
    }

    /**
     * @param mixed $resolved
     * @param mixed $toResolve
     *
     * @throws RuntimeException
     *
     * @return callable
     */
    private function assertCallable($resolved, $toResolve): callable
    {
        if (!is_callable($resolved)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_callable($toResolve) || is_object($toResolve) || is_array($toResolve) ?
                json_encode($toResolve) : $toResolve
            ));
        }
        return $resolved;
    }

    /**
     * @param callable $callable
     *
     * @return callable
     */
    private function bindToContainer(callable $callable): callable
    {
        if (is_array($callable) && $callable[0] instanceof Closure) {
            $callable = $callable[0];
        }
        if ($this->container && $callable instanceof Closure) {
            $callable = $callable->bindTo($this->container);
        }
        return $callable;
    }
}
