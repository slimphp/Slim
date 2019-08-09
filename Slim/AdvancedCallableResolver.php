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
            $resolved = $this->resolveInstanceAndMethod($toResolve);
            if ($resolved[1] === null) {
                $resolved[1] = '__invoke';
            }
        }

        return $this->checkResolvedAndBindContainerIfClosure($resolved, $toResolve);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveRoute($toResolve): callable
    {
        $resolved = $toResolve;

        if (!is_callable($toResolve) && is_string($toResolve)) {
            [$instance, $method] = $this->resolveInstanceAndMethod($toResolve);

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

        return $this->checkResolvedAndBindContainerIfClosure($resolved, $toResolve);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveMiddleware($toResolve): callable
    {
        $resolved = $toResolve;

        if (!is_callable($toResolve) && is_string($toResolve)) {
            [$instance, $method] = $this->resolveInstanceAndMethod($toResolve);

            // For a class that implements MiddlewareInterface, we will call process()
            // if no method has been specified explicitly
            if ($instance instanceof MiddlewareInterface && $method === null) {
                $method = 'process';
            }

            $resolved = [$instance, $method ?? '__invoke'];
        }

        if ($resolved instanceof MiddlewareInterface) {
            $resolved = [$resolved, 'process'];
        }

        return $this->checkResolvedAndBindContainerIfClosure($resolved, $toResolve);
    }

    /**
     * Resolves the given param and if successful returns an instance as well
     * as a method name.
     *
     * @param string $toResolve
     *
     * @return array [Instance, Method Name]
     */
    private function resolveInstanceAndMethod(string $toResolve): array
    {
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

        return [$instance, $method];
    }

    /**
     * @param mixed           $resolved
     * @param string|callable $toResolve
     *
     * @return callable
     */
    private function checkResolvedAndBindContainerIfClosure($resolved, $toResolve): callable
    {
        if (!is_callable($resolved)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_callable($toResolve) || is_array($toResolve) || is_object($toResolve) ?
                    json_encode($toResolve) :
                    $toResolve
            ));
        }

        if ($this->container && $resolved instanceof Closure) {
            $resolved = $resolved->bindTo($this->container);
        }

        return $resolved;
    }
}
