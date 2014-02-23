<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.5
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
 *
 * @package Slim
 * @author  Josh Lockhart, Thomas Bley
 * @since   1.0.0
 */
class Route implements RouteInterface
{
    /**
     * The route pattern (e.g. "/hello/:first/:name")
     * @var string
     */
    protected $pattern;

    /**
     * The route callable
     * @var mixed
     */
    protected $callable;

    /**
     * Conditions for this route's URL parameters
     * @var array
     */
    protected $conditions = array();

    /**
     * Default conditions applied to all route instances
     * @var array
     */
    protected static $defaultConditions = array();

    /**
     * The name of this route (optional)
     * @var string
     */
    protected $name;

    /**
     * Array of URL parameters
     * @var array
     */
    protected $params = array();

    /**
     * Array of URL parameter names
     * @var array
     */
    protected $paramNames = array();

    /**
     * Array of URL parameter names with + at the end
     * @var array
     */
    protected $paramNamesPath = array();

    /**
     * HTTP methods supported by this route
     * @var array
     */
    protected $methods = array();

    /**
     * Middleware to be invoked before immediately before this route is dispatched
     * @var array[Callable]
     */
    protected $middleware = array();

    /**
     * Whether or not this route should be matched in a case-sensitive manner
     * @var bool
     */
    protected $caseSensitive = true;

    /**
     * Constructor
     * @param  string $pattern  The URL pattern
     * @param  mixed  $callable Anything that returns `true` for `is_callable()`
     * @param bool $caseSensitive Whether or not this route should be matched in a case-sensitive manner
     * @api
     */
    public function __construct($pattern = null, $callable = null, $caseSensitive = true)
    {
        $this->setPattern($pattern);
        $this->setCallable($callable);
        $this->setConditions(self::getDefaultConditions());
        $this->setCaseSensitive($caseSensitive);
    }

    /**
     * Set default route conditions for all routes
     * @param  array $defaultConditions
     * @api
     */
    public static function setDefaultConditions(array $defaultConditions)
    {
        self::$defaultConditions = $defaultConditions;
    }

    /**
     * Get default route conditions for all instances
     * @return array
     * @api
     */
    public static function getDefaultConditions()
    {
        return self::$defaultConditions;
    }

    /**
     * Get route pattern
     * @return string
     * @api
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Set route pattern
     * @param  string $pattern
     * @api
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
    }

    /**
     * Get route callable
     * @return mixed
     * @api
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * Set route callable
     * @param  mixed                     $callable
     * @throws \InvalidArgumentException If argument is not callable
     * @api
     */
    public function setCallable($callable)
    {
        if (! is_null($callable)) {
            $matches = array();
            if (is_string($callable) && preg_match('!^([^\:]+)\:([[:alnum:]]+)$!', $callable, $matches)) {
                $callable = array(new $matches[1], $matches[2]);
            }

            if (!is_callable($callable)) {
                throw new \InvalidArgumentException('Route callable must be callable');
            }

            $this->callable = $callable;
        }
    }

    /**
     * Get route conditions
     * @return array
     * @api
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Set route conditions
     * @param  array $conditions
     * @api
     */
    public function setConditions(array $conditions)
    {
        $this->conditions = $conditions;
    }

    /**
     * Get route name (this may be null if not set)
     * @return string|null
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set route name
     * @param string $name
     * @api
     */
    public function setName($name)
    {
        $this->name = (string)$name;
    }

    /**
     * Get route parameters
     * @return array
     * @api
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set route parameters
     * @param  array $params
     * @api
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * Get route parameter value
     * @param  string                    $index Name of URL parameter
     * @return string
     * @throws \InvalidArgumentException        If route parameter does not exist at index
     * @api
     */
    public function getParam($index)
    {
        if (!isset($this->params[$index])) {
            throw new \InvalidArgumentException('Route parameter does not exist at specified index');
        }

        return $this->params[$index];
    }

    /**
     * Set route parameter value
     * @param  string                    $index     Name of URL parameter
     * @param  mixed                     $value     The new parameter value
     * @return void
     * @throws \InvalidArgumentException            If route parameter does not exist at index
     * @api
     */
    public function setParam($index, $value)
    {
        if (!isset($this->params[$index])) {
            throw new \InvalidArgumentException('Route parameter does not exist at specified index');
        }
        $this->params[$index] = $value;
    }

    /**
     * Whether or not this route should be matched in a case-sensitive manner
     * @param $caseSensitive
     */
    public function setCaseSensitive($caseSensitive)
    {
        $this->caseSensitive = $caseSensitive;
    }

