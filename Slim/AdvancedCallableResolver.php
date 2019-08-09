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

final class AdvancedCallableResolver implements AdvancedCallableResolverInterface
{
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
        $resolved = $toResolve;

        if (!is_callable($toResolve) && is_string($toResolve)) {
            $class = $toResolve;
            $instance = null;
            $method = null;

            // Check for Slim callable as `class:method`
            if (preg_match(CallableResolver::$callablePattern, $toResolve, $matches)) {
                $class = $matches[1];
                $method = $matches[2];
            }

            if ($this->container && $this->container->has($class)) {
                $instance = $this->container->get($class);
            } else {
                if (!class_exists($class)) {
                    throw new RuntimeException(sprintf('Callable %s does not exist', $class));
                }
                $instance = new $class($this->container);
            }

            $resolved = [$instance, $method ?? '__invoke'];
        }

        if (!is_callable($resolved)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_array($toResolve) || is_object($toResolve) ? json_encode($toResolve) : $toResolve
            ));
        }

        if ($this->container && $resolved instanceof Closure) {
            $resolved = $resolved->bindTo($this->container);
        }

        return $resolved;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveRoute($toResolve): callable
    {
        $resolved = $toResolve;

        if (!is_callable($toResolve) && is_string($toResolve)) {
            $class = $toResolve;
            $instance = null;
            $method = null;

            // Check for Slim callable as `class:method`
            if (preg_match(CallableResolver::$callablePattern, $toResolve, $matches)) {
                $class = $matches[1];
                $method = $matches[2];
            }

            if ($this->container && $this->container->has($class)) {
                $instance = $this->container->get($class);
            } else {
                if (!class_exists($class)) {
                    throw new RuntimeException(sprintf('Callable %s does not exist', $class));
                }
                $instance = new $class($this->container);
            }

            // For a class that implements RequestHandlerInterface, we will call handle()
            // if no method has been specified explicitly
            if ($instance instanceof RequestHandlerInterface && $method === null) {
                $method = 'handle';
            }

            $resolved = [$instance, $method ?? '__invoke'];
        }

        if ($resolved instanceof RequestHandlerInterface) {
            $resolved = [$resolved, 'handle'];
        }

        if (!is_callable($resolved)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_array($toResolve) || is_object($toResolve) ? json_encode($toResolve) : $toResolve
            ));
        }

        if ($this->container && $resolved instanceof Closure) {
            $resolved = $resolved->bindTo($this->container);
        }

        return $resolved;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveMiddleware($toResolve): callable
    {
        $resolved = $toResolve;

        if (!is_callable($toResolve) && is_string($toResolve)) {
            $class = $toResolve;
            $instance = null;
            $method = null;

            // Check for Slim callable as `class:method`
            if (preg_match(CallableResolver::$callablePattern, $toResolve, $matches)) {
                $class = $matches[1];
                $method = $matches[2];
            }

            if ($this->container && $this->container->has($class)) {
                $instance = $this->container->get($class);
            } else {
                if (!class_exists($class)) {
                    throw new RuntimeException(sprintf('Callable %s does not exist', $class));
                }
                $instance = new $class($this->container);
            }

            if ($instance instanceof MiddlewareInterface && $method === null) {
                $method = 'process';
            }

            $resolved = [$instance, $method ?? '__invoke'];
        }

        if ($resolved instanceof MiddlewareInterface) {
            $resolved = [$resolved, 'process'];
        }

        if (!is_callable($resolved)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_array($toResolve) || is_object($toResolve) ? json_encode($toResolve) : $toResolve
            ));
        }

        if ($this->container && $resolved instanceof Closure) {
            $resolved = $resolved->bindTo($this->container);
        }

        return $resolved;
    }
}
