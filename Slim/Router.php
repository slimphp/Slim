<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.0.0
 * @package     Slim
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace Slim;

/**
 * Router
 *
 * This class organizes, iterates, and dispatches \Slim\Route objects.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.0.0
 */
class Router implements \Iterator
{
    /**
     * @var string Request URI
     */
    protected $resourceUri;

    /**
     * @var array Lookup hash of all route objects
     */
    protected $routes;

    /**
     * @var array Lookup hash of named route objects, keyed by route name (lazy-loaded)
     */
    protected $namedRoutes;

    /**
     * @var array Array of route objects that match the request URI (lazy-loaded)
     */
    protected $matchedRoutes;

    /**
     * @var mixed Callable to be invoked if no matching route objects are found
     */
    protected $notFound;

    /**
     * @var mixed Callable to be invoked if application error
     */
    protected $error;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->routes = array();
    }

    /**
     * Set Resource URI
     *
     * This method injects the current request's resource URI. This method should be invoked
     * only immediately before router iteration.
     *
     * @param string $uri The request URI
     */
    public function setResourceUri($uri)
    {
        $this->resourceUri = $uri;
    }

    /**
     * Get Current Route object
     * @return \Slim\Route|false
     */
    public function getCurrentRoute()
    {
        $this->getMatchedRoutes(); // <-- Parse if not already parsed

        return $this->current();
    }

    /**
     * Return route objects that match the current request URI
     * @param  bool                 $reload     Should matching routes be re-parsed?
     * @return array[\Slim\Route]
     */
    public function getMatchedRoutes($reload = false)
    {
        if ($reload || is_null($this->matchedRoutes)) {
            $this->matchedRoutes = array();
            foreach ($this->routes as $route) {
                if ($route->matches($this->resourceUri)) {
                    $this->matchedRoutes[] = $route;
                }
            }
        }

        return $this->matchedRoutes;
    }

    /**
     * Map a route object to a callback function
     * @param  string     $pattern      The URL pattern (ie. "/books/:id")
     * @param  mixed      $callable     Anything that returns TRUE for is_callable()
     * @return \Slim\Route
     */
    public function map($pattern, $callable)
    {
        $route = new \Slim\Route($pattern, $callable);
        $this->routes[] = $route;

        return $route;
    }

    /**
     * Get URL for named route
     * @param  string               $name   The name of the route
     * @param  array                Associative array of URL parameter names and replacement values
     * @throws RuntimeException     If named route not found
     * @return string               The URL for the given route populated with provided replacement values
     */
    public function urlFor($name, $params = array())
    {
        if (!$this->hasNamedRoute($name)) {
            throw new \RuntimeException('Named route not found for name: ' . $name);
        }
        $search = array();
        foreach (array_keys($params) as $key) {
            $search[] = '#:' . $key . '\+?(?!\w)#';
        }
        $pattern = preg_replace($search, $params, $this->getNamedRoute($name)->getPattern());

        //Remove remnants of unpopulated, trailing optional pattern segments
        return preg_replace('#\(/?:.+\)|\(|\)#', '', $pattern);
    }

    /**
     * Dispatch route
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * This method is smart about trailing slashes on the route pattern.
     * If the route's pattern is defined with a trailing slash, and if the
     * current request URI does not have a trailing slash but otherwise
     * matches the route's pattern, a Slim_Exception_RequestSlash
     * will be thrown triggering an HTTP 301 Permanent Redirect to the same
     * URI _with_ a trailing slash. This Exception is caught in the
     * `Slim::call` loop. If the route's pattern is defined without a
     * trailing slash, and if the current request URI does have a trailing
     * slash, the route will not be matched and a 404 Not Found
     * response will be sent if no subsequent matching routes are found.
     *
     * @param  \Slim\Route                  $route  The route object
     * @return bool                         Was route callable invoked successfully?
     * @throws \Slim\Exception\RequestSlash
     */
    public function dispatch(\Slim\Route $route)
    {
        if (substr($route->getPattern(), -1) === '/' && substr($this->resourceUri, -1) !== '/') {
            throw new Exception\RequestSlash();
        }

        //Invoke middleware
        foreach ($route->getMiddleware() as $mw) {
            if (is_callable($mw)) {
                call_user_func_array($mw, array($route));
            }
        }

        //Invoke callable
        if (is_callable($route->getCallable())) {
            call_user_func_array($route->getCallable(), array_values($route->getParams()));

            return true;
        }

        return false;
    }

    /**
     * Add named route
     * @param  string            $name   The route name
     * @param  \Slim\Route       $route  The route object
     * @throws \RuntimeException If a named route already exists with the same name
     */
    public function addNamedRoute($name, \Slim\Route $route)
    {
        if ($this->hasNamedRoute($name)) {
            throw new \RuntimeException('Named route already exists with name: ' . $name);
        }
        $this->namedRoutes[(string) $name] = $route;
    }

    /**
     * Has named route
     * @param  string   $name   The route name
     * @return bool
     */
    public function hasNamedRoute($name)
    {
        $this->getNamedRoutes();

        return isset($this->namedRoutes[(string) $name]);
    }

    /**
     * Get named route
     * @param  string           $name
     * @return \Slim\Route|null
     */
    public function getNamedRoute($name)
    {
        $this->getNamedRoutes();
        if ($this->hasNamedRoute($name)) {
            return $this->namedRoutes[(string) $name];
        } else {
            return null;
        }
    }

    /**
     * Get named routes
     * @return \ArrayIterator
     */
    public function getNamedRoutes()
    {
        if (is_null($this->namedRoutes)) {
            $this->namedRoutes = array();
            foreach ($this->routes as $route) {
                if ($route->getName() !== null) {
                    $this->addNamedRoute($route->getName(), $route);
                }
            }
        }

        return new \ArrayIterator($this->namedRoutes);
    }

    /**
     * Register a 404 Not Found callback
     * @param  mixed    $callable   Anything that returns TRUE for is_callable()
     * @return mixed
     */
    public function notFound($callable = null)
    {
        if (is_callable($callable)) {
            $this->notFound = $callable;
        }

        return $this->notFound;
    }

    /**
     * Register a 500 Error callback
     * @param  mixed    $callable   Anything that returns TRUE for is_callable()
     * @return mixed
     */
    public function error($callable = null)
    {
        if (is_callable($callable)) {
            $this->error = $callable;
        }

        return $this->error;
    }

    /**
     * Iterator Interface: Rewind
     */
    public function rewind()
    {
        reset($this->matchedRoutes);
    }

    /**
     * Iterator Interface: Current
     * @return \Slim\Route|false
     */
    public function current()
    {
        return current($this->matchedRoutes);
    }

    /**
     * Iterator Interface: Key
     * @return int|null
     */
    public function key()
    {
        return key($this->matchedRoutes);
    }

    /**
     * Iterator Interface: Next
     */
    public function next()
    {
        next($this->matchedRoutes);
    }

    /**
     * Iterator Interface: Valid
     * @return boolean
     */
    public function valid()
    {
        return $this->current();
    }
}