    /**
     * Add supported HTTP methods (this method accepts an unlimited number of string arguments)
     * @api
     */
    public function setHttpMethods()
    {
        $args = func_get_args();
        $this->methods = $args;
    }

    /**
     * Get supported HTTP methods
     * @return array
     * @api
     */
    public function getHttpMethods()
    {
        return $this->methods;
    }

    /**
     * Append supported HTTP methods (this method accepts an unlimited number of string arguments)
     * @api
     */
    public function appendHttpMethods()
    {
        $args = func_get_args();
        $this->methods = array_merge($this->methods, $args);
    }

    /**
     * Append supported HTTP methods (alias for Route::appendHttpMethods)
     * @return \Slim\Route
     * @api
     */
    public function via()
    {
        $args = func_get_args();
        $this->methods = array_merge($this->methods, $args);

        return $this;
    }

    /**
     * Detect support for an HTTP method
     * @param  string $method
     * @return bool
     * @api
     */
    public function supportsHttpMethod($method)
    {
        return in_array($method, $this->methods);
    }

    /**
     * Get middleware
     * @return array[Callable]
     * @api
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Set middleware
     *
     * This method allows middleware to be assigned to a specific Route.
     * If the method argument `is_callable` (including callable arrays!),
     * we directly append the argument to `$this->middleware`. Else, we
     * assume the argument is an array of callables and merge the array
     * with `$this->middleware`.  Each middleware is checked for is_callable()
     * and an InvalidArgumentException is thrown immediately if it isn't.
     *
     * @param  Callable|array[Callable]
     * @return \Slim\Route
     * @throws \InvalidArgumentException If argument is not callable or not an array of callables.
     * @api
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
     * Matches URI?
     *
     * Parse this route's pattern, and then compare it to an HTTP resource URI
     * This method was modeled after the techniques demonstrated by Dan Sosedoff at:
     *
     * http://blog.sosedoff.com/2009/09/20/rails-like-php-url-router/
     *
     * @param  string $resourceUri A Request URI
     * @return bool
     * @api
     */
    public function matches($resourceUri)
    {
        //Convert URL params into regex patterns, construct a regex for this route, init params
        $patternAsRegex = preg_replace_callback(
            '#:([\w]+)\+?#',
            array($this, 'matchesCallback'),
            str_replace(')', ')?', (string)$this->pattern)
        );
        if (substr($this->pattern, -1) === '/') {
            $patternAsRegex .= '?';
        }

        $regex = '#^' . $patternAsRegex . '$#';

        if ($this->caseSensitive === false) {
            $regex .= 'i';
        }

        //Cache URL params' names and values if this route matches the current HTTP request
        if (!preg_match($regex, $resourceUri, $paramValues)) {
            return false;
        }
        foreach ($this->paramNames as $name) {
            if (isset($paramValues[$name])) {
                if (isset($this->paramNamesPath[$name])) {
                    $this->params[$name] = explode('/', urldecode($paramValues[$name]));
                } else {
                    $this->params[$name] = urldecode($paramValues[$name]);
                }
            }
        }

        return true;
    }

    /**
     * Convert a URL parameter (e.g. ":id", ":id+") into a regular expression
     * @param  array  $m URL parameters
     * @return string    Regular expression for URL parameter
     */
    protected function matchesCallback($m)
    {
        $this->paramNames[] = $m[1];
        if (isset($this->conditions[$m[1]])) {
            return '(?P<' . $m[1] . '>' . $this->conditions[$m[1]] . ')';
        }
        if (substr($m[0], -1) === '+') {
            $this->paramNamesPath[$m[1]] = 1;

            return '(?P<' . $m[1] . '>.+)';
        }

        return '(?P<' . $m[1] . '>[^/]+)';
    }

    /**
     * Set route name
     * @param  string      $name The name of the route
     * @return \Slim\Route
     * @api
     */
    public function name($name)
    {
        $this->setName($name);

        return $this;
    }

    /**
     * Merge route conditions
     * @param  array       $conditions Key-value array of URL parameter conditions
     * @return \Slim\Route
     * @api
     */
    public function conditions(array $conditions)
    {
        $this->conditions = array_merge($this->conditions, $conditions);

        return $this;
    }

    /**
     * Dispatch route
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * @return bool
     * @api
     */
    public function dispatch()
    {
        foreach ($this->middleware as $mw) {
            call_user_func_array($mw, array($this));
        }

        $return = call_user_func_array($this->getCallable(), array_values($this->getParams()));
        return ($return === false) ? false : true;
    }
}
