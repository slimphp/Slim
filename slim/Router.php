<?php
/**
 * Slim
 *
 * A simple PHP framework for PHP 5 or newer
 *
 * @author		Josh Lockhart <info@joshlockhart.com>
 * @link		http://slim.joshlockhart.com
 * @copyright	2010 Josh Lockhart
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

/**
 * Slim Router
 *
 * The Router is responsible for registering route paths with associated
 * callback functions. When a Slim application is run, the Router is then
 * responsible for matching a registered route with the current HTTP request,
 * and if a matching route is found, executing the associated callback function
 * using any URL parameters in the request URI.
 *
 * @author Josh Lockhart <info@joshlockhart.com>
 * @since Version 1.0
 */
class Router {
	
	/**
	 * @var Request
	 */
	private $request;
	
	/**
	 * @var array Routes that are registered with the Router
	 */
	private $routes;
	
	/**
	 * @var Route The Route that matches the current HTTP request, or NULL
	 */
	private $matchedRoute;
	
	/**
	 * @var mixed 404 Not Found callback function if a matching route is not found
	 */
	private $notFound;
	
	/**
	 * @var array Paramter names and values extracted from the resource URI for the matching route
	 */
	private $params;
	
	/**
	 * Constructor
	 *
	 * @param Request $request The current HTTP request object
	 */
	public function __construct( Request $request ) {
		$this->request = $request;
		$this->routes = array();
		$this->params = array();
	}
	
	/**
	 * Map a route to a callback function
	 *
	 * @param string $pattern The URL pattern (ie. "/books/:id")
	 * @param mixed $callable Anything that returns TRUE for is_callable()
	 * @param string $method The HTTP request method (GET, POST, PUT, DELETE)
	 */
	public function map( $pattern, $callable, $method ) {
		$route = new Route( $pattern, $method, $this->request, $callable );
		if( $route->matched() ) {
			$this->matchedRoute = $route;
			$this->params = $route->params();
		}
		$this->routes[$method][] = $route;
	}
	
	/**
	 * Register a 404 Not Found callback
	 *
	 * @param mixed $callable Anything that returns TRUE for is_callable()
	 * @return mixed
	 */
	public function notFound( $callable = null ) {
		if( is_callable($callable) ) {
			$this->notFound = $callable;
		}
		return $this->notFound;
	}
	
	/**
	 * Dispatch request
	 *
	 * @return true|false TRUE if matching route is found and callable, else FALSE
	 */
	public function dispatch() {
		if( !is_null($this->matchedRoute) ) {
			$callable = $this->matchedRoute->callable();
			if( is_callable($callable) ) {
				call_user_func_array($callable, array_values($this->params));
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * Return the parameter names and values for this Route
	 *
	 * @return array
	 */
	public function params() {
		return $this->params;
	}

	/**
	 * Return a parameter or default if needed
	 *
	 * @return string
	 */
	public function param($key, $default = NULL) {
		if (isset($this->params[$key])) {
			return $this->params[$key];
		}
		return $default;
	}
}

/**
 * Slim Route
 *
 * @author Josh Lockhart <info@joshlockhart.com>
 * @since Version 1.0
 */
class Route {
	
	/**
	 * @var bool Does this route match the current HTTP request?
	 */
	private $matched;
	
	/**
	 * @var string The route pattern (ie. "/books/:id")
	 */
	private $pattern;
	
	/**
	 * @var string The HTTP request method required for this route (GET, POST, PUT, DELETE)
	 */
	private $method;
	
	/**
	 * @var Request
	 */
	private $request;
	
	/**
	 * @var array Conditions for this route's URL parameters (not implemented yet)
	 */
	private $conditions;
	
	/**
	 * @var array Array of URL parameter names and values
	 */
	private $params;
	
	/**
	 * @var mixed The callable associated with this route
	 */
	private $callable;
	
	/**
	 * Constructor
	 *
	 * This method was modeled after the techniques demonstrated by
	 * Dan Sosedoff at:
	 *
	 * http://blog.sosedoff.com/2009/09/20/rails-like-php-url-router/
	 *
	 * @param string $pattern The URL pattern (ie. "/books/:id")
	 * @param string $method The HTTP request method required for this route
	 * @param Request $request The current Request object
	 * @param mixed $callable Anything that returns TRUE for is_callable()
	 * @param array $conditions Conditions for the route pattern parameters (not implemented yet)
	 */
	public function __construct( $pattern, $method, $request, $callable, $conditions = array() ) {
		
		$this->pattern = $pattern;
		$this->method = $method;
		$this->request = $request;
		$this->callable = $callable;
		$this->conditions = $conditions;
		$this->params = array();
		$this->matched = false;
		
		// If pattern not an array, make it an array to work in foreach
		if ( !is_array($this->pattern) ) {
			$this->pattern = array($this->pattern);
		}
		
		foreach ($this->pattern as $_pattern) {
			
			// Pattern
			$_pattern = ltrim($_pattern, '/');
			
			//Extract URL params
			preg_match_all('@:([\w]+)@', $_pattern, $paramNames, PREG_PATTERN_ORDER);
			$paramNames = $paramNames[0];
	
			//Convert URL params into regex patterns, construct a regex for this route
			$patternAsRegex = preg_replace_callback('@:[\w]+@', array($this, 'convertPatternToRegex'), $_pattern);
			$patternAsRegex = '@^' . $patternAsRegex . '$@';
	
			//Cache URL params' names and values if this route matches the current HTTP request
			if( $this->method == $this->request->method && preg_match($patternAsRegex, $this->request->resource, $paramValues) ) {
				array_shift($paramValues);
				foreach( $paramNames as $index => $value ) {
					$this->params[substr($value, 1)] = urldecode($paramValues[$index]);
				}
				$this->matched = true;
				break;
			}
		
		}
		
	}
	
	/**
	 * Convert a URL parameter (ie. ":id") into a regular expression
	 *
	 * @param array Array of URL params
	 * @return string Regex for specific URL param's name
	 */
	private function convertPatternToRegex($matches) {
		$key = str_replace(':', '', $matches[0]);
		if( array_key_exists($key, $this->conditions) ) {
			return '(' . $this->conditions[$key] . ')';
		} else {
			return '([a-zA-Z0-9_\+\-%]+)';
		}
	}
	
	/**
	 * Does this Route match the current request?
	 *
	 * @return true|false
	 */
	public function matched() {
		return $this->matched;
	}
	
	/**
	 * Return the callable for this Route
	 *
	 * @return mixed
	 */
	public function callable() {
		return $this->callable;
	}
	
	/**
	 * Return the parameter names and values for this Route
	 *
	 * @return array
	 */
	public function params() {
		return $this->params;
	}
}

?>