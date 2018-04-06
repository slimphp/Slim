<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @see      https://github.com/slimphp/Slim
 *
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim;

use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

/**
 * Middleware
 *
 * This is an internal class that enables concentric middleware layers. This
 * class is an implementation detail and is used only inside of the Slim
 * application; it is not visible to—and should not be used by—end users
 */
trait MiddlewareAwareTrait
{
    /**
     * middleware call stack
     *
     * @var callable
     */
    protected $stack = [];

    /**
     * Middleware stack lock
     *
     * @var bool
     */
    protected $middlewareLock = false;

    /**
     * Add middleware
     *
     * This method prepends new middleware to the application middleware stack
     *
     * @param callable $callable Any callable that accepts three arguments:
     *                           1. A Request object
     *                           2. A Response object
     *                           3. A "next" middleware callable
     *
     * @return static
     *
     * @throws RuntimeException         If middleware is added while the stack is dequeuing
     * @throws UnexpectedValueException If the middleware doesn't return a Psr\Http\Message\ResponseInterface
     */
    protected function addMiddleware(callable $callable)
    {
        if ($this->middlewareLock) {
            throw new RuntimeException('Middleware can’t be added once the stack is dequeuing');
        }

        if (empty($this->stack)) {
            $this->seedMiddlewareStack();
        }

        $this->stack[] = $callable;

        return $this;
    }

    /**
     * Seed middleware stack with first callable
     *
     * @param callable $kernel The last item to run as middleware
     *
     * @throws RuntimeException if the stack is seeded more than once
     */
    protected function seedMiddlewareStack(callable $kernel = null)
    {
        if (!empty($this->stack)) {
            throw new RuntimeException('MiddlewareStack can only be seeded once.');
        }
        if (null === $kernel) {
            $kernel = $this;
        }
        $this->stack[] = $kernel;
    }

    protected function prepareStack()
    {
        $handler = array_shift($this->stack);
        if (!empty($this->stack)) {
            if (isset($this->container->get('settings')['middlewareFifo']) && $this->container->get('settings')['middlewareFifo']) {
                $this->stack = array_reverse($this->stack);
            }

            foreach ($this->stack as $callable) {
                $next = $handler;
                $handler = function (
                    ServerRequestInterface $request,
                    ResponseInterface $response
                ) use (
                    $callable,
                    $next
                ) {
                    $result = call_user_func($callable, $request, $response, $next);
                    if (false === $result instanceof ResponseInterface) {
                        throw new UnexpectedValueException(
                            'Middleware must return instance of \Psr\Http\Message\ResponseInterface'
                        );
                    }

                    return $result;
                };
            }
        }

        return $handler;
    }

    /**
     * Call middleware stack
     *
     * @param ServerRequestInterface $request  A request object
     * @param ResponseInterface      $response A response object
     *
     * @return ResponseInterface
     */
    public function callMiddlewareStack(ServerRequestInterface $request, ResponseInterface $response)
    {
        if (empty($this->stack)) {
            $this->seedMiddlewareStack();
        }
        /** @var callable $stack */
        $stack = $this->prepareStack();
        $this->middlewareLock = true;
        $response = $stack($request, $response);
        $this->middlewareLock = false;

        return $response;
    }
}
