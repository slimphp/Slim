<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim;

use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware
 *
 * This is an internal class that enables concentric middleware layers. This
 * class is an implementation detail and is used only inside of the Slim
 * application; it is not visible to—and should not be used by—end users.
 */
trait MiddlewareAwareTrait
{
    /**
     * Middleware call stack
     *
     * @var  \Slim\Stack\Stack
     */
    protected $stack;

    /**
     * Add middleware
     *
     * This method prepends new middleware to the application middleware stack.
     *
     * @param callable $callable Any callable that accepts three arguments:
     *                           1. A Request object
     *                           2. A Response object
     *                           3. A "next" middleware callable
     * @return static
     *
     * @throws RuntimeException         If middleware is added while the stack is dequeuing
     */
    protected function addMiddleware(callable $callable)
    {
        if (is_null($this->stack)) {
            $this->seedMiddlewareStack();
        }

        $this->stack->add($callable);

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
        if (!is_null($this->stack)) {
            throw new RuntimeException('MiddlewareStack can only be seeded once.');
        }
        if ($kernel === null) {
            $kernel = $this;
        }

        $this->stack = new Stack\Stack($kernel);
    }

    /**
     * Call middleware stack
     *
     * @param  ServerRequestInterface $request A request object
     * @param  ResponseInterface      $response A response object
     *
     * @return ResponseInterface
     */
    public function callMiddlewareStack(ServerRequestInterface $request, ResponseInterface $response)
    {
        if (is_null($this->stack)) {
            $this->seedMiddlewareStack();
        }

        return $this->stack->run($request, $response);
    }
}
