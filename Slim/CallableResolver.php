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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Interfaces\AdvancedCallableResolverInterface;

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
        if (is_callable($toResolve)) {
            return $this->bindToContainer($toResolve);
        }
        $resolved = $toResolve;
        if (is_string($toResolve)) {
            $resolved = $this->resolveSlimNotation($toResolve);
            $resolved[1] = $resolved[1] ?? '__invoke';
        }
        $callable = $this->assertCallable($resolved, $toResolve);
        return $this->bindToContainer($callable);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveRoute($toResolve): callable
    {
        return $this->resolveByPredicate($toResolve, [$this, 'isRoute'], 'handle');
    }

    /**
     * {@inheritdoc}
     */
    public function resolveMiddleware($toResolve): callable
    {
        return $this->resolveByPredicate($toResolve, [$this, 'isMiddleware'], 'process');
    }

    /**
     * @param string|callable $toResolve
     * @param callable        $predicate
     * @param string          $defaultMethod
     *
     * @throws RuntimeException
     *
     * @return callable
     */
    private function resolveByPredicate($toResolve, callable $predicate, string $defaultMethod): callable
    {
        if (is_callable($toResolve)) {
            return $this->bindToContainer($toResolve);
        }
        $resolved = $toResolve;
        if ($predicate($toResolve)) {
            $resolved = [$toResolve, $defaultMethod];
        }
        if (is_string($toResolve)) {
            [$instance, $method] = $this->resolveSlimNotation($toResolve);
            if ($predicate($instance) && $method === null) {
                $method = $defaultMethod;
            }
            $resolved = [$instance, $method ?? '__invoke'];
        }
        $callable = $this->assertCallable($resolved, $toResolve);
        return $this->bindToContainer($callable);
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

    /**
     * @param string $toResolve
     *
     * @throws RuntimeException
     *
     * @return array [Instance, Method Name]
     */
    private function resolveSlimNotation(string $toResolve): array
    {
        preg_match(CallableResolver::$callablePattern, $toResolve, $matches);
        [$class, $method] = $matches ? [$matches[1], $matches[2]] : [$toResolve, null];

        if ($this->container && $this->container->has($class)) {
            $instance = $this->container->get($class);
        } else {
            if (!class_exists($class)) {
                throw new RuntimeException(sprintf('Callable %s does not exist', $class));
            }
            $instance = new $class($this->container);
        }
        return [$instance, $method];
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
