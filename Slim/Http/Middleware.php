<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware
 *
 * This class serves as a helper class for writing middleware. The call to the
 * next middleware is abstracted away and two new methods, before() and
 * after(), are exposed to better separate code that operates before the next
 * callable is called and after the next callable is called.
 */
abstract class Middleware {

    /**
     * Invokation that will run with the class is implemented as a callable
     * 
     * @param  RequestInterface  $request  The immutable Request
     * @param  ResponseInterface $response The immutable Response
     * @param  callable          $next     The next callable in the middleware
     *     stack
     * @return ResponseInterface
     */
    final public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $this->before($request, $response);

        $this->response = $next($request, $response);
    
        $response = $this->after($request, $response);

        return $response;
    }

    /**
     * Handle any actions that should happen before the next callable is called.
     *
     * The method MUST return an array containing the altered RequestInterface
     * and ResponseInterface objects.
     * 
     * @param  RequestInterface  $request  The immutable Request
     * @param  ResponseInterface $response The immutable Response
     * @return array                       An array containing the latest
     *     RequestInterface and ResponseInterface objects, in that order
     */
    public function before(RequestInterface $request, ResponseInterface $response)
    {
        return [$request, $response];
    }

    /**
     * Handle any actions that should happen after the next callable is called.
     *
     * The method MUST return the altered ResponseInterface object.
     * 
     * @param  RequestInterface  $request  The immutable Request
     * @param  ResponseInterface $response The immutable Response
     * @return ResponseInterface           The latest ResponseInterface object
     */
    public function after(RequestInterface $request, ResponseInterface $response)
    {
        return $response;
    }

}