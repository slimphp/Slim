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
	 * @var array Lookup hash of routes, organized by Request method
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
	 * Constructor
	 *
	 * @param Request $request The current HTTP request object
	 */
	public function __construct( Request $request ) {
		$this->request = $request;
		$this->routes = array(
			'GET' => array(),
			'POST' => array(),
			'PUT' => array(),
			'DELETE' => array()
		);
	}
	
	/**
	 * Map a route to a callback function
	 *
	 * @param string $pattern The URL pattern (ie. "/books/:id")
	 * @param mixed $callable Anything that returns TRUE for is_callable()
	 * @param string $method The HTTP request method (GET, POST, PUT, DELETE)
	 * @return Route
	 */
	public function map( $pattern, $callable, $method ) {
		$route = new Route( $pattern, $callable );
		$this->routes[$method][] = $route;
		return $route;
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
		
		//Iterate over routes for current Request method
		foreach( $this->routes[$this->request->method] as $route ) {
			if ( $route->matches($this->request->resource) ) {
				$this->matchedRoute = $route;
				break;
			}
		}
		
		//If matching route found... else return FALSE
		if ( !is_null($this->matchedRoute) ) {
			$callable = $this->matchedRoute->callable();
			if ( is_callable($callable) ) {
				call_user_func_array($callable, array_values($this->matchedRoute->params()));
				return true;
			}
		}
		
		return false;
		
	}
	
}

?>