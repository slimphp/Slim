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
class Route implements RouteInterface
{
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
     * Conditions for this route's URL parameters
     *
     * @var array
     */
    protected $conditions = array();

    /**
     * Default conditions applied to all route instances
     *
     * @var array
     */
    protected static $defaultConditions = array();

    /**
     * The name of this route (optional)
     *
     * @var null|string
     */
    protected $name;

    /**
     * Array of URL parameters
     *
     * @var array
     */
    protected $params = array();

    /**
     * Array of URL parameter names
     *
     * @var array
     */
    protected $paramNames = array();

    /**
     * Array of URL parameter names with + at the end
     *
     * @var array
     */
    protected $paramNamesPath = array();

    /**
     * HTTP methods supported by this route
     *
     * @var string[]
     */
    protected $methods = array();

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
    public function __construct($pattern, $callable, $caseSensitive = true)
    {
        $this->setPattern($pattern);
        $this->setCallable($callable);
        $this->setConditions(self::getDefaultConditions());
        $this->caseSensitive = $caseSensitive;
    }

    /**
     * Set default Route conditions (applies to _all_ Route objects)
     *
     * @param array $defaultConditions
     */
    public static function setDefaultConditions(array $defaultConditions)
    {
        self::$defaultConditions = $defaultConditions;
    }

    /**
     * Get default route conditions
     *
     * @return array
     */
    public static function getDefaultConditions()
    {
        return self::$defaultConditions;
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
     * Set route pattern
     *
     * @param string $pattern
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
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
                    $obj = new $class;
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
     * Get route conditions
     *
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Set route conditions
     *
     * @param array $conditions
     */
    public function setConditions(array $conditions)
    {
        $this->conditions = $conditions;
    }

    /**
     * Get route name (this may be null if not set)
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set route name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = (string)$name;
    }

    /**
     * Get route parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set route parameters
     *
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * Get route parameter value
     *
     * @param  string                    $index Name of URL parameter
     * @return string
     * @throws \InvalidArgumentException        If route parameter does not exist at index
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
     *
     * @param  string                    $index Name of URL parameter
     * @param  mixed                     $value The new parameter value
     * @return void
     * @throws \InvalidArgumentException If route parameter does not exist at index
     */
    public function setParam($index, $value)
    {
        if (!isset($this->params[$index])) {
            throw new \InvalidArgumentException('Route parameter does not exist at specified index');
        }
        $this->params[$index] = $value;
    }

    /**
     * Set supported HTTP methods
     *
     * @param array $methods
     */
    public function setHttpMethods(array $methods)
    {
        $this->methods = $methods;
    }

    /**
     * Get supported HTTP methods
     *
     * @return array
     */
    public function getHttpMethods()
    {
        return $this->methods;
    }

    /**
     * Append supported HTTP methods
     *
     * @param array $methods
     */
    public function appendHttpMethods(array $methods)
    {
        $this->methods = array_merge($this->methods, $methods);
    }

    /**
     * Append supported HTTP methods
     *
     * @param  array $methods
     * @return self
     * @see    appendHttpMethods()
     */
    public function via(array $methods)
    {
        $this->appendHttpMethods($methods);

        return $this;
    }

    /**
     * Does this route answer for a given HTTP method?
     *
     * @param  string $method
     * @return bool
     */
    public function supportsHttpMethod($method)
    {
        return in_array($method, $this->methods);
    }

    /**
     * Get Route middleware
     *
     * @return callable[]
     */
    public function getMiddleware()
    {
        return $this->middleware;
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
     * Does this Route's pattern match a given request Uri path?
     *
     * Parse this route's pattern, and then compare it to an HTTP resource URI
     * This method was modeled after the techniques demonstrated by Dan Sosedoff at:
     *
     * @param  string $uriPath A Request Uri path
     * @return bool
     */
    public function matches($uriPath)
    {
        // Convert URL params into regex patterns, construct a regex for this route, init params
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
        if (!preg_match($regex, $uriPath, $paramValues)) {
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
     * Convert a URL parameter (e.g., ":id" or ":id+") into a regular expression
     *
     * @param  array  $matches URL parameter match
     * @return string          Regular expression string for URL parameter
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
     *
     * @param  string $name The name of the route
     * @return self
     */
    public function name($name)
    {
        $this->setName($name);

        return $this;
    }

    /**
     * Merge route conditions
     *
     * @param  array $conditions Key-value array of URL parameter conditions
     * @return self
     */
    public function conditions(array $conditions)
    {
        $this->conditions = array_merge($this->conditions, $conditions);

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
    public function dispatch(RequestInterface $request, ResponseInterface $response)
    {
        // Invoke route middleware
        foreach ($this->middleware as $mw) {
            $newResponse = call_user_func_array($mw, [$request, $response, $this]);
            if ($newResponse instanceof Interfaces\Http\ResponseInterface) {
                $response = $newResponse;
            }
        }

        // Inject route parameters into Request object as attributes
        $request = $request->withAttributes($this->getParams());

        // Invoke route callable
        ob_start();
        $newResponse = call_user_func_array($this->getCallable(), [$request, $response, $this]);
        $output = ob_get_clean();

        // End if route callback returns Interfaces\Http\ResponseInterface object
        if ($newResponse instanceof Interfaces\Http\ResponseInterface) {
            return $newResponse;
        }

        // Else append output buffer content
        if ($output) {
            $response->write($output);
        }

        return $response;
    }
}
