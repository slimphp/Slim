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
use \Slim\Interfaces\RouteInterface;

/**
 * Route
 *
 * This class is a relationship of HTTP method(s), an HTTP URI, and a callback
 * to create a Slim application route. The Slim application will determine
 * the one Route object to dispatch for the current HTTP request.
 *
 * Each route object will have a URI pattern. This pattern must match the
 * current HTTP request's URI for the route object to be dispatched by
 * the Slim application. The route pattern may contain parameters, segments
 * prefixed with a colon (:). For example:
 *
 *     /hello/:first/:last
 *
 * When the route is dispatched, it's parameters array will be populated
 * with the values of the corresponding HTTP request URI segments.
 *
 * Each route object may also be assigned middleware; middleware are callbacks
 * to be invoked before the route's callable is invoked. Route middleware (not
 * to be confused with Slim application middleware) are useful for applying route
 * specific logic such as authentication.
 */
class Route
{
    /**
     * HTTP methods supported by this route
     *
     * @var string[]
     */
    protected $methods = array();

    /**
     * The route pattern (e.g. "/hello/:first/:name")
     *
     * @var string
     */
    protected $pattern;

    /**
     * The route callable
     *
     * @var callable
     */
    protected $callable;

    /**
     * Middleware to be invoked before immediately before this route is dispatched
     *
     * @var callable[]
     */
    protected $middleware = array();

    /**
     * Create new route
     *
     * @param string   $pattern       The Route pattern
     * @param callable $callable      The Route callable
     * @param bool     $caseSensitive Is the Route path case-sensitive?
     */
    public function __construct($methods, $pattern, $callable)
    {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->setCallable($callable);
    }

    /**
     * Set route callable
     *
     * @param  string|callable           $callable
     * @throws \InvalidArgumentException If argument is not callable
     */
    public function setCallable($callable)
    {
        $matches = array();
        if (is_string($callable) && preg_match('!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!', $callable, $matches)) {
            $class = $matches[1];
            $method = $matches[2];
            $callable = function() use ($class, $method) {
                static $obj = null;
                if ($obj === null) {
                    if (!class_exists($class)) {
                        throw new \InvalidArgumentException('Route callable class does not exist');
                    }
                    $obj = new $class;
                }
                if (!method_exists($obj, $method)) {
                    throw new \InvalidArgumentException('Route callable method does not exist');
                }
                return call_user_func_array(array($obj, $method), func_get_args());
            };
        }

        if (!is_callable($callable)) {
            throw new \InvalidArgumentException('Route callable must be callable');
        }

        $this->callable = $callable;
    }

    /**
     * Set Route middleware
     *
     * This method allows middleware to be assigned to a specific Route.
     * If the method argument `is_callable` (including callable arrays!),
     * we directly append the argument to `$this->middleware`. Else, we
     * assume the argument is an array of callables and merge the array
     * with `$this->middleware`.  Each middleware is checked for is_callable()
     * and an InvalidArgumentException is thrown immediately if it isn't.
     *
     * @param  callable|callable[]
     * @return self
     * @throws \InvalidArgumentException If argument is not callable or not an array of callables.
     */
    public function setMiddleware($middleware)
    {
        if (is_callable($middleware)) {
            $this->middleware[] = $middleware;
        } elseif (is_array($middleware)) {
            foreach ($middleware as $callable) {
                if (!is_callable($callable)) {
                    throw new \InvalidArgumentException('All Route middleware must be callable');
                }
            }
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            throw new \InvalidArgumentException('Route middleware must be callable or an array of callables');
        }

        return $this;
    }

    /**
     * Dispatch route callable against current Request and Response objects
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * @param  RequestInterface  $request  The current Request object
     * @param  ResponseInterface $response The current Response object
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response)
    {
        // Invoke route middleware
        foreach ($this->middleware as $mw) {
            $newResponse = call_user_func_array($mw, [$request, $response, $this]);
            if ($newResponse instanceof ResponseInterface) {
                $response = $newResponse;
            }
        }

        // Invoke route callable
        try {
            ob_start();
            $newResponse = call_user_func_array($this->callable, [$request, $response, $this]);
            $output = ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }

        // End if route callback returns Interfaces\Http\ResponseInterface object
        if ($newResponse instanceof ResponseInterface) {
            return $newResponse;
        }

        // Else append output buffer content
        if ($output) {
            $response->write($output);
        }

        return $response;
    }
}
