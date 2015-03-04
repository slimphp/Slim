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
 * Route
 */
class Route implements RouteInterface
{
    /**
     * HTTP methods supported by this route
     *
     * @var string[]
     */
    protected $methods = [];

    /**
     * Route pattern
     *
     * @var string
     */
    protected $pattern;

    /**
     * Route callable
     *
     * @var callable
     */
    protected $callable;

    /**
     * Middleware to be invoked before the route callable
     *
     * @var callable[]
     */
    protected $middleware = array();

    /**
     * Create new route
     *
     * @param string[] $methods       The route HTTP methods
     * @param string   $pattern       The route pattern
     * @param callable $callable      The route callable
     */
    public function __construct($methods, $pattern, $callable)
    {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->setCallable($callable);
    }

    /**
     * Get route methods
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Get route pattern
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Get route callable
     *
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * Set route callable
     *
     * @param string|callable $callable
     *
     * @throws \InvalidArgumentException If argument is not callable
     */
    protected function setCallable($callable)
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
     *
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
     * @param RequestInterface  $request  The current Request object
     * @param ResponseInterface $response The current Response object
     * @param array             $args     Parsed pattern data
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, array $args)
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
            $newResponse = call_user_func_array($this->callable, [$request, $response, $args]);
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
