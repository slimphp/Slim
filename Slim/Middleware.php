<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

/**
 * Middleware
 *
 * This is an internal class that enables concentric middleware layers. This
 * class is an implementation detail and is used only inside of the Slim
 * application; it is not visible to—and should not be used by—end users.
 */
class Middleware
{
    /**
     * The middleware callable
     *
     * @var callable
     */
    protected $callable;

    /**
     * The _next_ middleware encapsulated by _this_ middleware
     *
     * @var callable
     */
    protected $next;

    /**
     * Create new middleware
     *
     * @param callable $callable
     * @param callable $next
     */
    public function __construct(callable $callable, callable $next)
    {
        $this->callable = $callable;
        $this->next = $next;
    }

    /**
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * @return callable
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * Invoke middleware
     *
     * @param  RequestInterface  $req
     * @param  ResponseInterface $res
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $req, ResponseInterface $res)
    {
        return call_user_func_array($this->callable, [$req, $res, $this->next]);
    }
}
