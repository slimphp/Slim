<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=0);

namespace Slim;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Interfaces\AdvancedCallableResolverInterface;

use function class_exists;
use function is_array;
use function is_callable;
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
        return $this->resolveSlimNotation($toResolve);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveMiddleware($toResolve): callable
    {
        return $this->resolveSlimNotation($toResolve);
    }

    /**
     * @param callable|string $toResolve
     * @return callable
     */
    private function resolveSlimNotation($toResolve): callable
    {
        if (is_callable($toResolve)) {
            $resolved = $toResolve;
        } elseif (is_string($toResolve)) {
            if (preg_match(CallableResolver::$callablePattern, $toResolve, $matches)) {
                $instance = $this->resolveClass($matches[1]);
                $method = $matches[2];
            } else {
                $instance = $this->resolveClass($toResolve);

                if ($this->isRoute($instance)) {
                    $method = 'handle';
                } elseif ($this->isMiddleware($instance)) {
                    $method = 'process';
                } else {
                    $method = '__invoke';
                }
            }

            $resolved = $this->assertCallable([$instance, $method], $toResolve);
        } else {
            $instance = $toResolve;
            if ($this->isRoute($instance)) {
                $method = 'handle';
            } elseif ($this->isMiddleware($instance)) {
                $method = 'process';
            } else {
                $method = '__invoke';
            }
            $resolved = $this->assertCallable([$instance, $method], $toResolve);
        }

        return $this->bindToContainer($resolved);
    }

    /**
     * @param string $class
     * @return mixed
     */
    private function resolveClass(string $class)
    {
        if ($this->container && $this->container->has($class)) {
            $instance = $this->container->get($class);
        } elseif (class_exists($class)) {
            $instance = new $class($this->container);
        } else {
            throw new RuntimeException(sprintf('Callable %s is not resolvable', $class));
        }

        return $instance;
    }

    /**
     * @param mixed $toResolve
     *
     * @return bool
     */
    private function isRoute($toResolve): bool
    {
        return $toResolve instanceof RequestHandlerInterface;
    }
    /**
     * @param mixed $toResolve
     *
     * @return bool
     */
    private function isMiddleware($toResolve): bool
    {
        return $toResolve instanceof MiddlewareInterface;
    }

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
