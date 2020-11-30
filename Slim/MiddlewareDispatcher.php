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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Interfaces\AdvancedCallableResolverInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\MiddlewareDispatcherInterface;

use function class_exists;
use function function_exists;
use function is_callable;
use function is_string;
use function preg_match;
use function sprintf;

class MiddlewareDispatcher implements MiddlewareDispatcherInterface
{
    /**
     * Tip of the middleware call stack
     *
     * @var RequestHandlerInterface
     */
    protected $tip;

    /**
     * @var CallableResolverInterface|null
     */
    protected $callableResolver;

    /**
     * @var ContainerInterface|null
     */
    protected $container;

    /**
     * @param RequestHandlerInterface        $kernel
     * @param CallableResolverInterface|null $callableResolver
     * @param ContainerInterface|null        $container
     */
    public function __construct(
        RequestHandlerInterface $kernel,
        ?CallableResolverInterface $callableResolver = null,
        ?ContainerInterface $container = null
    ) {
        $this->seedMiddlewareStack($kernel);
        $this->callableResolver = $callableResolver;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function seedMiddlewareStack(RequestHandlerInterface $kernel): void
    {
        $this->tip = $kernel;
    }

    /**
     * Invoke the middleware stack
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->tip->handle($request);
    }

    /**
     * Add a new middleware to the stack
     *
     * Middleware are organized as a stack. That means middleware
     * that have been added before will be executed after the newly
     * added one (last in, first out).
     *
     * @param MiddlewareInterface|string|callable $middleware
     * @return MiddlewareDispatcherInterface
     */
    public function add($middleware): MiddlewareDispatcherInterface
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $this->addMiddleware($middleware);
        }

        if (is_string($middleware)) {
            return $this->addDeferred($middleware);
        }

        if (is_callable($middleware)) {
            return $this->addCallable($middleware);
        }

        /** @phpstan-ignore-next-line */
        throw new RuntimeException(
            'A middleware must be an object/class name referencing an implementation of ' .
            'MiddlewareInterface or a callable with a matching signature.'
        );
    }

    /**
     * Add a new middleware to the stack
     *
     * Middleware are organized as a stack. That means middleware
     * that have been added before will be executed after the newly
     * added one (last in, first out).
     *
     * @param MiddlewareInterface $middleware
     * @return MiddlewareDispatcherInterface
     */
    public function addMiddleware(MiddlewareInterface $middleware): MiddlewareDispatcherInterface
    {
        $next = $this->tip;
        $this->tip = new class ($middleware, $next) implements RequestHandlerInterface {
            /**
             * @var MiddlewareInterface
             */
            private $middleware;

            /**
             * @var RequestHandlerInterface
             */
            private $next;

            public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $next)
            {
                $this->middleware = $middleware;
                $this->next = $next;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->middleware->process($request, $this->next);
            }
        };

        return $this;
    }

    /**
     * Add a new middleware by class name
     *
     * Middleware are organized as a stack. That means middleware
     * that have been added before will be executed after the newly
     * added one (last in, first out).
     *
     * @param string $middleware
     * @return self
     */
    public function addDeferred(string $middleware): self
    {
        $next = $this->tip;
        $this->tip = new class (
            $middleware,
            $next,
            $this->container,
            $this->callableResolver
        ) implements RequestHandlerInterface {
            /**
             * @var string
             */
            private $middleware;

            /**
             * @var RequestHandlerInterface
             */
            private $next;

            /**
             * @var ContainerInterface|null
             */
            private $container;

            /**
             * @var CallableResolverInterface|null
             */
            private $callableResolver;

            public function __construct(
                string $middleware,
                RequestHandlerInterface $next,
                ?ContainerInterface $container = null,
                ?CallableResolverInterface $callableResolver = null
            ) {
                $this->middleware = $middleware;
                $this->next = $next;
                $this->container = $container;
                $this->callableResolver = $callableResolver;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if ($this->callableResolver instanceof AdvancedCallableResolverInterface) {
                    $callable = $this->callableResolver->resolveMiddleware($this->middleware);
                    return $callable($request, $this->next);
                }

                $callable = null;

                if ($this->callableResolver instanceof CallableResolverInterface) {
                    try {
                        $callable = $this->callableResolver->resolve($this->middleware);
                    } catch (RuntimeException $e) {
                        // Do Nothing
                    }
                }

                if (!$callable) {
                    $resolved = $this->middleware;
                    $instance = null;
                    $method = null;

                    // Check for Slim callable as `class:method`
                    if (preg_match(CallableResolver::$callablePattern, $resolved, $matches)) {
                        $resolved = $matches[1];
                        $method = $matches[2];
                    }

                    if ($this->container && $this->container->has($resolved)) {
                        $instance = $this->container->get($resolved);
                        if ($instance instanceof MiddlewareInterface) {
                            return $instance->process($request, $this->next);
                        }
                    } elseif (!function_exists($resolved)) {
                        if (!class_exists($resolved)) {
                            throw new RuntimeException(sprintf('Middleware %s does not exist', $resolved));
                        }
                        $instance = new $resolved($this->container);
                    }

                    if ($instance && $instance instanceof MiddlewareInterface) {
                        return $instance->process($request, $this->next);
                    }

                    $callable = $instance ?? $resolved;
                    if ($instance && $method) {
                        $callable = [$instance, $method];
                    }

                    if ($this->container && $callable instanceof Closure) {
                        $callable = $callable->bindTo($this->container);
                    }
                }

                if (!is_callable($callable)) {
                    throw new RuntimeException(
                        sprintf(
                            'Middleware %s is not resolvable',
                            $this->middleware
                        )
                    );
                }

                return $callable($request, $this->next);
            }
        };

        return $this;
    }

    /**
     * Add a (non standard) callable middleware to the stack
     *
     * Middleware are organized as a stack. That means middleware
     * that have been added before will be executed after the newly
     * added one (last in, first out).
     *
     * @param callable $middleware
     * @return self
     */
    public function addCallable(callable $middleware): self
    {
        $next = $this->tip;

        if ($this->container && $middleware instanceof Closure) {
            /** @var Closure $middleware */
            $middleware = $middleware->bindTo($this->container);
        }

        $this->tip = new class ($middleware, $next) implements RequestHandlerInterface {
            /**
             * @var callable
             */
            private $middleware;

            /**
             * @var RequestHandlerInterface
             */
            private $next;

            public function __construct(callable $middleware, RequestHandlerInterface $next)
            {
                $this->middleware = $middleware;
                $this->next = $next;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->middleware)($request, $this->next);
            }
        };

        return $this;
    }
}
