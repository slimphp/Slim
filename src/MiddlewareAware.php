<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware
 *
 * This is an internal class that enables concentric middleware layers. This
 * class is an implementation detail and is used only inside of the Slim
 * application; it is not visible to—and should not be used by—end users.
 */
trait MiddlewareAware
{
    /**
     * Middleware call stack
     *
     * @var  \SplStack
     * @link http://php.net/manual/class.splstack.php
     */
    protected $stack;

    /**
     * Add middleware
     *
     * This method prepends new middleware to the application middleware stack.
     *
     * @param callable $newMiddleware Any callable that accepts three arguments:
     *                                1. A Request object
     *                                2. A Response object
     *                                3. A "next" middleware callable
     */
    public function add(callable $callable)
    {
        if (is_null($this->stack)) {
            $this->seedMiddlewareStack();
        }
        $next = $this->stack->top();
        $this->stack[] = function (RequestInterface $req, ResponseInterface $res) use ($callable, $next) {
            $result = $callable($req, $res, $next);
            if ($result instanceof ResponseInterface === false) {
                throw new \RuntimeException('Middleware must return instance of \Psr\Http\Message\ResponseInterface');
            }

            return $result;
        };
    }

    /**
     * Seed middleware stack with first callable
     */
    protected function seedMiddlewareStack()
    {
        $this->stack = new \SplStack;
        $this->stack->setIteratorMode(\SplDoublyLinkedList::IT_MODE_LIFO | \SplDoublyLinkedList::IT_MODE_KEEP);
        $this->stack[] = $this;
    }

    /**
     * Call middleware stack
     *
     * @param  RequestInterface  $req A request object
     * @param  ResponseInterface $res A response object
     *
     * @return ResponseInterface
     */
    public function callMiddlewareStack(RequestInterface $req, ResponseInterface $res)
    {
        $start = $this->stack->top();

        return $start($req, $res);
    }
}
